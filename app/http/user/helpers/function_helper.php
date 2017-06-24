<?php

function not_pay($user_id)
{
	$where = 'and pay_status = ' . PS_UNPAYED . ' and order_status not in(' . OS_CANCELED . ',' . OS_INVALID . ',' . OS_RETURNED . ')';
	$sql = 'SELECT count(*) as num FROM {pre}order_info WHERE user_id = \'' . $user_id . '\' ' . $where;
	$res = $GLOBALS['db']->getRow($sql);
	return $res['num'];
}

function not_shouhuo($user_id)
{
	$sql = 'SELECT count(*) as num FROM {pre}order_info WHERE user_id = \'' . $user_id . '\' and shipping_status = 1 ';
	$res = $GLOBALS['db']->getRow($sql);
	return $res['num'];
}

function not_comments($user_id)
{
	$sql = 'select count(b.goods_id) from {pre}order_info as o  LEFT JOIN {pre}order_goods  as b on o.order_id=b.order_id  where user_id=\'' . $user_id . '\' ' . ' AND o.order_status ' . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) . ' AND o.shipping_status ' . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . ' AND o.pay_status ' . db_create_in(array(PS_PAYED, PS_PAYING)) . ' AND b.goods_id not in(select id_value from {pre}comment where user_id=\'' . $user_id . '\')';
	$res = $GLOBALS['db']->getRow($sql);
	$row = $res['count(b.goods_id)'];
	return $row;
}

function team_ongoing($user_id)
{
	$where = ' and t.status < 1 and \'' . gmtime() . '\'< (t.start_time+(tg.validity_time*3600)) ';
	$sql = 'select count(o.order_id) as num from {pre}order_info as o left join {pre}team_log as t on o.team_id = t.team_id left join {pre}team_goods as tg on tg.goods_id = t.goods_id ' . ' where o.user_id =' . $user_id . ' and o.extension_code =\'team_buy\' and o.pay_status = \'' . PS_PAYED . '\' ' . $where . ' ';
	$res = $GLOBALS['db']->getRow($sql);
	return $res['num'];
}

function my_bonus($user_id)
{
	$time = time();
	$sql = 'select count(u.bonus_id) from {pre}user_bonus as u left join {pre}bonus_type as b on u.bonus_type_id=b.type_id' . ' where u.user_id=\'' . $user_id . '\' and b.use_end_date>' . $time . ' and u.order_id=0 ';
	$res = $GLOBALS['db']->getRow($sql);
	$count = $res['count(u.bonus_id)'];
	return $count;
}

function pay_money($user_id)
{
	$sql = 'SELECT user_money , pay_points FROM {pre}users WHERE user_id = \'' . $user_id . '\'';
	$res = $GLOBALS['db']->getRow($sql);
	return $res;
}

function msg_lists($user_id)
{
	$sql = 'select msg_id from {pre}feedback where  user_id= \'' . $user_id . '\'';
	$ress = $GLOBALS['db']->getAll($sql);
	$str = '';

	if ($ress) {
		foreach ($ress as $k) {
			$str .= $k['msg_id'] . ',';
		}
	}

	$reb = substr($str, 0, -1);

	if (!$reb) {
		$reb = 0;
	}

	$sql = 'select parent_id from {pre}feedback where parent_id in (' . $reb . ')';
	$res = $GLOBALS['db']->getAll($sql);

	if ($res) {
		$strs = '';

		foreach ($res as $k) {
			$strs .= $k['parent_id'] . ',';
		}
	}

	$rebs = substr($strs, 0, -1);

	if (!$rebs) {
		$rebs = 0;
	}
}

function num_collection_goods($user_id)
{
	$sql = 'SELECT count(*) as num FROM {pre}collect_goods WHERE user_id = \'' . $user_id . '\'  ';
	$res = $GLOBALS['db']->getRow($sql);
	return $res;
}

function num_collection_store($user_id)
{
	$sql = 'SELECT count(*) as num FROM {pre}collect_store WHERE user_id = \'' . $user_id . '\'  ';
	$res = $GLOBALS['db']->getRow($sql);
	return $res;
}

function historys($record_count, $limit = '')
{
	$str = '';
	$history = array();

	if (!empty($_COOKIE['ECS']['history_goods'])) {
		$where = db_create_in($_COOKIE['ECS']['history_goods'], 'goods_id');
		$sql = 'SELECT goods_id, goods_name, goods_thumb, shop_price FROM {pre}goods' . ' WHERE ' . $where . ' AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0' . $limit;
		$query = $GLOBALS['db']->getAll($sql);
		$res = array();

		foreach ($query as $key => $row) {
			$goods['goods_id'] = $row['goods_id'];
			$goods['goods_name'] = $row['goods_name'];
			$goods['short_name'] = 0 < c('goods_name_length') ? sub_str($row['goods_name'], c('goods_name_length')) : $row['goods_name'];
			$goods['goods_thumb'] = get_image_path($row['goods_thumb']);
			$goods['shop_price'] = price_format($row['shop_price']);
			$goods['url'] = url('goods/index/index', array('id' => $row['goods_id']));
			$history[] = $goods;
		}
	}

	rsort($history);
	$arr = array('goods_list' => $history, 'record_count' => $record_count, 'paper' => $paper, 'size' => $size);
	return $arr;
}

function check_user_info($data, $type = 'mobile')
{
	if ($type == 'mobile') {
		$where = ' user_name=\'' . $data['user_name'] . '\' and mobile_phone=\'' . $data['mobile_phone'] . '\'';
	}
	else {
		$where = ' user_name=\'' . $data['user_name'] . '\' and email=\'' . $data['email'] . '\'';
	}

	$sql = 'SELECT mobile_phone, email FROM {pre}users WHERE ' . $where;
	$query = $GLOBALS['db']->getRow($sql);

	if (!empty($query)) {
		return true;
	}
	else {
		return false;
	}
}

function get_admin_feedback($user_id)
{
	if (!empty($user_id)) {
		$sql = 'SELECT COUNT(*) AS admin_count FROM {pre}feedback AS a WHERE a.parent_id IN ' . ' (SELECT msg_id FROM {pre}feedback AS b WHERE b.user_id = \'' . $user_id . '\')';
		$query = $GLOBALS['db']->getRow($sql);
	}

	return $query['admin_count'];
}

