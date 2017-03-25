<?php
/**
 * 交易
 */
namespace App\Http\Controllers\Purchase;

use App\DataServer\Front\ActivityApi;
use App\DataServer\Front\BankApi;
use App\DataServer\Front\CouponApi;
use App\DataServer\Front\ProdApi;
use App\DataServer\Front\TradeApi as fTradeApi;
use App\DataServer\TA\AccountApi;
use App\DataServer\TA\PurchaseApi;
use App\DataServer\TA\TADynamicProdApi;
use App\Exceptions\FrontApi\CouponApiException;
use App\Http\Controllers\CommonTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\BalanceRequest;
use App\Http\Requests\Purchase\CancelCFTransferRequest;
use App\Http\Requests\Purchase\CFTransferRequest;
use App\Http\Requests\Purchase\DPBalanceRequest;
use App\Http\Requests\Purchase\DPHybridPaymentRequest;
use App\Http\Requests\Purchase\DPQuickPaymentRequest;
use App\Http\Requests\Purchase\DPRedeemRequest;
use App\Http\Requests\Purchase\EpayConfirmRequest;
use App\Http\Requests\Purchase\EpaySendValidateCodeRequest;
use App\Http\Requests\Purchase\HybridPaymentRequest;
use App\Http\Requests\Purchase\QuickPaymentRequest;
use App\Http\Requests\Purchase\RechargeRequest;
use App\Http\Requests\Purchase\TransferBalanceRequest;
use App\Http\Requests\Purchase\TransferHybridPaymentRequest;
use App\Http\Requests\Purchase\TransferQuickPaymentRequest;
use App\Http\Requests\Purchase\WithdrawRequest;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


class PurchaseController extends Controller
{
    use CommonTrait;
    private $purchaseApi;

    function __construct()
    {
        $this->purchaseApi = new PurchaseApi();
        $this->setBase();
        if (!$this->userBaseInfo['isBindCard']) {
            throw new UnauthorizedHttpException('Basic realm="My Realm"', '用户还没有绑定银行卡');
        }
    }

