<?php
/**
 * TA 交易相关api
 */

namespace App\DataServer\TA;

use App\Exceptions\TAApi\PurchaseApiException;
use App\Exceptions\BaseApiException;
use Illuminate\Http\Request;

class PurchaseApi extends TAApi
{

    //api uri list
    const PURCHASE_BALANCE = 'cgweb/c2s/purchase/cashier';   //产品申购-余额支付
    const PURCHASE_ASYNC = 'cgweb/c2s/purchase/async';   //产品申购-快捷支付
    const PURCHASE_HYBRID = 'cgweb/c2s/purchase/hybrid';   //产品申购-混合支付
    const PURCHASE_DETAIL = 'cgweb/c2s/opt/orderDetail';   //订单基本信息查询
    const PURCHASE_DETAIL_BY_LIAN_LIAN = 'cgweb/c2s/opt/orderCallback';   //通过连连订单号查询订单基本信息查询
    const PURCHASE_LAST = 'cgweb/c2s/opt/orderLast';   //查询该订单是否是尾单
    const PURCHASE_DP_BALANCE = 'cgweb/c2s/dp/v2/purchase/cashier';   //活期产品申购-余额支付
    const PURCHASE_DP_ASYNC = 'cgweb/c2s/dp/v2/purchase/async';   //活期产品申购-快捷支付
    const PURCHASE_DP_HYBRID = 'cgweb/c2s/dp/v2/purchase/hybrid';   //活期产品申购-混合支付
    const PURCHASE_CF_TRANSFER = 'cgweb/c2s/opt/profile/cfTrade/redeem';   // 众筹产品转让
    const PURCHASE_CF_TRANSFER_CANCEL = 'cgweb/c2s/opt/profile/cfTrade/cancelRedemption';   // 取消众筹产品转让

    const PURCHASE_EPAY_CONFIRM = 'cgweb/c2s/purchase/smsConfirm';   //易宝支付确认
    const PURCHASE_EPAY_SMS = 'cgweb/c2s/purchase/sendValidateCode';   //易宝支付重发短信
    const PURCHASE_EPAY_QUERY_ORDER = 'cgweb/c2s/purchase/queryOrder';   //易宝支付查询订单状态

    const PURCHASE_RECHARGE = 'cgweb/c2s/cashier/async';   //充值
    const PURCHASE_WITHDRAW = 'cgweb/c2s/cashier/withdraw2';   //提现
    const PURCHASE_TRANSFER_BALANCE = 'cgweb/c2s/cf/purchase/cashier';   //众筹转让 余额购买
    const PURCHASE_TRANSFER_ASYNC = 'cgweb/c2s/cf/purchase/async';   //众筹转让 快捷支付
    const PURCHASE_TRANSFER_HYBRID = 'cgweb/c2s/cf/purchase/hybrid';   //众筹转让 混合支付

    /**
     * 产品申购-余额支付
     * @param $account
     * @param $pid
     * @param $share
     * @param $fee
     * @param array $coupons
     * @param string $msgSender
     * @return mixed
     * @throws PurchaseApiException
     */
    function purchaseForBalance($account, $pid, $share, $fee, $coupons = [], $msgSender = '1001')
    {
        $param = [
            'account' => $account,
            'transProductId' => $pid,
            'share' => $share,
            'fee' => $fee,
            'coupons' => json_encode($coupons),
            'msgSender' => $msgSender,
            'reqIp' => \Request::ip(),
        ];
        try {
            //\Log::info(__FUNCTION__.'：'.self::PURCHASE_BALANCE,$param);
            $payRel = $this->method(self::PURCHASE_BALANCE)->post($param);
            //\Log::info(__FUNCTION__.' Rel：'.json_encode($payRel));
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('余额支付失败：' . $e->getMessage());
        }

    }

