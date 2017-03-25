<?php
/**
 * 绑卡Request
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午3:45
 */
namespace App\Http\Requests\User;

use App\Http\Requests\Request;


class BindCardRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'realName'=>'required|string',
            'IDCard'=>'required|zh_id_card',//身份证
            'cardNo'=>'required|string',//银行卡号
            'cardMobile'=>'required|zh_mobile',//绑卡的手机号
            'bankId'=>'sometimes|integer',//银行ID
        ];
    }


}