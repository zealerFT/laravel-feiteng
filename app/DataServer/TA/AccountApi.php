<?php
/**
 * TA 账户相关api
 */

namespace App\DataServer\TA;

class AccountApi extends TAApi
{
    //api uri list
    const ACCOUNT_DETAILS   = 'cgweb/c2s/account/getAccountDetails/v2';   //获取账户信息
    const ACCOUNT_CHECK_BIND_CARD   = 'cgweb/c2s/account/getAccountBindCardOrNot/v2';   //获取用户是否真实绑卡
    const ACCOUNT_BANK_INFO   = 'cgweb/c2s/account/getAccountBindCardInfo/v2';   //获取用户绑卡信息
    const ACCOUNT_BALANCE   = 'cgweb/c2s/opt/profile/balance';   //获取用户绑卡信息

    const ACCOUNT_OPEN   = 'cgweb/c2s/member/regist';   //TA开户
    const ACCOUNT_MODIFY_INFO   = 'cgweb/c2s/member/modify_auth_info';   //更新
    const ACCOUNT_BIND_CARD_EPAY   = 'cgweb/c2s/bank/ePayBindCard';   //易宝绑卡
    const ACCOUNT_BIND_CARD_EPAY_CONFIRM   = 'cgweb/c2s/bank/ePayConfirmBindCard';   //易宝绑卡短信确认

    const ACCOUNT_BIND_CARD_INFO = 'cgweb/c2s/bank/checkout';//查询用户绑卡信息

    const ACCOUNT_BALANCE_FLOW = 'cgweb/c2s/opt/profile/balanceFlow';//用户余额明细
    const ACCOUNT_INCOME_FLOW = 'cgweb/c2s/opt/flow/income';//用户收益明细
    const ACCOUNT_INCOME_BY_MONTH = 'cgweb/c2s/opt/flow/incomeByMonth';//用户收益按月累计

    const ACCOUNT_REGULAR_PROD_SUMMARY = 'cgweb/c2s/opt/profile/regularData';//用户定期产品收益相关
    const ACCOUNT_REGULAR_PROD_LIST = 'cgweb/c2s/opt/profile/regularList';//用户定期资产列表
    const ACCOUNT_REGULAR_PROD_DETAIL = 'cgweb/c2s/opt/profile/regularDetail';//用户定期资产详情

    const ACCOUNT_CF_PROD_SUMMARY = 'cgweb/c2s/opt/profile/crowdData';//用户众筹产品收益相关
    const ACCOUNT_CF_PROD_LIST = 'cgweb/c2s/opt/profile/crowdList';//用户众筹产品持有列表
    const ACCOUNT_CF_PROD_DETAIL = 'cgweb/c2s/opt/profile/crowdDetail';//用户众筹产品持有详情
    const ACCOUNT_CF_PROD_AVAIL_FLOW = 'cgweb/c2s/opt/profile/crowdAvailFlow';//用户众筹产品持有收益流水
    
    const ACCOUNT_INVITE_BALANCE = 'cgweb/c2s/opt/profile/inviteBalance';//用户邀请返现金额
    //const ACCOUNT_INVITE_MEMBER = 'cgweb/c2s/opt/profile/inviteMember';//用户邀请返现用户
    const ACCOUNT_STATUS = '/cgweb/c2s/account/accountStatus';//根据用户查询用户状态

    /**
     * 账户基本信息
     * @param string $account
     * @return mixed
     */
    function accountDetail($account){
        return  $this->method(self::ACCOUNT_DETAILS.'/'.$account)->get(['prodId'=>1000001]);
    }

    /**
     * 检测账户是否绑卡
     * @param $account
     * @return mixed
     */
    function checkBindCard($account){
        return $this->method(self::ACCOUNT_CHECK_BIND_CARD.'/'.$account)->get();
    }

    /**
     * 获取用户绑卡信息
     * @param $account
     * @return mixed
     */
    function getAccountBankInfo($account){
        return $this->method(self::ACCOUNT_BANK_INFO.'/'.$account)->get();
    }

