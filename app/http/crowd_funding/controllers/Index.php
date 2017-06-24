<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\crowd_funding\controllers;

class Index extends \app\http\base\controllers\Frontend
{
	public function __construct()
	{
		parent::__construct();
		l(require LANG_PATH . c('shop.lang') . '/user.php');
		l(require LANG_PATH . c('shop.lang') . '/flow.php');
		$files = array('order', 'clips', 'transaction');
		$this->load_helper($files);
		$this->user_id = $_SESSION['user_id'];
		$this->cat_id = i('request.c_id');
		$this->type = i('request.type');
		$this->keywords = i('request.keywords');
		$this->goods_id = i('request.id');
		$this->page = 1;
		$this->size = 10;

		if (!empty($_COOKIE['ZCECS']['keywords'])) {
			$histroy = explode(',', $_COOKIE['ZCECS']['keywords']);

			foreach ($histroy as $key => $val) {
				if ($key < 10) {
					$zchistroy_list[$key] = $val;
				}
			}

			$this->assign('zcsearch_histroy', $zchistroy_list);
		}
	}

	public function actionIndex()
	{
		$this->page = i('request.page', 1, 'intval');

		if (IS_AJAX) {
			$goodslist = $this->zc_goods();
			exit(json_encode(array('list' => $goodslist['list'], 'totalPage' => $goodslist['totalpage'])));
		}

		$category = top_all($this->type);
		$this->assign('id', $this->cat_id);
		$this->assign('keywords', $this->keywords);
		$this->assign('category', $category);
		$this->assign('type', $this->type);
		$this->assign('page_title', '众筹列表');
		$this->display();
	}

	private function zc_goods()
	{
		if (!empty($_COOKIE['ZCECS']['keywords'])) {
			$history = explode(',', $_COOKIE['ZCECS']['keywords']);
			array_unshift($history, $this->keywords);
			$history = array_unique($history);
			cookie('ZCECS[keywords]', implode(',', $history));
		}
		else {
			cookie('ZCECS[keywords]', $this->keywords);
		}

		if ($this->keywords) {
			$where .= ' and title like \'%' . $this->keywords . '%\' ';
		}

		if (0 < $this->cat_id) {
			$sql = 'SELECT `cat_id` FROM ' . $this->ecs->table('zc_category') . ' where cat_id =' . $this->cat_id . ' or parent_id=' . $this->cat_id . ' ';
			$category = $this->db->query($sql);

			if ($category) {
				foreach ($category as $key) {
					$cat_id[] = $key['cat_id'];
				}

				$catid = implode(',', $cat_id);
			}
			else {
				$catid = $this->cat_id;
			}

			$where .= ' and cat_id in (' . $catid . ') ';
		}

		if ($this->type) {
			switch ($this->type) {
			case 'new':
				$where .= ' order by start_time DESC';
				break;

			case 'amount':
				$where .= ' order by amount DESC ';
				break;

			case 'join_num':
				$where .= ' order by join_num DESC ';
				break;

			default:
				$where .= '';
			}
		}

		$now = time();
		$sql = 'SELECT `id`,`cat_id`,`title`,`start_time`,`end_time`,`amount`,`join_money`,`join_num`,`title_img`,`describe`,(end_time-unix_timestamp(now())) as shenyu_time FROM ' . $this->ecs->table('zc_project') . ' where start_time <= \'' . $now . '\' AND end_time >= \'' . $now . '\'  ' . $where . ' ';
		$zc_arr = $this->db->query($sql);
		$total = (is_array($zc_arr) ? count($zc_arr) : 0);
		$res = $this->db->selectLimit($sql, $this->size, ($this->page - 1) * $this->size);

		foreach ($res as $k => $z_val) {
			$res[$k]['star_time'] = date('Y-m-d', $z_val['start_time']);
			$res[$k]['end_time'] = date('Y-m-d', $z_val['end_time']);
			$res[$k]['shenyu_time'] = ceil($z_val['shenyu_time'] / 3600 / 24);
			$res[$k]['title_img'] = get_image_path($z_val['title_img']);
			$res[$k]['url'] = url('info', array('id' => $z_val['id']));
			$res[$k]['baifen_bi'] = round($z_val['join_money'] / $z_val['amount'], 2) * 100;

			if (50 < mb_strlen($z_val['describe'], 'utf-8')) {
				$res[$k]['duan_des'] = mb_substr($z_val['describe'], 0, 50, 'utf-8') . '...';
			}
			else {
				$res[$k]['duan_des'] = mb_substr($z_val['describe'], 0, 50, 'utf-8');
			}

			if (time() < $z_val['start_time']) {
				$res[$k]['zc_status'] = '预热中';
			}
			else {
				if (($z_val['start_time'] <= time()) && (time() <= $z_val['end_time'])) {
					$res[$k]['zc_status'] = '众筹中';
				}
				else if ($z_val['end_time'] < time()) {
					if ($z_val['amount'] <= $z_val['join_money']) {
						$res[$k]['zc_status'] = '众筹成功';
						$res[$k]['shenyu_time'] = 0;
					}
					else {
						unset($res[$k]);
					}
				}
			}
		}

		return array('list' => array_values($res), 'totalpage' => ceil($total / $this->size));
	}

