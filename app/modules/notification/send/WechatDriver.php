<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\modules\notification\send;

class WechatDriver implements SendInterface
{
	protected $config = array();
	protected $wechat;

	public function __construct($config = array())
	{
		$this->config = array_merge($this->config, $config);
		$this->wechat = new \app\modules\notification\wechat\Wechat($this->config);
	}

	public function push($to, $title, $content, $data = array())
	{
		return $this->wechat->setData($to, $title, $content, $data)->send($to);
	}

	public function getError()
	{
		return $this->wechat->getError();
	}
}

?>
