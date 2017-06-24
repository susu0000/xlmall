<?php
//zend by  QQ:2172298892  瑾梦网络
class wechat
{
	private $wechat = '';

	public function __construct($config)
	{
		$options = array('appid' => $config['app_id'], 'appsecret' => $config['app_secret']);
		$this->wechat = new \ectouch\Wechat($options);
	}

	public function redirect($callback_url, $state = 'wechat_oauth', $snsapi = 'snsapi_userinfo')
	{
		if (is_dir(APP_WECHAT_PATH) && is_wechat_browser() && isset($_COOKIE['ectouch_ru_id'])) {
			$snsapi = 'snsapi_base';
		}

		return $this->wechat->getOauthRedirect($callback_url, $state, $snsapi);
	}

	public function callback($callback_url, $code)
	{
		if (!empty($code)) {
			$token = $this->wechat->getOauthAccessToken();
			$userinfo = $this->wechat->getOauthUserinfo($token['access_token'], $token['openid']);

			if (!empty($userinfo)) {
				include 'emoji.php';
				$userinfo['nickname'] = strip_tags(emoji_unified_to_html($userinfo['nickname']));
				$_SESSION['openid'] = $userinfo['openid'];
				$_SESSION['nickname'] = $userinfo['nickname'];
				$_SESSION['avatar'] = $userinfo['headimgurl'];
				$identify = (isset($userinfo['unionid']) && !empty($userinfo['unionid']) ? $userinfo['unionid'] : $userinfo['openid']);
				$data = array('openid' => $identify, 'name' => $userinfo['nickname'], 'sex' => $userinfo['sex'], 'avatar' => $userinfo['headimgurl']);
				if (is_dir(APP_WECHAT_PATH) && is_wechat_browser()) {
					$this->updateInfo($userinfo);
				}

				return $data;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

	private function updateInfo($res = array())
	{
		if (empty($res)) {
			return false;
		}

		$res['privilege'] = serialize($res['privilege']);
		$unionid = false;
		if (isset($res['unionid']) && !empty($res['unionid'])) {
			$userinfo = dao('wechat_user')->where(array('unionid' => $res['unionid']))->find();
			$unionid = true;
		}
		else {
			$userinfo = dao('wechat_user')->where(array('openid' => $res['openid']))->find();
		}

		if (empty($userinfo)) {
			$res['ect_uid'] = 0;
			$res['wechat_id'] = 1;
			dao('wechat_user')->data($res)->add();
		}
		else {
			if ($unionid) {
				$condition = array('unionid' => $res['unionid']);
			}
			else {
				$condition = array('openid' => $res['openid']);
			}

			dao('wechat_user')->data($res)->where($condition)->save();
		}
	}
}

defined('IN_ECTOUCH') || exit('Deny Access');
$payment_lang = LANG_PATH . c('shop.lang') . '/connect/' . basename(__FILE__);

if (file_exists($payment_lang)) {
	include_once $payment_lang;
	l($_LANG);
}

if (isset($set_modules) && ($set_modules == true)) {
	$i = (isset($modules) ? count($modules) : 0);
	$modules[$i]['name'] = 'Wechat';
	$modules[$i]['type'] = 'wechat';
	$modules[$i]['className'] = 'wechat';
	$modules[$i]['author'] = 'ECTouch';
	$modules[$i]['qq'] = '800007167';
	$modules[$i]['email'] = 'support@ecmoban.com';
	$modules[$i]['website'] = 'http://mp.weixin.qq.com';
	$modules[$i]['version'] = '1.0';
	$modules[$i]['date'] = '2014-10-03';
	$modules[$i]['config'] = array(
	array('type' => 'text', 'name' => 'app_id', 'value' => ''),
	array('type' => 'text', 'name' => 'app_secret', 'value' => ''),
	array('type' => 'radio', 'name' => 'oauth_status', 'value' => '0')
	);
	return NULL;
}

?>
