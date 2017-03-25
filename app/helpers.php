<?php
/**
 *帮助函数
 */

/**
 * @param Exception $e
 * @return array
 */
function makeExceptionMsg(\Exception $e)
{
    return [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ];
}

/**
 * 打包 成功返回信息
 * @param $data
 * @param int $responseCode
 * @param string $responseMsg
 * @return \Illuminate\Http\JsonResponse
 */
function makeSuccessMsg($data = [], $responseCode = 200, $responseMsg = 'success')
{
    if ((int)$responseCode == 0) {
        $reallyCode = 0;
    } else {
        $reallyCode = $responseCode;
    }
    return new \Illuminate\Http\JsonResponse([
        'code' => $reallyCode,
        'message' => $responseMsg,
        'result' => $data
    ], 200);
}

/**
 * 打包 失败返回信息
 * @param int $responseCode
 * @param string $responseMsg
 * @param array $data
 * @return \Illuminate\Http\JsonResponse
 */
function makeFailedMsg($responseCode = 500, $responseMsg = 'something wrong', $data = [])
{
    $responseData = [
        'code' => $responseCode,
        'message' => $responseMsg,
    ];
    if (count($data)) {
        $responseData['result'] = $data;
    }
    return new \Illuminate\Http\JsonResponse($responseData, 200);
}


/**
 * Captcha check by id
 * @param $value
 * @param $captchaId
 * @return bool
 */
function checkCaptchaById($value, $captchaId)
{
    $captcha = 'captcha' . $captchaId;
    if (!Cache::has($captcha)) {
        return false;
    }
    $key = Cache::get($captcha);
    Cache::forget($captcha);
    return $value == $key;
}

/**
 * 通过定期产品参数判断产品售卖状态
 * @param array $prod
 * @return int
 */
function getRegularProdSaleStatus(array $prod)
{
    //判断此商品状态：1.立即抢购 2.还有机会（overplus<=0  freeze>0） 3.抢光了 4.已起息  5.已结息 6.尾单秒杀
    $nowDay = date('Y-m-d');
    $checkDate = $prod['valuesDate'] >= $nowDay; //结束购买时间设置为起息日零点
    $checkFinish = $prod['finishDate'] >= $nowDay;//产品到期日，判断已结息
    $checkOverplus = (float)$prod['remainCredit'] <= 0;
    $valueType = isset($prod['valueType']) ? $prod['valueType'] : 1;// 起息类型 1.募满起息 2.指定起息
    $checkFreeze = $prod['frozenCredit'] > 0;//冻结份额
    $saleStatus = 1;
    if (!$checkFinish) {
        $saleStatus = 5;//已结息
    } elseif (!$checkDate) {
        $saleStatus = 4;
    } elseif ($checkDate && $checkOverplus && $checkFreeze) {
        $saleStatus = 2;
    } elseif (($checkDate && !$checkOverplus) || ($valueType == 1 && !$checkOverplus)) {//指定起息产品根据起息时间和销售情况判断，募满起息产品忽略起息时间这一条件
        //如果剩余额度小于10000则返回尾单提示
        $saleStatus = (int)$prod['remainCredit'] < 10000 ? 6 : 1;
    } elseif ($checkOverplus && !$checkFreeze) {
        $saleStatus = 3;
    }
    return $saleStatus;
}

/**
 * 获取众筹产品标签及相关信息
 * @param $cfPsProcess
 * @param $prod 产品信息 判断产品是否可以转让
 * @return array
 */
