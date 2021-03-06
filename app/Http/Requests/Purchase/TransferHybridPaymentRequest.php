<?php
/**
 *
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午3:45
 */
namespace App\Http\Requests\Purchase;

use App\Http\Requests\Request;

class TransferHybridPaymentRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'transProductId'=>'required|numeric',
            'share'=>'required|integer',
            'fee'=>'required|numeric',
            'depositFee'=>'required|numeric',
            'callbackUrl'=>'sometimes|string',
            'payType'=>'sometimes|in:WAP,ios,android,h5',
        ];
    }

}