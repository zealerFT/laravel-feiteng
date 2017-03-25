<?php

namespace App\Http\Controllers\User;

use App\Service\CaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webpatser\Uuid\Uuid;

/**
 * Class CaptchaController
 * @package Mews\Captcha
 */
class CaptchaController extends Controller
{

    /**
     * get CAPTCHA
     * @param CaptchaService $captcha
     * @param string $config
     * @param $captchaId
     * @return \Intervention\Image\ImageManager
     */
    public function getCaptcha(CaptchaService $captcha, $config = 'register',$captchaId)
    {
        return $captcha->createById($config,$captchaId);
    }

    /**
     * get CAPTCHA getCaptchaInfo API
     * @param string $config
     * @return JsonResponse
     * @throws \Exception
     */
    public function getCaptchaInfo($config = 'register')
    {
        $urlDomain = substr(str_replace(\Request::decodedPath(),'',\Request::url()),0,-1);
        $urlStaticPrefix = \Route::current()->getCompiled()->getStaticPrefix();
        $captchaUuid = Uuid::generate();
        $captchaData = [
            'captchaUrl'=>$urlDomain.$urlStaticPrefix.'/'.$config.'/'.$captchaUuid,
            'captchaUuid'=>(string)$captchaUuid
        ];
        return makeSuccessMsg($captchaData);
    }

}
