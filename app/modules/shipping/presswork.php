<?php
//zend by  QQ:2172298892  瑾梦网络
class presswork
{
	/**
     * 配置信息
     */
	public $configure;

	public function presswork($cfg = array())
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
			$fee = ($goods_weight * 4) + 3.4;

			if (0.1 < $goods_weight) {
				$fee += ceil(($goods_weight - 0.1) / 0.1) * 0.4;
			}

			return $fee;
		}
	}

	public function query($invoice_sn)
	{
		$str = '<a class="btn-submit" href="http://wap.guoguo-app.com/wuliuDetail.htm?mailNo=' . $invoice_sn . '">订单跟踪</a>';
		return $str;
	}

	public function calculate_insure($total_price, $insure_rate)
	{
		$total_price = ceil($total_price);
		$price = $total_price * $insure_rate;

		if ($price < 1) {
			$price = 1;
		}

		return ceil($price);
	}
}

defined('IN_ECTOUCH') || exit('Deny Access');

?>
