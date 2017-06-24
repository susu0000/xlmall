<?php
namespace app\http\wechat\controllers;

use app\http\base\controllers\Frontend;
use ectouch\Wechat;

class Wall extends Frontend
{
    private $weObj = '';
    private $secret_key = '';
    private $wechat_id = 0;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();

        //平台微信公众号ID
        $this->wechat_id = dao('wechat')->where(array('default_wx' => 1))->getField('id');
    }

    /**
     * 微信交流墙
     */
    public function actionWallMsg(){
        $wall_id = I('get.wall_id');
        if(empty($wall_id)){
            $this->redirect(url('index/index'));
        }

        //活动内容
        $wall = dao('wechat_marketing')->field('id, name, logo, background, starttime, endtime, config, description, support')->where(array('id' => $wall_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id))->find();

        $starttime = $wall['starttime'];
        $endtime = $wall['endtime'];
        $nowtime = gmtime();

        if($starttime > $nowtime){
            $wall['status'] = 0; //未开始
        }
        elseif($starttime < $nowtime && $endtime > $nowtime){
            $wall['status'] = 1; //进行中
        }
        elseif($endtime < $nowtime){
            $wall['status'] = 2; //已结束
        }

        $wall['logo'] = get_wechat_image_path($wall['logo']);
        $wall['background'] = get_wechat_image_path($wall['background']);

        if($wall['status'] == 1){
            $cache_key = md5('cache_0');
            $list = cache($cache_key);
            if($list === false){
                //留言
                $sql = "SELECT u.nickname, u.headimg, m.content, m.addtime FROM {pre}wechat_wall_msg m LEFT JOIN {pre}wechat_wall_user u ON m.user_id = u.id WHERE m.status = 1 and u.wall_id = '$wall_id' ORDER BY m.addtime DESC LIMIT 0, 10";
                $data = $this->model->query($sql);
                if($data){
                    usort($data, function($a, $b){
                        if($a['addtime'] == $b['addtime']){
                            return 0;
                        }
                        return $a['addtime'] > $b['addtime'] ? 1 : -1;
                    });
                    foreach($data as $k=>$v){
                        $data[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                    }
                }
                cache($cache_key, $data, 10);
                $list = cache($cache_key);
            }

            $sql = "SELECT count(*) as num FROM {pre}wechat_wall_msg m LEFT JOIN {pre}wechat_wall_user u ON m.user_id = u.id WHERE m.status = 1 AND u.wall_id = '$wall_id' ORDER BY m.addtime DESC";
            $num = $this->model->query($sql);
            $this->assign('msg_count', $num[0]['num']);
        }

        $this->assign('wall', $wall);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 微信头像墙
     */
    public function actionWallUser(){
        $wall_id = I('get.wall_id');
        if(empty($wall_id)){
            $this->redirect(url('index/index'));
        }
        //活动内容
        $wall = dao('wechat_marketing')->field('id, name, logo, background, starttime, endtime, config, description, support,status')->where(array('id' => $wall_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id))->find();

        $starttime = $wall['starttime'];
        $endtime = $wall['endtime'];
        $nowtime = gmtime();
        if($starttime > $nowtime){
            $wall['status'] = 0; //未开始
        }
        elseif($starttime < $nowtime && $endtime > $nowtime){
            $wall['status'] = 1; //进行中
        }
        elseif($endtime < $nowtime){
            $wall['status'] = 2; //已结束
        }

        $wall['logo'] = get_wechat_image_path($wall['logo']);
        $wall['background'] = get_wechat_image_path($wall['background']);

        //用户
        $list = dao('wechat_wall_user')->field('nickname, headimg')->where(array('wall_id' => $wall_id, 'status' => 1, 'wechat_id' => $this->wechat_id))->order('addtime desc')->select();
        /*$sql = "SELECT u.nickname, u.headimg FROM {pre}wechat_wall_msg m LEFT JOIN {pre}wechat_wall_user u ON m.user_id = u.id WHERE u.wall_id = '$wall_id' AND m.status = 1 GROUP BY m.user_id ORDER BY u.addtime DESC";
        $list = $this->model->query($sql);*/

        $this->assign('wall', $wall);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 抽奖页面
     */
    public function actionWallPrize(){
        $wall_id = I('get.wall_id');
        if(empty($wall_id)){
            $this->redirect(url('index/index'));
        }
        //活动内容
        $wall = dao('wechat_marketing')->field('id, name, logo, background, starttime, endtime, config, description, support')->where(array('id'=>$wall_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id))->find();
        if($wall){
            $wall['config'] = unserialize($wall['config']);
            $wall['logo'] = get_wechat_image_path($wall['logo']);
            $wall['background'] = get_wechat_image_path($wall['background']);
        }

        //中奖的用户
        //$sql = "SELECT u.nickname, u.headimg, u.id FROM {pre}wechat_wall_user u LEFT JOIN {pre}wechat_prize p ON u.openid = p.openid WHERE u.wall_id = '$wall_id' AND u.status = 1 AND u.openid in (SELECT openid FROM {pre}wechat_prize WHERE wall_id = '$wall_id' AND activity_type = 'wall') GROUP BY u.id ORDER BY p.dateline ASC";
        $sql = "SELECT u.nickname, u.headimg, u.id, u.wechatname, u.headimgurl FROM {pre}wechat_prize p LEFT JOIN {pre}wechat_wall_user u ON u.openid = p.openid WHERE u.wall_id = '$wall_id' AND u.status = 1 AND u.openid in (SELECT openid FROM {pre}wechat_prize WHERE wall_id = '$wall_id' AND wechat_id = '$this->wechat_id' AND activity_type = 'wall' AND prize_type = 1) GROUP BY u.id ORDER BY p.dateline ASC";
        $rs = $this->model->query($sql);
        $list = array();
        if($rs){
            foreach($rs as $k=>$v){
                $list[$k+1] = $v;
            }
        }
        //参与人数
        $total = dao('wechat_wall_user')->where(array('status'=>1, 'wechat_id' => $this->wechat_id))->count();

        $this->assign('total', $total);
        $this->assign('prize_num', count($list));
        $this->assign('list', $list);
        $this->assign('wall', $wall);
        $this->display();
    }

    /**
     * 获取未中奖用户
     */
    public function actionNoPrize(){
        if(IS_AJAX){
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $wall_id = I('get.wall_id');
            if(empty($wall_id)){
                $result['errCode'] = 1;
                $result['errMsg'] = url('index/index');
                exit(json_encode($result));
            }
            //没中奖的用户
            $sql = "SELECT nickname, headimg, id, wechatname, headimgurl FROM {pre}wechat_wall_user WHERE wall_id = '$wall_id' AND status = 1 AND openid not in (SELECT openid FROM {pre}wechat_prize WHERE wall_id = '$wall_id' AND wechat_id = '$this->wechat_id' AND activity_type = 'wall') ORDER BY addtime DESC";
            //$sql = "SELECT u.nickname, u.headimg, u.id FROM {pre}wechat_wall_user u LEFT JOIN {pre}wechat_prize p ON u.openid = p.openid WHERE u.wall_id = '$wall_id' AND u.status = 1 AND u.openid not in (SELECT openid FROM {pre}wechat_prize WHERE wall_id = '$wall_id' AND activity_type = 'wall') ORDER BY u.addtime ASC";
            $no_prize = $this->model->query($sql);
            if(empty($no_prize)){
                $result['errCode'] = 2;
                $result['errMsg'] = '暂无参与抽奖用户';
                exit(json_encode($result));
            }

            $result['data'] = $no_prize;
            exit(json_encode($result));
        }
    }

    /**
     * 抽奖的动作
     */
    public function actionStartDraw(){
        if(IS_AJAX){
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $wall_id = I('get.wall_id');
            if(empty($wall_id)){
                $result['errCode'] = 1;
                $result['errMsg'] = url('index/index');
                exit(json_encode($result));
            }
            $wall = dao('wechat_marketing')->field('id, name, logo, background, starttime, endtime, config, description, support')->where(array('id'=>$wall_id, 'marketing_type' => 'wall', 'status'=>1, 'wechat_id' => $this->wechat_id))->find();
            if(empty($wall)){
                $result['errCode'] = 1;
                $result['errMsg'] = url('index/index');
                exit(json_encode($result));
            }
            $nowtime = gmtime();
            if($wall['starttime'] > $nowtime || $wall['endtime'] < $nowtime){
                $result['errCode'] = 2;
                $result['errMsg'] = '活动尚未开始或者已结束';
                exit(json_encode($result));
            }

            $sql = "SELECT u.nickname, u.headimg, u.openid, u.id, u.wechatname, u.headimgurl FROM {pre}wechat_wall_user u LEFT JOIN {pre}wechat_prize p ON u.openid = p.openid WHERE u.wall_id = '$wall_id' AND u.status = 1 AND u.openid not in (SELECT openid FROM {pre}wechat_prize WHERE wall_id = '$wall_id' AND wechat_id = '$this->wechat_id' AND activity_type = 'wall') ORDER BY u.addtime DESC";
            $list = $this->model->query($sql);
            //$list = array('1');
            if($list){
                //随机一个中奖人
                $key = mt_rand(0, count($list) - 1);
                $rs = isset($list[$key]) ? $list[$key] : $list[0];
                //存储中奖用户
                $data['wechat_id'] = $this->wechat_id;
                $data['openid'] = $rs['openid'];
                $data['issue_status'] = 0;
                $data['dateline'] = $nowtime;
                $data['prize_type'] = 1;
                $data['activity_type'] = 'wall';
                $data['wall_id'] = $wall_id;
                dao('wechat_prize')->data($data)->add();

                //中奖人数
                $rs['prize_num'] = dao('wechat_prize')->where(array('wall_id'=>$wall_id, 'wechat_id' => $this->wechat_id, 'activity_type'=>'wall'))->count();

                /*$rs = array();
                $rs['nickname'] = 'null';
                $rs['headimg'] = __TPL__.'/wall/img/wall_4.png';*/
                $result['data'] = $rs;
                exit(json_encode($result));
            }
        }
        $result['errCode'] = 2;
        $result['errMsg'] = '暂无数据';
        exit(json_encode($result));
    }

    /**
     * 重置抽奖
     */
    public function actionResetDraw(){
        if(IS_AJAX){
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $wall_id = I('get.wall_id');
            if(empty($wall_id)){
                $result['errCode'] = 1;
                $result['errMsg'] = url('index/index');
                exit(json_encode($result));
            }
            //删除中奖的用户
            //$this->model->table('wechat_prize')->where(array('wall_id'=>$wall_id, 'activity_type'=>'wall'))->delete();
            //不显示在中奖池
            dao('wechat_prize')->data(array('prize_type'=>0))->where(array('wall_id'=>$wall_id, 'activity_type'=>'wall', 'wechat_id' => $this->wechat_id))->save();
            exit(json_encode($result));
        }
        $result['errCode'] = 2;
        $result['errMsg'] = '无效的请求';
        exit(json_encode($result));
    }

    /**
     * 微信端抽奖用户申请
     */
    public function actionWallUserWechat(){
        if(!empty($_SESSION)){
            if(IS_POST){
                $wall_id = I('post.wall_id');
                if(empty($wall_id)){
                    show_message("请选择对应的活动");
                }
                $data['nickname'] = I('post.nickname');
                $data['headimg'] = I('post.headimg');
                $data['sex'] = I('post.sex');
                $data['wall_id'] = $wall_id;
                $data['wechat_id'] = $this->wechat_id;
                $data['addtime'] = gmtime();
                $data['openid'] = !empty($_SESSION['openid']) ? $_SESSION['openid'] : '';
                $data['wechatname'] = !empty($_SESSION['nickname']) ? $_SESSION['nickname'] : '';
                $data['headimgurl'] = !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : '';

                dao('wechat_wall_user')->data($data)->add();
                $this->redirect(url('wall_msg_wechat', array('wall_id'=>$wall_id)));
                exit;
            }
            // 显示页面
            $wall_id = I('get.wall_id');
            if(empty($wall_id)){
                $this->redirect(url('index/index'));
            }
            /*if(isset($_GET['debug'])){
                $_SESSION['wechat_user']['openid'] = 'o1UgVuKGG67Y1Yoy_zC1JqoYSH54';
            }*/
            //更改过头像跳到聊天页面
            $wechat_user = dao('wechat_wall_user')->where(array('openid'=>$_SESSION['openid']))->count();
            if($wechat_user > 0){
                $this->redirect(url('wall_msg_wechat', array('wall_id'=>$wall_id)));
            }
            $user = $_SESSION;
            $this->assign('user', $user);
            $this->assign('wall_id', $wall_id);
            $this->display();
        }
    }

    /**
     * 微信端留言页面
     */
    public function actionWallMsgWechat(){
        if(!empty($_SESSION['openid'])){
            if(IS_POST && IS_AJAX){
                $wall_id = I('post.wall_id');
                if(empty($wall_id)){
                    exit(json_encode(array('code'=>1, 'errMsg'=>'请选择对应的活动')));
                }
                $data['user_id'] = I('post.user_id');
                $data['content'] = I('post.content', '', 'trim,htmlspecialchars');
                if(empty($data['user_id']) || empty($data['content'])){
                    exit(json_encode(array('code'=>1, 'errMsg'=>'请先登录或者发表的内容不能为空')));
                }
                $data['addtime'] = gmtime();
                $data['wechat_id'] = $this->wechat_id;

                dao('wechat_wall_msg')->data($data)->add();
                //留言成功，跳转
                exit(json_encode(array('code'=>0, 'errMsg'=>'您的留言正在进行审查，请关注微信墙')));
            }

            $wall_id = I('get.wall_id');
            if(empty($wall_id)){
                $this->redirect(url('index/index'));
            }
            /*if(isset($_GET['debug'])){
                $_SESSION['openid'] = 'o1UgVuKGG67Y1Yoy_zC1JqoYSH54';
            }*/
            $openid = $_SESSION['openid'];
            $wechat_user = dao('wechat_wall_user')->field('id')->where(array('openid'=>$openid))->find();
            //聊天室人数
            // $user_num = $this->model->table('wechat_wall_msg')->field("COUNT(DISTINCT user_id)")->getOne();
            $user_num = dao('wechat_wall_msg')->field("user_id")->count();

            //初始缓存
            $cache_key = md5('cache_wechat_0');
            $list = cache($cache_key);
            if($list === false){
                $sql = "SELECT m.content, m.addtime, u.nickname, u.headimg, u.id FROM {pre}wechat_wall_msg m LEFT JOIN {pre}wechat_wall_user u ON m.user_id = u.id WHERE (m.status = 1 OR u.openid = '$openid') AND u.wall_id = '$wall_id' ORDER BY m.addtime DESC LIMIT 0, 10";
                $data = $this->model->query($sql);

                if($data){
                    usort($data, function($a, $b){
                        if($a['addtime'] == $b['addtime']){
                            return 0;
                        }
                        return $a['addtime'] > $b['addtime'] ? 1 : -1;
                    });
                }

                cache($cache_key, $data, 10);
                $list = cache($cache_key);
            }
            //最后一条数据的key
            $sql = "SELECT count(*) as num FROM {pre}wechat_wall_msg m LEFT JOIN {pre}wechat_wall_user u ON m.user_id = u.id WHERE (m.status = 1 OR u.openid = '$openid') AND u.wall_id = '$wall_id' ORDER BY m.addtime DESC";
            $num = $this->model->query($sql);

            $this->assign('list', $list);
            $this->assign('msg_count', $num[0]['num']);
            $this->assign('user_num', $user_num);
            $this->assign('user', $wechat_user);
            $this->assign('wall_id', $wall_id);
            $this->display();
        }
    }

    /**
     * ajax请求留言
     */
    public function actionGetWallMsg(){
        if(IS_AJAX && IS_GET){
            $start = I('get.start', 0, 'intval');
            $num = I('get.num', 5);
            $wall_id = I('get.wall_id');
            if((!empty($start) || $start === 0) && $num){
                $cache_key = md5('cache_'.$start);
                //微信端数据单独存储
                if(isset($_SESSION) && !empty($_SESSION['openid'])){
                    $cache_key = md5('cache_wechat_'.$start);
                }
                $list = cache($cache_key);
                if($list === false){
                    $sql = "SELECT m.content, m.addtime, u.nickname, u.headimg, u.id, m.status FROM {pre}wechat_wall_msg m LEFT JOIN {pre}wechat_wall_user u ON m.user_id = u.id WHERE m.status = 1 AND u.wall_id = '$wall_id' ORDER BY m.addtime ASC LIMIT ".$start.", ".$num;
                    if(isset($_SESSION) && !empty($_SESSION['openid'])){
                        $openid = $_SESSION['openid'];
                        $sql = "SELECT m.content, m.addtime, u.nickname, u.headimg, u.id, m.status FROM {pre}wechat_wall_msg m LEFT JOIN {pre}wechat_wall_user u ON m.user_id = u.id WHERE (m.status = 1 OR u.openid = '$openid') AND u.wall_id = '$wall_id' ORDER BY m.addtime ASC LIMIT ".$start.", ".$num;
                    }

                    $data = $this->model->query($sql);
                    cache($cache_key, $data, 10);
                    $list = cache($cache_key);
                }
                foreach($list as $k=>$v){
                    $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                }
                $result = array('code'=>0, 'data'=>$list);
                exit(json_encode($result));
            }
        }
        else{
            $result = array('code'=>1, 'errMsg'=>'请求不合法');
            exit(json_encode($result));
        }
    }

    /**
     * 获取公众号配置
     *
     * @param string $secret_key
     * @return array
     */
    private function get_config($secret_key = '')
    {
        $config = dao('wechat')
            ->field('id, token, appid, appsecret, encodingaeskey, ru_id')
            ->where(array('secret_key' => $secret_key, 'status' => 1))
            ->find();
        if (empty($config)) {
            $config = array();
        }
        return $config;
    }

}
