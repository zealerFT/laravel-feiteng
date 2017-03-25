<?php
/**
 * PDF service api
 */
namespace App\DataServer\PDF;
use App\DataServer\BaseApi;
use App\Exceptions\PDF\PDFServiceApiException;

class PDFServiceApi extends BaseApi
{
    //api uri list
    const PDF_SERVICE = 'GetPdfService.php';   //获取pdf合同

    function __construct()
    {
        $this->apiUrl=config('sys-config.pdf_service_url');
    }

    /**
     * 获取pdf信息
     * @param $modelId
     * @param string $badge
     * @return mixed
     */
    function getPDFInfo($modelId,$badge=''){
        $params['model_name'] = $modelId;
        if(!empty($badge)){
            $params['badge'] = $badge;
        }
        return $this->method(self::PDF_SERVICE)->post($params);
    }

    /**
     * 获取合同
     * @param $fileName
     * @param $modelName
     * @param $contractData
     * @return mixed
     */
    function getTradePDF($fileName,$modelName,$contractData){
        $params = [
          'file_name'=>$fileName,
          'model_name'=>$modelName,
          'contract_data'=>$contractData,
        ];
        return $this->method(self::PDF_SERVICE)->post($params);
    }
}