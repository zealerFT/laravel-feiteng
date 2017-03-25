<?php


Route::get('/', function () {
    return makeSuccessMsg([
        'aboutUs'=>'“光合联萌”是东方日升新能源股份有限公司（股票代码300118）打造的“新能源+金融创新+能源互联网”的平台，以构建全新的能源互联网金融生态圈。',
        'joinUs'=>'https://www.lagou.com/gongsi/95054.html'
    ]);
});

//前端配置
Route::get('/config',['as'=>'system.config','uses'=>'SystemController@config']);

//User
Route::group(['prefix' => 'user', 'namespace' => 'User','middleware'=>'checkToken'], function () {
    //注册
    //验证手机号和图片验证码，成功：发送手机验证码
    Route::post('/register/verifyCap',['as'=>'user.register.regVerifyCap','uses'=>'UserController@regVerifyCap']);
    //重试：发送手机验证码
    Route::post('/register/regSmsCodeRetry',['as'=>'user.register.regSmsCodeRetry','uses'=>'UserController@regSmsCodeRetry']);
    //接收手机号，密码，手机验证码 ，验证手机验证码，进行注册
    Route::post('/register',['as'=>'user.register','uses'=>'UserController@register']);
    //重置密码
    //验证手机号和发送手机验证码&重试发送手机验证码
    Route::post('/resetPwd/smsCode',['as'=>'user.resetPwd.smsCode','uses'=>'UserController@resetPwdSmsCode']);

    //验证手机验证码
    Route::post('/resetPwd/smsCodeVerify',['as'=>'user.resetPwd.smsCodeVerify','uses'=>'UserController@resetPwdSmsCodeVerify']);
    //重置用户密码
    Route::put('/resetPwd',['as'=>'user.resetPwd','uses'=>'UserController@resetPwd']);

    //登陆
    Route::post('/login',['as'=>'user.login','uses'=>'UserController@login']);
    //登出
    Route::delete('/logout',['as'=>'user.logout','uses'=>'UserController@logout']);
    //个人中心
    Route::get('/profile',['as'=>'user.profile','uses'=>'UserController@profile']);
    //我的账户
    Route::get('/myAccount',['as'=>'user.myAccount','uses'=>'UserController@myAccount']);
    //用户状态
    Route::get('/userStatus',['as'=>'user.userStatus','uses'=>'UserController@userStatus']);
    //获取验证码id
    Route::get('/captcha/{type?}',['as'=>'user.captcha.info','uses'=>'CaptchaController@getCaptchaInfo']);
    //生成验证码
    Route::get('/captcha/{type}/{id}',['as'=>'user.captcha','uses'=>'CaptchaController@getCaptcha']);
    //检测用户是否真绑卡
    Route::get('/checkUserCertified',['as'=>'user.checkUserCertified','uses'=>'UserController@checkUserCertified']);
    //用户优惠券详情
    Route::get('/couponDetail',['as'=>'user.coupon.detail','uses'=>'UserController@couponDetail']);
    //用户优惠券数量
    Route::get('/couponCount',['as'=>'user.coupon.count','uses'=>'UserController@couponCount']);
    //用户可用余额
    Route::get('/balance',['as'=>'user.balance','uses'=>'UserController@balance']);
    //设置用户投资评分
    Route::post('/eval/set',['as'=>'user.eval.set','uses'=>'UserController@setEval']);
    //获取用户投资风险评估题组
    Route::get('/eval/{examId}',['as'=>'user.eval.exam','uses'=>'UserController@getExam']);
    //绑卡
    Route::post('/bindCard',['as'=>'user.bindCard','uses'=>'BankController@bindCard']);
    Route::post('/bindCardConfirm',['as'=>'user.bindCardConfirm','uses'=>'BankController@bindCardConfirm']);
    Route::get('/bindCardBankList',['as'=>'user.bindCardBankList','uses'=>'BankController@bindCardBankList']);
    Route::get('/bindCardAccount',['as'=>'user.bindCardAccount','uses'=>'BankController@bindCardAccount']);
    Route::get('/bindCardInfo',['as'=>'user.bindCardInfo','uses'=>'BankController@bindCardInfo']);
    //好友邀请
    Route::get('/invitedSummary',['as'=>'user.getInvited','uses'=>'UserController@invitedSummary']);
});
//自定义页面
Route::group(['prefix'=> 'common','namespace'=>'Common'],function(){
    //首页
    Route::get('home',['as'=>'common.home','uses'=>'HomeController@home']);
});
//电站相关
Route::group(['prefix'=> 'powerStation','namespace'=>'PowerStation'],function(){
	//电站发电展示
    Route::get('psinfo/{indexId}',['as'=>'common.ps','uses'=>'PowerStationController@getByIndexId']);
    Route::get('/pslist', ['as' => 'list.ps', 'uses' => 'PowerStationController@getPsList']);
});
//产品相关
Route::group(['prefix'=> 'product','namespace'=>'Product'],function(){
    //定期产品
    Route::get('regular/{pid}/summary',['as'=>'product.regular.summary','uses'=>'RegularController@summary']);
    Route::get('regular/{pid}/detail',['as'=>'product.regular.detail','uses'=>'RegularController@detail']);

    //众筹产品
    Route::get('cf/list',['as'=>'product.cf.list','uses'=>'CFController@prodList']);
    Route::get('cf/{pid}/detail',['as'=>'product.cf.detail','uses'=>'CFController@prodDetail']);
    Route::get('cf/{pid}/follow',['as'=>'product.cf.addFollow','uses'=>'CFController@addFollow']);
    Route::get('cfTransfer/list',['as'=>'product.cfTransfer.list','uses'=>'CFController@prodTransferList']);
    Route::get('cfTransfer/{tradeId}/detail',['as'=>'product.cfTransfer.detail','uses'=>'CFController@prodTransferDetail']);

    //活期产品
    Route::get('dp/detail',['as'=>'product.dp.detail','uses'=>'DPController@dpProdDetail']);
    Route::get('dp/redeem',['as'=>'product.dp.redeem','uses'=>'DPController@dpRedeem']);

});

