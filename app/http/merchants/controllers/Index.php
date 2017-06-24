<?php
//zend by QQ:2172298892 瑾梦网络
namespace app\http\merchants\controllers;

class Index extends \app\http\base\controllers\Frontend
{
	public $user_id;

	public function __construct()
	{
		parent::__construct();
		$this->user_id = $_SESSION['user_id'];
		$this->actionchecklogin();
		l(require LANG_PATH . c('shop.lang') . '/merchants.php');
		$files = array('clips', 'transaction', 'main');
		$this->load_helper($files);
		$this->sid = 1;
	}

	public function actionIndex()
	{
		$shop = $this->model->table('merchants_shop_information')->where(array('user_id' => $this->user_id))->find();

		if ($shop) {
			ecs_header('Location: ' . url('merchants/index/audit'));
		}

		if (IS_POST) {
			if (i('agree') == 1) {
				$data['agreement'] = i('agree');
			}
			else {
				show_message('请同意用户协议', '', '', 'error');
			}

			$data['contactName'] = i('contactName');
			$data['contactPhone'] = i('contactPhone');
			$data['license_adress'] = i('license_adress');
			$data['company_located'] = i('province_region_id') . ',' . i('city_region_id') . ',' . i('district_region_id');

			if ($data['contactPhone']) {
				$preg = (preg_match('#^13[\\d]{9}$|^14[5,7]{1}\\d{8}$|^15[^4]{1}\\d{8}$|^17[0,6,7,8]{1}\\d{8}$|^18[\\d]{9}$#', $data['contactPhone']) ? true : false);

				if ($preg == FALSE) {
					show_message(l('mobile_not_null'));
				}
			}

			if (empty($data['contactName'])) {
				show_message(l('msg_shop_owner_notnull'));
			}

			$data['user_id'] = $this->user_id;

			if ($this->model->table('merchants_steps_fields')->data($data)->add()) {
				ecs_header('Location: ' . url('merchants/index/shop'));
			}
			else {
				show_message(l('add_error'));
			}
		}

		$this->assign('page_title', l('business_information'));
		$this->display();
	}

	public function actionShop()
	{
		if (IS_POST) {
			$data = i('');

			if (empty($data['rz_shopName'])) {
				show_message(l('msg_shop_name_notnull'));
			}

			if (empty($data['hopeLoginName'])) {
				show_message(l('msg_login_shop_name_notnull'));
			}

			$data['user_id'] = $this->user_id;

			if ($this->model->table('merchants_shop_information')->data($data)->add()) {
				ecs_header('Location: ' . url('merchants/index/audit'));
			}
			else {
				show_message(l('add_error'));
			}
		}

		$parent_id = 0;
		$sql = 'select cat_id, cat_name from {pre}category where parent_id = \'' . $parent_id . '\'';
		$category = $this->db->getAll($sql);
		$this->assign('category', $category);
		$this->assign('page_title', l('store_information'));
		$this->display();
	}

	public function actionAudit()
	{
		$shop = $this->model->table('merchants_shop_information')->field('merchants_audit,merchants_message')->where(array('user_id' => $this->user_id))->find();
		$this->assign('shop', $shop);
		$this->assign('img', elixir('img/shenqing-loding.gif'));
		$this->assign('page_title', l('review_the_status'));
		$this->display();
	}

	public function actionGuide()
	{
		$sql = 'select process_title, process_article from {pre}merchants_steps_process where process_steps = \'' . $this->sid . '\'';
		$row = $this->db->getRow($sql);

		if (0 < $row['process_article']) {
			$row['article_centent'] = $this->db->getOne('select content from {pre}article where article_id = \'' . $row['process_article'] . '\'');
		}

		$this->assign('row', $row);
		$this->assign('page_title', l('instructions'));
		$this->display();
	}

	public function actionchecklogin()
	{
		if (!$this->user_id) {
			$url = urlencode(__HOST__ . $_SERVER['REQUEST_URI']);

			if (IS_POST) {
				$url = urlencode($_SERVER['HTTP_REFERER']);
			}

			ecs_header('Location: ' . u('user/login/index', array('back_act' => $url)));
			exit();
		}
	}
}

?>
