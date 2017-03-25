<?php
/**
 * 注册-重发短信验证码-Request
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午3:45
 */
namespace App\Http\Requests\User;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class RegSmsCodeRetryRequest extends Request
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
            'verifyUserUUID' => 'required|string',
        ];
    }

}