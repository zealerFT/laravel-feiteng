<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 指定返回形式
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function response(array $errors)
    {
        $firstMsgArr =  current($errors);
        return makeFailedMsg(422,$firstMsgArr[0]);
    }

    public function attributes(){
        return [
            'mobile'=>'手机号',
            'password'=>'密码',
            'smsCode'=>'短信验证码',
            'cardMobile'=>'手机号',
            'cardNo'=>'银行卡号',
            'IDCard'=>'身份证号',
            'realName'=>'真实姓名',
            'captcha'=>'图形验证码',
            'newPwd'=>'新密码',
            'tradeId'=>'交易ID',
        ];
    }

    public function messages()
    {
        return [
            'zh_mobile'=>':attribute格式不正确',
            'alpha_and_num'=>':attribute必须是字母和数字的组合',
            'between'=>':attribute位数必须介于:min～:max位',
            'numeric'=>':attribute必须为数字',
            'required'=>':attribute必填',
            'zh_id_card'=>':attribute格式不正确',

        ];
    }


}
