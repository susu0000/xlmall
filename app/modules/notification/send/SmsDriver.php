<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\modules\notification\send;

class SmsDriver implements SendInterface
{
	protected $config = array('sms_name' => '', 'sms_password' => '');
	protected $sms;

	public function __construct($config = array())
	{
		$this->config = array_merge($this->config, $config);
		$this->sms = new \app\modules\notification\sms\Sms($this->config);
	}

	public function push($to, $title, $content, $data = array())
	{
		return $this->sms->setSms($title, $content)->sendSms($to);
	}

	public function getError()
	{
		return $this->sms->getError();
	}
}

?>
