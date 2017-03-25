<?php
/**
 * 用户余额明细Request
 */
namespace App\Http\Requests\Trade;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class AccountFeeFlowRequest extends Request
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
            'serial'=>'sometimes|numeric',
            'length'=>'sometimes|integer',
            'type'=>'sometimes|integer',
        ];
    }

}