function presale_settle_status($extension_id)
{
	$now = gmtime();
	$sql = ' SELECT pay_start_time, pay_end_time FROM ' . $GLOBALS['ecs']->table('presale_activity') . ' WHERE act_id = \'' . $extension_id . '\' ';
	$row = $GLOBALS['db']->getRow($sql);
	$result = array();
	if (($row['pay_start_time'] <= $now) && ($now <= $row['pay_end_time'])) {
		$result['start_time'] = local_date('Y-m-d', $row['pay_start_time']);
		$result['end_time'] = local_date('Y-m-d', $row['pay_end_time']);
		$result['settle_status'] = 1;
		return $result;
	}
	else if ($row['pay_end_time'] < $now) {
		$result['start_time'] = local_date('Y-m-d ', $row['pay_start_time']);
		$result['end_time'] = local_date('Y-m-d', $row['pay_end_time']);
		$result['settle_status'] = -1;
		return $result;
	}
	else {
		$result['start_time'] = local_date('Y-m-d', $row['pay_start_time']);
		$result['end_time'] = local_date('Y-m-d', $row['pay_end_time']);
		$result['settle_status'] = 0;
		return $result;
	}
}

function zc_best_list()
{
	$now = time();
	$sql = 'SELECT `id`,`cat_id`,`title`,`start_time`,`end_time`,`amount`,`join_money`,`join_num`,`title_img`,`describe`,(end_time-unix_timestamp(now())) as shenyu_time FROM ' . $GLOBALS['ecs']->table('zc_project') . ' where start_time <= \'' . $now . '\' AND end_time >= \'' . $now . '\' and is_best = 1 ORDER BY id DESC ';
	$zc_arr = $GLOBALS['db']->query($sql);

	foreach ($zc_arr as $k => $z_val) {
		$zc_arr[$k]['star_time'] = date('Y-m-d', $z_val['start_time']);
		$zc_arr[$k]['end_time'] = date('Y-m-d', $z_val['end_time']);
		$zc_arr[$k]['shenyu_time'] = ceil($z_val['shenyu_time'] / 3600 / 24);
		$zc_arr[$k]['title_img'] = get_image_path($z_val['title_img']);
		$zc_arr[$k]['url'] = url('crowd_funding/index/info', array('id' => $z_val['id']));
		$zc_arr[$k]['baifen_bi'] = round($z_val['join_money'] / $z_val['amount'], 2) * 100;
		$zc_arr[$k]['min_price'] = plan_min_price($z_val['id']);

		if (50 < mb_strlen($z_val['describe'], 'utf-8')) {
			$zc_arr[$k]['duan_des'] = mb_substr($z_val['describe'], 0, 50, 'utf-8') . '...';
		}
		else {
			$zc_arr[$k]['duan_des'] = mb_substr($z_val['describe'], 0, 50, 'utf-8');
		}

		if (time() < $z_val['start_time']) {
			$zc_arr[$k]['zc_status'] = '预热中';
		}
		else {
			if (($z_val['start_time'] <= time()) && (time() <= $z_val['end_time'])) {
				$zc_arr[$k]['zc_status'] = '众筹中';
			}
			else if ($z_val['end_time'] < time()) {
				if ($z_val['amount'] <= $z_val['join_money']) {
					$zc_arr[$k]['zc_status'] = '众筹成功';
					$zc_arr[$k]['shenyu_time'] = 0;
				}
				else {
					unset($zc_arr[$k]);
				}
			}
		}
	}

	return $zc_arr;
}

function zc_focus_list($user_id = 0, $type = 1)
{
	$now = time();

	switch ($type) {
	case 1:
		$where = ' ';
		break;

	case 2:
		$where = ' AND ' . $now . '  < zp.end_time';
		break;

	case 3:
		$where = ' AND ' . $now . ' > zp.end_time';
		break;

	case 4:
		$where = ' AND zp.status = 2';
		break;
	}

	$sql = 'select zp.*,(end_time-unix_timestamp(now())) as shenyu_time,sum(zg.backer_num) as zhichi_num from ' . $GLOBALS['ecs']->table('zc_focus') . "zf\r\n\t\t\tleft join" . $GLOBALS['ecs']->table('zc_project') . "zp on zf.pid=zp.id\r\n\t\t\tleft join " . $GLOBALS['ecs']->table('zc_goods') . "zg on zp.id=zg.pid\r\n\t\t\twhere zf.user_id='" . $user_id . '\' ' . $where . ' group by zp.id';
	$zc_focus_list = $GLOBALS['db']->getAll($sql);

	foreach ($zc_focus_list as $k => $z_val) {
		$zc_focus_list[$k]['star_time'] = date('Y-m-d', $z_val['start_time']);
		$zc_focus_list[$k]['end_time'] = date('Y-m-d', $z_val['end_time']);
		$zc_focus_list[$k]['shenyu_time'] = ceil($z_val['shenyu_time'] / 3600 / 24);
		$zc_focus_list[$k]['title_img'] = get_image_path($z_val['title_img']);
		$zc_focus_list[$k]['url'] = url('crowd_funding/index/info', array('id' => $z_val['id']));
		$zc_focus_list[$k]['baifen_bi'] = round($z_val['join_money'] / $z_val['amount'], 2) * 100;
		$zc_focus_list[$k]['min_price'] = plan_min_price($z_val['id']);

		if (50 < mb_strlen($z_val['describe'], 'utf-8')) {
			$zc_focus_list[$k]['duan_des'] = mb_substr($z_val['describe'], 0, 50, 'utf-8') . '...';
		}
		else {
			$zc_focus_list[$k]['duan_des'] = mb_substr($z_val['describe'], 0, 50, 'utf-8');
		}

		if (time() < $z_val['start_time']) {
			$zc_focus_list[$k]['zc_status'] = '预热中';
		}
		else {
			if (($z_val['start_time'] <= time()) && (time() <= $z_val['end_time'])) {
				$zc_focus_list[$k]['zc_status'] = '众筹中';
			}
			else if ($z_val['end_time'] < time()) {
				if ($z_val['amount'] <= $z_val['join_money']) {
					$zc_focus_list[$k]['zc_status'] = '众筹成功';
					$zc_focus_list[$k]['shenyu_time'] = 0;
				}
				else {
					unset($zc_arr[$k]);
				}
			}
		}
	}

	return $zc_focus_list;
}

