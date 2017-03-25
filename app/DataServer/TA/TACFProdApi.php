<?php
/**
 * TA活期产品相关api
 */

namespace App\DataServer\TA;

class TACFProdApi extends TAApi
{
    //api uri list
    const PROD_LIST   = 'cgweb/c2s/opt/crowd/products';   //获取众筹产品列表
    const PROD_DETAIL   = 'cgweb/c2s/opt/crowd/product';   //获取众筹产品详情
    const PROD_TRANSFER_DERIVE   = 'cgweb/c2s/opt/crowd/transIds';   // 获取众筹产品 衍生的转让交易信息

    /**
     * 获取众筹产品列表
     * @param int $pageId
     * @param int $pageSize
     * @return mixed
     */
    function getProdList($pageId =1 ,$pageSize = 10){
        return $this->method(self::PROD_LIST)->get(['pageId'=>$pageId,'pageSize'=>$pageSize]);
    }


    /**
     * 获取众筹产品详情
     * @param $pid
     * @return mixed
     */
    function getProdDetail($pid){
        return $this->method(self::PROD_DETAIL)->get(['prodId'=>$pid]);
    }

    /**
     * 获取众筹产品 转让交易衍生记录
     * @param $sourceId
     * @return mixed
     */
    public function getTransferDerive($sourceId)
    {
        return $this->method(self::PROD_TRANSFER_DERIVE.'/'.$sourceId)->get();
    }

}