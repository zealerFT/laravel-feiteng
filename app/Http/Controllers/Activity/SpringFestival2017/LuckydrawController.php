<?php
/**
 * 2017春节活动 3：获取抽奖机会 和 抽奖记录
 */
namespace App\Http\Controllers\Activity\SpringFestival2017;

use App\Http\Controllers\Controller;
use App\DataServer\Hybrid\UserService;
use App\Jobs\RMQ\SendMsgToQueueJob;
use App\Models\Activity\ActivityConfigure;
use App\Models\Activity\LuckydrawChances;
use App\Models\Activity\LuckydrawRecords;
use Illuminate\Database\QueryException;

class LuckydrawController extends Controller
{
    const P_ACTIVITY_CODE = "spring_festival";
    const C_ACTIVITY_CODE = "lucky_draw";

    private $activityStart;
    private $activityEnd;
    private $activityState;
    private $userId;
    private $chances;//累计抽奖机会
    private $otherChances;//活动其它规则产生的抽奖机会，如活动期间每天赠送一次机会
    private $records;//中奖记录

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
            $this->activityStart = $activityConfig['start_time'];
            $this->activityEnd = $activityConfig['end_time'];
            if ($now < $this->activityStart) {
                $this->activityState = ["code" => 411, "errMsg" => "活动未开始"];
                return false;
            } else if ($now > $this->activityEnd) {
                $this->activityState = ["code" => 412, "errMsg" => "活动已结束"];
                return false;
            }
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
     * 获取用户  累计的抽奖机会 && 活动期间每天登录一次送一次机会
     * */
    private function luckydrawChances()
    {
        $now = date('Y-m-d H:i:s');
        if ($now >= $this->activityStart && $now <= $this->activityEnd) {
            $luckydrawChances = LuckydrawChances::where('user_id', $this->userId)->first();
            if (!empty($luckydrawChances)) {
                $this->chances = $luckydrawChances["luckydraw_chances"];//累计的抽奖机会
                $this->otherChances = $luckydrawChances["other_chances"];//当前赠送的抽奖机会
                $otherChancesLastTime = $luckydrawChances['other_chances_last_time'];
                $otherChancesLastDate = explode(' ', $otherChancesLastTime)[0];
                $interval = abs((int)date_diff(date_create(date('Y-m-d')), date_create($otherChancesLastDate))->format("%R%a"));
                if($interval > 0) {
                    $this->otherChances = $this->otherChances + 1;
                    //更新赠送的抽奖机会
                    try {
                        LuckydrawChances::where('user_id', $this->userId)->where('p_activity_code', self::P_ACTIVITY_CODE)
                            ->where('c_activity_code', self::C_ACTIVITY_CODE)->update(['other_chances' =>$this->otherChances, 'other_chances_last_time' => date('Y-m-d H:i:s')]);
                    } catch (QueryException $e) {
                        \Log::critical('【新年活动】更新每天赠送抽奖机会异常：' . $e->getCode() . '->' . $e->getMessage());
                        return false;
                    }
                }
            } else {
                $this->chances = 0;//累计的抽奖机会
                $this->otherChances = 1;//活动期间第一次登录送一次抽奖机会
                try{
                    //插入一条记录
                    LuckydrawChances::create(['user_id' => $this->userId, 'p_activity_code'=> self::P_ACTIVITY_CODE,
                        'c_activity_code'=>self::C_ACTIVITY_CODE, 'luckydraw_chances' => 0,
                        'other_chances' => 1, 'other_chances_last_time' => date('Y-m-d H:i:s')
                    ]);
                } catch (QueryException $e) {
                    \Log::critical('【新年活动】插入每天赠送抽奖机会异常：' . $e->getCode() . '->' . $e->getMessage());
                    return false;
                }
            }

            return true;
        } else {
            $this->chances = 0;
            $this->otherChances = 0;
            return false;
        }
    }