function crowd_buy_list($user_id = 0, $size = 10, $page = 1, $type = 1)
{
	$now = time();

	switch ($type) {
	case 1:
		$where = ' ';
		break;

	case 2:
		$where = ' AND ' . $now . '  < zp.end_time';
		break;

	case 3:
		$where = ' AND ' . $now . ' > zp.end_time';
		break;

	case 4:
		break;
	}

	$sql = 'select zp.id, zp.title,zp.start_time,zp.end_time,zp.amount,zp.join_money,zp.describe,zp.title_img,(end_time-unix_timestamp(now())) as shenyu_time,oi.order_id,oi.pay_status,shipping_status,zp.join_num as zhichi_num from ' . $GLOBALS['ecs']->table('zc_goods') . ' as zg left join ' . $GLOBALS['ecs']->table('zc_project') . " as zp on zg.pid=zp.id\r\n\t\t\tleft join " . $GLOBALS['ecs']->table('order_info') . 'as oi on zg.id=oi.zc_goods_id where oi.user_id=\'' . $user_id . '\' ' . $where . ' and oi.is_zc_order=1  GROUP BY zp.id order by oi.order_id desc  ';
	$buy_list = $GLOBALS['db']->getAll($sql);
	$total = (is_array($buy_list) ? count($buy_list) : 0);
	$crowd_buy_list = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	foreach ($crowd_buy_list as $k => $z_val) {
		$crowd_buy_list[$k]['star_time'] = date('Y-m-d', $z_val['start_time']);
		$crowd_buy_list[$k]['end_time'] = date('Y-m-d', $z_val['end_time']);
		$crowd_buy_list[$k]['shenyu_time'] = ceil($z_val['shenyu_time'] / 3600 / 24);
		$crowd_buy_list[$k]['title_img'] = get_image_path($z_val['title_img']);
		$crowd_buy_list[$k]['url'] = url('crowd_funding/index/info', array('id' => $z_val['id']));
		$crowd_buy_list[$k]['baifen_bi'] = round($z_val['join_money'] / $z_val['amount'], 2) * 100;
		$crowd_buy_list[$k]['min_price'] = plan_min_price($z_val['id']);

		if (50 < mb_strlen($z_val['describe'], 'utf-8')) {
			$crowd_buy_list[$k]['duan_des'] = mb_substr($z_val['describe'], 0, 50, 'utf-8') . '...';
		}
		else {
			$crowd_buy_list[$k]['duan_des'] = mb_substr($z_val['describe'], 0, 50, 'utf-8');
		}

		if (time() < $z_val['start_time']) {
			$crowd_buy_list[$k]['zc_status'] = '预热中';
		}
		else {
			if (($z_val['start_time'] <= time()) && (time() <= $z_val['end_time'])) {
				$crowd_buy_list[$k]['zc_status'] = '众筹中';
			}
			else if ($z_val['end_time'] < time()) {
				if ($z_val['amount'] <= $z_val['join_money']) {
					$crowd_buy_list[$k]['zc_status'] = '众筹成功';
					$crowd_buy_list[$k]['shenyu_time'] = 0;
				}
				else {
					unset($zc_arr[$k]);
				}
			}
		}
	}

	return array('list' => array_values($crowd_buy_list), 'totalpage' => ceil($total / $size));
}

