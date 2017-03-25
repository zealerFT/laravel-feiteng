<?php
/**
 * 用户投资评分Request
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午3:45
 */
namespace App\Http\Requests\User;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class UserEvalRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'answer'=>'required|string',
            'questionListId'=>'required|integer',

        ];
    }

}