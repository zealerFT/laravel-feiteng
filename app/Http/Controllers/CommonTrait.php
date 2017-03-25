<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-8-19
 * Time: 下午5:53
 */

namespace App\Http\Controllers;


Trait CommonTrait
{
    private $userBaseInfo ;
    private $token;
    protected $account;

    function setBase(){
        $this->token = \Request::header('Token');
        $this->userBaseInfo = \Cache::get($this->token);
        $this->account = isset($this->userBaseInfo['account']) ? $this->userBaseInfo['account'] : '';
    }
}