function zc_get_user_orders($user_id, $size = 10, $page = 1, $status = 0)
{
	$where = '';

	if ($status == 1) {
		$where = '';
	}
	else if ($status == 2) {
		$where = 'and oi.pay_status = ' . PS_UNPAYED . ' and oi.order_status not in(' . OS_CANCELED . ',' . OS_INVALID . ',' . OS_RETURNED . ')';
	}
	else if ($status == 3) {
		$where = 'and oi.pay_status = ' . PS_PAYED . ' and oi.shipping_status =' . SS_UNSHIPPED;
	}
	else if ($status == 4) {
		$where = 'and oi.pay_status = ' . PS_PAYED . ' and oi.shipping_status =' . SS_SHIPPED;
	}
	else {
		$where = 'and oi.pay_status = ' . PS_PAYED . ' and oi.shipping_status =' . SS_RECEIVED;
	}

	$arr = array();
	$now = time();
	$sql = 'select zp.title,zp.title_img,zg.content,zg.price,oi.consignee,oi.pay_name, oi.order_id, oi.order_sn, oi.order_status, oi.shipping_status, oi.pay_status, oi.add_time, oi.shipping_time, oi.auto_delivery_time, oi.sign_time,(oi.goods_amount + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee + oi.tax - oi.discount) AS total_fee,oi.invoice_no, oi.shipping_name, oi.tel, oi.email, oi.address, oi.province, oi.city, oi.district  from ' . $GLOBALS['ecs']->table('zc_goods') . ' as zg left join ' . $GLOBALS['ecs']->table('zc_project') . " as zp on zg.pid=zp.id\r\n\t\t\tleft join " . $GLOBALS['ecs']->table('order_info') . 'as oi on zg.id=oi.zc_goods_id where oi.user_id=\'' . $user_id . '\' ' . $where . ' and oi.is_zc_order=1 order by oi.order_id desc';
	$orderlist = $GLOBALS['db']->getAll($sql);
	$total = (is_array($orderlist) ? count($orderlist) : 0);
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	foreach ($res as $key => $row) {
		$os = l('os');
		$ps = l('ps');
		$ss = l('ss');

		if ($row['order_status'] == OS_UNCONFIRMED) {
			$row['handler'] = '<a class="btn-default" href="' . url('user/crowd/cancel', array('order_id' => $row['order_id'])) . '" onclick="if (!confirm(\'' . l('confirm_cancel') . '\')) return false;">' . l('cancel') . '</a>';
		}
		else if ($row['order_status'] == OS_SPLITED) {
			if ($row['shipping_status'] == SS_SHIPPED) {
				@$row['handler'] = '<a class="btn-default" href="' . url('user/crowd/affirmreceived', array('order_id' => $row['order_id'])) . '" onclick="if (!confirm(\'' . l('confirm_received') . '\')) return false;">' . l('received') . '</a>';
			}
			else if ($row['shipping_status'] == SS_RECEIVED) {
				@$row['handler'] = '<a class="btn-default">' . l('ss_received') . '</a>';
			}
			else if ($row['pay_status'] == PS_UNPAYED) {
				@$row['handler'] = '<a class="btn-default" href="' . url('user/crowd/detail', array('order_id' => $row['order_id'])) . '" >' . l('pay_money') . '</a>';
			}
			else {
				@$row['handler'] = '<a  class="btn-default" href="' . url('user/crowd/detail', array('order_id' => $row['order_id'])) . '">' . l('view_order') . '</a>';
			}
		}
		else {
			$row['handler'] = '<a class="btn-default">' . $os[$row['order_status']] . '</a>';
		}

		$row['user_order'] = $row['order_status'];
		$row['user_shipping'] = $row['shipping_status'];
		$row['user_pay'] = $row['pay_status'];
		if (($row['user_order'] == OS_SPLITED) && ($row['user_shipping'] == SS_RECEIVED) && ($row['user_pay'] == PS_PAYED)) {
			$row['delete_yes'] = 1;
		}
		else {
			if ((($row['user_order'] == OS_CONFIRMED) || ($row['user_order'] == OS_UNCONFIRMED) || ($row['user_order'] == OS_CANCELED)) && ($row['user_shipping'] == SS_UNSHIPPED) && ($row['user_pay'] == PS_UNPAYED)) {
				$row['delete_yes'] = 1;
			}
			else {
				if (($row['user_order'] == OS_INVALID) && ($row['user_pay'] == PS_PAYED_PART) && ($row['user_shipping'] == SS_UNSHIPPED)) {
					$row['delete_yes'] = 1;
				}
				else {
					$row['delete_yes'] = 0;
				}
			}
		}

		$row['shipping_status'] = $row['shipping_status'] == SS_SHIPPED_ING ? SS_PREPARING : $row['shipping_status'];
		$row['order_status'] = $os[$row[order_status]] . ',' . $ps[$row[pay_status]] . ',' . $ss[$row['shipping_status']];
		$br = '';
		$order_over = 0;
		if (($row['user_order'] == OS_SPLITED) && ($row['user_shipping'] == SS_RECEIVED) && ($row['user_pay'] == PS_PAYED)) {
			$order_over = 1;
			$row['order_status'] = l('ss_received');
			$sign_url = '';

			if (0 < $row['sign']) {
				$sign = '&sign=' . $row['sign'];
				$sign_url = url('user/crowd/add_comment', array('order_id' => $row['order_id']));
			}
			else {
				$sign = '';
				$sign_url = url('user/crowd/add_comment', array('order_id' => $row['order_id']));
				$row['handler'] = '<a href="' . $sign_url . '" class="btn-submit1">订单评价</a>';
			}
		}
		else {
			if (($row['user_order'] == OS_CANCELED) && ($row['user_shipping'] == SS_UNSHIPPED) && ($row['user_pay'] == PS_UNPAYED)) {
				$order_over = 1;
				$row['handler'] = '';
			}
			else {
				if (($row['user_order'] == OS_SPLITED) && ($row['user_shipping'] == SS_SHIPPED) && ($row['user_pay'] == PS_PAYED)) {
					$row['handler'] = $row['handler'];
					$br = '<br/>';
				}
				else {
					if (!(($row['user_order'] == OS_UNCONFIRMED) && ($row['user_shipping'] == SS_UNSHIPPED) && ($row['user_pay'] == PS_UNPAYED))) {
						$row['handler'] = '';
					}
					else {
						$br = '<br/>';
					}
				}
			}
		}

		$ru_id = $row['ru_id'];
		$order_id = $row['order_id'];
		$date = array('order_id');
		$order_child = count(get_table_date('order_info', 'main_order_id=\'' . $order_id . '\'', $date, 1));
		$row[$key]['order_child'] = $order_child;
		$sql = 'select order_id from ' . $GLOBALS['ecs']->table('order_info') . ' where main_order_id = \'' . $row['main_order_id'] . '\' and main_order_id > 0';
		$order_count = count($GLOBALS['db']->getAll($sql));
		$sql = 'select kf_type, kf_ww, kf_qq  from ' . $GLOBALS['ecs']->table('seller_shopinfo') . ' where ru_id=\'' . $ru_id . '\'';
		$basic_info = $GLOBALS['db']->getRow($sql);
		$sql = 'select invoice_no, shipping_name, update_time from ' . $GLOBALS['ecs']->table('delivery_order') . ' where order_id = \'' . $row['order_id'] . '\'';
		$delivery = $GLOBALS['db']->getRow($sql);
		$province = get_order_region_name($row['province']);
		$city = get_order_region_name($row['city']);
		$district = get_order_region_name($row['district']);

		if ($district['region_name']) {
			$district_name = $district['region_name'];
		}

		$address_detail = $province['region_name'] . '&nbsp;' . $city['region_name'] . '市' . '&nbsp;' . $district_name;
		$delivery['delivery_time'] = local_date($GLOBALS['_CFG']['time_format'], $delivery['update_time']);
		$arr[] = array('order_id' => $row['order_id'], 'order_sn' => $row['order_sn'], 'order_time' => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']), 'order_status' => $row['order_status'], 'status' => $row['status'], 'status_number' => $status_number, 'consignee' => $row['consignee'], 'main_order_id' => $row['main_order_id'], 'title' => $row['title'], 'title_img' => get_image_path($row['title_img']), 'content' => $row['content'], 'price' => $row['price'], 'order_child' => $order_child, 'no_picture' => $GLOBALS['_CFG']['no_picture'], 'order_child' => $order_child, 'delete_yes' => $row['delete_yes'], 'invoice_no' => $row['invoice_no'], 'shipping_name' => $row['shipping_name'], 'email' => $row['email'], 'address_detail' => $row['address_detail'], 'address' => $row['address'], 'address_detail' => $address_detail, 'tel' => $row['tel'], 'delivery_time' => $delivery['delivery_time'], 'order_count' => $order_count, 'kf_type' => $basic_info['kf_type'], 'kf_ww' => $basic_info['kf_ww'], 'kf_qq' => $basic_info['kf_qq'], 'total_fee' => price_format($row['total_fee'], false), 'handler_return' => $row['handler_return'], 'pay_status' => $row['pay_status'], 'handler' => $row['handler'], 'order_url' => url('user/crowd/detail', array('order_id' => $row['order_id'])));
	}

	return array('list' => array_values($arr), 'totalpage' => ceil($total / $size));
}

function zc_cancel_order($order_id, $user_id = 0)
{
	$sql = 'SELECT user_id, order_id, order_sn , surplus , integral , bonus_id, order_status, shipping_status, pay_status FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE order_id = \'' . $order_id . '\'';
	$order = $GLOBALS['db']->GetRow($sql);

	if (empty($order)) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['order_exist']);
		return false;
	}

	if ((0 < $user_id) && ($order['user_id'] != $user_id)) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
		return false;
	}

	if (($order['order_status'] != OS_UNCONFIRMED) && ($order['order_status'] != OS_CONFIRMED)) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['current_os_not_unconfirmed']);
		return false;
	}

	if ($order['order_status'] == OS_CONFIRMED) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['current_os_already_confirmed']);
		return false;
	}

	if ($order['shipping_status'] != SS_UNSHIPPED) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['current_ss_not_cancel']);
		return false;
	}

	if ($order['pay_status'] != PS_UNPAYED) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['current_ps_not_cancel']);
		return false;
	}

	$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' SET order_status = \'' . OS_CANCELED . '\' WHERE order_id = \'' . $order_id . '\'';

	if ($GLOBALS['db']->query($sql)) {
		order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, l('buyer_cancel'), 'buyer');
		if ((0 < $order['user_id']) && (0 < $order['surplus'])) {
			$change_desc = sprintf(l('return_surplus_on_cancel'), $order['order_sn']);
			log_account_change($order['user_id'], $order['surplus'], 0, 0, 0, $change_desc);
		}

		if ((0 < $order['user_id']) && (0 < $order['integral'])) {
			$change_desc = sprintf(l('return_integral_on_cancel'), $order['order_sn']);
			log_account_change($order['user_id'], 0, 0, 0, $order['integral'], $change_desc);
		}

		if ((0 < $order['user_id']) && (0 < $order['bonus_id'])) {
			change_user_bonus($order['bonus_id'], $order['order_id'], false);
		}

		$arr = array('bonus_id' => 0, 'bonus' => 0, 'integral' => 0, 'integral_money' => 0, 'surplus' => 0);
		return true;
	}
	else {
		exit($GLOBALS['db']->errorMsg());
	}
}

