<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\oauth\controllers;

class Admin extends \app\http\base\controllers\Backend
{
	public function __construct()
	{
		parent::__construct();
		l(require LANG_PATH . c('shop.lang') . '/other.php');
		$this->assign('lang', array_change_key_case(l()));
		$this->admin_priv('oauth_admin');
	}

	public function actionIndex()
	{
		$modules = $this->read_modules(BASE_PATH . 'modules/connect');

		foreach ($modules as $key => $value) {
			$modules[$key]['install'] = dao('touch_auth')->where(array('type' => $value['type']))->count();
		}

		$this->assign('modules', $modules);
		$this->display();
	}

	public function actionInstall()
	{
		if (IS_POST) {
			$data['type'] = i('type');
			$data['status'] = i('status', 0, 'intval');
			$data['sort'] = i('sort', 0, 'intval');
			$cfg_value = i('cfg_value');
			$cfg_name = i('cfg_name');
			$cfg_type = i('cfg_type');
			$cfg_label = i('cfg_label');
			$auth_config = array();
			if (isset($cfg_value) && is_array($cfg_value)) {
				for ($i = 0; $i < count($cfg_value); $i++) {
					$auth_config[] = array('name' => trim($cfg_name[$i]), 'type' => trim($cfg_type[$i]), 'value' => trim($cfg_value[$i]));
				}
			}

			$data['auth_config'] = serialize($auth_config);
			$this->model->table('touch_auth')->data($data)->add();
			$this->message(l('msg_ins_success'), url('index'));
			return NULL;
		}

		$type = i('type');
		$oauth_config = $this->getOauthConfig($type);

		if ($oauth_config !== false) {
			$this->redirect('index');
		}

		$filepath = BASE_PATH . 'modules/connect/' . $type . '.php';

		if (file_exists($filepath)) {
			$set_modules = true;
			include_once $filepath;
			$info = $modules[$i];

			foreach ($info['config'] as $key => $value) {
				$info['config'][$key] = $value + array('label' => l($value['name']));
			}
		}

		$this->assign('info', $info);
		$this->assign('ur_here', l('plug_install'));
		$this->display();
	}

	public function actionEdit()
	{
		if (IS_POST) {
			$data['type'] = i('type');
			$data['status'] = i('status', 0, 'intval');
			$data['sort'] = i('sort', 0, 'intval');
			$cfg_value = i('cfg_value');
			$cfg_name = i('cfg_name');
			$cfg_type = i('cfg_type');
			$cfg_label = i('cfg_label');
			$auth_config = array();
			if (isset($cfg_value) && is_array($cfg_value)) {
				for ($i = 0; $i < count($cfg_value); $i++) {
					$auth_config[] = array('name' => trim($cfg_name[$i]), 'type' => trim($cfg_type[$i]), 'value' => trim($cfg_value[$i]));
				}
			}

			$data['auth_config'] = serialize($auth_config);
			dao('touch_auth')->data($data)->where(array('type' => $data['type']))->save();
			$this->message(l('edit_success'), url('index'));
			return NULL;
		}

		$type = i('type');
		$oauth_config = $this->getOauthConfig($type);

		if ($oauth_config === false) {
			$this->redirect('index');
		}

		$filepath = BASE_PATH . 'modules/connect/' . $type . '.php';

		if (file_exists($filepath)) {
			$set_modules = true;
			include_once $filepath;
			$info = $modules[$i];

			foreach ($info['config'] as $key => $value) {
				$info['config'][$key] = $value + array('label' => l($value['name']));
			}
		}

		foreach ($info['config'] as $key => $value) {
			if (isset($oauth_config[$value['name']])) {
				$info['config'][$key]['value'] = $oauth_config[$value['name']];
			}
			else {
				$info['config'][$key]['value'] = $value['value'];
			}
		}

		$info['status'] = $oauth_config['status'];
		$info['sort'] = $oauth_config['sort'];
		$this->assign('info', $info);
		$this->assign('ur_here', l('edit_plug'));
		$this->display();
	}

	public function actionUninstall()
	{
		$condition['type'] = i('type');
		dao('touch_auth')->where($condition)->delete();
		$this->message(l('upload_success'), url('index'));
	}

	private function getOauthConfig($type)
	{
		$condition['type'] = $type;
		$info = dao('touch_auth')->field('auth_config, status, sort')->where($condition)->find();

		if ($info) {
			$user = unserialize($info['auth_config']);
			$config = array('status' => $info['status'], 'sort' => $info['sort']);

			foreach ($user as $key => $value) {
				$config[$value['name']] = $value['value'];
			}

			return $config;
		}

		return false;
	}

	private function read_modules($directory = '.')
	{
		$dir = @opendir($directory);
		$set_modules = true;
		$modules = array();

		while (false !== ($file = @readdir($dir))) {
			if (preg_match('/^.*?\\.php$/', $file)) {
				include_once $directory . '/' . $file;
			}
		}

		@closedir($dir);
		unset($set_modules);

		foreach ($modules as $key => $value) {
			ksort($modules[$key]);
		}

		ksort($modules);
		return $modules;
	}
}

?>
