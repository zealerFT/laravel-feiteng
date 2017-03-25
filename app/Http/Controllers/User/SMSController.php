<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-14
 * Time: 下午8:10
 */

namespace App\Http\Controllers\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Toplan\PhpSms\Facades\Sms;

class SMSController extends Controller
{
    /**
     * 用户注册验证码
     * @param $code
     * @return mixed
     */
   public function register($code){
        $sms = Sms::make();
        $sms->content('【光合联萌】您的手机验证码：'.$code.'，有效时间5分钟，为了您的账户安全，请不要向任何人泄露。');
        return $sms->to('13317140411')->template(['Alidayu'=>'SMS_61950881'])->data(['code'=>$code])->send();
    }



}