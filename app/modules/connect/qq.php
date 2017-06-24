<?php
//zend by  QQ:2172298892  瑾梦网络
class qq
{
	public $api_url = 'https://graph.qq.com/';
	private $appid = '';
	private $appkey = '';
	private $access_token = '';
	private $scope = '';

	public function __construct($config, $access_token = NULL)
	{
		$this->appid = $config['app_id'];
		$this->appkey = $config['app_key'];
		$this->access_token = $access_token;
		$this->scope = 'get_user_info, add_share';
	}

	public function redirect($callback_url)
	{
		return $this->login_url($callback_url, $this->scope);
	}

	public function callback($callback_url, $code)
	{
		$result = $this->access_token($callback_url, $code);
		if (isset($result['access_token']) && ($result['access_token'] != '')) {
			$this->access_token = $result['access_token'];
			$openid = $this->get_openid();
			$userinfo = $this->get_user_info($openid);

			if ($userinfo['gender'] == '男') {
				$userinfo['gender'] = 1;
			}
			else if ($userinfo['gender'] == '女') {
				$userinfo['gender'] = 2;
			}
			else {
				$userinfo['gender'] = 0;
			}

			$_SESSION['nickname'] = $this->get_user_name($userinfo);
			$_SESSION['avatar'] = $userinfo['figureurl_qq_2'] ? $userinfo['figureurl_qq_2'] : $userinfo['figureurl_qq_1'];
			$data = array('openid' => $openid, 'name' => $this->get_user_name($userinfo), 'sex' => $userinfo['gender'], 'avatar' => $userinfo['figureurl_qq_2'] ? $userinfo['figureurl_qq_2'] : $userinfo['figureurl_qq_1']);
			return $data;
		}
		else {
			return false;
		}
	}

	public function login_url($callback_url, $scope = '')
	{
		$params = array('client_id' => $this->appid, 'redirect_uri' => $callback_url, 'response_type' => 'code', 'scope' => $scope);
		return 'https://graph.qq.com/oauth2.0/authorize?' . http_build_query($params, '', '&');
	}

	public function access_token($callback_url, $code)
	{
		$params = array('grant_type' => 'authorization_code', 'client_id' => $this->appid, 'client_secret' => $this->appkey, 'code' => $code, 'state' => '', 'redirect_uri' => $callback_url);
		$url = 'https://graph.qq.com/oauth2.0/token?' . http_build_query($params, '', '&');
		$result_str = $this->http($url);
		$json_r = array();

		if ($result_str != '') {
			parse_str($result_str, $json_r);
		}

		return $json_r;
	}

	public function get_openid()
	{
		$params = array('access_token' => $this->access_token);
		$url = 'https://graph.qq.com/oauth2.0/me?' . http_build_query($params, '', '&');
		$result_str = $this->http($url);
		$json_r = array();

		if ($result_str != '') {
			preg_match('/callback\\(\\s+(.*?)\\s+\\)/i', $result_str, $result_a);
			$json_r = json_decode($result_a[1], true);
		}

		return $json_r['openid'];
	}

	public function get_user_info($openid)
	{
		$params = array('openid' => $openid);
		return $this->api('user/get_user_info', $params);
	}

	public function get_user_name($userinfo)
	{
		return $userinfo['nickname'];
	}

	public function add_share($openid, $title, $url, $site, $fromurl, $images = '', $summary = '')
	{
		$params = array('openid' => $openid, 'title' => $title, 'url' => $url, 'site' => $site, 'fromurl' => $fromurl, 'images' => $images, 'summary' => $summary);
		return $this->api('share/add_share', $params, 'POST');
	}

	public function api($url, $params = array(), $method = 'GET')
	{
		$url = $this->api_url . $url;
		$params['access_token'] = $this->access_token;
		$params['oauth_consumer_key'] = $this->appid;
		$params['format'] = 'json';

		if ($method == 'GET') {
			$query_url = $url . '?' . http_build_query($params, '', '&');
			$result_str = $this->http($query_url);
		}
		else {
			$query = http_build_query($params, '', '&');
			$result_str = $this->http($url, $query, 'POST');
		}

		$result = array();

		if ($result_str != '') {
			$result = json_decode($result_str, true);
		}

		return $result;
	}

	private function http($url, $postfields = '', $method = 'GET', $headers = array())
	{
		$ci = curl_init();
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ci, CURLOPT_TIMEOUT, 30);

		if ($method == 'POST') {
			curl_setopt($ci, CURLOPT_POST, true);

			if ($postfields != '') {
				curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
			}
		}

		$headers[] = 'User-Agent: ECTouch.cn';
		curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);
		curl_close($ci);
		return $response;
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
	$modules[$i]['name'] = 'QQ';
	$modules[$i]['type'] = 'qq';
	$modules[$i]['className'] = 'qq';
	$modules[$i]['author'] = 'ECTouch';
	$modules[$i]['qq'] = '800007167';
	$modules[$i]['email'] = 'support@ecmoban.com';
	$modules[$i]['website'] = 'http://open.qq.com';
	$modules[$i]['version'] = '1.0';
	$modules[$i]['date'] = '2016-01-10';
	$modules[$i]['config'] = array(
	array('type' => 'text', 'name' => 'app_id', 'value' => ''),
	array('type' => 'text', 'name' => 'app_key', 'value' => '')
	);
	return NULL;
}

?>
