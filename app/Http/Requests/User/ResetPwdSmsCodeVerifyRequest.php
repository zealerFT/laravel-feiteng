<?php
/**
 * 重置密码-验证短信验证码-Request
 * Created by PhpStorm.
 * User: aishan
 * Date: 17-3-1
 * Time: 下午3:45
 */
namespace App\Http\Requests\User;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class ResetPwdSmsCodeVerifyRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mobile'=>'required|zh_mobile',
            'smsCode'=>'required|numeric',
        ];
    }

}