<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\user\controllers;

class Region extends \app\http\base\controllers\Frontend
{
	public function __construct()
	{
		parent::__construct();
	}

	public function actionIndex()
	{
		if (IS_AJAX) {
			$province = i('province');
			$city = i('city');
			$area = i('area');
			$result = array();

			if (strpos($province, '市') == true) {
				$province = str_replace('市', '', $province);
			}

			if (strpos($province, '省') == true) {
				$province = str_replace('省', '', $province);
			}

			$province_condition = array('region_type' => 1, 'region_name' => $province);
			$province_id = $this->model->table('region')->field('region_id')->where($province_condition)->find();
			$result['province_id'] = $province_id['region_id'];

			if (strpos($city, '市') == true) {
				$city = str_replace('市', '', $city);
			}

			$city_condition = array('region_type' => 2, 'region_name' => $city);
			$city_id = $this->model->table('region')->field('region_id')->where($city_condition)->find();
			$result['city_id'] = $city_id['region_id'];
			$area_condition = array('region_type' => 3, 'region_name' => $area);
			$area_id = $this->model->table('region')->field('region_id')->where($area_condition)->find();
			$result['area_id'] = $area_id['region_id'];
			exit(json_encode($result));
		}
	}
}

?>
