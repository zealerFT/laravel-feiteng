<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午5:08
 */

namespace App\DataServer\Front;

use App\Exceptions\FrontApi\BankApiException;
use App\Exceptions\User\BankException;

class BankApi extends FrontApi
{
    //api uri list
    const Bank_INFO   = 'fe_agent/bank';   //银行信息
    const Bank_TA_ID_BY_CODE   = 'fe_agent/taBankId';   //银行信息
    const Bank_LIST_FOR_BIND_CARD   = 'fe_agent/bankRec';   //绑卡银行列表


    /**
     * 获取银行信息
     * @param $bankId
     * @return mixed
     * @throws BankApiException
     */
    function bankInfo($bankId){
        $bankInfo = $this->method(self::Bank_INFO)->get(['bankId'=>$bankId]);
        if(!isset($bankInfo['result'])){
            \Log::alert('获取银行卡信息异常：'.self::Bank_INFO.' 参数-'.json_encode(['bankId'=>$bankId]).' 返回结果：'.json_encode($bankInfo));
            throw new BankApiException('系统错误，请重试',501);
        }
        $bankInfoRel = $bankInfo['result'];
        $bankInfoRel['payChannelAgile'] = $this->transPayChannel($bankInfoRel['payChannelAgile']);//快捷支付
        $bankInfoRel['bindCardChannel'] = $this->transPayChannel($bankInfoRel['bindCardChannel']);//绑卡渠道
        $bankInfoRel['payChannelWG'] = $this->transPayChannel($bankInfoRel['payChannelWG']);//网关支付
        foreach($bankInfoRel['limit'] as $index => &$channel){
            $channel['payChannelId'] = $this->transPayChannel($channel['payChannelId']);
            if($channel['payChannelId'] == $bankInfoRel['payChannelAgile']){
                //unset($bankInfoRel['limit'][$index]);
                $bankInfoRel['limit'] = $channel;
                break;
            }
        }
        $bankInfo['result'] = $bankInfoRel;
        return $bankInfo;
    }

    /**
     * 将前台表的1,2转成后台对应的支付渠道编码 快捷支付渠道配置 1.连连支付 2.易宝支付
     * @param $payChannel
     * @return string
     */
    function transPayChannel($payChannel){
        switch($payChannel){
            case 1:
                $payChannelRel = '100006';
                break;
            case 2:
                $payChannelRel = '100007';
            break;
            default:
                $payChannelRel = '100006';
        }
        return $payChannelRel;
    }

    /**
     * 通过银行卡号获取银行的ta bank id
     * @param $cardNo
     * @return mixed
     * @throws BankApiException
     */
    function getBankIdByCardNo($cardNo){
        //调用淘宝接口方案
        $bankOfAliApiUrl = "https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&cardBinCheck=true&cardNo=";//后接银行卡号
        $bankInfo = $this->serverUrl($bankOfAliApiUrl.$cardNo)->withOption('SSL_VERIFYPEER',false)->get();//todo 调用方式问题
        $bankInfo = json_decode($bankInfo,1);
        //$cardType = $bankInfo['cardType'];
        if(!$bankInfo['validated']){
            throw new BankApiException('银行卡非法',422);
        }
        $cardType = $bankInfo['cardType'];
        if($cardType != 'DC'){
            throw new BankApiException('不支持的银行卡类型',422);
        }
        $bankCode = $bankInfo['bank'];
        $this->apiUrl=config('sys-config.front_api_url');//todo
        $taBankInfo = $this->method(self::Bank_TA_ID_BY_CODE)->get(['bankCode'=>$bankCode]);
        if($taBankInfo['code']!= '200' || !isset($taBankInfo['result'])){
            \Log::alert('获取TA银行信息异常：'.self::Bank_TA_ID_BY_CODE.' 参数-'.json_encode(['bankCode'=>$bankCode]).' 返回结果：'.json_encode($taBankInfo));
            throw new BankApiException('系统错误，请重试',501);
        }
        return $taBankInfo;
    }


    /**
     * 获取绑卡银行列表
     * @throws BankApiException
     */
    function getBindCardBankList(){
        $bankListRel = $this->method(self::Bank_LIST_FOR_BIND_CARD)->get();
        if($bankListRel['code'] != 200){
            throw new BankApiException('获取绑卡银行列表失败',500);
        }
        $bankList = $bankListRel['result'];
        foreach($bankList as &$item){
            $item['bind_card_channel'] = $this->transPayChannel($item['bind_card_channel']);
        }
        return $bankList;
    }

}