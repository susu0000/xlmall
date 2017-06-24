<?php
//zend by  QQ:2172298892  瑾梦网络
class fpd
{
	/**
     * 配置信息
     */
	public $configure;

	public function fpd($cfg = array())
	{
	}

	public function calculate($goods_weight, $goods_amount)
	{
		return 0;
	}

	public function query($invoice_sn)
	{
		return $invoice_sn;
	}
}

defined('IN_ECTOUCH') || exit('Deny Access');

?>