    /**
     * 充值
     * @param RechargeRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     */
    function recharge(RechargeRequest $request)
    {
        $fee = $request->get('fee', 0);
        $callbackUrl = $request->get('callbackUrl', '');
        $payType = in_array($request->get('payType', 'WAP'), ['ios', 'android']) ? 'MOBILE' : 'WAP';
        //支付渠道 100006 连连支付 100007 易宝支付
        $taApi = new AccountApi();
        $accountInfo = $taApi->getAccountBankInfo($this->account);
        $userBankData = $accountInfo['result']['bankCard'];
        $bankApi = new BankApi();
        $bankInfo = $bankApi->bankInfo($userBankData['bankId']);
        $typePayment = $bankInfo['result']['payChannelAgile'];
        if ($typePayment == '100006' && empty($callbackUrl)) {//连连支付必须回调地址
            return makeFailedMsg(422, 'callbackUrl is required');
        }
        //给出回调地址特征值
        $redirectUrlMark = generateUuid();
        $payRel = $this->purchaseApi->recharge($this->account, $fee, $typePayment, $userBankData['bankId'], $userBankData['cardNo'], $redirectUrlMark, $payType);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . '-' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        } else {
            if ($typePayment == '100006') {
                //将回调页存入缓存一小时
                $this->cacheCallbackUrl($redirectUrlMark, $payRel['tradeID'], $callbackUrl, 0);
            }
            return makeSuccessMsg($payRel);
        }
    }

    /**
     * 定期和众筹的余额支付
     * @param BalanceRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     * @throws \Exception
     */
    function balance(BalanceRequest $request)
    {
        $transProductId = $request->get('transProductId');
        $share = $request->get('share');
        $fee = $request->get('fee');
        $couponArr = trim($request->get('coupon')) ? explode(',', $request->get('coupon')) : [];
        $isDp = $request->get('isDp', 0);
        //订单优惠券逻辑
        $couponApi = new CouponApi();
        try {
            $orderCoupons = $couponApi->checkCouponForPayment($couponArr, $transProductId, $share);
        } catch (CouponApiException $e) {
            return makeFailedMsg($e->getCode(), $e->getMessage());
        }
        //下单
        $payRel = $this->purchaseApi->purchaseForBalance($this->account, $transProductId, $share, $fee, $orderCoupons, $isDp);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . ' - ' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        } else {
            //查询尾单
            $tradeId = $payRel['tradeID'];
            $purchaseApi = new PurchaseApi();
            $isLast = $purchaseApi->purchaseIsLast($tradeId);
            $payRel['isLastOrder'] = $isLast['data']['isTargetTheLastOrder'];
            //尾单抽奖机会
            if ($payRel['isLastOrder']) {
                $activityApi = new ActivityApi();
                $activityApi->accountNewLuckydraw($payRel['account'], 'tail_luckydraw');
            }
            return makeSuccessMsg($payRel);
        }
    }

    /**
     * 活期产品-余额支付
     * @param DPBalanceRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     * @throws \Exception
     */
    function dpBalance(DPBalanceRequest $request)
    {
        if (!$this->checkIsLock()) {
            // 判断活期产品是否处在续标锁定阶段
            return makeFailedMsg(500, '活期续标期间，禁止购买！');
        }

        $isLocked = $request->get('isLocked', 0);//是否是封闭期产品
        $share = $request->get('share');
        $fee = $request->get('fee');
        //下单
        $payRel = $this->purchaseApi->dpPurchaseForBalance($this->account, $share, $fee, [], '1001', $isLocked);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . '-' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        } else {
            return makeSuccessMsg($payRel);
        }
    }

    /**
     * 快捷支付
     * @param QuickPaymentRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\FrontApi\CouponApiException
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     */
    function quickPayment(QuickPaymentRequest $request)
    {
        $transProductId = $request->get('transProductId');
        $share = $request->get('share');
        $fee = $request->get('fee');
        $callbackUrl = $request->get('callbackUrl', '');
        $payType = in_array($request->get('payType', 'WAP'), ['ios', 'android']) ? 'MOBILE' : 'WAP';
        $couponArr = trim($request->get('coupon')) ? explode(',', $request->get('coupon')) : [];
        $isRetender = $request->get('isRetender', 0);

        //订单优惠券逻辑
        $couponApi = new CouponApi();
        try {
            $orderCoupons = $couponApi->checkCouponForPayment($couponArr, $transProductId, $share);
        } catch (CouponApiException $e) {
            return makeFailedMsg($e->getCode(), $e->getMessage());
        }

        //支付渠道 100006 连连支付 100007易宝支付
        $taApi = new AccountApi();
        $accountInfo = $taApi->getAccountBankInfo($this->account);
        $userBankData = $accountInfo['result']['bankCard'];
        $bankApi = new BankApi();
        $bankInfo = $bankApi->bankInfo($userBankData['bankId']);
        $typePayment = $bankInfo['result']['payChannelAgile'];
        if ($typePayment == '100006' && empty($callbackUrl)) {//连连支付必须回调地址
            return makeFailedMsg(422, 'callbackUrl is required');
        }
        //给出回调地址特征值
        $redirectUrlMark = generateUuid();
        $payRel = $this->purchaseApi->purchaseForAsync($this->account, $transProductId, $share, $fee, $typePayment, $userBankData['bankId'], $userBankData['cardNo'], $orderCoupons, $redirectUrlMark, $payType);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . '-' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        } else {
            // 记录交易到trade表中
            // 即使出错，也忽略，不影响正常购买流程
            $tradeApi = new fTradeApi();
            $tradeDetailRel = $tradeApi->userTradeSave($this->account, $payRel['tradeID'], $isRetender, $fee, $share, $transProductId);
            if ($typePayment == '100006') {
                //将回调页存入缓存一小时
                $this->cacheCallbackUrl($redirectUrlMark, $payRel['tradeID'], $callbackUrl);
            }
            return makeSuccessMsg($payRel);
        }
    }

    /**
     * 活期产品-快捷支付
     * @param DPQuickPaymentRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\FrontApi\CouponApiException
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     */
    function dpQuickPayment(DPQuickPaymentRequest $request)
    {
        if (!$this->checkIsLock()) {
            // 判断活期产品是否处在续标锁定阶段
            return makeFailedMsg(500, '活期续标期间，禁止购买！');
        }

        $isLocked = $request->get('isLocked', 0);//是否是封闭期产品
        $share = $request->get('share');
        $fee = $request->get('fee');
        $callbackUrl = $request->get('callbackUrl', '');
        $payType = in_array($request->get('payType', 'WAP'), ['ios', 'android']) ? 'MOBILE' : 'WAP';
        //支付渠道 100006 连连支付 100007易宝支付
        $taApi = new AccountApi();
        $accountInfo = $taApi->getAccountBankInfo($this->account);
        $userBankData = $accountInfo['result']['bankCard'];
        $bankApi = new BankApi();
        $bankInfo = $bankApi->bankInfo($userBankData['bankId']);
        $typePayment = $bankInfo['result']['payChannelAgile'];
        if ($typePayment == '100006' && empty($callbackUrl)) {//连连支付必须回调地址
            return makeFailedMsg(422, 'callbackUrl is required');
        }
        //给出回调地址特征值
        $redirectUrlMark = generateUuid();
        $payRel = $this->purchaseApi->dpPurchaseForAsync($this->account, $share, $fee, $typePayment, $userBankData['bankId'], $userBankData['cardNo'], [], $redirectUrlMark, $payType, $isLocked);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . '-' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        } else {
            if ($typePayment == '100006') {
                //将回调页存入缓存一小时
                $this->cacheCallbackUrl($redirectUrlMark, $payRel['tradeID'], $callbackUrl);
            }
            return makeSuccessMsg($payRel);
        }
    }

    /**
     * 混合支付
     * @param HybridPaymentRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\FrontApi\CouponApiException
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     */
    function hybridPayment(HybridPaymentRequest $request)
    {
        $transProductId = $request->get('transProductId');
        $share = $request->get('share');
        $fee = $request->get('fee');
        $depositFee = $request->get('depositFee');
        $couponArr = trim($request->get('coupon')) ? explode(',', $request->get('coupon')) : [];
        $callbackUrl = $request->get('callbackUrl', '');
        $payType = in_array($request->get('payType', 'WAP'), ['ios', 'android']) ? 'MOBILE' : 'WAP';
        //订单优惠券逻辑
        $couponApi = new CouponApi();
        try {
            $orderCoupons = $couponApi->checkCouponForPayment($couponArr, $transProductId, $share);
        } catch (CouponApiException $e) {
            return makeFailedMsg($e->getCode(), $e->getMessage());
        }
        //支付渠道 100006 连连支付 100007易宝支付
        $taApi = new AccountApi();
        $accountInfo = $taApi->getAccountBankInfo($this->account);
        $userBankData = $accountInfo['result']['bankCard'];
        $bankApi = new BankApi();
        $bankInfo = $bankApi->bankInfo($userBankData['bankId']);
        $typePayment = $bankInfo['result']['payChannelAgile'];
        if ($typePayment == '100006' && empty($callbackUrl)) {//连连支付必须回调地址
            return makeFailedMsg(422, 'callbackUrl is required');
        }
        //给出回调地址特征值
        $redirectUrlMark = generateUuid();
        $payRel = $this->purchaseApi->purchaseForHybrid($this->account, $transProductId, $share, $fee, $depositFee, $typePayment, $userBankData['bankId'], $userBankData['cardNo'], $orderCoupons, $redirectUrlMark, $payType);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . '-' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        } else {
            if ($typePayment == '100006') {
                //将回调页存入缓存一小时
                $this->cacheCallbackUrl($redirectUrlMark, $payRel['tradeID'], $callbackUrl);
            }
            return makeSuccessMsg($payRel);
        }
    }

    /**
     * 活期产品-混合支付
     * @param DPHybridPaymentRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\FrontApi\CouponApiException
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     */
    function dpHybridPayment(DPHybridPaymentRequest $request)
    {
        if (!$this->checkIsLock()) {
            // 判断活期产品是否处在续标锁定阶段
            return makeFailedMsg(500, '活期续标期间，禁止购买！');
        }

        $isLocked = $request->get('isLocked', 0);//是否是封闭期产品
        $share = $request->get('share');
        $fee = $request->get('fee');
        $depositFee = $request->get('depositFee');
        $callbackUrl = $request->get('callbackUrl', '');
        $payType = in_array($request->get('payType', 'WAP'), ['ios', 'android']) ? 'MOBILE' : 'WAP';
        //支付渠道 100006 连连支付 100007易宝支付
        $taApi = new AccountApi();
        $accountInfo = $taApi->getAccountBankInfo($this->account);
        $userBankData = $accountInfo['result']['bankCard'];
        $bankApi = new BankApi();
        $bankInfo = $bankApi->bankInfo($userBankData['bankId']);
        $typePayment = $bankInfo['result']['payChannelAgile'];
        if ($typePayment == '100006' && empty($callbackUrl)) {//连连支付必须回调地址
            return makeFailedMsg(422, 'callbackUrl is required');
        }
        //给出回调地址特征值
        $redirectUrlMark = generateUuid();
        $payRel = $this->purchaseApi->dpPurchaseForHybrid($this->account, $share, $fee, $depositFee, $typePayment, $userBankData['bankId'], $userBankData['cardNo'], [], $redirectUrlMark, $payType, $isLocked);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . '-' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        } else {
            if ($typePayment == '100006') {
                //将回调页存入缓存一小时
                $this->cacheCallbackUrl($redirectUrlMark, $payRel['tradeID'], $callbackUrl);
            }
            return makeSuccessMsg($payRel);
        }
    }

    /**
     * 易宝支付-验证短信验证码
     * @param EpayConfirmRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     */
    function epayConfirm(EpayConfirmRequest $request)
    {
        $orderId = $request->get('orderId');
        $validateCode = $request->get('validateCode');
        $valRel = $this->purchaseApi->epayConfirm($orderId, $validateCode);
        if ($valRel['stateCode'] == '600116') {
            return makeFailedMsg(406, $valRel['message']);
        } elseif ($valRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $valRel['stateCode'] . '-' . $valRel['message']);
        } else {
            $responseData = $valRel['result'];
            //查询订单详情
            $orderIdStr = $responseData['orderid'];
            $orderIdArr = explode('|', $orderIdStr);
            $orderTrueId = $orderIdArr[1];
            $purchaseDetail = $this->purchaseApi->purchaseDetail($orderTrueId);
            $purchaseDetail = $purchaseDetail['data'];
            //如果是下单购买，则查询尾单
            if ($purchaseDetail['reqInterface'] == 'PURCHASE') {
                $isLast = $this->purchaseApi->purchaseIsLast($purchaseDetail['tradeId']);
                if ($isLast['stateCode'] != '00000') {
                    return makeFailedMsg('501', '下单成功，但查询尾单信息失败:' . $isLast['message']);
                }
                $responseData['isLastOrder'] = $isLast['data']['isTargetTheLastOrder'];
            }
            return makeSuccessMsg($responseData);
        }
    }

    /**
     * 易宝支付获取订单支付状态
     * @param $orderId
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     */
    function epayQueryOrder($orderId)
    {
        $queryRel = $this->purchaseApi->epayQueryOrder($orderId);
        if ($queryRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $queryRel['message']);
        } else {
            $responseData = [
                'payStatus' => $queryRel['result']['status']
            ];
            return makeSuccessMsg($responseData);
        }

    }


    /**
     * 易宝支付-重发短信验证码
     * @param EpaySendValidateCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     */
    function epaySendValidateCode(EpaySendValidateCodeRequest $request)
    {
        $orderId = $request->get('orderId');
        $sentRel = $this->purchaseApi->epaySendValidateCode($orderId);
        if ($sentRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $sentRel['stateCode'] . '-' . $sentRel['message']);
        } else {
            return makeSuccessMsg($sentRel);
        }
    }


    /**
     * 提现
     * @param WithdrawRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\TAApi\PurchaseApiException
     */
    function withdraw(WithdrawRequest $request)
    {
        $fee = $request->get('fee');
        //提现金额最小限制
        $withdrawMinFee = config('sys-config.withdraw_min_fee');
        if ($fee < $withdrawMinFee) {
            return makeFailedMsg(422, '提现金额不能小于' . $withdrawMinFee . '元');
        }
        $taAccountApi = new AccountApi();
        $accountInfo = $taAccountApi->getAccountBankInfo($this->account);
        $userBankData = $accountInfo['result']['bankCard'];
        $withdrawRel = $this->purchaseApi->withdraw($this->account, $fee, $userBankData['cardNo']);
        if ($withdrawRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $withdrawRel['stateCode'] . '-' . $withdrawRel['message'], ['tradeID' => (isset($withdrawRel['tradeID']) ? $withdrawRel['tradeID'] : '')]);
        } else {
            return makeSuccessMsg($withdrawRel);
        }

    }

    /**
     * 活期转让
     * @param DPRedeemRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function dpRedeemAction(DPRedeemRequest $request)
    {
        $redeemShare = $request->get('share');
        $taDPApi = new TADynamicProdApi();
        //验证提交的share是否合法
        $taProdInfoRel = $taDPApi->getDynamicProd();
        if ($taProdInfoRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $taProdInfoRel['message']);
        }
        $taProdInfo = $taProdInfoRel['dpBaseInfo'];
        $dpPrice = $taProdInfo['price'];
        $dpTransStep = $taProdInfo['transStep'];
        $minTransCredit = $taProdInfo['minTransCredit'];
        $redeemCredit = $redeemShare * $dpPrice;
        if ($minTransCredit > $redeemCredit || ((($redeemCredit - $minTransCredit) * 100) % ($dpTransStep * 100))) {
            return makeFailedMsg(501, '提交的share非法');
        }
        //执行转让请求
        $redeemRel = $taDPApi->doRedeem($this->account, $redeemShare);
        if ($redeemRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $redeemRel['message']);
        }
        $responseData = [
            'dpGetTime' => date("Y-m-d", strtotime($redeemRel['dp_redeem_time'] . '+ 2 day')),// 预计到账时间：转让时间 + 2day
            'account' => $redeemRel['account'],
            'share' => $redeemRel['share'],
            'dpRedeemTime' => $redeemRel['dp_redeem_time'],
        ];
        return makeSuccessMsg($responseData);
    }

    /**
     * 保存callbackUrl到缓存
     * @param $redirectUrlMark
     * @param $tradeId
     * @param $reqInterface //0 DEPOSIT 充值，1 PURCHASE 购买
     * @param $callbackUrl
     */
    function cacheCallbackUrl($redirectUrlMark, $tradeId, $callbackUrl, $reqInterface = 1)
    {
        //将回调页存入缓存一小时
        //\Log::debug(config('sys-config.pay_callback_cache_pre').$redirectUrlMark);
        $cacheData = [
            'tradeId' => $tradeId,
            'reqInterface' => $reqInterface,
            'callbackUrl' => $callbackUrl
        ];
        \Cache::put(config('sys-config.pay_callback_cache_pre') . $redirectUrlMark, $cacheData, config('sys-config.pay_callback_cache_time'));
    }

    /**
     * 申请众筹转让
     * @param CFTransferRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cfTransfer(CFTransferRequest $request)
    {
        $share = $request->get('share', 0); // 转让份数
        $fee = $request->get('fee', 0); // 转让金额
        $account = $this->account;
        $tradeId = $request->get('tradeId'); // 由哪个交易派生的 转让（即原订单）

        $purchaseApi = new PurchaseApi();
        $transferRel = $purchaseApi->doCFTransfer($share, $fee, $account, $tradeId);
        if ($transferRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $transferRel['message']);
        }
        $responseData = [
            'tradeId' => $transferRel['tradeID']
        ];
        return makeSuccessMsg($responseData);
    }

    /**
     * 取消众筹转让的申请
     * @param CancelCFTransferRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelCFTransfer(CancelCFTransferRequest $request)
    {
        $account = $this->account;
        $tradeId = $request->get('tradeId'); // 众筹转让 tradeId

        $purchaseApi = new PurchaseApi();
        $transferRel = $purchaseApi->doCancelCFTransfer($account, $tradeId);
        if ($transferRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $transferRel['message']);
        }

        if ($transferRel['stateCode'] == 'R333') {
            // 正在转让中（锁定），不能取消
            return makeFailedMsg(412, $transferRel['message']);
        }
        $responseData = [
            'tradeId' => $transferRel['tradeID']
        ];
        return makeSuccessMsg($responseData);
    }

    /**
     * 众筹转让 余额支付
     * @param TransferBalanceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferBalance(TransferBalanceRequest $request)
    {
        $transProductId = $request->get('transProductId');
        $share = $request->get('share');
        $fee = $request->get('fee');
        //下单
        $payRel = $this->purchaseApi->purchaseForTransferBalance($this->account, $transProductId, $share, $fee);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . ' - ' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        }
        return makeSuccessMsg($payRel);
    }

    /**
     * 众筹转让  快捷支付
     * @param TransferQuickPaymentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function transferQuickPayment(TransferQuickPaymentRequest $request)
    {
        $transProductId = $request->get('transProductId');
        $share = $request->get('share');
        $fee = $request->get('fee');
        $callbackUrl = $request->get('callbackUrl', '');
        $payType = in_array($request->get('payType', 'WAP'), ['ios', 'android']) ? 'MOBILE' : 'WAP';

        //支付渠道 100006 连连支付 100007易宝支付
        $taApi = new AccountApi();
        $accountInfo = $taApi->getAccountBankInfo($this->account);
        $userBankData = $accountInfo['result']['bankCard'];
        $bankApi = new BankApi();
        $bankInfo = $bankApi->bankInfo($userBankData['bankId']);
        $typePayment = $bankInfo['result']['payChannelAgile'];
        if ($typePayment == '100006' && empty($callbackUrl)) {//连连支付必须回调地址
            return makeFailedMsg(422, 'callbackUrl is required');
        }
        //给出回调地址特征值
        $redirectUrlMark = generateUuid();
        $payRel = $this->purchaseApi->purchaseForTransferAsync($this->account, $transProductId, $share, $fee, $typePayment, $userBankData['bankId'], $redirectUrlMark, $payType);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . '-' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        } else {
            if ($typePayment == '100006') {
                //将回调页存入缓存一小时
                $this->cacheCallbackUrl($redirectUrlMark, $payRel['tradeID'], $callbackUrl);
            }
            return makeSuccessMsg($payRel);
        }
    }

    /**
     * 众筹转让混合支付
     * @param TransferHybridPaymentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function transferHybridPayment(TransferHybridPaymentRequest $request)
    {
        $transProductId = $request->get('transProductId');
        $share = $request->get('share');
        $fee = $request->get('fee');
        $depositFee = $request->get('depositFee');
        $callbackUrl = $request->get('callbackUrl', '');
        $payType = in_array($request->get('payType', 'WAP'), ['ios', 'android']) ? 'MOBILE' : 'WAP';

        //支付渠道 100006 连连支付 100007易宝支付
        $taApi = new AccountApi();
        $accountInfo = $taApi->getAccountBankInfo($this->account);
        $userBankData = $accountInfo['result']['bankCard'];
        $bankApi = new BankApi();
        $bankInfo = $bankApi->bankInfo($userBankData['bankId']);
        $typePayment = $bankInfo['result']['payChannelAgile'];
        if ($typePayment == '100006' && empty($callbackUrl)) {//连连支付必须回调地址
            return makeFailedMsg(422, 'callbackUrl is required');
        }
        //给出回调地址特征值
        $redirectUrlMark = generateUuid();
        $payRel = $this->purchaseApi->purchaseForTransferHybrid($this->account, $transProductId, $share, $fee, $depositFee, $typePayment, $userBankData['bankId'], $redirectUrlMark, $payType);
        if ($payRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $payRel['stateCode'] . '-' . $payRel['message'], ['tradeID' => (isset($payRel['tradeID']) ? $payRel['tradeID'] : '')]);
        } else {
            if ($typePayment == '100006') {
                //将回调页存入缓存一小时
                $this->cacheCallbackUrl($redirectUrlMark, $payRel['tradeID'], $callbackUrl);
            }
            return makeSuccessMsg($payRel);
        }
    }


    /**
     * 判断活期产品是否处在续标锁定阶段
     * @return bool
     */
    private function checkIsLock()
    {
        // 判断活期产品是否处在续标锁定阶段
        $prodApi = new ProdApi();
        $dpConfig = $prodApi->getDpConfig();
        if ($dpConfig['code'] != '200') {
            \log::notice('获取活期锁定购买时间失败！');
        }
        $now = date('Y-m-d H:i:s');
        $start = isset($dpConfig['result']['lockStartTime']) ? $dpConfig['result']['lockStartTime'] : '';
        $end = isset($dpConfig['result']['lockEndTime']) ? $dpConfig['result']['lockEndTime'] : '';
        if ($now >= $start && $now <= $end) {
            // 不能购买
            return false;
        }
        return true;
    }
}