    /**
     * 获取用户余额
     * @param $account
     * @return mixed
     */
    function getAccountBalance($account){
        return $this->method(self::ACCOUNT_BALANCE)->get(['account'=>$account]);
    }

    /**
     * 在TA开户
     * @param $mobile -注册手机号
     * @param $realName -真实姓名
     * @param $IDCard -身份证号
     * @return mixed
     */
    function openAccount($mobile,$realName,$IDCard){
        $params = [
            'mobile'=>$mobile,
            'realName'=>$realName,
            'cardNo'=>$IDCard,
            'cardType'=>1,
            'transpw'=>config('sys-config.trans_pw'),
            'perMember'=>'123',
            'msgSender'=>config('sys-config.msg_sender')
        ];
        return $this->method(self::ACCOUNT_OPEN)->post($params);
    }

    /**
     * 用户在未交易前，更改用户的开户信息
     * @param $account
     * @param $realName
     * @param $IDCard
     * @return mixed
     */
    function modifyAccountInfo($account,$realName,$IDCard){
        $params = [
            'account'=>$account,
            'realName'=>$realName,
            'cardType'=>1,
            'identityNo'=>$IDCard,
            'transpw'=>config('sys-config.trans_pw'),
            'msgSender'=>config('sys-config.msg_sender'),
        ];
        return $this->method(self::ACCOUNT_MODIFY_INFO)->post($params);
    }

    /**
     * 易宝绑卡
     * @param $account
     * @param $bankId - 银行id
     * @param $cardNo
     * @param $cardMobile
     * @return mixed
     */
    function bindCardByEpay($account,$cardMobile,$bankId,$cardNo){
        $params = [
            'account'	    =>$account,
            'bankId'        =>$bankId,
            'cardNo'        =>$cardNo,
            'userIp'        =>\Request::ip(),
            'cardMobile'    =>$cardMobile,
            'msgSender'   =>config('sys-config.msg_sender')
        ];
        return $this->method(self::ACCOUNT_BIND_CARD_EPAY)->post($params);
    }

    /**
     * 易宝绑卡-短信确认
     * @param $account
     * @param $bankId
     * @param $cardNo
     * @param $cardMobile
     * @param $smsCode
     * @param $requestId
     * @return mixed
     */
    function bindCardConfirmByEpay($account,$bankId,$cardNo,$cardMobile,$smsCode,$requestId){
        $params = [
            'account'           =>$account,
            'bankId'            =>$bankId,
            'cardNo'            =>$cardNo,
            'cardMobile'        =>$cardMobile,
            'validateCode'      =>$smsCode,
            'requestId'         =>$requestId,
            'msgSender'         =>config('sys-config.msg_sender')
        ];
        return $this->method(self::ACCOUNT_BIND_CARD_EPAY_CONFIRM)->post($params);
    }

    /**
     * 获取用户绑卡信息
     * @param $account
     * @return mixed
     */
    function bindCardInfoByAccount($account){
        $params = [
            'account'       =>$account,
            'msgSender'     =>config('sys-config.msg_sender')
        ];
        return $this->method(self::ACCOUNT_BIND_CARD_INFO)->post($params);

    }

    /**
     * 用户余额流水
     * @param $account
     * @param string $tradeId
     * @param int $length
     * @return mixed
     */
    function getBalanceFlow($account,$tradeId = "",$length = 10){
        $params = empty($tradeId) ? [] : ['initTradeId' => $tradeId];
        $params = array_merge($params,[
            'account'=>$account,
            'length'=>$length,
        ]);
        return $this->method(self::ACCOUNT_BALANCE_FLOW)->post($params);
    }

    /**
     * 用户收益明细
     * @param $account
     * @param string $serial
     * @param int $length
     * @return mixed
     */
    function getIncomeFlow($account,$serial = "",$length = 10){
        $params = empty($serial) ? [] : ['initSerial' => $serial];
        $params = array_merge($params,[
            'account'=>$account,
            'length'=>$length,
        ]);
        return $this->method(self::ACCOUNT_INCOME_FLOW)->post($params);
    }

