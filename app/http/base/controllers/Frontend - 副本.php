<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\base\controllers;

abstract class Frontend extends Foundation
{
	public $province_id = 0;
	public $city_id = 0;
	public $district_id = 0;
	public $caching = false;
	public $custom = '';
	public $customs = '';

	public function __construct()
	{
		parent::__construct();
		$this->start();
		$this->geocoding();
		$this->ecjia_login();
	}

	private function geocoding()
	{
		$current_city_id = cookie('lbs_city');
		$current_city_info = get_region_name(intval($current_city_id));

		if (empty($current_city_info)) {
			$res_city_name = $this->getApiCityName();
			$sql = 'select `region_id`, `region_name`, `parent_id` from ' . $GLOBALS['ecs']->table('region') . ' where region_type = 2 and region_name = \'' . $res_city_name . '\'';
			$current_city_info = $GLOBALS['db']->getRow($sql);

			if (empty($current_city_info)) {
				$current_city_info = get_region_name(c('shop.shop_city'));
			}

			setrawcookie('lbs_city_name', rtrim($current_city_info['region_name'], '市'));
			cookie('lbs_city', $current_city_info['region_id']);
			cookie('province', $current_city_info['parent_id']);
			cookie('city', $current_city_info['region_id']);
			cookie('district', 0);
		}

		$this->assign('current_city', $current_city_info);
	}

	private function ecjia_login()
	{
		if (isset($_GET['origin']) && ($_GET['origin'] == 'app')) {
			$openid = i('get.openid');
			$token = i('get.token');
			$sql = 'select cu.token,u.user_name from {pre}connect_user as cu LEFT JOIN {pre}users as u on cu.user_id = u.user_id where open_id = \'' . $openid . '\' ';
			$user = $this->db->getRow($sql);

			if ($token == $user['token']) {
				$GLOBALS['user']->set_session($user['user_name']);
				$GLOBALS['user']->set_cookie($user['user_name']);
			}
		}
	}

	private function start()
	{
		$this->init();
		$this->init_user();
		$this->init_gzip();		
		//start by 瑾梦网络
		$this->init_wxpay_jspay();	
		//end by 瑾梦网络		
		$this->init_assign();
		$this->init_area();
		$ru_id = get_ru_id();

		if (0 < $ru_id) {
			$wechat = '\\app\\http\\wechat\\controllers\\Index';
			$wechat::snsapi_base($ru_id);
		}
		else {
			$this->init_oauth();
		}

		\Think\Hook::listen('frontend_init');
		$this->assign('lang', array_change_key_case(l()));
		$this->assign('charset', CHARSET);
	}

	private function init()
	{
		$helper_list = array('time', 'base', 'common', 'main', 'insert', 'goods');
		$this->load_helper($helper_list);
		$this->ecs = $GLOBALS['ecs'] = new \app\classes\Ecshop(config('DB_NAME'), config('DB_PREFIX'));
		$this->db = $GLOBALS['db'] = new \app\classes\Mysql();
		$this->err = $GLOBALS['err'] = new \app\classes\Error('message');
		$GLOBALS['_CFG'] = load_ecsconfig();
		$GLOBALS['_CFG']['template'] = 'default';
		$GLOBALS['_CFG']['rewrite'] = 0;
		config('shop', $GLOBALS['_CFG']);
		$app_config = MODULE_BASE_PATH . 'config/web.php';
		config('app', file_exists($app_config) ? require $app_config : array());
		l(require LANG_PATH . config('shop.lang') . '/common.php');
		$app_lang = MODULE_BASE_PATH . 'language/' . config('shop.lang') . '/' . strtolower(MODULE_NAME) . '.php';
		l(file_exists($app_lang) ? require $app_lang : array());
		$app_lang = MODULE_BASE_PATH . 'language/' . config('shop.lang') . '/' . strtolower(CONTROLLER_NAME) . '.php';
		l(file_exists($app_lang) ? require $app_lang : array());
		$this->load_helper('function', 'app');

		if (config('shop_closed') == 1) {
			exit('<p>' . l('shop_closed') . '</p><p>' . config('close_comment') . '</p>');
		}

		if (!defined('INIT_NO_USERS')) {
			session(array('name' => 'ECS_ID'));
			session('[start]');
			define('SESS_ID', real_cart_mac_ip());
		}

		$schelper_list = array('scecmoban', 'scfunction');
		$this->load_helper($schelper_list);
	}

