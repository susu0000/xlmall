<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\modules\notification\wechat;

class Wechat
{
	/**
     * 微信通配置
     * @var array
     */
	protected $config = array('token' => '', 'appid' => '', 'appsecret' => '');
	/**
     * @var objcet 微信对象
     */
	protected $wechat;

	public function __construct($config = array())
	{
		$this->config = array_merge($this->config, $config);
	}

	public function setData($to, $title, $content, $data)
	{
		$to = (!empty($to) ? $to : $_SESSION['user_id']);
		$openid = $this->get_openid($to);
		$sql = 'SELECT title,content FROM {pre}wechat_template WHERE code = \'' . $title . '\' and status = 1 ';
		$template = $GLOBALS['db']->getRow($sql);
		if ($to && $openid && $template['title']) {
			$content['first'] = !empty($content['first']) ? $content['first'] : array('value' => $template['title'], 'color' => '#173177');
			$content['remark'] = !empty($template['content']) ? array('value' => $template['content'], 'color' => '#FF0000') : $content['remark'];
			$rs['code'] = $title;
			$rs['openid'] = $openid;
			$rs['data'] = serialize($content);
			$rs['url'] = $data['url'];
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wechat_template_log'), $rs, 'INSERT');
		}

		return $this;
	}

	public function send($to = 0)
	{
		$openid = $this->get_openid($to);
		$sql = 'SELECT d.code, d.openid, d.data, d.url, t.template_id FROM {pre}wechat_template_log d LEFT JOIN {pre}wechat_template t ON d.code = t.code WHERE d.status = 0 and d.openid = \'' . $openid . '\' ORDER BY d.id ASC';
		$list = $GLOBALS['db']->getAll($sql);

		if ($list) {
			foreach ($list as $k => $v) {
				$data['touser'] = $v['openid'];
				$data['template_id'] = $v['template_id'];
				$data['url'] = $v['url'];
				$data['topcolor'] = '#FF0000';
				$data['data'] = unserialize($v['data']);
				$weObj = new \ectouch\Wechat($this->config);
				$rs = $weObj->sendTemplateMessage($data);

				if (empty($rs)) {
					return false;
				}

				$sql = 'UPDATE {pre}wechat_template_log SET status = 1 WHERE code = \'' . $v['code'] . '\' AND openid = \'' . $v['openid'] . '\' ';
				$GLOBALS['db']->query($sql);
				return true;
			}
		}

		return false;
	}

	private function get_openid($to = 0)
	{
		if (isset($_COOKIE['ectouch_ru_id'])) {
			$openid = (!empty($_COOKIE['seller_openid']) ? $_COOKIE['seller_openid'] : $_SESSION['seller_openid']);
		}
		else if ($to) {
			$sql = 'SELECT openid FROM {pre}wechat_user WHERE ect_uid = \'' . $to . '\'';
			$openid = $GLOBALS['db']->getOne($sql);
		}
		else if ($_SESSION['openid']) {
			$openid = $_SESSION['openid'];
		}

		return $openid;
	}

	public function getError()
	{
		return $this->errorInfo;
	}

	public function __destruct()
	{
		unset($this->wechat);
	}
}


?>
