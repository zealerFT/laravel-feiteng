<?php
/**
 * 系统配置相关
 */
namespace App\Http\Controllers;

use App\Jobs\RMQ\SendMsgToQueueJob;

class SystemController extends Controller
{
    function config(){
        $configData = [
            'is_debug'      =>env('APP_DEBUG',true),
            'is_cdn'        =>env('CDN_ENABLE',false),
            'cdn'           =>env('CDN_URL',''),
            'version'       =>env('APP_VERSION','1.0'),
            'fake_sms'      =>env('SMS_FAKE',true),
            'tongji_baidu'  =>env('TONGJI_BAIDU',''),
        ];
        $configStr ='';
        foreach($configData as $index => $value){
            $configStr .= 'var '.$index.' = '.json_encode($value).';';
        }
        return response($configStr)->header('Content-Type','text/javascript');
    }

    function testRmq(){
        $exchange = 'e.discount.topic';
        $exchangeType = 'topic';
        $queueName = 'q.discount.signUp';
        $msgBody = ['uid'=>1254];
        $job = new SendMsgToQueueJob($queueName,json_encode($msgBody),$exchange,$exchangeType,'Register Info');
        $this->dispatch($job->onQueue('agile.register'));
        echo 'Finish';
    }


}