function getProdTarget($cfPsProcess, $prod = [])
{
    foreach ($cfPsProcess as $process) {
        $processTimeLine[$process['status']] = $process['date'];
    }
    $now = date('Y.m.d');
    // $prodStatusNo: 用于控制标签背景颜色
    // $prodStatusNow: 用于控制页面上电站众筹进度圆点位置
    // $prodStatusOrder: 排序规则：众筹中>未开始>建设中>已投产>已回购; “众筹中”状态按生成时间从近到远排序
    if (empty($processTimeLine[1]) || $now < $processTimeLine[1]) {
        $prodStatus = '未开始';
        $prodStatusNo = "0";
        $prodStatusNow = "0";
        $prodStatusOrder = 1;
        $prodStatusTransfer = '';
        $prodCanTransfer = 0;
    } else if (empty($processTimeLine[10])
        || ($now >= $processTimeLine[1] && $now < $processTimeLine[10])
    ) {
        $prodStatus = '众筹中';
        $prodStatusNo = "1";
        $prodStatusNow = "1";
        $prodStatusOrder = 0;
        $prodStatusTransfer = '并网180天后可转让';
        $prodCanTransfer = 0;
    } else if (empty($processTimeLine[11])
        || ($now >= $processTimeLine[10] && $now < $processTimeLine[11])
    ) {
        $prodStatus = '已募满';
        $prodStatusNo = "2";
        $prodStatusNow = "1";
        $prodStatusOrder = 2;
        $prodStatusTransfer = '并网180天后可转让';
        $prodCanTransfer = 0;
    } else if (empty($processTimeLine[3])
        || ($now >= $processTimeLine[11] && $now < $processTimeLine[3])
    ) {
        $prodStatus = '建设中';
        $prodStatusNo = "3";
        $prodStatusNow = "2";
        $prodStatusOrder = 2;
        $prodStatusTransfer = '并网180天后可转让';
        $prodCanTransfer = 0;
    } else if (empty($processTimeLine[4])
        || ($now >= $processTimeLine[3] && $now < $processTimeLine[4])
    ) {
        $prodStatus = '已并网';
        $prodStatusNo = "4";
        $prodStatusNow = "3";
        $prodStatusOrder = 3;

        if ($prod && $prod['hangingShare'] > 0) {
            // 当有转让的份额
            $prodStatusTransfer = '转让中'; // 当众筹项目部分转让时，显示“转让中”
            $prodCanTransfer = 2;
        } else if ($prod && $prod['profitTimes'] > 2) {
            $prodStatusTransfer = '可转让'; // 当众筹项目“已并网”,并三次结息后(既并网后两次结息)，显示“可转让”
            $prodCanTransfer = 1;
        } else {
            $buildFinishDate = array_filter($cfPsProcess, function ($item) {
                if ($item['status'] == 3) {
                    return $item;
                }
            });
            $buildFinishDate = array_shift($buildFinishDate)['date']; // 并网发电时间 （建设完成时间）
            $buildFinishDate = str_replace('.', '-', $buildFinishDate);

            $canTransferDate = date('Ymd', strtotime("$buildFinishDate + 180 days"));
            $diffDays = ceil((strtotime($canTransferDate) - time()) / 24 / 3600);
            if ($diffDays > 0) {
                $prodStatusTransfer = $diffDays . '天后可转让'; // 当众筹项目处于“已并网”，如中台项目建设完成时间+180-当前日期>0时，显示为“N天后可转让“；
                $prodCanTransfer = 0;
            } else {
                $prodStatusTransfer = '本次结息后可转让'; // d.当众筹项目“已并网”，如中台项目建设完成时间+180-当前日期<=0， “本次结息后可转让”；
                $prodCanTransfer = 0;
            }
        }
    } else {
        $prodStatus = '已退出';
        $prodStatusNo = "5";
        $prodStatusNow = "4";
        $prodStatusOrder = 4;
        $prodStatusTransfer = ''; // 当众筹项目处于“已退出”时，不显示其转让状态
        $prodCanTransfer = 0;
    }

    return [
        'prodStatus' => $prodStatus,
        'prodStatusNo' => $prodStatusNo,
        'prodStatusNow' => $prodStatusNow,
        'prodStatusOrder' => $prodStatusOrder,
        'prodStatusTransfer' => $prodStatusTransfer, // 是否可转让的状态
        'prodCanTransfer' => $prodCanTransfer// 是否可转让 0不可转让 1可转让 2 转让中
    ];
}

/**
 * 自定义异常信息
 * @param Exception $e
 * @return string
 */
function myException(Exception $e)
{
    return $e->getMessage() . '->' . $e->getFile() . ':' . $e->getLine();
}

/**
 * 转中文时间形式
 * @param $time 2015-2-5
 * @return string
 */

function transZHTime($time)
{
    $timestamp = strtotime($time);
    $y = date("Y", $timestamp);
    $m = date("m", $timestamp);
    $d = date("d", $timestamp);
    $zhTime = '';
    //年
    for ($i = 0; $i < strlen($y); $i++) {
        $zhTime .= strNum(substr($y, $i, 1));
    }
    $zhTime .= "年";
    //月
    $zhTime .= strNum($m, 'm') . "月";
    //日
    $zhTime .= strNum($d, 'm') . "日";

    return $zhTime;
}

/**
 * 将数字转成汉字对应的数
 * @param $str_1
 * @param $type
 * @return string
 */
