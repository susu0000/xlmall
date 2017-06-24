<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\modules\notification;

class Send
{
	/**
     * 驱动配置
     * @var array
     */
	protected $config = array(
		'driver'       => 'Email',
		'driverConfig' => array()
		);
	/**
     * 驱动
     * @var string
     */
	protected $driver;
	/**
     * 驱动对象
     * @var array
     */
	static protected $objArr = array();

	public function __construct($config)
	{
		$this->config = $config;
		if (empty($this->config) || !isset($this->config['driver'])) {
			throw new \Exception('send config error', 500);
		}
	}

	public function __call($method, $args)
	{
		if (!isset(self::$objArr[$this->send])) {
			$sendDriver = 'app\\modules\\notification' . '\\send\\' . ucfirst($this->config['driver']) . 'Driver';

			if (!class_exists($sendDriver)) {
				throw new \Exception('Send Driver \'' . $sendDriver . '\' not found\'', 500);
			}

			self::$objArr[$this->send] = new $sendDriver($this->config['driverConfig']);
		}

		return call_user_func_array(array(self::$objArr[$this->send], $method), $args);
	}
}


?>
