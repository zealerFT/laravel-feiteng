<?php
/**
 * 2017春节活动 2：投资排行
 * */

namespace App\Http\Controllers\Activity\SpringFestival2017;

use App\Http\Controllers\Controller;
use App\DataServer\Hybrid\UserService;
use App\Models\Activity\ActivityConfigure;
use App\Models\Activity\SpringFestivalRank;
use Illuminate\Database\QueryException;

class InvestmentRankController extends Controller
{
    const P_ACTIVITY_CODE = "spring_festival";
    const C_ACTIVITY_CODE = "invest_rank";
    const RANK_QUANTITY = 10;

    private $activityStart;
    private $activityEnd;
    private $activityState;
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

    function investmentRankInfo()
    {
        if (!$this->checkActivityState()) {
            return makeFailedMsg($this->activityState["code"], $this->activityState["errMsg"]);
        }
        if (!$this->getUserId()) {
            return makeFailedMsg(401, "此用户未登录");
        }

        $rankResult = SpringFestivalRank::select(\DB::raw('user_id, user_mobile, sum(fee) as total, max(order_time) as order_time'))
                                        ->groupBy('user_mobile')->orderBy('total', 'desc')->orderBy('order_time', 'asc')
                                        ->get();

        if(sizeof($rankResult)){
            /**
             * 前十名排行榜
             * */
            $highInvestmentRank = $rankResult->take(self::RANK_QUANTITY);
            $highInvestmentRank->map(function ($item, $key) {
                $item['user_mobile'] = substr_replace($item['user_mobile'], '****', 3, 4);
                $item['rank'] = $key + 1;
                if($item['user_id'] == $this->userId) {
                    $item['isMe'] = true;
                } else {
                    $item['isMe'] = false;
                }

                return $item;
            });
            /**
             * 我的好友投资详情
             * */
            $friendsInvestInfo = $rankResult->where('user_id', $this->userId)->first();
            if (!empty($friendsInvestInfo)) {
                $rank = $rankResult->search($friendsInvestInfo) + 1;
                $friendsInvestInfo["rank"] = $rank;

                // 距离各个排名差距
                switch ($rank) {
                    case 1:
                        $upLevel = -1;
                        break;
                    case ($rank == 2 || $rank == 3) :
                        $upLevel = 0;
                        break;
                    case ($rank <= 10 && $rank >= 4):
                        $upLevel = 2;
                        break;
                    case ($rank <= 50 && $rank >= 11):
                        $upLevel = 9;
                        break;
                    case ($rank <= 100 && $rank >= 51):
                        $upLevel = 49;
                        break;
                    case ($rank >= 101):
                        $upLevel = 99;
                        break;
                    default:
                        $upLevel = -100;
                        break;
                }

                if ($upLevel >= 0) {
                    $friendsInvestInfo["rangeToUpLevel"] = $rankResult[$upLevel]["total"] - $friendsInvestInfo["total"];
                } else {
                    $friendsInvestInfo["rangeToUpLevel"] = 0;
                }

                return makeSuccessMsg(["rankResult" => $highInvestmentRank, "myFriendsInvest" => $friendsInvestInfo]);
            } else {
                $friendsInvestInfo["noRank"] = true;
                $length = count($rankResult);
                if($length <= 100) {
                    $friendsInvestInfo["rangeToUpLevel"] = $rankResult[$length-1]["total"];
                    $rank = $length + 1;

                    // 计算排名
                    switch ($rank) {
                        case 1:
                            $myRealRank = 2;
                            break;
                        case ($rank == 2 || $rank == 3) :
                            $myRealRank = 4;
                            break;
                        case ($rank <= 10 && $rank >= 4):
                            $myRealRank = 11;
                            break;
                        case ($rank <= 50 && $rank >= 11):
                            $myRealRank = 51;
                            break;
                        case ($rank <= 100 && $rank >= 51):
                            $myRealRank = 101;
                            break;
                        default:
                            $myRealRank = 999;
                            break;
                    }

                    $friendsInvestInfo["rank"] = $myRealRank;
                } else {
                    $friendsInvestInfo["rangeToUpLevel"] = $rankResult[99]["total"];
                    $friendsInvestInfo["rank"] = 101;
                }
                return makeSuccessMsg(["rankResult" => $highInvestmentRank, "myFriendsInvest" => $friendsInvestInfo]);
            }
        } else {
            return makeFailedMsg(410, "暂无数据");
        }
    }

    function investmentRankTime()
    {
        if (!$this->checkActivityState()) {
            return makeFailedMsg($this->activityState["code"], $this->activityState["errMsg"]);
        }
        return makeSuccessMsg(["actStart" => $this->activityStart, "actEnd" => $this->activityEnd]);
    }
}