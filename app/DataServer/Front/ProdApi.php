<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午5:08
 */

namespace App\DataServer\Front;

class ProdApi extends FrontApi
{
    //api uri list
    const PROD_DETAIL = 'fe_agent/regular/product';   //产品详情
    const PROD_TARGET = 'fe_agent/tag';   //产品标签
    const PROD_ATTACH = 'fe_agent/attachment';   //产品附件
    const PROD_CONTRACT = 'fe_agent/contract';   //产品合同
    const CF_PROD_LIST = 'fe_agent/crowd/products';   //众筹产品列表
    const CF_PROD_DETAIL = 'fe_agent/crowd/product';   //众筹产品详情
    const CF_PROD_SUMMARY_BY_IDS = 'fe_agent/crowd/products/summary';   //众筹产品摘要
    const CF_PROD_FOLLOW_ADD = 'fe_agent/crowd/follow';   //众筹产品增加关注
    const CF_PROD_TRANSFER = 'cgweb/c2s/opt/crowd/onSaleList';   //众筹转让列表
    const CF_PROD_TRANSFER_DETAIL = 'cgweb/c2s/opt/crowd/transTrade';   //众筹订单转让详情
    const PROD_DP_CONFIG = 'fe_agent/dynamic/config';   //获取活期产品基本配置信息


    /**
     * 获取前台定期产品详细信息
     * @param $pid ->母产品id
     * @param $sceneType ->场景 1.PC 2.手机
     * @return mixed
     * @throws \Exception
     */
    function regularProd($pid, $sceneType = 2)
    {
        $params = ['pId' => $pid, 'sceneType' => $sceneType];
        return $this->method(self::PROD_DETAIL)->post($params);
    }

    /**
     * 产品标签
     * @param $pid -六位id
     * @return mixed
     */
    function prodTarget($pid)
    {
        $params = ['pId' => $pid];
        return $this->method(self::PROD_TARGET)->get($params);
    }

    /**
     * 获取产品附件
     * @param $pid -六位id
     * @return mixed
     */
    function prodAttach($pid)
    {
        $params = ['pId' => $pid];
        return $this->method(self::PROD_ATTACH)->get($params);
    }

    /**
     * 获取产品合同
     * @param $pid -六位id
     * @return mixed
     */
    function prodContract($pid)
    {
        $params = ['pId' => $pid];
        return $this->method(self::PROD_CONTRACT)->get($params);
    }

    /**
     * 众筹产品列表
     * @param int $pageId
     * @param int $pageSize
     * @return mixed
     */
    function cfProdList($pageId = 1, $pageSize = 10)
    {
        $params = ['pageId' => $pageId, 'pageSize' => $pageSize];
        return $this->method(self::CF_PROD_LIST)->get($params);
    }


    /**
     * 获取众筹产品详情
     * @param $pid
     * @return mixed
     */
    function cfProdDetail($pid)
    {
        return $this->method(self::CF_PROD_DETAIL)->get(['cId' => $pid]);
    }

    /**
     * 根据产品id去批量获取产品的摘要
     * @param $pidArr
     * @return mixed
     */
    function cfProdSummaryByIds($pidArr)
    {
        $pidStr = implode(',', $pidArr);
        return $this->method(self::CF_PROD_SUMMARY_BY_IDS)->get(['prodIds' => $pidStr]);
    }

    /**
     * 新增产品关注数
     * @param $mobile
     * @param $pid
     * @return mixed
     */
    function cfProdAddFollow($mobile, $pid)
    {
        $params = [
            'pid' => $pid,
            'mobile' => $mobile,
            'token' => $this->token
        ];
        return $this->method(self::CF_PROD_FOLLOW_ADD)->post($params);
    }

    /**
     * 众筹产品转让列表
     * @return mixed
     */
    function cfProdTransferList()
    {
        return $this->method(self::CF_PROD_TRANSFER)->get();
    }

    /**
     * 众筹转让列表中，额度等相关数据
     * @param $tradeId
     * @return mixed
     */
    function cfProdTransferDetail($tradeId)
    {
        return $this->method(self::CF_PROD_TRANSFER_DETAIL . '/' . $tradeId)->get();
    }

    /**
     * 获取活期产品基本配置（活期续标，锁定购买时间段的需求）
     * @return mixed
     */
    function getDpConfig()
    {
        return $this->method(self::PROD_DP_CONFIG)->get();
    }
}