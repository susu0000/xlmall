<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\upgrade\controllers;

class Index extends \app\http\base\controllers\Backend
{
	private $md5_arr = array();
	private $_filearr = array('admin', 'api', 'include', 'plugins', '');
	private $_wechat = 'wechat';
	private $_extend = 'extend';
	private $_upgrademd5 = 'http://www.ectouch.cn/upgrademd5/';
	private $_patchurl = 'http://upgrade.ecmoban.com/dscmall/1.0/';

	public function __construct()
	{
		parent::__construct();
		$this->charset = str_replace('-', '', CHARSET);
		l(require LANG_PATH . c('shop.lang') . '/upgrade.php');
		defined('UPGRADE_PATH') || define('UPGRADE_PATH', ROOT_PATH . 'storage/upgrade/');
	}

	public function actionIndex()
	{
		$pathlist = $this->pathlist();
		$this->assign('pathlist', $pathlist);
		$this->assign('page_title', '在线更新');
		$this->display();
	}

	public function actionInit()
	{
		$do = i('get.do');
		$cover = i('cover', 0);

		if (empty($do)) {
			$this->message(l('upgradeing'), url('init', array('do' => 1, 'cover' => $cover)));
		}

		$pathlist = $this->pathlist();

		if (empty($pathlist)) {
			$this->message(l('upgrade_success'), url('checkfile'));
		}

		if (!file_exists(UPGRADE_PATH)) {
			@mkdir(UPGRADE_PATH);
		}

		foreach ($pathlist as $k => $v) {
			$patch_name = $v . '_patch.zip';
			$release = str_replace('v', '', $v);
			$upgradezip_url = $this->_patchurl . 'patch/' . $patch_name . '?t=' . time();
			$upgrade_zip = UPGRADE_PATH . $patch_name;
			$upgrade_path = UPGRADE_PATH . basename($patch_name, '.zip') . '/';
			file_put_contents($upgrade_zip, \ectouch\Http::curlPost($upgradezip_url));
			$zip = new \ectouch\Zip();

			if ($zip->decompress($upgrade_zip, $upgrade_path) == 0) {
				exit('Error : unpack the failure.');
			}

			$this->copyfailnum = 0;
			$this->copydir($upgrade_path, dirname(ROOT_PATH), $cover);

			if (0 < $this->copyfailnum) {
				$this->message(l('please_check_filepri'), url('index'));
			}

			$sql_path = $upgrade_path . 'upgrade/';
			$file_list = glob($sql_path . '*');
			@unlink($upgrade_zip);
			$this->del_dir($upgrade_path);
			$tmp_k = $k + 1;

			if (!empty($pathlist[$tmp_k])) {
				$next_update = '<br />' . l('upgradeing') . basename($pathlist[$tmp_k], '.zip');
			}
			else {
				$next_update = '';
			}

			$this->message($v . l('upgrade_success') . $next_update, url('init', array('do' => 1, 'cover' => $cover)));
		}
	}

	public function checkfile()
	{
		$do = i('get.do', 0);

		if (!empty($do)) {
			$this->ec_readdir('.');
			$ectouch_md5 = \ectouch\Http::doGet($this->_upgrademd5 . RELEASE . '_' . $this->patch_charset . '.php');
			$ectouch_md5_arr = json_decode($ectouch_md5, 1);
			$ectouch_md5_arr = (empty($ectouch_md5_arr) ? array() : $ectouch_md5_arr);
			$diff = array_diff($ectouch_md5_arr, $this->md5_arr);
			$lostfile = array();

			foreach ($ectouch_md5_arr as $k => $v) {
				if (!in_array($k, array_keys($this->md5_arr))) {
					$lostfile[] = $k;
					unset($diff[$k]);
				}
			}

			$unknowfile = array_diff(array_keys($this->md5_arr), array_keys($ectouch_md5_arr));
			$this->assign('diff', $diff);
			$this->assign('lostfile', $lostfile);
			$this->assign('unknowfile', $unknowfile);
			$this->display();
		}
		else {
			$this->message(l('begin_checkfile'), url('index', array('do' => 1)));
		}
	}

	public function buildhash()
	{
		$this->ec_readdir('.');
		file_put_contents(CACHE_PATH . RELEASE . '_' . $this->patch_charset . '.php', json_encode($this->md5_arr));
		$this->message(l('build_success'), url('index'));
	}

	private function pathlist()
	{
		$patch_url = $this->_patchurl . 'index.html?t=' . time();
		$pathlist_str = \ectouch\Http::doGet($patch_url);
		$patch_list = $allpathlist = array();
		$key = -1;
		preg_match_all('/"(patch\\/v(.*)_patch\\.zip)"/', $pathlist_str, $allpathlist);
		$allpathlist = $allpathlist[1];

		foreach ($allpathlist as $k => $v) {
			if (strstr($v, 'v' . VERSION . '_patch.zip')) {
				$key = $k;
				break;
			}
		}

		$key = ($key < 0 ? 9999 : $key);

		foreach ($allpathlist as $k => $v) {
			if ($key < $k) {
				$patch_list[$k] = str_replace(array('patch/', '_patch.zip'), '', $v);
			}
		}

		return $patch_list;
	}

	private function ec_readdir($path = '')
	{
		$dir_arr = explode('/', dirname($path));

		if (is_dir($path)) {
			$handler = opendir($path);

			while (($filename = @readdir($handler)) !== false) {
				if (substr($filename, 0, 1) != '.') {
					$this->ec_readdir($path . '/' . $filename);
				}
			}

			closedir($handler);
		}
		else {
			if ((dirname($path) == '.') || (isset($dir_arr[1]) && in_array($dir_arr[1], $this->_filearr))) {
				$pos_wechat = strpos(strtolower($path), $this->_wechat);
				$pos_extend = strpos(strtolower($path), $this->_extend);
				if (($pos_wechat === false) && ($pos_extend === false)) {
					$this->md5_arr[base64_encode($path)] = md5_file($path);
				}
			}
		}
	}

	private function copydir($dirfrom, $dirto, $cover = '')
	{
		if (is_file($dirto)) {
			exit(l('have_no_pri') . $dirto);
		}

		if (!file_exists($dirto)) {
			mkdir($dirto);
		}

		$handle = opendir($dirfrom);

		while (false !== ($file = readdir($handle))) {
			if (($file != '.') && ($file != '..')) {
				$filefrom = $dirfrom . '/' . $file;
				$fileto = $dirto . '/' . $file;

				if (is_dir($filefrom)) {
					$this->copydir($filefrom, $fileto, $cover);
				}
				else if (!copy($filefrom, $fileto)) {
					$this->copyfailnum++;
					echo l('copy') . $filefrom . l('to') . $fileto . l('failed') . '<br />';
				}
			}
		}
	}

	private function del_dir($dir)
	{
		if (!is_dir($dir)) {
			return false;
		}

		$handle = opendir($dir);

		while (($file = readdir($handle)) !== false) {
			if (($file != '.') && ($file != '..')) {
				is_dir($dir . '/' . $file) ? $this->del_dir($dir . '/' . $file) : @unlink($dir . '/' . $file);
			}
		}

		if (readdir($handle) == false) {
			closedir($handle);
			@rmdir($dir);
		}
	}
}

?>
