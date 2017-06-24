<?php
namespace app\http\drp\controllers;

use app\http\base\controllers\Backend;
use PHPExcel\PHPExcel;
use PHPExcel\PHPExcel_IOFactory;

class Admin extends Backend
{

    public function __construct()
    {
        parent::__construct();
        include_once(BASE_PATH . 'http/' . MODULE_NAME . '/helpers/function_helper.php');
        L(require(MODULE_PATH  .'language/'. C('shop.lang') . '/drp.php'));
        $this->assign('lang', array_change_key_case(L()));
        $files = array(
            'scecmoban'
        );
        $this->load_helper($files);
		// 初始化 每页分页数量
		$this->page_num = 10;
        $this->assign('page_num', $this->page_num);
    }

    /**
     * 店铺设置
     */
    public function actionConfig()
    {
        $this->admin_priv('drp_config');
        if (IS_POST) {
            $data_list = I('post.data');
            if (empty($data_list)) {
                $this->message(L('request_error'), null, 2);
            }
            foreach ($data_list as $k => $v) {
                $where = array();
                $data = array();
                $where['code'] = $k;
                $data['value'] = $v;
                $this->model->table('drp_config')->data($data)->where($where)->save();
            }
            $this->redirect('config');
        }
        $config = $this->model->table('drp_config')->order('sort_order ASC')->select();
        $this->assign('list', $config);
        $this->display();
    }
	 /**
     * 分成比例设置
     */
    public function actionDrpsetconfig()
    {
        $this->admin_priv('drp_set_config');
        $drp_affiliate = get_drp_affiliate_config();
        if (is_array($drp_affiliate['item'])) foreach ($drp_affiliate['item'] as $kk => $vv) {
            $drp_affiliate['item'][$kk]['order'] = ($kk + 1);
        }
        $this->assign('drp_a_config', $drp_affiliate);
        $this->display();
    }

    public function actionAjaxeditdrp()
    {
        if (IS_POST) {
            $drp_affiliate = get_drp_affiliate_config();
            $type = I('post.type');
            $val = I('post.val');
            $edit_key = I('post.edit_key');
            $max = 100;
            if ($edit_key >= 0 && isset($edit_key) && $edit_key !='') {
                //每级别比例
                foreach ($drp_affiliate['item'] as $k => $v) {
                    if ($k != $edit_key) {
                        $max -= $v[$type];
                    }
                }
                $val > $max && $val = $max;

                if (!empty($val) && strpos($val, '%') === false) {
                    $val .= '%';
                }
                $drp_affiliate['item'][$edit_key][$type] = $val;
            } else {
                //总比例 和 提现天数
                if($type == 'on'){
                    $drp_affiliate['on'] = $val;
                }else{
                    $drp_affiliate['config'][$type] = $val;
                }
            }
            $this->put_drp_affiliate($drp_affiliate);
            $result['val'] = $val;
            $result['msg'] = L('edit_success');
            die(json_encode($result));
        }
    }

