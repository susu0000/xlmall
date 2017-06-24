<?php
namespace app\http\wechat\controllers;

use app\http\base\controllers\Backend;
use ectouch\Wechat;
use ectouch\Form;
use ectouch\Http;
use ectouch\QRcode;
use Think\Image;

class Marketing extends Backend
{

    protected $weObj = '';

    protected $wechat_id = 0;

    protected $page_num = 0;

    public function __construct()
    {
        parent::__construct();
        L(require(MODULE_PATH . 'language/' . C('shop.lang') . '/wechat.php'));
        $this->assign('lang', array_change_key_case(L()));

        //平台微信公众号ID
        $this->wechat_id = dao('wechat')->where(array('default_wx' => 1))->getField('id');

        // 初始化 每页分页数量
        $this->page_num = 10;
        $this->assign('page_num', $this->page_num);
    }

    /**
     * 营销活动首页
     * @return
     */
    public function actionIndex()
    {
        $list = array(
            '0' => array('name' => L('wechat_wall'), 'keywords' => 'wall', 'url' => url('wall_index')),
            // '1' => array('name' => L('wechat_zjd'), 'keywords' => 'zjd',  'url' => url('zjd_index')),
            // '2' => array('name' => L('wechat_dzp'), 'keywords' => 'dzp', 'url' => url('dzp_index')),
            // '3' => array('name' => L('wechat_ggk'), 'keywords' => 'ggk', 'url' => url('ggk_index')),
            );
        $this->assign('list', $list);
        $this->display();
    }


    /**
     * 微信墙
     */
    public function actionWallIndex()
    {
        $list = dao('wechat_marketing')->field('id, name, marketing_type, command, starttime, endtime, support, status')->where(array('marketing_type' => 'wall','wechat_id' => $this->wechat_id))->order('id DESC')->select();
        // $sql = "SELECT w.*, count(DISTINCT u.id) as user_count, count(m.id) as msg_count FROM {pre}wechat_wall w LEFT JOIN {pre}wechat_wall_user u ON w.id = u.wall_id LEFT JOIN {pre}wechat_wall_msg m ON u.id = m.user_id WHERE w.wechat_id = '" .$this->wechat_id. "' ORDER BY w.id DESC";
        // $list = $this->model->query($sql);
        if($list[0]['id']){
            foreach($list as $k => $v)
            {
                $list[$k]['starttime'] = date('Y-m-d H:i:s', $v['starttime']);
                $list[$k]['endtime'] = date('Y-m-d H:i:s', $v['endtime']);
                $res = $this->get_user_msg_count($v['id']);
                $list[$k]['user_count'] = $res['user_count'];  // 参与人数
                $list[$k]['msg_count'] = $res['msg_count'];  // 上墙信息
                if($v['status'] == 0){
                    $list[$k]['status'] = L('no_start');
                }
                elseif($v['status'] == 1){
                    $list[$k]['status'] = L('start');
                }
                elseif($v['status'] == 2){
                    $list[$k]['status'] = L('over');
                }
            }
        }
        else{
            $list = array();
        }

        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 微信墙活动添加与编辑
     */
    public function actionWallEdit()
    {
        if(IS_POST){
            $id = I('post.id');
            $data = I('post.data');
            $config = I('post.config');
            if(empty($data['name'])){
                $this->message(L('market_name') . L('empty'), NULL, 2);
            }
            $data['wechat_id'] = $this->wechat_id;
            $data['marketing_type'] = I('post.marketing_type');
            $data['command'] = I('post.command');
            $data['starttime'] = strtotime($data['starttime']);
            $data['endtime'] = strtotime($data['endtime']);

            $nowtime = gmtime();
            if($data['starttime'] > $nowtime){
                $data['status'] = 0; //未开始
            }
            elseif($data['starttime'] < $nowtime && $data['endtime'] > $nowtime){
                $data['status'] = 1; //进行中
            }
            elseif($data['endtime'] < $nowtime){
                $data['status'] = 2; //已结束
            }
            $logo_path = I('post.logo_path');
            $background_path = I('post.background_path');
            // 编辑图片处理
            $logo_path = edit_upload_image($logo_path);
            $background_path = edit_upload_image($background_path);

            //上传logo, 上传背景图片
            if($_FILES['logo']['name'] || $_FILES['background']['name']){
                // 判断类型
                $type = array('image/jpeg','image/png');
                if(($_FILES['logo']['type'] && !in_array($_FILES['logo']['type'], $type)) || ($_FILES['background']['type'] && !in_array($_FILES['background']['type'], $type)))
                {
                    $this->message(L('not_file_type'), NULL, 2);
                }
                $result = $this->upload('data/attached/wall', false, 5);
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 2);
                }
                //处理logo
                if($_FILES['logo']['name'] && $result['url']['logo']['url']){
                    $data['logo'] = $result['url']['logo']['url'];
                }
                //处理背景图片
                if($_FILES['background']['name'] && $result['url']['background']['url']){
                    $data['background'] = $result['url']['background']['url'];
                }
            }

            //配置
            if($config){
                // 奖品处理
                if (is_array($config['prize_level']) && is_array($config['prize_count']) && is_array($config['prize_name'])) {
                    foreach ($config['prize_level'] as $key => $val) {
                        $prize_arr[] = array(
                            'prize_level' => $val,
                            'prize_name' => $config['prize_name'][$key],
                            'prize_count' => $config['prize_count'][$key]
                        );
                    }
                }
                $data['config'] = serialize($prize_arr);
            }
            //更新活动
            if($id){
                // 删除原logo图片
                if ($data['logo'] && $logo_path != $data['logo']) {
                    $this->remove($logo_path);
                }
                // 删除原背景图片
                if ($data['background'] && $background_path != $data['background']) {
                    $this->remove($background_path);
                }
                $where = array('id' => $id, 'wechat_id' => $this->wechat_id);
                dao('wechat_marketing')->data($data)->where($where)->save();
                $this->message(L('market_edit') . L('success'), url('wall_index'));
            }else{
                //添加活动
                $data['addtime'] = gmtime();
                dao('wechat_marketing')->data($data)->add();
                $this->message(L('market_add') . L('success'), url('wall_index'));
            }
        }

