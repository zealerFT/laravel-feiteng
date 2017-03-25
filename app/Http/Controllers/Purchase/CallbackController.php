<?php
/**
 * 交易过程中第三方支付的回调处理
 */
namespace App\Http\Controllers\Purchase;
use App\DataServer\Front\ActivityApi;
use App\DataServer\TA\PurchaseApi;
use App\Exceptions\BaseApiException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CallbackController extends Controller
{

    /**
     * 支付成功回调 只允许 get和post方式
     * @param Request $request
     * @param string $redirectUrlMark
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    function paymentRedirect(Request $request,$redirectUrlMark){
        $cacheMark = config('sys-config.pay_callback_cache_pre').$redirectUrlMark;
        if(!\Cache::has($cacheMark)){
            return redirect(config('sys-config.pay_callback_default'));
        }
        $cacheTransData = \Cache::pull($cacheMark);
        $tradeId = $cacheTransData['tradeId'];
        $callbackUrl = $cacheTransData['callbackUrl'];
        $reqInterface = $cacheTransData['reqInterface'];
        $httpMethod = $request->method();
        //回调链接拼接
        $responseDataString = '';
        if($httpMethod == 'POST'){//支付成功
            //$resData = json_decode($request->get('res_data'),1);
            \Log::info('支付成功回调：'.$request->get('res_data'));
            //$lianLianOrderId = $resData['no_order'];
            //根据连连交易id查询交易id
            $purchaseApi = new PurchaseApi();
            try{
                usleep(300);
                $purchaseDetail = $purchaseApi->purchaseDetail($tradeId);
            }catch(BaseApiException $e){
                \Log::info('1.回调订单查询异常：'.$e->getMessage());
                try{
                    usleep(500);
                    $purchaseDetail = $purchaseApi->purchaseDetail($tradeId);
                }catch(BaseApiException $e){
                    \Log::info('2.回调订单查询异常：'.$e->getMessage());
                    try{
                        usleep(1000);
                        $purchaseDetail = $purchaseApi->purchaseDetail($tradeId);
                    }catch(BaseApiException $e){
                        \Log::critical('3.回调订单查询异常：'.$e->getMessage());
                        //die('订单异常，请联系客服：'.$e->getMessage());
                    }
                }
            }
            if(isset($purchaseDetail)){//成功查询到了订单
                if($purchaseDetail['stateCode']!='00000'){
                    \Log::alert('POST支付成功回调：获取订单 '.$tradeId.' 详情失败--'.$purchaseDetail['stateCode'].'--'.$purchaseDetail['message']);
                    return redirect(config('sys-config.pay_callback_default'));
                    //die('订单异常，请联系客服：'.$purchaseDetail['message']);
                }
                \Log::info('支付成功回调，查询订单返回数据：'.json_encode($purchaseDetail));
                $purchaseDetail = $purchaseDetail['data'];
                //$tradeId = $purchaseDetail['tradeId'];
                $responseData = [
                    'reqInterface'      =>$purchaseDetail['reqInterface'] == 'PURCHASE' ? 1 : 0,//0 DEPOSIT 充值，1 PURCHASE 购买
                    'tradeId'           =>$purchaseDetail['tradeId'],
                    //'state'             =>in_array($purchaseDetail['stateNow'],['0000','2100']) ? 1 : 0,
                    'state'             =>1,//post过来的都视作成功
                    'stateMessage'      =>$purchaseDetail['stateMessage'],
                    'lastOrder'         =>0
                ];
                //如果是下单购买，则查询尾单
                if($purchaseDetail['reqInterface'] == 1){
                    $isLast = $purchaseApi->purchaseIsLast($tradeId);
                    $responseData['isLastOrder'] = $isLast['data']['isTargetTheLastOrder'];
                    //尾单抽奖机会
                    if($responseData['isLastOrder']){
                        $activityApi = new ActivityApi();
                        $activityApi->accountNewLuckydraw($purchaseDetail['account'],'tail_luckydraw');
                    }
                    //拼接链接
                    $responseDataString .= '?isLastOrder='.$responseData['isLastOrder'];
                }
            }else{//没有成功查询到订单
                //拼接链接
                $responseDataString .= '?isLastOrder=0';//没有查询到回调的订单信息，则默认该订单非尾单
            }

            $responseDataString .= '#/resultPay/'.$tradeId.'/'.$reqInterface.'/1';
        }else{//GET方式 支付失败
            $responseDataString .= '#/resultPay/'.$tradeId.'/'.$reqInterface.'/0';
            \Log::info('支付失败回调：'.$responseDataString);
        }

        //获取缓存中的回调地址
 /*       $cacheCallbackKey = config('sys-config.pay_callback_cache_pre').$tradeId;
        if(\Cache::has($cacheCallbackKey)){
            $callbackUrl = \Cache::pull($cacheCallbackKey);
        }else{
            $callbackUrl = config('sys-config.pay_callback_default');
        }*/
        $redirectUrl = $callbackUrl.$responseDataString;
        //回调信息包括  支付类型 交易id  支付状态 错误信息 是否是尾单
        return redirect($redirectUrl);
    }


}
