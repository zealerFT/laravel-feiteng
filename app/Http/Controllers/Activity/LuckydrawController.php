<?php
/**
 * 抽奖活动
 */
namespace App\Http\Controllers\Activity;

use App\Http\Controllers\CommonTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\LuckydrawRequest;
use App\DataServer\Front\ActivityApi;

class LuckydrawController extends Controller
{
    use CommonTrait;
    function __construct()
    {
        $this->setBase();
    }

    /**
     * 获取抽奖机会
     * @param LuckydrawRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function luckydrawChance(LuckydrawRequest $request){
        $type = $request->get('type');
        $activityApi = new ActivityApi();
        $luckydrawNumRel = $activityApi->accountLuckydrawNumByUId($this->userBaseInfo['userId'],$type);
        return makeSuccessMsg(['luckydrawNum'=>$luckydrawNumRel['data']['luckydrawNum']]);
    }


}