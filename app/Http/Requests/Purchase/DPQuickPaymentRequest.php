<?php
/**
 * 登陆Request
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午3:45
 */
namespace App\Http\Requests\Purchase;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class DPQuickPaymentRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'share'=>'required|integer',
            'fee'=>'required|numeric',
            'callbackUrl'=>'sometimes|string',
            'payType'=>'sometimes|in:WAP,ios,android,h5',
        ];
    }

}