//合同相关
Route::group(['prefix'=>'contract','namespace'=>'Contract','middleware'=>'checkToken'],function(){
    Route::get('prod/{pid}',['as'=>'contract.prod','uses'=>'ProdController@prodEmpty']);
    Route::get('prodTransfer/{pid}',['as'=>'contract.prod.transfer','uses'=>'ProdController@prodTransferEmpty']);// 众筹转让模板合同
    Route::get('user/{tradeId}',['as'=>'contract.user.show','uses'=>'UserController@tradeContract']);
    Route::get('user/transfer/{tradeId}',['as'=>'contract.user.show','uses'=>'UserController@transferTradeContract']);
    //生成合同
    Route::get('newTradeContract/{tradeId}',['as'=>'contract.newTradeContract','uses'=>'UserController@newTradeContract']);
});
//活动（抽奖）
Route::group(['prefix' => 'activity', 'namespace' => 'Activity','middleware'=>'checkToken'], function () {
    Route::get('luckydrawChance',['as'=>'activity.luckydrawChance','uses'=>'LuckydrawController@luckydrawChance']);

    Route::group(['prefix' => 'springFestival2017', 'namespace' => 'SpringFestival2017'], function(){
        Route::get('luckydrawChancesAndRecords',['as'=>'activity.springFestival2017.luckydrawChancesAndRecords','uses'=>'LuckydrawController@luckydrawChancesAndRecords']);
        Route::get('luckydraw',['as'=>'activity.springFestival2017.luckydraw','uses'=>'LuckydrawController@luckydraw']);
        Route::get('luckydrawTime',['as'=>'activity.springFestival2017.luckydrawTime','uses'=>'LuckydrawController@luckydrawTime']);
        Route::get('redPacketState', ['as' => 'activity.springFestival2017.redPacketState', 'uses' => 'RedPacketController@redPacketState']);
        Route::get('insertRedPacket', ['as' => 'activity.springFestival2017.insertRedPacket', 'uses' => 'RedPacketController@insertRedPacket']);
        Route::get('investmentRankInfo', ['as' => 'activity.springFestival2017.investmentRankInfo', 'uses' => 'InvestmentRankController@investmentRankInfo']);
        Route::get('investmentRankTime', ['as' => 'activity.springFestival2017.investmentRankTime', 'uses' => 'InvestmentRankController@investmentRankTime']);
    });

    Route::group(['prefix' => 'homeActivityInfo', 'namespace' => 'HomeActivityInfo'], function(){
        Route::get('springFestival2017Info', ['as' => 'activity.homeActivityInfo.springFestival2017Info', 'uses' => 'SpringFestival2017Controller@springFestival2017Info']);
    });
});


