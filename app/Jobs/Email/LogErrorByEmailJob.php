<?php
namespace App\Jobs\Email;

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
        $exchange = 'e.discount.topic';
        $routingKey = 'r.discount.signUp';
        $exchangeType = 'topic';
        $queueName = 'q.discount.signUp';
        $msgBody = ['uid'=>$this->uid];
        $job = new SendMsgToQueueJob($queueName,json_encode($msgBody),$exchange,$routingKey,$exchangeType,'Register Info');
        $this->dispatch($job->onQueue(config('sys-config.register_queue')));
    }
}