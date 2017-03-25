<?php
namespace App\Http\Controllers\Activity\HomeActivityInfo;

use App\Http\Controllers\Controller;
use App\DataServer\Front\ActivityApi;

Class SpringFestival2017Controller extends Controller {

    function springFestival2017Info()
    {
        $actApi = new ActivityApi();
        $actInfo = $actApi->springFestival2017();
        if ($actInfo['code'] != 200) {
            return makeFailedMsg(410, '获取活动信息失败：' . $actInfo['message']);
        }
        return makeSuccessMsg(['actInfo'=>$actInfo['result']]);
    }
}