	private function init_user()
	{
		if (!defined('INIT_NO_USERS')) {
			$GLOBALS['user'] = $this->users = init_users();

			if (!isset($_SESSION['user_id'])) {
				$site_name = (isset($_GET['from']) ? htmlspecialchars($_GET['from']) : addslashes(l('self_site')));
				$from_ad = (!empty($_GET['ad_id']) ? intval($_GET['ad_id']) : 0);
				$wechat_from = array('timeline', 'groupmessage', 'singlemessage');

				if (in_array($site_name, $wechat_from)) {
					$site_name = addslashes(l('self_site'));
				}

				$_SESSION['from_ad'] = $from_ad;
				$_SESSION['referer'] = stripslashes($site_name);
				unset($site_name);

				if (!defined('INGORE_VISIT_STATS')) {
					visit_stats();
				}
			}

			if (empty($_SESSION['user_id'])) {
				if ($this->users->get_cookie()) {
					if (0 < $_SESSION['user_id']) {
						update_user_info();
					}
				}
				else {
					$_SESSION['user_id'] = 0;
					$_SESSION['user_name'] = '';
					$_SESSION['email'] = '';
					$_SESSION['user_rank'] = 0;
					$_SESSION['discount'] = 1;

					if (!isset($_SESSION['login_fail'])) {
						$_SESSION['login_fail'] = 0;
					}
				}
			}

			if (isset($_GET['u'])) {
				set_affiliate();
			}

			if (isset($_GET['ru_id'])) {
				set_ru_id();
			}

			if (!empty($_COOKIE['ECS']['user_id']) && !empty($_COOKIE['ECS']['password'])) {
				$condition = array('user_id' => intval($_COOKIE['ECS']['user_id']), 'password' => $_COOKIE['ECS']['password']);
				$row = $this->db->table('users')->where($condition)->find();

				if (!$row) {
					$time = time() - 3600;
					cookie('ECS[user_id]', '');
					cookie('ECS[password]', '');
				}
				else {
					$_SESSION['user_id'] = $row['user_id'];
					$_SESSION['user_name'] = $row['user_name'];
					update_user_info();
				}
			}

			if (isset($this->tpl)) {
				$this->tpl->assign('ecs_session', $_SESSION);
			}
		}
	}

	private function init_assign()
	{
		$search_keywords = config('shop.search_keywords');
		$hot_keywords = array();

		if ($search_keywords) {
			$hot_keywords = explode(',', $search_keywords);
		}

		$this->assign('hot_keywords', $hot_keywords);
		$history = '';

		if (!empty($_COOKIE['ECS']['keywords'])) {
			$history = explode(',', $_COOKIE['ECS']['keywords']);
			$history = array_unique($history);
		}

		$this->assign('history_keywords', $history);
		$is_wechat = (is_wechat_browser() && is_dir(APP_WECHAT_PATH) ? 1 : 0);
		$this->assign('is_wechat', $is_wechat);
	}

