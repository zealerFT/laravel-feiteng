<?php
/**
 * 绑卡确认Request
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午3:45
 */
namespace App\Http\Requests\User;

use App\Http\Requests\Request;


class BindCardConfirmRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'validateCode'=>'required|numeric',
            'requestId'=>'required|string',
            'cardNo'=>'required|string',//银行卡号
            'cardMobile'=>'required|zh_mobile',//绑卡的手机号
        ];
    }


}