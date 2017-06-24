<?php
//zend by QQ:2172298892 瑾梦网络
function team_categories()
{
	$sql = 'SELECT c.id,c.name,c.parent_id,c.sort_order,c.status FROM {pre}team_category as c ' . 'WHERE c.parent_id = 0 AND c.status = 1 ORDER BY c.sort_order ASC, c.id ASC';
	$res = $GLOBALS['db']->getAll($sql);

	foreach ($res as $key => $row) {
		$cat_arr[$key]['id'] = $row['id'];
		$cat_arr[$key]['name'] = $row['name'];
	}

	return $cat_arr;
}

function team_get_child_tree($id = 0)
{
	$three_arr = array();
	$sql = 'SELECT count(*) FROM {pre}team_category WHERE parent_id = \'' . $id . '\' AND status = 1 ';
	if ($GLOBALS['db']->getOne($sql) || ($id == 0)) {
		$child_sql = 'SELECT c.id,c.name,c.parent_id,c.sort_order,c.status,c.tc_img ' . 'FROM {pre}team_category as c ' . ' WHERE c.parent_id = \'' . $id . '\' AND c.status = 1 GROUP BY c.id ORDER BY c.sort_order ASC, c.id ASC';
		$res = $GLOBALS['db']->getAll($child_sql);

		foreach ($res as $row) {
			if ($row['status']) {
				$three_arr[$row['id']]['id'] = $row['id'];
				$three_arr[$row['id']]['name'] = $row['name'];
				$three_arr[$row['id']]['tc_img'] = get_image_path($row['tc_img']);
				$three_arr[$row['id']]['url'] = url('team/index/category', array('tc_id' => $row['id']));
			}

			if (isset($row['cat_id']) != NULL) {
				$three_arr[$row['id']]['cat_id'] = $this->team_get_child_tree($row['id']);
			}
		}
	}

	return $three_arr;
}

