<?php
/**
 * 日志管理配置
 * User: aishan
 * Date: 16-12-14
 * Time: 下午6:03
 */
return [
    'log_local'=>env('LOG_LOCAL',true),//是否开启本地日志
    //开启生产环境的日志邮件发送和日志推送logStash
    'log_production'=>env('LOG_PRODUCTION',false),
    'mail_receiver'=>env('LOG_NOTICE_MAIL',''),//邮件接收人配置
];