    private function put_drp_affiliate($drp_affiliate)
    {
        $temp = serialize($drp_affiliate);
        $sql = "UPDATE " . $GLOBALS['ecs']->table('drp_config') .
            "SET  value = '$temp'" .
            "WHERE code = 'drp_affiliate'";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 分销商管理
     */
    public function actionShop()
    {
        $this->admin_priv('drp_shop');
        $where = "";
        if (IS_POST) {
            $shop_name = I('post.shop_name');
            $real_name = I('post.real_name');
            $mobile = I('post.mobile');
            $user_name = I('post.user_name');
            if (!empty($shop_name)) {
                $where = ' and (s.shop_name like "%' . $shop_name . '%" or s.real_name like "%' . $shop_name . '%" or s.mobile like "%' . $shop_name . '%")';
            }
            if (!empty($real_name)) {
                $where .= " AND s.real_name like '%" . $real_name . "%'";
            }
            if (!empty($mobile)) {
                $where .= " AND s.mobile like '%" . $mobile . "%'";
            }
            if (!empty($user_name)) {
                $where .= " AND u.user_name like '%" . $user_name . "%'";
            }
        }
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('shop', $filter), $this->page_num);
        $sql_count = "SELECT count(*) as count FROM {pre}drp_shop s LEFT JOIN {pre}users u ON s.user_id = u.user_id WHERE 1 " . $where . " ORDER BY create_time DESC";
        $total = $this->model->query($sql_count);
        $this->assign('page', $this->pageShow($total[0]['count']));
        $sql = "SELECT s.*, u.user_name ,u.parent_id FROM {pre}drp_shop s LEFT JOIN {pre}users u ON s.user_id = u.user_id WHERE 1 " . $where . " ORDER BY create_time DESC LIMIT " . $offset;
        $list = $this->model->query($sql);
        foreach($list as $key => $val){
            if($val['parent_id'] > 0){
                $list[$key]['parent_name'] = parent_name($val['parent_id']);
            }
            $list[$key]['create_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['create_time']);
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 改变分销商状态
     */
    public function actionSetShop()
    {
        $id = I('get.id', 0, 'intval');
        if (empty($id)) {
            $this->message(L('select_shop'), NULL, 2);
        }
        if (isset($_GET['audit'])) {
            $data['audit'] = I('get.audit', 1, 'intval');
        }
        if (isset($_GET['status'])) {
            $data['status'] = I('get.status', 0, 'intval');
        }
        if (!empty($data)) {
            $this->model->table('drp_shop')->data($data)->where(array('id' => $id))->save();
        }
        $this->redirect('shop');
    }

    /**
    * 下线会员
    */
    public function actionAfflist()
    {
        $this->admin_priv('drp_shop');
        $user_id = I('get.user_id', 0, 'intval');
        $where = "";
        if (IS_POST) {
            $keyword = I('post.keyword');
            $user_id = I('post.user_id');
            if (!empty($keyword)) {
                $where = ' and ( user_name like "%' . $keyword . '%" or mobile_phone like "%' . $keyword . '%")';
            }
        }
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('afflist', $filter), $this->page_num);
        $sql_count = "SELECT count(*) as count FROM {pre}users  WHERE parent_id = '" . $user_id . "'  " . $where . " ORDER BY reg_time DESC";
        $total = $this->model->query($sql_count);
        $this->assign('page', $this->pageShow($total[0]['count']));
        $sql = "SELECT * FROM {pre}users  WHERE parent_id = '" . $user_id . "' " . $where . " ORDER BY reg_time DESC LIMIT " . $offset;
        $list = $this->model->query($sql);
        foreach($list as $key => $val){
            if($val['parent_id'] > 0){
                $list[$key]['reg_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['reg_time']);
            }
        }
        $this->assign('list', $list);
        $this->assign('user_id', $user_id);
        $this->display();
    }



    /**
     * 导出分销商
     */
    public function actionExportShop()
    {
        if (IS_POST) {
            $starttime = I('post.starttime', '', 'strtotime');
            $endtime = I('post.endtime', '', 'strtotime');
            if (empty($starttime) || empty($endtime)) {
                $this->message(L('select_start_end_time'), NULL, 2);
            }
            if ($starttime > $endtime) {
                $this->message(L('start_lt_end_time'), NULL, 2);
            }
            $sql = "SELECT * FROM {pre}drp_shop WHERE create_time >='" . $starttime . "' AND create_time <= '" . $endtime . "' ORDER BY create_time DESC";
            $list = $this->model->query($sql);
            if ($list) {
                $excel = new \PHPExcel();
                //设置单元格宽度
                $excel->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);
                //设置表格的宽度  手动
                $excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $excel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
                $excel->getActiveSheet()->getColumnDimension('H')->setWidth(25);
                $excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
                $excel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
                //设置标题
                $rowVal = array(
                    0 => L('shop_number'),
                    1 => L('shop_name'),
                    2 => L('rely_name'),
                    3 => L('mobile'),
                    4 => L('open_time'),
                    5 => L('shop_audit'),
                    6 => L('shop_state'),
                    7 => L('qq_number')
                );
                foreach ($rowVal as $k => $r) {
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k, 1)->getFont()->setBold(true);//字体加粗
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k, 1)->getAlignment(); //文字居中
                    $excel->getActiveSheet()->setCellValueByColumnAndRow($k, 1, $r);
                }
                //设置当前的sheet索引 用于后续内容操作
                $excel->setActiveSheetIndex(0);
                $objActSheet = $excel->getActiveSheet();
                //设置当前活动的sheet的名称
                $title = "分销商信息";
                $objActSheet->setTitle($title);
                //设置单元格内容
                foreach ($list as $k => $v) {
                    $num = $k + 2;
                    $excel->setActiveSheetIndex(0)
                        //Excel的第A列，uid是你查出数组的键值，下面以此类推
                        ->setCellValue('A' . $num, $v['id'])
                        ->setCellValue('B' . $num, $v['shop_name'])
                        ->setCellValue('C' . $num, $v['real_name'])
                        ->setCellValue('D' . $num, $v['mobile'])
                        ->setCellValue('E' . $num, date("Y-m-d H:i:s", $v['create_time']))
                        ->setCellValue('F' . $num, $v['audit'])
                        ->setCellValue('G' . $num, $v['status'])
                        ->setCellValue('H' . $num, $v['qq']);
                }
                $name = date('Y-m-d'); //设置文件名
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");
                header("Content-Transfer-Encoding:utf-8");
                header("Pragma: no-cache");
                header('Content-Type: application/vnd.ms-e xcel');
                header('Content-Disposition: attachment;filename="' . $title . '_' . urlencode($name) . '.xls"');
                header('Cache-Control: max-age=0');
                $objWriter = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                $objWriter->save('php://output');
                exit;
            }
        }
        $this->redirect('shop');
    }