function team_category_goods($tc_id = 0, $keywords = '', $size = 10, $page = 1, $intro = '', $sort, $order, $brand, $min, $max)
{
	$where .= ' g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.review_status>2 ';

	if (0 < $tc_id) {
		$where .= ' AND  tg.tc_id = ' . $tc_id . ' ';
	}

	if ($intro) {
		switch ($intro) {
		case 'best':
			$where .= ' AND g.is_best = 1 ';
			break;

		case 'new':
			$where .= ' AND g.is_new = 1 ';
			break;

		case 'hot':
			$where .= ' AND g.is_hot = 1 ';
			break;

		case 'promotion':
			$time = gmtime();
			$where .= ' AND g.promote_price > 0 AND g.promote_start_date <= \'' . $time . '\' AND g.promote_end_date >= \'' . $time . '\' ';
			break;

		default:
			$where .= '';
		}
	}

	$leftJoin = '';

	if (0 < $brand) {
		$leftJoin .= 'LEFT JOIN ' . $GLOBALS['ecs']->table('link_brand') . ' AS lb ' . 'ON lb.bid = g.brand_id ';
		$leftJoin .= 'LEFT JOIN ' . $GLOBALS['ecs']->table('merchants_shop_brand') . ' AS msb ' . 'ON msb.bid = lb.bid ';
		$where .= 'AND (g.brand_id = \'' . $brand . '\' OR (lb.brand_id = \'' . $brand . '\' AND g.brand_id = lb.bid AND msb.audit_status = 1)) ';
	}
	else {
		$leftJoin .= 'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ' . 'ON b.brand_id = g.brand_id ';
		$leftJoin .= 'LEFT JOIN ' . $GLOBALS['ecs']->table('link_brand') . ' AS lb ' . 'ON lb.bid = g.brand_id ';
		$leftJoin .= 'LEFT JOIN ' . $GLOBALS['ecs']->table('merchants_shop_brand') . ' AS msb ' . 'ON msb.bid = lb.bid ';
		$where .= 'AND (b.audit_status = 1 OR msb.audit_status = 1) ';
	}

	if (!empty($keywords)) {
		$where .= ' AND (g.goods_name LIKE \'%' . $keywords . '%\' OR g.goods_sn LIKE \'%' . $keywords . '%\' OR g.keywords LIKE \'%' . $keywords . '%\')';
	}

	if (0 < $min) {
		$where .= ' AND  g.shop_price >= ' . $min . ' ';
	}

	if (0 < $max) {
		$where .= ' AND g.shop_price <= ' . $max . ' ';
	}

	if ($sort == 'last_update') {
		$sort = 'g.last_update';
	}

	$arr = array();
	$sql = 'SELECT g.goods_id, g.goods_name, g.shop_price,g.market_price,g.goods_number, g.goods_name_style, g.comments_number, g.sales_volume,g.goods_thumb , g.goods_img,g.model_price, tg.team_price, tg.team_num,tg.limit_num ' . ' FROM {pre}team_goods AS tg ' . 'LEFT JOIN {pre}goods AS g ON tg.goods_id = g.goods_id ' . $leftJoin . ' WHERE ' . $where . ' ORDER BY ' . $sort . ' ' . $order;
	$goods_list = $GLOBALS['db']->getAll($sql);
	$total = (is_array($goods_list) ? count($goods_list) : 0);
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	foreach ($res as $key => $val) {
		$arr[$key]['goods_name'] = $val['goods_name'];
		$arr[$key]['shop_price'] = price_format($val['shop_price']);
		$arr[$key]['market_price'] = price_format($val['market_price']);
		$arr[$key]['goods_number'] = $val['goods_number'];
		$arr[$key]['sales_volume'] = $val['sales_volume'];
		$arr[$key]['goods_img'] = get_image_path($val['goods_img']);
		$arr[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
		$arr[$key]['url'] = url('team/goods/index', array('id' => $val['goods_id']));
		$arr[$key]['team_price'] = price_format($val['team_price']);
		$arr[$key]['team_num'] = $val['team_num'];
		$arr[$key]['limit_num'] = $val['limit_num'];
	}

	return array('list' => array_values($arr), 'totalpage' => ceil($total / $size));
}

function team_goods($size = 10, $page = 1, $type = 'limit_num')
{
	$where .= ' g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.review_status>2 ';

	switch ($type) {
	case 'limit_num':
		$type = '  ORDER BY tg.limit_num DESC';
		break;

	case 'is_new':
		$type = 'AND g.is_new = 1 ORDER BY g.add_time DESC';
		break;

	case 'is_hot':
		$type = 'AND g.is_hot = 1';
		break;

	case 'is_best':
		$type = 'AND g.is_best = 1';
		break;

	default:
		$type = '1';
	}

	$arr = array();
	$sql = 'SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_name_style, g.comments_number, g.sales_volume, g.market_price, g.goods_thumb , g.goods_img, tg.team_price, tg.team_num,tg.limit_num' . ' FROM {pre}team_goods AS tg ' . 'LEFT JOIN {pre}goods AS g ON tg.goods_id = g.goods_id ' . 'WHERE ' . $where . ' ' . $type;
	$goods_list = $GLOBALS['db']->getAll($sql);
	$total = (is_array($goods_list) ? count($goods_list) : 0);
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	foreach ($res as $key => $val) {
		if (($key < 3) && ($page < 2)) {
			$arr[$key]['key'] = $key + 1;
		}

		$arr[$key]['goods_name'] = $val['goods_name'];
		$arr[$key]['shop_price'] = price_format($val['shop_price']);
		$arr[$key]['goods_img'] = get_image_path($val['goods_img']);
		$arr[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
		$arr[$key]['url'] = url('team/goods/index', array('id' => $val['goods_id']));
		$arr[$key]['team_price'] = price_format($val['team_price']);
		$arr[$key]['team_num'] = $val['team_num'];
		$arr[$key]['limit_num'] = $val['limit_num'];
	}

	return array('list' => array_values($arr), 'totalpage' => ceil($total / $size));
}

function get_good_comment($id, $rank = NULL, $hasgoods = 0, $start = 0, $size = 10)
{
	if (empty($id)) {
		return false;
	}

	$where = '';
	$rank = (empty($rank) && ($rank !== 0) ? '' : intval($rank));

	if ($rank == 4) {
		$where = ' AND  comment_rank in (4, 5)';
	}
	else if ($rank == 2) {
		$where = ' AND  comment_rank in (2, 3)';
	}
	else if ($rank === 0) {
		$where = ' AND  comment_rank in (0, 1)';
	}
	else if ($rank == 1) {
		$where = ' AND  comment_rank in (0, 1)';
	}
	else if ($rank == 5) {
		$where = ' AND  comment_rank in (0, 1, 2, 3, 4,5)';
	}

	$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') . ' WHERE id_value = \'' . $id . '\' and comment_type = 0 and status = 1 and parent_id = 0 ' . $where . ' ORDER BY comment_id DESC LIMIT ' . $start . ', ' . $size;
	$comment = $GLOBALS['db']->getAll($sql);
	$arr = array();

	if ($comment) {
		$ids = '';

		foreach ($comment as $key => $row) {
			$ids .= ($ids ? ',' . $row['comment_id'] : $row['comment_id']);
			$arr[$row['comment_id']]['id'] = $row['comment_id'];
			$arr[$row['comment_id']]['email'] = $row['email'];
			$arr[$row['comment_id']]['content'] = str_replace('\\r\\n', '<br />', $row['content']);
			$arr[$row['comment_id']]['content'] = nl2br(str_replace('\\n', '<br />', $arr[$row['comment_id']]['content']));
			$arr[$row['comment_id']]['rank'] = $row['comment_rank'];
			$arr[$row['comment_id']]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
			$user_nick = get_user_default($row['user_id']);
			$arr[$row['comment_id']]['username'] = encrypt_username($user_nick['nick_name']);
			$arr[$row['comment_id']]['headerimg'] = $user_nick['user_picture'];
			if ($row['order_id'] && $hasgoods) {
				$sql = 'SELECT o.goods_id, o.goods_name, o.goods_attr, g.goods_img FROM ' . $GLOBALS['ecs']->table('order_goods') . ' o LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' g ON o.goods_id = g.goods_id WHERE o.order_id = \'' . $row['order_id'] . '\' ORDER BY rec_id DESC';
				$goods = $GLOBALS['db']->getAll($sql);

				if ($goods) {
					foreach ($goods as $k => $v) {
						$goods[$k]['goods_img'] = get_image_path($v['goods_img']);
						$goods[$k]['goods_attr'] = str_replace('\\r\\n', '<br />', $v['goods_attr']);
					}
				}

				$arr[$row['comment_id']]['goods'] = $goods;
			}

			$sql = 'SELECT img_thumb FROM {pre}comment_img WHERE comment_id = ' . $row['comment_id'];
			$comment_thumb = $GLOBALS['db']->getCol($sql);

			if (0 < count($comment_thumb)) {
				foreach ($comment_thumb as $k => $v) {
					$comment_thumb[$k] = get_image_path($v);
				}

				$arr[$row['comment_id']]['thumb'] = $comment_thumb;
			}
			else {
				$arr[$row['comment_id']]['thumb'] = 0;
			}
		}

		if ($ids) {
			$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') . ' WHERE parent_id IN( ' . $ids . ' )';
			$res = $GLOBALS['db']->query($sql);

			foreach ($res as $row) {
				$arr[$row['parent_id']]['re_content'] = nl2br(str_replace('\\n', '<br />', htmlspecialchars($row['content'])));
				$arr[$row['parent_id']]['re_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
				$arr[$row['parent_id']]['re_email'] = $row['email'];
				$arr[$row['parent_id']]['re_username'] = $row['user_name'];
			}
		}

		$arr = array_values($arr);
	}

	return $arr;
}

function get_good_comment_as($id, $rank = NULL, $hasgoods = 0, $start = 0, $size = 10)
{
	if (empty($id)) {
		return false;
	}

	$where = '';
	$rank = (empty($rank) && ($rank !== 0) ? '' : intval($rank));

	if ($rank == 4) {
		$where = ' AND  comment_rank in (4, 5)';
	}
	else if ($rank == 2) {
		$where = ' AND  comment_rank in (2, 3)';
	}
	else if ($rank === 0) {
		$where = ' AND  comment_rank in (0, 1)';
	}
	else if ($rank == 1) {
		$where = ' AND  comment_rank in (0, 1)';
	}
	else if ($rank == 5) {
		$where = ' AND  comment_rank in (0, 1, 2, 3, 4,5)';
	}

	$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') . ' WHERE id_value = \'' . $id . '\' and comment_type = 0 and status = 1 and parent_id = 0 ' . $where . ' ORDER BY comment_id DESC LIMIT ' . $start . ', ' . $size;
	$comment = $GLOBALS['db']->getAll($sql);
	$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') . ' WHERE id_value = \'' . $id . '\' and comment_type = 0 and status = 1 and parent_id = 0 ' . $where;
	$max = $GLOBALS['db']->getAll($sql);
	$max = ceil(count($max) / $size);
	$arr = array();

	if ($comment) {
		$ids = '';

		foreach ($comment as $key => $row) {
			$ids .= ($ids ? ',' . $row['comment_id'] : $row['comment_id']);
			$arr[$row['comment_id']]['id'] = $row['comment_id'];
			$arr[$row['comment_id']]['email'] = $row['email'];
			$users = get_user_default($row['user_id']);
			$arr[$row['comment_id']]['username'] = encrypt_username($users['nick_name']);
			$arr[$row['comment_id']]['user_picture'] = get_image_path($users['user_picture']);
			$arr[$row['comment_id']]['content'] = str_replace('\\r\\n', '<br />', $row['content']);
			$arr[$row['comment_id']]['content'] = nl2br(str_replace('\\n', '<br />', $arr[$row['comment_id']]['content']));
			$arr[$row['comment_id']]['rank'] = $row['comment_rank'];
			$arr[$row['comment_id']]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
			if ($row['order_id'] && $hasgoods) {
				$sql = 'SELECT o.goods_id, o.goods_name, o.goods_attr, g.goods_img FROM ' . $GLOBALS['ecs']->table('order_goods') . ' o LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' g ON o.goods_id = g.goods_id WHERE o.order_id = \'' . $row['order_id'] . '\' ORDER BY rec_id DESC';
				$goods = $GLOBALS['db']->getAll($sql);

				if ($goods) {
					foreach ($goods as $k => $v) {
						$goods[$k]['goods_img'] = get_image_path($v['goods_img']);
						$goods[$k]['goods_attr'] = str_replace('\\r\\n', '<br />', $v['goods_attr']);
					}
				}

				$arr[$row['comment_id']]['goods'] = $goods;
			}

			$sql = 'SELECT img_thumb FROM {pre}comment_img WHERE comment_id = ' . $row['comment_id'];
			$comment_thumb = $GLOBALS['db']->getCol($sql);

			if (0 < count($comment_thumb)) {
				foreach ($comment_thumb as $k => $v) {
					$comment_thumb[$k] = get_image_path($v);
				}

				$arr[$row['comment_id']]['thumb'] = $comment_thumb;
			}
			else {
				$arr[$row['comment_id']]['thumb'] = 0;
			}
		}

		if ($ids) {
			$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') . ' WHERE parent_id IN( ' . $ids . ' )';
			$res = $GLOBALS['db']->query($sql);

			foreach ($res as $row) {
				$arr[$row['parent_id']]['re_content'] = nl2br(str_replace('\\n', '<br />', htmlspecialchars($row['content'])));
				$arr[$row['parent_id']]['re_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
				$arr[$row['parent_id']]['re_email'] = $row['email'];
				$arr[$row['parent_id']]['re_username'] = $row['user_name'];
			}
		}

		$arr = array_values($arr);
	}

	return array('arr' => $arr, 'max' => $max);
}

function commentCol($id)
{
	if (empty($id)) {
		return false;
	}

	$sql = 'SELECT count(comment_id) as num FROM {pre}comment WHERE id_value =' . $id . ' and comment_type = 0 and status = 1 and parent_id = 0';
	$arr['all_comment'] = $GLOBALS['db']->getOne($sql);
	$sql = 'SELECT count(comment_id) as num FROM {pre}comment WHERE id_value =' . $id . ' AND  comment_rank in (4, 5) and comment_type = 0 and status = 1 and parent_id = 0 ';
	$arr['good_comment'] = $GLOBALS['db']->getOne($sql);
	$sql = 'SELECT count(comment_id) as num FROM {pre}comment WHERE id_value =' . $id . ' AND  comment_rank in (2, 3) and comment_type = 0 and status = 1 and parent_id = 0 ';
	$arr['in_comment'] = $GLOBALS['db']->getOne($sql);
	$sql = 'SELECT count(comment_id) as num FROM {pre}comment WHERE id_value =' . $id . ' AND  comment_rank in (0, 1) and comment_type = 0 and status = 1 and parent_id = 0 ';
	$arr['rotten_comment'] = $GLOBALS['db']->getOne($sql);
	$sql = 'SELECT count( DISTINCT b.comment_id) as num FROM {pre}comment as a LEFT JOIN {pre}comment_img as b ON a.id_value=b.goods_id WHERE a.id_value =' . $id . ' and a.comment_type = 0 and a.status = 1 and a.parent_id = 0 and b.img_thumb != \'\'';
	$arr['img_comment'] = $GLOBALS['db']->getOne($sql);

	foreach ($arr as $key => $val) {
		$arr[$key] = empty($val) ? 0 : $arr[$key];
	}

	return $arr;
}

function cart_number()
{
	if (!empty($_SESSION['user_id'])) {
		$sess_id = ' user_id = \'' . $_SESSION['user_id'] . '\' ';
	}
	else {
		$sess_id = ' session_id = \'' . real_cart_mac_ip() . '\' ';
	}

	$sql = 'SELECT SUM(goods_number) AS number ' . ' FROM ' . $GLOBALS['ecs']->table('cart') . ' WHERE ' . $sess_id . ' AND rec_type = \'' . CART_GENERAL_GOODS . '\'';
	$row = $GLOBALS['db']->GetRow($sql);

	if ($row) {
		$number = intval($row['number']);
	}
	else {
		$number = 0;
	}

	return $number;
}

function get_goods_attr_ajax($goods_id, $goods_attr, $goods_attr_id)
{
	$arr = array();
	$arr['attr_id'] = '';
	$goods_attr = implode(',', $goods_attr);

	if ($goods_attr) {
		if ($goods_attr_id) {
			$goods_attr_id = implode(',', $goods_attr_id);
			$where = ' AND ga.goods_attr_id IN(' . $goods_attr_id . ')';
		}
		else {
			$where = '';
		}

		$sql = 'SELECT ga.goods_attr_id, ga.attr_id, ga.attr_value  FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS ga' . ' LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON ga.attr_id = a.attr_id ' . ' WHERE ga.attr_id IN(' . $goods_attr . ') AND ga.goods_id = \'' . $goods_id . '\' ' . $where . ' AND a.attr_type > 0 ORDER BY a.sort_order, ga.attr_sort, ga.goods_attr_id';
		$res = $GLOBALS['db']->getAll($sql);

		foreach ($res as $key => $row) {
			$arr[$row['attr_id']][$row['goods_attr_id']] = $row;
			$arr['attr_id'] .= $row['attr_id'] . ',';
		}

		if ($arr['attr_id']) {
			$arr['attr_id'] = substr($arr['attr_id'], 0, -1);
			$arr['attr_id'] = explode(',', $arr['attr_id']);
		}
		else {
			$arr['attr_id'] = array();
		}
	}

	return $arr;
}

function tean_get_final_price($goods_id, $goods_num = '1', $is_spec_price = false, $spec = array(), $warehouse_id = 0, $area_id = 0, $type = 0, $presale = 0, $add_tocart = 1, $show_goods = 0)
{
	$spec_price = 0;
	$warehouse_area['warehouse_id'] = $warehouse_id;
	$warehouse_area['area_id'] = $area_id;

	if ($is_spec_price) {
		if (!empty($spec)) {
			$spec_price = spec_price($spec, $goods_id, $warehouse_area);
		}
	}

	$final_price = '0';
	$sql = 'SELECT mp.user_price, mp.user_price, ' . ' g.promote_start_date, g.promote_end_date' . ' FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' . ' LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ON mp.goods_id = g.goods_id  ' . ' WHERE g.goods_id = \'' . $goods_id . '\'' . ' AND g.is_delete = 0 LIMIT 1';
	$goods = $GLOBALS['db']->getRow($sql);
	$team = dao('team_goods')->field('team_price,team_num,astrict_num')->where(array('goods_id' => $goods_id))->find();
	$final_price = $team['team_price'];
	$final_price += $spec_price;
	return $final_price;
}

function team_goods_info($goods = 0)
{
	$sql = 'SELECT g.*, tg.team_price, tg.team_num,tg.astrict_num FROM ' . $GLOBALS['ecs']->table('team_goods') . 'AS tg LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON tg.goods_id = g.goods_id ' . 'WHERE tg.goods_id = ' . $goods . ' ';
	$goods = $GLOBALS['db']->getRow($sql);
	return $goods;
}

function team_goods_log($goods_id = 0)
{
	$sql = 'select tl.team_id, tl.start_time,o.team_parent_id,g.goods_id,tg.validity_time ,tg.team_num from ' . $GLOBALS['ecs']->table('team_log') . ' as tl LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . ' as o ON tl.team_id = o.team_id LEFT JOIN  ' . $GLOBALS['ecs']->table('goods') . ' as g ON tl.goods_id = g.goods_id LEFT JOIN ' . $GLOBALS['ecs']->table('team_goods') . ' AS tg ON tl.goods_id = tg.goods_id  ' . ' where tl.goods_id = ' . $goods_id . ' and tl.status <1 and tl.is_show = 1 and o.extension_code =\'team_buy\' and o.team_parent_id > 0 and pay_status = 2';
	$result = $GLOBALS['db']->getAll($sql);

	foreach ($result as $key => $vo) {
		$validity_time = $vo['start_time'] + ($vo['validity_time'] * 3600);
		$goods[$key]['team_id'] = $vo['team_id'];
		$goods[$key]['end_time'] = local_date('Y/m/d H:i:s', $vo['start_time'] + ($vo['validity_time'] * 3600));
		$goods[$key]['surplus'] = $vo['team_num'] - surplus_num($vo['team_id']);
		$user_nick = get_user_default($vo['team_parent_id']);
		$goods[$key]['user_name'] = encrypt_username($user_nick['nick_name']);
		$goods[$key]['headerimg'] = $user_nick['user_picture'];

		if ($validity_time <= gmtime()) {
			unset($goods[$key]);
		}
	}

	return $goods;
}

function surplus_num($team_id = 0)
{
	$sql = 'SELECT count(order_id) as num  FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE team_id = \'' . $team_id . '\' AND extension_code = \'team_buy\'  and pay_status = \'' . PS_PAYED . '\' ';
	$res = $GLOBALS['db']->getRow($sql);
	return $res['num'];
}

function my_team_goods($user_id, $type = 1, $page = 1, $size = 10)
{
	switch ($type) {
	case '1':
		$type = '';
		break;

	case '2':
		$type = ' and t.status < 1 and \'' . gmtime() . '\'< (t.start_time+(tg.validity_time*3600)) ';
		break;

	case '3':
		$type = ' and t.status = 1 ';
		break;

	case '4':
		$type = ' and t.status != 1 and \'' . gmtime() . '\'> (t.start_time+(tg.validity_time*3600)) ';
		break;

	default:
		$type = '';
	}

	$sql = 'select o.order_id,o.pay_status, t.team_id,t.start_time,t.status,g.goods_id,g.goods_name,g.goods_thumb,g.goods_img,tg.validity_time,tg.team_num,tg.team_price,tg.limit_num from ' . $GLOBALS['ecs']->table('order_info') . ' as o left join ' . $GLOBALS['ecs']->table('team_log') . ' as t on o.team_id = t.team_id left join ' . $GLOBALS['ecs']->table('goods') . ' as g on t.goods_id = g.goods_id left join ' . $GLOBALS['ecs']->table('team_goods') . ' as tg on g.goods_id = tg.goods_id' . ' where o.user_id =' . $user_id . ' and o.extension_code =\'team_buy\' and o.pay_status = 2 and t.is_show = 1 ' . $type . '  ORDER BY o.add_time DESC ';
	$goods_list = $GLOBALS['db']->getAll($sql);
	$total = (is_array($goods_list) ? count($goods_list) : 0);
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	foreach ($res as $key => $vo) {
		$goods[$key]['id'] = $vo['goods_id'];
		$goods[$key]['team_id'] = $vo['team_id'];
		$goods[$key]['order_id'] = $vo['order_id'];
		$goods[$key]['pay_status'] = $vo['pay_status'];
		$goods[$key]['goods_name'] = $vo['goods_name'];
		$goods[$key]['goods_img'] = get_image_path($vo['goods_img']);
		$goods[$key]['goods_thumb'] = get_image_path($vo['goods_thumb']);
		$goods[$key]['team_num'] = $vo['team_num'];
		$goods[$key]['limit_num'] = surplus_num($vo['team_id']);
		$goods[$key]['team_price'] = $vo['team_price'];
		$goods[$key]['url'] = url('goods/index', array('id' => $vo['goods_id']));
		$goods[$key]['order_url'] = url('user/order/detail', array('order_id' => $vo['order_id']));
		$goods[$key]['team__url'] = url('team/goods/teamwait', array('team_id' => $vo['team_id']));

		if ($vo['status'] == 1) {
			$goods[$key]['status'] = 1;
		}

		$validity_time = $vo['start_time'] + ($vo['validity_time'] * 3600);
		if (($validity_time <= gmtime()) && ($vo['status'] != 1)) {
			$goods[$key]['status'] = 2;
		}
	}

	return array('list' => array_values($goods), 'totalpage' => ceil($total / $size));
}

function get_cart_value($flow_type = 0)
{
	$where = '';

	if (!empty($_SESSION['user_id'])) {
		$c_sess = ' c.user_id = \'' . $_SESSION['user_id'] . '\' ';
	}
	else {
		$c_sess = ' c.session_id = \'' . real_cart_mac_ip() . '\' ';
	}

	$sql = 'SELECT c.rec_id FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON c.goods_id = g.goods_id WHERE ' . $where . ' ' . $c_sess . 'AND rec_type = \'' . $flow_type . '\' order by c.rec_id asc';
	$goods_list = $GLOBALS['db']->getAll($sql);
	$rec_id = '';

	if ($goods_list) {
		foreach ($goods_list as $key => $row) {
			$rec_id .= $row['rec_id'] . ',';
		}

		$rec_id = substr($rec_id, 0, -1);
	}

	return $rec_id;
}

function cart_by_favourable($merchant_goods)
{
	foreach ($merchant_goods as $key => $row) {
		$goods_num = 0;
		$package_goods_num = 0;
		$user_cart_goods = $row['goods_list'];
		$favourable_list = favourable_list($_SESSION['user_rank'], $row['ru_id']);
		$sort_favourable = sort_favourable($favourable_list);

		foreach ($user_cart_goods as $key1 => $row1) {
			$row1['original_price'] = $row1['goods_price'] * $row1['goods_number'];

			if ($row1['extension_code'] == 'package_buy') {
				$package_goods_num++;
			}
			else {
				$goods_num++;
			}

			if (is_dir(APP_TEAM_PATH) == false) {
				if (isset($sort_favourable['by_all']) && ($row1['extension_code'] != 'package_buy')) {
					foreach ($sort_favourable['by_all'] as $key2 => $row2) {
						if ($row1['is_gift'] == 0) {
							$merchant_goods[$key]['new_list'][$row2['act_id']]['act_id'] = $row2['act_id'];
							$merchant_goods[$key]['new_list'][$row2['act_id']]['act_name'] = $row2['act_name'];
							$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type'] = $row2['act_type'];

							switch ($row2['act_type']) {
							case 0:
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满赠';
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = intval($row2['act_type_ext']);
								break;

							case 1:
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满减';
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = number_format($row2['act_type_ext'], 2);
								break;

							case 2:
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '折扣';
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = floatval($row2['act_type_ext'] / 10);
								break;

							default:
								break;
							}

							$merchant_goods[$key]['new_list'][$row2['act_id']]['min_amount'] = $row2['min_amount'];
							$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext'] = intval($row2['act_type_ext']);
							$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_fav_amount'] = cart_favourable_amount($row2);
							$merchant_goods[$key]['new_list'][$row2['act_id']]['available'] = favourable_available($row2);
							$cart_favourable = cart_favourable();
							$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]);
							$merchant_goods[$key]['new_list'][$row2['act_id']]['favourable_used'] = favourable_used($row2, $cart_favourable);
							$merchant_goods[$key]['new_list'][$row2['act_id']]['left_gift_num'] = intval($row2['act_type_ext']) - (empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]));

							if ($row2['gift']) {
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_gift_list'] = $row2['gift'];
							}

							$merchant_goods[$key]['new_list'][$row2['act_id']]['act_goods_list'][$row1['rec_id']] = $row1;
						}
						else {
							$merchant_goods[$key]['new_list'][$row2['act_id']]['act_cart_gift'][$row1['rec_id']] = $row1;
						}

						break;
					}

					continue;
				}

				if (isset($sort_favourable['by_category']) && ($row1['extension_code'] != 'package_buy')) {
					$get_act_range_ext = get_act_range_ext($_SESSION['user_rank'], $row['ru_id'], 1);
					$id_list = array();

					foreach ($get_act_range_ext as $id) {
						$id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0)));
					}

					$cat_id = $GLOBALS['db']->getOne('SELECT cat_id FROM ' . $GLOBALS['ecs']->table('goods') . ' WHERE goods_id = \'' . $row1['goods_id'] . '\' ');
					$favourable_id_list = get_favourable_id($sort_favourable['by_category']);
					if ((in_array(trim($cat_id), $id_list) && ($row1['is_gift'] == 0)) || in_array($row1['is_gift'], $favourable_id_list)) {
						foreach ($sort_favourable['by_category'] as $key2 => $row2) {
							$fav_act_range_ext = array();

							foreach (explode(',', $row2['act_range_ext']) as $id) {
								$fav_act_range_ext = array_merge($fav_act_range_ext, array_keys(cat_list(intval($id), 0)));
							}

							if (($row1['is_gift'] == 0) && in_array($cat_id, $fav_act_range_ext)) {
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_id'] = $row2['act_id'];
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_name'] = $row2['act_name'];
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type'] = $row2['act_type'];

								switch ($row2['act_type']) {
								case 0:
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满赠';
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = intval($row2['act_type_ext']);
									break;

								case 1:
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满减';
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = number_format($row2['act_type_ext'], 2);
									break;

								case 2:
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '折扣';
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = floatval($row2['act_type_ext'] / 10);
									break;

								default:
									break;
								}

								$merchant_goods[$key]['new_list'][$row2['act_id']]['min_amount'] = $row2['min_amount'];
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext'] = intval($row2['act_type_ext']);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_fav_amount'] = cart_favourable_amount($row2);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['available'] = favourable_available($row2);
								$cart_favourable = cart_favourable();
								$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['favourable_used'] = favourable_used($row2, $cart_favourable);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['left_gift_num'] = intval($row2['act_type_ext']) - (empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]));

								if ($row2['gift']) {
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_gift_list'] = $row2['gift'];
								}

								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_goods_list'][$row1['rec_id']] = $row1;
							}

							if ($row1['is_gift'] == $row2['act_id']) {
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_cart_gift'][$row1['rec_id']] = $row1;
							}
						}

						continue;
					}
				}

				if (isset($sort_favourable['by_brand']) && ($row1['extension_code'] != 'package_buy')) {
					$get_act_range_ext = get_act_range_ext($_SESSION['user_rank'], $row['ru_id'], 2);
					$brand_id = $GLOBALS['db']->getOne('SELECT brand_id FROM ' . $GLOBALS['ecs']->table('goods') . ' WHERE goods_id = \'' . $row1['goods_id'] . '\' ');
					$favourable_id_list = get_favourable_id($sort_favourable['by_brand']);
					if ((in_array(trim($brand_id), $get_act_range_ext) && ($row1['is_gift'] == 0)) || in_array($row1['is_gift'], $favourable_id_list)) {
						foreach ($sort_favourable['by_brand'] as $key2 => $row2) {
							$act_range_ext_str = ',' . $row2['act_range_ext'] . ',';
							$brand_id_str = ',' . $brand_id . ',';
							if (($row1['is_gift'] == 0) && strstr($act_range_ext_str, trim($brand_id_str))) {
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_id'] = $row2['act_id'];
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_name'] = $row2['act_name'];
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type'] = $row2['act_type'];

								switch ($row2['act_type']) {
								case 0:
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满赠';
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = intval($row2['act_type_ext']);
									break;

								case 1:
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满减';
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = number_format($row2['act_type_ext'], 2);
									break;

								case 2:
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '折扣';
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = floatval($row2['act_type_ext'] / 10);
									break;

								default:
									break;
								}

								$merchant_goods[$key]['new_list'][$row2['act_id']]['min_amount'] = $row2['min_amount'];
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext'] = intval($row2['act_type_ext']);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_fav_amount'] = cart_favourable_amount($row2);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['available'] = favourable_available($row2);
								$cart_favourable = cart_favourable();
								$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['favourable_used'] = favourable_used($row2, $cart_favourable);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['left_gift_num'] = intval($row2['act_type_ext']) - (empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]));

								if ($row2['gift']) {
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_gift_list'] = $row2['gift'];
								}

								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_goods_list'][$row1['rec_id']] = $row1;
							}

							if ($row1['is_gift'] == $row2['act_id']) {
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_cart_gift'][$row1['rec_id']] = $row1;
							}
						}

						continue;
					}
				}

				if (isset($sort_favourable['by_goods']) && ($row1['extension_code'] != 'package_buy')) {
					$get_act_range_ext = get_act_range_ext($_SESSION['user_rank'], $row['ru_id'], 3);
					$favourable_id_list = get_favourable_id($sort_favourable['by_goods']);
					if (in_array($row1['goods_id'], $get_act_range_ext) || in_array($row1['is_gift'], $favourable_id_list)) {
						foreach ($sort_favourable['by_goods'] as $key2 => $row2) {
							$act_range_ext_str = ',' . $row2['act_range_ext'] . ',';
							$goods_id_str = ',' . $row1['goods_id'] . ',';
							if (strstr($act_range_ext_str, $goods_id_str) && ($row1['is_gift'] == 0)) {
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_id'] = $row2['act_id'];
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_name'] = $row2['act_name'];
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type'] = $row2['act_type'];

								switch ($row2['act_type']) {
								case 0:
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满赠';
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = intval($row2['act_type_ext']);
									break;

								case 1:
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满减';
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = number_format($row2['act_type_ext'], 2);
									break;

								case 2:
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '折扣';
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = floatval($row2['act_type_ext'] / 10);
									break;

								default:
									break;
								}

								$merchant_goods[$key]['new_list'][$row2['act_id']]['min_amount'] = $row2['min_amount'];
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext'] = intval($row2['act_type_ext']);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_fav_amount'] = cart_favourable_amount($row2);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['available'] = favourable_available($row2);
								$cart_favourable = cart_favourable();
								$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['favourable_used'] = favourable_used($row2, $cart_favourable);
								$merchant_goods[$key]['new_list'][$row2['act_id']]['left_gift_num'] = intval($row2['act_type_ext']) - (empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]));

								if ($row2['gift']) {
									$merchant_goods[$key]['new_list'][$row2['act_id']]['act_gift_list'] = $row2['gift'];
								}

								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_goods_list'][$row1['rec_id']] = $row1;
								break;
							}

							if ($row1['is_gift'] == $row2['act_id']) {
								$merchant_goods[$key]['new_list'][$row2['act_id']]['act_cart_gift'][$row1['rec_id']] = $row1;
							}
						}
					}
					else {
						$merchant_goods[$key]['new_list'][0]['act_goods_list'][$row1['rec_id']] = $row1;
					}
				}
				else {
					$merchant_goods[$key]['new_list'][0]['act_goods_list'][$row1['rec_id']] = $row1;
				}
			}
		}

		$merchant_goods[$key]['goods_count'] = $goods_num;
		$merchant_goods[$key]['package_goods_num'] = $package_goods_num;
	}

	return $merchant_goods;
}