function zc_get_order_detail($order_id, $user_id = 0)
{
	include_once BASE_PATH . 'helpers/order_helper.php';
	$order_id = intval($order_id);

	if ($order_id <= 0) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['invalid_order_id']);
		return false;
	}

	$order = zc_order_info($order_id);
	if ((0 < $user_id) && ($user_id != $order['user_id'])) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
		return false;
	}

	if (!empty($order['invoice_no'])) {
		$shipping_code = $GLOBALS['db']->GetOne('SELECT shipping_code FROM ' . $GLOBALS['ecs']->table('shipping') . ' WHERE shipping_id = \'' . $order['shipping_id'] . '\'');
		$plugin = ADDONS_PATH . 'shipping/' . $shipping_code . '.php';

		if (file_exists($plugin)) {
			include_once $plugin;
			$shipping = new $shipping_code();
			$order['invoice_no'] = $shipping->query($order['invoice_no']);
		}
	}

	if ($order['order_status'] == OS_UNCONFIRMED) {
		$order['allow_update_address'] = 1;
	}
	else {
		$order['allow_update_address'] = 0;
	}

	$order['exist_real_goods'] = exist_real_goods($order_id);
	if (($order['pay_status'] == PS_UNPAYED) || (($order['pay_status'] == PS_PAYED_PART) && (($order['order_status'] == OS_UNCONFIRMED) || ($order['order_status'] == OS_CONFIRMED)))) {
		$payment_info = array();
		$payment_info = payment_info($order['pay_id']);

		if ($payment_info === false) {
			$order['pay_online'] = '';
		}
		else if (substr($payment_info['pay_code'], 0, 4) == 'pay_') {
			$order['pay_online'] = '';
		}
		else {
			$payment = unserialize_config($payment_info['pay_config']);
			$order['log_id'] = get_paylog_id($order['order_id'], $pay_type = PAY_ORDER);
			$order['user_name'] = $_SESSION['user_name'];
			$order['pay_desc'] = $payment_info['pay_desc'];
			$order['pay_online'] = '';

			if (file_exists(ADDONS_PATH . 'payment/' . $payment_info['pay_code'] . '.php')) {
				include_once ADDONS_PATH . 'payment/' . $payment_info['pay_code'] . '.php';
				$pay_obj = new $payment_info['pay_code']();
				$order['pay_online'] = $pay_obj->get_code($order, $payment);
			}
		}
	}
	else {
		$order['pay_online'] = '';
	}

	($order['shipping_id'] == -1) && $order['shipping_name'] = $GLOBALS['_LANG']['shipping_not_need'];
	$order['how_oos_name'] = $order['how_oos'];
	$order['how_surplus_name'] = $order['how_surplus'];

	if ($order['pay_status'] != PS_UNPAYED) {
		$virtual_goods = get_virtual_goods($order_id, true);
		$virtual_card = array();

		foreach ($virtual_goods as $code => $goods_list) {
			if ($code == 'virtual_card') {
				foreach ($goods_list as $goods) {
					if ($info = virtual_card_result($order['order_sn'], $goods)) {
						$virtual_card[] = array('goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info);
					}
				}
			}

			if ($code == 'package_buy') {
				foreach ($goods_list as $goods) {
					$sql = 'SELECT g.goods_id FROM ' . $GLOBALS['ecs']->table('package_goods') . ' AS pg, ' . $GLOBALS['ecs']->table('goods') . ' AS g ' . 'WHERE pg.goods_id = g.goods_id AND pg.package_id = \'' . $goods['goods_id'] . '\' AND extension_code = \'virtual_card\'';
					$vcard_arr = $GLOBALS['db']->getAll($sql);

					foreach ($vcard_arr as $val) {
						if ($info = virtual_card_result($order['order_sn'], $val)) {
							$virtual_card[] = array('goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $info);
						}
					}
				}
			}
		}

		$var_card = deleterepeat($virtual_card);
		$GLOBALS['smarty']->assign('virtual_card', $var_card);
	}

	if ((0 < $order['confirm_time']) && (($order['order_status'] == OS_CONFIRMED) || ($order['order_status'] == OS_SPLITED) || ($order['order_status'] == OS_SPLITING_PART))) {
		$order['confirm_time'] = sprintf($GLOBALS['_LANG']['confirm_time'], local_date($GLOBALS['_CFG']['time_format'], $order['confirm_time']));
	}
	else {
		$order['confirm_time'] = '';
	}

	if (0 < $order['pay_time']) {
		$order['pay_time'] = $order['pay_time'];
	}
	else {
		$order['pay_time'] = '';
	}

	if ((0 < $order['shipping_time']) && in_array($order['shipping_status'], array(SS_SHIPPED, SS_RECEIVED))) {
		$order['shipping_time'] = sprintf($GLOBALS['_LANG']['shipping_time'], local_date($GLOBALS['_CFG']['time_format'], $order['shipping_time']));
	}
	else {
		$order['shipping_time'] = '';
	}

	$order['add_time'] = $order['add_time'];
	return $order;
}

function zc_order_info($order_id, $order_sn = '')
{
	$order_id = intval($order_id);
	$now = time();
	$sql = 'select zp.id,zp.title,zp.title_img,zp.amount,zp.join_money,zp.join_num,(zp.end_time-unix_timestamp(now())) as shenyu_time,zg.content,zg.price,oi.consignee,oi.pay_name,oi.* ,(oi.goods_amount + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee + oi.tax - oi.discount) AS total_fee  from ' . $GLOBALS['ecs']->table('zc_goods') . ' as zg left join ' . $GLOBALS['ecs']->table('zc_project') . " as zp on zg.pid=zp.id\r\n\t\t\tleft join " . $GLOBALS['ecs']->table('order_info') . 'as oi on zg.id=oi.zc_goods_id where oi.order_id=\'' . $order_id . '\'  and oi.is_zc_order=1 ';
	$order = $GLOBALS['db']->getRow($sql);
	$order['shenyu_time'] = ceil($order['shenyu_time'] / 3600 / 24);
	$order['baifen_bi'] = round($order['join_money'] / $order['amount'], 2) * 100;
	$order['add_time'] = date('Y-m-d H:i:s', $order['add_time']);
	$order['title_img'] = get_image_path($order['title_img']);
	$order['url'] = url('crowd_funding/index/info', array('id' => $order['id']));

	if (0 < $order['pay_time']) {
		$order['pay_time'] = date('Y-m-d H:i:s', $order['pay_time']);
	}

	$os = l('os');

	if ($order) {
		$order['formated_goods_amount'] = price_format($order['goods_amount'], false);
		$order['formated_discount'] = price_format($order['discount'], false);
		$order['formated_tax'] = price_format($order['tax'], false);
		$order['formated_shipping_fee'] = price_format($order['shipping_fee'], false);
		$order['formated_insure_fee'] = price_format($order['insure_fee'], false);
		$order['formated_pay_fee'] = price_format($order['pay_fee'], false);
		$order['formated_pack_fee'] = price_format($order['pack_fee'], false);
		$order['formated_card_fee'] = price_format($order['card_fee'], false);
		$order['formated_total_fee'] = price_format($order['total_fee'], false);
		$order['formated_money_paid'] = price_format($order['money_paid'], false);
		$order['formated_bonus'] = price_format($order['bonus'], false);
		$order['formated_integral_money'] = price_format($order['integral_money'], false);
		$order['formated_surplus'] = price_format($order['surplus'], false);
		$order['formated_order_amount'] = price_format(abs($order['order_amount']), false);
		$order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
	}

	return $order;
}

function plan_min_price($pid = 0)
{
	$sql = 'SELECT min(price) as price ' . 'FROM ' . $GLOBALS['ecs']->table('zc_goods') . 'WHERE  pid = \'' . $pid . '\'  ';
	$res = $GLOBALS['db']->getRow($sql);
	return $res['price'];
}

function get_coupons_lists($num = 2, $page = 1, $status = 0)
{
	$time = time();
	$uid = $_SESSION['user_id'];

	if (empty($uid)) {
		show_message('请登录', '', url('user'), true);
	}

	if ($status == 0) {
		$where = 'where cu.is_use = 0  and cu.user_id = \'' . $uid . '\' and c.cou_end_time>\'' . $time . '\' ';
	}
	else if ($status == 1) {
		$where = 'where cu.is_use = 1  and cu.user_id = \'' . $uid . '\' ';
	}
	else if ($status == 2) {
		$where = 'where  \'' . $time . '\'>c.cou_end_time and  cu.is_use = 0  and cu.user_id = \'' . $uid . '\'';
	}

	$sql = 'select * from ' . $GLOBALS['ecs']->table('coupons') . ' AS  c  right join  ' . $GLOBALS['ecs']->table('coupons_user') . ' AS cu  on  c.cou_id = cu.cou_id ' . $where . ' ';
	$total = (is_array($sql) ? count($sql) : 0);
	$start = ($page - 1) * $num;
	$sqls = 'select * from ' . $GLOBALS['ecs']->table('coupons') . ' AS  c  right join  ' . $GLOBALS['ecs']->table('coupons_user') . ' AS cu  on  c.cou_id = cu.cou_id ' . $where . '  limit ' . $start . ',' . $num;
	$tab = $GLOBALS['db']->getAll($sqls);

	if ($status == 0) {
		foreach ($tab as &$v) {
			$v['begintime'] = date('Y-m-d', $v['cou_start_time']);
			$v['endtime'] = date('Y-m-d', $v['cou_end_time']);
			$v['img'] = 'images/coupons_default.png';
		}
	}
	else if ($status == 1) {
		foreach ($tab as &$v) {
			$v['begintime'] = date('Y-m-d', $v['cou_start_time']);
			$v['endtime'] = date('Y-m-d', $v['cou_end_time']);
			$v['img'] = 'images/coupons_default.png';
		}
	}
	else if ($status == 2) {
		foreach ($tab as &$v) {
			$v['begintime'] = date('Y-m-d', $v['cou_start_time']);
			$v['endtime'] = date('Y-m-d', $v['cou_end_time']);
			$v['img'] = 'images/coupons_default.png';
		}
	}

	$tab_list = array('tab' => $tab, 'totalpage' => ceil($total / $num));
	return $tab_list;
}

function get_user_coupons_list($user_id = '', $is_use = false, $total = false, $cart_goods = false, $user = true)
{
	$time = gmtime();
	if ($is_use && $total && $cart_goods) {
		foreach ($cart_goods as $k => $v) {
			$res[$v['ru_id']][] = $v;
		}

		foreach ($res as $k => $v) {
			foreach ($v as $m => $n) {
				$store_total[$k] += $n['goods_price'] * $n['goods_number'];
			}
		}

		foreach ($cart_goods as $k => $v) {
			foreach ($store_total as $m => $n) {
				$where = ' WHERE cu.is_use=0 AND c.cou_end_time > ' . $time . ' AND ' . $time . '>c.cou_start_time AND ' . $n . ' >= c.cou_man AND cu.user_id =\'' . $user_id . "'\r\n                        AND (c.cou_goods =0 OR FIND_IN_SET('" . $v['goods_id'] . '\',c.cou_goods)) AND c.ru_id=\'' . $v['ru_id'] . '\'';
				$sql = ' SELECT c.*,cu.*,o.order_sn,o.add_time FROM ' . $GLOBALS['ecs']->table('coupons_user') . ' cu LEFT JOIN ' . $GLOBALS['ecs']->table('coupons') . ' c ON c.cou_id=cu.cou_id LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . ' o ON cu.order_id=o.order_id ' . $where . ' ';
				$arrr[] = $GLOBALS['db']->getAll($sql);
			}
		}

		foreach ($arrr as $k => $v) {
			foreach ($v as $m => $n) {
				$arr[$n['uc_id']] = $n;
			}
		}

		return $arr;
	}
	else {
		if (!empty($user_id) && $user) {
			$where = ' WHERE cu.user_id IN(' . $user_id . ')';
		}
		else if (!empty($user_id)) {
			$where = ' WHERE cu.user_id IN(' . $user_id . ') GROUP BY c.cou_id';
		}

		$res = $GLOBALS['db']->getAll(' SELECT c.*,cu.*,o.order_sn,o.add_time FROM ' . $GLOBALS['ecs']->table('coupons_user') . ' cu LEFT JOIN ' . $GLOBALS['ecs']->table('coupons') . ' c ON c.cou_id=cu.cou_id LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . ' o ON cu.order_id=o.order_id ' . $where . ' ');
		return $res;
	}
}

function get_coupons_type_info($cou_type = '1,2,3,4', $ru_id = '')
{
	$where = '';

	if (!empty($ru_id)) {
		$where .= ' AND ru_id = ' . $ru_id . ' ';
	}

	$cou_name = $_REQUEST['cou_name'];

	if (!empty($cou_name)) {
		$where .= ' AND cou_name like \'%' . $cou_name . '%\' ';
	}

	$result = get_filter();

	if ($result === false) {
		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'cou_id' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
		$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('coupons') . 'WHERE cou_type IN(' . $cou_type . ') ' . $where;
		$filter['record_count'] = $GLOBALS['db']->getOne($sql);
		$filter = page_and_size($filter);
		$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('coupons') . 'WHERE cou_type IN(' . $cou_type . ') ' . $where . '  ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'];
		set_filter($filter, $sql);
	}
	else {
		$sql = $result['sql'];
		$filter = $result['filter'];
	}

	$arr = array();
	$res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$row['cou_type'] = $row['cou_type'] == 1 ? '注册赠券' : ($row['cou_type'] == 2 ? '购物赠券' : ($row['cou_type'] == 3 ? '全场赠券' : ($row['cou_type'] == 4 ? '会员赠券' : '')));
		$row['user_name'] = get_shop_name($row['ru_id'], 1);
		$row['cou_start_time'] = local_date('Y-m-d', $row['cou_start_time']);
		$row['cou_end_time'] = local_date('Y-m-d', $row['cou_end_time']);
		$row['cou_is_use'] = $row['cou_is_use'] == 0 ? '未使用' : '<span style=color:red;>已使用</span>';
		$row['cou_is_time'] = gmtime() < local_strtotime($row['cou_end_time']) ? '未过期' : '<span style=color:red;>已过期</span>';
		$arr[] = $row;
	}

	$arr = array('item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function get_return_detail($ret_id, $user_id = 0)
{
	$ret_id = intval($ret_id);

	if ($ret_id <= 0) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['invalid_order_id']);
		return false;
	}

	$order = return_order_info($ret_id);
	return $order;
}

