<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\base\controllers;

abstract class Backend extends Foundation
{
	public function __construct()
	{
		parent::__construct();
		$helper_list = array('time', 'base', 'common', 'main', 'insert', 'goods');
		$this->load_helper($helper_list);
		$this->ecs = $GLOBALS['ecs'] = new \app\classes\Ecshop(config('DB_NAME'), config('DB_PREFIX'));
		$this->db = $GLOBALS['db'] = new \app\classes\Mysql();

		if (!defined('INIT_NO_USERS')) {
			session(array('name' => 'ECSCP_ID'));
			session('[start]');
			$condition['sesskey'] = substr(cookie('ECSCP_ID'), 0, 32);
			$session = $this->model->table('sessions')->where($condition)->find();
			$_SESSION = unserialize($session['data']);
			$_SESSION['user_id'] = $session['userid'];
			$_SESSION['admin_id'] = $session['adminid'];
			$_SESSION['user_name'] = $session['user_name'];
			$_SESSION['user_rank'] = $session['user_rank'];
			$_SESSION['discount'] = $session['discount'];
			$_SESSION['email'] = $session['email'];
			define('SESS_ID', substr($session['sesskey'], 0, 32));

			if (empty($_SESSION['admin_id'])) {
				session(array('name' => 'ECSCP_SELLER_ID'));
				session('[start]');
				$condition['sesskey'] = substr(cookie('ECSCP_SELLER_ID'), 0, 32);
				$session_seller = $this->model->table('sessions_data')->where($condition)->find();
				$_SESSION = unserialize($session_seller['data']);
				$_SESSION['user_id'] = 0;
				$_SESSION['admin_id'] = 0;
				$_SESSION['user_name'] = 0;
				$_SESSION['user_rank'] = 0;
				$_SESSION['discount'] = 0;
				$_SESSION['email'] = 0;
			}
		}

		$GLOBALS['_CFG'] = load_ecsconfig();
		$GLOBALS['_CFG']['template'] = 'default';
		c('shop', $GLOBALS['_CFG']);
		if (isset($_SESSION['admin_id']) && (0 < $_SESSION['admin_id'])) {
			$this->checkLogin();
		}
		else {
			if (isset($_SESSION['seller_id']) && (0 < $_SESSION['seller_id'])) {
				$this->checkSellerLogin();
			}
		}

		l(require LANG_PATH . c('shop.lang') . '/common.php');
	}

	public function message($msg, $url = NULL, $type = '1', $seller = false, $waitSecond = 3)
	{
		if ($url == NULL) {
			$url = 'javascript:history.back();';
		}

		if ($type == '2') {
			$title = l('error_information');
		}
		else {
			$title = l('prompt_information');
		}

		$data['title'] = $title;
		$data['message'] = $msg;
		$data['type'] = $type;
		$data['url'] = $url;
		$data['second'] = $waitSecond;
		$this->assign('data', $data);
		$tpl = ($seller == true ? 'admin/seller_message' : 'admin/message');
		$this->display($tpl);
		exit();
	}

	private function checkLogin()
	{
		$condition['user_id'] = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 0;
		$action_list = $this->model->table('admin_user')->where($condition)->getField('action_list');

		if (empty($action_list)) {
			redirect('../admin/index.php?act=main');
		}
	}

	private function checkSellerLogin()
	{
		$condition['user_id'] = isset($_SESSION['seller_id']) ? intval($_SESSION['seller_id']) : 0;
		$action_list = $this->model->table('admin_user')->where($condition)->getField('action_list');

		if (empty($action_list)) {
			redirect('../seller/privilege.php?act=login');
		}
	}

	public function admin_priv($priv_str)
	{
		$condition['user_id'] = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 0;
		$action_list = $this->model->table('admin_user')->where($condition)->getField('action_list');

		if ($action_list == 'all') {
			return true;
		}

		if (strpos(',' . $action_list . ',', ',' . $priv_str . ',') === false) {
			redirect('../admin/index.php?act=main');
		}
		else {
			return true;
		}
	}

	public function seller_admin_priv($priv_str)
	{
		$condition['user_id'] = isset($_SESSION['seller_id']) ? intval($_SESSION['seller_id']) : 0;
		$action_list = $this->model->table('admin_user')->where($condition)->getField('action_list');

		if ($action_list == 'all') {
			return true;
		}

		if (strpos(',' . $action_list . ',', ',' . $priv_str . ',') === false) {
			return true;
		}
		else {
			return true;
		}
	}
}

?>