function strNum($str_1, $type = 'y')
{
    $strNum = '';
    // 转年
    if ($type == 'y') {
        switch ($str_1) {
            case '1':
                $strNum = "一";
                break;
            case '2':
                $strNum = "二";
                break;
            case '3':
                $strNum = "三";
                break;
            case '4':
                $strNum = "四";
                break;
            case '5':
                $strNum = "五";
                break;
            case '6':
                $strNum = "六";
                break;
            case '7':
                $strNum = "七";
                break;
            case '8':
                $strNum = "八";
                break;
            case '9':
                $strNum = "九";
                break;
            case '0':
                $strNum = "零";
                break;
        }
    } else {//转月 日
        switch ($str_1) {
            case '01':
                $strNum = "一";
                break;
            case '02':
                $strNum = "二";
                break;
            case '03':
                $strNum = "三";
                break;
            case '04':
                $strNum = "四";
                break;
            case '05':
                $strNum = "五";
                break;
            case '06':
                $strNum = "六";
                break;
            case '07':
                $strNum = "七";
                break;
            case '08':
                $strNum = "八";
                break;
            case '09':
                $strNum = "九";
                break;
            case '10':
                $strNum = "十";
                break;
            case '11':
                $strNum = "十一";
                break;
            case '12':
                $strNum = "十二";
                break;
            case '13':
                $strNum = "十三";
                break;
            case '14':
                $strNum = "十四";
                break;
            case '15':
                $strNum = "十五";
                break;
            case '16':
                $strNum = "十六";
                break;
            case '17':
                $strNum = "十七";
                break;
            case '18':
                $strNum = "十八";
                break;
            case '19':
                $strNum = "十九";
                break;
            case '20':
                $strNum = "二十";
                break;
            case '21':
                $strNum = "二十一";
                break;
            case '22':
                $strNum = "二十二";
                break;
            case '23':
                $strNum = "二十三";
                break;
            case '24':
                $strNum = "二十四";
                break;
            case '25':
                $strNum = "二十五";
                break;
            case '26':
                $strNum = "二十六";
                break;
            case '27':
                $strNum = "二十七";
                break;
            case '28':
                $strNum = "二十八";
                break;
            case '29':
                $strNum = "二十九";
                break;
            case '30':
                $strNum = "三十";
                break;
            case '31':
                $strNum = "三十一";
                break;
        }
    }
    return $strNum;
}

/**
 * 数字金额转换成中文大写金额的函数
 * String Int $num 要转换的小写数字或小写字符串
 * return 大写字母
 * 小数位为两位
 * @param $num
 * @return string
 */
function transNumToZH($num)
{
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    //精确到分后面就不要了，所以只留两个小数位
    $num = round($num, 2);
    //将数字转化为整数
    $num = $num * 100;
    if (strlen($num) > 10) {
        return "金额太大，请检查";
    }
    $i = 0;
    $c = "";
    while (1) {
        if ($i == 0) {
            //获取最后一位数字
            $n = substr($num, strlen($num) - 1, 1);
        } else {
            $n = $num % 10;
        }
        //每次将最后一位数字转化为中文
        $p1 = substr($c1, 3 * $n, 3);
        $p2 = substr($c2, 3 * $i, 3);
        if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
            $c = $p1 . $p2 . $c;
        } else {
            $c = $p1 . $c;
        }
        $i = $i + 1;
        //去掉数字最后一位了
        $num = $num / 10;
        $num = (int)$num;
        //结束循环
        if ($num == 0) {
            break;
        }
    }
    $j = 0;
    $slen = strlen($c);
    while ($j < $slen) {
        //utf8一个汉字相当3个字符
        $m = substr($c, $j, 6);
        //处理数字中很多0的情况,每次循环去掉一个汉字“零”
        if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
            $left = substr($c, 0, $j);
            $right = substr($c, $j + 3);
            $c = $left . $right;
            $j = $j - 3;
            $slen = $slen - 3;
        }
        $j = $j + 3;
    }
    //这个是为了去掉类似23.0中最后一个“零”字
    if (substr($c, strlen($c) - 3, 3) == '零') {
        $c = substr($c, 0, strlen($c) - 3);
    }
    //将处理的汉字加上“整”
    if (empty($c)) {
        return "零元整";
    } else {
        return $c . "整";
    }
}

/**
 * 获取UUID
 * @return string
 */
function generateUuid()
{
    $charId = md5(uniqid(rand(), true));
    $hyphen = chr(45);// "-"
    $uuid = substr($charId, 0, 8) . $hyphen
        . substr($charId, 8, 4) . $hyphen
        . substr($charId, 12, 4) . $hyphen
        . substr($charId, 16, 4) . $hyphen
        . substr($charId, 20, 12);
    return $uuid;
}

/**
 * 转化登陆鉴权返回码
 * @param $errCode
 * @return string
 */
function transAuthMsg($errCode){
    switch($errCode){
        case 101 : $errMsg = '手机号未注册';break;
        case 102 : $errMsg = '手机号码已注册，立即投资';break;
        case 103 : $errMsg = '微信令牌异常';break;
        case 104 : $errMsg = '令牌不存在';break;
        case 105 : $errMsg = '手机号或密码错误';break;
        case 106 : $errMsg = '请输入正确的短信验证码';break;
        case 107 : $errMsg = '用户不存在';break;
        case 901 : $errMsg = '缺少必要参数';break;
        case 902 : $errMsg = '参数错误';break;
        case 500 : $errMsg = '请求处理失败';break;
        default : $errMsg = '未知错误';
    }
    return $errMsg;
}

