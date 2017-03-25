<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午5:08
 */
namespace App\DataServer\Front;
use App\DataServer\BaseApi;
class FrontApi extends BaseApi
{
    public $token;
    function __construct()
    {
        $this->setApiUrl();
        $this->token = \Request::header('Token');
    }

    protected function setApiUrl(){
        $this->apiUrl=config('sys-config.front_api_url');
    }
}