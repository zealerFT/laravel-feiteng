<?php
namespace App\DataServer\PowerStation;

use App\DataServer\BaseApi;
use App\Exceptions\BaseApiException;
use App\Exceptions\PowerStation\PowerStationOldApiException as oldEx;
use App\Exceptions\PowerStation\PowerStationNewApiException as newEx;

class PsApi extends BaseApi
{
	//api uri list
	const OLD_PS_ENERGY_DATA = 'ps/v2/common/energyData';   // 旧接口获取电站发电数据
	const OLD_PS_WEATHER_INFO = '/ps/v2/common/weatherInfo';   // 旧接口获取电站天气数据
	
	const NEW_PS_ENERGY_DATA = 'finance/PVWorkInfo';   //新接口获取电站发电数据
	
	/**
	 * 对外获取电站发电数据
	 * @param string $psId 电站uuid
	 * @return mixed
	 * @throws BaseException
	 */
	function getPsEnergyData($psId){
		if(in_array($psId, config('sys-config.ps_old_ids'))){
			return $this->getPsOldEnergyData($psId);
		} else {
			return $this->getPsNewEnergyData($psId);
		}
	}
	
	/**
	 * 老接口获取电站发电数据
	 * @param string $psId 电站uuid
	 * @return mixed
	 * @throws BaseException
	 */
	function getPsOldEnergyData($psId){
		// 重置api
		$this->apiUrl=config('sys-config.ps_old_url');
		$param = [
			'psId'  =>$psId,
		];
		try{
			$energyRes = $this->method(self::OLD_PS_ENERGY_DATA)->post($param);
			$weatherRes = $this->method(self::OLD_PS_WEATHER_INFO)->post($param);
			if($energyRes['status_no']=='0000' && $weatherRes['status_no']=='0000') {
				$energyRes['data'] = $energyRes['data'] + $weatherRes['data'];
			} else {
				throw new oldEx('获取电站发电数据1失败:'.$res['status_msg']);
			}
		}catch(BaseApiException $e){
			throw new oldEx('获取电站发电数据1失败:'.$e->getMessage());
		}
		return $energyRes;
	}
	
	/**
	 * 新接口获取电站发电数据
	 * @param string $psId 电站uuid
	 * @return mixed
	 * @throws BaseException
	 */
	function getPsNewEnergyData($psId){
		// 重置api
		$this->apiUrl=config('sys-config.ps_new_url');
		$param = [
			'pv_plant_code'  =>$psId,
		];
		try{
			$res =  $this->method(self::NEW_PS_ENERGY_DATA)->get($param);
			if($res['code'] == '10000') {
				$week = [];
				if(!empty($res['result']['pv_week_energy_trend']["data"])) {
					foreach($res['result']['pv_week_energy_trend']["data"] as $v) {
						array_push($week, ["date"=> $v['date_key'] , "energy" => $v['total_energy']]);
					}
				}
				return [
					"data" => [
						"total_energy" => $res['result']['pv_total_energy'],
						"daily_energy" => ["date" =>date("Y-m-d"), "energy" => $res['result']['pv_daily_energy']],
						"monthly_energy" => ["date" =>date("Y-m-d"), "energy" => $res['result']['pv_month_energy']],
						"yearly_energy" => ["date" =>date("Y-m-d"), "energy" => null] ,
						"weekly_energy" => $week,
						"realtime_temperature" => $res['result']['realtime_temperature'],
						"weather" => $res['result']['weather'],
					],
				];
			} else {
				throw new newEx('获取电站发电数据2失败:'.$res['sub_msg']);
			}
		}catch(BaseApiException $e){
			throw new newEx('获取电站发电数据2失败:'.$e->getMessage());
		}
	
	}
}