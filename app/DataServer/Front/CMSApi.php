<?php
/**
 *
 */

namespace App\DataServer\Front;

use App\Exceptions\BaseApiException;
use App\Exceptions\FrontApi\CMSApiException;

class CMSApi extends FrontApi
{
    //api uri list
    const BANNER = 'fe_agent/home/banners';   //banner
    const ANNOUNCEMENT = 'fe_agent/home/announcements';   //banner


    /**
     * 获取公告
     * @param int $slideStatus banner 启用状态 0.禁用 1.启用 2.全部
     * @param string $slideCate 分类： home home_h5 app_start_page 全部则传空值
     * @param int $pageId
     * @param int $pageSize
     * @return mixed
     * @throws CMSApiException
     */
    function banner($slideStatus =1 ,$slideCate = '',$pageId =1 ,$pageSize = 10){
        $param = [
            'pageId'  =>1,
            'pageSize'  =>$pageSize,
        ];
        if($slideStatus!=2){
            $param['slideStatus'] = $slideStatus;
        }
        if(!empty($slideStatus)){
            $param['slideCate'] = $slideCate;
        }
        try{
            return $this->method(self::BANNER)->get($param);
        }catch(BaseApiException $e){
            throw new CMSApiException('获取banner失败:'.$e->getMessage());
        }

    }

    /**
     * 获取公告
     * @param string $validDate 处在指定时间点的公告 e.g:2016-08-08 12:25:45 默认是当前时间
     * @param int $platform 0:全部 1:PC端 2:移动端
     * @param int $pageId
     * @param int $pageSize
     * @return mixed
     * @throws CMSApiException
     */
    function announcements($platform = 0,$validDate = '',$pageId=1,$pageSize=10){
        $param = [
            'pageId'  =>$pageId,
            'pageSize'  =>$pageSize
        ];
        if(!empty($validDate)){
            $param['validDate'] = $validDate;
        }
        $param['platform'] = $platform;
        try{
            return $this->method(self::ANNOUNCEMENT)->get($param);
        }catch(BaseApiException $e){
            throw new CMSApiException('获取公告出错：'.$e->getMessage());
        }

    }


}