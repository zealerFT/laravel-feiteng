<?php
/**
 * 重置密码-获取短信验证码-Request
 * Created by PhpStorm.
 * User: aishan
 * Date: 17-3-1
 * Time: 下午3:45
 */
namespace App\Http\Requests\User;

use App\Http\Requests\Request;

class ResetPwdSmsCodeRequest extends Request
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
        ];
    }

}