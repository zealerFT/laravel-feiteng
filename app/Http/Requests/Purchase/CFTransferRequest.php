<?php
namespace App\Http\Requests\Purchase;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class CFTransferRequest extends Request
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'share' => 'required|integer',
            'fee' => 'required|numeric',
            'tradeId' => 'required|numeric'
        ];
    }

}