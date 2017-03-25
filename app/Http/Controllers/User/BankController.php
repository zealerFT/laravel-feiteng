<?php
/**
 * 用户银行相关
 */

namespace App\Http\Controllers\User;
use App\DataServer\Front\BankApi;
use App\DataServer\Front\UserApi;
use App\DataServer\Hybrid\UserService;
use App\DataServer\TA\AccountApi;
use App\Exceptions\FrontApi\BankApiException;
use App\Exceptions\Hybrid\UserServiceException;
use App\Exceptions\User\BankException;
use App\Http\Controllers\CommonTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\BindCardConfirmRequest;
use App\Http\Requests\User\BindCardRequest;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BankController extends Controller
{
    use CommonTrait;
    function __construct(){
        $this->setBase();
    }

    /**
     * 绑卡页面-获取绑卡银行信息
     * @return \Illuminate\Http\JsonResponse
     * @throws BankException
     */
    function bindCardBankList(){
        //检测是否已绑卡
        if($this->userBaseInfo['isBindCard']){
            return makeFailedMsg(501,'用户已绑卡，不能执行此操作');
            //throw new BankException('用户已绑卡，不能执行此操作',501);
        }
        $bankApi = new BankApi();
        $bankList = $bankApi->getBindCardBankList();
        return makeSuccessMsg($bankList);
    }

    /**
     * 绑卡页面-获取用户实名信息
     * @return \Illuminate\Http\JsonResponse
     * @throws BankException
     */
    function bindCardAccount(){
        //检测是否已绑卡
        if($this->userBaseInfo['isBindCard']){
            return makeFailedMsg(501,'用户已绑卡，不能执行此操作');
            //throw new BankException('用户已绑卡，不能执行此操作',501);
        }
        $responseData = [
            'name'          =>'',
            'cardNo'        =>'',
            'bankMobile'    =>$this->userBaseInfo['mobile'],
        ];
        //如果已实名，则取回账户身份证号和姓名  没有实名的则返回手机号
        if(!empty($this->account)){
            $taAccountApi = new AccountApi();
            $accountInfoRel = $taAccountApi->getAccountBankInfo($this->account);
            if($accountInfoRel['code'] != 200){
                return makeFailedMsg(501,'获取用户账户信息失败，请重试：'.$accountInfoRel['message']);
                //throw new BankException('获取用户账户信息失败，请重试：'.$accountInfoRel['message'],501);
            }
            $accountInfo = $accountInfoRel['result'];
            $responseData['name'] = $accountInfo['name'];
            $responseData['cardNo'] = $accountInfo['IDCardNo'];
        }
        return makeSuccessMsg($responseData);
    }


    /**
     * 用户绑卡 默认易宝绑卡
     * @param BindCardRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws BankException
     * @throws \App\Exceptions\FrontApi\BankApiException
     */
    function  bindCard(BindCardRequest  $request){
        $realName = $request->get('realName');
        $IDCard = $request->get('IDCard');
        $cardNo = $request->get('cardNo');
        $cardMobile = $request->get('cardMobile');
        $taBankId = $request->get('bankId','');
        //判断是否实名过
        $userServiceApi = new UserService();
        $userInfo = $userServiceApi->getUserInfo();
        $accountApi = new AccountApi();
        if(empty($userInfo['account'])){//从未开户过
            //在后台开户
            $userServiceApi->updateUserInfo();
            $openAccountRel = $accountApi->openAccount($this->userBaseInfo['mobile'],$realName,$IDCard);
            if($openAccountRel['stateCode'] != '00000'){
                return makeFailedMsg(501,$openAccountRel['stateCode'].':'.$openAccountRel['message']);
                //throw new BankException($openAccountRel['stateCode'].':'.$openAccountRel['message'],501);
            }
            $account = $openAccountRel['account'];
            //在前台更新用户account
            $userApi = new UserApi();
            $updateUserRel = $userApi->updateUserInfo($account,$realName);
            if($updateUserRel['code'] != "200"){
                return makeFailedMsg(501,'用户实名，更新用户数据失败:'.$updateUserRel['message']);
                //throw new BankException('用户实名，更新用户数据失败:'.$updateUserRel['message'],501);
            }
            //在用户缓存中更新用户数据
            try{
                $userServiceApi->updateUserInfo();
            }catch(UserServiceException $e){
                return makeFailedMsg(501,'用户实名成功，更新用户缓存信息失败！');
            }
        }else{//之前已开户，现在绑卡时，将信息更新成最新的提交信息
            $account = $userInfo['account'];
            $modifyRel = $accountApi->modifyAccountInfo($account,$realName,$IDCard);
            if($modifyRel['stateCode'] != '00000'){
                return makeFailedMsg(501,'用户实名信息更新失败：'.$modifyRel['message']);
                //throw new BankException($modifyRel['stateCode'].':'.$modifyRel['message'],501);
            }
        }

        //易宝绑卡
        $bankApi = new BankApi();
        try{
            $bankInfo = $bankApi->getBankIdByCardNo($cardNo);
            $bankId = $bankInfo['result']['taBankId'];
            if($taBankId != $bankId && !empty($taBankId)){
                return makeFailedMsg(422,'银行卡号与所选银行不符');
            }
        }catch(BankApiException $e){
            return makeFailedMsg($e->getCode(),$e->getMessage());
        }
        $bindCardRel = $accountApi->bindCardByEpay($account,$cardMobile,$bankId,$cardNo);
        if($bindCardRel['stateCode'] != '00000'){
            return makeFailedMsg(501,$bindCardRel['message']);
        }else{
            return makeSuccessMsg($bindCardRel);
        }
    }

    /**
     * 易宝绑卡短信确认
     * @param BindCardConfirmRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\FrontApi\BankApiException
     */
    function bindCardConfirm(BindCardConfirmRequest $request){
        $cardNo = $request->get('cardNo');
        $cardMobile = $request->get('cardMobile');
        $validateCode = $request->get('validateCode');
        $requestId = $request->get('requestId');
        //易宝绑卡
        $bankApi = new BankApi();
        try{
            $bankInfo = $bankApi->getBankIdByCardNo($cardNo);
            $bankId = $bankInfo['result']['taBankId'];
        }catch(BankApiException $e){
            return makeFailedMsg($e->getCode(),$e->getMessage());
        }

        //获取account
        $userApi = new UserApi();
        $userBaseInfo = $userApi->profile();
        $account = $userBaseInfo['result']['account'];
        //绑卡确认
        $accountApi = new AccountApi();
        $bindCardConfirmRel = $accountApi->bindCardConfirmByEpay($account,$bankId,$cardNo,$cardMobile,$validateCode,$requestId);
        if($bindCardConfirmRel['stateCode'] == '00000'){
            return makeSuccessMsg($bindCardConfirmRel);
        }elseif($bindCardConfirmRel['stateCode'] == '600311'){
            return makeFailedMsg(406,$bindCardConfirmRel['message']);
        }else{
            return makeFailedMsg(501,$bindCardConfirmRel['message']);
        }
    }

    /**
     * 查询用户绑卡信息
     * @return \Illuminate\Http\JsonResponse
     */
    function bindCardInfo(){
        if(!$this->userBaseInfo['isBindCard']){
            throw new UnauthorizedHttpException('Basic realm="My Realm"','用户还没有绑定银行卡');
        }
        $taAccountApi = new AccountApi();
        $bindCardInfoRel = $taAccountApi->bindCardInfoByAccount($this->account);
        if($bindCardInfoRel['stateCode'] == '00000'){
            $bankId = $bindCardInfoRel['bankId'];
            $bankApi = new BankApi();
            $bankInfoRel = $bankApi->bankInfo($bankId);
            $bankInfo = $bankInfoRel['result'];
            $bindCardInfoRel['bankName'] = $bankInfo['bankName'];
            $bindCardInfoRel['bankNameShort'] = $bankInfo['bankNameShort'];
            return makeSuccessMsg($bindCardInfoRel);
        }else{
            return makeFailedMsg(501,$bindCardInfoRel['message']);
        }
    }



}