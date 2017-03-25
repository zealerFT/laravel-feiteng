<?php
/**
 * TA 交易记录相关api
 */

namespace App\DataServer\TA;


class TradeApi extends TAApi
{

    //api uri list
    const TRANS_MEMBER_CARD_NO      = 'cgweb/c2s/trans/memberNameAndCardNo';   //通过交易id获取用户身份


    /**
     * 通过交易id获取用户身份证
     * @param $tradeId
     * @return mixed
     */
    function userCardNo($tradeId){
        $param = [
            'tradeId'       =>$tradeId,
        ];
        return  $this->method(self::TRANS_MEMBER_CARD_NO)->post($param);

    }


}