//交易下单相关
Route::group(['prefix' => 'purchase', 'namespace' => 'Purchase','middleware'=>'checkToken'], function () {
    //下单页
    //定期产品购买
    Route::get('regularProd/{prodId}',['as'=>'purchase.regularProd','uses'=>'OrderController@regularProd']);
    //众筹产品购买
    Route::get('cfProd/{prodId}',['as'=>'purchase.cfProd','uses'=>'OrderController@cfProd']);
    //活期产品购买
    Route::get('dpProd',['as'=>'purchase.dpProd','uses'=>'OrderController@dpProd']);
    //活期产品转让
    Route::post('dpRedeem',['as'=>'purchase.dpRedeem','uses'=>'PurchaseController@dpRedeemAction']);

    //定期和众筹产品的申购
    Route::post('balance',['as'=>'purchase.balance','uses'=>'PurchaseController@balance']);
    Route::post('quickPayment',['as'=>'purchase.quickPayment','uses'=>'PurchaseController@quickPayment']);
    Route::post('hybridPayment',['as'=>'purchase.hybridPayment','uses'=>'PurchaseController@hybridPayment']);
    //活期产品申购
    Route::post('dpBalance',['as'=>'purchase.dpBalance','uses'=>'PurchaseController@dpBalance']);
    Route::post('dpQuickPayment',['as'=>'purchase.dpQuickPayment','uses'=>'PurchaseController@dpQuickPayment']);
    Route::post('dpHybridPayment',['as'=>'purchase.dpHybridPayment','uses'=>'PurchaseController@dpHybridPayment']);
    //易宝支付确认
    Route::post('epayConfirm',['as'=>'purchase.epayConfirm','uses'=>'PurchaseController@epayConfirm']);
    Route::post('epaySendValidateCode',['as'=>'purchase.epaySendValidateCode','uses'=>'PurchaseController@epaySendValidateCode']);
    Route::get('{orderId}/epayQueryOrder',['as'=>'purchase.epayQueryOrder','uses'=>'PurchaseController@epayQueryOrder']);

    // 众筹产品转让
    Route::post('cfTransfer',['as'=>'purchase.cfTransfer','uses'=>'PurchaseController@cfTransfer']);// 申请众筹转让
    Route::post('cancelCFTransfer',['as'=>'purchase.cancelCFTransfer','uses'=>'PurchaseController@cancelCFTransfer']);// 取消众筹转让
    Route::post('transferBalance',['as'=>'purchase.transfer.balance','uses'=>'PurchaseController@transferBalance']);// 众筹转让余额支付
    Route::post('transferQuickPayment',['as'=>'purchase.transfer.quickPayment','uses'=>'PurchaseController@transferQuickPayment']);// 众筹转让快捷支付
    Route::post('transferHybridPayment',['as'=>'purchase.transfer.hybridPayment','uses'=>'PurchaseController@transferHybridPayment']);


    //支付回调
    Route::match(['get', 'post'],'paymentRedirect/{redirectUrlMark}',['as'=>'purchase.paymentRedirect','uses'=>'CallbackController@paymentRedirect']);
    //充值
    Route::post('recharge',['as'=>'purchase.recharge','uses'=>'PurchaseController@recharge']);
    //提现
    Route::post('withdraw',['as'=>'purchase.withdraw','uses'=>'PurchaseController@withdraw']);

});

//订单相关
Route::group(['prefix' => 'trade', 'namespace' => 'Trade','middleware'=>'checkToken'], function () {
    //交易记录
    Route::get('/dpProd/boughtList',['as'=>'trade.dpProd.boughtList','uses'=>'DPProdController@boughtList']);

    //用户余额明细
    Route::get('/account/balanceFlow',['as'=>'trade.account.balanceFlow','uses'=>'AccountController@balanceFlow']);
    Route::get('/account/incomeFlow',['as'=>'trade.account.incomeFlow','uses'=>'AccountController@incomeFlow']);
    //用户按月收益总额
    Route::get('/account/incomeForMonth',['as'=>'trade.account.incomeForMonth','uses'=>'AccountController@incomeForMonth']);
    //用户定期资产持有
    Route::get('account/regularProdSummary',['as'=>'trade.account.regularProdSummary','uses'=>'AccountController@regularProdSummary']);
    Route::get('account/regularProdDetail/{tradeId}',['as'=>'trade.account.regularProdDetail','uses'=>'AccountController@regularProdDetail']);
    //用户众筹资产持有
    Route::get('account/CFProdSummary',['as'=>'trade.account.CFProdSummary','uses'=>'AccountController@CFProdSummary']);
    Route::get('account/CFProdDetail/{tradeId}',['as'=>'trade.account.CFProdDetail','uses'=>'AccountController@CFProdDetail']);
    Route::get('account/CFProdDetail/{tradeId}/availFlow',['as'=>'trade.account.CFProdAvailFlow','uses'=>'AccountController@CFProdAvailFlow']);
    //用户活期收益明细
    Route::get('/dpProd/feeFlow',['as'=>'trade.dpProd.feeFlow','uses'=>'DPProdController@feeFlow']);
    Route::get('/dpProd/availsForMonth',['as'=>'trade.dpProd.availsForMonth','uses'=>'DPProdController@availsForMonth']);
    //用户定期自动续标状态修改
    Route::post('/account/{tradeId}/modify',['as'=>'trade.user.modify','uses'=>'AccountController@userTradeModify']);
});


//Wechat
Route::any('wechat',['as'=>'wechat','uses'=>'Wechat\WechatController@serve']);
Route::any('wechatJS',['as'=>'wechatJS','uses'=>'Wechat\WechatController@wechatJS']);
Route::group(['prefix' => 'wechat', 'namespace' => 'Wechat','middleware'=>'wechat.oauth'], function () {
    Route::get('/login',['as'=>'wechat.login','uses'=>'WechatController@login']);

});

//test
//Route::get('/testRmq',['as'=>'system.testRmq','uses'=>'SystemController@testRmq']);
