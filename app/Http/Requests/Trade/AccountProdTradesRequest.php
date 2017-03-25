<?php
/**
 * 用户余额明细Request
 */
namespace App\Http\Requests\Trade;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class AccountProdTradesRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'tradeId'=>'sometimes|integer',
            'length'=>'sometimes|integer',
            'type'=>'sometimes|integer',
            'autoPurchase'=>'sometimes|integer',
        ];
    }

}