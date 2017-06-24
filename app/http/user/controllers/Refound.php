<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\user\controllers;

class Refound extends \app\http\base\controllers\Frontend
{
	private $user_id;

	public function __construct()
	{
		parent::__construct();
		$this->user_id = $_SESSION['user_id'];
		$this->actionchecklogin();
		l(require LANG_PATH . c('shop.lang') . '/user.php');
		l(require LANG_PATH . c('shop.lang') . '/flow.php');
		$files = array('order', 'clips', 'payment', 'transaction');
		$this->load_helper($files);
	}

	public function actionIndex()
	{
		$order_id = i('order_id', 0, 'intval');
		$page = i('page', 1);
		$size = i('size', 1);
		$type = i('type', 0);
		$record_count = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('order_return') . ' WHERE user_id =' . $_SESSION['user_id']);

		if (IS_AJAX) {
			if ($type == 1) {
				$return_list = return_order($order_id);
				exit(json_encode(array('refound_list' => $return_list, 'totalPage' => ceil($record_count / $size))));
			}
			else {
				$order_list = get_all_return_order($order_id);
				exit(json_encode(array('order_list' => $order_list, 'totalPage' => ceil($record_count / $size))));
			}
		}

		$this->assign('page_title', l('return'));
		$this->assign('order_id', $order_id);
		$this->display();
	}

	public function actionApplyReturn()
	{
		$return_rec_id = i('order_goods_id');

		if (empty($return_rec_id)) {
			show_message('找不到对应的订单商品', '', '', 'info', true);
		}

		$sql = ' SELECT rec_id FROM ' . $GLOBALS['ecs']->table('order_return') . ' WHERE rec_id = ' . $return_rec_id;
		$order_return = $GLOBALS['db']->getOne($sql);

		if ($order_return) {
			show_message('同一订单的同一商品不能重复提交', '', '', 'info', true);
		}

		$sql = ' SELECT order_id FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE order_id = \'' . $_REQUEST['order_id'] . '\' AND shipping_status > 0 ';
		$return_allowable = $GLOBALS['db']->getOne($sql);
		$this->assign('return_allowable', $return_allowable);
		$this->assign('return_rec_id', $return_rec_id);
		$parent_cause = get_parent_cause();
		$this->assign('cause_list', $parent_cause);
		$this->assign('country_list', get_regions());
		$this->assign('shop_country', c('shop.shop_country'));
		$this->assign('shop_province_list', get_regions(1, c('shop.shop_country')));
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
		$sql = ' SELECT province, city, district FROM ' . $GLOBALS['ecs']->table('user_address') . ' WHERE user_id = ' . $_SESSION['user_id'];
		$user_address = $GLOBALS['db']->getRow($sql);
		$this->assign('user_address_id', $user_address);
		$user_address = get_goods_region_name($user_address['province']) . ' ' . get_goods_region_name($user_address['city']) . ' ' . get_goods_region_name($user_address['district']);
		$goods = get_order_goods_info($return_rec_id);
		$this->assign('goods', $goods);
		$this->assign('user_address', $user_address);
		$this->assign('user_id', $_SESSION['user_id']);
		$this->assign('page_title', '申请退换货');
		$this->display();
	}

	public function actionSubmitReturn()
	{
		$rec_id = (empty($_REQUEST['return_rec_id']) ? 0 : intval($_REQUEST['return_rec_id']));
		$last_option = (!isset($_REQUEST['last_option']) ? $_REQUEST['parent_id'] : $_REQUEST['last_option']);
		$return_remark = (!isset($_REQUEST['return_remark']) ? '' : htmlspecialchars(trim($_REQUEST['return_remark'])));
		$return_brief = (!isset($_REQUEST['return_brief']) ? '' : htmlspecialchars(trim($_REQUEST['return_brief'])));

		if (empty($last_option)) {
			show_message('退货原因不能为空', '', '', 'info', true);
		}

		if (0 < $rec_id) {
			$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('order_return') . ' WHERE rec_id = ' . $rec_id;
			$num = $GLOBALS['db']->getOne($sql);

			if (0 < $num) {
				show_message('同一订单的同一商品不能重复提交', '', '', 'info', true);
			}
		}
		else {
			show_message('退换货提交出现异常，请稍后重试', '', '', 'info', true);
		}

		$sql = 'select g.goods_name, g.goods_sn,g.brand_id, og.order_id, og.goods_id, og.product_id, og.goods_attr, og.warehouse_id, og.area_id, o.order_sn, ' . ' og.is_real, og.goods_attr_id, og.goods_price, og.goods_price, og.goods_number, o.user_id ' . 'from ' . $GLOBALS['ecs']->table('order_goods') . ' as og ' . ' left join ' . $GLOBALS['ecs']->table('goods') . ' as g on og.goods_id = g.goods_id ' . ' left join ' . $GLOBALS['ecs']->table('order_info') . ' as o on o.order_id = og.order_id ' . ' where og.rec_id = \'' . $rec_id . '\'';
		$order_goods = $GLOBALS['db']->getRow($sql);

		if ($order_goods['user_id'] != $_SESSION['user_id']) {
			show_message('退换货提交出现异常，请稍后重试', '', '', 'info', true);
		}

		$sql = ' SELECT order_sn, country,province,city ,district FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE order_id =' . $order_goods['order_id'];
		$res = $GLOBALS['db']->getRow($sql);
		$return_number = (empty($_REQUEST['goods_number']) ? 1 : intval($_REQUEST['goods_number']));
		$return_type = intval($_REQUEST['return_type']);
		$maintain = 0;

		if ($return_type == 1) {
			$back = 1;
			$exchange = 0;
		}
		else if ($return_type == 2) {
			$back = 0;
			$exchange = 2;
		}
		else {
			$back = 0;
			$exchange = 0;
		}

		$attr_val = (isset($_REQUEST['attr_val']) ? $_REQUEST['attr_val'] : array());
		$return_attr_id = (!empty($attr_val) ? implode(',', $attr_val) : '');
		$attr_val = get_goods_attr_info_new($attr_val, 'pice', $order_goods['warehouse_id'], $order_goods['area_id']);
		$order_return = array('rec_id' => $rec_id, 'goods_id' => $order_goods['goods_id'], 'order_id' => $order_goods['order_id'], 'order_sn' => $order_goods['order_sn'], 'return_type' => $return_type, 'maintain' => $maintain, 'back' => $back, 'exchange' => $exchange, 'user_id' => $_SESSION['user_id'], 'goods_attr' => $order_goods['goods_attr'], 'attr_val' => $attr_val, 'return_brief' => $return_brief, 'remark' => $return_remark, 'credentials' => $_REQUEST['credentials'] == 0 ? 0 : intval($_REQUEST['credentials']), 'country' => empty($_REQUEST['country']) ? 0 : intval($_REQUEST['country']), 'province' => empty($_REQUEST['province_region_id']) ? 0 : intval($_REQUEST['province_region_id']), 'city' => empty($_REQUEST['city_region_id']) ? 0 : intval($_REQUEST['city_region_id']), 'district' => empty($_REQUEST['district_region_id']) ? 0 : intval($_REQUEST['district_region_id']), 'cause_id' => $last_option, 'apply_time' => gmtime(), 'actual_return' => '', 'address' => empty($_REQUEST['return_address']) ? '' : htmlspecialchars(trim($_REQUEST['return_address'])), 'zipcode' => empty($_REQUEST['code']) ? '' : intval($_REQUEST['code']), 'addressee' => empty($_REQUEST['addressee']) ? '' : htmlspecialchars(trim($_REQUEST['addressee'])), 'phone' => empty($_REQUEST['mobile']) ? '' : htmlspecialchars(trim($_REQUEST['mobile'])), 'return_status' => '0');
		$order_return['should_return'] = get_return_refound($order_return['order_id'], $order_return['rec_id'], $return_number);
		$error_no = 0;

		do {
			$order_return['return_sn'] = get_order_sn();
			$query = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return'), $order_return, 'INSERT', '', 'SILENT');
			$error_no = $GLOBALS['db']->errno();
			if ((0 < $error_no) && ($error_no != 1062)) {
				exit($GLOBALS['db']->errorMsg());
			}
		} while ($error_no == 1062);

		if ($query) {
			$sql = 'select ret_id from ' . $GLOBALS['ecs']->table('order_return') . ' where return_sn = \'' . $order_return['return_sn'] . '\'';
			$ret_id = $GLOBALS['db']->query($sql);
			$ret_id = $ret_id[0]['ret_id'];
			return_action($ret_id, '申请退款（由用户寄回）', '', $order_return['remark'], '买家');
			$return_goods['rec_id'] = $order_return['rec_id'];
			$return_goods['ret_id'] = $ret_id;
			$return_goods['goods_id'] = $order_goods['goods_id'];
			$return_goods['goods_name'] = $order_goods['goods_name'];
			$return_goods['brand_name'] = $order_goods['brand_name'];
			$return_goods['product_id'] = $order_goods['product_id'];
			$return_goods['goods_sn'] = $order_goods['goods_sn'];
			$return_goods['is_real'] = $order_goods['is_real'];
			$return_goods['goods_attr'] = $order_goods['goods_attr'];
			$return_goods['attr_id'] = $order_goods['goods_attr_id'];
			$return_goods['refound'] = $order_goods['goods_price'];
			$return_goods['return_type'] = $return_type;
			$return_goods['return_number'] = $return_number;

			if ($return_type == 1) {
				$return_goods['out_attr'] = '';
			}
			else if ($return_type == 2) {
				$return_goods['out_attr'] = $order_return['attr_val'];
				$return_goods['return_attr_id'] = $return_attr_id;
			}
			else {
				$return_goods['out_attr'] = '';
			}

			$query = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('return_goods'), $return_goods, 'INSERT', '', 'SILENT');
			$sql = 'select count(*) from' . $GLOBALS['ecs']->table('return_images') . ' where rec_id = \'' . $rec_id . '\' and user_id = \'' . $_SESSION['user_id'] . '\'';
			$images_count = $GLOBALS['db']->getOne($sql);

			if (0 < $images_count) {
				$images['rg_id'] = $order_goods['goods_id'];
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('return_images'), $images, 'UPDATE', 'rec_id = \'' . $rec_id . '\' and user_id = \'' . $_SESSION['user_id'] . '\'');
			}

			$order_return_extend = array('ret_id' => $ret_id, 'return_number' => $return_number);
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return_extend'), $order_return_extend, 'INSERT', '', 'SILENT');
			$address_detail = get_consignee_info($order_goods['order_id'], $order_return['address']);
			$order_return['address_detail'] = $address_detail;
			$order_return['apply_time'] = local_date('Y-m-d H:i:s', $order_return['apply_time']);
			show_message('申请提交成功，工作人员将尽快审核！', '查看退换货订单', url('detail', array('ret_id' => $ret_id)), 'info', true, $order_return);
		}
		else {
			show_message('申请提交出现了异常，请稍后重试', '', '', 'info', true);
		}
	}

	public function actionImgReturn()
	{
		$img = $_FILES['myfile']['tmp_name'];
		list($width, $height, $type) = getimagesize($img);

		if (empty($img)) {
			return NULL;
		}

		$user_id = $_SESSION['user_id'];
		$rec_id = i('rec_id');
		$sql = 'SELECT count(*) FROM' . $GLOBALS['ecs']->table('return_images') . 'WHERE user_id = ' . $user_id . ' and rec_id = ' . $rec_id;
		$res = $GLOBALS['db']->getOne($sql);

		if (5 <= $res) {
			echo json_encode(array('error' => 1, 'content' => '图片不能超过5张'));
			return NULL;
		}

		if (empty($type)) {
			echo json_encode(array('error' => 1, 'content' => '图片类型不正确'));
			return NULL;
		}

		$result = $this->upload('data/return_images', false, 2, array(600, 600));
		$path = $result['url']['myfile']['url'];
		$add_time = gmtime();
		$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('return_images') . ' (rec_id,user_id,img_file,add_time)values(' . $rec_id . ',' . $user_id . ',\'' . $path . '\',' . $add_time . ')';
		$GLOBALS['db']->query($sql);
		$sql = 'SELECT id, img_file FROM' . $GLOBALS['ecs']->table('return_images') . 'WHERE user_id = ' . $user_id . ' and rec_id = ' . $rec_id;
		$res = $GLOBALS['db']->query($sql);
		$img = array();

		foreach ($res as $key => $val) {
			$img[$key]['id'] = $val['id'];
			$img[$key]['pic'] = get_image_path($val['img_file']);
		}

		echo json_encode($img);
	}

	public function actionClearPictures()
	{
		$id = (isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);
		$rec_id = (isset($_REQUEST['rec_id']) ? intval($_REQUEST['rec_id']) : 0);
		$result = array('error' => 0, 'content' => '');
		$sql = 'select img_file from ' . $GLOBALS['ecs']->table('return_images') . ' where user_id = \'' . $_SESSION['user_id'] . '\' and rec_id = \'' . $rec_id . '\'' . ' and id=' . $id;
		$img_list = $GLOBALS['db']->getAll($sql);

		foreach ($img_list as $key => $row) {
			get_oss_del_file(array($row['img_file']));
			@unlink(get_image_path($row['img_file']));
		}

		$sql = 'delete from ' . $GLOBALS['ecs']->table('return_images') . ' where user_id = \'' . $_SESSION['user_id'] . '\' and rec_id = \'' . $rec_id . '\'' . ' and id=' . $id;
		$GLOBALS['db']->query($sql);
		echo json_encode($result);
	}

	public function actionDetail()
	{
		$ret_id = (isset($_GET['ret_id']) ? intval($_GET['ret_id']) : 0);
		$order = get_return_detail($ret_id);

		if ($order === false) {
			$this->err->show('退换货列表', url('index'));
			exit();
		}

		$this->assign('page_title', '退换货详情');
		$this->assign('return_detail', $order);
		$this->display();
	}

	public function actionCancel()
	{
		$user_id = $_SESSION['user_id'];
		$ret_id = (isset($_GET['ret_id']) ? intval($_GET['ret_id']) : 0);

		if (cancel_return($ret_id, $user_id)) {
			show_message('取消成功', '退换货列表', url('index'));
		}
		else {
			$this->err->show('我的退换货单列表', url('index'));
		}
	}

	public function actionOrderTracking()
	{
		$order_id = i('order_id', 0, 'intval');
		$order = get_order_detail($order_id, $this->user_id);

		if ($order === false) {
			$this->err->show(l('back_home_lnk'), './');
			exit();
		}

		if ($order['invoice_no']) {
			preg_match('/^<a.*href="(.*?)">/is', $order['invoice_no'], $url);

			if ($url[1]) {
				redirect($url[1]);
			}
		}

		show_message(l('msg_unfilled_or_receive'), l('user_center'), url('user/index/index'));
	}

	public function actionchecklogin()
	{
		if (!$this->user_id) {
			$url = urlencode(__HOST__ . $_SERVER['REQUEST_URI']);

			if (IS_POST) {
				$url = urlencode($_SERVER['HTTP_REFERER']);
			}

			ecs_header('Location: ' . url('user/login/index', array('back_act' => $url)));
			exit();
		}
	}

	public function actionGetSpec()
	{
		$result = array('error' => 0, 'message' => '', 'attr_val' => '');
		$rec_id = i('id', 0);
		$sql = 'select warehouse_id, area_id, goods_id from ' . $GLOBALS['ecs']->table('order_goods') . ' where rec_id = \'' . $rec_id . '\'';
		$order_goods = $GLOBALS['db']->getRow($sql);
		$g_id = $order_goods['goods_id'];
		if (($rec_id == 0) || ($g_id == 0) || empty($order_goods)) {
			$result['err_msg'] = '获取不到属性值';
			$result['err_no'] = 1;
		}
		else {
			$properties = get_goods_properties($g_id, $order_goods['warehouse_id'], $order_goods['area_id']);
			$spec = $properties['spe'];
			$result['spec'] = $spec;
		}

		exit(json_encode($result));
	}
}

?>