// 格式化 温度
function bdWeatherTemperature($realtime_temperature) {
	if(is_numeric($realtime_temperature)) {
		return $realtime_temperature."℃";
	}
	$pos1 = strpos($realtime_temperature, "实时：");
	$pos2 = strpos($realtime_temperature, "℃");
	$sl = strlen("实时：");
	$s2 = strlen("℃");
	return $pos1 >=0 && $pos2 >= 0 ? substr($realtime_temperature, $pos1+$sl, $pos2-$pos1-$sl+$s2) : "N/A";
}

// 格式化 weather 数据
function bdWeatherToInfo($weather) {
	if( is_numeric(strpos($weather, "晴"))) {
		return array(
				"type" => 1,
				"hint" => "天气晴好，开足马力发电中。"
		);
	} else if(is_numeric(strpos($weather, "云"))) {
		return array(
				"type" => 2,
				"hint" => "天气晴好，开足马力发电中。"
		);
	} else if(is_numeric(strpos($weather, "阴"))) {
		return array(
				"type" => 3,
				"hint" => "天阴沉沉的，发电量降低了。"
		);
	} else if(is_numeric(strpos($weather, "雨"))) {
		if(is_numeric(strpos($weather, "阵"))) {
			return array(
					"type" => 4.1,
					"hint" => "下雨了，发电量降低了。"
			);
		}else {
			return array(
					"type" => 4.2,
					"hint" => "下雨了，发电量降低了。"
			);
		}
	} else if(is_numeric(strpos($weather, "雪"))) {
		return array(
				"type" => 5,
				"hint" => "下雪了，发电量降低了。"
		);
	} else if(is_numeric(strpos($weather, "雾")) || is_numeric(strpos($weather, "霾"))
	|| is_numeric(strpos($weather, "尘")) || is_numeric(strpos($weather, "沙"))) {
		return array(
				"type" => 6,
				"hint" => "大雾遮住了阳光，发电量降低了。"
		);
	} else {
		return array(
				"type" => 0,
				"hint" => "获取不到天气。"
		);
	}
}

// 交易状态的文字描述
function getTradeRepType($type)
{
	$msg_txt = array(
			// 1. 提现
			"WITHDRAW" => "提现 - 提现至银行卡",
			// 2. 充值
			"DEPOSIT" => "充值 - 充值到余额",
			// 3. 投资
			"PURCHASE_PRODUCT" => "投资 - 投资月月盈", // 包括活期产品（转入） 和 定期产品
			"PURCHASE_PRODUCT_CF" => "投资 - 众筹产品", //
			"PURCHASE_DP" => "投资 - 投资周周涨", // 包括活期产品（转入） 和 定期产品
			"NEWBIE" => "投资 - 投资新手产品",
			// 4. 收益
			"DP_DAILY_PROFIT" => "收益 - 周周涨收益",
			"CF_PRODUCT_REDEM" => "收益 - 众筹收益",  // 众筹收益
			// 5. 还本：用户投资本金转入账户余额
			"REDEMPTIONPORDOVER" => "还本 - 月月盈还本付息",  // 目前定期产品本息合在一起，稍后分开
			"NEWBIE_PRODUCT_REDEM" => "还本 - 新手产品还本付息", //  目前定期产品本息合在一起，稍后分开
			"DP_REDEMPTION" => "还本 - 周周涨转让", // 周周涨转让
			// 6. 奖励
			"DO_COUPON_DISCOUNT" => "奖励 - 返现券",
			"INVITE_TYPE" => "奖励 - 月结返现", // 中台定义，目前暂时不用该字段
			"CASHBACK_INVITE_SIGN_UP" => "奖励 - 邀请好友注册", // 中台定义
			"CASHBACK_INVITE_ORDER" => "奖励 - 好友投资返现", // 中台定义
			"WONMEN_DAY" => "奖励 - 活动奖励", // 中台定义
			"CASHBACK_INVITE_APRIL" => "奖励 - 活动奖励", // 中台定义
			"CASHBACK_IPHONE" => "奖励 - 活动奖励", // 送中秋iphone7活动
	);
	return $msg_txt[$type] ? $msg_txt[$type] : "红包";
}

/**
 * 传递数据以易于阅读的样式格式化后输出
 * @param int $data 数据
 * @param int $type
 * @return result
 */
function debug($data, $type = ''){
    // 定义样式
    $str='<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
    // 如果是boolean或者null直接显示文字；否则print
    if (is_bool($data)) {
        $show_data=$data ? 'true' : 'false';
    }elseif (is_null($data)) {
        $show_data='null';
    }else{
        $show_data=print_r($data,true);
        $str.=$show_data;
        $str.='</pre>';
        echo $str;
        exit;
    }
}
