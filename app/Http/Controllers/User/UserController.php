<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-14
 * Time: 下午8:10
 */
namespace App\Http\Controllers\User;
use App\DataServer\Cornerstone\MemberApi;
use App\Exceptions\User\UserException;
use App\Http\Controllers\CommonTrait;
use App\Http\Controllers\Controller;
use App\DataServer\Front\BankApi;
use App\DataServer\Front\UserApi;
use App\DataServer\TA\AccountApi;
use App\Http\Requests\User\CouponDetailRequest;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\RegSmsCodeRetryRequest;
use App\Http\Requests\User\RegVerifyCapRequest;
use App\Http\Requests\User\ResetPwdRequest;
use App\Http\Requests\User\ResetPwdSmsCodeRequest;
use App\Http\Requests\User\ResetPwdSmsCodeVerifyRequest;
use App\Http\Requests\User\UserEvalRequest;
use App\Jobs\User\RegisterToRMQJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Toplan\PhpSms\Sms;
use Webpatser\Uuid\Uuid;

class UserController extends Controller
{
    use CommonTrait;
    private $userApi ;
    private $memberApi;
    function __construct()
    {
        $this->setBase();
        $this->userApi = new UserApi();
        $this->memberApi = new MemberApi();
    }

    /**
     * 用户注册，验证图形验证码,返回手机验证码
     * @param RegVerifyCapRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    function regVerifyCap(RegVerifyCapRequest $request){
        $mobile = $request->get('mobile');
        $captcha = $request->get('captcha');
        $captchaId = $request->get('captchaId');
        //验证图片验证码
        $checkRel =checkCaptchaById($captcha,$captchaId);
        if(!$checkRel){
            return makeFailedMsg(412,'请输入正确的图片验证码');
        }
        //获取手机验证码
        try{
            $smsCodeData = $this->memberApi->getSMSCode($mobile);
        }catch(UserException $e){
            return makeFailedMsg($e->getCode(),$e->getMessage());
        }
        if($smsCodeData['code']!='200'){
            return makeFailedMsg($smsCodeData['code'],transAuthMsg($smsCodeData['code']));
        }
        $smsCode = $smsCodeData['data']['validCode'];
        $verifyUserUUID = 'register_'.$mobile.'_'.Uuid::generate();
        //发送手机验证码
        $expiresMin = config('sys-config.verify_user_UUid_expires');
        if(env('APP_DEBUG')){
            $data = [
                'smsCode'=>$smsCode,
                'verifyUserUUID'=>$verifyUserUUID
            ];
            \Cache::add($verifyUserUUID,time().'_'.(time()+$expiresMin*60),$expiresMin);//标记这个获取短信验证码的id，10分钟
            return makeSuccessMsg($data);
        }else{
            $sms = Sms::make();
            $sms->content('【光合联萌】您的手机验证码：'.$smsCode.'，有效时间5分钟，为了您的账户安全，请不要向任何人泄露。');
            $sendSMSRel = $sms->to($mobile)->template(['Alidayu'=>'SMS_6195088'])->data(['code'=>$smsCode])->send();
            if(!$sendSMSRel['success']){
                \Log::alert('Send register SMS Error'.$mobile.'  smsCode:'.$smsCode,$sendSMSRel);
                return makeFailedMsg(500,'sent sms went wrong');
            }else{
                \Cache::add($verifyUserUUID,time().'_'.(time()+$expiresMin*60),$expiresMin);//标记这个获取短信验证码的id，10分钟
                return makeSuccessMsg(['verifyUserUUID'=>$verifyUserUUID]);
            }
        }
    }

    /**
     * 注册时重发短信验证码
     * @param RegSmsCodeRetryRequest $request
     * @return JsonResponse
     * @throws UserException
     */
    function regSmsCodeRetry(RegSmsCodeRetryRequest $request){
        $mobile = $request->get('mobile');
        $verifyUserUUID = $request->get('verifyUserUUID');
        if(!\Cache::has($verifyUserUUID) || empty(stristr($verifyUserUUID,$mobile))){
            return makeFailedMsg(500,'超时或非法操作');
        }
        $timeMark = explode('_',\Cache::get($verifyUserUUID));
        $lastSentTime = $timeMark[0];//短信上一次发放时间
        $expiresTime = $timeMark[1];//id的过期时间
        $sendSmsMin = config('sys-config.send_sms_expires');
        if(time() < $lastSentTime + 60*$sendSmsMin){//重新获取验证码时间间隔必须大于一分钟
            return makeFailedMsg(501,'短信验证码获取过于频繁');
        }
        //从后台获取手机验证码
        try{
            $smsCodeData = $this->memberApi->getSMSCode($mobile);
        }catch(UserException $e){
            return makeFailedMsg($e->getCode(),$e->getMessage());
        }
        if($smsCodeData['code']!='200'){
            return makeFailedMsg($smsCodeData['code'],transAuthMsg($smsCodeData['code']));
            //throw new UserException($smsCodeData['message'],$smsCodeData['code']);
        }
        $smsCode = $smsCodeData['data']['validCode'];
        //发送手机验证码
        if(env('APP_DEBUG')){
            $data = [
                'smsCode'=>$smsCode,
            ];
            \Cache::put($verifyUserUUID,time().'_'.$expiresTime,($expiresTime - time())/60);//标记这个获取短信验证码的id，10分钟
            return makeSuccessMsg($data);
        }else{
            $sms = Sms::make();
            $sms->content('【光合联萌】您的手机验证码：'.$smsCode.'，有效时间5分钟，为了您的账户安全，请不要向任何人泄露。');
            $sendSMSRel = $sms->to($mobile)->template(['Alidayu'=>'SMS_6195088'])->data(['code'=>$smsCode])->send();
            if(!$sendSMSRel['success']){
                \Log::alert('Send register SMS Error'.$mobile.'  smsCode:'.$smsCode,$sendSMSRel);
                return makeFailedMsg(500,'验证码发送失败，请联系客服！');
            }else{
                \Cache::put($verifyUserUUID,time().'_'.$expiresTime,($expiresTime - time())/60);//标记这个获取短信验证码的id，10分钟
                return makeSuccessMsg([]);
            }
        }

    }

