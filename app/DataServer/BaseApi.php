<?php
/**
 * base api
 */
namespace App\DataServer;

use App\Exceptions\BaseApiException;
use Ixudra\Curl\Facades\Curl;

class BaseApi
{
    protected $apiUrl;
    private $curlAction;
    private $data;
    private $method;
    private $requestUrl;
    /*function __construct()
    {
        $this->apiUrl=config('sys-config.pdf_service_url');
    }*/

    /**
     * 初始化curl
     * @param $method
     * @return $this
     */
    protected function method($method){
        $this->method = $method;
        $this->requestUrl = $this->apiUrl.$this->method;
        $this->curlAction=Curl::to($this->requestUrl);
        return $this;
    }

    /**
     * 初始化自定义的服务器地址
     * @param $serverUrl
     * @return $this
     */
    protected function serverUrl($serverUrl){
        $this->method = '';
        $this->apiUrl = $serverUrl;
        $this->requestUrl = $this->apiUrl.$this->method;
        $this->curlAction=Curl::to($this->requestUrl);
        return $this;
    }

    protected function withOption($key, $value)
    {
        return $this->curlAction->withOption( $key, $value );
    }


    /**
     * @param string $reqMethod http请求方法
     * @param array $params 请求参数
     * @param int $times 重试次数
     * @return mixed
     * @throws BaseApiException
     */
    private function check($reqMethod = 'get', $params = [], $times = 3)
    {
        $curl = $this->curlAction;
        $curl = $curl->withOption('CONNECTTIMEOUT', 10);

        if (sizeof($params)) {
            $curl = $curl->withData($params);
        }

        $response = $this->send($curl, $reqMethod);// 发送请求

        while (!$response && $times-- > 0) {
            $this->curlAction = $curl->withOption('URL', $this->apiUrl . $this->method);
            // 失败重试
            usleep(200 * 1000);// usleep单位微秒  200 毫秒
            $response = $this->send($curl, $reqMethod);
        }

        if (!$response) {
            throw new BaseApiException('Api Request Error:' . $this->requestUrl . ' ;' . json_encode($this->data), 408);
        } else {
            return json_decode($response, true);
        }
    }

    /**
     * 真正执行curl请求
     * @param $curl  curl 句柄
     * @param string $reqMethod http方法名  get post put delete
     * @return mixed
     */
    private function send($curl, $reqMethod = 'get')
    {
        $reqStartTime = microtime(true);
        switch ($reqMethod) {
            case 'post':
                $response = $curl->post();
                break;
            case 'put':
                $response = $curl->asJsonRequest()->put();
                break;
            case 'delete':
                $response = $curl->delete();
                break;
            default:
                $response = $curl->get();
                break;
        }
        $reqTime = microtime(true) - $reqStartTime;//请求时间
        \Log::info('[***Api REQ***]:' . $this->apiUrl.$this->method . ',HTTP METHOD:' . $reqMethod . ',PARAMS :' . json_encode($this->data) . ';[***Api Rel***]:' . $response . ';[***REQ TIME***]:' . $reqTime);
        return $response;
    }

    /**
     * GET
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    protected function get($data = [])
    {
        $this->data = $data;
        return $this->check('get', $this->data);
    }

    /**
     * POST
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    protected function post($data)
    {
        $this->data = $data;
        return $this->check('post', $this->data);
    }

    /**
     * PUT
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    protected function put($data)
    {
        $this->data = $data;
        return $this->check('put', $this->data);
    }

    /**
     * DELETE
     * @return mixed
     * @throws \Exception
     */
    protected function delete()
    {
        return $this->check('delete');
    }


}