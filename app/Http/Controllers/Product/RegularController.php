<?php
/**
 * 定期产品
 */
namespace App\Http\Controllers\Product;
use App\DataServer\Hybrid\UserService;
use App\Http\Controllers\Controller;
use App\DataServer\Front\ProdApi;
use App\DataServer\TA\TARegularProdApi;

class RegularController extends Controller
{
    /**
     * 定期子产品摘要信息
     * @param $pid
     * @return \Illuminate\Http\JsonResponse
     */
    function summary($pid){
        //获取ta产品数据
        $taRegularProdApi = new TARegularProdApi();
        $taProd = $taRegularProdApi->getRegularProdDetail($pid);
        $taProdData = $taProd['data'];
        if(empty($taProdData)){
            return makeFailedMsg(422,'产品不存在');
        }
        //产品份额需要减去冻结份额
        $taProdData['remainCredit'] = $taProdData['remainCredit'] - $taProdData['frozenCredit'];
        $taProdData['saleStatus'] = getRegularProdSaleStatus($taProdData);
        //获取前台母产品数据
        $regularProdApi = new ProdApi();
        $pMainId = substr($pid,0,6);
        $prodDesc = $regularProdApi->regularProd($pMainId);
        $prodDescData = $prodDesc['result'];
        //合并TA和前台数据
        $taProdData['mobileEarningCompare'] = $prodDescData['mobileEarningCompare'];
        $taProdData['mobileAbstract'] = $prodDescData['mobileAbstract'];
        $taProdData['isRetender'] = empty($prodDescData['isRetender']) ? 0 : $prodDescData['isRetender']; // 能否自动续标标记（快结束的资产能否续标前台做判断控制）
        //产品标签
        $prodTarget = $regularProdApi->prodTarget($pMainId);
        $taProdData['prodTarget'] = $prodTarget['result']['target'];
        //判断支付渠道
        $userService = new UserService();
        $userBaseInfo = $userService->getUserInfo(false);
        $taProdData['checkToken'] = sizeof($userBaseInfo) ? 1:0;
        $taProdData['isBindCard'] = isset($userBaseInfo['isBindCard']) ? $userBaseInfo['isBindCard'] : 0;
        $taProdData['payMethod'] = 0;
        if(isset($userBaseInfo['isBindCard']) && $userBaseInfo['isBindCard']){
            $account = $userBaseInfo['account'];
            $userBankInfo = $userService->getBankInfoByAccount($account);
            $taProdData['payMethod'] = $userBankInfo['payChannelAgile'];
        }
        return makeSuccessMsg($taProdData);
    }

    /**
     * 定期子产品详情
     * @param $pid
     * @return \Illuminate\Http\JsonResponse
     */
    function detail($pid){
        //获取ta产品数据
        $taRegularProdApi = new TARegularProdApi();
        $taProd = $taRegularProdApi->getRegularProdDetail($pid);
        $taProdData = $taProd['data'];
        if(empty($taProdData)){
            return makeFailedMsg(422,'产品不存在');
        }
        //产品份额需要减去冻结份额
        $taProdData['remainCredit'] = $taProdData['remainCredit'] - $taProdData['frozenCredit'];
        $taProdData['saleStatus'] = getRegularProdSaleStatus($taProdData);
        //获取前台母产品数据
        $regularProdApi = new ProdApi();
        $pMainId = substr($pid,0,6);
        $prodDesc = $regularProdApi->regularProd($pMainId);
        $prodDescData = $prodDesc['result'];
        //合并TA和前台数据
        $taProdData['mobileSummary'] = $prodDescData['mobileSummary'];
        //产品标签
        $prodTarget = $regularProdApi->prodTarget($pMainId);
        $taProdData['prodTarget'] = $prodTarget['result']['target'];
        //判断支付渠道
        $userService = new UserService();
        $userBaseInfo = $userService->getUserInfo(false);
        $taProdData['checkToken'] = sizeof($userBaseInfo) ? 1:0;
        $taProdData['isBindCard'] = isset($userBaseInfo['isBindCard']) ? $userBaseInfo['isBindCard'] : 0;
        $taProdData['payMethod'] = "";
        if(isset($userBaseInfo['isBindCard']) && $userBaseInfo['isBindCard']){
            $account = $userBaseInfo['account'];
            $userBankInfo = $userService->getBankInfoByAccount($account);
            $taProdData['payMethod'] = $userBankInfo['payChannelAgile'];
        }
        return makeSuccessMsg($taProdData);
    }

}