function favourable_list($user_rank, $user_id = -1, $fav_id = 0)
{
	$where = '';

	if (0 <= $user_id) {
		$where .= ' AND user_id = \'' . $user_id . '\'';
	}

	if (0 < $fav_id) {
		$where .= ' AND act_id = \'' . $fav_id . '\' ';
	}

	$used_list = cart_favourable();
	$favourable_list = array();
	$user_rank = ',' . $user_rank . ',';
	$now = gmtime();
	$sql = 'SELECT * ' . 'FROM ' . $GLOBALS['ecs']->table('favourable_activity') . ' WHERE CONCAT(\',\', user_rank, \',\') LIKE \'%' . $user_rank . '%\'' . ' AND start_time <= \'' . $now . '\' AND end_time >= \'' . $now . '\' AND review_status = 3  ' . $where . ' ORDER BY sort_order';
	$res = $GLOBALS['db']->query($sql);

	foreach ($res as $favourable) {
		$favourable['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['start_time']);
		$favourable['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['end_time']);
		$favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
		$favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
		$favourable['gift'] = unserialize($favourable['gift']);

		foreach ((array) $favourable['gift'] as $key => $value) {
			$favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);
			$favourable['gift'][$key]['thumb_img'] = $GLOBALS['db']->getOne('SELECT goods_thumb FROM ' . $GLOBALS['ecs']->table('goods') . ' WHERE goods_id = \'' . $value['id'] . '\'');
			$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('goods') . ' WHERE is_on_sale = 1 AND goods_id = ' . $value['id'];
			$is_sale = $GLOBALS['db']->getOne($sql);

			if (!$is_sale) {
				unset($favourable['gift'][$key]);
			}
		}

		$favourable['act_range_desc'] = act_range_desc($favourable);
		$favourable['act_type_desc'] = sprintf($GLOBALS['_LANG']['fat_ext'][$favourable['act_type']], $favourable['act_type_ext']);
		$favourable['available'] = favourable_available($favourable);

		if ($favourable['available']) {
			$favourable['available'] = !favourable_used($favourable, $used_list);
		}

		$favourable_list[] = $favourable;
	}

	return $favourable_list;
}

function cart_favourable()
{
	if (!empty($_SESSION['user_id'])) {
		$sess_id = ' user_id = \'' . $_SESSION['user_id'] . '\' ';
	}
	else {
		$sess_id = ' session_id = \'' . real_cart_mac_ip() . '\' ';
	}

	$list = array();
	$sql = 'SELECT is_gift, COUNT(*) AS num ' . 'FROM ' . $GLOBALS['ecs']->table('cart') . ' WHERE ' . $sess_id . ' AND rec_type = \'' . CART_GENERAL_GOODS . '\'' . ' AND is_gift > 0' . ' GROUP BY is_gift';
	$res = $GLOBALS['db']->query($sql);

	foreach ($res as $row) {
		$list[$row['is_gift']] = $row['num'];
	}

	return $list;
}

function sort_favourable($favourable_list)
{
	$arr = array();

	foreach ($favourable_list as $key => $value) {
		switch ($value['act_range']) {
		case FAR_ALL:
			$arr['by_all'][$key] = $value;
			break;

		case FAR_CATEGORY:
			$arr['by_category'][$key] = $value;
			break;

		case FAR_BRAND:
			$arr['by_brand'][$key] = $value;
			break;

		case FAR_GOODS:
			$arr['by_goods'][$key] = $value;
			break;

		default:
			break;
		}
	}

	return $arr;
}

function act_range_desc($favourable)
{
	if ($favourable['act_range'] == FAR_BRAND) {
		$sql = 'SELECT brand_name FROM ' . $GLOBALS['ecs']->table('brand') . ' WHERE brand_id ' . db_create_in($favourable['act_range_ext']);
		return join(',', $GLOBALS['db']->getCol($sql));
	}
	else if ($favourable['act_range'] == FAR_CATEGORY) {
		$sql = 'SELECT cat_name FROM ' . $GLOBALS['ecs']->table('category') . ' WHERE cat_id ' . db_create_in($favourable['act_range_ext']);
		return join(',', $GLOBALS['db']->getCol($sql));
	}
	else if ($favourable['act_range'] == FAR_GOODS) {
		$sql = 'SELECT goods_name FROM ' . $GLOBALS['ecs']->table('goods') . ' WHERE goods_id ' . db_create_in($favourable['act_range_ext']);
		return join(',', $GLOBALS['db']->getCol($sql));
	}
	else {
		return '';
	}
}

function favourable_available($favourable)
{
	$user_rank = $_SESSION['user_rank'];

	if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank . ',') === false) {
		return false;
	}

	$amount = cart_favourable_amount($favourable);
	return ($favourable['min_amount'] <= $amount) && (($amount <= $favourable['max_amount']) || ($favourable['max_amount'] == 0));
}