	public function actionInfo()
	{
		$init = $this->get_initiator_info($this->goods_id);
		$goods = zc_goods_info($this->goods_id);
		$goods_arr = zc_goods($this->goods_id);
		$progress = zc_progress($this->goods_id);
		$backer_list = get_backer_list($this->goods_id);
		$topic_list = get_topic_list($this->goods_id);

		if ($_SESSION['user_id']) {
			$where['user_id'] = $_SESSION['user_id'];
			$where['pid'] = $this->goods_id;
			$rs = $this->db->table('zc_focus')->where($where)->count();

			if (0 < $rs) {
				$this->assign('goods_collect', 1);
			}
		}

		$sql = 'select b.is_IM, a.ru_id,a.province, a.city, a.kf_type, a.kf_ww, a.kf_qq, a.meiqia, a.shop_name, a.kf_appkey from {pre}seller_shopinfo as a left join {pre}merchants_shop_information as b on a.ru_id=b.shop_id where ru_id=\'' . $goods['user_id'] . '\' ';
		$basic_info = $this->db->getRow($sql);
		$info_ww = ($basic_info['kf_ww'] ? explode("\r\n", $basic_info['kf_ww']) : '');
		$info_qq = ($basic_info['kf_qq'] ? explode("\r\n", $basic_info['kf_qq']) : '');
		$kf_ww = ($info_ww ? $info_ww[0] : '');
		$kf_qq = ($info_qq ? $info_qq[0] : '');
		$basic_ww = ($kf_ww ? explode('|', $kf_ww) : '');
		$basic_qq = ($kf_qq ? explode('|', $kf_qq) : '');
		$basic_info['kf_ww'] = $basic_ww ? $basic_ww[1] : '';
		$basic_info['kf_qq'] = $basic_qq ? $basic_qq[1] : '';
		if ((($basic_info['is_IM'] == 1) || ($basic_info['ru_id'] == 0)) && !empty($basic_info['kf_appkey'])) {
			$basic_info['kf_appkey'] = $basic_info['kf_appkey'];
		}
		else {
			$basic_info['kf_appkey'] = '';
		}

		$basic_date = array('region_name');
		$basic_info['province'] = get_table_date('region', 'region_id = \'' . $basic_info['province'] . '\'', $basic_date, 2);
		$basic_info['city'] = get_table_date('region', 'region_id= \'' . $basic_info['city'] . '\'', $basic_date, 2) . '市';
		$this->assign('basic_info', $basic_info);
		$this->assign('init', $init);
		$this->assign('goods', $goods);
		$this->assign('goods_arr', $goods_arr);
		$this->assign('progress', $progress);
		$this->assign('backer_list', $backer_list);
		$this->assign('topic_list', $topic_list);
		$this->assign('page_title', '项目详情');
		$this->display();
	}

	public function get_initiator_info($cid)
	{
		$id = $this->get_initiator_id($cid);
		$sql = ' SELECT * FROM ' . $this->ecs->table('zc_initiator') . ' WHERE id = \'' . $id . '\' ';
		$row = $this->db->getRow($sql);
		$row['img'] = get_image_path($row['img']);
		$logo = explode(',', $row['rank']);

		if ($logo) {
			foreach ($logo as $val) {
				$row['logo'][] = $this->get_rank_logo($val);
			}
		}

		$start_sql = ' SELECT count(*) FROM ' . $this->ecs->table('zc_project') . ' WHERE init_id = \'' . $id . '\' ';
		$count = $GLOBALS['db']->getOne($start_sql);
		$row['start_count'] = isset($count) ? $count : 1;
		return $row;
	}

	public function get_rank_logo($id)
	{
		$sql = ' SELECT logo_name, img FROM ' . $this->ecs->table('zc_rank_logo') . ' WHERE id = \'' . $id . '\' ';
		$row = $this->db->getRow($sql);
		return $row;
	}

	public function get_initiator_id($cid)
	{
		$sql = ' SELECT init_id FROM ' . $this->ecs->table('zc_project') . ' WHERE id = \'' . $cid . '\' ';
		$init_id = $this->db->getOne($sql);
		return $init_id;
	}

	public function actionProperties()
	{
		$sql = ' SELECT `risk_instruction`, `describe` FROM ' . $this->ecs->table('zc_project') . ' WHERE id = \'' . $this->goods_id . '\' ';
		$row = $this->db->getRow($sql);
		$this->assign('describe', $row['describe']);
		$this->assign('row', $row);
		$this->assign('id', $this->goods_id);
		$this->display();
	}

