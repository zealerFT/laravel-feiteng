<?php
/**
 * 活动相关
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-10-25
 * Time: 下午5:08
 */

namespace App\DataServer\Front;

class ActivityApi extends FrontApi
{
    private $userBaseInfo;
    //api uri list
    const LUCKYDRAW_ACCOUNT   = 'fe_agent/users/luckydraw';   //获取用户抽奖机会
    const LUCKYDRAW_ACCOUNT_ADD   = 'fe_agent/users/luckydraw';   //新增用户抽奖机会
    const SPRING_FESTIVAL_2017 = 'fe_agent/act/hybrid'; //2017春节活动信息
    function __construct()
    {
        parent::__construct();
        $this->userBaseInfo = \Cache::get($this->token);

    }

    /**
     * 获取用户的抽奖次数
     * @param int $userId
     * @param string $type
     * @return mixed
     */
    function accountLuckydrawNumByUId($userId = 0,$type = ''){
        return  $this->method(self::LUCKYDRAW_ACCOUNT)->get(['type'=>$type,'uId'=>$userId]);
    }

    /**
     * 获取用户的抽奖次数
     * @param string $account
     * @param string $type
     * @return mixed
     */
    function accountLuckydrawNumByAccount($account = "",$type = ''){
        return  $this->method(self::LUCKYDRAW_ACCOUNT)->get(['account'=>$account,'type'=>$type]);
    }

    /**
     * 新增用户抽奖次数（一次）
     * @param $account
     * @param $type
     * @return mixed
     */
    function accountNewLuckydraw($account,$type){
        return  $this->method(self::LUCKYDRAW_ACCOUNT_ADD)->post(['account'=>$account,'type'=>$type]);
    }

    /**
     * 获取2017年春节活动
     */
    function springFestival2017() {
        return  $this->method(self::SPRING_FESTIVAL_2017)->post(['token'=>$this->token]);
    }
}