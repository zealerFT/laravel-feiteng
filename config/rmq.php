<?php
/**
 * RMQ配置
 * User: aishan
 * Date: 16-12-14
 * Time: 下午6:03
 */
return [
    //连接配置
    'connect'=>[
        'host'=>env('RMQ_HOST',''),
        'port'=>env('RMQ_PORT',''),
        'user'=>env('RMQ_USER'),
        'pass'=>env('RMQ_PASS'),
        'vhost'=>env('RMQ_VHOST')
    ],
    //日志RMQ连接配置
    'log_connect'=>[
        'host'=>env('RMQ_LOG_HOST',''),
        'port'=>env('RMQ_LOG_PORT',''),
        'user'=>env('RMQ_LOG_USER'),
        'pass'=>env('RMQ_LOG_PASS'),
        'vhost'=>env('RMQ_LOG_VHOST')
    ],
    //注册RMQ配置
    'reg'=>[
        'exchange' => 'e.discount.topic',
        'routingKey' => 'r.discount.signUp',
        'exchangeType' => 'topic',
        'queueName' => 'q.discount.signUp'
    ],
    //2017春节活动1发券RMQ配置
    'activity_2017Spring_act1'=>[
        'exchange' => 'e.sendCoupon',
        'routingKey' => 'r.sendCoupon',
        'exchangeType' => 'topic',
        'queueName' => 'q.sendCoupon'
    ],
    //2017春节活动3发券RMQ配置
    'activity_2017Spring_act3'=>[
        'exchange' => 'e.activity.topic',
        'routingKey' => 'r.activity.springFestivalWithdraw',
        'exchangeType' => 'topic',
        'queueName' => 'q.activity.springFestivalWithdraw'
    ],
    //日志RMQ配置
    'log'=>[
        'exchange' => 'e.log.frontend',
        'routingKey' => 'r.log.frontend',
        'exchangeType' => 'direct',
        'queueName' => 'q.log.frontend'
    ]
];