	public function actionComment()
	{
		if (IS_AJAX) {
			$this->page = i('request.page', 1, 'intval');
			$this->goods_id = i('request.goods', '', 'intval');
			$comment_list = zc_comment_list($this->goods_id, $this->size, $this->page);
			exit(json_encode(array('list' => $comment_list['list'], 'totalPage' => $comment_list['totalpage'])));
		}

		$this->assign('id', $this->goods_id);
		$this->assign('page_title', '商品评论');
		$this->display();
	}

	public function actionPrice()
	{
		$res = array('err_msg' => '', 'result' => '', 'qty' => 1);
		$id = (isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 1);
		$pid = (isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 1);
		$number = (isset($_REQUEST['number']) ? intval($_REQUEST['number']) : 1);

		if ($pid == 0) {
			$res['err_msg'] = l('err_change_attr');
			$res['err_no'] = 1;
		}
		else {
			$sql = ' SELECT `id`, `pid`, `limit`, `backer_num`, `price` FROM ' . $this->ecs->table('zc_goods') . ' WHERE id = \'' . $id . '\' and pid = \'' . $pid . '\' ';
			$goods = $this->db->getRow($sql);

			if (0 <= $goods['limit']) {
				$surplus_num = $goods['limit'] - $goods['backer_num'];

				if ($number <= 0) {
					$res['qty'] = 1;
				}
				else {
					$res['qty'] = $number;
				}

				if ($surplus_num < $number) {
					$res['err_msg'] = '已超出计划销售数量';
					$res['err_no'] = 1;
				}
			}

			$res['result'] = price_format($goods['price'] * $number);
		}

		exit(json_encode($res));
	}

	public function actionAddCollection()
	{
		$result = array('error' => 0, 'message' => '');
		if (!isset($this->user_id) || ($this->user_id == 0)) {
			$result['error'] = 2;
			$result['message'] = l('login_please');
			exit(json_encode($result));
		}
		else {
			$where['user_id'] = $this->user_id;
			$where['pid'] = $this->goods_id;
			$rs = $this->db->table('zc_focus')->where($where)->count();

			if (0 < $rs) {
				$rs = $this->db->table('zc_focus')->where($where)->delete();

				if (!$rs) {
					$result['error'] = 1;
					$result['message'] = m()->errorMsg();
					exit(json_encode($result));
				}
				else {
					$result['error'] = 0;
					$result['message'] = '已成移除关注列表';
					exit(json_encode($result));
				}
			}
			else {
				$data['user_id'] = $this->user_id;
				$data['pid'] = $this->goods_id;
				$data['add_time'] = gmtime();

				if ($this->db->table('zc_focus')->data($data)->add() === false) {
					$result['error'] = 1;
					$result['message'] = m()->errorMsg();
					exit(json_encode($result));
				}
				else {
					$result['error'] = 0;
					$result['message'] = '已成功添加关注列表';
					exit(json_encode($result));
				}
			}
		}
	}

