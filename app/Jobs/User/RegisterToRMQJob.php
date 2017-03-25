<?php
namespace App\Jobs\User;

use App\Jobs\Job;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\RMQ\SendMsgToQueueJob;

class RegisterToRMQJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue,SerializesModels,DispatchesJobs;
    public $uid;

    /**
     * RegisterToRMQJob constructor.
     * @param $uid
     */
    public function __construct($uid)
    {
        $this->uid =  $uid;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $rmgConf = config('rmq.reg');
        $exchange = $rmgConf['exchange'];
        $routingKey = $rmgConf['routingKey'];
        $exchangeType = $rmgConf['exchangeType'];
        $queueName = $rmgConf['queueName'];
        $msgBody = ['uid'=>$this->uid];
        $job = new SendMsgToQueueJob($queueName,json_encode($msgBody),$exchange,$routingKey,$exchangeType,'Register Info');
        $this->dispatch($job->onQueue(config('sys-config.register_queue')));
    }
}