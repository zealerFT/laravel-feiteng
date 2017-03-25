<?php
/**
 * 2017春节活动 1：领券活动
 * */

namespace App\Http\Controllers\Activity\SpringFestival2017;

use App\Http\Controllers\Controller;

use App\DataServer\Hybrid\UserService;

use App\Models\Activity\ActivityConfigure;
use App\Models\Activity\SpringFestivalOne;
use Illuminate\Database\QueryException;
use App\Jobs\RMQ\SendMsgToQueueJob;

class RedPacketController extends Controller
{
    const P_ACTIVITY_CODE = "spring_festival";
    const C_ACTIVITY_CODE = "red_packet";

    private $activityState;
    private $activityData;
    private $userId;

    /**
     * 判断活动是否有效
     * */
    private function checkActivityState()
    {
        $activityConfig = ActivityConfigure::where('p_activity_code', self::P_ACTIVITY_CODE)
            ->where('c_activity_code', self::C_ACTIVITY_CODE)->first();
        if (empty($activityConfig)) {
            $this->activityState = ["code" => 410, "errMsg" => "活动不存在"];
            return false;
        } else {
            $now = date('Y-m-d H:i:s');
            if ($now < $activityConfig['start_time']) {
                $this->activityState = ["code" => 411, "errMsg" => "活动未开始"];
                return false;
            } else if ($now > $activityConfig['end_time']) {
                $this->activityState = ["code" => 412, "errMsg" => "活动已结束"];
                return false;
            }

            $this->activityData = json_decode($activityConfig['activity_data'], true);
            return true;
        }
    }

    /**
     * 获取用户ID
     * */
    private function getUserId()
    {
        $userService = new UserService();
        $userBaseInfo = $userService->getUserInfo(false);
        if (empty($userBaseInfo) || empty($userBaseInfo["userId"])) {
            return false;
        }
        $this->userId = $userBaseInfo["userId"];
        return true;
    }

    /**
     * 判断用户是否领取礼包
     * */
    function redPacketState()
    {
        if (!$this->checkActivityState()) {
            return makeFailedMsg($this->activityState["code"], $this->activityState["errMsg"]);
        }
        if (!$this->getUserId()) {
            return makeFailedMsg(401, "此用户未登录");
        }

        $userIdRecord = SpringFestivalOne::where('user_id', $this->userId)->first();
        if (!empty($userIdRecord)) {
            return makeSuccessMsg(["hasGotRedPacket" => true]);// 已领取
        } else {
            return makeSuccessMsg(["hasGotRedPacket" => false]);// 未领取
        }
    }

    /**
     * 插入领取记录
     * */
    function insertRedPacket()
    {
        if (!$this->checkActivityState()) {
            return makeFailedMsg($this->activityState["code"], $this->activityState["errMsg"]);
        }
        if (!$this->getUserId()) {
            return makeFailedMsg(401, "此用户未登录");
        }

        $userIdRecord = SpringFestivalOne::where('user_id', $this->userId)->first();
        if (!empty($userIdRecord)) {
            return makeFailedMsg(420, "已领取礼包");// 已领取
        } else {
            // 未领取
            try {
                // 发送消息队列
                $rmgConf = config('rmq.activity_2017Spring_act1');
                $exchange = $rmgConf['exchange'];
                $routingKey = $rmgConf['routingKey'];
                $exchangeType = $rmgConf['exchangeType'];
                $queueName = $rmgConf['queueName'];

                $msgBody = ['uid' => $this->userId, 'coupons' => $this->activityData['coupons'], 'remark'=>'春节活动1发券', 'repeat' => 0];
                $job = new SendMsgToQueueJob($queueName, json_encode($msgBody), $exchange, $routingKey, $exchangeType, 'Register Info');
                $this->dispatch($job->onQueue(config('sys-config.activity_queue')));

                // 插入记录
                SpringFestivalOne::create(['user_id' => $this->userId]);
                return makeSuccessMsg(["gotRedPacketSuccess" => true]);
            } catch (QueryException $e) {
                \Log::critical('【新年活动】领取礼包异常：' . $e->getCode() . '->' . $e->getMessage());
                return makeFailedMsg(430, "领取礼包失败");
            }
        }
    }
}
