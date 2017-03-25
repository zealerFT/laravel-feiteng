<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午5:08
 */
namespace App\DataServer\TA;

use App\DataServer\BaseApi;

class TAApi extends BaseApi
{
    function __construct()
    {
        $this->apiUrl = config('sys-config.ta_api_url');
    }

}