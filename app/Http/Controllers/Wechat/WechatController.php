<?php
/**
 * 微信部分
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-14
 * Time: 下午8:10
 */

namespace App\Http\Controllers\Wechat;
use App\Http\Controllers\Controller;
use App\DataServer\Front\UserApi;
use EasyWeChat\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WechatController extends Controller
{
    /**
     * 微信服务
     * @return mixed
     */
    function serve(){
        $wechat = app('wechat');
        $wechat->server->setMessageHandler(function($message){
            return "欢迎关注 Sunallies！";
        });
        return $wechat->server->serve();
    }


    /**
     * 微信登陆，获取openid，尝试openid登陆
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    function login(Request $request){
        //微信授权信息
        $user = session('wechat.oauth_user');
        //处理回调地址
        $frontUrl = config('front.wechat_login_callback');
        $login_success_urlback = $request->has('login_success_urlback') ? $frontUrl.$request->get('login_success_urlback') : $frontUrl;
        $login_failed_urlback = $request->has('login_failed_urlback') ? $frontUrl.$request->get('login_failed_urlback') : $frontUrl;
        $openId = $user->id;
        //尝试后台openid登陆
        $userApi = new UserApi();
        try{
            $loginRel = $userApi->DoLogin(['openId'=>$openId]);
            //如果登陆成功，跳转前端首页，在跳转链接中附带token和openId;如果不能登陆，则跳转前端首页，在跳转链接中附带openId
            if($loginRel['code'] == 200){
                $result = $loginRel['result'];
                $redirectUrl = $login_success_urlback.'?'.'openId='.$openId .'&'.'token='.$result['token'];
            }else{
                $redirectUrl = $login_failed_urlback.'?'.'openId='.$openId;
            }
            return redirect($redirectUrl,302);
        }catch(\Exception $e){
            return new JsonResponse(makeExceptionMsg($e));
        }
    }

    public function wechatJS(Application $wechat,Request $request){
        $url = $request->get('url',$request->fullUrl());
        $js =  $wechat->js;
        $js = $js->setUrl($url);
        $data = $js->config(array('onMenuShareQQ', 'onMenuShareWeibo'), true);
        return makeSuccessMsg(json_decode($data));

    }
}