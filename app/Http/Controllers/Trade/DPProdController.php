<?php
/**
 * 活期产品交易订单相关
 */
namespace App\Http\Controllers\Trade;
use App\DataServer\TA\TADynamicProdApi;
use App\Http\Controllers\CommonTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\AccountFeeFlowRequest;


class DPProdController extends Controller
{
    use CommonTrait;

    function __construct()
    {
        $this->setBase();
    }

    /**
     * 用户活期产品持有列表
     * @return \Illuminate\Http\JsonResponse
     */
   function boughtList(){
       $taDPApi = new TADynamicProdApi();
       //活期持有总额
        $accountCapitalInfoRel = $taDPApi->dpCapitalInfo($this->account);
        if($accountCapitalInfoRel['stateCode'] != '00000'){
            return makeFailedMsg(501,$accountCapitalInfoRel['message']);
        }
       $accountCapitalInfo = $accountCapitalInfoRel['data'];
       $responseData['capitalInfo'] = $accountCapitalInfo;
       //活期持有列表
       $dpListRel = $taDPApi->getTradeList($this->account);
       if($dpListRel['stateCode'] != '00000'){
            return makeFailedMsg(501,$dpListRel['message']);
       }
       $responseData['capitalList'] = array_except($dpListRel,['stateCode','message']);
       return makeSuccessMsg($responseData);
   }

    /**
     * 活期收益流水
     * @param AccountFeeFlowRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function feeFlow(AccountFeeFlowRequest $request){
        $dpTransFlow = $request->get('dpTransFlow',"");
        $length = $request->get('length',10);
        $type = $request->get('type',1);
        $taDPApi = new TADynamicProdApi();
        $feeFlowRel =$taDPApi->getFeeFlow($this->account,$length,$type,$dpTransFlow);
        if($feeFlowRel['stateCode'] != '00000'){
            return makeFailedMsg(501,$feeFlowRel['message']);
        }
        $feeFlow = $feeFlowRel['data']['incomeFlow'];
        foreach($feeFlow as &$item){
            $item['dateKey'] = date('Y年m月',strtotime($item['dpTransTime']));
        }
        return makeSuccessMsg($feeFlow);
    }

    /**
     * 活期按月获取收益总额
     * @param AccountFeeFlowRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function availsForMonth(AccountFeeFlowRequest $request){
        $type = $request->get('type',1);
        $taDPApi = new TADynamicProdApi();
        $availsRel = $taDPApi->getAvailsForMonth($this->account,$type);
        if($availsRel['stateCode'] != '00000'){
            return makeFailedMsg(501,$availsRel['message']);
        }
        return makeSuccessMsg($availsRel['data']);
    }

}
