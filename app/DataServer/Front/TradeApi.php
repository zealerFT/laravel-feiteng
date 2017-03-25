<?php
/**
 * User: sam
 * Date: 17-3-3
 */
namespace App\DataServer\Front;

class TradeApi extends FrontApi
{
    const USER_TRADE   = 'fe_agent/trade';   //定期交易状态  put->修改  get->获取   post->新建

    /**
     * 用户定期自动续标状态修改
     * @param $account
     * @param $tradeId
     * @param $isRetender
     * @return mixed
     */
    function userTradeModify($tradeId,$isRetender){
        $params = [
            'tradeId'=>$tradeId,
            'isRetender'=>$isRetender,
        ];
        return $this->method(self::USER_TRADE)->put($params);
    }

    /**
     * 获取用户定期自动续标状态
     * @param $tradeId
     * @return mixed
     */
    function getUserTrade($tradeId){
        return $this->method(self::USER_TRADE."/".$tradeId)->get();
    }

    /**
     * 保存用户定期快捷支付交易数据
     * @param $account
     * @param $tradeId
     * @param $isRetender
     * @param $fee
     * @param $share
     * @param $productId
     * @return mixed
     */
    function userTradeSave($account,$tradeId,$isRetender,$fee,$share,$productId){
        $params = [
            'tradeId'=>$tradeId,
            'fee'=>$fee,
            'share'=>$share,
            'productId'=>$productId,
            'isRetender'=>$isRetender,
            'account'=>$account,
        ];
        return $this->method(self::USER_TRADE)->post($params);
    }
}