function cancel_return($ret_id, $user_id = 0)
{
	$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('order_return') . ' WHERE ret_id = \'' . $ret_id . '\'';
	$order = $GLOBALS['db']->GetRow($sql);

	if (empty($order)) {
		$GLOBALS['err']->add(l('return_exist'));
		return false;
	}

	if ((0 < $user_id) && ($order['user_id'] != $user_id)) {
		$GLOBALS['err']->add(l('no_priv'));
		return false;
	}

	if (($order['return_status'] != RF_APPLICATION) && ($order['refound_status'] != FF_NOREFOUND)) {
		$GLOBALS['err']->add(l('return_not_unconfirmed'));
		return false;
	}

	if ($order['return_status'] == RF_RECEIVE) {
		$GLOBALS['err']->add(l('current_os_already_receive'));
		return false;
	}

	if (($order['return_status'] == RF_SWAPPED_OUT_SINGLE) || ($order['return_status'] == RF_SWAPPED_OUT)) {
		$GLOBALS['err']->add(l('already_out_goods'));
		return false;
	}

	if ($order['refound_status'] == FF_REFOUND) {
		$GLOBALS['err']->add(l('have_refound'));
		return false;
	}

	$sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('order_return') . ' WHERE ret_id =' . $ret_id;

	if ($GLOBALS['db']->query($sql)) {
		$sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('return_goods') . ' WHERE rec_id =' . $order['rec_id'];
		$GLOBALS['db']->query($sql);
		$sql = 'select img_file from ' . $GLOBALS['ecs']->table('return_images') . ' where user_id = \'' . $_SESSION['user_id'] . '\' and rec_id = \'' . $order['rec_id'] . '\'';
		$img_list = $GLOBALS['db']->getAll($sql);

		if ($img_list) {
			foreach ($img_list as $key => $row) {
				@unlink(ROOT_PATH . $row['img_file']);
			}

			$sql = 'delete from ' . $GLOBALS['ecs']->table('return_images') . ' where user_id = \'' . $_SESSION['user_id'] . '\' and rec_id = \'' . $order['rec_id'] . '\'';
			$GLOBALS['db']->query($sql);
		}

		$sql = 'delete from ' . $GLOBALS['ecs']->table('order_return_extend') . ' where ret_id = \'' . $ret_id . '\' ';
		$GLOBALS['db']->query($sql);
		return_action($ret_id, '取消', '', '', '买家', '');
		return true;
	}
	else {
		exit($GLOBALS['db']->errorMsg());
	}
}

