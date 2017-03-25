<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-14
 * Time: 下午8:10
 */

namespace App\Http\Controllers\Common;

use App\DataServer\Front\ProdApi;
use App\Exceptions\FrontApi\CMSApiException;
use App\Http\Controllers\Controller;
use App\DataServer\Front\CMSApi;
use App\DataServer\TA\TADynamicProdApi;
use App\DataServer\TA\TARegularProdApi;
use Carbon\Carbon;


class HomeController extends Controller
{
    /**
     * 首页
     * @return \Illuminate\Http\JsonResponse
     */
    public function home()
    {
        $homeData = [];

        //banner
        $cmsApi = new CMSApi();
        try {
            $bannerList = $cmsApi->banner(1, 'home_h5', 1);
            $homeData['banner'] = $bannerList['result']['data'];
        } catch (CMSApiException $e) {
            \Log::critical('Home:' . myException($e));
            $homeData['banner'] = [];
        }

        //Announcement
        try {
            $announcementList = $cmsApi->announcements(2, Carbon::now()->format('Y-m-d H:i:s'), 1, 1);
            $homeData['announcement'] = $announcementList['result']['data'];
        } catch (CMSApiException $e) {
            \Log::critical('Home:' . myException($e));
            $homeData['announcement'] = [];
        }

        //product list
        try {
            $taRegularProdApi = new TARegularProdApi();
            $homeData['regularProds'] = $taRegularProdApi->getHomeRegularProds();
        } catch (\Exception $e) {
            \Log::critical('Home-regularProds:' . myException($e));
            $homeData['regularProds'] = [];
        }

        //周周涨
        try {
            $taDynamicProdApi = new TADynamicProdApi();
            $taDynamicProdData = $taDynamicProdApi->getDynamicProd();
            if ($taDynamicProdData['stateCode'] != '00000') {
                $homeData['dynamicProd'] = [];
            }

            $dpBaseInfo = $taDynamicProdData['dpBaseInfo'];

            // 判断活期产品是否处在续标锁定阶段
            $prodApi = new ProdApi();
            $dpConfig = $prodApi->getDpConfig();
            if ($dpConfig['code'] == '200') {
                $now = date('Y-m-d H:i:s');
                $start = isset($dpConfig['result']['lockStartTime']) ? $dpConfig['result']['lockStartTime'] : '';
                $end = isset($dpConfig['result']['lockEndTime']) ? $dpConfig['result']['lockEndTime'] : '';
                if ($now >= $start && $now <= $end) {
                    // 将剩余额度置0，以阻止购买
                    $dpBaseInfo['remainCredit'] = 0;
                }
            }
            $homeData['dynamicProd'] = $dpBaseInfo;
        } catch (\Exception $e) {
            \Log::critical('Home-dynamicProd:' . myException($e));
            $homeData['dynamicProd'] = [];
        }
        return makeSuccessMsg($homeData);
    }


}