    /**
     * 用户注册,验证手机号和手机验证码以及密码
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    function register(RegisterRequest $request){
        $mobile = $request->get('mobile');
        $smsCode = $request->get('smsCode');
        $password = $request->get('password');
        $inviteCode = $request->get('inviteCode','');
        $device = $request->get('device','');
        $channelType = $request->get('channelType','');
        //验证手机验证码，并注册
        try{
            $regRel = $this->memberApi->DoRegister($mobile,$password,$smsCode,$inviteCode,$device,$channelType);
        }catch(UserException $e){
            return makeFailedMsg($e->getCode(),$e->getMessage());
        }
        if($regRel['code'] == '200'){
            $uid = $regRel['data']['userId'];
            //用户中心注册成功， 向互金前台数据注册用户信息
            $addRel = $this->userApi->register($uid,$mobile,$password,$inviteCode,$device,$channelType);
            if($addRel['code'] == '200'){
                //执行登陆操作
                $loginRel = $this->memberApi->DoLogin(['mobile'=>$mobile,'password'=>$password]);
                if($loginRel['code'] != '200'){
                    return makeFailedMsg($loginRel['code'],'注册成功，自动登录失败，请手动登录');
                }else{
                    $this->dispatch((new RegisterToRMQJob($uid))->onQueue(config('sys-config.register_queue')));
                    return makeSuccessMsg(['token'=>$loginRel['data']['token'],'uId'=>$uid]);
                }
            }else{
                return makeFailedMsg(501,$addRel['message']);
            }
        }else{
            return makeFailedMsg($regRel['code'],transAuthMsg($regRel['code']));
        }
    }

    /**
     * 重置密码-发送短信验证码
     * @param ResetPwdSmsCodeRequest $request
     * @return JsonResponse
     * @throws UserException
     * @throws \Exception
     */
    public function resetPwdSmsCode(ResetPwdSmsCodeRequest $request){
        $mobile = $request->get('mobile');
        //判断获取验证码行为是否合法
        $verifyUserID = 'resetPWD_'.$mobile;
        if(\Cache::has($verifyUserID)){
            return makeFailedMsg(401,'短信验证码获取过于频繁');
        }
        //获取手机验证码
        try{
            $smsCodeData = $this->memberApi->getSMSCode($mobile,'resetPwd');
        }catch(UserException $e){
            return makeFailedMsg($e->getCode(),$e->getMessage());
        }

        if($smsCodeData['code']!='200'){
            return makeFailedMsg($smsCodeData['code'],transAuthMsg($smsCodeData['code']));
        }
        $smsCode = $smsCodeData['data']['validCode'];

        //发送手机验证码
        $sendSmsMin = config('sys-config.send_sms_expires');
        if(env('APP_DEBUG')){
            $data = [
                'smsCode'=>$smsCode,
            ];
            \Cache::add($verifyUserID,time(),$sendSmsMin);//标记这个获取短信验证码的id，10分钟
            return makeSuccessMsg($data);
        }else{
            $sms = Sms::make();
            $sms->content('【光合联萌】您的手机验证码：'.$smsCode.'，有效时间5分钟，为了您的账户安全，请不要向任何人泄露。');
            $sendSMSRel = $sms->to($mobile)->template(['Alidayu'=>'SMS_6195088'])->data(['code'=>$smsCode])->send();
            if(!$sendSMSRel['success']){
                \Log::alert('Send resetPwd SMS Error'.$mobile.'  smsCode:'.$smsCode,$sendSMSRel);
                return makeFailedMsg(500,'sent sms went wrong');
            }else{
                \Cache::add($verifyUserID,time(),$sendSmsMin);//标记此次短信发送
                return makeSuccessMsg();
            }
        }
    }