function get_count_return()
{
	$sql = 'select count(*) from ' . $GLOBALS['ecs']->table('order_return') . ' WHERE user_id = ' . $_SESSION['user_id'];
	$count = $GLOBALS['db']->getRow($sql);
	return $count['count(*)'];
}

function get_all_return_order($order_id = 0)
{
	if (!empty($order_id) && !is_int($order_id)) {
		exit(json_encode(array('error' => 1, 'content' => '订单号不存在')));
	}

	$where = '';

	if (0 < $order_id) {
		$where = ' AND o.order_id = ' . $order_id;
	}

	$sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('shop_config') . ' WHERE code ="sign"';
	$sign_time = $GLOBALS['db']->getOne($sql);
	$time = gmtime();
	$log_time = $time - ($sign_time * 24 * 3600);
	$sql = "SELECT o.order_id, o.order_sn, o.add_time, o.extension_id\r\n\t\t\tFROM " . $GLOBALS['ecs']->table('order_info') . ' AS o' . ' WHERE o.user_id = \'' . $_SESSION['user_id'] . '\' AND o.order_status <> ' . OS_CANCELED . ' AND o.pay_status = ' . PS_PAYED . ' AND o.pay_time > ' . $log_time . ' AND o.pay_time < ' . $time . $where . ' group by o.order_id';
	$order = $GLOBALS['db']->query($sql);

	foreach ($order as $key => $val) {
		$order[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
		$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('order_return') . ' WHERE rec_id = ' . $val['extension_id'];
		$return_count = $GLOBALS['db']->getOne($sql);

		if (0 < $return_count) {
			continue;
		}

		$sql = 'SELECT og.order_id, og.goods_name, og.goods_number, og.rec_id, g.goods_id, IF(og.extension_code = \'package_buy\', (SELECT activity_thumb FROM ' . $GLOBALS['ecs']->table('goods_activity') . " WHERE act_id = g.goods_id),g.goods_thumb) as goods_thumb\r\n\t\t\tFROM " . $GLOBALS['ecs']->table('order_goods') . ' AS og' . ' LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON  og.goods_id = g.goods_id' . ' WHERE og.order_id = ' . $val['order_id'];
		$goods = $GLOBALS['db']->query($sql);

		foreach ($goods as $gkey => $gval) {
			$goods[$gkey]['goods_thumb'] = get_image_path($gval['goods_thumb']);
			$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('order_return') . ' WHERE rec_id = ' . $gval['rec_id'];
			$return_count = $GLOBALS['db']->getOne($sql);

			if (0 < $return_count) {
				$goods[$gkey]['apply_return_url'] = NULL;
			}
			else {
				$goods[$gkey]['apply_return_url'] = url('user/refound/apply_return', array('order_goods_id' => $gval['rec_id']));
			}
		}

		$order[$key]['goods_list'] = $goods;
		$order[$key]['order_url'] = url('user/order/detail', array('order_id' => $val['order_id']));
	}

	foreach ($order as $key => $val) {
		$is_return_order = false;

		foreach ($val['goods_list'] as $gkey => $gval) {
			if (!empty($gval['apply_return_url'])) {
				$is_return_order = true;
			}
			else {
				unset($order[$key]['goods_list'][$gkey]);
			}
		}

		if (!$is_return_order) {
			unset($order[$key]);
		}
	}

	return $order;
}

function get_order_goods_info($id)
{
	$sql = 'SELECT og.goods_name, og.goods_number, g.goods_thumb, og.goods_attr, og.goods_price, IF(og.extension_code = \'package_buy\', (SELECT activity_thumb FROM ' . $GLOBALS['ecs']->table('goods_activity') . " WHERE act_id = g.goods_id),g.goods_thumb) as goods_thumb\r\n\t\t\tFROM " . $GLOBALS['ecs']->table('order_goods') . ' AS og' . ' LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON  og.goods_id = g.goods_id' . ' WHERE og.rec_id = ' . $id;
	$goods = $GLOBALS['db']->getRow($sql);
	$goods['goods_thumb'] = get_image_path($goods['goods_thumb']);
	return $goods;
}


?>