	public function actionCheckout()
	{
		if (!empty($_POST)) {
			$pid = i('pid');
			$id = i('id');
			$number = i('number');
			$_SESSION['pid'] = $pid;
			$_SESSION['id'] = $id;
			$_SESSION['number'] = $number;
		}
		else {
			$_SESSION['pid'];
			$_SESSION['id'];
			$_SESSION['number'];
		}

		$this->assign('goods_id', $_SESSION['goods_id']);
		$this->assign('cp_id', $_SESSION['cp_id']);
		$this->assign('number', $_SESSION['number']);
		$flow_type = (isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS);

		if ($_SESSION['user_id'] == 0) {
			ecs_header('Location: ' . url('user/login/index'));
			exit();
		}

		$consignee = get_consignee($_SESSION['user_id']);

		if (!zc_check_consignee_info($consignee)) {
			ecs_header('Location: ' . url('address_list'));
			exit();
		}

		$user_address = get_order_user_address_list($_SESSION['user_id']);

		if (count($user_address) <= 0) {
			ecs_header('Location: ' . url('address_list'));
			exit();
		}

		if ($consignee) {
			$consignee['province_name'] = get_goods_region_name($consignee['province']);
			$consignee['city_name'] = get_goods_region_name($consignee['city']);
			$consignee['district_name'] = get_goods_region_name($consignee['district']);
			$consignee['region'] = $consignee['province_name'] . '&nbsp;' . $consignee['city_name'] . '&nbsp;' . $consignee['district_name'];
		}

		$default_id = $this->db->getOne('SELECT address_id FROM {pre}users WHERE user_id=\'' . $_SESSION['user_id'] . '\'');
		$default_id = $this->db->getOne('SELECT address_id FROM {pre}users WHERE user_id=\'' . $_SESSION['user_id'] . '\'');

		if ($consignee['address_id'] == $default_id) {
			$this->assign('is_default', '1');
		}

		$_SESSION['flow_consignee'] = $consignee;
		$this->assign('consignee', $consignee);
		$cart_goods = zc_cart_goods($_SESSION['pid'], $_SESSION['id'], $_SESSION['number']);
		$this->assign('goods', $cart_goods);
		$shengyu = $cart_goods['limit'] - $cart_goods['backer_num'];

		if ($shengyu == 0) {
			show_message('该产品已售罄，请选择其他产品', '', url('index'), 'warning');
		}

		$region = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
		$order = flow_order_info();
		$this->assign('order', $order);
		$total = zc_order_fee($order, $cart_goods, $consignee);
		$this->assign('total', $total);
		$shipping_list = zc_get_ru_shippng_info($cart_goods, $consignee);
		$this->assign('shipping_list', $shipping_list);

		if ($order['shipping_id'] == 0) {
			$cod = true;
			$cod_fee = 0;
		}
		else {
			$shipping = shipping_info($order['shipping_id']);
			$cod = $shipping['support_cod'];

			if ($cod) {
				if ($flow_type == CART_GROUP_BUY_GOODS) {
					$group_buy_id = $_SESSION['extension_id'];

					if ($group_buy_id <= 0) {
						show_message('error group_buy_id');
					}

					$group_buy = group_buy_info($group_buy_id);

					if (empty($group_buy)) {
						show_message('group buy not exists: ' . $group_buy_id);
					}

					if (0 < $group_buy['deposit']) {
						$cod = false;
						$cod_fee = 0;
						$this->assign('gb_deposit', $group_buy['deposit']);
					}
				}

				if ($cod) {
					$shipping_area_info = shipping_area_info($order['shipping_id'], $region);
					$cod_fee = $shipping_area_info['pay_fee'];
				}
			}
			else {
				$cod_fee = 0;
			}
		}

		$payment_list = available_payment_list(1, $cod_fee);

		if (isset($payment_list)) {
			foreach ($payment_list as $key => $payment) {
				$payment_list[$key]['pay_name'] = strip_tags($payment['pay_name']);

				if (substr($payment['pay_code'], 0, 4) == 'pay_') {
					unset($payment_list[$key]);
					continue;
				}

				if ($payment['is_cod'] == '1') {
					$payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
				}

				if (($payment['pay_code'] == 'yeepayszx') && (300 < $total['amount'])) {
					unset($payment_list[$key]);
				}

				if (!is_dir(APP_WECHAT_PATH) || !is_dir(ROOT_PATH . '/app/modules/wechat')) {
					if ($payment['pay_code'] == 'wxpay') {
						unset($payment_list[$key]);
					}
				}

				if ($payment['pay_code'] == 'balance') {
					if ($_SESSION['user_id'] == 0) {
						unset($payment_list[$key]);
					}
					else if ($_SESSION['flow_order']['pay_id'] == $payment['pay_id']) {
						$this->assign('disable_surplus', 1);
					}
				}

				if (!file_exists(ADDONS_PATH . 'payment/' . $payment['pay_code'] . '.php')) {
					unset($payment_list[$key]);
				}
			}
		}

		$this->assign('payment_list', $payment_list);

		if ($order['pay_id']) {
			$payment_selected = payment_info($order['pay_id']);

			if (file_exists(ADDONS_PATH . 'payment/' . $payment_selected['pay_code'] . '.php')) {
				$payment_selected['format_pay_fee'] = strpos($payment_selected['pay_fee'], '%') !== false ? $payment_selected['pay_fee'] : price_format($payment_selected['pay_fee'], false);
				$this->assign('payment_selected', $payment_selected);
			}
		}

		$this->assign('page_title', '订单确认');
		$this->display();
	}