    /**
     * 获取用户中奖记录
     * */
    private function luckydrawRecords()
    {
        $luckydrawRecords = LuckydrawRecords::where('user_id', $this->userId)
                                            ->select(['luckydraw_records', 'records_time'])->get();
        if(!empty($luckydrawRecords)) {
            $this->records = $luckydrawRecords;
            return true;
        } else {
            $this->records = [];
            return false;
        }
    }

    /**
     * 产生抽奖结果
     * */
    private function generateLuckydraw()
    {
        $r = rand(1, 1000);// 返回随机整数
        switch ($r) {
            case ($r <= 90):
                return 0;
                break;
            case (90 < $r && $r <= 240):
                return 1;
                break;
            case(240 < $r && $r <= 390):
                return 2;
                break;
            case (390 < $r && $r <= 640):
                return 3;
                break;
            case(640 < $r && $r <= 649):
                return 4;
                break;
            case(649 < $r && $r <= 849):
                return 5;
                break;
            case(849 < $r && $r <= 999):
                return 6;
                break;
            case (999 < $r && $r <= 1000):
                return 7;
                break;
            default:
                return -1;
                break;
        }
    }

    /**
     * 获取用户 抽奖机会 & 中奖记录
     * */
    function luckydrawChancesAndRecords()
    {
        if (!$this->checkActivityState()) {
            return makeFailedMsg($this->activityState["code"], $this->activityState["errMsg"]);
        }
        if (!$this->getUserId()) {
            return makeFailedMsg(401, "此用户未登录");
        }

        if ($this->luckydrawChances() && $this->luckydrawRecords()) {
            $remainedChances = $this->chances + $this->otherChances - count($this->records);
            return makeSuccessMsg(["luckydrawChances" => $remainedChances, "luckydrawRecords" => $this->records]);
        } else {
            return makeFailedMsg(410, "未获取到数据");
        }
    }

    /**
     * 插入中奖记录
     * */
    function luckydraw()
    {
        if (!$this->checkActivityState()) {
            return makeFailedMsg($this->activityState["code"], $this->activityState["errMsg"]);
        }
        if (!$this->getUserId()) {
            return makeFailedMsg(401, "此用户未登录");
        }

        if ($this->luckydrawChances() && $this->luckydrawRecords()) {
            $remainedChances = $this->chances + $this->otherChances - count($this->records);
            if ($remainedChances > 0) {
                try {
                    $luckydrawPosition = $this->generateLuckydraw();
                    LuckydrawRecords::create(['user_id' => $this->userId, 'p_activity_code' => self::P_ACTIVITY_CODE, 'c_activity_code' => self::C_ACTIVITY_CODE, 'luckydraw_records' => $luckydrawPosition, 'records_time' => date('Y-m-d H:i:s')]);
                    // 发送消息队列
                    $rmgConf = config('rmq.activity_2017Spring_act3');
                    $exchange = $rmgConf['exchange'];
                    $routingKey = $rmgConf['routingKey'];
                    $exchangeType = $rmgConf['exchangeType'];
                    $queueName = $rmgConf['queueName'];

                    $msgBody = ['uid' => $this->userId, 'position' => $luckydrawPosition, 'c_activity_code' => self::C_ACTIVITY_CODE];
                    $job = new SendMsgToQueueJob($queueName, json_encode($msgBody), $exchange, $routingKey, $exchangeType, 'Register Info');
                    $this->dispatch($job->onQueue(config('sys-config.activity_queue')));

                    return makeSuccessMsg(["luckydrawPosition" => $luckydrawPosition]);
                } catch (QueryException $e) {
                    \Log::critical('【新年活动】转盘抽奖异常：' . $e->getCode() . '->' . $e->getMessage());
                    return makeFailedMsg(430, "转盘抽奖失败");
                }
            } else {
                return makeFailedMsg(420, "无抽奖机会");
            }
        } else {
            return makeFailedMsg(410, "未获取到数据");
        }
    }

    function luckydrawTime()
    {
        if (!$this->checkActivityState()) {
            return makeFailedMsg($this->activityState["code"], $this->activityState["errMsg"]);
        }
        return makeSuccessMsg(["actStart" => $this->activityStart, "actEnd" => $this->activityEnd]);
    }
}
