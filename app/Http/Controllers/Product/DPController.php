<?php
/**
 * 活期产品
 */
namespace App\Http\Controllers\Product;

use App\DataServer\Front\ProdApi;
use App\DataServer\Hybrid\UserService;
use App\DataServer\TA\TADynamicProdApi;
use App\Http\Controllers\Controller;


class DPController extends Controller
{

    /**
     * 活期产品详细
     */
    function dpProdDetail()
    {
        $pageData = [];
        //产品交易数据
        $taProdApi = new TADynamicProdApi();
        $taProdInfoRel = $taProdApi->getDynamicProd();
        if ($taProdInfoRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $taProdInfoRel['message']);
        }
        $taProdInfo = $taProdInfoRel['dpBaseInfo'];
        $pageData['dpCapitalInfo'] = [];
        //用户活期产品  持有总额 和 累计收益 昨日收益
        $token = \Request::header('Token');
        if (empty($token)) {//未登陆的
            $pageData['accountInfo'] = [];
            $pageData['checkToken'] = 0;
        } else {
            //获取用户基本信息
            $userService = new UserService();
            $userBaseInfo = $userService->getUserInfo(false);

            $pageData['checkToken'] = sizeof($userBaseInfo) ? 1 : 0;
            if (isset($userBaseInfo['isBindCard']) && $userBaseInfo['isBindCard']) {
                $account = $userBaseInfo['account'];
                //支付渠道
                $userBankInfo = $userService->getBankInfoByAccount($account);
                $userBaseInfo['payMethod'] = $userBankInfo['payChannelAgile'];
                //活期资产持有状态
                $accountDPInfoRel = $taProdApi->dpCapitalInfo($account);
                if ($accountDPInfoRel['stateCode'] != '00000') {
                    return makeFailedMsg(501, $accountDPInfoRel['message']);
                }
                $accountDPInfo = $accountDPInfoRel['data'];
                //判断用户当前活期持有最低收益，如果没有购买则返回活期基本收益率
                $accountDPInfo['maxDpInterest'] = !empty($accountDPInfo['maxDpInterest']) ? $accountDPInfo['maxDpInterest'] : $taProdInfo['baseInterestRate'];

                $availableCreditRel = $taProdApi->getDPAvailableCreditsForUser($account);//活期产品可购额度
                if ($availableCreditRel['stateCode'] != '00000') {
                    return makeFailedMsg(501, '获取活期产品可购买额度失败：' . $availableCreditRel['message']);
                }
                $accountDPInfo['availableCredit'] = $availableCreditRel['dp_available_credits'];
                $accountDPInfo['availableLockPeriodCredit'] = isset($availableCreditRel['dp_available_lock_period_credits']) ? $availableCreditRel['dp_available_lock_period_credits'] : 0;
                //活期大户持有配置
                if (in_array($userBaseInfo['mobile'], explode(',', env('DP_VIP_MOBILE', '15968400752,18621351218')))) {
                    $taProdInfo['basePersonalQuotaCredit'] = env('DP_VIP_MAX_FEE', 10000000);
                }

                $pageData['dpCapitalInfo'] = $accountDPInfo;
            }
            $pageData['accountInfo'] = $userBaseInfo;
        }
        // 判断活期产品是否处在续标锁定阶段
        $prodApi = new ProdApi();
        $dpConfig = $prodApi->getDpConfig();
        if ($dpConfig['code'] != '200') {
            \log::notice('获取活期锁定购买时间失败！');
        }
        $now = date('Y-m-d H:i:s');
        if ($now >= $dpConfig['result']['lockStartTime'] && $now <= $dpConfig['result']['lockEndTime']) {
            // 将剩余额度置0，以阻止购买
            $taProdInfo['remainCredit'] = 0;
            if (isset($pageData['dpCapitalInfo']['availableCredit'])) {
                $pageData['dpCapitalInfo']['availableCredit'] = 0;
            }
        }
        $pageData['prodInfo'] = $taProdInfo;
        return makeSuccessMsg($pageData);
    }

    /**
     * 活期转让页
     * @return \Illuminate\Http\JsonResponse
     */
    function dpRedeem()
    {
        //产品交易数据
        $taProdApi = new TADynamicProdApi();
        $taProdInfoRel = $taProdApi->getDynamicProd();
        if ($taProdInfoRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $taProdInfoRel['message']);
        }
        $taProdInfo = $taProdInfoRel['dpBaseInfo'];
        $responseData = ['taProdInfo' => $taProdInfo];
        $userService = new UserService();
        $userBaseInfo = $userService->getUserInfo();
        $account = $userBaseInfo['account'];
        if (empty($account)) {
            $capitalInfo = [
                'dpRedeemingCredit' => 0,
                'dpRedeemableCredit' => 0,
            ];
        } else {
            $taProdApi = new TADynamicProdApi();
            $accountDPInfoRel = $taProdApi->getAccountDPRedeemAble($account);
            if ($accountDPInfoRel['stateCode'] != '00000') {
                return makeFailedMsg(501, $accountDPInfoRel['message']);
            } else {
                $capitalInfo = [
                    'dpRedeemingCredit' => $accountDPInfoRel['dp_redeeming_share'] * $taProdInfo['price'],
                    'dpRedeemableCredit' => $accountDPInfoRel['dp_redeemable_share'] * $taProdInfo['price'],
                ];
            }
        }
        $responseData['capitalInfo'] = $capitalInfo;
        return makeSuccessMsg($responseData);
    }
}