	public function actionDone()
	{
		if (empty($_SESSION['id']) && empty($_SESSION['pid']) && empty($_SESSION['number'])) {
			ecs_header('Location: ' . url('/') . "\n");
			exit();
		}

		$sql = ' SELECT COUNT(order_id) FROM ' . $this->ecs->table('order_info') . ' WHERE user_id = \'' . $_SESSION['user_id'] . '\' AND is_zc_order = 1 AND zc_goods_id = \'' . $_SESSION['id'] . '\' AND pay_status = 0 and order_status !=2 ';
		$zc_order_num = $GLOBALS['db']->getOne($sql);

		if (0 < $zc_order_num) {
			show_message('您有未支付的众筹订单，请付款后再提交新订单', '返回上一页', url('user/crowd/order'));
		}

		if (empty($_SESSION['direct_shopping']) && ($_SESSION['user_id'] == 0)) {
			ecs_header('Location: ' . url('user/login/index'));
			exit();
		}

		$consignee = get_consignee($_SESSION['user_id']);

		if (!zc_check_consignee_info($consignee)) {
			ecs_header('Location: ' . url('address_list'));
			exit();
		}

		$where_flow = '';
		$_POST['how_oos'] = isset($_POST['how_oos']) ? intval($_POST['how_oos']) : 0;
		$_POST['card_message'] = isset($_POST['card_message']) ? compile_str($_POST['card_message']) : '';
		$_POST['inv_type'] = !empty($_POST['inv_type']) ? compile_str($_POST['inv_type']) : '';
		$_POST['inv_payee'] = isset($_POST['inv_payee']) ? compile_str($_POST['inv_payee']) : '';
		$inv_content = (isset($_POST['inv_content']) ? compile_str($_POST['inv_content']) : '0');
		$postscript = i('post.postscript', '', 'trim');
		$ru_id_arr = i('post.ru_id');
		$shipping_arr = i('post.shipping_id');
		$order = array('shipping_id' => $shipping_arr, 'pay_id' => intval($_POST['payment_id']), 'pack_id' => isset($_POST['pack']) ? intval($_POST['pack']) : 0, 'card_id' => isset($_POST['card']) ? intval($_POST['card']) : 0, 'card_message' => trim($_POST['card_message']), 'surplus' => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0, 'integral' => isset($_POST['integral']) ? intval($_POST['integral']) : 0, 'bonus_id' => isset($_POST['bonus']) ? intval($_POST['bonus']) : 0, 'need_inv' => empty($_POST['need_inv']) ? 0 : 1, 'inv_type' => i('inv_type'), 'inv_payee' => trim($_POST['inv_payee']), 'inv_content' => $inv_content, 'postscript' => is_array($postscript) ? '' : $postscript, 'how_oos' => isset($GLOBALS['LANG']['oos'][$_POST['how_oos']]) ? addslashes($GLOBALS['LANG']['oos'][$_POST['how_oos']]) : '', 'need_insure' => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0, 'user_id' => $_SESSION['user_id'], 'add_time' => time(), 'order_status' => OS_UNCONFIRMED, 'shipping_status' => SS_UNSHIPPED, 'pay_status' => PS_UNPAYED, 'agency_id' => get_agency_by_regions(array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'])), 'point_id' => $point_id ? $point_id : 0);
		if (empty($order['shipping_id']) || empty($order['pay_id'])) {
			show_message('请选择配送方式或者支付方式');
		}

		$user_id = $_SESSION['user_id'];

		if (0 < $user_id) {
			$user_info = user_info($user_id);
			$order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);

			if ($order['surplus'] < 0) {
				$order['surplus'] = 0;
			}

			$user_points = $user_info['pay_points'];
			$order['integral'] = min($order['integral'], $user_points, $flow_points);

			if ($order['integral'] < 0) {
				$order['integral'] = 0;
			}
		}
		else {
			$order['surplus'] = 0;
			$order['integral'] = 0;
		}

		foreach ($consignee as $key => $value) {
			if (!is_array($value)) {
				if ($key != 'shipping_dateStr') {
					$order[$key] = addslashes($value);
				}
				else {
					$order[$key] = addslashes($order['shipping_dateStr']);
				}
			}
		}

		$cart_goods = zc_cart_goods($_SESSION['pid'], $_SESSION['id'], $_SESSION['number']);

		if (empty($cart_goods)) {
			show_message(l('no_goods_in_cart'), l('back_home'), './', 'warning');
		}

		$pay_type = 0;
		$total = zc_order_fee($order, $cart_goods, $consignee);
		$order['bonus'] = $total['bonus'];
		$order['goods_amount'] = $total['goods_price'];
		$order['discount'] = $total['discount'] ? $total['discount'] : 0;
		$order['surplus'] = $total['surplus'];
		$order['tax'] = $total['tax'];

		if (!empty($order['shipping_id'])) {
			if (count($_POST['shipping']) == 1) {
				$shipping = shipping_info($order['shipping_id']);
			}

			$order['shipping_name'] = addslashes($shipping['shipping_name']);
		}

		$order['shipping_fee'] = $total['shipping_fee'];
		$order['insure_fee'] = $total['shipping_insure'];

		if (0 < $order['pay_id']) {
			$payment = payment_info($order['pay_id']);
			$order['pay_name'] = strip_tags($payment['pay_name']);
		}

		$order['pay_fee'] = $total['pay_fee'];
		$order['cod_fee'] = $total['cod_fee'];

		if (0 < $order['pack_id']) {
			$pack = pack_info($order['pack_id']);
			$order['pack_name'] = addslashes($pack['pack_name']);
		}

		$order['pack_fee'] = $total['pack_fee'];

		if (0 < $order['card_id']) {
			$card = card_info($order['card_id']);
			$order['card_name'] = addslashes($card['card_name']);
		}

		$order['card_fee'] = $total['card_fee'];
		$order['order_amount'] = number_format($total['amount'], 2, '.', '');
		if (($payment['pay_code'] == 'balance') && (0 < $order['order_amount'])) {
			if (0 < $order['surplus']) {
				$order['order_amount'] = $order['order_amount'] + $order['surplus'];
				$order['surplus'] = 0;
			}

			if (($user_info['user_money'] + $user_info['credit_line']) < $order['order_amount']) {
				show_message(l('balance_not_enough'), l('back_up_page'), url('checkout') . $where_flow);
			}
			else if ($_SESSION['flow_type'] == CART_PRESALE_GOODS) {
				$order['surplus'] = $order['order_amount'];
				$order['pay_status'] = PS_PAYED_PART;
				$order['order_status'] = OS_CONFIRMED;
				$order['order_amount'] = ($order['goods_amount'] + $order['shipping_fee'] + $order['insure_fee'] + $order['tax']) - $order['discount'] - $order['surplus'];
			}
			else {
				$order['surplus'] = $order['order_amount'];
				$order['order_amount'] = 0;
			}
		}

		if ($order['order_amount'] <= 0) {
			$order['order_status'] = OS_CONFIRMED;
			$order['confirm_time'] = time();
			$order['pay_status'] = PS_PAYED;
			$order['pay_time'] = time();
			$order['order_amount'] = 0;
		}

		$order['integral_money'] = $total['integral_money'];
		$order['integral'] = $total['integral'];

		if ($order['extension_code'] == 'exchange_goods') {
			$order['integral_money'] = 0;
			$order['integral'] = $total['exchange_integral'];
		}

		$order['from_ad'] = !empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
		$order['referer'] = !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : addslashes(l('self_site'));
		$order['is_zc_order'] = $_SESSION['number'];
		$order['zc_goods_id'] = $_SESSION['id'];

		if ($flow_type != CART_GENERAL_GOODS) {
			$order['extension_code'] = $_SESSION['extension_code'];
			$order['extension_id'] = $_SESSION['extension_id'];
		}

		do {
			$order['order_sn'] = get_order_sn();
			$new_order = $this->db->filter_field('order_info', $order);
			$new_order_id = $this->db->table('order_info')->data($new_order)->add();
			$error_no = $GLOBALS['db']->errno();
			if ((0 < $error_no) && ($error_no != 1062)) {
				exit($GLOBALS['db']->errno());
			}
		} while ($error_no == 1062);

		$order['order_id'] = $new_order_id;
		if ((0 < $order['user_id']) && (0 < $order['surplus'])) {
			log_account_change($order['user_id'], $order['surplus'] * -1, 0, 0, 0, '订单:' . $order['order_sn'], $order['order_sn']);
			update_zc_project($order['order_id']);
		}

		if ((0 < $order['user_id']) && (0 < $order['integral'])) {
			log_account_change($order['user_id'], 0, 0, 0, $order['integral'] * -1, sprintf(l('pay_order'), $order['order_sn']));
		}

		$order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);
		$payment = payment_info($order['pay_id']);
		$order['pay_code'] = $payment['pay_code'];

		if (0 < $order['order_amount']) {
			include_once ADDONS_PATH . 'payment/' . $payment['pay_code'] . '.php';
			$pay_obj = new $payment['pay_code']();
			$pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
			$order['pay_desc'] = $payment['pay_desc'];
			$this->assign('pay_online', $pay_online);
		}

		if (!empty($order['shipping_name'])) {
			$order['shipping_name'] = trim(stripcslashes($order['shipping_name']));
		}

		$this->assign('order', $order);
		$this->assign('total', $total);
		$this->assign('goods_list', $cart_goods);
		$this->assign('page_title', l('order_success'));
		$this->assign('order_submit_back', sprintf($GLOBALS['LANG']['order_submit_back'], $GLOBALS['LANG']['back_home'], $GLOBALS['LANG']['goto_user_center']));
		unset($_SESSION['flow_consignee']);
		unset($_SESSION['flow_order']);
		unset($_SESSION['direct_shopping']);
		unset($_SESSION['id']);
		unset($_SESSION['pid']);
		unset($_SESSION['number']);
		$this->display();
	}