    /**
     * 验证短信验证码
     * @param ResetPwdSmsCodeVerifyRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    function resetPwdSmsCodeVerify(ResetPwdSmsCodeVerifyRequest $request){
        $mobile = $request->get('mobile');
        $smsCode = $request->get('smsCode');
        $checkRel = $this->memberApi->checkSMSCode($mobile,$smsCode);
        if($checkRel['code'] != 200){
            return makeFailedMsg(422,'请输入正确的短信验证码');
        }
        //生成令牌
        $expiresMin = config('sys-config.verify_user_UUid_expires');
        $resetPwdToken = (string)Uuid::generate();
        \Cache::add($resetPwdToken,$mobile,$expiresMin);
        return makeSuccessMsg(['resetPwdToken'=>$resetPwdToken]);
    }

    /**
     * 重置密码
     * @param ResetPwdRequest $request
     * @return JsonResponse
     */
    function resetPwd(ResetPwdRequest $request){
        $resetPwdToken = $request->get('resetPwdToken');
        $newPwd = $request->get('newPwd');
        if(!\Cache::has($resetPwdToken)){
            return makeFailedMsg(501,'令牌过期或非法');
        }
        $mobile = \Cache::pull($resetPwdToken);
        $resetRel = $this->memberApi->resetPwd($mobile,$newPwd);
        if($resetRel['code'] != 200)
        {
            return makeFailedMsg( 501, '密码重置出错');
        }else{
            return makeSuccessMsg();
        }
    }

    /**
     * 登陆
     * @param LoginRequest $request
     * @return JsonResponse
     */
    function login(LoginRequest $request){
        $loginData = $request->all();
        $loginRel = $this->memberApi->DoLogin($loginData);
        if($loginRel['code'] != '200'){
            return makeFailedMsg(501,transAuthMsg($loginRel['code']));
        }else{
            $loginData = $loginRel['data'];
            $token = $loginData['token'];
            if(isset($loginData['isFirstLogin']) && $loginData['isFirstLogin']){//判断是否是首次登陆

                $userInfoRel = $this->memberApi->getUserInfoByToken($token);
                if($userInfoRel['code'] != 200){
                    \Log::critical('用户首次登陆，获取用户信息失败：'.$userInfoRel['code'].'->'.$userInfoRel['message']);
                }else{
                    $userInfoData = $userInfoRel['data'];
                    $userId = $userInfoData['userId'];
                    //首次登陆进行注册
                    $userApi = new UserApi();
                    $addRel = $userApi->register($userId,$userInfoData['mobile'],'qqq111');
                    if($addRel['code'] == '200'){
                        //首次登陆，触发注册事件
                        $this->dispatch((new RegisterToRMQJob($userId))->onQueue(config('sys-config.register_queue')));
                    }elseif($addRel['code'] != 422){
                        \Log::critical('user first login ，but register failed:'.$userId.'---'.json_encode($addRel));
                        return makeFailedMsg(501,$addRel['message']);
                    }
                }
            }
            return makeSuccessMsg(['token'=>$loginData['token']]);
        }
    }

