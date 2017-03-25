<?php

/**
 * 用户信息相关
 */
namespace App\DataServer\Hybrid;
use App\DataServer\Front\BankApi;
use App\DataServer\Front\UserApi;
use App\DataServer\TA\AccountApi;
use App\Exceptions\Hybrid\UserServiceException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UserService
{

    /**
     * 通过account获取用户银行卡和支付渠道信息
     * @param $account
     * @return mixed
     */
    function getBankInfoByAccount($account){
        $taAccount = new AccountApi();
        $taBankInfo = $taAccount->getAccountBankInfo($account);
        $bankId = $taBankInfo['result']['bankCard']['bankId'];
        $bankApi = new BankApi();
        $userBankInfo = $bankApi->bankInfo($bankId);
        return $userBankInfo['result'];
    }

    /**
     * 获取用户基本信息
     * @param bool $needVerify
     * @return array|mixed
     * @throws UserServiceException
     */
    function getUserInfo($needVerify = true){
        $token = \Request::header('Token');
        if(!empty($token)) {
            //if ((\App::environment() != 'production') || (!\Cache::has($token))) {
                $userApi = new UserApi();
                $userBaseInfo = $userApi->profile();
                if ($userBaseInfo['code'] != 200) {
                    if($needVerify){
                        throw new UnauthorizedHttpException('Basic realm="My Realm"', $userBaseInfo['message']);
                    }else{
                        return [];
                    }
                } else {
                    $userBaseInfo = $userBaseInfo['result'];
                    if (empty($userBaseInfo['account'])) {//没有account
                        $userBaseInfo['isBindCard'] = 0;
                    } else {//有account
                        $accountApi = new AccountApi();
                        $isBindCardRel = $accountApi->checkBindCard($userBaseInfo['account']);
                        if($isBindCardRel['code'] != 200){
                            throw new UserServiceException('TA Error:'.$isBindCardRel['message']);
                        }
                        $userBaseInfo['isBindCard'] = $isBindCardRel['result'];
                    }
                    \Cache::put($token, $userBaseInfo,24*60);//缓存一天
                }
           // }
            return \Cache::get($token);
        }else{
            if($needVerify){
                throw new UnauthorizedHttpException('Basic realm="My Realm"', 'need token');
            }else{
                return [];
            }
        }
    }

    /**
     * 更新用户缓存信息
     * @throws UserServiceException
     */
    function updateUserInfo(){
        $token = \Request::header('Token');
        $userApi = new UserApi();
        $userBaseInfo = $userApi->profile();
        if ($userBaseInfo['code'] != 200) {
            throw new UserServiceException('更新用户信息失败：'.$userBaseInfo['message'],501);
        } else {
            $userBaseInfo = $userBaseInfo['result'];
            if (empty($userBaseInfo['account'])) {//没有account
                $userBaseInfo['isBindCard'] = 0;
            } else {//有account
                $accountApi = new AccountApi();
                $isBindCardRel = $accountApi->checkBindCard($userBaseInfo['account']);
                $userBaseInfo['isBindCard'] = $isBindCardRel['result'];
            }
            \Cache::forever($token, $userBaseInfo);
        }
    }
}