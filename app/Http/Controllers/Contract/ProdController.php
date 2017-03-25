<?php
/**
 * 产品合同
 */
namespace App\Http\Controllers\Contract;

use App\DataServer\Front\ProdApi;
use App\DataServer\PDF\PDFServiceApi;
use App\Http\Controllers\Controller;

class ProdController extends Controller
{

    /**
     * 产品合同模板 未签订的空模板
     * @param $prodId
     * @return \Illuminate\Http\JsonResponse
     */
    function prodEmpty($prodId)
    {
        $prodMainId = substr($prodId, 0, 6);
        //获取产品合同信息  模板和公章
        $prodApi = new ProdApi();
        $prodContractRel = $prodApi->prodContract($prodMainId);
        $prodContract = $prodContractRel['result'];
        if (!$prodContract) {
            return makeFailedMsg(501, '合同不存在');
        }
        $contractModelId = $prodContract['contractModelId'];
        $assetDetail = json_decode($prodContract['assetDetail'], 1);
        $contractStamp = isset($assetDetail['STAMP']) ? $assetDetail['STAMP'] : "imgs/badge_002.png";
        //调用pdf service
        $pdfServiceApi = new PDFServiceApi();
        $contractPDFInfoRel = $pdfServiceApi->getPDFInfo($contractModelId, $contractStamp);
        if ($contractPDFInfoRel['code'] != 0) {
            return makeFailedMsg(501, $contractPDFInfoRel['message']);
        }
        $contractData = [
            'modelHtmlLink' => $contractPDFInfoRel['model_html_link']
        ];
        return makeSuccessMsg($contractData);
    }

    /**
     * 众筹转让 模板合同
     * @param $prodId 产品id
     * @return \Illuminate\Http\JsonResponse
     */
    function prodTransferEmpty($prodId)
    {
        //获取产品合同信息  模板和公章
        $prodMainId = substr($prodId, 0, 6);
        //获取产品合同信息  模板和公章
        $prodApi = new ProdApi();
        $prodContractRel = $prodApi->prodContract($prodMainId);
        $prodContract = $prodContractRel['result'];
        if (!$prodContract) {
            return makeFailedMsg(501, '合同不存在');
        }
//        dd($prodContract);
        $assetDetail = json_decode($prodContract['assetDetail'], 1);
        $contractStamp = isset($assetDetail['STAMP']) ? $assetDetail['STAMP'] : "imgs/badge_002.png";
        $contractModelId = 'contract_100'; //指定 合同编号为 100
        //调用pdf service
        $pdfServiceApi = new PDFServiceApi();
        $contractPDFInfoRel = $pdfServiceApi->getPDFInfo($contractModelId, $contractStamp);
        if ($contractPDFInfoRel['code'] != 0) {
            return makeFailedMsg(501, $contractPDFInfoRel['message']);
        }
        $contractData = [
            'title' => '股权收益权转让及服务协议（后续转让）',
            'prodCreateTime' => $prodContract['createTime'],
            'modelHtmlLink' => $contractPDFInfoRel['model_html_link']
        ];
        return makeSuccessMsg($contractData);
    }
}
