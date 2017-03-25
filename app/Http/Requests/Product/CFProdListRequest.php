<?php
/**
 * 众筹产品列表request
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午3:45
 */
namespace App\Http\Requests\Product;

use App\Http\Requests\Request;


class CFProdListRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'pageId'=>'sometimes|integer',
            'pageSize'=>'sometimes|integer',
        ];
    }

}