    /**
     * 登出
     * @param Request $request
     * @return mixed
     */
    function logout(Request $request){
        $token = $request->header('Token');
        if(!empty($token)){
            \Cache::forget($token);
        }
        $logoutRel = $this->memberApi->DoLogout();
        if($logoutRel['code'] == 200){
            return makeSuccessMsg();
        }else{
            return makeFailedMsg(401,$logoutRel['message']);
        }
    }

    /**
     * 我的账户
     *
     */
    function myAccount(){
        $accountApi = new AccountApi();
        if(!empty($this->account)){
            $userProperty = $accountApi->accountDetail($this->userBaseInfo['account']);
            if($userProperty['code'] != '200' || !isset($userProperty['result'])){
                \Log::alert('Get accountDetail Failed： REQUEST：'.json_encode($this->userBaseInfo).'  ,RESPONSE：'.json_encode($userProperty));
                return makeFailedMsg(501,'获取账户信息失败，请重试');
            }
            $userProperty = $userProperty['result'];
        }else{
            $userProperty = [
                'totalCredits' => $this->userBaseInfo['fee'] // 用户没实名，在cashback_temp表
            ];
        }
        return makeSuccessMsg(['userBaseInfo'=>$this->userBaseInfo,'userProperty'=>$userProperty]);
    }

    /**
     * 个人中心
     */
    function profile(){
        if(!empty($this->account)){//实名用户
            $taApi = new AccountApi();
            $bankInfo = $taApi->getAccountBankInfo($this->account);
            $bankInfoData = $bankInfo['result'];
            if(sizeof($bankInfoData['bankCard'])){//实名用户并绑卡
                //查询银行简称
                $bankFrontApi  = new BankApi();
                $bankFrontInfo = $bankFrontApi->bankInfo($bankInfoData['bankCard']['bankId']);
                //dd($bankFrontInfo);
                $bankFrontInfoData = $bankFrontInfo['result'];
                $bankShortName = $bankFrontInfoData['bankNameShort'];
                $profileData = [
                    'name'=>$this->userBaseInfo['name'],
                    'mobile'=>$this->userBaseInfo['mobile'],
                    'IDCardNo'=>substr_replace($bankInfoData['IDCardNo'],'******',4,10),
                    'bankName'=>$bankShortName,
                    'bankCardLastNo'=>substr($bankInfoData['bankCard']['cardNo'],-4),
                    'bankDesc'=>$bankFrontInfoData,
                ];
            }else{//实名用户未绑卡
                $profileData = [
                    'name'=>$this->userBaseInfo['name'],
                    'mobile'=>$this->userBaseInfo['mobile'],
                    'IDCardNo'=>substr_replace($bankInfoData['IDCardNo'],'******',4,10),
                    'bankName'=>'',
                    'bankCardLastNo'=>'',
                    'bankDesc'=>[]
                ];
            }
        }else{//没有实名用户
            $profileData = [
                'name'=>$this->userBaseInfo['name'],
                'mobile'=>$this->userBaseInfo['mobile'],
                'IDCardNo'=>'',
                'bankName'=>'',
                'bankCardLastNo'=>'',
                'bankDesc'=>[]
            ];
        }
        return makeSuccessMsg($profileData);
    }

    /**
     * 用户实名情况
     */
    function checkUserCertified(){
        $taApi = new AccountApi();
        return $taApi->checkBindCard($this->account);
    }

