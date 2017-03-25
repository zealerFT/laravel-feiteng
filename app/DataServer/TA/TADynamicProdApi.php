<?php
/**
 * TA活期产品相关api
 */

namespace App\DataServer\TA;

class TADynamicProdApi extends TAApi
{
    //api uri list
    const DYNAMIC_PROD   = 'cgweb/c2s/dpTrade/v2/baseInfo';   //获取周周涨产品基本信息
    const DYNAMIC_PROD_AVAILABLE_CREDITS   = 'cgweb/c2s/dpTrade/v2/availableCredits';   //获取周周涨可售额度
    const DYNAMIC_PROD_ACCOUNT_INFO   = 'cgweb/c2s/dpTrade/v2/accountDpInfo';   //查询用户周周涨持有情况
    const DYNAMIC_PROD_ACCOUNT_REDEEM_ABLE   = 'cgweb/c2s/dpTrade/v2/redeemableShare';   //获取活期产品用户可转让份额
    const ACCOUNT_DP_CAPITAL_INFO = 'cgweb/c2s/opt/capital/dp';//查询用户周周涨持有情况 包含所持有活期的最高收益
    const ACCOUNT_DP_REDEEM = 'cgweb/c2s/dpTrade/v2/redeem';//处理用户转让申请

    const ACCOUNT_DP_BOUGHT = 'cgweb/c2s/dpTrade/v2/detailInfo';//活期持有资产列表

    const ACCOUNT_DP_FEE_FLOW = 'cgweb/c2s/opt/flow/dp';//活期流水
    const ACCOUNT_DP_AVAILS_MONTH = 'cgweb/c2s/opt/flow/dpAvails';//活期以月累计的收益总额

    /**
     * 获取活期产品
     * @return mixed
     */
    function getDynamicProd(){
        $params = ['msgSender'=>config('sys-config.msg_sender'),];
        $rel =  $this->method(self::DYNAMIC_PROD)->post($params);
        if(isset($rel['dpBaseInfo']) && sizeof($rel['dpBaseInfo'])){
            $dpBaseInfo = $rel['dpBaseInfo'][0];
            $dpBaseInfoReal = [];
            $price = $dpBaseInfo['price'];
            $dpBaseInfoReal['transStep'] = $dpBaseInfo['transStep'] * $price;
            $dpBaseInfoReal['remainCredit'] = $dpBaseInfo['remainedDpShare'] * $price;
            $dpBaseInfoReal['minTransCredit'] = $dpBaseInfo['minTransShare'] * $price;
            $dpBaseInfoReal['price'] =  $price;
            $dpBaseInfoReal['prodName'] =  $dpBaseInfo['prodName'];
            $dpBaseInfoReal['interestPeriodUpBound'] =  $dpBaseInfo['interestPeriodUpBound'];
            $dpBaseInfoReal['interestIncrement'] =  $dpBaseInfo['interestIncrement'];
            $dpBaseInfoReal['baseInterestRate'] =  $dpBaseInfo['baseInterestRate'];
            $dpBaseInfoReal['prodId'] =  $dpBaseInfo['prodId'];
            $dpBaseInfoReal['interestIncreasePeriod'] =  $dpBaseInfo['interestIncreasePeriod'];
            $dpBaseInfoReal['basePersonalQuotaCredit'] =  $dpBaseInfo['basePersonalQuota'] * $price;
            $dpBaseInfoReal['totalCredit'] =  $dpBaseInfo['maxScale'] * $price;
            $dpBaseInfoReal['baseLockPeriod'] =  $dpBaseInfo['baseLockPeriod'];
            $rel['dpBaseInfo'] = $dpBaseInfoReal;
        }
        return $rel;
    }

    /**
     * 获取指定用户活期产品可售额度
     * @param $account
     * @return mixed
     */
    function getDPAvailableCreditsForUser($account){
        $params = [
            'account'=>$account,
            'msgSender'=>config('sys-config.msg_sender')
        ];
        return $this->method(self::DYNAMIC_PROD_AVAILABLE_CREDITS)->post($params);
    }



    /**
     * 获取用户持有活期产品的基本信息
     * @param $account
     * @return mixed
     */
    function getAccountDPInfo($account){
        $params = [
            'account'=>$account,
            'msgSender'=>config('sys-config.msg_sender')
        ];
        return $this->method(self::DYNAMIC_PROD_ACCOUNT_INFO)->post($params);
    }

    /**
     * 获取用户周周涨持有情况(多一个持有产品最高收益)
     * @param $account
     * @return mixed
     */
    function dpCapitalInfo($account){
        $params = ['account'=>$account];
        return $this->method(self::ACCOUNT_DP_CAPITAL_INFO)->post($params);
    }

    /**
     * 获取用户可转让份额
     * @param $account
     * @return mixed
     */
    function getAccountDPRedeemAble($account){
        $params = [
            'account'=>$account,
            'msgSender'=>config('sys-config.msg_sender'),
        ];
        return $this->method(self::DYNAMIC_PROD_ACCOUNT_REDEEM_ABLE)->post($params);
    }

    /**
     * 处理用户
     * @param $account
     * @param $share
     * @return mixed
     */
    function doRedeem($account,$share){
        $params = [
            'account'=>$account,
            'share'=>$share,
            'msgSender'=>config('sys-config.msg_sender'),
        ];
        return $this->method(self::ACCOUNT_DP_REDEEM)->post($params);
    }

    /**
     * 获取用户持有活期产品列表
     * @param $account
     * @return mixed
     */
    function getTradeList($account){
        $params = [
            'account'=>$account,
            'msgSender'=>config('sys-config.msg_sender'),
        ];
        return $this->method(self::ACCOUNT_DP_BOUGHT)->post($params);
    }

    /**
     * 获取用户活期产品相关收益转入转出情况
     * @param $account
     * @param int $length
     * @param string $dpTransFlow
     * @param int $type type 1所有 2收益 3转入 4转出
     * @return mixed
     */
    function getFeeFlow($account,$length = 10,$type = 1,$dpTransFlow = ""){
        $params = [
            'account'=>$account,
            'length'=>$length,
            'type'=>$type,
        ];
        if(!empty($dpTransFlow)){
            $params['dpTransFlow'] = $dpTransFlow;
        }
        return $this->method(self::ACCOUNT_DP_FEE_FLOW)->post($params);
    }

    /**
     * 获取用户按月累计的收益总额
     * @param $account
     * @param int $type type 1所有 2收益 3转入 4转出
     * @return mixed
     */
    function getAvailsForMonth($account,$type = 1){
        $params = [
            'account'=>$account,
            'type'=>$type,
        ];
        return $this->method(self::ACCOUNT_DP_AVAILS_MONTH)->post($params);
    }

}