    /**
     * 分销排行
     */
    public function actionDrpList()
    {
        $this->admin_priv('drp_list');
        $act = I('where');
        if (empty($act)) {
            //全部
            $where = '';
        } elseif ($act == 1) {
            //满一年
            $where = ' and ' . strtotime('-1 year') . '>=d.create_time';
        } elseif ($act == 2) {
            //满半年
            $where = ' and ' . strtotime('-6 month') . '>=d.create_time';
        } elseif ($act == 3) {
            //满一月
            $where = ' and ' . strtotime('-1 month') . '>=d.create_time';
        }
        $filter['where'] = $act;
        $offset = $this->pageLimit(url('drp_list', $filter), $this->page_num);
        $sql = "SELECT count(id) as num FROM {pre}drp_shop as d LEFT JOIN {pre}users as u ON d.user_id=u.user_id WHERE d.audit=1 and d.status=1 " . $where;
        $count = $this->model->query($sql);
        $this->assign('page', $this->pageShow($count[0]['num']));
        $sql = "SELECT d.id, IFNULL(w.nickname,u.user_name) as  name, d.shop_name, d.mobile, FROM_UNIXTIME(d.create_time, '%Y-%m-%d') as time,
                IFNULL((select sum(money) from {pre}drp_affiliate_log where separate_type != -1 and user_id=d.user_id),0) as money
                FROM {pre}drp_shop as d
                LEFT JOIN {pre}users as u ON d.user_id=u.user_id
                LEFT JOIN {pre}wechat_user as w ON d.user_id=w.ect_uid
                LEFT JOIN {pre}drp_affiliate_log as log ON log.user_id=d.user_id
                where d.audit=1 and d.status=1 $where
                GROUP BY d.user_id
                ORDER BY money desc
                LIMIT " . $offset;
        $list = $this->model->query($sql);
        $this->assign('list', $list);
		$this->assign('act', $act);
        $this->display();
    }