    /**
     * 获取用户优惠券详情
     * @param CouponDetailRequest $request
     * @return mixed
     */
    function couponDetail(CouponDetailRequest $request){
        $type = $request->get('type',1);
        $pageId = $request->get('pageId',1);
        $pageSize = $request->get('pageSize',10);
        return $this->userApi->userCouponDetail($type,$pageId,$pageSize);
    }

    /**
     * 获取用户优惠券数量
     * @param CouponDetailRequest $request
     * @return mixed
     */
    function couponCount(CouponDetailRequest $request){
        $type = $request->get('type',1);
        return $this->userApi->userCouponCount($type);
    }

    /**
     * 获取用户余额
     * @return JsonResponse
     * @throws UserException
     */
    function balance(){
        //未实名用户
        if(empty($this->account)){
            throw new UserException('用户未实名',501);
        }
        //用户余额
        $accountApi = new AccountApi();
        $accountBalance = $accountApi->getAccountBalance($this->account);
        if($accountBalance['stateCode'] != '00000'){
            return makeFailedMsg(501,'获取用户可用余额失败，请重试');
        }
        $userBalance = $accountBalance['data']['balance'];
        return makeSuccessMsg(['userBalance'=>$userBalance]);
    }

    /**
     * 设置用户投资评分
     * @param UserEvalRequest $request
     * @return mixed
     */
    function setEval(UserEvalRequest $request){
       /*   answer:2,1,1,2,2,1
            question_list_id:1*/
        $answerStr = $request->get('answer');
        $examId = $request->get('questionListId');
        $examList = config('sys-config.user_exam_list');
        if(!isset($examList[$examId])){
            return makeFailedMsg(501,'指定题组不存在');
        }
        $examData = $examList[$examId];
        $answerArr = explode(",", $answerStr);
        $answerNum = sizeof($answerArr);
        if(!sizeof($answerNum) || $answerNum != sizeof($examData)){
            return makeFailedMsg(501,'答案格式有误');
        }
        $scoreTotal = 0;
        foreach($answerArr as $index=>$answer){
            $scoreTotal += $examData[$index]['options'][$answer]['score'];
        }
        $setRel = $this->userApi->setUserEval($scoreTotal);
        if($setRel['code'] == 200){
            $reposeData = [
                'score'=>$scoreTotal,
                'qualify'=> $scoreTotal >= config('sys-config.user_risk_min_score') ? 1 : 0,
            ];
            return makeSuccessMsg($reposeData);
        }else{
            return makeFailedMsg($setRel['code'],$setRel['message']);
        }
    }

    /**
     * 获取众筹题组
     * @param $examId
     * @return JsonResponse
     */
    function getExam($examId){
        $userExamList = config('sys-config.user_exam_list');
        if(!isset($userExamList[$examId])){
            return makeFailedMsg(501,'指定题组不存在');
        }
        return makeSuccessMsg($userExamList[$examId]);
    }

