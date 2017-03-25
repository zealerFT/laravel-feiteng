<?php
/**
 * 用户交易记录
 */
namespace App\Http\Controllers\Trade;

use App\DataServer\Front\CouponApi;
use App\DataServer\Front\ProdApi;
use App\DataServer\Front\UserApi;
use App\DataServer\Front\TradeApi as fTradeApi;
use App\DataServer\TA\AccountApi;
use App\DataServer\TA\TACFProdApi;
use App\Http\Controllers\CommonTrait;
use App\Http\Controllers\Contract\UserController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Product\CFController;
use App\Http\Requests\Trade\AccountFeeFlowRequest;
use App\Http\Requests\Trade\AccountProdTradesRequest;
use App\DataServer\TA\TradeApi;


class AccountController extends Controller
{
    use CommonTrait;

    function __construct()
    {
        $this->setBase();
    }

    /**
     * 用户余额明细
     * @param AccountFeeFlowRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function balanceFlow(AccountFeeFlowRequest $request)
    {
        $tradeId = $request->get('tradeId', "");
        $length = $request->get('length', 10);
        $accountApi = new AccountApi();
        $balanceFlowRel = $accountApi->getBalanceFlow($this->account, $tradeId, $length);
        if ($balanceFlowRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $balanceFlowRel['message']);
        }
        $balanceFlow = $balanceFlowRel['data']['balanceFlow'];
        foreach ($balanceFlow as &$item) {
            $item['createDate'] = substr($item['createTime'], 0, 10);
            $item['dateKey'] = date('Y年m月', strtotime($item['createDate']));

        }
        return makeSuccessMsg($balanceFlow);
    }

    /**
     * 用户收益明细
     * @param AccountFeeFlowRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function incomeFlow(AccountFeeFlowRequest $request)
    {
        $serial = $request->get('serial', "");
        $length = $request->get('length', 10);
        $accountApi = new AccountApi();
        $incomeFlowRel = $accountApi->getIncomeFlow($this->account, $serial, $length);
        if ($incomeFlowRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $incomeFlowRel['message']);
        }
        $incomeFlow = $incomeFlowRel['data']['incomeFlow'];
        foreach ($incomeFlow as &$item) {
            $item['dateKey'] = date('Y年m月', strtotime($item['createDate']));
        }
        return makeSuccessMsg($incomeFlow);
    }

    /**
     * 按月获取用户收益总和
     * @return \Illuminate\Http\JsonResponse
     */
    function incomeForMonth()
    {
        $accountApi = new AccountApi();
        $incomeMonthRel = $accountApi->getIncomeForMonth($this->account);
        if ($incomeMonthRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $incomeMonthRel['message']);
        }
        $incomeMonth = $incomeMonthRel['data'];
        return makeSuccessMsg($incomeMonth);
    }

    /**
     * 用户定期资产持有状况
     * @param AccountProdTradesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function regularProdSummary(AccountProdTradesRequest $request)
    {
        $tradeId = $request->get('tradeId', "");
        $length = $request->get('length', 10);
        $type = $request->get('type', 1);
        $pageData = [
            'userBaseInfo' => $this->userBaseInfo,
            'summary' => [],
            'prodTradeList' => [],
        ];
        if (!empty($this->account)) {//实名过的
            $accountApi = new AccountApi();
            //获取持有定期收益相关
            $accountProdSummaryRel = $accountApi->getRegularProdSummary($this->account);
            if ($accountProdSummaryRel['stateCode'] != '00000') {
                return makeFailedMsg(501, $accountProdSummaryRel['message']);
            }
            $accountProdSummary = $accountProdSummaryRel['data'];
            $pageData['summary'] = $accountProdSummary;
            //获取定期持有资产列表
            $accountProdListRel = $accountApi->getRegularProdList($this->account, $type, $length, $tradeId);
            if ($accountProdListRel['stateCode'] != '00000') {
                return makeFailedMsg(501, $accountProdListRel['message']);
            }
            $accountProdList = $accountProdListRel['data'];
            $nowDate = strtotime(date("Y-m-d"));
            $couponArr = [];//所有订单里非新手券集合，进行集中查询

            foreach ($accountProdList as &$prod) {
                //提取优惠券
                $couponId = $prod['couponId'];
                //$coupon = [];
                $doubleFirstPurchase = 0;//首投翻倍
                if (!empty($couponId)) {
                    $couponList = explode(',', $couponId);
                    $couponArr = array_merge($couponArr, $couponList);
                    /*foreach($couponList as $couponItem){
                        if(stristr($couponItem,'nbd_')){
                            $doubleFirstPurchase = $nowDate <= date('Y-m-d', strtotime(date('Y-m-d', strtotime($prod['valuesDate'])) . '+' . config('sys-config.newbie_coupon_term') . 'day')) ? 1 : 0;
                            $coupon[] = ['uuId'=>$couponItem];
                        }else{
                            $couponArr[]=$couponItem;
                        }
                    }*/
                }
                //$prod['coupon'] = $coupon;
                $prod['doubleFirstPurchase'] = $doubleFirstPurchase;
                //补全状态信息
                /* 计算离下一阶段的天数
                 * trade_status_num => 1 : 募集中
                 * trade_status_num => 2 : 计息中
                 * trade_status_num => 3 : 回款中
                 * trade_status_num => 4 : 已到账
                 * */
                $tradeStatusNum = $prod['tradeStatusNum'];
                switch ($tradeStatusNum) {
                    case 2:
                        $prod['tradeStatusRemainedDays'] = (strtotime($prod['finishDate']) - $nowDate) / 86400;
                        break;
                    case 3:
                        $prod['tradeStatusRemainedDays'] = (strtotime($prod['redeemDate']) - $nowDate) / 86400;
                        break;
                    default:
                        $prod['tradeStatusRemainedDays'] = 0;
                }
            }

            //批量获取优惠券
            $couponApi = new CouponApi();
            $couponArrInfoRel = $couponApi->couponMultiDetail($couponArr);
            if (!empty($couponArrInfoRel['result'])) {
                $couponArrInfo = $couponArrInfoRel['result'];
                //转化coupon list
                $couponArrTrans = [];
                foreach ($couponArrInfo as $couponData) {
                    $couponArrTrans[$couponData['uuId']] = $couponData;
                }
                //拼接优惠券到订单中
                foreach ($accountProdList as &$prod) {
                    $couponCash = 0;
                    $couponAddInterest = 0;
                    $couponInfoArr = [];
                    $couponUUId = $prod['couponId'];
                    $couponList = explode(',', $couponUUId);
                    $couponNBD = [];
                    foreach ($couponList as $couponUUId) {
                        if (!empty($couponUUId)) {
                            if (isset($couponArrTrans[$couponUUId])) {
                                $couponInfo = $couponArrTrans[$couponUUId];
                                $couponInfoData = json_decode($couponInfo['data'], 1);
                                //提取优惠券参数
                                switch ($couponInfo['type']) {
                                    case 1:
                                        $couponCash = $couponInfoData['coupon_amount'];
                                        break;
                                    case 2:
                                        $couponAddInterest = $couponInfoData['coupon_rate'];
                                        break;
                                    case 4:
                                        $couponCash = isset($couponInfoData['coupon_amount']) ? $couponInfoData['coupon_amount'] : $couponInfoData['coupon_discount'];
                                        break;
                                }
                                $couponInfoArr[] = array_only($couponInfo, ['name', 'uuId', 'data', 'type', 'state']);
                            } elseif (!empty($couponUUId)) {
                                $couponNBD[] = ['uuId' => $couponUUId];
                            }
                        }
                    }
                    $prod['couponCash'] = (double)$couponCash;
                    $prod['couponAddInterest'] = (double)$couponAddInterest;
                    $prod['coupon'] = array_merge($couponNBD, $couponInfoArr);
                }
            }
            $pageData['prodTradeList'] = $accountProdList;
        }
        return makeSuccessMsg($pageData);
    }

    /**
     * 获取用户定期资产详情
     * @param $tradeId
     * @return \Illuminate\Http\JsonResponse
     */
    function regularProdDetail($tradeId)
    {
        $accountApi = new AccountApi();
        $tradeDetailRel = $accountApi->getRegularProdDetail($this->account, $tradeId);
        if ($tradeDetailRel['stateCode'] != '00000' || empty($tradeDetailRel['data'])) {
            if (empty($tradeDetailRel['data'])) {
                $tradeDetailRel['message'] = '记录不存在';
            }
            return makeFailedMsg(501, $tradeDetailRel['message']);
        }
        $nowDate = strtotime(date("Y-m-d"));
        $tradeDetail = $tradeDetailRel['data'];
        $couponId = $tradeDetail['couponId'];
        $coupon = [];
        $couponCash = 0;
        $couponAddInterest = 0;//
        $couponList = explode(',', $couponId);
        $couponApi = new CouponApi();
        foreach ($couponList as $couponItem) {
            $couponInfoRel = $couponApi->couponDetail($couponItem);
            if (!empty($couponInfoRel['result'])) {
                $couponInfo = $couponInfoRel['result'];
                $couponData = json_decode($couponInfo['data'], 1);
                //提取优惠券参数
                switch ($couponInfo['type']) {
                    case 1:
                        $couponCash = $couponData['coupon_amount'];
                        break;
                    case 2:
                        $couponAddInterest = $couponData['coupon_rate'];
                        break;
                    case 4:
                        $couponCash = isset($couponData['coupon_amount']) ? $couponData['coupon_amount'] : $couponData['coupon_discount'];
                        break;
                }
                $coupon[] = array_only($couponInfo, ['name', 'uuId', 'data', 'type', 'state']);
            } elseif (!empty($couponItem)) {
                $coupon[] = ['uuId' => $couponItem];
            }
        }
        //补全状态信息
        /* 计算离下一阶段的天数
         * trade_status_num => 1 : 募集中
         * trade_status_num => 2 : 计息中
         * trade_status_num => 3 : 回款中
         * trade_status_num => 4 : 已到账
         * */
        $tradeStatusNum = $tradeDetail['tradeStatusNumber'];
        switch ($tradeStatusNum) {
            case 2:
                $tradeDetail['tradeStatusRemainedDays'] = (strtotime($tradeDetail['finishDate']) - $nowDate) / 86400;
                break;
            case 3:
                $tradeDetail['tradeStatusRemainedDays'] = (strtotime($tradeDetail['redeemDate']) - $nowDate) / 86400;
                break;
            default:
                $tradeDetail['tradeStatusRemainedDays'] = 0;
        }
        $tradeDetail['couponCash'] = (double)$couponCash;
        $tradeDetail['couponAddInterest'] = (double)$couponAddInterest;
        $tradeDetail['coupon'] = $coupon;
        //生成合同
        $userContract = new UserController();
        $userContract->newTradeContract($tradeId);

        //获取产品信息（是否支持续投）
        $prodApi = new ProdApi();
        $pMainId = substr($tradeDetail['productId'],0,6);
        $prodInfoRel = $prodApi->regularProd($pMainId);
        if (empty($prodInfoRel['result'])) {
            return makeFailedMsg(500, '定期产品描述获取失败');
        }
        $prodInfo = $prodInfoRel['result'];
        $tradeDetail['prodInfo'] = $prodInfo;

        // 读取是否自动续标标记相关
        $userTrade = [];
        $tradeApi = new fTradeApi();
        $userTradeRel = $tradeApi->getUserTrade($tradeId);
        if($userTradeRel['code'] == 200 && !empty($userTradeRel['result'])){
            $userTrade = $userTradeRel['result'];
        }
        $tradeDetail['userTrade'] = $userTrade;

        return makeSuccessMsg($tradeDetail);
    }

    /**
     * 获取用户众筹持有资产详情
     * @param AccountProdTradesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function CFProdSummary(AccountProdTradesRequest $request)
    {
        $tradeId = $request->get('tradeId', "");
        $length = $request->get('length', 10);
        $pageData = [
            'userBaseInfo' => $this->userBaseInfo,
            'summary' => [],
            'prodTradeList' => [],
        ];
        if (!empty($this->account)) {//实名过的
            $accountApi = new AccountApi();
            //获取持有众筹收益相关
            $CFProdSummaryRel = $accountApi->getCFProdSummary($this->account);
            if ($CFProdSummaryRel['stateCode'] != '00000') {
                return makeFailedMsg(501, $CFProdSummaryRel['message']);
            }
            $CFProdSummary = $CFProdSummaryRel['data'];
            $pageData['summary'] = $CFProdSummary;
            //获取众筹资产持有列表
            $accountProdListRel = $accountApi->getCFProdList($this->account, $length, $tradeId);
            if ($accountProdListRel['stateCode'] != '00000') {
                return makeFailedMsg(501, $accountProdListRel['message']);
            }
            $accountProdList = $accountProdListRel['data'];
            foreach ($accountProdList as &$prod) {
                $prodId = $prod['productId'];
                //前台产品详细信息
                $prodApi = new ProdApi();
                $prodInfoRel = $prodApi->cfProdDetail($prodId);
                if (empty($prodInfoRel['result'])) {
                    return makeFailedMsg(500, '众筹产品描述获取失败');
                }
                $prodInfo = $prodInfoRel['result'];
                $prod['psProcessInfo'] = array_merge(["每季结息", "平台贴息"], getProdTarget(json_decode($prodInfo['cfPsProcess'], 1), $prod));
            }
            $pageData['prodTradeList'] = $accountProdList;
        }
        return makeSuccessMsg($pageData);
    }

    /**
     * 获取用户众筹资产详情
     * @param $tradeId
     * @return \Illuminate\Http\JsonResponse
     */
    function CFProdDetail($tradeId)
    {
        $accountApi = new AccountApi();
        $tradeDetailRel = $accountApi->getCFProdDetail($this->account, $tradeId);
        if ($tradeDetailRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $tradeDetailRel['message']);
        }
        $tradeDetail = $tradeDetailRel['data'];
        //判断订单成功转出的情况
        if(empty($tradeDetail['detail'])){
            return makeFailedMsg(501,'该笔订单已成功转出或失效');
        }
        //生成合同
        $userContract = new UserController();
        $aboveTradeId = $tradeDetail['aboveTradeId'];//众筹订单来源，如果是接的别人的订单，则会是上一个人的订单id，如果不是接的别人的，那么为null
        if (!empty($aboveTradeId)) {
            // 如果是 转让购买
            $userContract->newTradeTransferContract($tradeId, $aboveTradeId, $tradeDetail['detail']['productId'], $tradeDetail['detail']['share'] * $tradeDetail['detail']['price'], $tradeDetail['detail']['purchaseDate']); // 生成转让合同
        } else {
            $userContract->newTradeContract($tradeId); // 生成产品合同
        }
        // 判断 此tradeId 是否存在 转让记录，有则生成 对应的转让合同
        $transferRecords = $tradeDetail['successTransferBothTradeIds'];// 转让记录
        $tradeApi = new TradeApi();
        $userInfoRel = $tradeApi->userCardNo($tradeId);// 购买人（受让方）
        if ($userInfoRel['stateCode'] != '00000') {
            \Log::error('生成产品合同错误，获取用户信息失败：' . $userInfoRel['message'] . '   ' . $tradeId);
        }
        $sellerInfo = $userInfoRel['memberInfo'];
        if (sizeof($transferRecords) && $sellerInfo) {
            foreach ($transferRecords as $record) {
                $userContract->newTradeTransferSellerContract($record['theSellerTransferSuccessTradeId'], $record['theBuyerTransferSuccessTradeId'], $tradeDetail['detail']['productId'], $record['amount'], $record['createTime'], $sellerInfo['real_name'], $sellerInfo['card_no']); // 卖方合同
                //$buyerInfo = $tradeApi->userCardNo($record['theBuyerTransferSuccessTradeId']);
                //$buyerInfo = $buyerInfo['memberInfo'];
                $userContract->newTradeTransferSellerContract($record['theBuyerTransferSuccessTradeId'], $record['theBuyerTransferSuccessTradeId'], $tradeDetail['detail']['productId'], $record['amount'], $record['createTime'], $sellerInfo['real_name'], $sellerInfo['card_no']); // 在卖方页，给买方生成合同
            }
        }

        $tradeDetail['tradeIdForTransferOrCancel'] = sizeof($tradeDetail['tradeIdForTransferOrCancel']) ? $tradeDetail['tradeIdForTransferOrCancel']['0']['tradeIdForTransferOrCancel'] : '';
        $prodId = $tradeDetail['detail']['productId'];
        //前台产品详细信息
        $prodApi = new ProdApi();
        $prodInfoRel = $prodApi->cfProdDetail($prodId);
        if (empty($prodInfoRel['result'])) {
            return makeFailedMsg(500, '众筹产品描述获取失败');
        }
        $prodInfo = $prodInfoRel['result'];
        // 获取产品结息记录
        $taCFProdApi = new TACFProdApi();
        $taProdRel = $taCFProdApi->getProdDetail($prodId);
        if ($taProdRel['stateCode'] != '00000') {
            return makeFailedMsg(501, '获取上一季度结息时的年化收益率失败：' . $taProdRel['message']);
        }
        $settlement = $taProdRel['data']['settlement'];
        $tradeDetail['detail']['lastIncomeRate'] = '';
        if (sizeof($settlement)) {
            $settlement = array_filter($settlement, function ($item) {
                // 发放的要是小于等于今天的记录
                return $item['redeemDate'] <= date('Y-m-d');
            });
            if (sizeof($settlement)) {
                $keys = array_column($settlement, 'createTime');
                array_multisort($keys, SORT_DESC, $settlement);
                $tradeDetail['detail']['lastIncomeRate'] = $settlement['0']['incomeRate'];
            }
        }

        $tradeDetail['detail']['psProcessInfo'] = array_merge(["每季结息", "平台贴息"], getProdTarget(json_decode($prodInfo['cfPsProcess'], 1), $tradeDetail['detail']));
        return makeSuccessMsg($tradeDetail);
    }

    /**
     * 获取用户众筹资产收益流水详情
     * @param $tradeId
     * @return \Illuminate\Http\JsonResponse
     */
    function CFProdAvailFlow($tradeId)
    {
        $accountApi = new AccountApi();
        $tradeDetailRel = $accountApi->getCFProdAvailFlow($this->account, $tradeId);
        if ($tradeDetailRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $tradeDetailRel['message']);
        }
        $tradeDetail = $tradeDetailRel['data'];
        foreach ($tradeDetail as &$item) {
            $item['dateKey'] = date('Y年m月', strtotime($item['createDate']));
        }
        return makeSuccessMsg($tradeDetail);
    }

    /**
     * 用户持有定期资产自动续标状态修改
     * @param $tradeId
     * @param AccountProdTradesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function userTradeModify($tradeId, AccountProdTradesRequest $request) {
        $isRetender = $request->get('isRetender', 0);
        // 校验account和tradeId是否匹配
        $accountApi = new AccountApi();
        $tradeDetailRel = $accountApi->getRegularProdDetail($this->account,$tradeId);
        if($tradeDetailRel['stateCode'] != '00000' || empty($tradeDetailRel['data'])){
            if(empty($tradeDetailRel['data'])){
                $tradeDetailRel['message'] = '记录不存在';
            }
            return makeFailedMsg(501,$tradeDetailRel['message']);
        }
        // 查看是否可以修改（15天内）
        $finishDate = $tradeDetailRel['data']['finishDate'];
        $second1 = strtotime(date('Y-m-d'));
        $second2 = strtotime(date('Y-m-d', strtotime($finishDate)));
        $days = round(($second2 - $second1)/3600/24) ;
        if($days < env('USER_TRADE_MODIFY_DAYS',15)) {
            return makeFailedMsg(501,"产品已接近结息日，不能再修改状态");
        }
        $tradeApi = new fTradeApi();
        $tradeDetailRel = $tradeApi->userTradeModify($tradeId,$isRetender);
        if($tradeDetailRel['code'] != '200'){
            return makeFailedMsg(501,$tradeDetailRel['message']);
        }
        $tradeDetail = $tradeDetailRel['result'];
        return makeSuccessMsg($tradeDetail);
    }

}
