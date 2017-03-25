<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午5:08
 */
namespace App\DataServer\Cornerstone;
use App\DataServer\BaseApi;
class CornerstoneApi extends BaseApi
{
    public $token;
    function __construct()
    {
        $this->setApiUrl();
        $this->token = \Request::header('Token');
    }

    protected function setApiUrl(){
        $this->apiUrl=config('sys-config.user_api_url');
    }
}