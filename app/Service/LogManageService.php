<?php
/**
 * 日志管理
 */
namespace App\Service;

use App\Jobs\RMQ\SendLogMsgToQueueJob;
use App\Jobs\SendMailJob;
use Illuminate\Foundation\Bus\DispatchesJobs;

class LogManageService{
    private $record;
    use DispatchesJobs;
    function __construct($record)
    {
        $this->record = $record;
    }

    /**
     * 发送日志信息邮件
     */
    function sendEmail(){
        //日志格式化
        $templateRecord = $this->record['level_name'].':'.
            $this->record['message'].'<->'.
            $this->record['datetime']->format('Y-m-d H:i:s').'=======>'.
            json_encode($this->record['context']);
        //发送
        $receiver = config('log-manage.mail_receiver');
        $receiverArr = explode(',',$receiver);
        if(count($receiverArr)){
            foreach($receiverArr as $mail){
                if(!empty($mail)){
                    $mailJob = new SendMailJob($mail,'Agile Log Notice',$templateRecord,null,'log');
                    $this->dispatch($mailJob->onQueue('agile.log'));
                }
            }
        }
    }

    /**
     * 推送日志到RMQ
     */
    function logToRMQ(){
        $exchange = config('rmq.log.exchange');
        $routingKey = config('rmq.log.routingKey');
        $exchangeType = config('rmq.log.exchangeType');
        $queueName = config('rmq.log.queueName');
        $msgBody = json_encode($this->formatForLogStash($this->record,'agile'));
        $rmqJob = new SendLogMsgToQueueJob($queueName,$msgBody,$exchange,$routingKey,$exchangeType,'log');
        $this->dispatch($rmqJob->onQueue('agile.log'));
    }

    /**
     * 格式化数据符合logstash
     * @param array $record
     * @param string $systemName
     * @return array
     */
    protected function formatForLogStash(array $record,$systemName)
    {
        if (empty($record['datetime'])) {
            $record['datetime'] = gmdate('c');
        }
        $message = array(
            '@timestamp' => $record['datetime'],
            '@source' => $systemName,
            '@fields' => array(),
        );
        if (isset($record['message'])) {
            $message['@message'] = $record['message'];
        }
        if (isset($record['channel'])) {
            $message['@tags'] = array($record['channel']);
            $message['@fields']['channel'] = $record['channel'];
        }
        if (isset($record['level'])) {
            $message['@fields']['level'] = $record['level'];
        }
        if (isset($record['level_name'])) {
            $message['@fields']['level_name'] = $record['level_name'];
        }
        if (isset($record['extra']['server'])) {
            $message['@source_host'] = $record['extra']['server'];
        }
        if (isset($record['extra']['url'])) {
            $message['@source_path'] = $record['extra']['url'];
        }
        if (!empty($record['extra'])) {
            foreach ($record['extra'] as $key => $val) {
                $message['@fields'][$systemName . $key] = $val;
            }
        }
        if (!empty($record['context'])) {
            foreach ($record['context'] as $key => $val) {
                $message['@fields'][$systemName . $key] = $val;
            }
        }

        return $message;
    }
}