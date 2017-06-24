<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\chat\controllers;

class Index extends \app\http\base\controllers\Frontend
{
	private $user_id = 0;
	private $userinfo = array();

	public function __construct()
	{
		parent::__construct();
		$this->load_helper('order');
		$this->user_id = $_SESSION['user_id'];
		$this->userinfo = user_info($this->user_id);
		$this->userinfo['user_picture'] = isset($_SESSION['avatar']) ? $_SESSION['avatar'] : $this->userinfo['user_picture'];

		if (!$this->user_id) {
			$this->redirect('user/login/index');
		}
	}

	public function actionIndex()
	{
		$goods_id = i('goods_id', 0, 'intval');
		$ru_id = i('ru_id', 0, 'intval');

		if (!empty($goods_id)) {
			$data = get_goods_info($goods_id);
			$config = $this->model->table('seller_shopinfo')->field('kf_appkey, kf_secretkey, kf_touid, kf_logo, kf_welcomeMsg')->where(array('ru_id' => $data['user_id']))->find();
		}

		if (!empty($ru_id)) {
			$config = $this->model->table('seller_shopinfo')->field('kf_appkey, kf_secretkey, kf_touid, kf_logo, kf_welcomeMsg')->where(array('ru_id' => $ru_id))->find();
		}

		$this->createImUser($config['kf_appkey'], $config['kf_secretkey']);
		$data['avatar'] = get_image_path($this->userinfo['user_picture']);
		$data['appkey'] = $config['kf_appkey'];
		$data['secretkey'] = $config['kf_secretkey'];
		$data['touid'] = $config['kf_touid'];
		$data['uid'] = $this->userinfo['user_id'];
		$data['credential'] = md5($this->userinfo['user_id']);
		$this->assign('data', $data);
		$this->display();
	}

	private function createImUser($appkey = '', $secretkey = '')
	{
		require dirname(ROOT_PATH) . '/plugins/aliyunim/TopSdk.php';
		$c = new \TopClient();
		$c->appkey = $appkey;
		$c->secretKey = $secretkey;
		$req = new \OpenimUsersAddRequest();
		$userinfos = new \Userinfos();
		$userinfos->nick = $this->userinfo['user_name'];
		$userinfos->userid = $this->userinfo['user_id'];
		$userinfos->password = md5($this->userinfo['user_id']);
		$req->setUserinfos(json_encode($userinfos));
		$resp = $c->execute($req);
	}
}

?>
