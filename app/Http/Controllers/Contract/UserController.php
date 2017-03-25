<?php
/**
 * 用户合同相关
 */
namespace App\Http\Controllers\Contract;

use App\DataServer\Front\ContractApi;
use App\DataServer\Front\ProdApi;
use App\DataServer\PDF\PDFServiceApi;
use App\DataServer\TA\AccountApi;
use App\DataServer\TA\TACFProdApi;
use App\DataServer\TA\TradeApi;
use App\Http\Controllers\CommonTrait;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    use CommonTrait;

    function __construct()
    {
        $this->setBase();
    }


    /**
     * 查询用户交易合同
     * @param $tradeId
     * @return \Illuminate\Http\JsonResponse
     */
    function tradeContract($tradeId)
    {
        $contractApi = new ContractApi();
        $userContractRel = $contractApi->getUserContract($tradeId);
        if ($userContractRel['code'] != "200") {
            return makeFailedMsg(501, '获取用户交易合同失败：' . $userContractRel['message']);
        }
        $userContract = $userContractRel['result'];
        //判断这个合同是否属于该用户
        $contractUserId = $userContract['uId'];
        $userId = $this->userBaseInfo['userId'];
        if ($contractUserId != $userId) {
            return makeFailedMsg(501, '无权查看此合同');
        }
        //获取合同
        $pdfApi = new PDFServiceApi();
        $pdfInfoRel = $pdfApi->getTradePDF($userContract['contractTitle'], $userContract['contractModelId'], $userContract['contractDate']);
        if ($pdfInfoRel['code'] != 0) {
            return makeFailedMsg(501, '获取合同文件失败：' . $pdfInfoRel['message']);
        }
        $contractData = [
            'modelHtmlLink' => $pdfInfoRel['model_html_link']
        ];
        return makeSuccessMsg($contractData);
    }

    /**
     * 获取转让合同
     * @param $tradeId
     * @return \Illuminate\Http\JsonResponse
     */
    function transferTradeContract($tradeId)
    {
        $contractApi = new ContractApi();
        $accountApi = new AccountApi();
        $tradeDataRel = $accountApi->getCFProdDetail($this->account, $tradeId);
        if ($tradeDataRel['stateCode'] != '00000' || empty($tradeDataRel['data']['detail'])) {
            return makeFailedMsg(501, '订单不存在：' . $tradeDataRel['message']);
        }
        $tradeData = $tradeDataRel['data'];
        $tradeDetail = $tradeData['detail'];
        //判断订单是否属于该用户
        if ($this->account != $tradeDetail['account']) {
            return makeFailedMsg(501, '无权查看此合同');
        }
        $transferRecords = $tradeData['successTransferBothTradeIds'];// 转让记录
        $userContract = [];
        if ($transferRecords) {
            // 如果是转出方
            foreach ($transferRecords as $record) {
                $tmp = $contractApi->getUserContract($record['theSellerTransferSuccessTradeId']);
                if ($tmp['code'] != "200") {
                    \Log::alert('获取用户转让合同失败:' . $tradeId, $record);
                    continue;
                }
                $tmpData = $tmp['result'];
                $date = date('Y-m-d', strtotime($record['createTime']));// 合同日期
                $tmpData['date'] = $date;
                $userContract[] = $tmpData;
            }
        }

        if ($tradeData['aboveTradeId']) {
            // 如果是购买人(此单是从别人转让过来的)
            $tmp = $contractApi->getUserContract($tradeId);
            if ($tmp['code'] != "200") {
                \Log::alert('获取用户交易合同失败:' . $tradeId, $tmp);
                return makeFailedMsg(501, '获取用户交易合同失败：' . $tmp['message']);
            }
            $date = date('Y-m-d', strtotime($tradeDetail['purchaseDate']));// 合同日期
            $tmp['result']['date'] = $date;
            $selfContract[] = $tmp['result'];
            $userContract = array_merge($selfContract, $userContract);
        }

        //获取合同
        $pdfApi = new PDFServiceApi();
        $contractData = [];
        foreach ($userContract as $item) {
            $pdfTmp = $pdfApi->getTradePDF($item['contractTitle'], $item['contractModelId'], $item['contractDate']);
            if ($pdfTmp['code'] != 0) {
                return makeFailedMsg(501, '获取合同文件失败：' . $pdfTmp['message']);
            }
            $tmp = [
                'date' => $item['date'],
                'title' => $item['contractTitle'],
                'link' => $pdfTmp['model_html_link']
            ];
            $contractData[] = $tmp;
        }
        return makeSuccessMsg($contractData);
    }

    /**
     * 通过交易id生成用户交易合同
     * @param $tradeId
     * @return bool
     */
    function newTradeContract($tradeId)
    {
        if (empty($this->account)) {
            //\Log::error('无权访问',$this->userBaseInfo);
            return;
        }
        //查询该订单是否已生成合同，有则不生成
        $contractApi = new ContractApi();
        $checkRel = $contractApi->getUserContract($tradeId);
        if (isset($checkRel['result'])) {
            //\Log::warning('该交易合同已存在:'.$tradeId);
            return;
        }
        //通过交易id获取交易详情
        $accountApi = new AccountApi();
        $tradeInfoRel = $accountApi->getRegularProdDetail($this->account, $tradeId);
        if ($tradeInfoRel['stateCode'] != '00000' || empty($tradeInfoRel['data'])) {
            return;
        }
        $tradeInfo = $tradeInfoRel['data'];
        $tradeCredit = round($tradeInfo['credit'], 2);
        if ($this->account != $tradeInfo['account']) {
            //\Log::error('无权访问',$this->userBaseInfo);
            return;
        }
        $prodMainId = substr($tradeInfo['productId'], 0, 6);
        //查产品合同
        $prodApi = new ProdApi();
        $prodContractRel = $prodApi->prodContract($prodMainId);
        if (empty($prodContractRel['result'])) {
            \Log::critical('生成产品合同错误，产品合同不存在' . $tradeId);
            return;
        }
        $prodContract = $prodContractRel['result'];
        $assetDetail = json_decode($prodContract['assetDetail'], 1);
        $contractModelId = $prodContract['contractModelId'];

        //获取用户身份证信息
        $tradeApi = new TradeApi();
        $userInfoRel = $tradeApi->userCardNo($tradeId);
        if ($userInfoRel['stateCode'] != '00000') {
            \Log::error('生成产品合同错误，获取用户信息失败：' . $userInfoRel['message'] . '   ' . $tradeId);
            return;
        }
        $userInfo = $userInfoRel['memberInfo'];
        switch ($contractModelId) {
            case 'contract_001' ://定期
                $contract = [
                    'ASSIGNEE' => $userInfo['real_name'],
                    'ASSIGNEEID' => $userInfo['card_no'],
                    'TRANSFEROR' => $prodContract['transferor'],
                    'TRANSFERORADDR' => $prodContract['transferorAddr'],
                    'TRANSDATE' => transZHTime(date_format(new \DateTime($tradeInfo['createTime']), 'Y-m-d')),
                    'PRODNAME' => $tradeInfo['prodName'],
                    'PRODID' => $tradeInfo['productId'],
                    'TRADEID' => $tradeId,
                    'IRATE' => round($tradeInfo['iRate'] * 100, 1) . "%",
                    'FEE' => $tradeCredit,
                    'REDEEMDATE' => transZHTime($tradeInfo['redeemDate']),
                    'VALUEDATE' => transZHTime($tradeInfo['valuesDate']),
                ];
                break;
            case 'contract_002' ://众筹
                $taCFProdApi = new TACFProdApi();
                $taProdDetailRel = $taCFProdApi->getProdDetail($tradeInfo['productId']);
                if (empty($taProdDetailRel['data'])) {
                    \Log::critical('生成产品合同错误,众筹产品信息找不到');
                    return;
                }
                $prodInfo = $taProdDetailRel['data']['productInfo'];
                $contract = [
                    'PRODID' => $tradeInfo['productId'],
                    'TRADEID' => $tradeId,
                    'YEAR' => date("Y"),
                    'MONTH' => date("m"),
                    'DAY' => date("d"),
                    'TRANSDATE' => transZHTime(date_format(new \DateTime($tradeInfo['createTime']), 'Y-m-d')),

                    'ASSIGNEE' => $userInfo['real_name'],
                    'ASSIGNEEID' => $userInfo['card_no'],
                    'ASSIGNEENAME' => $userInfo['real_name'],

                    'TRANSFEROR' => $prodContract['transferor'],
                    'TRANSFERORADDR' => $prodContract['transferorAddr'],
                    'TRANSFERORCORPORATE' => '', // asset_detail 字段里面编辑  法人代表 h
                    'TRANSFERORCONTACT' => '', // asset_detail 字段里面编辑  联系方式 h

                    'CORPORATIONNAME' => '', // asset_detail 字段里面编辑  目标企业的名称 h
                    'CRATE' => '', // asset_detail 目标企业的名称百分比股权 h
                    'ARATE' => round($tradeCredit * 100 / $prodInfo['totalCredit'], 2) . "%", // 甲方百分比股权

                    'AFEENUM' => $tradeCredit,
                    'AFEETEXT' => transNumToZH($tradeCredit), // 甲方费用 中文

                    'RANGEDAY' => '', // asset_detail 字段里面编辑  募集期  30天 h
                    'FINISHRANGE' => '', // asset_detail 时间, 并网之后的24个月 h

                    'PROFIT' => "8%",      // asset_detail 字段里面编辑 建设期收益分红
                    'MINIPROFIT' => "8%",   // asset_detail 字段里面编辑 项目运营期，约定股权收益权分红最低为

                    'CFEENUM' => $tradeCredit, // 回购总价款 数字
                    'CFEETEXT' => transNumToZH($tradeCredit), // 回购总价款 中文
                ];
                break;
            case 'contract_011' :// 长拆短
                $terms = ceil((strtotime($tradeInfo['finishDate']) - strtotime($tradeInfo['valuesDate'])) / 60 / 60 / 24);
                $contract = [
                    'ASSIGNEE' => $userInfo['real_name'],
                    'ASSIGNEEID' => $userInfo['card_no'],
                    'TRANSFEROR' => $prodContract['transferor'],
                    'TRANSFERORADDR' => $prodContract['transferorAddr'],
                    'TRANSDATE' => transZHTime(date_format(new \DateTime($tradeInfo['createTime']), 'Y-m-d')),
                    'PRODNAME' => $tradeInfo['prodName'],
                    'PRODID' => $tradeInfo['productId'],
                    'TRADEID' => $tradeId,
                    'IRATE' => round($tradeInfo['iRate'] * 100, 1) . "%",
                    'FEE' => $tradeCredit,
                    'REDEEMDATE' => transZHTime($tradeInfo['redeemDate']),
                    'VALUEDATE' => transZHTime($tradeInfo['valuesDate']),
                    'TERMS' => $terms,
                    'UNLOCK_DAYS' => 15 // 先写死
                ];
                break;
            default:
                \Log::critical('生成模板出错，无对应合同模板' . $tradeId);
                return;
        }
        //合同字段拼接产品合同信息
        if (!isset($assetDetail['STAMP']) || empty($assetDetail['STAMP'])) {// 针对以前旧模板，补充合同印章数据
            $assetDetail['STAMP'] = "imgs/badge_002.png";  // 原产品默认章
        }
        $contractData = array_merge($contract, $assetDetail);
        //用户合同数据
        $newRel = $contractApi->NewUserContract($tradeInfo['productId'], $this->userBaseInfo['userId'], $tradeId, $this->userBaseInfo['name'], $contractModelId, $prodContract['contractTitle'], json_encode($contractData));
        if ($newRel['code'] != 200) {
            \Log::error('创建用户合同失败：' . $newRel['message'] . '    :' . $tradeId);
        } else {
            \Log::info($tradeId . ' 用户合同创建成功');
        }
        return;
    }

    /**
     * 生成众筹转让合同（针对购买人）
     * @param $buyerTradeId
     * @param $sellerTradeId -出让人订单id
     * @param $prodId
     * @param $fee
     * @param $createTime
     */
    function newTradeTransferContract($buyerTradeId, $sellerTradeId, $prodId, $fee, $createTime)
    {
        if (empty($this->account)) {
            //\Log::error('无权访问',$this->userBaseInfo);
            return;
        }
        //查询该订单是否已生成合同，有则不生成
        $contractApi = new ContractApi();
        $checkRel = $contractApi->getUserContract($buyerTradeId);
        if (isset($checkRel['result'])) {
            //\Log::warning('该交易合同已存在:'.$tradeId);
            return;
        }
        $tradeCredit = round($fee, 2);
        $prodMainId = substr($prodId, 0, 6);
        //查产品合同(用于获取 融资方和spv)
        $prodApi = new ProdApi();
        $prodContractRel = $prodApi->prodContract($prodMainId);
        if (empty($prodContractRel['result'])) {
            \Log::critical('生成产品合同错误，产品合同不存在' . $buyerTradeId);
            return;
        }
        $prodContract = $prodContractRel['result'];
        $assetDetail = json_decode($prodContract['assetDetail'], 1);

        $contractModelId = 'contract_100';

        //获取用户身份证信息
        $tradeApi = new TradeApi();
        $userInfoRel = $tradeApi->userCardNo($buyerTradeId);// 购买人（受让方）
        $outInfoRel = $tradeApi->userCardNo($sellerTradeId);// 乙方（转让方）
        if ($userInfoRel['stateCode'] != '00000') {
            \Log::error('生成产品合同错误，获取用户信息失败：' . $userInfoRel['message'] . '   ' . $buyerTradeId . '   ' . $sellerTradeId);
            return;
        }
        $userInfo = $userInfoRel['memberInfo'];
        $outInfo = $outInfoRel['memberInfo'];
        $taCFProdApi = new TACFProdApi();
        $taProdDetailRel = $taCFProdApi->getProdDetail($prodId);
        if (empty($taProdDetailRel['data'])) {
            \Log::critical('生成产品合同错误,众筹产品信息找不到');
            return;
        }
        $prodInfo = $taProdDetailRel['data']['productInfo'];
        $contractData = [
            'PRODID' => $prodId,
            'TRADEID' => $buyerTradeId,
            'YEAR' => date("Y"),
            'MONTH' => date("m"),
            'DAY' => date("d"),
            'TRANSDATE' => transZHTime(date_format(new \DateTime($createTime), 'Y-m-d')),

            'ASSIGNEE' => $userInfo['real_name'], // 甲方（受让方）
            'ASSIGNEEID' => $userInfo['card_no'],
            'ASSIGNEENAME' => $userInfo['real_name'],

            'TRANSFEROR' => $outInfo['real_name'],
            'TRANSFERORID' => $outInfo['card_no'], // 乙方（转让方）
            'TRANSFERORIDNAME' => $outInfo['real_name'], // 乙方（转让方）

            'DEBTOR' => $prodContract['transferor'], // 丙方：（债务人） spv
            'DEBTORADDR' => $prodContract['transferorAddr'], // 丙方：（债务人） spv地址

            'CORPORATIONNAME' => $assetDetail['CORPORATIONNAME'], // 目标企业的名称
            'ARATE' => round($tradeCredit * 100 / $prodInfo['totalCredit'], 2), // 甲方百分比股权

            'ORIGINALNAME' => $prodContract['transferor'], // 初始转让人名称(spv)
            'GOALNAME' => $assetDetail['CORPORATIONNAME'],  // 目标企业名称(融资方)
            'baseassetsName' => $prodContract['transferor'], // spv 名称
            'BASEASSETSORIGINALNAME' => $assetDetail['CORPORATIONNAME'], // 融资方 名称
            'AFEENUM' => $tradeCredit,// 转让金额
            'AFEETEXT' => transNumToZH($tradeCredit), // 甲方费用 中文
            'BUILDRATE' => '8%', // 建设期内
            'OPERATERATE' => '8%', // 运营期内
            'payFee' => $tradeCredit,
            'PAYFEETEXT' => transNumToZH($tradeCredit), // 甲方费用 中文
            'days' => "2",
            'STAMP' => $assetDetail['STAMP'] ? $assetDetail['STAMP'] : "imgs/badge_002.png"
        ];
        //用户合同数据
        $newRel = $contractApi->NewUserContract($prodId, $this->userBaseInfo['userId'], $buyerTradeId, $this->userBaseInfo['name'], $contractModelId, '股权收益权转让及服务协议（后续转让）', json_encode($contractData));
        if ($newRel['code'] != 200) {
            \Log::error('创建众筹转让合同失败：' . $newRel['message'] . '    :' . $buyerTradeId);
        } else {
            \Log::info($buyerTradeId . ' 转让合同创建成功');
        }
        return;
    }

    /**
     * 卖方合同
     * @param $sellerTradeId
     * @param $buyerTradeId
     * @param $prodId
     * @param $fee
     * @param $createTime
     * @param string $sellerName
     * @param string $sellerCardNo
     * @param string $sellerUid
     */
    function newTradeTransferSellerContract($sellerTradeId, $buyerTradeId, $prodId, $fee, $createTime, $sellerName = '', $sellerCardNo = '', $sellerUid = '00000')
    {
        //查询该订单是否已生成合同，有则不生成
        $contractApi = new ContractApi();
        $checkRel = $contractApi->getUserContract($sellerTradeId);
        if (isset($checkRel['result'])) {
            //\Log::warning('该交易合同已存在:'.$tradeId);
            return;
        }
        $tradeCredit = round($fee, 2);
        $prodMainId = substr($prodId, 0, 6);
        //查产品合同(用于获取 融资方和spv)
        $prodApi = new ProdApi();
        $prodContractRel = $prodApi->prodContract($prodMainId);
        if (empty($prodContractRel['result'])) {
            \Log::critical('生成产品合同错误，产品合同不存在' . $buyerTradeId);
            return;
        }
        $prodContract = $prodContractRel['result'];
        $assetDetail = json_decode($prodContract['assetDetail'], 1);

        $contractModelId = 'contract_100';

        //获取用户身份证信息
        $tradeApi = new TradeApi();
        $buyerInfo = $tradeApi->userCardNo($buyerTradeId);// 购买人（受让方）
        if ($buyerInfo['stateCode'] != '00000') {
            \Log::error('生成产品合同错误，获取用户信息失败：' . $buyerInfo['message'] . '   ' . $sellerTradeId . '  ' . $buyerTradeId);
            return;
        }
        $buyerInfo = $buyerInfo['memberInfo'];
        $taCFProdApi = new TACFProdApi();
        $taProdDetailRel = $taCFProdApi->getProdDetail($prodId);
        if (empty($taProdDetailRel['data'])) {
            \Log::critical('生成产品合同错误,众筹产品信息找不到');
            return;
        }
        $prodInfo = $taProdDetailRel['data']['productInfo'];
        $contractData = [
            'PRODID' => $prodId,
            'TRADEID' => $buyerTradeId,
            'YEAR' => date("Y"),
            'MONTH' => date("m"),
            'DAY' => date("d"),
            'TRANSDATE' => transZHTime(date_format(new \DateTime($createTime), 'Y-m-d')),

            'ASSIGNEE' => $buyerInfo['real_name'], // 甲方（受让方）
            'ASSIGNEEID' => $buyerInfo['card_no'],
            'ASSIGNEENAME' => $buyerInfo['real_name'],

            'TRANSFEROR' => $sellerName,
            'TRANSFERORID' => $sellerCardNo, // 乙方（转让方）
            'TRANSFERORIDNAME' => $sellerName, // 乙方（转让方）

            'DEBTOR' => $prodContract['transferor'], // 丙方：（债务人） spv
            'DEBTORADDR' => $prodContract['transferorAddr'], // 丙方：（债务人） spv地址

            'CORPORATIONNAME' => $assetDetail['CORPORATIONNAME'], // 目标企业的名称
            'ARATE' => round($tradeCredit * 100 / $prodInfo['totalCredit'], 2), // 甲方百分比股权

            'ORIGINALNAME' => $prodContract['transferor'], // 初始转让人名称(spv)
            'GOALNAME' => $assetDetail['CORPORATIONNAME'],  // 目标企业名称(融资方)
            'baseassetsName' => $prodContract['transferor'], // spv 名称
            'BASEASSETSORIGINALNAME' => $assetDetail['CORPORATIONNAME'], // 融资方 名称
            'AFEENUM' => $tradeCredit,// 转让金额
            'AFEETEXT' => transNumToZH($tradeCredit), // 甲方费用 中文
            'BUILDRATE' => '8%', // 建设期内
            'OPERATERATE' => '8%', // 运营期内
            'payFee' => $tradeCredit,
            'PAYFEETEXT' => transNumToZH($tradeCredit), // 甲方费用 中文
            'days' => "2",
            'STAMP' => $assetDetail['STAMP'] ? $assetDetail['STAMP'] : "imgs/badge_002.png"
        ];
        //用户合同数据
        $newRel = $contractApi->NewUserContract($prodId, $sellerUid, $sellerTradeId, $sellerName, $contractModelId, '股权收益权转让及服务协议（后续转让）', json_encode($contractData));
        if ($newRel['code'] != 200) {
            \Log::error('创建众筹转让合同失败：' . $newRel['message'] . '    :' . $buyerTradeId);
        } else {
            \Log::info($buyerTradeId . ' 转让合同创建成功');
        }
        return;
    }
}

