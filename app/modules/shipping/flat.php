<?php
//zend by  QQ:2172298892  瑾梦网络
class flat
{
	/**
     * 配置信息
     */
	public $configure;

	public function flat($cfg = array())
	{
		foreach ($cfg as $key => $val) {
			$this->configure[$val['name']] = $val['value'];
		}
	}

	public function calculate($goods_weight, $goods_amount)
	{
		if ((0 < $this->configure['free_money']) && ($this->configure['free_money'] <= $goods_amount)) {
			return 0;
		}
		else {
			return isset($this->configure['base_fee']) ? $this->configure['base_fee'] : 0;
		}
	}

	public function query($invoice_sn)
	{
		return $invoice_sn;
	}
}

defined('IN_ECTOUCH') || exit('Deny Access');

?>
