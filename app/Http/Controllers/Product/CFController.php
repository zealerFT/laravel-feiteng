<?php
/**
 * 众筹产品
 */
namespace App\Http\Controllers\Product;

use App\DataServer\Front\ProdApi;
use App\DataServer\Front\UserApi;
use App\DataServer\Hybrid\UserService;
use App\DataServer\TA\TACFProdApi;
use App\Exceptions\Purchase\CFProdException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CFProdListRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CFController extends Controller
{

    /**
     * 众筹列表
     * @param CFProdListRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws CFProdException
     */
    function prodListOld(CFProdListRequest $request)
    {
        $pageId = $request->get('pageId', 1);
        $pageSize = $request->get('pageSize', 20);
        //TA产品交易数据
        $taCFProdApi = new TACFProdApi();
        $cfProdListRel = $taCFProdApi->getProdList($pageId, $pageSize);

        if ($cfProdListRel['stateCode'] != '00000') {
            throw new CFProdException('众筹产品列表获取失败：' . $cfProdListRel['message'], 501);
        }
        $taCFProdList = $cfProdListRel['data'];
        //获取产品id和产品售卖状态
        $cfProdIds = [];
        foreach ($taCFProdList['result'] as &$taProd) {
            $cfProdIds[] = $taProd['prodId'];
            //产品份额需要减去冻结份额
            $taProd['remainCredit'] = $taProd['remainCredit'] - $taProd['frozenCredit'];
            $taProd['saleStatus'] = getRegularProdSaleStatus($taProd);
            unset($taProd['__vstone_row_nr__']);
        }

        //前台产品基本数据
        $cfProdList = [];
        $prodApi = new ProdApi();
        $prodSummaryListRel = $prodApi->cfProdSummaryByIds($cfProdIds);
        if ($prodSummaryListRel['code'] != 200) {
            throw  new CFProdException('众筹产品列表获取失败：' . $prodSummaryListRel['message'], 500);
        }
        $prodSummaryList = $prodSummaryListRel['result'];
        foreach ($prodSummaryList as $prod) {
            $prod['cfPsProcess'] = json_decode($prod['cfPsProcess'], 1);
            $cfProdList[$prod['cId']] = [
                'pid' => $prod['cId'],
                'followNumber' => $prod['followNumber'],
                'cfPsPicForList' => $prod['cfPsPicForList'],
                'cfPsRecap' => $prod['cfPsRecap'],
                'remarkInfo' => getProdTarget($prod['cfPsProcess']),
            ];
        }
        $data = [];
        //拼接ta和前台数据
        foreach ($taCFProdList['result'] as $prodIndex => $taProd) {
            if (isset($cfProdList[$taProd['prodId']])) {
                $data[] = array_merge($taProd, $cfProdList[$taProd['prodId']]);
            }
        }
        //dd($taCFProdList);
        return makeSuccessMsg($data);

    }

    /**
     * 根据前台返回众筹列表显示
     * @param CFProdListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    function prodList(CFProdListRequest $request)
    {
        $pageId = $request->get('pageId', 1);
        $pageSize = $request->get('pageSize', 20);
        //前台众筹产品列表
        $prodApi = new ProdApi();
        $prodListRel = $prodApi->cfProdList($pageId, $pageSize);
        if ($prodListRel['code'] != '200') {
            return makeFailedMsg(501, $prodListRel['message']);
        }
        $prodList = $prodListRel['result']['data'];
        //unset($prodListRel['result']['data']);
        $responseData = [];
        foreach ($prodList as $prod) {
            //获取产品交易信息
            $taCFProdApi = new TACFProdApi();
            $taProdRel = $taCFProdApi->getProdDetail($prod['cId']);//todo 可优化
            //dd($taProdRel);
            if ($taProdRel['stateCode'] != '00000') {
                return makeFailedMsg(501, $taProdRel['message']);
            }
            $taProd = $taProdRel['data']['productInfo'];
            //判断是否有 valuesDate
            if (!isset($taProd['valuesDate'])) {
                \Log::alert('TA GetProdDetail Failed--REQUEST:' . $prod['cId'] . ', RESPONSE:' . json_encode($taProd));
                return makeFailedMsg(501, '产品信息获取异常，请重试');
            }
            //产品份额需要减去冻结份额
            $taProd['remainCredit'] = $taProd['remainCredit'] - $taProd['frozenCredit'];
            $taProd['saleStatus'] = getRegularProdSaleStatus($taProd);
            //拼接产品
            $prod['cfPsProcess'] = json_decode($prod['cfPsProcess'], 1);
            $responseData[] = array_merge($taProd, [
                'pid' => $prod['cId'],
                'followNumber' => $prod['followNumber'],
                'cfPsPicForList' => $prod['cfPsPicForList'],
                'cfPsRecap' => $prod['cfPsRecap'],
                'remarkInfo' => getProdTarget($prod['cfPsProcess']),
            ]);
        }
        $prodListRel['result']['data'] = $responseData;
        return makeSuccessMsg($prodListRel['result']);
    }


    /**
     * 众筹产品详细
     * @param $pid
     * @return \Illuminate\Http\JsonResponse
     */
    function prodDetail($pid)
    {
        //ta产品交易数据
        $taCFProdApi = new TACFProdApi();
        $taProdDetailRel = $taCFProdApi->getProdDetail($pid);
        if ($taProdDetailRel['stateCode'] != '00000' || empty($taProdDetailRel['data'])) {
            return makeFailedMsg(500, '众筹产品交易详情获取失败');
        }
        $taProdDetail = $taProdDetailRel['data']['productInfo'];
        //产品售卖状态
        //产品份额需要减去冻结份额
        $taProdDetail['remainCredit'] = $taProdDetail['remainCredit'] - $taProdDetail['frozenCredit'];
        $taProdDetail['saleStatus'] = getRegularProdSaleStatus($taProdDetail);
        //前台产品详细信息
        $prodApi = new ProdApi();
        $prodDetailRel = $prodApi->cfProdDetail($pid);
        if (empty($prodDetailRel['result'])) {
            return makeFailedMsg(500, '众筹产品描述获取失败');
        }
        $prodDetail = array_merge($taProdDetail, $prodDetailRel['result']);
        $prodDetail['cfPsImportant'] = json_decode($prodDetail['cfPsImportant'], 1);
        $prodDetail['cfPsProcess'] = json_decode($prodDetail['cfPsProcess'], 1);
        $prodDetail = array_except($prodDetail, ['createdAt', 'updatedAt', 'id', 'cId', 'pId']);
        //用户认证资质
        $token = \Request::header('Token');
        if (empty($token)) {//未登陆的
            $eval = 0;
        } else {
            $userApi = new UserApi();
            $evalRel = $userApi->getUserEval();
            if (in_array($evalRel['code'], ['404', '401'])) {
                $eval = 0;
            } else {
                $eval = $evalRel['result'];
            }
        }
        $prodDetail['userEval'] = $eval;
        $prodDetail['userQualify'] = $eval >= config('sys-config.user_risk_min_score') ? 1 : 0;
        //判断支付渠道
        $userService = new UserService();
        $userBaseInfo = $userService->getUserInfo(false);
        $prodDetail['checkToken'] = sizeof($userBaseInfo) ? 1 : 0;
        $prodDetail['isBindCard'] = isset($userBaseInfo['isBindCard']) ? $userBaseInfo['isBindCard'] : 0;
        $prodDetail['payMethod'] = "";
        if (isset($userBaseInfo['isBindCard']) && $userBaseInfo['isBindCard']) {
            $account = $userBaseInfo['account'];
            $userBankInfo = $userService->getBankInfoByAccount($account);
            $prodDetail['payMethod'] = $userBankInfo['payChannelAgile'];
        }
        //产品阶段标示
        $prodDetail['remarkInfo'] = getProdTarget($prodDetail['cfPsProcess']);

        //产品附件
        $prodMainId = substr($pid, 0, 6);
        $prodAttachRel = $prodApi->prodAttach($prodMainId);
        $prodDetail['attach'] = $prodAttachRel['result'];

        //合同
        $prodContractRel = $prodApi->prodContract($prodMainId);
        $prodContract = $prodContractRel['result'];
        $prodDetail['contract'] = empty($prodContract) ? [] : array_only($prodContract, ['contractTitle', 'createTime']);
        return makeSuccessMsg($prodDetail);
    }


    /**
     * 众筹产品加关注
     * @param $pid
     * @return \Illuminate\Http\JsonResponse
     */
    function addFollow($pid)
    {
        $token = \Request::header('Token');
        if (empty($token)) {//未登陆的
            return makeSuccessMsg([]);
        } else {
            //获取用户手机号
            $userService = new UserService();
            $userBaseInfo = $userService->getUserInfo(false);
            //dd($userBaseInfo);
            if (!sizeof($userBaseInfo)) {//token无效，直接忽略
                return makeSuccessMsg([]);
            }
            $mobile = $userBaseInfo['mobile'];
            $prodApi = new ProdApi();
            $prodApi->cfProdAddFollow($mobile, $pid);
            return makeSuccessMsg([]);
        }
    }

    /**
     * @param Request $request
     */
    public function prodTransferList(Request $request)
    {
        $prodApi = new ProdApi();
        $prodListRel = $prodApi->cfProdTransferList();
        if ($prodListRel['stateCode'] != '00000') {
            return makeFailedMsg(501, $prodListRel['message']);
        }
        $prodList = $prodListRel['data'];
        $taCFProdApi = new TACFProdApi();

        $lists = [];
        foreach ($prodList as $prod) {
            $taProdRel = $taCFProdApi->getProdDetail($prod['productId']);
            if ($taProdRel['stateCode'] != '00000') {
                return makeFailedMsg(501, $taProdRel['message']);
            }
            $taProd = $taProdRel['data']['productInfo'];
            //判断是否有 valuesDate
            if (!isset($taProd['valuesDate'])) {
                \Log::alert('TA GetProdDetail Failed--REQUEST:' . $prod['cId'] . ', RESPONSE:' . json_encode($taProd));
                return makeFailedMsg(501, '产品信息获取异常，请重试');
            }
            $diffDays = time() - strtotime("{$prod['preRedeemDate']}");
            $diffDays = floor($diffDays / 24 / 60 / 60); // 额外获得利息天数
            $nextRedeem = 90 - $diffDays;
            $predictRate = $prod['preIncomeRate'] / $nextRedeem * 90;
            // $prod['status'] 1. 卖完  0.正在卖 3. 取消
            $lists[] = [
                'prodId' => $taProd['prodId'],
                'prodName' => $taProd['prodName'],
                'amount' => $prod['amount'], // 份额
                'avgIncomeRateAfterGrid' => $prod['avgIncomeRateAfterGrid'],// 平均年化收益率
                'tag' => $nextRedeem,
                'predictRate' => $predictRate, // 预计本季度利率
                'status' => $prod['onSale'], // 是否锁定状态 1表示锁定 0表示正常
                'createTime' => $prod['createTime'],
                'sourceId' => $prod['sourceId'],
                'tradeId' => $prod['tradeId']
            ];
        }
        // 预计本季度年化收益率（高->低）>距离下次结息天数（低->高）>提交转让时间（远->近）；
        // predictRate                  tag                 createTime
        if ($lists) {
            $predictRate = array_column($lists, 'predictRate');
            $tag = array_column($lists, 'tag');
            $createTime = array_column($lists, 'createTime');
            array_multisort($predictRate, SORT_DESC, $tag, SORT_ASC, $createTime, SORT_ASC, $lists);
        }
        $responseData['data'] = $lists;
        return makeSuccessMsg($responseData);
    }

    /**
     * 获取众筹转让订单详情
     * @param $tradeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function prodTransferDetail($tradeId)
    {
        // 转让情况
        $prodApi = new ProdApi();
        $transferDetailRel = $prodApi->cfProdTransferDetail($tradeId);
        if ($transferDetailRel['stateCode'] != '00000' || empty($transferDetailRel['data'])) {
            return makeFailedMsg(501, '该转让订单已失效或转让成功');
        }
        $transferDetail = $transferDetailRel['data'];
        $diffDays = (new Carbon($transferDetail['preRedeemDate']))->diffInDays();// 额外获得利息天数
        $nextRedeem = 90 - $diffDays;
        $predictRate = $transferDetail['preIncomeRate'] / $nextRedeem * 90;
        $transferData['data'] = [
            'extraDays'                 => $diffDays, // 额外获得的天数
            'avgIncomeRateAfterGrid'    => $transferDetail['avgIncomeRateAfterGrid'],// 平均年化收益率
            'predictRate'               => $predictRate, // 预计本季度利率
            'amount'                    => $transferDetail['amount'], // 份额
            'price'                     => $transferDetail['price'],
            'untilNextRedeemDays'       => $nextRedeem,
            'prodId'                    => $transferDetail['productId'],
            'status'                    => $transferDetail['onSale'], // 是否锁定状态 1表示锁定 0表示正常
            'createTime'                => $transferDetail['createTime'],
            'sourceId'                  => $transferDetail['sourceId'],
            'tradeId'                   => $transferDetail['tradeId']
        ];
        return makeSuccessMsg($transferData);
    }
}

