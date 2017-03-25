<?php
/**
 * 优惠券相关
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午5:08
 */

namespace App\DataServer\Front;


class ContractApi extends FrontApi
{
    private $userBaseInfo;
    //api uri list
    const CONTRACT_USER   = 'fe_agent/users/contract';   //查询用户合同
    const CONTRACT_CREATE   = 'fe_agent/users/contract';   //生成用户合同

    function __construct()
    {
        parent::__construct();
        $this->userBaseInfo = \Cache::get($this->token);
    }

    /**
     * 获取用户合同
     * @param $tranId
     * @return mixed
     */
    function getUserContract($tranId){
        $params = ['tranId'=>$tranId];
        return $this->method(self::CONTRACT_USER.'/'.$this->token)->get($params);
    }

    /**
     * 生成用户合同
     * @param $pId
     * @param $uId
     * @param $tranId
     * @param $realName
     * @param $contractModelId
     * @param $contractTitle
     * @param $contractData
     * @return mixed
     */
    function NewUserContract($pId,$uId,$tranId,$realName,$contractModelId,$contractTitle,$contractData){
        $params = [
            'pId'=>$pId,
            'uId'=>$uId,
            'tranId'=>$tranId,
            'realName'=>$realName,
            'contractModelId'=>$contractModelId,
            'contractTitle'=>$contractTitle,
            'contractData'=>$contractData,
        ];
        return $this->method(self::CONTRACT_CREATE.'/'.$this->token)->post($params);
    }


}