    /**
     * 活期产品申购-余额支付
     * @param $account
     * @param $share
     * @param $fee
     * @param array $coupons
     * @param string $msgSender
     * @param $isLocked -是否是封闭期产品   0否  1是
     * @return mixed
     * @throws PurchaseApiException
     */
    function dpPurchaseForBalance($account, $share, $fee, $coupons = [], $msgSender = '1001', $isLocked = 0)
    {
        $param = [
            'account' => $account,
            'share' => $share,
            'fee' => $fee,
            'coupons' => json_encode($coupons),
            'msgSender' => $msgSender,
            'reqIp' => \Request::ip(),
            'isDp' => true,
            'restrictedTrans' => $isLocked
        ];
        try {
            //\Log::info(__FUNCTION__.'：'.self::PURCHASE_DP_BALANCE,$param);
            $payRel = $this->method(self::PURCHASE_DP_BALANCE)->post($param);
            //\Log::info(__FUNCTION__.' Rel：'.json_encode($payRel));
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('周周涨余额支付失败：' . $e->getMessage());
        }
    }

    /**
     * 产品申购-快捷支付
     * @param $account
     * @param $pid
     * @param $share
     * @param $fee
     * @param string $typePayment //支付渠道 10006连连支付 10007易宝支付
     * @param string $payType
     * @param string $bankId
     * @param string $redirectUrlMark
     * @param $cardNo //银行卡号
     * @param array $coupons
     * @return mixed
     * @throws PurchaseApiException
     */
    function purchaseForAsync($account, $pid, $share, $fee, $typePayment = '10006', $bankId = '', $cardNo, $coupons = [], $redirectUrlMark = '', $payType = 'WAP')
    {
        $param = [
            "account" => $account,
            "transProductId" => $pid,
            "share" => $share,
            "fee" => $fee,
            "typePayment" => $typePayment,
            "payType" => $payType,
            "bankName" => $bankId,
            "cardNo" => $cardNo,
            'coupons' => json_encode($coupons),
            "msgSender" => config('sys-config.msg_sender'),
            "pageURL" => $this->generateRedirectUrl($redirectUrlMark),//支付成功回调地址
            'reqIp' => \Request::ip(),
        ];
        try {
            //\Log::info(__FUNCTION__.'：'.self::PURCHASE_ASYNC,$param);
            $payRel = $this->method(self::PURCHASE_ASYNC)->post($param);
            //\Log::info(__FUNCTION__.' Rel：'.json_encode($payRel));
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('快捷支付失败：' . $e->getMessage());
        }

    }

    /**
     * 活期产品申购-快捷支付
     * @param $account
     * @param $share
     * @param $fee
     * @param string $typePayment //支付渠道 10006连连支付 10007易宝支付
     * @param string $payType
     * @param string $bankId
     * @param $isLocked  是否是封闭期产品   0否  1是
     * @param string $redirectUrlMark
     * @param $cardNo //银行卡号
     * @param array $coupons
     * @return mixed
     * @throws PurchaseApiException
     */
    function dpPurchaseForAsync($account, $share, $fee, $typePayment = '10006', $bankId = '', $cardNo, $coupons = [], $redirectUrlMark = '', $payType = 'WAP', $isLocked = 0)
    {
        $param = [
            "account" => $account,
            "transProductId" => '1000001',//活期产品id固定
            "share" => $share,
            "fee" => $fee,
            "typePayment" => $typePayment,
            "payType" => $payType,
            "bankName" => $bankId,
            "cardNo" => $cardNo,
            'coupons' => json_encode($coupons),
            "msgSender" => config('sys-config.msg_sender'),
            "pageURL" => $this->generateRedirectUrl($redirectUrlMark),//支付成功回调地址
            'reqIp' => \Request::ip(),
            "isDp" => true,
            'restrictedTrans' => $isLocked
        ];
        try {
            //\Log::info(__FUNCTION__.'：'.self::PURCHASE_DP_ASYNC,$param);
            $payRel = $this->method(self::PURCHASE_DP_ASYNC)->post($param);
            //\Log::info(__FUNCTION__.' Rel：'.json_encode($payRel));
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('周周涨快捷支付失败：' . $e->getMessage());
        }

    }

