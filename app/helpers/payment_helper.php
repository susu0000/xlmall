<?php
//zend by QQ:2172298892 瑾梦网络
function return_url($code)
{
	return __URL__ . '/respond.php?code=' . $code;
}

function notify_url($code)
{
	return __URL__ . '/app/notify/' . $code . '.php';
}

function get_payment($code)
{
	$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment') . ' WHERE pay_code = \'' . $code . '\' AND enabled = \'1\'';
	$payment = $GLOBALS['db']->getRow($sql);

	if ($payment) {
		$config_list = unserialize($payment['pay_config']);

		foreach ($config_list as $config) {
			$payment[$config['name']] = $config['value'];
		}
	}

	return $payment;
}

function get_order_id_by_sn($order_sn, $voucher = 'false')
{
	if ($voucher == 'true') {
		if (is_numeric($order_sn)) {
			return $GLOBALS['db']->getOne('SELECT log_id FROM ' . $GLOBALS['ecs']->table('pay_log') . ' WHERE order_id=' . $order_sn . ' AND order_type=1');
		}
		else {
			return '';
		}
	}
	else {
		if (is_numeric($order_sn)) {
			$sql = 'SELECT order_id FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE order_sn = \'' . $order_sn . '\'';
			$order_id = $GLOBALS['db']->getOne($sql);
		}

		if (!empty($order_id)) {
			$pay_log_id = $GLOBALS['db']->getOne('SELECT log_id FROM ' . $GLOBALS['ecs']->table('pay_log') . ' WHERE order_id=\'' . $order_id . '\'');
			return $pay_log_id;
		}
		else {
			return '';
		}
	}
}

function get_goods_name_by_id($order_id)
{
	$sql = 'SELECT goods_name FROM ' . $GLOBALS['ecs']->table('order_goods') . ' WHERE order_id = \'' . $order_id . '\'';
	$goods_name = $GLOBALS['db']->getCol($sql);
	return implode(',', $goods_name);
}

function check_money($log_id, $money)
{
	if (is_numeric($log_id)) {
		$sql = 'SELECT order_amount FROM ' . $GLOBALS['ecs']->table('pay_log') . ' WHERE log_id = \'' . $log_id . '\'';
		$amount = $GLOBALS['db']->getOne($sql);
	}
	else {
		return false;
	}

	if ($money == $amount) {
		return true;
	}
	else {
		return false;
	}
}

