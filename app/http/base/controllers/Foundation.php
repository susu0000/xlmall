<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\base\controllers;

abstract class Foundation extends \Think\Controller\RestController
{
	protected $model;
	protected $cache;
	protected $fs;
	protected $pager = '';

	public function __construct()
	{
		parent::__construct();
		$HTTP_HOST = $_SERVER['HTTP_HOST'] . ($_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT']);
		define('__HOST__', (is_ssl() ? 'https://' : 'http://') . $HTTP_HOST);
		define('__URL__', __HOST__ . __ROOT__);
		define('__STATIC__', config('TMPL_PARSE_STRING.__STATIC__'));
		define('__PUBLIC__', config('TMPL_PARSE_STRING.__PUBLIC__'));
		define('__TPL__', config('TMPL_PARSE_STRING.__TPL__'));
		define('MODULE_BASE_PATH', BASE_PATH . 'http/' . MODULE_NAME . '/');
		$this->fs = new \Symfony\Component\Filesystem\Filesystem();
		$this->model = new \app\classes\Mysql();
		$GLOBALS['cache'] = $this->cache = \Think\Cache::getInstance();
		$GLOBALS['smarty'] = \Think\Think::instance('Think\\View');
	}

	protected function getApiCityName($ip = '')
	{
		$ip = (empty($ip) ? get_client_ip() : $ip);
		$data = array('ip' => $ip);
		new \app\service\IpBasedLocation($data);
		return $data['city'];
	}

	protected function load_helper($files = array(), $type = 'base')
	{
		if (!is_array($files)) {
			$files = array($files);
		}

		$base_path = ($type == 'app' ? MODULE_BASE_PATH : BASE_PATH);

		foreach ($files as $vo) {
			$helper = $base_path . 'helpers/' . $vo . '_helper.php';

			if (file_exists($helper)) {
				require_once $helper;
			}
		}
	}

	protected function pageLimit($url, $num = 10)
	{
		$url = str_replace(urlencode('{page}'), '{page}', $url);
		$page = (isset($this->pager['obj']) && is_object($this->pager['obj']) ? $this->pager['obj'] : new \ectouch\Page());
		$cur_page = $page->getCurPage($url);
		$limit_start = ($cur_page - 1) * $num;
		$limit = $limit_start . ',' . $num;
		$this->pager = array('obj' => $page, 'url' => $url, 'num' => $num, 'cur_page' => $cur_page, 'limit' => $limit);
		return $limit;
	}

	protected function pageShow($count)
	{
		return $this->pager['obj']->show($this->pager['url'], $count, $this->pager['num']);
	}

	protected function upload($savePath = '', $hasOne = false, $size = 2, $thumb = false)
	{
		$config = array(
			'maxSize'  => $size * 1024 * 1024,
			'rootPath' => config('UPLOAD_PATH'),
			'savePath' => rtrim($savePath, '/') . '/',
			'exts'     => array('jpg', 'gif', 'png', 'jpeg', 'bmp', 'mp3', 'amr', 'mp4'),
			'autoSub'  => false,
			'thumb'    => $thumb
			);
		$aliossConfig = $this->getBucketInfo();
		if ((config('shop.open_oss') == 1) && ($aliossConfig !== false)) {
			$up = new \Think\Upload($config, 'Alioss', $aliossConfig);
		}
		else {
			$up = new \Think\Upload($config);
		}

		$result = $up->upload();

		if (!$result) {
			return array('error' => 1, 'message' => $up->getError());
		}
		else {
			$res = array('error' => 0);

			if ($hasOne) {
				$info = reset($result);
				$res['url'] = $info['savepath'] . $info['savename'];
			}
			else {
				foreach ($result as $k => $v) {
					$result[$k]['url'] = $v['savepath'] . $v['savename'];
				}

				$res['url'] = $result;
			}

			return $res;
		}
	}

	protected function remove($file = '')
	{
		if (empty($file) || in_array($file, array('/', '\\'))) {
			return false;
		}

		$config = $this->getBucketInfo();
		if ((config('shop.open_oss') == 1) && ($config !== false)) {
			$client = new \Think\Upload\Driver\Alioss($config);

			if ($client->delete($file)) {
				return true;
			}
		}
		else {
			$file = (is_file(ROOT_PATH . $file) ? ROOT_PATH . $file : dirname(ROOT_PATH) . '/' . $file);

			if (is_file($file)) {
				$this->fs->remove($file);
				return true;
			}
		}

		return false;
	}

	protected function ossMirror($file = '', $savepath = '')
	{
		$data = array('savepath' => rtrim($savepath, '/') . '/', 'savename' => basename($file), 'tmp_name' => $file);
		$config = $this->getBucketInfo();

		if ($config !== false) {
			$client = new \Think\Upload\Driver\Alioss($config);
			$client->save($data);
			return $data['url'];
		}

		return false;
	}

	private function getBucketInfo()
	{
		$condition = array('is_use' => 1);
		$res = $this->model->table('oss_configure')->cache(true)->where($condition)->find();

		if (empty($res)) {
			return false;
		}

		$regional = substr($res['regional'], 0, 2);
		if (($regional == 'us') || ($regional == 'ap')) {
			$res['endpoint'] = 'oss-' . $res['regional'] . '.aliyuncs.com';
			$res['outside_site'] = 'http://' . $res['bucket'] . '.oss-' . $res['regional'] . '.aliyuncs.com';
			$res['inside_site'] = 'http://' . $res['bucket'] . '.oss-' . $res['regional'] . '-internal.aliyuncs.com';
		}
		else {
			$res['endpoint'] = 'oss-cn-' . $res['regional'] . '.aliyuncs.com';
			$res['outside_site'] = 'http://' . $res['bucket'] . '.oss-cn-' . $res['regional'] . '.aliyuncs.com';
			$res['inside_site'] = 'http://' . $res['bucket'] . '.oss-cn-' . $res['regional'] . '-internal.aliyuncs.com';
		}

		return array('bucket' => $res['bucket'], 'accessKeyId' => $res['keyid'], 'accessKeySecret' => $res['keysecret'], 'endpoint' => $res['endpoint'], 'isCName' => (bool) $res['is_cname']);
	}

	protected function sentry($e, $type = 0)
	{
		$client = new \Raven_Client('https://ae2118aa1c3149c5bba492ed9abaf43f:2e4b9be6f4d9495eb3f0a44f28484893@sentry.io/106949');
		$error_handler = new \Raven_ErrorHandler($client);
		$error_handler->registerExceptionHandler();
		$error_handler->registerErrorHandler();
		$error_handler->registerShutdownFunction();

		if ($type) {
			$client->captureMessage($e);
		}
		else {
			$client->captureException($e);
		}
	}
}

?>