    /**
     * 用户总收益按月总收益
     * @param $account
     * @return mixed
     */
    function getIncomeForMonth($account){
        return $this->method(self::ACCOUNT_INCOME_BY_MONTH)->post(['account'=>$account]);
    }

    /**
     * 用户持有定期产品收益概况
     * @param $account
     * @return mixed
     */
    function getRegularProdSummary($account){
        return $this->method(self::ACCOUNT_REGULAR_PROD_SUMMARY)->get(['account'=>$account]);
    }

    /**
     * 用户持有定期产品列表
     * @param $account
     * @param $length
     * @param $type 1.持有中 2.已到账
     * @param $initTradeId
     * @return mixed
     */
    function getRegularProdList($account,$type = 1,$length = 10,$initTradeId = ""){
        $params = [
            'account'=>$account,
            'type'=>$type,
            'length'=>$length,
        ];
        if(!empty($initTradeId)){
            $params['initTradeId'] = $initTradeId;
        }
        return $this->method(self::ACCOUNT_REGULAR_PROD_LIST)->get($params);
    }

    /**
     * 获取用户定期订单详情
     * @param $account
     * @param $tradeId
     * @return mixed
     */
    function getRegularProdDetail($account,$tradeId){
        $params = [
            'account'=>$account,
            'tradeId'=>$tradeId,
        ];
        return $this->method(self::ACCOUNT_REGULAR_PROD_DETAIL)->get($params);
    }

    /**
     * 获取用户众筹资产持有情况
     * @param $account
     * @return mixed
     */
    function getCFProdSummary($account){
        return $this->method(self::ACCOUNT_CF_PROD_SUMMARY)->get(['account'=>$account]);
    }

    /**
     * 用户持有众筹产品列表
     * @param $account
     * @param $length
     * @param $initTradeId
     * @return mixed
     */
    function getCFProdList($account,$length = 10,$initTradeId = ""){
        $params = [
            'account'=>$account,
            'length'=>$length,
        ];
        if(!empty($initTradeId)){
            $params['initTradeId'] = $initTradeId;
        }
        return $this->method(self::ACCOUNT_CF_PROD_LIST)->get($params);
    }

    /**
     * 获取用户众筹订单详情
     * @param $account
     * @param $tradeId
     * @return mixed
     */
    function getCFProdDetail($account,$tradeId){
        $params = [
            'account'=>$account,
            'tradeId'=>$tradeId,
        ];
        return $this->method(self::ACCOUNT_CF_PROD_DETAIL)->get($params);
    }

    /**
     * 获取用户众筹订单收益流水
     * @param $account
     * @param $tradeId
     * @return mixed
     */
    function getCFProdAvailFlow($account,$tradeId){
        $params = [
            'account'=>$account,
            'tradeId'=>$tradeId,
        ];
        return $this->method(self::ACCOUNT_CF_PROD_AVAIL_FLOW)->get($params);
    }
    
    /**
     * 获取用户邀请返现总和
     * @param $account
     * @param $tradeId		// 特征id
     * @param $length
     * @param $fromDate   // 起始日
     * @return $toDate		 // 结束日
     */
    function getInviteBalance($account,$tradeId=1000000,$length=10,$fromDate="2000-01-01",$toDate="5000-01-01"){
        $params = [
            'account'=>$account,
            'initTradeId' =>$tradeId,
            'length' =>$length,
            'fromDate'=>$fromDate,
            'toDate'=>$toDate,
        ];
        return $this->method(self::ACCOUNT_INVITE_BALANCE)->get($params);
    }
    
    /**
     * 批量获取用户绑卡支付状态
     * @param $accounts
     */
    function getAccountStatus($accounts=""){
    	$params = [
	    	'accounts'=>$accounts,
    	];
    	return $this->method(self::ACCOUNT_STATUS)->post($params);
    }
}