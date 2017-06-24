<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\region\controllers;

class Index extends \app\http\base\controllers\Frontend
{
	public function __construct()
	{
		parent::__construct();
		l(require LANG_PATH . c('shop.lang') . '/other.php');
	}

	public function actionIndex()
	{
		$type = i('get.type', 0, 'intval');
		$parent = i('get.parent', 0, 'intval');
		$user_id = i('get.user_id', 0, 'intval');
		$regions = get_regions($type, $parent);
		if (($type == 2) && !empty($regions)) {
			foreach ($regions as $k => $v) {
				$regions[$k]['district'] = get_regions(3, $v['region_id']);
			}
		}

		$arr['regions'] = $regions;
		$arr['type'] = $type;
		$arr['user_id'] = $user_id;

		if ($user_id) {
			$user_address = get_user_address_region($user_id);
			$user_address = explode(',', $user_address['region_address']);

			if (in_array($parent, $user_address)) {
				$arr['isRegion'] = 1;
			}
			else {
				$arr['isRegion'] = 88;
				$arr['message'] = l('input_dispatch_addr');
				$arr['province'] = $_COOKIE['province'];
				$arr['city'] = $_COOKIE['city'];
			}
		}

		if (empty($arr['regions'])) {
			$arr['empty_type'] = 1;
		}

		echo json_encode($arr);
	}

	public function actionSelectRegionChild()
	{
		if (IS_AJAX) {
			$result = array('error' => 0, 'message' => '', 'content' => '', 'ra_id' => '', 'region_id' => '');
			$type = i('get.type', 0, 'intval');
			$parent = i('get.parent', 0, 'intval');
			$ra_id = i('get.raId', 0, 'intval');
			$regions = get_regions(2, $parent);
			if (($type == 2) && !empty($regions)) {
				foreach ($regions as $k => $v) {
					$regions[$k]['district'] = get_regions(3, $v['region_id']);
				}
			}

			if ($type == 0) {
				if (empty($regions)) {
					cookie('province', $parent);
				}
			}
			else if ($type == 2) {
				cookie('type_province', $parent);
			}

			if (empty($regions)) {
				$result['regions'] = 1;
				cookie('province', $_COOKIE['type_province']);
				cookie('city', $_COOKIE['type_city']);
			}

			cookie('ra_id', $ra_id);
			$result['type'] = $type;
			$result['ra_id'] = $ra_id;
			$result['regions'] = $regions;
			exit(json_encode($result));
		}
	}

	public function actionSelectDistrictList()
	{
		if (IS_AJAX) {
			$result = array('error' => 0, 'message' => '', 'content' => '', 'ra_id' => '', 'region_id' => '');
			$region_id = i('get.region_id', 0, 'intval');
			$type = i('get.type', 0, 'intval');
			$where = 'region_id = \'' . $region_id . '\'';
			$date = array('parent_id');
			$parent_id = get_table_date('region', $where, $date, 2);

			if ($type == 0) {
				cookie('province', $parent_id);
				cookie('city', $region_id);
				$where = 'parent_id = \'' . $region_id . '\' order by region_id asc limit 0, 1';
				$date = array('region_id', 'region_name');
				$district_list = get_table_date('region', $where, $date, 1);

				if (0 < count($district_list)) {
					cookie('district', $district_list[0]['region_id']);
				}
				else {
					cookie('district', 0);
				}

				cookie('type_province', 0);
				cookie('type_city', 0);
				cookie('type_district', 0);
			}
			else {
				$where = 'region_id = \'' . $parent_id . '\'';
				$date = array('parent_id');
				$province = get_table_date('region', $where, $date, 2);
				cookie('type_province', $province);
				cookie('type_city', $parent_id);
				cookie('type_district', $region_id);
			}

			exit(json_encode($result));
		}
	}

	public function actionAddress()
	{
		$pid = input('parent_id', 1, 'intval');
		$list = $this->model->table('region')->field('region_id,region_name')->where(array('parent_id' => $pid))->cache(true, 12 * 3600)->select();
		$res = array();

		foreach ($list as $key => $v) {
			$res[$key]['name'] = $v['region_name'];
			$res[$key]['id'] = $v['region_id'];
		}

		$addresslist = array('addressList' => $res);
		exit(json_encode($addresslist));
	}
}

?>