	public function actionAddressList()
	{
		if (IS_AJAX) {
			$id = i('address_id');
			drop_consignee($id);
			unset($_SESSION['flow_consignee']);
			exit();
		}

		$user_id = $_SESSION['user_id'];

		if (0 < $_SESSION['user_id']) {
			$consignee_list = get_consignee_list($_SESSION['user_id']);
		}
		else if (isset($_SESSION['flow_consignee'])) {
			$consignee_list = array($_SESSION['flow_consignee']);
		}
		else {
			$consignee_list[] = array('country' => c('shop.shop_country'));
		}

		$this->assign('name_of_region', array(c('shop.name_of_region_1'), c('shop.name_of_region_2'), c('shop.name_of_region_3'), c('shop.name_of_region_4')));

		if ($consignee_list) {
			foreach ($consignee_list as $k => $v) {
				$address = '';

				if ($v['province']) {
					$res = get_region_name($v['province']);
					$address .= $res['region_name'];
				}

				if ($v['city']) {
					$ress = get_region_name($v['city']);
					$address .= $ress['region_name'];
				}

				if ($v['district']) {
					$resss = get_region_name($v['district']);
					$address .= $resss['region_name'];
				}

				$consignee_list[$k]['address'] = $address . ' ' . $v['address'];
				$consignee_list[$k]['url'] = url('user/edit_address', array('id' => $v['address_id']));
			}
		}

		$default_id = $this->db->getOne('SELECT address_id FROM {pre}users WHERE user_id=\'' . $user_id . '\'');
		$address_id = $_SESSION['flow_consignee']['address_id'];
		$this->assign('defulte_id', $default_id);
		$this->assign('address_id', $address_id);
		$this->assign('consignee_list', $consignee_list);
		$this->assign('page_title', '收货地址');
		$this->display();
	}