	public function init_area()
	{
		$city_district_list = get_ishas_area($_COOKIE['type_city']);

		if (!$city_district_list) {
			cookie('type_district', 0);
			$_COOKIE['type_district'] = 0;
		}

		$provinceT_list = get_ishas_area($_COOKIE['type_province']);
		$cityT_list = get_ishas_area($_COOKIE['type_city'], 1);
		$districtT_list = get_ishas_area($_COOKIE['type_district'], 1);
		if ((0 < $_COOKIE['type_province']) && $provinceT_list) {
			if ($city_district_list) {
				if (($cityT_list['parent_id'] == $_COOKIE['type_province']) && ($_COOKIE['type_city'] == $districtT_list['parent_id'])) {
					$_COOKIE['province'] = $_COOKIE['type_province'];

					if (0 < $_COOKIE['type_city']) {
						$_COOKIE['city'] = $_COOKIE['type_city'];
					}

					if (0 < $_COOKIE['type_district']) {
						$_COOKIE['district'] = $_COOKIE['type_district'];
					}
				}
			}
			else if ($cityT_list['parent_id'] == $_COOKIE['type_province']) {
				$_COOKIE['province'] = $_COOKIE['type_province'];

				if (0 < $_COOKIE['type_city']) {
					$_COOKIE['city'] = $_COOKIE['type_city'];
				}

				if (0 < $_COOKIE['type_district']) {
					$_COOKIE['district'] = $_COOKIE['type_district'];
				}
			}
		}

		$this->province_id = isset($_COOKIE['province']) ? $_COOKIE['province'] : 0;
		$this->city_id = isset($_COOKIE['city']) ? $_COOKIE['city'] : 0;
		$this->district_id = isset($_COOKIE['district']) ? $_COOKIE['district'] : 0;
		$warehouse_date = array('region_id', 'region_name');
		$warehouse_where = 'regionId = \'' . $this->province_id . '\'';
		$warehouse_province = get_table_date('region_warehouse', $warehouse_where, $warehouse_date);
		$sellerInfo = get_seller_info_area();

		if (!$warehouse_province) {
			$this->province_id = $sellerInfo['province'];
			$this->city_id = $sellerInfo['city'];
			$this->district_id = $sellerInfo['district'];
		}

		cookie('province', $this->province_id);
		cookie('city', $this->city_id);
		cookie('district', $this->district_id);
	}

	private function init_gzip()
	{
		if (!defined('INIT_NO_SMARTY') && gzip_enabled()) {
			ob_start('ob_gzhandler');
		}
		else {
			ob_start();
		}
	}

	private function init_oauth()
	{
		if (is_wechat_browser() && empty($_SESSION['openid']) && (MODULE_NAME != 'oauth')) {
			$sql = 'SELECT `auth_config` FROM' . $GLOBALS['ecs']->table('touch_auth') . ' WHERE `type` = \'wechat\' AND `status` = 1';
			$auth_config = $GLOBALS['db']->getOne($sql);

			if ($auth_config) {
				$res = unserialize($auth_config);
				$config = array();

				foreach ($res as $key => $value) {
					$config[$value['name']] = $value['value'];
				}

				if ($config['oauth_status'] == 1) {
					$back_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
					$this->redirect('oauth/index/index', array('type' => 'wechat', 'back_url' => urlencode($back_url)));
				}
			}
		}
	}


	private function init_wxpay_jspay(){
		if( ! preg_match('/micromessenger/', strtolower($_SERVER['HTTP_USER_AGENT']))){
			return false;
		}
		if ( !empty($_POST) ){
			return false;
		}

		if ( empty($_SESSION['wxpay_jspay_openid'])  ){
			if(isset($_COOKIE["wxpay_jspay_openid"]) && !empty($_COOKIE["wxpay_jspay_openid"]))
			{
				//$_SESSION["wxpay_jspay_openid"]= $_COOKIE["wxpay_jspay_openid"];
				//return true;
			}
			//获取openid
			include_once(BASE_PATH.'helpers/payment_helper.php');
			$plugin_file = ADDONS_PATH.'payment/wxpay.php';
			require_once( $plugin_file );
			$payment  = get_payment('wxpay');
			if( empty($payment)  && $payment['enabled']  != 1 ){
				return false;
			}
			$wxpay_jspay = new \wxpay();
			
			$data = $wxpay_jspay->getOpenid( $payment );

			if (  $data !== false ){
				
				$_SESSION['wxpay_jspay_openid'] = $data;
			}
		}
	}

}

?>
