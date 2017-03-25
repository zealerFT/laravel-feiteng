<?php
/**
 * 邮件发送队列
 */
namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mail;

class SendMailJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $receiver;
    private $subject;
    private $data;
    private $attach;
    private $mailType;

    /**
     * SendMailJob 发送邮件
     * @param $receiver string 接收人
     * @param $subject string 主题（邮件标题）
     * @param string $data mix 邮件正文 ：1.纯文本  $data String;2.带视图  $data Array   ['view'=>'视图文件','view_data'=>'视图数据']
     * @param null $attach string 附件地址
     * @param $mailType string 邮件类型
     */
    public function __construct($receiver, $subject, $data = '', $attach = null,$mailType='')
    {
        $this->receiver = $receiver;
        $this->subject = $subject;
        $this->data = $data;
        $this->attach = $attach;
        $this->mailType = $mailType;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try{
            if (is_array($this->data)) {
                //有视图
                Mail::send($this->data['view'], $this->data['view_data'], function ($message) {
                    $message->to($this->receiver)->subject($this->subject);
                    //在邮件中上传附件
                    if ($this->attach) {
                        $message->attach($this->attach);
                    }
                });
            } else {
                //无视图
                Mail::raw($this->data, function ($message) {
                    $message->to($this->receiver)->subject($this->subject);
                    //在邮件中上传附件
                    if ($this->attach) {
                        $message->attach($this->attach);
                    }
                });
            }
            if($this->mailType != 'log'){
                \Log::info('邮件发送成功:'.$this->subject.' ,To:'.$this->receiver);
            }
        }catch(\Exception $e){
            if($this->mailType != 'log'){
                \Log::info('邮件发送失败:'.$this->subject.' ,To:'.$this->receiver.',Error Msg:'.$e->getMessage());
            }else{
                \Log::debug('邮件发送失败:'.$this->subject.' ,To:'.$this->receiver.',Error Msg:'.$e->getMessage());
            }

        }
    }
}
