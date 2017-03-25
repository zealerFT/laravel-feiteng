<?php
/**
 * 前台配置
 */
return [

    //支付回调地址
        'payment_callback'=>env('FRONT_PAYMENT_URL','http://www.sunallies.com/'),

    //微信登陆回调地址
    'wechat_login_callback'=>env('FRONT_LOGIN_URL','http://www.sunallies.com/'),
];