    /**
     * 获取好友邀请数据汇总
     * @return JsonResponse
     */
    function invitedSummary(Request $request){
    	$dpTransFlow = $request->get('dpTransFlow', 0);  // 累计红包序列号，list 分页用
    	$balanceTradeId = $dpTransFlow > 0 ? substr($dpTransFlow, 14, 10) : 1000000;
    	$userId = $request->get('userId', 1000000); 					   // 好友列表序列号，list 分页用
        $length = $request->get('length', 10);
        $userId = is_numeric($userId) && $userId > 0 ? $userId : 1000000;
        $length = is_numeric($length) && $length > 0 ? $length : 10;
    	// 默认结果
    	$res = [
    		"inviteBalance"=>0, 			 // 金额总和
    		"inviteBalanceTotal"=>0,   // list 总条数 
    		"inviteBalanceList"=>[],     // list 
    		"inviteBalanceSumList"=>[],     // 汇总的list 
    		"inviteMember"=>0, 			 // 用户个数总和
    		"inviteMemberTotal"=>0,  // list 总条数 
    		"inviteMemberList"=>[],    // list 
		];

    	$accountApi = new AccountApi();
    	$userApi = new UserApi();
    	
    	// 获取用户好友邀请用户列表
    	$inviteMemberRes = $userApi->getInviteList($userId, $length);
    	if($inviteMemberRes['code'] == '200'){
    		$inviteList = $inviteMemberRes['data']['inviteList'];
    		$res['inviteMember'] = $res['inviteMemberTotal'] = $inviteMemberRes['data']['inviteNum'];
    		if(empty($inviteList)) {
    			$res["inviteMemberList"] = [];
    		} else {
    			$accountArr=array();//记录有
    			foreach ($inviteList as &$item) {
    				if(!empty($item['account'])){
    					$accountArr[]=$item['account'];
    				}
    				$item['name']=substr_replace($item['mobile'],'****',3,4);
    				$date = new \DateTime();
    				$date->setTimestamp($item['created_at']/1000);
    				$item['createdAt']=$date->format("Y-m-d H:i:s");
    				$item['userId']=$item['id'];
    				$item['isInvest']=0;
    				unset($item['mobile']);
    			}
    			if(!sizeof($accountArr)){//查询出的账户都没有account直接返回没有投资
    				$res['inviteMemberList'] = $inviteList;
    			}
    			$inviteAccountRes = $accountApi->getAccountStatus(implode(',',$accountArr));
    			if($inviteAccountRes['stateCode'] != '00000'){
    				return makeFailedMsg(501,'获取用户好友邀请用户失败，请重试');
    			}
    			$accountStatus=array();
    			if(!empty($inviteAccountRes['accountStatus'])) {
    				foreach($inviteAccountRes['accountStatus'] as $iVal){
    					$accountStatus[$iVal['account']]=array('status'=>$iVal['status'],'name'=>$iVal['name']);
    				}
    				foreach($inviteList as &$item){
    					//if(in_array($item['account'],$accountArr)){
    					if(  !empty($item['account']) &&  $accountStatus[$item['account']]['status'] == 1 ){
    						$item['isInvest']=$accountStatus[$item['account']]['status'];
    						$item['name']=$accountStatus[$item['account']]['name'];
    					}
    				}
    			}
    		}
    		$res['inviteMemberList'] = $inviteList;
    	}
    	
        //未实名用户直接返回默认
        if(!empty($this->account)){
        	// 获取用户好友邀请总额
        	$inviteBalanceRes = $accountApi->getInviteBalance($this->account,$balanceTradeId,$length);
        	if($inviteBalanceRes['stateCode'] != '00000'){
        		return makeFailedMsg(501,'获取用户好友邀请金额失败，请重试');
        	}
        	$res = $inviteBalanceRes['data'] + $res;
        	// 格式化 inviteBalanceSumList 里面内容
        	if(!empty($res['inviteBalanceSumList'])) {
        		$_tmpk = [];
        		foreach ($res['inviteBalanceSumList'] as $items) {
	        		$date = new \DateTime($items['date']);
		            $_key = $date->format('Y年m月');
		            $_tmpk[$_key] = $items['fee'];
        		}
        		$res['inviteBalanceSumList'] = $_tmpk;
        	}
        	// 格式化 inviteBalanceList 里面内容
        	$items = $res['inviteBalanceList'];
        	$items = array_map(function($item){
        		$item["stateMessage"] = getTradeRepType($item['type']);
        		$item["reqType"] = $item['type'];
        		$item["credits"] = $item['fee'];
        	
        		$date = new \DateTime();
        		$date->setTimestamp($item['time']/1000);
        		$weekarray=array("日","一","二","三","四","五","六");
        		$item['weekday'] = $weekarray[$date->format("w")];
        		$item["_key"] = $date->format('Y年m月');
        		$item['createtime'] = $date->format('Y-m-d H:i:s');
        		$item['_create_date'] = $date->format('m.d');
        		$item['dpTransFlow'] = $date->format('YmdHis').$item['trade_id'];
        		$item['tradeId'] = $item['trade_id'];
        		return $item;
        	}, $items);
        	$res['inviteBalanceList'] = $items;
        }
        return makeSuccessMsg($res);
    }
}