    /**
     * 产品申购-混合支付
     * @param $account
     * @param $pid
     * @param $share
     * @param $fee
     * @param $depositFee
     * @param string $typePayment
     * @param string $bankId
     * @param string $redirectUrlMark
     * @param $cardNo
     * @param array $coupons
     * @param string $payType
     * @return mixed
     * @throws PurchaseApiException
     */
    function purchaseForHybrid($account, $pid, $share, $fee, $depositFee, $typePayment = '10006', $bankId = '', $cardNo, $coupons = [], $redirectUrlMark = '', $payType = 'WAP')
    {
        $param = [
            "account" => $account,
            "transProductId" => $pid,
            "share" => $share,
            "fee" => $fee,
            "depositFee" => $depositFee,
            "typePayment" => $typePayment,
            "bankName" => $bankId,
            "cardNo" => $cardNo,
            'coupons' => json_encode($coupons),
            "msgSender" => config('sys-config.msg_sender'),
            "pageURL" => $this->generateRedirectUrl($redirectUrlMark),//支付成功回调地址
            "payType" => $payType,
            'reqIp' => \Request::ip(),
        ];
        try {
            //\Log::info(__FUNCTION__.'：'.self::PURCHASE_HYBRID,$param);
            $payRel = $this->method(self::PURCHASE_HYBRID)->post($param);
            //\Log::info(__FUNCTION__.' Rel：'.json_encode($payRel));
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('混合支付失败：' . $e->getMessage());
        }
    }

    /**
     * 活期产品申购-混合支付
     * @param $account
     * @param $share
     * @param $fee
     * @param $depositFee
     * @param string $typePayment
     * @param string $bankId
     * @param string $redirectUrlMark
     * @param $cardNo
     * @param $isLocked 1是封闭期产品  0 随存随取产品
     * @param array $coupons
     * @param string $payType
     * @return mixed
     * @throws PurchaseApiException
     */
    function dpPurchaseForHybrid($account, $share, $fee, $depositFee, $typePayment = '10006', $bankId = '', $cardNo, $coupons = [], $redirectUrlMark = '', $payType = 'WAP', $isLocked = 0)
    {
        $param = [
            "account" => $account,
            "transProductId" => '1000001',//活期产品id固定
            "share" => $share,
            "fee" => $fee,
            "depositFee" => $depositFee,
            "typePayment" => $typePayment,
            "bankName" => $bankId,
            "cardNo" => $cardNo,
            'coupons' => json_encode($coupons),
            "msgSender" => config('sys-config.msg_sender'),
            "pageURL" => $this->generateRedirectUrl($redirectUrlMark),//支付成功回调地址
            "payType" => $payType,
            'reqIp' => \Request::ip(),
            "isDp" => true,
            'restrictedTrans' => $isLocked
        ];
        try {
            //\Log::info(__FUNCTION__.'：'.self::PURCHASE_DP_HYBRID,$param);
            $payRel = $this->method(self::PURCHASE_DP_HYBRID)->post($param);
            //\Log::info(__FUNCTION__.' Rel：'.json_encode($payRel));
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('周周涨混合支付失败：' . $e->getMessage());
        }
    }

    /**
     * 交易详情
     * @param $orderId
     * @return mixed
     */
    function purchaseDetail($orderId)
    {
        $orderDetail = $this->method(self::PURCHASE_DETAIL)->get(['orderId' => $orderId]);
        return $orderDetail;
    }

    /**
     * 通过连连订单获取交易详情
     * @param $noOrder
     * @return mixed
     */
    function purchaseDetailByLianLian($noOrder)
    {
        $orderDetail = $this->method(self::PURCHASE_DETAIL_BY_LIAN_LIAN)->get(['callbackId' => $noOrder]);
        return $orderDetail;
    }

    /**
     * 判断交易是否是尾单（非充值）
     * @param $orderId
     * @return mixed
     */
    function purchaseIsLast($orderId)
    {
        $orderInfo = $this->method(self::PURCHASE_LAST)->get(['orderId' => $orderId]);
        return $orderInfo;
    }

