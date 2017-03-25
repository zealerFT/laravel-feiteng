<?php
/**
 * TA定期产品相关api
 */

namespace App\DataServer\TA;

class TARegularProdApi extends TAApi
{
    //api uri list
    const REGULAR_PROD_LIST   = 'cgweb/c2s/opt/regular/products';   //获取定期产品列表
    const REGULAR_PROD_DETAIL   = 'cgweb/c2s/opt/regular/product';   //获取定期产品详情

    /**
     * 获取定期产品列表
     * @param int $series
     * @param int $remainCreditFrom
     * @param string $orderTypeProdId
     * @param int $pageId
     * @param int $pageSize
     * @return mixed
     */
    function getRegularProdList($series = 0,$remainCreditFrom=-1,$orderTypeProdId ='DESC',$pageId = 1,$pageSize = 10){
        $param=[
            'orderTypeProdId'=>$orderTypeProdId,
            'pageId'=>$pageId,
            'pageSize'=>$pageSize,
        ];
        if($series !=0){
            $param['series'] = $series;
        }
        if($remainCreditFrom != -1){
            $param['remainCreditFrom'] = $remainCreditFrom;
        }
        return $this->method(self::REGULAR_PROD_LIST)->get($param);
    }

    /**
     * 获取TA定期子产品详细信息
     * @param $pid ->12位
     * @return mixed
     */
    function getRegularProdDetail($pid){
        $param = ['prodId'=>$pid];
        return $this->method(self::REGULAR_PROD_DETAIL)->get($param);
    }

    /**
     * 获取首页定期产品列表逻辑
     * @return array
     */
    function getHomeRegularProds(){
        $taProdList = $this->getRegularProdList(0,0,'ASC',1,100);
        $taProdListData = $taProdList['data']['result'];
        //筛选出三个系列定期产品未售完产品
        $prodList = [
            1  =>[],
            2  =>[],
            3  =>[],
        ];
        foreach($taProdListData as $taProd){
            switch($taProd['series']){
                case 1:
                    if(empty($prodList[1])){
                        $prodList[1] = $taProd;
                    }
                    break;
                case 2:
                    if(empty($prodList[2])){
                        $prodList[2]=$taProd;
                    }
                    break;
                case 3:
                    if(empty($prodList[3])){
                        $prodList[3]=$taProd;
                    }
                    break;
            }
        }
        //如果有某产品系列中存在全卖完的产品则用该系列下产品id最大的一个已卖完产品顶替
        foreach($prodList as $series=> &$prod){
            if(!sizeof($prod)){//说明这个系列下的产品都卖完了
                $seriesTaProdList = $this->getRegularProdList($series,-1,'DESC',1,1);
                $seriesTaProdListData = $seriesTaProdList['data']['result'];
                $prod = $seriesTaProdListData[0];
            }
            //产品份额需要减去冻结份额
            $prod['remainCredit'] = $prod['remainCredit'] - $prod['frozenCredit'];
            //判断产品售卖状态
            $prod['saleStatus'] = getRegularProdSaleStatus($prod);

        }
        return $prodList;
    }
}