function cart_favourable_amount($favourable)
{
	if (!empty($_SESSION['user_id'])) {
		$c_sess = ' c.user_id = \'' . $_SESSION['user_id'] . '\' ';
	}
	else {
		$c_sess = ' c.session_id = \'' . real_cart_mac_ip() . '\' ';
	}

	$fav_where = '';

	if ($favourable['userFav_type'] == 0) {
		$fav_where = ' AND g.user_id = \'' . $favourable['user_id'] . '\' ';
	}

	$sql = 'SELECT SUM(c.goods_price * c.goods_number) ' . 'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c, ' . $GLOBALS['ecs']->table('goods') . ' AS g ' . 'WHERE c.goods_id = g.goods_id ' . 'AND ' . $c_sess . ' AND c.rec_type = \'' . CART_GENERAL_GOODS . '\' ' . 'AND c.is_gift = 0 ' . 'AND c.goods_id > 0 ' . $fav_where;

	if ($favourable['act_range'] == FAR_ALL) {
	}
	else if ($favourable['act_range'] == FAR_CATEGORY) {
		$id_list = array();
		$cat_list = explode(',', $favourable['act_range_ext']);

		foreach ($cat_list as $id) {
			$id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0)));
		}

		$sql .= 'AND g.cat_id ' . db_create_in($id_list);
	}
	else if ($favourable['act_range'] == FAR_BRAND) {
		$id_list = explode(',', $favourable['act_range_ext']);
		$sql .= 'AND g.brand_id ' . db_create_in($id_list);
	}
	else {
		$id_list = explode(',', $favourable['act_range_ext']);
		$sql .= 'AND g.goods_id ' . db_create_in($id_list);
	}

	return $GLOBALS['db']->getOne($sql);
}