    /**
     * 分销订单列表
     **/
    public function actionDrpOrderList()
    {
        $this->admin_priv('drp_order_list');
        $drp_affiliate = get_drp_affiliate_config();
        $able_day = $drp_affiliate['config']['day']?$drp_affiliate['config']['day']:7;
        $sqladd = '';
        $status = I('status', NULL, 'intval');
        if (isset($status)) {
            $sqladd = ' AND o.drp_is_separate = ' . $status;
            $able = I('able', NULL, 'intval');
            if(isset($able) && $able == 1){
                $sqladd .= ' AND ' . strtotime(-$able_day.'day',gmtime()) . '>=o.pay_time';
            }
            if(isset($able) && $able == 2){
                $sqladd .= ' AND ' . strtotime(-$able_day.'day',gmtime()) . '<o.pay_time';
            }
        }

        $condition['user_id'] = $_SESSION['admin_id'];
        $ru_id = $this->model->table('admin_user')->where($condition)->getField('ru_id');
        if ($ru_id) {
            $sqladd .= " AND (SELECT og.ru_id FROM " . $GLOBALS['ecs']->table('order_goods') . " AS og WHERE og.order_id = o.order_id LIMIT 1) = " . $ru_id; //只显示对应商家分销订单
        }
        $sqladd .= " AND (select count(*) from " . $GLOBALS['ecs']->table('order_info') . " as oi2 where oi2.main_order_id = o.order_id) = 0 ";  //主订单下有子订单时，则主订单不显示
        $sqladd .= " AND (select sum(drp_money) from " . $GLOBALS['ecs']->table('order_goods') . " as og2 where og2.order_id = o.order_id) > 0 ";  //订单有分销商品时，才显示
         $order_sn = I('order_sn', '');
        if ($order_sn) {
            $sqladd = " AND o.order_sn like '%" . $order_sn . "%' ";
        }
        $list = array();
        if ($drp_affiliate['on'] == 1) {

            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o" .
                " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" .
                " LEFT JOIN " . $GLOBALS['ecs']->table('drp_affiliate_log') . " a ON o.order_id = a.order_id" .
                " WHERE o.user_id > 0 AND (u.parent_id > 0 AND o.drp_is_separate = 0 OR o.drp_is_separate > 0) AND o.pay_status = ".PS_PAYED." $sqladd";
            $record_count = $GLOBALS['db']->getOne($sql);

            $filter['page'] = '{page}';
            $offset = $this->pageLimit(url('drporderlist', $filter), $this->page_num);
            $this->assign('page', $this->pageShow($record_count));

            $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up,
                    (select ss.shop_name from {pre}seller_shopinfo as ss left join {pre}order_goods as og3
                     on og3.ru_id=ss.ru_id left join {pre}order_info as o
                     on o.order_id=og3.order_id limit 1) as shop_name FROM " .
                $GLOBALS['ecs']->table('order_info') . " o" .
                " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" .
                " LEFT JOIN " . $GLOBALS['ecs']->table('drp_affiliate_log') . " a ON o.order_id = a.order_id" .
                " WHERE o.user_id > 0 AND (u.parent_id > 0 AND o.drp_is_separate = 0 OR o.drp_is_separate > 0) AND o.pay_status = ".PS_PAYED." $sqladd " .
                " ORDER BY order_id DESC" .
                " LIMIT " . $offset;
            $list = $this->model->query($sql);
        }
        if(!empty($list)){
            foreach ($list as $rt) {

                $sql = "select ru_id from {pre}order_goods  where order_id = '".$rt['order_id']."'  limit 1";
                $ru_id = $GLOBALS['db']->getOne($sql);
                $rt['shop_name']  = get_shop_name($ru_id, 1);//商家名称

                if ($rt['up'] > 0) {
                    if((strtotime(-$able_day.'day',gmtime()) >= $rt['pay_time'])){
                        $rt['separate_able'] = 1;
                    }
                }
                if (!empty($rt['suid'])) {
                    //在drp_affiliate_log有记录
                    $rt['info'] = sprintf(L('drp_separate_info'), $rt['suid'], $rt['auser'], $rt['money']);
                    if ($rt['separate_type'] == -1) {
                        //已被撤销
                        $rt['drp_is_separate'] = 3;
                        $rt['info'] = "<s>" . $rt['info'] . "</s>";
                    }
                }
                $order_list[] = $rt;
            }
        }
		$this->assign('status', $status);
		$this->assign('able', $able);
        $this->assign('on', $drp_affiliate['on']);
        $this->assign('able_day', $able_day);
        $this->assign('list', $order_list);
        $this->display();
    }

