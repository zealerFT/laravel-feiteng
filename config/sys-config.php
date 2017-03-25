<?php
/**
 * 前台配置
 */
return [

    //前台数据层接口地址
    'front_api_url' =>env('API_FRONT_URL', 'SomeRandomString'),

    //前台数据层接口地址
    'user_api_url' =>env('API_USER_URL', 'Url'),

    //电站接口
    'ps_old_url' =>env('PS_OLD_URL'),
    'ps_new_url' =>env('PS_NEW_URL'),
    
    //TA数据接口地址
    'ta_api_url'    =>env('API_TA_URL', 'SomeRandomString'),
    //Ta 接口调用定义数据
    'msg_sender'    =>env('TA_MSG_SENDER','10001'),
    'trans_pw'    =>env('TA_TRANS_PW','111111'),

    //PDF服务器地址
    'pdf_service_url' =>env('PDF_SERVICE_URL','http://pdf.sunallies.com/'),

    //注册短信
    'verify_user_UUid_expires'=>env('VERIFY_USER_UUID_EXPIRES',10),//注册短信可以不断重试发送的时间
    'send_sms_expires'=>env('SEND_SMS_EXPIRES',1),//发送一条短信的单位时间

    //自定义队列
    'register_queue'=>'agile.register',//注册队列名称
    'activity_queue'=>'agile.activity',//活动队列名称

    //支付
    'pay_callback_cache_time'=>60 ,//缓存支付回调链接时间（分钟）
    'pay_callback_default'=>'http://www.sunallies.com/m/',//默认支付回调地址
    'pay_callback_cache_pre'=>'pay_async_',//缓存的支付回调页的key的前缀

    //提现
    'withdraw_min_fee'=>env('WITHDRAW_MIN_FEE',50),
	
    // 老接口电站ids，已废弃
	'ps_old_ids'=>[],
    
    //用户众筹投资风险测试题库
    'user_risk_min_score'=>12,//用户众筹风险评估最小分数
    'user_exam_list'=>[
        "1" => [
            [
                'qid' => 1,
                'content' => '您了解股权众筹的风险吗？',
                'options' => [
                    [
                        'oid' => 0,
                        'score' => 1,
                        'content' => '不清楚，我不接受亏损风险',
                    ],[
                        'oid' => 1,
                        'score' => 2,
                        'content' => '了解一些，有一定的亏损风险',
                    ],[
                        'oid' => 2,
                        'score' => 3,
                        'content' => '比较清楚，收益越高风险越大',
                    ]
                ],
            ],
            [
                'qid' => 2,
                'content' => '您了解光伏发电站吗？',
                'options' => [
                    [
                        'oid' => 0,
                        'score' => 1,
                        'content' => '不清楚，不知道什么是光伏电站',

                    ],[
                        'oid' => 1,
                        'score' => 2,
                        'content' => '了解一些，有阳光就能发电的设备',
                    ],[
                        'oid' => 2,
                        'score' => 3,
                        'content' => '比较清楚，新能源资产，绿色环保',
                    ]
                ],
            ],
            [
                'qid' => 3,
                'content' => '您的家庭收入状况？',
                'options' => [
                    [
                        'oid' => 0,
                        'score' => 1,
                        'content' => '10万元（含）以下',
                    ],[
                        'oid' => 1,
                        'score' => 2,
                        'content' => '10万至30万（含）',
                    ],[
                        'oid' => 2,
                        'score' => 3,
                        'content' => '30万以上',
                    ]
                ],
            ],
            [
                'qid' => 4,
                'content' => '您对产品投资期限的要求？',
                'options' => [
                    [
                        'oid' => 0,
                        'score' => 1,
                        'content' => '半年或更短的投资收益期限',
                    ],[
                        'oid' => 1,
                        'score' => 2,
                        'content' => '最多1年期的投资收益期限',
                    ],[
                        'oid' => 2,
                        'score' => 3,
                        'content' => '1-3年或更长的投资收益期限',
                    ]
                ],
            ],
            [
                'qid' => 5,
                'content' => '投资过风险最高的理财产品？',
                'options' => [
                    [
                        'oid' => 0,
                        'score' => 1,
                        'content' => '储蓄、银行理财、余额宝等风险极小的产品',
                    ],[
                        'oid' => 1,
                        'score' => 2,
                        'content' => '债券、信托等风险适中的固定收益产品',
                    ],[
                        'oid' => 2,
                        'score' => 3,
                        'content' => '股票、基金，股权，PE等有较高风险的产品',
                    ]
                ],
            ],
            [
                'qid' => 6,
                'content' => '您的投资亏损承受能力？',
                'options' => [
                    [
                        'oid' => 0,
                        'score' => 1,
                        'content' => '我不能接受亏损',
                    ],[
                        'oid' => 1,
                        'score' => 2,
                        'content' => '接受跌幅在30%以内',
                    ],[
                        'oid' => 2,
                        'score' => 3,
                        'content' => '接受跌幅在30%以上',
                    ]
                ],
            ]
        ]
    ],

    //新手翻倍天数
    'newbie_coupon_term'=>5,
];
