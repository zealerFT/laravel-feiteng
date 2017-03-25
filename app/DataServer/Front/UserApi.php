<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午5:08
 */

namespace App\DataServer\Front;

use App\Exceptions\User\UserException;

class UserApi extends FrontApi
{
    //api uri list
    const USER_PROFILE   = 'fe_agent/users/profile';   //用户基本信息
    const USER_FRONT_REGISTER   = 'fe_agent/users/user';   //用户注册
    const USER_COUPON_DETAIL    = 'fe_agent/coupons';   //用户优惠券详情
    const USER_COUPON_COUNT     = 'fe_agent/couponQuan';   //用户优惠券数目
    const USER_COUPONS_DETAIL_FOR_PROD   = 'fe_agent/avalCoupons';   //用户优惠券数目
    const USER_INFO_UPDATE      = 'fe_agent/users/binding';   //更新用户绑卡
    const USER_EVAL             = 'fe_agent/users/eval';   //获取用户投资评分
    const USER_INVITE_LIST             = 'fe_agent/users/inviteList';   //获取用户好友邀请列表


    /**
     * 互金前台新增用户记录
     * @param $userId
     * @param $mobile
     * @param $password
     * @param string $inviteCode
     * @param string $device
     * @param string $channelType
     * @return mixed
     */
    public function register($userId , $mobile,$password,$inviteCode = '',$device = '',$channelType = ''){
        //注册
        $data = [
            'userId'        => $userId,
            'mobile'        => $mobile,
            'password'      => $password,
            'inviteCode'    => $inviteCode,
            'device'        => $device,
            'channelType'   => $channelType,
            'ip'            => \Request::ip()
        ];
        $reqRel = $this->method(self::USER_FRONT_REGISTER)->post($data);
        return $reqRel;
    }

    /**
     * 用户基本信息
     * @return mixed
     */
    public function profile()
    {
        return  $this->method(self::USER_PROFILE.'/'.$this->token)->get();
    }

    /**
     * 根据token获取用户信息
     * @param $token
     * @return mixed
     */
    public function getUserInfoByToken($token)
    {
        $this->token = $token;
        return $this->method(self::USER_PROFILE.'/'.$this->token)->get();
    }

    /**
     * 根据优惠券类型查询优惠券
     * @param int $type
     * @param int $pageId
     * @param int $pageSize
     * @return mixed
     */
    function userCouponDetail($type = 1,$pageId =1 ,$pageSize =10){
        $data = [
            'type'=>$type,
            'pageId'=>$pageId,
            'pageSize'=>$pageSize,
        ];
        return $this->method(self::USER_COUPON_DETAIL.'/'.$this->token)->get($data);
    }

    /**
     * 根据优惠券类型查询优惠券数量
     * @param int $type
     * @return mixed
     */
    function userCouponCount($type =1 ){
        return $this->method(self::USER_COUPON_COUNT.'/'.$this->token)->get(['type' => $type]);
    }

    /**
     * 根据产品查询用户可用的优惠券列表
     * @param string $prodId
     * @return mixed
     */
    function userCouponsForProd($prodId = ''){
        return $this->method(self::USER_COUPONS_DETAIL_FOR_PROD.'/'.$this->token)->get(['cId'=>$prodId]);
    }

    /**
     * 更新用户信息
     * @param $account
     * @param $userName
     * @return mixed
     */
    function updateUserInfo($account,$userName){
        $params = [
            'account'=>$account,
            'userName'=>$userName,
        ];
        return $this->method(self::USER_INFO_UPDATE.'/'.$this->token)->post($params);
    }

    /**
     * 获取用户投资评分
     * @return mixed
     */
    function getUserEval(){
        return $this->method(self::USER_EVAL.'/'.$this->token)->get();
    }

    /**
     * 更新用户投资评分
     * @param $score
     * @return mixed
     */
    function setUserEval($score){
        $params = ['score'=>$score];
        return $this->method(self::USER_EVAL.'/'.$this->token)->post($params);
    }
    
    /**
     * 获取用户好友邀请列表
     * @param $userId    分页标识
     * @param $length  每页多少个
     * @return mixed
     */
    function getInviteList($userId=1000000, $length=10){
    	$params = [
    		'initUId' => $userId,
    		'length' => $length,
		];
    	return $this->method(self::USER_INVITE_LIST.'/'.$this->token)->get($params);
    }
}