    /**
     * 分成
     **/
    public function actionSeparateDrpOrder()
    {
        $this->admin_priv('drp_order_list');
        if(IS_POST){
            $arr = array('url'=>url('drporderlist'));
        }
        $drp_affiliate = get_drp_affiliate_config();
        $oid = I('oid', NULL, 'intval');
        if(is_array($oid)){
            $oid_arr = $oid;
        }else{
            $oid_arr[] = $oid;
        }
        if(is_array($oid_arr)){
            foreach ($oid_arr as $oid){
                $sql = "SELECT o.order_sn, o.drp_is_separate, o.user_id, SUM(og.drp_money) as drp_money
                    FROM " . $GLOBALS['ecs']->table('order_info') . " o" .
                    " RIGHT JOIN" . $GLOBALS['ecs']->table('order_goods') . " og ON o.order_id = og.order_id" .
                    " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " g ON og.goods_id = g.goods_id" .
                    " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" .
                    " WHERE o.order_id = '$oid' ";
                $row = $GLOBALS['db']->getRow($sql);
                //积分总比例，调取默认，如果商品设置了比例，调取该比例
                if (empty($row['drp_is_separate'])) {
                    if ($row['dis_commission']) {
                        $drp_affiliate['config']['level_point_all'] = (float)$drp_affiliate['config']['level_point_all'];
                        $drp_affiliate['config']['level_money_all'] = (float)$row['dis_commission'];
                    } else {
                        $drp_affiliate['config']['level_point_all'] = (float)$drp_affiliate['config']['level_point_all'];
                        $drp_affiliate['config']['level_money_all'] = (float)$drp_affiliate['config']['level_money_all'];
                    }

                    if ($drp_affiliate['config']['level_point_all']) {
                        $drp_affiliate['config']['level_point_all'] /= 100;
                    }
                    if ($drp_affiliate['config']['level_money_all']) {
                        $drp_affiliate['config']['level_money_all'] /= 100;
                    }
                    $money = $row['drp_money'];                                                                          //分销钱是付款时就算好的
                    $order_sn = $row['order_sn'];
                    include_once(BASE_PATH . 'helpers/order_helper.php');
                    $integral = integral_to_give(array('order_id' => $oid, 'extension_code' => ''));
                    $point = round($drp_affiliate['config']['level_point_all'] * intval($integral['rank_points']), 0);  //分销积分是分成时才算的

                    $num = count($drp_affiliate['item']);
                    for ($i = 0; $i < $num; $i++) {
                        $drp_affiliate['item'][$i]['level_point'] = (float)$drp_affiliate['item'][$i]['level_point'];
                        $drp_affiliate['item'][$i]['level_money'] = (float)$drp_affiliate['item'][$i]['level_money'];
                        if ($drp_affiliate['item'][$i]['level_point']) {
                            $perp = ($drp_affiliate['item'][$i]['level_point'] / 100);
                        }
                        if ($drp_affiliate['item'][$i]['level_money']) {
                            $per = ($drp_affiliate['item'][$i]['level_money'] / 100);
                        }
                        $setmoney = round($money * $per, 2);
                        $setpoint = round($point * $perp, 0);
                        $row = $GLOBALS['db']->getRow("SELECT o.parent_id as user_id,u.user_name FROM " . $GLOBALS['ecs']->table('users') . " o" .
                            " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id" .
                            " WHERE o.user_id = '$row[user_id]'"
                        );
                        $up_uid = $row['user_id'];
                        if (empty($up_uid) || empty($row['user_name'])) {
                            break;
                        } else {
                            $info = sprintf(L('drp_separate_info'),$row['user_name'],$order_sn, $setmoney, $setpoint);
                            drp_log_account_change($up_uid, $setmoney, 0,0,$setpoint,$info,ACT_SEPARATE);  //分成金钱和积分先写到drp_shop表里面，提现后钱再写到用户表里，积分字段保留
                            $this->write_drp_affiliate_log($oid, $up_uid, $row['user_name'], $setmoney, $setpoint, 0);
                            //获得佣金，发送模版消息 start
                            $pushData = array(
                                'keyword1' => array('value' => $setmoney),
                                'keyword2' => array('value' => date('Y-m-d H:i:s', gmtime()))
                            );
                            $url = __HOST__ . url('drp/user/orderdetail',array('order_id'=>$oid));
                            push_template('OPENTM201812627', $pushData, $url,$up_uid);
                            //获得佣金，发送模版消息 end
                        }
                    }
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
                        " SET drp_is_separate = 1" .
                        " WHERE order_id = '$oid'";
                    $GLOBALS['db']->query($sql);
                }
            }
        }

        if(IS_POST){
            die(json_encode($arr));
        }else{
            $this->redirect('drporderlist');
        }
    }

    /**
     * 插入后台分成记录
     **/
    private function write_drp_affiliate_log($oid, $uid, $username, $money, $point, $separate_by)
    {
        $time = gmtime();
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('drp_affiliate_log') . "( order_id, `time`, user_id, user_name, money, point, separate_type)" .
            " VALUES ( '$oid','$time', '$uid', '$username', '$money', '$point',$separate_by)";
        if ($oid) {
            $GLOBALS['db']->query($sql);
        }
    }

    /**
     * 取消分成，不再能对该订单进行分成
     **/
    public function actionDelDrpOrder()
    {
        $this->admin_priv('drp_order_list');
        $oid = I('oid', NULL, 'intval');
        if ($oid) {
            $stat = $GLOBALS['db']->getOne("SELECT drp_is_separate FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$oid'");
            if (empty($stat)) {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
                    " SET drp_is_separate = 2" .
                    " WHERE order_id = '$oid'";
                $GLOBALS['db']->query($sql);
            }
        }
        $this->redirect('drporderlist');
    }

    /**
     * 撤销某次分成，将已分成的收回来
     **/
    public function actionRollbackDrpOrder()
    {
        $this->admin_priv('drp_order_list');
        $logid = I('log_id', NULL, 'intval');
        if ($logid) {
            $stat = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('drp_affiliate_log') . " WHERE log_id = '$logid'");
            if (!empty($stat)) {
                $flag = -1;
                drp_log_account_change($stat['user_id'], -$stat['money'], 0,0,-$stat['point'],L('loginfo_cancel'),ACT_SEPARATE);
                $sql = "UPDATE " . $GLOBALS['ecs']->table('drp_affiliate_log') .
                    " SET separate_type = '$flag'" .
                    " WHERE log_id = '$logid'";
                $GLOBALS['db']->query($sql);
            }
        }
        $this->redirect('drporderlist');
    }
}

