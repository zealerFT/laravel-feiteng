<?php
/**
 * 交易页面，定期 众筹 活期三种产品下单页面
 */
namespace App\Http\Controllers\Purchase;
use App\DataServer\Front\CouponApi;
use App\DataServer\Front\ProdApi;
use App\DataServer\TA\AccountApi;
use App\DataServer\TA\TACFProdApi;
use App\DataServer\TA\TADynamicProdApi;
use App\DataServer\TA\TARegularProdApi;
use App\Exceptions\Purchase\OrderException;
use App\Http\Controllers\CommonTrait;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OrderController extends Controller
{
    use CommonTrait;
    function __construct()
    {
        $this->setBase();
        if(empty($this->account)){
            throw new UnauthorizedHttpException('Basic realm="My Realm"','您的账户内没有可使用的银行卡');
        }
    }

    /**
     * 定期产品下单页
     * @param $prodId
     * @return \Illuminate\Http\JsonResponse
     * @throws OrderException
     */
    function regularProd($prodId){
        $prodMainId = substr($prodId,0,6);
        $pageData =[];
        //产品交易信息
        $taProdApi = new TARegularProdApi();
        $taProdInfo = $taProdApi->getRegularProdDetail($prodId);
        if(!$taProdInfo['data']){
            return makeFailedMsg(422,'产品不存在');
        }
        $taProdInfoData = $taProdInfo['data'];
        //产品份额需要减去冻结份额
        $taProdInfoData['remainCredit'] = $taProdInfoData['remainCredit'] - $taProdInfoData['frozenCredit'];
        //判断尾单
        $pageData['saleStatus'] = getRegularProdSaleStatus($taProdInfoData);
        $pageData['transInfo'] = $taProdInfoData;
        //产品标签
        $prodApi = new ProdApi();
        $prodTarget = $prodApi->prodTarget($prodMainId);
        $pageData['prodTarget'] = $prodTarget['result']['target'];

        //用户可用优惠券
        $couponApi = new CouponApi();
        $couponEnable = $couponApi->getEnableCouponsForProd($prodId);

        $pageData['coupons'] = $couponEnable['result'];

        //用户余额
        $accountApi = new AccountApi();
        $accountBalance = $accountApi->getAccountBalance($this->account);
        $pageData['balance'] = $accountBalance['data']['balance'];
        return makeSuccessMsg($pageData);

    }

    /**
     * 众筹产品下单页
     * @param $prodId
     * @return \Illuminate\Http\JsonResponse
     */
    function cfProd($prodId){
        $pageData =[];
        //产品交易信息
        $taCFProdApi = new TACFProdApi();
        $taProdDetailRel = $taCFProdApi->getProdDetail($prodId);
        if(!$taProdDetailRel['data']){
            return makeFailedMsg(422,'产品不存在');
        }
        $pageData['transInfo'] = $taProdDetailRel['data']['productInfo'];
        //用户余额
        $accountApi = new AccountApi();
        $accountBalance = $accountApi->getAccountBalance($this->account);
        $pageData['balance'] = $accountBalance['data']['balance'];
        return makeSuccessMsg($pageData);
    }

    /**
     * 活期产品下单页
     * @return \Illuminate\Http\JsonResponse
     */
    function dpProd(){
        //产品交易数据
        $taProdApi = new TADynamicProdApi();
        $taProdInfoRel = $taProdApi->getDynamicProd();
        if($taProdInfoRel['stateCode'] != '00000'){
            return makeFailedMsg(501,$taProdInfoRel['message']);
        }
        $taProdInfo = $taProdInfoRel['dpBaseInfo'];
        $pageData['transInfo'] = $taProdInfo;
        //用户余额
        $accountApi = new AccountApi();
        $accountBalance = $accountApi->getAccountBalance($this->account);
        $pageData['balance'] = $accountBalance['data']['balance'];
        //用户可购买金额
            //活期资产持有状态
        $accountDPInfoRel = $taProdApi->dpCapitalInfo($this->account);
        if($accountDPInfoRel['stateCode'] != '00000'){
            return makeFailedMsg(501,$accountDPInfoRel['message']);
        }
        $accountDPInfo = $accountDPInfoRel['data'];
        $userBaseInfo = $this->userBaseInfo;
        $availableCreditRel = $taProdApi->getDPAvailableCreditsForUser($this->account);//活期产品可购额度
        if($availableCreditRel['stateCode'] != '00000'){
            return makeFailedMsg(501,'获取活期产品可购买额度失败：'.$availableCreditRel['message']);
        }
        $accountDPInfo['availableCredit'] = $availableCreditRel['dp_available_credits'];
        $accountDPInfo['availableLockPeriodCredit'] = isset($availableCreditRel['dp_available_lock_period_credits']) ? $availableCreditRel['dp_available_lock_period_credits'] : 0 ;
        if(in_array($userBaseInfo['mobile'],explode(',',env('DP_VIP_MOBILE','15968400752,18621351218')))){
            $accountDPInfo['availableCredit'] = $taProdInfo['remainCredit'];
        }
        $pageData['dpCapitalInfo'] = $accountDPInfo;
        return makeSuccessMsg($pageData);
    }

    /**
     * 活期转让
     * @return \Illuminate\Http\JsonResponse
     */
    function dpRedeem(){
        $taProdApi = new TADynamicProdApi();
        //获取活期基本信息
        $taProdInfoRel = $taProdApi->getDynamicProd();
        if($taProdInfoRel['stateCode'] != '00000'){
            return makeFailedMsg(501,$taProdInfoRel['message']);
        }
        $taProdInfo = $taProdInfoRel['dpBaseInfo'];
        $responseData['dpPrice'] = $taProdInfo['price'];
        $accountRedeemRel = $taProdApi->getAccountDPRedeemAble($this->account);
        if($accountRedeemRel['stateCode'] != '00000'){
            return makeFailedMsg(501,$accountRedeemRel['message']);
        }
        $responseData['dpRedeemingCredit'] = $accountRedeemRel['dp_redeeming_share'] * $responseData['dpPrice'];
        $responseData['dpRedeemableCredit'] = $accountRedeemRel['dp_redeemable_share'] * $responseData['dpPrice'];
        return makeSuccessMsg($responseData);
    }


}