	public function actionAddAddress()
	{
		if (IS_POST) {
			$consignee = array('address_id' => i('address_id'), 'consignee' => i('consignee'), 'country' => 1, 'province' => i('province_region_id'), 'city' => i('city_region_id'), 'district' => i('district_region_id'), 'email' => i('email'), 'address' => i('address'), 'zipcode' => i('zipcode'), 'tel' => i('tel'), 'mobile' => i('mobile'), 'sign_building' => i('sign_building'), 'best_time' => i('best_time'), 'user_id' => $_SESSION['user_id']);

			if (empty($consignee['consignee'])) {
				show_message('收货人不能为空');
			}

			if (empty($consignee['mobile'])) {
				show_message('收货联系方式不能为空');
			}

			if (!preg_match('/^1[3|5|8|7|4]\\d{9}$/', $consignee['mobile'])) {
				show_message('手机号码格式不正确');
			}

			if (empty($consignee['address'])) {
				show_message('详细地址不能为空');
			}

			$limit_address = $this->db->getOne('select count(address_id) from {pre}user_address where user_id = \'' . $consignee['user_id'] . '\'');

			if (5 < $limit_address) {
				show_message('最多只能保存5个收货地址');
			}

			if (0 < $_SESSION['user_id']) {
				save_consignee($consignee, false);
			}

			$_SESSION['flow_consignee'] = stripslashes_deep($consignee);
			ecs_header('Location: ' . url('crowd_funding/index/checkout') . "\n");
			exit();
		}

		$this->assign('user_id', $_SESSION['user_id']);
		$this->assign('country_list', get_regions());
		$this->assign('shop_country', c('shop.shop_country'));
		$this->assign('shop_province_list', get_regions(1, c('shop.shop_country')));
		$this->assign('address_id', i('address_id'));
		$province_list = get_regions(1, c('shop.shop_country'));
		$this->assign('province_list', $province_list);
		$city_list = get_region_city_county($this->province_id);

		if ($city_list) {
			foreach ($city_list as $k => $v) {
				$city_list[$k]['district_list'] = get_region_city_county($v['region_id']);
			}
		}

		$this->assign('city_list', $city_list);
		$district_list = get_region_city_county($this->city_id);
		$this->assign('district_list', $district_list);
		$this->assign('page_title', '新增收货地址');
		$this->display();
	}

