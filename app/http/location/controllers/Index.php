<?php
//zend by QQ:2172298892 瑾梦网络
namespace app\http\location\controllers;

class Index extends \app\http\base\controllers\Frontend
{
	public function actionIndex()
	{
		if (IS_POST) {
			$city = array('region_id' => i('city_id', 0), 'region_name' => i('city_name', ''));
			$this->setRecentCity($city);
			$sql = 'select `parent_id` from ' . $GLOBALS['ecs']->table('region') . ' where region_type = 2 and region_id = \'' . $city['region_id'] . '\'';
			$city['parent_id'] = $GLOBALS['db']->getOne($sql);
			setrawcookie('lbs_city_name', rtrim($city['region_name'], '市'));
			cookie('lbs_city', $city['region_id']);
			cookie('province', $city['parent_id']);
			cookie('city', $city['region_id']);
			cookie('district', 0);
			cookie('type_province', 0);
			cookie('type_city', 0);
			cookie('type_district', 0);
			return NULL;
		}

		$keywords = input('keywords', '', 'htmlspecialchars');
		$this->assign('recent_city', $this->getRecentCity());
		$this->assign('city_list', $this->getCity($keywords));
		$this->assign('page_title', '城市选择');
		$this->display();
	}

	public function actionInfo()
	{
		$current_city_id = cookie('lbs_city');
		$current_city_info = get_region_name(intval($current_city_id));
		if (!empty($current_city_info) && !isset($_GET['force'])) {
			exit(json_encode($current_city_info));
		}

		$city_name = i('city_name');
		$city_name = rtrim($city_name, '市');
		$city_group = $this->getCity($city_name);

		if (is_array($city_group)) {
			foreach ($city_group as $key => $city_list) {
				$city_list = end($city_list);
				setrawcookie('lbs_city_name', rtrim($city_list['region_name'], '市'));
				cookie('lbs_city', $city_list['region_id']);
				exit(json_encode($city_list));
			}
		}
	}

	private function getRecentCity()
	{
		return isset($_SESSION['recent_city_history']) ? $_SESSION['recent_city_history'] : array();
	}

	private function setRecentCity($data = array())
	{
		$_SESSION['recent_city_history'][$data['region_id']] = $data['region_name'];
	}

	private function getCity($keywords = '')
	{
		$data = array();
		$cacheFile = dirname(ROOT_PATH) . '/data/sc_file/pin_regions.php';

		if (file_exists_case($cacheFile)) {
			require $cacheFile;
			ksort($data);
		}

		if (!empty($keywords)) {
			foreach ($data as $key => $val) {
				foreach ($val as $k => $vo) {
					if (strpos($vo['region_name'], $keywords) === false) {
						unset($data[$key][$k]);
					}
				}

				if (empty($data[$key])) {
					unset($data[$key]);
				}
			}
		}

		return $data;
	}
}

?>