        $id = I('get.id');
        if($id){
            $info = dao('wechat_marketing')->field('id, name, keywords, command, logo, background, starttime, endtime, config, description, support')->where(array('id' => $id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id))->find();
            if($info){
                $info['starttime'] = date('Y-m-d H:i:s', $info['starttime']);
                $info['endtime'] = date('Y-m-d H:i:s', $info['endtime']);
                $info['prize_arr'] = unserialize($info['config']);
                $info['logo'] = get_wechat_image_path($info['logo']);
                $info['background'] = get_wechat_image_path($info['background']);
                $info['keywords'] = !empty($info['keywords']) ? $info['keywords'] : 'wall,微信墙';
            }
            $this->assign('info', $info);
        }

        $this->display();
    }

    /**
     * 删除微信墙活动
     */
    public function actionWallDel()
    {
        $id = I('get.id');
        if(!$id){
            $this->message(L('empty'), NULL, 2);
        }

        dao('wechat_marketing')->where(array('id'=>$id, 'wechat_id' => $this->wechat_id))->delete();
        $this->message(L('market_delete') . L('success'), url('wall_index'));
    }

    /**
     * 微信墙参与人员数据
     */
    public function actionWallUser()
    {
        $id = I('get.id');
        if(!$id){
            $this->message(L('empty'), NULL, 2);
        }

        //分页
        $filter['id'] = $id;
        $offset = $this->pageLimit(url('wall_user', $filter), $this->page_num);
        $total = dao('wechat_wall_user')->where(array('wall_id'=>$id, 'wechat_id' => $this->wechat_id))->count();
        $this->assign('page', $this->pageShow($total));

        //$list = $this->model->table('wechat_wall_user')->field('id, nickname, sex, headimg, status, addtime')->where(array('wall_id'=>$id))->order('addtime desc, id desc')->limit($offset)->select();
        $sql = "SELECT id, nickname, sex, headimg, status, addtime FROM {pre}wechat_wall_user WHERE wechat_id = '" .$this->wechat_id. "'  ORDER BY addtime DESC limit $offset";
        $list = $this->model->query($sql);
        if($list[0]['id']){
            foreach($list as $k=>$v){
                if($v['sex'] == 0){
                    $list[$k]['sex'] = '女';
                }
                elseif($v['sex'] == 1){
                    $list[$k]['sex'] = '男';
                }
                else{
                    $list[$k]['sex'] = '保密';
                }
                if($v['status'] == 1){
                    $list[$k]['status'] = L('is_checked');
                    $list[$k]['handler'] = '';
                }
                else{
                    $list[$k]['status'] = L('no_check');
                    $list[$k]['handler'] = '<a class="button btn-primary" href="'.url('wall_check', array('wall_id'=>$id, 'user_id'=>$v['id'])).'">'.L('check').'</a>';
                }
                $list[$k]['nocheck'] = dao('wechat_wall_msg')->where(array('status'=>0, 'user_id'=>$v['id']))->count();
                $list[$k]['addtime'] = $v['addtime'] ? local_date('Y-m-d H:i:s', $v['addtime']) : '';
            }
        }
        else{
            $list = array();
        }

        $this->assign('wall_id', $id);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 微信墙中奖名单
     */
    public function actionWallPrize()
    {
        $id = I('get.id');
        if(!$id){
            $this->message(L('empty'), NULL, 2);
        }

        //分页
        $filter['id'] = $id;
        $offset = $this->pageLimit(url('wall_prize', $filter), $this->page_num);
        $sql = "SELECT count(*) as count FROM {pre}wechat_prize p LEFT JOIN {pre}wechat_user u ON p.openid = u.openid WHERE p.activity_type = 'wall' AND p.wechat_id = '".$this->wechat_id."' ORDER BY dateline desc";
        $count = $this->model->query($sql);
        $total = $count[0]['count'];
        $this->assign('page', $this->pageShow($total));

        $sql = "SELECT p.id, p.prize_name, p.issue_status, p.winner, p.dateline, p.openid, u.nickname FROM {pre}wechat_prize p LEFT JOIN {pre}wechat_user u ON p.openid = u.openid WHERE p.activity_type = 'wall' AND p.wechat_id = '".$this->wechat_id."' ORDER BY dateline desc limit " . $offset;
        $list = $this->model->query($sql);

        if($list){
            foreach($list as $k=>$v){
                $list[$k]['dateline'] = $v['dateline'] ? local_date('Y-m-d H:i:s', $v['dateline']) : '';
                $list[$k]['winner'] = unserialize($v['winner']);
                if($v['issue_status'] == 1){
                    $list[$k]['issue_status'] = L('is_sended');
                    $list[$k]['handler'] = '<a href="'.url('winner_issue', array('id'=>$v['id'], 'cancel'=>1)).'" class="button btn-primary">'.L('cancle_send').'</a>';
                }
                else{
                    $list[$k]['issue_status'] = L('no_send');
                    $list[$k]['handler'] = '<a href="'.url('winner_issue', array('id'=>$v['id'])).'" class="button btn-primary">'.L('send').'</a>';
                }
            }
        }

        $this->assign('wall_id', $id);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 审核以及未审核消息记录
     */
    public function actionWallMsgcheck()
    {
        $wall_id = I('get.id');
        if(empty($wall_id)){
            $this->message(L('empty'), NULL, 2);
        }
        $status = I('get.status');
        $where = "";
        if(empty($status)){
            $where = " AND m.status = 0";
        }

        $sql = "SELECT COUNT(*) as num FROM {pre}wechat_wall_msg m LEFT JOIN {pre}wechat_wall_user u ON m.user_id = u.id LEFT JOIN {pre}wechat_marketing mk ON u.wall_id = mk.id WHERE mk.id = ".$wall_id . $where;
        $num  = $this->model->query($sql);
        //分页
        $filter['id'] = $wall_id;
        $filter['status'] = $status;
        $offset = $this->pageLimit(url('wall_msg_check', $filter), $this->page_num);
        $total = $num[0]['num'];
        $this->assign('page', $this->pageShow($total));

        $sql = "SELECT m.id, m.user_id, m.content, m.addtime, m.checktime, m.status, u.nickname FROM {pre}wechat_wall_msg m LEFT JOIN {pre}wechat_wall_user u ON m.user_id = u.id LEFT JOIN {pre}wechat_marketing mk ON u.wall_id = mk.id WHERE mk.id = " .$wall_id . $where . " ORDER BY m.addtime ASC LIMIT $offset";
        $list =  $this->model->query($sql);
        if($list){
            foreach($list as $k=>$v){
                if($v['status'] == 1){
                    $list[$k]['status'] = L('is_checked');
                    $list[$k]['handler'] = '';
                }
                else{
                    $list[$k]['status'] = L('no_check');
                    $list[$k]['handler'] = '<a class="button btn-primary" href="'.url('wall_check', array('wall_id'=>$wall_id, 'msg_id'=>$v['id'], 'user_id'=>$v['user_id'], 'status'=>$status)).'">' . L('check') . '</a>';
                }
                $list[$k]['addtime'] = $v['addtime'] ? local_date('Y-m-d H:i:s', $v['addtime']) : '';
                $list[$k]['checktime'] = $v['checktime'] ? local_date('Y-m-d H:i:s', $v['checktime']) : '';
            }
        }

        $this->assign('status', $status);
        $this->assign('wall_id', $wall_id);
        $this->assign('list', $list);
        $this->display('');
    }

    /**
     * 用户留言记录
     */
    public function actionWallMsg()
    {
        $wall_id = I('get.wall_id');
        $user_id = I('get.user_id');
        if(empty($wall_id) || empty($user_id)){
            $this->message(L('empty'), NULL, 2);
        }
        //分页
        $filter['wall_id'] = $wall_id;
        $filter['user_id'] = $user_id;
        $offset = $this->pageLimit(url('wall_msg', $filter), $this->page_num);
        $total = dao('wechat_wall_msg')->where(array('user_id'=>$user_id))->count();
        $this->assign('page', $this->pageShow($total));

        $list = dao('wechat_wall_msg')->field('id, content, addtime, checktime, status')->where(array('user_id'=>$user_id))->order('addtime asc, checktime asc')->limit($offset)->select();
        if($list){
            foreach($list as $k=>$v){
                if($v['status'] == 1){
                    $list[$k]['status'] = L('is_checked');
                    $list[$k]['handler'] = '';
                }
                else{
                    $list[$k]['status'] = L('no_check');
                    $list[$k]['handler'] = '<a class="button btn-primary" href="'.url('wall_check', array('wall_id'=>$wall_id, 'msg_id'=>$v['id'], 'user_id'=>$user_id)).'">' .L('check'). '</a>';
                }
                $list[$k]['addtime'] = $v['addtime'] ? local_date('Y-m-d H:i:s', $v['addtime']) : '';
                $list[$k]['checktime'] = $v['checktime'] ? local_date('Y-m-d H:i:s', $v['checktime']) : '';
            }
        }

        $this->assign('wall_id', $wall_id);
        $this->assign('user_id', $user_id);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 审核处理
     */
    public function actionWallCheck()
    {
        $wall_id = I('get.wall_id');
        $user_id = I('get.user_id');
        $msg_id = I('get.msg_id');

        if(empty($user_id) || empty($wall_id)){
            $this->message(L('empty'), NULL, 2);
        }
        $checktime = gmtime();
        //用户审核
        if(!empty($wall_id) && !empty($user_id) && empty($msg_id)){
            dao('wechat_wall_user')->data(array('status' => 1, 'checktime' => $checktime))->where(array('wall_id'=>$wall_id, 'id'=>$user_id, 'status'=>0))->save();

            $this->redirect(url('wall_user', array('id'=>$wall_id)));
        }

        //留言审核
        if(!empty($user_id) && !empty($msg_id)){
            dao('wechat_wall_msg')->data(array('status'=>1, 'checktime' => $checktime))->where(array('user_id'=>$user_id, 'id'=>$msg_id, 'status'=>0))->save();
            //审核用户
            dao('wechat_wall_user')->data(array('status'=>1, 'checktime' => $checktime))->where(array('id'=>$user_id, 'status'=>0))->save();
            if(isset($_GET['status'])){
                $status = I('get.status');
                $this->redirect(url('wall_msg_check', array('id'=>$wall_id, 'status'=>$status)));
            }

            $this->redirect(url('wall_msg', array('wall_id'=>$wall_id, 'user_id'=>$user_id)));
        }

        $this->redirect(url('wall_index'));
    }

    /**
     * 数据删除
     */
    public function actionWallDataDel()
    {
        $wall_id = I('get.wall_id');
        $user_id = I('get.user_id');
        $msg_id = I('get.msg_id');

        if(empty($user_id) || empty($wall_id)){
            $this->message(L('empty'), NULL, 2);
        }

        //用户删除
        if(!empty($wall_id) && !empty($user_id) && empty($msg_id)){
            dao('wechat_wall_user')->where(array('wall_id'=>$wall_id, 'id'=>$user_id))->delete();
            dao('wechat_wall_msg')->where(array('user_id'=>$user_id))->delete();
            $this->redirect(url('wall_user', array('id'=>$wall_id)));
        }

        //留言删除
        if(!empty($user_id) && !empty($msg_id)){
            dao('wechat_wall_msg')->where(array('user_id'=>$user_id, 'id'=>$msg_id))->delete();

            if(isset($_GET['status'])){
                $status = I('get.status');
                $this->redirect(url('wall_msg_check', array('id'=>$wall_id, 'status'=>$status)));
            }

            $this->redirect(url('wall_msg', array('wall_id'=>$wall_id, 'user_id'=>$user_id)));
        }
        $this->redirect(url('wall_index'));
    }

    /**
     * 上墙地址（微信二维码生成链接）
     */
    public function actionTowall()
    {
        $wall_id = I('get.id');
        if(empty($wall_id)){
            exit(json_encode(array('status' => 0,'msg' => L('empty'))));
        }
        $url = __HOST__ . url('wechat/wall/wall_user_wechat', array('wall_id' => $wall_id));
        //$this->assign('content', $url);
        $wall = dao('wechat_marketing')->field('qrcode')->where(array('id'=> $wall_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id))->find();

       /* if($wall_qrcode){
            $qrcode_url = $wall_qrcode;
        }
        else{
            $url = __HOST__.url('default/wall/wall_user_wechat', array('wall_id'=>$wall_id));
            $wxconfig = $this->model->table('wechat')->field('token, appid, appsecret')->where(array('id'=>$this->wechat_id, 'status'=>1))->find();
            if($wxconfig){
                $wxObj  = new Wechat($wxconfig);
                $shorturl = $wxObj->getShortUrl($url);
                if(empty($shorturl)){
                    exit(json_encode(array(
                        'status' => 0,
                        'msg' => $wxObj->errMsg
                    )));
                }
                $ticket = $wxObj->getQRCode($shorturl, 2);
                if(empty($ticket)){
                    exit(json_encode(array(
                        'status' => 0,
                        'msg' => $wxObj->errMsg
                    )));
                }
                $qrcode = $wxObj->getQRUrl($ticket['ticket']);
                if(empty($qrcode)){
                    exit(json_encode(array(
                        'status' => 0,
                        'msg' => $wxObj->errMsg
                    )));
                }
                $this->model->table('wechat_marketing')->data(array('qrcode'=>$qrcode))->where(array('id'=>$wall_id))->save();

                $qrcode_url = $wall_qrcode;
             }
            else{
                exit(json_encode(array(
                    'status' => 0,
                    'msg' => '请先配置公众号'
                )));
            }
        }*/
        // 生成二维码
        // 纠错级别：L、M、Q、H
        $errorCorrectionLevel = 'M';
        // 点的大小：1到10
        $matrixPointSize = 7;
        // 生成的文件位置
        $path = dirname(ROOT_PATH) .'/data/attached/wall/';
        // 水印logo
        $water_logo = ROOT_PATH . 'resources/assets/img/shop_app_icon.png';
        $water_logo_out = $path . 'water_logo' .$wall_id. '.png';

        // 输出二维码路径
        $filename = $path . $errorCorrectionLevel . $matrixPointSize . $wall_id. '.png';

        if(!is_dir($path)){
            @mkdir($path);
        }
        QRcode::png($url, $filename, $errorCorrectionLevel, $matrixPointSize, 2);

        // 添加水印
        $img = new Image();
        // 生成水印缩略图
        $img->open($water_logo)->thumb(80, 80)->save($water_logo_out);
        // 生成原图+水印
        $img->open($filename)->water($water_logo_out, 5, 100)->save($filename);

        $qrcode_url = __HOST__. __STATIC__ . '/data/attached/wall/' .basename($filename).'?t='.time();
        $this->assign('qrcode_url', $qrcode_url);
        $this->display();
    }


    /**
     * 上墙信息，参与人数数量
     * @param  [type] $wall_id
     * @return [type]
     */
    private function get_user_msg_count($wall_id)
    {
        $sql = "SELECT count(DISTINCT u.id) as user_count, count(m.id) as msg_count FROM {pre}wechat_wall_user u LEFT JOIN {pre}wechat_wall_msg m ON u.id = m.user_id WHERE u.wall_id = '" .$wall_id. "' AND u.wechat_id = '" .$this->wechat_id. "' ";
        $res = $this->model->query($sql);
        return $res[0];
    }

    /**
     * 获取配置信息
     */
    private function get_config()
    {
        // 公众号配置信息
        $where['id'] = $this->wechat_id;
        $wechat = dao('wechat')->field('token, appid, appsecret, type, status')->where($where)->find();
        if (empty($wechat)) {
            $wechat = array();
        }
        if (empty($wechat['status'])) {
            $this->message(L('open_wechat'), url('index'), 2);
            exit;
        }
        $config = array();
        $config['token'] = $wechat['token'];
        $config['appid'] = $wechat['appid'];
        $config['appsecret'] = $wechat['appsecret'];
        $this->weObj = new Wechat($config);
    }
}