	public function actionEditAddress()
	{
		if (IS_POST) {
			$consignee = array('address_id' => i('address_id'), 'consignee' => i('consignee'), 'country' => 1, 'province' => i('province_region_id'), 'city' => i('city_region_id'), 'district' => i('district_region_id'), 'email' => i('email'), 'address' => i('address'), 'zipcode' => i('zipcode'), 'tel' => i('tel'), 'mobile' => i('mobile'), 'sign_building' => i('sign_building'), 'best_time' => i('best_time'), 'user_id' => $_SESSION['user_id']);

			if (empty($consignee['consignee'])) {
				show_message('收货人不能为空');
			}

			if (empty($consignee['mobile'])) {
				show_message('收货联系方式不能为空');
			}

			if (!preg_match('/^1[3|5|8|7|4]\\d{9}$/', $consignee['mobile'])) {
				show_message('手机号码格式不正确');
			}

			if (empty($consignee['address'])) {
				show_message('详细地址不能为空');
			}

			$limit_address = $this->db->getOne('select count(address_id) from {pre}user_address where user_id = \'' . $consignee['user_id'] . '\'');

			if (5 < $limit_address) {
				show_message('最多只能保存5个收货地址');
			}

			if (0 < $_SESSION['user_id']) {
				save_consignee($consignee, TRUE);
			}

			$_SESSION['flow_consignee'] = stripslashes_deep($consignee);
			ecs_header('Location: ' . url('crowd_funding/index/checkout') . "\n");
			exit();
		}

		$this->assign('user_id', $_SESSION['user_id']);
		$this->assign('country_list', get_regions());
		$this->assign('shop_country', c('shop.shop_country'));
		$this->assign('shop_province_list', get_regions(1, c('shop.shop_country')));
		$this->assign('address_id', i('address_id'));
		$province_list = get_regions(1, c('shop.shop_country'));
		$this->assign('province_list', $province_list);
		$city_list = get_region_city_county($this->province_id);

		if ($city_list) {
			foreach ($city_list as $k => $v) {
				$city_list[$k]['district_list'] = get_region_city_county($v['region_id']);
			}
		}

		if (i('address_id')) {
			$address_id = $_GET['address_id'];
			$consignee_list = $this->db->getRow('SELECT * FROM {pre}user_address WHERE user_id=\'' . $_SESSION['user_id'] . ']\' AND address_id=\'' . $address_id . '\'');

			if (empty($consignee_list)) {
				show_message('您没有此收货地址');
			}

			$c = get_region_name($consignee_list['province']);
			$cc = get_region_name($consignee_list['city']);
			$ccc = get_region_name($consignee_list['district']);
			$consignee_list['province'] = $c['region_name'];
			$consignee_list['city'] = $cc['region_name'];
			$consignee_list['district'] = $ccc['region_name'];
			$consignee_list['province_id'] = $c['region_id'];
			$consignee_list['city_id'] = $cc['region_id'];
			$consignee_list['district_id'] = $ccc['region_id'];
			$city_list = get_region_city_county($c['region_id']);

			if ($city_list) {
				foreach ($city_list as $k => $v) {
					$city_list[$k]['district_list'] = get_region_city_county($v['region_id']);
				}
			}

			$this->assign('consignee_list', $consignee_list);
		}

		$this->assign('city_list', $city_list);
		$district_list = get_region_city_county($this->city_id);
		$this->assign('district_list', $district_list);
		$this->assign('page_title', '修改收货地址');
		$this->display();
	}

	public function actionSetAddress()
	{
		if (IS_AJAX) {
			$user_id = session('user_id');
			$address_id = (isset($_REQUEST['address_id']) ? intval($_REQUEST['address_id']) : 0);
			$sql = 'UPDATE {pre}users SET address_id = \'' . $address_id . '\' WHERE user_id = \'' . $user_id . '\'';
			$this->db->query($sql);
			unset($_SESSION['flow_consignee']);
			$sql = 'SELECT * FROM {pre}user_address WHERE address_id = \'' . $address_id . '\' AND user_id = \'' . $user_id . '\'';
			$address = $this->db->getRow($sql);

			if (!empty($address)) {
				$_SESSION['flow_consignee'] = $address;
			}

			echo json_encode(array('url' => url('checkout'), 'status' => 1));
		}
	}

	public function actionClearhistory()
	{
		if (IS_AJAX && IS_AJAX) {
			cookie('ZCECS[keywords]', '');
			echo json_encode(array('status' => 1));
		}
		else {
			echo json_encode(array('status' => 0));
		}
	}

	public function actionShippingfee()
	{
		if (IS_AJAX) {
			$result = array('error' => 0, 'massage' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1);
			$flow_type = (isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS);
			$shipping_type = (isset($_REQUEST['shipping']) ? intval($_REQUEST['shipping']) : 0);
			$consignee = get_consignee($_SESSION['user_id']);
			$cart_goods = zc_cart_goods($_SESSION['pid'], $_SESSION['id'], $_SESSION['number']);
			if (empty($cart_goods) || !zc_check_consignee_info($consignee)) {
				if (empty($cart_goods)) {
					$result['error'] = 1;
				}
				else if (!check_consignee_info($consignee, $flow_type)) {
					$result['error'] = 2;
				}
			}
			else {
				$this->assign('config', c('shop'));
				$order = flow_order_info();
				$_SESSION['flow_order'] = $order;

				if ($shipping_type == 1) {
					if (is_array($_SESSION['shipping_type_ru_id'])) {
						$_SESSION['shipping_type_ru_id'][$ru_id] = $ru_id;
					}
				}
				else if (isset($_SESSION['shipping_type_ru_id'][$ru_id])) {
					unset($_SESSION['shipping_type_ru_id'][$ru_id]);
				}

				$order['shipping_id'] = $shipping_type;
				$total = zc_order_fee($order, $cart_goods, $consignee);
				$this->assign('total', $total);

				if ($flow_type == CART_GROUP_BUY_GOODS) {
					$this->assign('is_group_buy', 1);
				}

				$result['amount'] = $total['amount_formated'];
				$result['content'] = $this->fetch('order_total');
			}

			exit(json_encode($result));
		}
	}
}

?>
