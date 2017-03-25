<?php
/**
 * 电站相关
 */

namespace App\Http\Controllers\PowerStation;
use App\DataServer\Front\PowerStationApi;
use App\DataServer\PowerStation\PsApi;
use App\Http\Controllers\Controller;
use App\Exceptions\PowerStation\PowerStationOldApiException as oldEx;
use App\Exceptions\PowerStation\PowerStationNewApiException as newEx;
use Hamcrest\Type\IsNumeric;

class PowerStationController extends Controller
{
    /**
     * 根据id返回电站的发电数据
     * @return \Illuminate\Http\JsonResponse
     */
   public function getByIndexId($indexId){
   		    // 设置默认返回结果
		    $res = array(
		    	"psBaseInfo" => [],
		        "totalEnergy" => 0,
		        "irradiationData" => 0,
		        "dailyEnergy" => array(),
		        "monthlyEnergy" => array(),
		        "yearlyEnergy"  => array(),
		        "env"  => array(),
		        "weeklyEnergy"  => array(array(
		            "date" => date('Y-m-d', strtotime(date('Y-m-d') . ' -7 day')),
		            "energy" => 0
		            ), array(
		            "date" => date("Y-m-d"),
		            "energy" => 0
		            )),
	    		"instantTemperature" => "N/A",
	    		"weatherInfo" => array(
	    				"type" => 1,
	    				"hint" => "天气晴好，开足马力发电中。"
	    		)
		    );

   		// 从前台拉取基本数据
   		$psf = new PowerStationApi();
   		try{
   			$psBaseRes = $psf->getPowerStationBaseInfo($indexId);
   			if($psBaseRes['code']!="200") {
   				return makeFailedMsg(501,'获取电站基本信息失败[1]，请重试');
   			}
          	$res['psBaseInfo'] = $psBaseRes['result'];
          	// 如果没有电站id，直接返回默认值
          	if(empty($res['psBaseInfo']['psId'])) {
          		return makeSuccessMsg($res);
          	}
          	$irradiation_day = json_decode($res['psBaseInfo']['irradiationDay'],true);
          	// 拉取电站发电数据
          	$psEnergy = new PsApi();
          	$psEnergyRes = $psEnergy->getPsEnergyData($res['psBaseInfo']['psId']);
          	$_psEnergyData = $psEnergyRes['data'];

          	// 格式化数据
			$res['psEnergyData'] = [
				"irradiationData" => $irradiation_day[date("m")],
				"totalEnergy" => $_psEnergyData["total_energy"],
				"dailyEnergy" => $_psEnergyData["daily_energy"],
				"monthlyEnergy" => $_psEnergyData["monthly_energy"],
				"yearlyEnergy" => $_psEnergyData["yearly_energy"],
				"weeklyEnergy" => $_psEnergyData["weekly_energy"],
			];
			$res['weatherData'] = [
				"instantTemperature" => bdWeatherTemperature($_psEnergyData['realtime_temperature']),
				"weatherInfo" => bdWeatherToInfo($_psEnergyData['weather']),
			];
			$res['irradiationData'] = $irradiation_day[date("m")];

			// 环保数据转化
			$res['env'] = array();
			$res['env']['coalReduction'] = $_psEnergyData["total_energy"] * 0.5;
			$res['env']['co2Reduction'] = $_psEnergyData["total_energy"] * 2.6;
			$res['env']['so2Reduction'] = $_psEnergyData["total_energy"] * 0.0087;
			$res['env']['noxReduction'] = $_psEnergyData["total_energy"] * 0.0074;

			$res['env']['tvOperation'] = $_psEnergyData["total_energy"] / (0.2*24);
			$res['env']['autoMobileOperation'] = $_psEnergyData["total_energy"] / 5;
			$res['env']['computerOperation'] = $_psEnergyData["total_energy"] / (0.4*24);
			$res['env']['airconditionOperation'] = $_psEnergyData["total_energy"] / (0.8*24);

      	 }catch(BaseException $e){
           	\Log::critical('PowerStation:'.myException($e));
           	return makeFailedMsg(501,'获取电站基本信息失败，请重试');
       	}catch(oldEx $e){
           	\Log::critical('PowerStation:'.myException($e));
           	return makeFailedMsg(501,'获取电站发电数据[1]失败，请重试');
       	}catch(newEx $e){
           	\Log::critical('PowerStation:'.myException($e));
           	return makeFailedMsg(501,'获取电站发电数据[2]失败，请重试');
       	}
        return makeSuccessMsg($res);
    }

    /**
     * 获取电站信息列表
     * @return Json
     */
     public function getPsList()
     {
         $power = new PowerStationApi();
         $pslist = $power->getPowerStationList();
         $data = $pslist['result'];
         if ($pslist['message'] == 'success') {
            //  $ps_worker_data = $power->getPowerStationInfo('1,2');
            //  debug($ps_worker);
             return makeSuccessMsg($data);
         } else {
             return makeFailedMsg(500, '获取电站信息列表失败，请重试');
         }
     }

}
