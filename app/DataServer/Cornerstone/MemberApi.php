<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午5:08
 */

namespace App\DataServer\Cornerstone;

use App\Exceptions\User\UserException;

class MemberApi extends CornerstoneApi
{
    //user service api list
    const USER_LOGIN            = 'auth/login';   //用户登陆
    const USER_LOGOUT           = 'auth/logout';   //用户登出
    const USER_REGISTER         = 'auth/user';   //用户注册
    const USER_RESET_PWD         = 'auth/user';   //put 密码重置
    const USER_INFO_BY_MOBILE   = 'auth/user/mobile';   //根据用户手机号获取用户信息
    const USER_SMS_CODE         = 'auth/code';   //获取&验证短信验证码
    const USER_PROFILE_BY_TOKEN = 'auth/user/token';   //根据用户token用户基本信息

    /**
     * 执行登陆操作
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    function DoLogin($data){

        $dataCollect = collect($data);
        //判断出三种登陆方式
        //3.用账号登陆并带上openid 参数：mobile，password，openid
        if($dataCollect->has('mobile') && $dataCollect->has('password') && $dataCollect->has('openId')){
            $loginType = 3;
        }elseif($dataCollect->has('mobile') && $dataCollect->has('password')){//2.用账号登陆 参数：mobile，password
            $loginType = 2;
        }elseif($dataCollect->has('openId')){//1.用openid登陆 参数：openid
            $loginType = 1;
        }else{
            throw new \Exception('INVALID REQUEST:Unable to determine the landing mode',400);
        }
        $data['type'] = $loginType;
        $data['client'] = 1;
        $data['ip'] = \Request::ip();
        return  $this->method(self::USER_LOGIN)->post($data);
    }



    /**
     * 用户登出
     * @return mixed
     */
    function DoLogout(){
        \Cache::forget($this->token);
        return  $this->method(self::USER_LOGOUT)->post(['token'=>$this->token]);
    }

    /**
     * 用户中心 获取短信验证码
     * @param $mobile
     * @param string $type
     * @return mixed
     * @throws UserException
     */
    function getSMSCode($mobile,$type = 'register'){
        $data = ['mobile'=>$mobile];
        //验证码手机号是否注册
        $userInfoRel = $this->getUserInfoByMobile($mobile);
        if($userInfoRel['code'] != 200 && $type == 'resetPwd'){
            throw new UserException('手机号码未注册，请先注册',401);
        }elseif($userInfoRel['code'] == 200 && $type == 'register'){
            throw new UserException('手机号码已注册，立即投资',401);
        }
        //获取短信验证码
        $reqRel = $this->method(self::USER_SMS_CODE)->post($data);
        return $reqRel;
    }

    /**
     * 通过手机号获取用户信息
     * @param $mobile
     * @return mixed
     */
    public function getUserInfoByMobile($mobile){
        //用户信息
        return $this->method(self::USER_INFO_BY_MOBILE.'/'.$mobile)->get();
    }


    /**
     * 用户中心验证短信验证码
     * @param $mobile
     * @param $smsCode
     * @return $this
     * @throws UserException
     */
    public function checkSMSCode($mobile , $smsCode){
        //验证短信验证码
       return $this->method(self::USER_SMS_CODE)->get(['mobile'=>$mobile,'validCode'=>$smsCode]);
    }

    /**
     * 用户中心注册，验证短信验证码
     * @param $mobile
     * @param $password
     * @param $smsCode
     * @param string $inviteCode
     * @param string $device
     * @param string $channelType
     * @return mixed
     * @throws UserException
     */
    function DoRegister($mobile,$password,$smsCode,$inviteCode = '',$device = '',$channelType = ''){
        //验证手机验证码
        $checkCodeRel = $this->checkSMSCode($mobile,$smsCode);
        if($checkCodeRel['code'] != 200){
            throw new UserException('请输入正确的短信验证码',501);
        }
        //注册
        $data = [
            'mobile'        => $mobile,
            'password'      => $password,
            'verifyCode'    => $smsCode,
            'inviteCode'    => $inviteCode,
            'device'        => $device,
            'channelType'   => $channelType,
            'client'        => 1,//互金平台
            'ip'            => \Request::ip()
        ];
        $reqRel = $this->method(self::USER_REGISTER)->post($data);
        return $reqRel;
    }


    /**
     * 重置密码
     * @param $mobile
     * @param $password
     * @return mixed
     */
    public function resetPwd($mobile , $password){
        return $this->method(self::USER_RESET_PWD.'/'.$mobile)->put(['password'=>$password,'client'=>1]);
    }
    /**
     * 用户基本信息
     * @return mixed
     */
    public function profile()
    {
        return  $this->method(self::USER_PROFILE_BY_TOKEN.'/'.$this->token)->get();
    }

    /**
     * 根据token获取用户信息
     * @param $token
     * @return mixed
     */
    public function getUserInfoByToken($token)
    {
        $this->token = $token;
        return $this->method(self::USER_PROFILE_BY_TOKEN.'/'.$this->token)->get();
    }

}