function order_paid($log_id, $pay_status = PS_PAYED, $note = '', $module_name = '')
{
	$module_path = BASE_PATH . 'http/' . $module_name;
	if (!empty($module_name) && is_dir($module_path)) {
		$handler = 'order_paid_' . $module_name;

		if (require_cache($module_path . '/helpers/' . $handler . '.php')) {
			return $handler($log_id, $pay_status = PS_PAYED, $note = '');
		}
	}

	$log_id = intval($log_id);

	if (0 < $log_id) {
		$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('pay_log') . ' WHERE log_id = \'' . $log_id . '\'';
		$pay_log = $GLOBALS['db']->getRow($sql);
		if ($pay_log && ($pay_log['is_paid'] == 0)) {
			$sql = 'UPDATE ' . $GLOBALS['ecs']->table('pay_log') . ' SET is_paid = \'1\' WHERE log_id = \'' . $log_id . '\'';
			$GLOBALS['db']->query($sql);

			if ($pay_log['order_type'] == PAY_ORDER) {
				$sql = 'SELECT main_order_id, order_id, user_id, order_sn, consignee, address, tel, mobile, shipping_id, pay_status, extension_code, extension_id, goods_amount, is_zc_order, zc_goods_id, ' . 'shipping_fee, insure_fee, pay_fee, tax, pack_fee, card_fee, surplus, money_paid, integral_money, bonus, order_amount, discount ' . 'FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE order_id = \'' . $pay_log['order_id'] . '\'';
				$order = $GLOBALS['db']->getRow($sql);
				$main_order_id = $order['main_order_id'];
				$order_id = $order['order_id'];
				$order_sn = $order['order_sn'];
				$is_zc_order = $order['is_zc_order'];
				$zc_goods_id = $order['zc_goods_id'];
				if (($is_zc_order == 1) && (0 < $zc_goods_id)) {
					$sql = ' select * from ' . $GLOBALS['ecs']->table('zc_goods') . ' where id = \'' . $zc_goods_id . '\' ';
					$zc_goods_info = $GLOBALS['db']->getRow($sql);
					$pid = $zc_goods_info['pid'];
					$goods_price = $zc_goods_info['price'];
					$sql = ' UPDATE ' . $GLOBALS['ecs']->table('zc_goods') . ' SET backer_num = backer_num+1 WHERE id = \'' . $zc_goods_id . '\' ';
					$GLOBALS['db']->query($sql);
					$sql = 'SELECT backer_list FROM ' . $GLOBALS['ecs']->table('zc_goods') . ' WHERE id = \'' . $zc_goods_id . '\'';
					$backer_list = $GLOBALS['db']->getOne($sql);

					if (empty($backer_list)) {
						$backer_list = $user_id;
					}
					else {
						$backer_list = $backer_list . ',' . $user_id;
					}

					$sql = 'UPDATE ' . $GLOBALS['ecs']->table('zc_goods') . ' SET backer_list=\'' . $backer_list . '\' WHERE id = \'' . $zc_goods_id . '\'';
					$GLOBALS['db']->query($sql);
					$sql = 'UPDATE ' . $GLOBALS['ecs']->table('zc_project') . ' SET join_num=join_num+1, join_money=join_money+' . $goods_price . ' WHERE id = \'' . $pid . '\'';
					$GLOBALS['db']->query($sql);
				}

				if ($order['extension_code'] == 'presale') {
					$money_paid = $order['money_paid'] + $order['order_amount'];

					if ($order['pay_status'] == 0) {
						$order_amount = ($order['goods_amount'] + $order['shipping_fee'] + $order['insure_fee'] + $order['pay_fee'] + $order['tax']) - $order['money_paid'] - $order['order_amount'];
						$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' SET order_status = \'' . OS_CONFIRMED . '\', ' . ' confirm_time = \'' . gmtime() . '\', ' . ' pay_status = \'' . PS_PAYED_PART . '\', ' . ' pay_time = \'' . gmtime() . '\', ' . ' money_paid = \'' . $money_paid . '\',' . ' order_amount = \'' . $order_amount . '\' ' . 'WHERE order_id = \'' . $order_id . '\'';
						$GLOBALS['db']->query($sql);
						order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, PS_PAYED_PART, $note, l('buyer'));
						update_pay_log($order_id);
					}
					else {
						$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' SET pay_status = \'' . PS_PAYED . '\', ' . ' pay_time = \'' . gmtime() . '\', ' . ' money_paid = \'' . $money_paid . '\',' . ' order_amount = 0 ' . 'WHERE order_id = \'' . $order_id . '\'';
						$GLOBALS['db']->query($sql);
						order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, PS_PAYED, $note, l('buyer'));
					}
				}
				else {
					$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' SET order_status = \'' . OS_CONFIRMED . '\', ' . ' confirm_time = \'' . gmtime() . '\', ' . ' pay_status = \'' . $pay_status . '\', ' . ' pay_time = \'' . gmtime() . '\', ' . ' money_paid = order_amount,' . ' order_amount = 0 ' . 'WHERE order_id = \'' . $order_id . '\'';
					$GLOBALS['db']->query($sql);
					order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);
				}

				$sql = 'SELECT order_id FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE main_order_id = \'' . $order_id . '\'';
				$child_order_id_arr = $GLOBALS['db']->getAll($sql);
				if (($main_order_id == 0) && (0 < count($child_order_id_arr))) {
					$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' SET order_status = \'' . OS_CONFIRMED . '\', ' . ' confirm_time = \'' . gmtime() . '\', ' . ' pay_status = \'' . $pay_status . '\', ' . ' pay_time = \'' . gmtime() . '\', ' . ' money_paid = order_amount,' . ' order_amount = 0 ' . 'WHERE main_order_id = \'' . $order_id . '\'';
					$GLOBALS['db']->query($sql);
					$sql = 'SELECT order_sn ' . 'FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE main_order_id = \'' . $order_id . '\'';
					$order_res = $GLOBALS['db']->getAll($sql);

					foreach ($order_res as $row) {
						order_action($row['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, l('buyer'));
					}
				}

				$sql = 'SELECT ru_id FROM ' . $GLOBALS['ecs']->table('order_goods') . ' WHERE order_id = \'' . $order_id . '\' LIMIT 1';
				$ru_id = $GLOBALS['db']->getOne($sql);

				if ($ru_id == 0) {
					$sms_shop_mobile = $GLOBALS['_CFG']['sms_shop_mobile'];
				}
				else {
					$sql = 'SELECT mobile FROM ' . $GLOBALS['ecs']->table('seller_shopinfo') . ' WHERE ru_id = \'' . $ru_id . '\'';
					$sms_shop_mobile = $GLOBALS['db']->getOne($sql);
				}

				if (($GLOBALS['_CFG']['sms_order_payed'] == '1') && ($sms_shop_mobile != '')) {
					$message = array('consignee' => $order['consignee'], 'order_mobile' => $order['mobile']);
					send_sms($sms_shop_mobile, 'sms_order_payed', $message);
				}

				if (is_dir(APP_WECHAT_PATH) && !empty($pay_log['openid'])) {
					$pushData = array(
						'keyword1' => array('value' => $order_sn),
						'keyword2' => array('value' => '已付款'),
						'keyword3' => array('value' => date('Y-m-d H:i:s', gmtime())),
						'keyword4' => array('value' => $GLOBALS['_CFG']['shop_name']),
						'keyword5' => array('value' => $pay_log['order_amount'])
						);
					$order_url = __HOST__ . url('user/order/detail', array('order_id' => $order_id));
					$url = str_replace('app/notify/wxpay.php', '', $order_url);
					push_template('OPENTM204987032', $pushData, $url, $order['user_id']);
				}

				$virtual_goods = get_virtual_goods($order_id);

				if (!empty($virtual_goods)) {
					$msg = '';

					if (!virtual_goods_ship($virtual_goods, $msg, $order_sn, true)) {
						$GLOBALS['_LANG']['pay_success'] .= '<div style="color:red;">' . $msg . '</div>' . $GLOBALS['_LANG']['virtual_goods_ship_fail'];
					}

					if ($order['shipping_id'] == -1) {
						$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' SET shipping_status = \'' . SS_SHIPPED . '\', shipping_time = \'' . gmtime() . '\'' . ' WHERE order_id = \'' . $order_id . '\'';
						$GLOBALS['db']->query($sql);
						order_action($order_sn, OS_CONFIRMED, SS_SHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);
						$integral = integral_to_give($order);
						log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));
					}
				}
			}
			else if ($pay_log['order_type'] == PAY_SURPLUS) {
				$sql = 'SELECT `id` FROM ' . $GLOBALS['ecs']->table('user_account') . ' WHERE `id` = \'' . $pay_log['order_id'] . '\' AND `is_paid` = 1  LIMIT 1';
				$res_id = $GLOBALS['db']->getOne($sql);

				if (empty($res_id)) {
					$sql = 'UPDATE ' . $GLOBALS['ecs']->table('user_account') . ' SET paid_time = \'' . gmtime() . '\', is_paid = 1' . ' WHERE id = \'' . $pay_log['order_id'] . '\' LIMIT 1';
					$GLOBALS['db']->query($sql);
					$sql = 'SELECT user_id, amount FROM ' . $GLOBALS['ecs']->table('user_account') . ' WHERE id = \'' . $pay_log['order_id'] . '\'';
					$arr = $GLOBALS['db']->getRow($sql);
					$_LANG = array();
					include_once LANG_PATH . $GLOBALS['_CFG']['lang'] . '/user.php';
					log_account_change($arr['user_id'], $arr['amount'], 0, 0, 0, $_LANG['surplus_type_0'], ACT_SAVING);
				}
			}
			else if ($pay_log['order_type'] == PAY_REGISTERED) {
				$sql = 'SELECT id FROM ' . $GLOBALS['ecs']->table('drp_shop') . ' WHERE user_id=\'' . $pay_log['order_id'] . '\' AND `isbuy` = 1  LIMIT 1';
				$res_id = $GLOBALS['db']->getOne($sql);

				if (empty($res_id)) {
					$time = gmtime();
					$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('drp_shop') . '(user_id,create_time, isbuy) VALUES ' . '(\'' . $pay_log[order_id] . '\',\'' . $time . '\', \'1\')';
					$GLOBALS['db']->query($sql);
					if (is_dir(APP_WECHAT_PATH) && !empty($pay_log['openid'])) {
						$shop = dao('drp_shop')->field('shop_name, mobile, create_time')->where($where)->find();
						$pushData = array(
							'keyword1' => array('value' => $shop['shop_name']),
							'keyword2' => array('value' => $shop['mobile']),
							'keyword3' => array('value' => date('Y-m-d', $shop['create_time']))
							);
						$drp_url = __HOST__ . url('drp/index/index');
						$url = str_replace('app/notify/wxpay.php', '', $drp_url);
						push_template('OPENTM207126233', $pushData, $url, $order['user_id']);
					}
				}
			}
		}
		else {
			$post_virtual_goods = get_virtual_goods($pay_log['order_id'], true);

			if (!empty($post_virtual_goods)) {
				$msg = '';
				$sql = 'SELECT pay_time, order_sn FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE order_id = \'' . $pay_log['order_id'] . '\'';
				$row = $GLOBALS['db']->getRow($sql);
				$intval_time = gmtime() - $row['pay_time'];
				if ((0 <= $intval_time) && ($intval_time < (3600 * 12))) {
					$virtual_card = array();

					foreach ($post_virtual_goods as $code => $goods_list) {
						if ($code == 'virtual_card') {
							foreach ($goods_list as $goods) {
								if ($info = virtual_card_result($row['order_sn'], $goods)) {
									$virtual_card[] = array('goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info);
								}
							}

							$GLOBALS['smarty']->assign('virtual_card', $virtual_card);
						}
					}
				}
				else {
					$msg = '<div>' . $GLOBALS['_LANG']['please_view_order_detail'] . '</div>';
				}

				$GLOBALS['_LANG']['pay_success'] .= $msg;
			}

			$virtual_goods = get_virtual_goods($pay_log['order_id'], false);

			if (!empty($virtual_goods)) {
				$GLOBALS['_LANG']['pay_success'] .= '<br />' . $GLOBALS['_LANG']['virtual_goods_ship_fail'];
			}
		}
	}
}

defined('IN_ECTOUCH') || exit('Deny Access');

?>
