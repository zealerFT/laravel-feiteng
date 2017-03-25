<?php
/**
 * 抽奖request
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-10-25
 * Time: 下午3:45
 */
namespace App\Http\Requests\Activity;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class LuckydrawRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type'=>'required|string',
        ];
    }
}