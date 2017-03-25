<?php

namespace App\DataServer\Front;

use App\Exceptions\BaseApiException;
use App\Exceptions\FrontApi\CMSApiException;

class PowerStationApi extends FrontApi
{
	//api uri list
	const PS_BASE_INFO = 'fe_agent/ps';   //电站基本信息
	const PS_LIST_INFO = 'fe_agent/dynamic/pss';   //获取sys_config 中 weeks_power_station 对应电站id的所有电站基本信息
	const PS_WORKER_DATA = 'finance/psdata';   //电站发电信息

	/**
	 * 获取电站基本信息
	 * @param string $psIndexId 电站前台编号
	 * @return mixed
	 * @throws BaseException
	 */
	function getPowerStationBaseInfo($psIndexId)
	{
		try{
			return $this->method(self::PS_BASE_INFO."/".$psIndexId)->get();
		}catch(BaseApiException $e){
			throw new BaseApiException('获取电站基本信息失败:'.$e->getMessage());
		}
	}

	/**
	 * 获取电站列表信息
	 * @return mixed
	 * @throws BaseApiException
	 */
	public function getPowerStationList()
	{
		try{
			return $this->method(self::PS_LIST_INFO)->get();
		}catch(BaseApiException $e){
			throw new BaseApiException('获取电站列表信息失败:'.$e->getMessage());
		}
	}

	/**
	 * 获取电站发电信息
	 * @param  String  $id 电站ID，多个用逗号隔开
	 * @return Array
	 * @throws BaseApiException
	 */
	public function getPowerStationInfo($id)
	{
		try{
			return $this->method(self::PS_WORKER_DATA.'/'.$id)->get();
		}catch(BaseApiException $e){
			throw new BaseApiException('获取电站发电信息失败:'.$e->getMessage());
		}
	}

}
