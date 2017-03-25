<?php
/**
 * 优惠券相关
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-15
 * Time: 下午5:08
 */

namespace App\DataServer\Front;

use App\DataServer\TA\TARegularProdApi;

use App\Exceptions\FrontApi\CouponApiException;
use Carbon\Carbon;
class CouponApi extends FrontApi
{
    private $userBaseInfo;
    //api uri list
    const COUPON_DETAIL   = 'fe_agent/coupons/coupon';   //获取指定uuid的优惠券详情
    const COUPON_MULTI_DETAIL   = 'fe_agent/coupons';   //批量获取指定uuid的优惠券详情
    const COUPON_ENABLE_FOR_PROD   = 'fe_agent/avalCoupons';   //通过产品id获取当前用户的可用优惠券
    function __construct()
    {
        parent::__construct();
        $this->userBaseInfo = \Cache::get($this->token);

    }

    /**
     * 获取指定uuid的优惠券详情
     * @param string $couponUUId
     * @return mixed
     */
    function couponDetail($couponUUId){
        return  $this->method(self::COUPON_DETAIL)->get(['couponId'=>$couponUUId]);
    }

    /**
     * 批量获取指定uuid的优惠券详情
     * @param array $couponUUIdArr
     * @return mixed
     */
    function couponMultiDetail($couponUUIdArr=[]){
        $couponUUIdStr = implode(',',$couponUUIdArr);
        return  $this->method(self::COUPON_MULTI_DETAIL)->get(['couponIds'=>$couponUUIdStr]);
    }

    /**
     * 验证下单时的优惠券是否合法并返回支付接口所需优惠券数据
     * @param array $couponArr
     * @param $prodId
     * @param $share
     * @return array
     * @throws \Exception
     */
    function checkCouponForPayment($couponArr=[],$prodId,$share){

        if(sizeof($couponArr)){//如果是空数组则返回空
            $orderCoupons = [];
            //获取产品信息
            $prodApi = new TARegularProdApi();
            $prodInfo = $prodApi->getRegularProdDetail($prodId);
            if($prodInfo['data']){//定期产品才检测优惠券，众筹产品跳过
                $prodSeries = $prodInfo['data']['series'];
                switch($prodSeries){
                    case 1:$prodSeriesName='快牛计划';break;
                    case 2:$prodSeriesName='金牛计划';break;
                    case 3:$prodSeriesName='稳牛计划';break;
                    default:
                        throw new CouponApiException('该产品系列不能使用优惠券',422);
                }
                $prodPrice = $prodInfo['data']['price'];//产品单价
                $purchaseTotal = $prodPrice * $share;//订单总额
                //判断优惠券是否合法
                $couponApi = new CouponApi();
                foreach($couponArr as $coupon){
                    $couponDetail = $couponApi->couponDetail($coupon);
                    if(!empty($couponDetail['result'])){
                        $couponDetail = $couponDetail['result'];
                        //判断优惠券是否已使用
                        if($couponDetail['state'] != 1){
                            throw new CouponApiException('优惠券已被使用',422);
                        }
                        //判断优惠券是否能使用该产品
                        if(!stristr($couponDetail['restrictedProductSeries'],$prodSeriesName) && !empty($couponDetail['restrictedProductSeries'])){
                            throw new CouponApiException('优惠券不能用在此产品',422);
                        }

                        //判断优惠券是否在可用的时间段
                        $couponStartDate = date('Y-m-d',strtotime($couponDetail['startTime']));
                        $couponEndDate = date('Y-m-d',strtotime($couponDetail['endTime'])+24*3600);
                        $nowTime = Carbon::now()->format('Y-m-d H:i:s');
                        if($nowTime < $couponStartDate){
                            throw new CouponApiException('优惠券还没到可用时间',422);
                        }
                        if($nowTime >= $couponEndDate){
                            throw new CouponApiException('优惠券已过期',422);
                        }
                        //判断优惠券是否属于该投资人
                        if($this->userBaseInfo['userId'] != $couponDetail['userId']){
                            throw new CouponApiException('优惠券不属于该用户',422);
                        }
                        //根据优惠券类型 判断优惠券是否符合使用条件 卡券类型：1.代金券 2.加息券 3.返现券,4.体验金
                        $couponUseData = json_decode($couponDetail['data'],1);
                        $couponMinFee = $couponUseData['mini_fee'];//优惠券使用最小金额
                        $couponAmount = 0;
                        $couponAmountTerm = 0;
                        $couponSameAsProd = 1;//体验金不与所购买产品同周期
                        $couponAmountReturn = 1;//体验金金额不会返还给用户
                        $couponRate = 0;
                        $couponRateTerm = 0;
                        $couponDiscount = 0;

                        if($couponDetail['type'] == 1){
                            if($couponMinFee > $purchaseTotal){
                                throw new CouponApiException('代金券未达到最小使用金额',422);
                            }
                            $couponAmount = $couponUseData['coupon_amount'];
                            $couponAmountTerm = 0;
                            $couponSameAsProd = 1;
                            $couponAmountReturn = 1;
                        }elseif($couponDetail['type'] == 3){
                            if($couponMinFee > $purchaseTotal){
                                throw new CouponApiException('返现券未达到最小使用金额',422);
                            }
                            $couponDiscount = $couponUseData['coupon_discount'];
                        }elseif($couponDetail['type'] == 2){//加息券
                            $couponMaxFee = $couponUseData['max_fee'];//优惠券使用最大金额
                            $couponSameAsProd = $couponUseData['coupon_same_as_prod'];
                            if($couponMinFee > $purchaseTotal){
                                throw new CouponApiException('订单金额未达到最小使用金额',422);
                            }elseif($couponMaxFee < $purchaseTotal){
                                throw new CouponApiException('订单金额超过加息券最大使用金额',422);
                            }
                            $couponRate = $couponUseData['coupon_rate'];
                            $couponRateTerm = $couponUseData['coupon_rate_term'];
                        }elseif($couponDetail['type'] == 4){//体验金
                            if($couponMinFee > $purchaseTotal){
                                throw new CouponApiException('体验金未达到最小使用金额',422);
                            }
                            $couponAmount = $couponUseData['coupon_amount'];
                            $couponAmountTerm = $couponUseData['coupon_amount_term'];
                            $couponSameAsProd = $couponUseData['coupon_same_as_prod'];
                            $couponAmountReturn = 0;
                        }else{
                            throw  new CouponApiException('不支持的优惠券类型',422);
                        }
                        //通过所有检测，拼接支付接口需要的数据类型
                        $orderCoupons[] = [
                            "coupon_id"=> $coupon,
                            "coupon_amount"=> $couponAmount, //贷金额
                            "coupon_amount_term"=> $couponAmountTerm, //有效期
                            "coupon_same_as_prod"=> $couponSameAsProd,
                            "coupon_amount_return"=> $couponAmountReturn, //返给用户
                            "coupon_rate"=> $couponRate, //...
                            "coupon_rate_term"=> empty($couponRateTerm) ? 0 : $couponRateTerm, //
                            "coupon_discount"=> $couponDiscount //返现
                        ];

                    }else{//不存在
                        throw new CouponApiException('优惠券不存在',422);
                    }
                }
            }
            return $orderCoupons;
        }else{
            return [];
        }
    }

    /**
     * 通过产品id获取当前用户的可用优惠券
     * @param $prodId
     * @return mixed
     */
    function getEnableCouponsForProd($prodId){
        return $this->method(self::COUPON_ENABLE_FOR_PROD.'/'.$this->token)->get(['cId'=>$prodId]);
    }
}