    /**
     * 充值
     * @param $account
     * @param $fee
     * @param string $typePayment
     * @param string $bankId
     * @param $cardNo
     * @param string $payType
     * @param string $redirectUrlMark
     * @return mixed
     * @throws PurchaseApiException
     */
    function recharge($account, $fee, $typePayment = '10006', $bankId = '', $cardNo, $redirectUrlMark = '', $payType = 'WAP')
    {
        $params = [
            "account" => $account,
            "fee" => $fee,
            "typePayment" => $typePayment,
            "payType" => $payType,
            "pageURL" => $this->generateRedirectUrl($redirectUrlMark),//支付成功回调地址
            "msgSender" => config('sys-config.msg_sender'),
            "bankName" => $bankId,
            "cardNo" => $cardNo,
            'reqIp' => \Request::ip(),
        ];
        try {
            //\Log::info(__FUNCTION__.'：'.self::PURCHASE_RECHARGE,$params);
            $payRel = $this->method(self::PURCHASE_RECHARGE)->post($params);
            //\Log::info(__FUNCTION__.' Rel：'.json_encode($payRel));
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('充值支付失败：' . $e->getMessage());
        }
    }

    /**
     * 提现
     * @param $account
     * @param $fee
     * @param $cardNo
     * @return mixed
     * @throws PurchaseApiException
     */
    function withdraw($account, $fee, $cardNo)
    {
        $params = [
            'account' => $account,
            'fee' => $fee,
            'cardNo' => $cardNo,
            'msgSender' => config('sys-config.msg_sender'),
            'reqIp' => \Request::ip(),
            'bankBranch' => 111111
        ];
        try {
            //\Log::info(__FUNCTION__.'：'.self::PURCHASE_WITHDRAW,$params);
            $payRel = $this->method(self::PURCHASE_WITHDRAW)->post($params);
            //\Log::info(__FUNCTION__.' Rel：'.json_encode($payRel));
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('提现失败：' . $e->getMessage());
        }
    }

    /**
     * 易宝支付确认
     * @param $orderId
     * @param $validateCode
     * @return mixed
     * @throws PurchaseApiException
     */
    function epayConfirm($orderId, $validateCode)
    {
        $params = [
            'orderId' => $orderId,
            'validateCode' => $validateCode,
            'msgSender' => config('sys-config.msg_sender')
        ];
        try {
            //\Log::info(__FUNCTION__.'：'.self::PURCHASE_EPAY_CONFIRM,$params);
            $payRel = $this->method(self::PURCHASE_EPAY_CONFIRM)->post($params);
            //\Log::info(__FUNCTION__.' Rel：'.json_encode($payRel));
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('易宝支付确认请求提交失败：' . $e->getMessage());
        }
    }

    /**
     * 易宝支付请求支付验证码重发
     * @param $orderId
     * @return mixed
     * @throws PurchaseApiException
     */
    function epaySendValidateCode($orderId)
    {
        $params = [
            'orderId' => $orderId,
            'msgSender' => config('sys-config.msg_sender')
        ];
        try {
            return $this->method(self::PURCHASE_EPAY_SMS)->post($params);
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('易宝支付重发支付验证码请求提交失败：' . $e->getMessage());
        }
    }

    /**
     * 易宝支付查询订单支付状态
     * @param $orderId
     * @return mixed
     * @throws PurchaseApiException
     */
    function epayQueryOrder($orderId)
    {
        $params = [
            'orderId' => $orderId,
            'msgSender' => config('sys-config.msg_sender')
        ];
        try {
            return $this->method(self::PURCHASE_EPAY_QUERY_ORDER)->post($params);
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('易宝支付订单查询请求提交失败：' . $e->getMessage());
        }
    }

    /**
     * 获取回调地址
     * @param $redirectUrlMark
     * @return string
     */
    private function generateRedirectUrl($redirectUrlMark)
    {
        return route('purchase.paymentRedirect', ['redirectUrlMark' => $redirectUrlMark]);
    }

