<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\respond\controllers;

class Index extends \app\http\base\controllers\Frontend
{
	private $data = array();

	public function __construct()
	{
		parent::__construct();
		$this->data = array('code' => i('get.code'));

		if (isset($_GET['code'])) {
			unset($_GET['code']);
		}

		if (APP_DEBUG) {
			logresult('-------GET-------');
			logresult($_GET);
			logresult('-------POST-------');
			logresult($_POST);
			logresult('-------INPUT-------');
			$_POST['postStr'] = file_get_contents('php://input');
			logresult($_POST['postStr']);
		}
	}

	public function actionIndex()
	{
		$msg_type = 2;
		$payment = $this->getPayment();

		if ($payment === false) {
			$msg = l('pay_disabled');
		}
		else if ($payment->callback($this->data)) {
			$msg = l('pay_success');
			$msg_type = 0;
		}
		else {
			$msg = l('pay_fail');
			$msg_type = 1;
		}

		$this->assign('message', $msg);
		$this->assign('msg_type', $msg_type);
		$this->assign('shop_url', __URL__);
		$this->assign('page_title', lang('pay_status'));
		$this->display();
	}

	public function actionNotify()
	{
		$payment = $this->getPayment();

		if ($payment === false) {
			exit('plugin load fail');
		}

		$payment->notify($this->data);
	}

	private function getPayment()
	{
		$condition = array('pay_code' => $this->data['code'], 'enabled' => 1);
		$enabled = $this->db->table('payment')->where($condition)->count();
		$plugin = ADDONS_PATH . 'payment/' . $this->data['code'] . '.php';
		if (!is_file($plugin) || ($enabled == 0)) {
			return false;
		}

		require_cache($plugin);
		$payment = new $this->data['code']();
		return $payment;
	}
}

?>