function flow_available_points($cart_value)
{
	if (!empty($_SESSION['user_id'])) {
		$c_sess = ' c.user_id = \'' . $_SESSION['user_id'] . '\' ';
	}
	else {
		$c_sess = ' c.session_id = \'' . real_cart_mac_ip() . '\' ';
	}

	$where = '';

	if (!empty($cart_value)) {
		$where = ' AND c.rec_id ' . db_create_in($cart_value);
	}

	$sql = 'SELECT SUM(g.integral * c.goods_number) ' . 'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c, ' . $GLOBALS['ecs']->table('goods') . ' AS g ' . 'WHERE ' . $c_sess . ' AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 ' . $where . 'AND c.rec_type = \'' . CART_GENERAL_GOODS . '\'';
	$val = intval($GLOBALS['db']->getOne($sql));
	return integral_of_value($val);
}

function get_user_coupons_list($user_id = '', $is_use = false, $total = false, $cart_goods = false, $user = true, $num = 10)
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

		$number = 0;

		if (!empty($arrr)) {
			foreach ($arrr as $k => $v) {
				foreach ($v as $m => $n) {
					$number++;
					$arr[$n['uc_id']] = $n;
				}
			}
		}

		if ($num == 0) {
			return $number;
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

function update_team($team_id = 0)
{
	if (0 < $team_id) {
		$sql = 'select g.goods_id,g.limit_num, g.team_num from ' . $GLOBALS['ecs']->table('team_log') . ' as tl LEFT JOIN ' . $GLOBALS['ecs']->table('team_goods') . ' as g ON tl.goods_id = g.goods_id where tl.team_id =' . $team_id . ' ';
		$res = $GLOBALS['db']->getRow($sql);
		$sql = 'SELECT count(order_id) as num  FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE team_id = \'' . $team_id . '\' AND extension_code = \'team_buy\'  and pay_status = \'' . PS_PAYED . '\' ';
		$team_count = $GLOBALS['db']->getRow($sql);

		if ($res['team_num'] <= $team_count['num']) {
			$sql = 'UPDATE ' . $GLOBALS['ecs']->table('team_log') . ' SET status = \'1\' ' . ' WHERE team_id = \'' . $team_id . '\' ';
			$GLOBALS['db']->query($sql);
		}

		$limit_num = $res['limit_num'] + 1;
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('team_goods') . ' SET limit_num = \'' . $limit_num . '\' ' . ' WHERE goods_id = \'' . $res['goods_id'] . '\' ';
		$GLOBALS['db']->query($sql);
	}
}

function get_user_region_address($order_id = 0, $address = '', $type = 0)
{
	if ($type == 1) {
		$table = 'order_return';
		$where = 'o.ret_id = \'' . $order_id . '\'';
	}
	else {
		$table = 'order_info';
		$where = 'o.order_id = \'' . $order_id . '\'';
	}

	$sql = 'SELECT concat(IFNULL(p.region_name, \'\'), ' . '\'  \', IFNULL(t.region_name, \'\'), \'  \', IFNULL(d.region_name, \'\'), \'  \', IFNULL(s.region_name, \'\')) AS region ' . 'FROM ' . $GLOBALS['ecs']->table($table) . ' AS o ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('region') . ' AS p ON o.province = p.region_id ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('region') . ' AS t ON o.city = t.region_id ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('region') . ' AS d ON o.district = d.region_id ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('region') . ' AS s ON o.street = s.region_id ' . 'WHERE ' . $where;
	$region = $GLOBALS['db']->getOne($sql);

	if ($address) {
		$region = $region . '&nbsp;' . $address;
	}

	return $region;
}

function categroy_number($tc_id = 0)
{
	if (0 < $tc_id) {
		$tc_id = categroy_id($tc_id);
		$where .= ' and tc_id in (' . $tc_id . ') ';
	}

	$sql = 'SELECT count(id) as num FROM {pre}team_goods WHERE is_team = 1 ' . $where . ' ';
	$goods_number = $GLOBALS['db']->getOne($sql);
	return $goods_number;
}

function categroy_id($tc_id = 0)
{
	$one = dao('team_category')->field('id')->where('id =' . $tc_id . ' or parent_id=' . $tc_id)->select();

	if ($one) {
		foreach ($one as $key) {
			$one_id[] = $key['id'];
		}

		$tc_id = implode(',', $one_id);
	}

	return $tc_id;
}

function set_default_filter($goods_id = 0, $cat_id = 0)
{
	if (0 < $cat_id) {
		$parent_cat_list = get_select_category($cat_id, 1, true);
		$filter_category_navigation = get_array_category_info($parent_cat_list);
		$GLOBALS['smarty']->assign('filter_category_navigation', $filter_category_navigation);
	}

	$GLOBALS['smarty']->assign('filter_category_list', get_category_list($cat_id));
	$GLOBALS['smarty']->assign('filter_brand_list', search_brand_list($goods_id));
	return true;
}

function get_array_category_info($arr = array())
{
	if ($arr) {
		$sql = ' SELECT cat_id, cat_name FROM ' . $GLOBALS['ecs']->table('category') . ' WHERE cat_id ' . db_create_in($arr);
		return $GLOBALS['db']->getAll($sql);
	}
	else {
		return false;
	}
}

function get_category_list($cat_id = 0, $relation = 0)
{
	if ($relation == 0) {
		$parent_id = $GLOBALS['db']->getOne(' SELECT parent_id FROM ' . $GLOBALS['ecs']->table('category') . ' WHERE cat_id = \'' . $cat_id . '\' ');
	}
	else if ($relation == 1) {
		$parent_id = $GLOBALS['db']->getOne(' SELECT parent_id FROM ' . $GLOBALS['ecs']->table('category') . ' WHERE cat_id = \'' . $cat_id . '\' ');
	}
	else if ($relation == 2) {
		$parent_id = $cat_id;
	}

	$parent_id = (empty($parent_id) ? 0 : $parent_id);
	$category_list = $GLOBALS['db']->getAll(' SELECT cat_id, cat_name FROM ' . $GLOBALS['ecs']->table('category') . ' WHERE parent_id = \'' . $parent_id . '\' ');

	foreach ($category_list as $key => $val) {
		if ($cat_id == $val['cat_id']) {
			$is_selected = 1;
		}
		else {
			$is_selected = 0;
		}

		$category_list[$key]['is_selected'] = $is_selected;
	}

	return $category_list;
}

function search_brand_list($goods_id = 0)
{
	$letter = (empty($_REQUEST['letter']) ? '' : trim($_REQUEST['letter']));
	$keyword = (empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']));
	$where = '';

	if (0 < $goods_id) {
		$sql = 'SELECT user_id FROM ' . $GLOBALS['ecs']->table('goods') . ' where goods_id = \'' . $goods_id . '\'';
		$adminru['ru_id'] = $GLOBALS['db']->getOne($sql);
	}

	if (0 < $adminru['ru_id']) {
		if (!empty($keyword)) {
			$where .= ' AND (brandName LIKE \'%' . mysql_like_quote($keyword) . '%\' OR bank_name_letter LIKE \'%' . mysql_like_quote($keyword) . '%\') ';
		}

		$sql = 'SELECT bid as brand_id, brandName as brand_name FROM ' . $GLOBALS['ecs']->table('merchants_shop_brand') . ' where user_id = \'' . $adminru['ru_id'] . '\' ' . $where . ' AND audit_status = 1 ORDER BY bid ASC';
		$res = $GLOBALS['db']->getAll($sql);
	}
	else {
		if (!empty($keyword)) {
			$where .= ' AND (brand_name LIKE \'%' . mysql_like_quote($keyword) . '%\' OR brand_letter LIKE \'%' . mysql_like_quote($keyword) . '%\') ';
		}

		$sql = 'SELECT brand_id, brand_name FROM ' . $GLOBALS['ecs']->table('brand') . ' WHERE 1 ' . $where . ' ORDER BY sort_order';
		$res = $GLOBALS['db']->getAll($sql);
	}

	$brand_list = array();

	foreach ($res as $key => $val) {
		$is_selected = 0;
		$res[$key]['is_selected'] = $is_selected;
		$res[$key]['letter'] = !empty($val['brand_name']) ? getfirstcharter($val['brand_name']) : '';
		$res[$key]['brand_name'] = !empty($val['brand_name']) ? addslashes($val['brand_name']) : '';

		if (!empty($letter)) {
			if (($letter == 'QT') && !$res[$key]['letter']) {
				$brand_list[] = $res[$key];
			}
			else if ($letter == $res[$key]['letter']) {
				$brand_list[] = $res[$key];
			}
		}
		else {
			$brand_list[] = $res[$key];
		}
	}

	return $brand_list;
}

function getFirstCharter($str)
{
	if (empty($str)) {
		return '';
	}

	$fchar = ord($str[0]);
	if ((ord('A') <= $fchar) && ($fchar <= ord('z'))) {
		return strtoupper($str[0]);
	}

	$s1 = iconv('UTF-8', 'gb2312', $str);
	$s2 = iconv('gb2312', 'UTF-8', $s1);
	$s = ($s2 == $str ? $s1 : $str);
	$asc = ((ord($s[0]) * 256) + ord($s[1])) - 65536;
	if ((-20319 <= $asc) && ($asc <= -20284)) {
		return 'A';
	}

	if ((-20283 <= $asc) && ($asc <= -19776)) {
		return 'B';
	}

	if ((-19775 <= $asc) && ($asc <= -19219)) {
		return 'C';
	}

	if ((-19218 <= $asc) && ($asc <= -18711)) {
		return 'D';
	}

	if ((-18710 <= $asc) && ($asc <= -18527)) {
		return 'E';
	}

	if ((-18526 <= $asc) && ($asc <= -18240)) {
		return 'F';
	}

	if ((-18239 <= $asc) && ($asc <= -17923)) {
		return 'G';
	}

	if ((-17922 <= $asc) && ($asc <= -17418)) {
		return 'H';
	}

	if ((-17417 <= $asc) && ($asc <= -16475)) {
		return 'J';
	}

	if ((-16474 <= $asc) && ($asc <= -16213)) {
		return 'K';
	}

	if ((-16212 <= $asc) && ($asc <= -15641)) {
		return 'L';
	}

	if ((-15640 <= $asc) && ($asc <= -15166)) {
		return 'M';
	}

	if ((-15165 <= $asc) && ($asc <= -14923)) {
		return 'N';
	}

	if ((-14922 <= $asc) && ($asc <= -14915)) {
		return 'O';
	}

	if ((-14914 <= $asc) && ($asc <= -14631)) {
		return 'P';
	}

	if ((-14630 <= $asc) && ($asc <= -14150)) {
		return 'Q';
	}

	if ((-14149 <= $asc) && ($asc <= -14091)) {
		return 'R';
	}

	if ((-14090 <= $asc) && ($asc <= -13319)) {
		return 'S';
	}

	if ((-13318 <= $asc) && ($asc <= -12839)) {
		return 'T';
	}

	if ((-12838 <= $asc) && ($asc <= -12557)) {
		return 'W';
	}

	if ((-12556 <= $asc) && ($asc <= -11848)) {
		return 'X';
	}

	if ((-11847 <= $asc) && ($asc <= -11056)) {
		return 'Y';
	}

	if ((-11055 <= $asc) && ($asc <= -10247)) {
		return 'Z';
	}

	return NULL;
}

function get_select_category($cat_id = 0, $relation = 0, $self = true)
{
	static $cat_list = array();
	$cat_list[] = intval($cat_id);

	if ($relation == 0) {
		return $cat_list;
	}
	else if ($relation == 1) {
		$sql = ' select parent_id from ' . $GLOBALS['ecs']->table('category') . ' where cat_id=\'' . $cat_id . '\' ';
		$parent_id = $GLOBALS['db']->getOne($sql);

		if (!empty($parent_id)) {
			get_select_category($parent_id, $relation, $self);
		}

		if ($self == false) {
			unset($cat_list[0]);
		}

		$cat_list[] = 0;
		return array_reverse(array_unique($cat_list));
	}
	else if ($relation == 2) {
		$sql = ' select cat_id from ' . $GLOBALS['ecs']->table('category') . ' where parent_id=\'' . $cat_id . '\' ';
		$child_id = $GLOBALS['db']->getCol($sql);

		if (!empty($child_id)) {
			foreach ($child_id as $key => $val) {
				get_select_category($val, $relation, $self);
			}
		}

		if ($self == false) {
			unset($cat_list[0]);
		}

		return $cat_list;
	}
}

function team_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0)
{
	static $res;

	if ($res === NULL) {
		$data = read_static_cache('team_cat_pid_releate');

		if ($data === false) {
			$sql = 'SELECT c.*, COUNT(s.id) AS has_children' . ' FROM {pre}team_category AS c ' . ' LEFT JOIN {pre}team_category  AS s ON s.parent_id=c.id' . ' where c.status = 1' . ' GROUP BY c.id ' . ' ORDER BY parent_id, sort_order DESC';
			$res = $GLOBALS['db']->query($sql);
			write_static_cache('team_cat_pid_releate', $res);
		}
		else {
			$res = $data;
		}
	}

	if (empty($res) == true) {
		return $re_type ? '' : array();
	}

	$options = team_cat_options($cat_id, $res);

	if (0 < $level) {
		if ($cat_id == 0) {
			$end_level = $level;
		}
		else {
			$first_item = reset($options);
			$end_level = $first_item['level'] + $level;
		}

		foreach ($options as $key => $val) {
			if ($end_level <= $val['level']) {
				unset($options[$key]);
			}
		}
	}

	$pre_key = 0;

	foreach ($options as $key => $value) {
		$options[$key]['has_children'] = 1;

		if (0 < $pre_key) {
			if ($options[$pre_key]['id'] == $options[$key]['parent_id']) {
				$options[$pre_key]['has_children'] = 1;
			}
		}

		$pre_key = $key;
	}

	if ($re_type == true) {
		$select = '';

		foreach ($options as $var) {
			$select .= '<option value="' . $var['id'] . '" ';
			$select .= ($selected == $var['id'] ? 'selected=\'ture\'' : '');
			$select .= '>';

			if (0 < $var['level']) {
				$select .= str_repeat('&nbsp;', $var['level'] * 4);
			}

			$select .= htmlspecialchars(addslashes($var['name'])) . '</option>';
		}

		return $select;
	}
	else {
		foreach ($options as $key => $value) {
			$options[$key]['url'] = build_uri('article_cat', array('acid' => $value['cat_id']), $value['cat_name']);
		}

		return $options;
	}
}

function team_cat_options($spec_cat_id, $arr)
{
	static $cat_options = array();

	if (isset($cat_options[$spec_cat_id])) {
		return $cat_options[$spec_cat_id];
	}

	if (!isset($cat_options[0])) {
		$level = $last_cat_id = 0;
		$options = $cat_id_array = $level_array = array();

		while (!empty($arr)) {
			foreach ($arr as $key => $value) {
				$cat_id = $value['id'];
				if (($level == 0) && ($last_cat_id == 0)) {
					if (0 < $value['parent_id']) {
						break;
					}

					$options[$cat_id] = $value;
					$options[$cat_id]['level'] = $level;
					$options[$cat_id]['id'] = $cat_id;
					$options[$cat_id]['name'] = $value['name'];
					unset($arr[$key]);

					if ($value['has_children'] == 0) {
						continue;
					}

					$last_cat_id = $cat_id;
					$cat_id_array = array($cat_id);
					$level_array[$last_cat_id] = ++$level;
					continue;
				}

				if ($value['parent_id'] == $last_cat_id) {
					$options[$cat_id] = $value;
					$options[$cat_id]['level'] = $level;
					$options[$cat_id]['id'] = $cat_id;
					$options[$cat_id]['name'] = $value['name'];
					unset($arr[$key]);

					if (0 < $value['has_children']) {
						if (end($cat_id_array) != $last_cat_id) {
							$cat_id_array[] = $last_cat_id;
						}

						$last_cat_id = $cat_id;
						$cat_id_array[] = $cat_id;
						$level_array[$last_cat_id] = ++$level;
					}
				}
				else if ($last_cat_id < $value['parent_id']) {
					break;
				}
			}

			$count = count($cat_id_array);

			if (1 < $count) {
				$last_cat_id = array_pop($cat_id_array);
			}
			else if ($count == 1) {
				if ($last_cat_id != end($cat_id_array)) {
					$last_cat_id = end($cat_id_array);
				}
				else {
					$level = 0;
					$last_cat_id = 0;
					$cat_id_array = array();
					continue;
				}
			}

			if ($last_cat_id && isset($level_array[$last_cat_id])) {
				$level = $level_array[$last_cat_id];
			}
			else {
				$level = 0;
			}
		}

		$cat_options[0] = $options;
	}
	else {
		$options = $cat_options[0];
	}

	if (!$spec_cat_id) {
		return $options;
	}
	else {
		if (empty($options[$spec_cat_id])) {
			return array();
		}

		$spec_cat_id_level = $options[$spec_cat_id]['level'];

		foreach ($options as $key => $value) {
			if ($key != $spec_cat_id) {
				unset($options[$key]);
			}
			else {
				break;
			}
		}

		$spec_cat_id_array = array();

		foreach ($options as $key => $value) {
			if ((($spec_cat_id_level == $value['level']) && ($value['id'] != $spec_cat_id)) || ($value['level'] < $spec_cat_id_level)) {
				break;
			}
			else {
				$spec_cat_id_array[$key] = $value;
			}
		}

		$cat_options[$spec_cat_id] = $spec_cat_id_array;
		return $spec_cat_id_array;
	}
}


?>