    /**
     * 众筹产品转让申请
     * @param $share    份数
     * @param $fee      金额
     * @param $account  用户 account
     * @param $tradeId  原订单 tradeId
     * @return mixed
     * @throws PurchaseApiException
     */
    public function doCFTransfer($share, $fee, $account, $tradeId)
    {
        $params = [
            'share' => $share,
            'fee' => $fee,
            'account' => $account,
            'tradeId' => $tradeId
        ];
        try {
            $transferRel = $this->method(self::PURCHASE_CF_TRANSFER)->post($params);
            return $transferRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('众筹转让失败：' . $e->getMessage());
        }
    }

    /**
     * 取消众筹转让申请
     * @param $account
     * @param $tradeId
     * @return mixed
     * @throws PurchaseApiException
     */
    public function doCancelCFTransfer($account, $tradeId)
    {
        $params = [
            'account' => $account,
            'tradeId' => $tradeId
        ];
        try {
            $cancelTransferRel = $this->method(self::PURCHASE_CF_TRANSFER_CANCEL)->post($params);
            return $cancelTransferRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('取消众筹转让失败：' . $e->getMessage());
        }
    }

    /**
     * 众筹余额支付
     * @param $account
     * @param $pid
     * @param $share
     * @param $fee
     * @param string $msgSender
     * @return mixed
     * @throws PurchaseApiException
     */
    function purchaseForTransferBalance($account, $pid, $share, $fee, $msgSender = '1001')
    {
        $param = [
            'account' => $account,
            'transProductId' => $pid,
            'share' => $share,
            'fee' => $fee,
            'msgSender' => $msgSender
        ];
        try {
            $payRel = $this->method(self::PURCHASE_TRANSFER_BALANCE)->post($param);
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('众筹余额支付失败：' . $e->getMessage());
        }
    }

    /**
     * @param $account
     * @param $pid
     * @param $share
     * @param $fee
     * @param string $typePayment //支付渠道 10006连连支付 10007易宝支付
     * @param string $bankId
     * @param string $redirectUrlMark
     * @param string $payType
     * @return mixed
     * @throws PurchaseApiException
     */
    function purchaseForTransferAsync($account, $pid, $share, $fee, $typePayment = '10006', $bankId = '', $redirectUrlMark = '', $payType = 'WAP')
    {
        $param = [
            "account" => $account,
            "transProductId" => $pid,
            "share" => $share,
            "fee" => $fee,
            "msgSender" => config('sys-config.msg_sender'),
            "isDp" => false,
            "typePayment" => $typePayment,
            "payType" => $payType,
            "bankName" => $bankId,
            "pageURL" => $this->generateRedirectUrl($redirectUrlMark),//支付成功回调地址
            'reqIp' => \Request::ip(),
        ];
        try {
            $payRel = $this->method(self::PURCHASE_TRANSFER_ASYNC)->post($param);
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('众筹快捷支付失败：' . $e->getMessage());
        }

    }


    /**
     * 众筹转让 混合支付
     * @param $account
     * @param $pid
     * @param $share
     * @param $fee
     * @param $depositFee
     * @param string $typePayment
     * @param string $bankId
     * @param string $redirectUrlMark
     * @param string $payType
     * @return mixed
     * @throws PurchaseApiException
     */
    function purchaseForTransferHybrid($account, $pid, $share, $fee, $depositFee, $typePayment = '10006', $bankId = '', $redirectUrlMark = '', $payType = 'WAP')
    {
        $param = [
            "account" => $account,
            "transProductId" => $pid,
            "share" => $share,
            "fee" => $fee,
            "depositFee" => $depositFee,
            "msgSender" => config('sys-config.msg_sender'),
            "isDp" => false,
            "typePayment" => $typePayment,
            "payType" => $payType,
            "bankName" => $bankId,
            "pageURL" => $this->generateRedirectUrl($redirectUrlMark),//支付成功回调地址
            'reqIp' => \Request::ip(),
        ];
        try {
            $payRel = $this->method(self::PURCHASE_TRANSFER_HYBRID)->post($param);
            return $payRel;
        } catch (BaseApiException $e) {
            throw new PurchaseApiException('众筹转让混合支付失败：' . $e->getMessage());
        }
    }
}
