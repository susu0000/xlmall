<?php
//zend by QQ:2172298892 瑾梦网络
function fromat_coupons($cou_data)
{
	$time = gmtime();

	foreach ($cou_data as $k => $v) {
		if (!isset($v['cou_surplus'])) {
			$cou_data[$k]['cou_surplus'] = 100;
		}

		if (!empty($v['cou_goods'])) {
			$cou_data[$k]['cou_goods_name'] = $GLOBALS['db']->getAll('SELECT goods_id,goods_name,goods_thumb FROM ' . $GLOBALS['ecs']->table('goods') . ' WHERE goods_id IN(' . $v['cou_goods'] . ')');
		}

		if (!empty($v['cou_ok_user'])) {
			$cou_data[$k]['cou_ok_user_name'] = $GLOBALS['db']->getOne('SELECT group_concat(rank_name)  FROM ' . $GLOBALS['ecs']->table('user_rank') . ' WHERE rank_id IN(' . $v['cou_ok_user'] . ')');
		}

		if ($v['ru_id']) {
			$store_info = get_shop_name($v['ru_id']);
			$cou_data[$k]['store_name'] = '限' . $store_info['shop_name'] . '可用';
		}
		else {
			$cou_data[$k]['store_name'] = '全平台可用';
		}

		$cou_data[$k]['cou_start_time_format'] = local_date('Y/m/d', $v['cou_start_time']);
		$cou_data[$k]['cou_end_time_format'] = local_date('Y/m/d', $v['cou_end_time']);

		if ($v['cou_end_time'] < $time) {
			$cou_data[$k]['is_overdue'] = 1;
		}
		else {
			$cou_data[$k]['is_overdue'] = 0;
		}

		$cou_data[$k]['cou_type_name'] = $v['cou_type'] == 3 ? '全场券' : ($v['cou_type'] == 4 ? '会员券' : '未知');

		if ($_SESSION['user_id']) {
			$r = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('coupons_user') . ' WHERE cou_id=\'' . $v['cou_id'] . '\' AND user_id =\'' . $_SESSION['user_id'] . '\'');
			$cou_data[$k]['cou_is_receive'] = $r ? 1 : 0;
		}
	}

	return $cou_data;
}

function get_coupons_list($num = 2, $page = 1, $status = 0)
{
	$time = gmtime();

	if ($status == 0) {
		$condition = 3;
	}
	else if ($status == 2) {
		$condition = 4;
	}

	$res = $GLOBALS['db']->getAll('select * from ' . $GLOBALS['ecs']->table('coupons') . 'where  cou_type =  \'' . $condition . '\' and  ' . $time . '<cou_end_time and ' . $time . '>cou_start_time');
	$total = (is_array($res) ? count($res) : 0);
	$start = ($page - 1) * $num;
	$sql = 'select * from ' . $GLOBALS['ecs']->table('coupons') . 'where  cou_type = \'' . $condition . '\'  and  ' . $time . '< cou_end_time and  ' . $time . ' > cou_start_time' . ' limit ' . $start . ',' . $num;
	$tab = $GLOBALS['db']->getAll($sql);

	if (status == 0) {
		foreach ($tab as &$v) {
			$v['begintime'] = date('Y-m-d H:i:s', $v['cou_start_time']);
			$v['endtime'] = date('Y-m-d H:i:s', $v['cou_end_time']);
			$v['img'] = 'images/coupons_default.png';
			$cou_goods = explode(',', $v['cou_goods']);

			foreach ($cou_goods as $k => $i) {
				$sql2 = 'select * from ' . $GLOBALS['ecs']->table('goods') . 'where goods_id = \'' . $i . '\'';
				$tab2 = $GLOBALS['db']->getAll($sql2);
				$cou_goods[$k] = $tab2;
			}

			$v['goodsInfro'] = $cou_goods;
		}
	}
	else if (status == 2) {
		foreach ($tab as &$vs) {
			$vs['begintime'] = date('Y-m-d H:i:s', $v['cou_start_time']);
			$vs['endtime'] = date('Y-m-d H:i:s', $v['cou_end_time']);
			$vs['img'] = 'images/coupons_default.png';
		}
	}

	$tab_list = array('tab' => $tab, 'totalpage' => ceil($total / $num));
	return $tab_list;
}


?>
