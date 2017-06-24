<?php
namespace app\http\wechat\controllers;

use app\http\base\controllers\Backend;
use ectouch\Wechat;
use ectouch\Form;
use ectouch\Http;

class Admin extends Backend
{

    protected $weObj = '';

    protected $wechat_id = 0;

    protected $page_num = 0;

    public function __construct()
    {
        parent::__construct();
        L(require(MODULE_PATH . 'language/' . C('shop.lang') . '/wechat.php'));
        $this->assign('lang', array_change_key_case(L()));
        // 默认微信公众号
        $this->wechat_id = 1;
        // 查找公众号
        $condition['id'] = $this->wechat_id;
        $mpInfo = $this->model->table('wechat')->field('*')->where($condition)->find();
        if (empty($mpInfo)) {
            $data = array(
                    'id' => $this->wechat_id,
                    'time' => gmtime(),
                    'type' => 2,
                    'status' => 1,
                    'default_wx' => 1
                    );
            $this->model->table('wechat')->data($data)->add();
            $this->redirect('modify');
        }
        // 获取配置信息
        $this->get_config();
        // 初始化 每页分页数量
        $this->page_num = 10;
        $this->assign('page_num', $this->page_num);
    }

    /**
     * 我的公众号
     */
    public function actionIndex()
    {
        $this->redirect('modify');
    }

    /**
     * 设置公众号为默认
     */
    /*
     * public function set_default()
     * {
     * $id = I('get.id');
     * if (empty($id)) {
     * $this->message('请选择公众号', NULL, 'error');
     * }
     * // 取消默认
     * $data['default_wx'] = 0;
     * $this->model->table('wechat')
     * ->data($data)
     * ->where('1')
     * ->save();
     * // 设置默认
     * $data1['default_wx'] = 1;
     * $this->model->table('wechat')
     * ->data($data1)
     * ->where('id = ' . $id)
     * ->save();
     *
     * $this->redirect('index');
     * }
     */

    /**
     * 新增公众号
     */
    public function actionAppend()
    {
        $this->redirect('index');
        /*
         * if (IS_POST) {
         * $data = I('post.data', '', 'trim,htmlspecialchars');
         * $data['time'] = time();
         * // 验证数据
         * $result = Check::rule(array(
         * Check::must($data['name']),
         * L('must_name')
         * ), array(
         * Check::must($data['orgid']),
         * L('must_id')
         * ), array(
         * Check::must($data['token']),
         * L('must_token')
         * ));
         * if ($result !== true) {
         * $this->message($result, NULL, 'error');
         * }
         * // 更新数据
         * $this->model->table('wechat')
         * ->data($data)
         * ->add();
         * $this->redirect('wechat/index');
         * }
         * $this->display();
         */
    }

    /**
     * 修改公众号
     */
    public function actionModify()
    {
        // 公众号设置权限
        $this->admin_priv('wechat_admin');

        $condition['id'] = $this->wechat_id;
        // 提交处理
        if (IS_POST) {
            $data = I('post.data','','trim');
            // 验证数据
            $form = new Form();
            if(!$form->isEmpty($data['name'], 1)){
                $this->message(L('must_name'), NULL, 2);
            }
            if(!$form->isEmpty($data['orgid'], 1)){
                $this->message(L('must_id'), NULL, 2);
            }
            if(!$form->isEmpty($data['token'], 1)){
                $this->message(L('must_token'), NULL, 2);
            }
            // 更新数据
            // 如果appsecret包含 * 跳过不保存数据库
            if(strpos($data['appsecret'], '*') == true){
                unset($data['appsecret']);
            }
            $data['secret_key'] = md5($data['orgid'] . $data['appid']);// 生成自定义密钥
            $this->model->table('wechat')->data($data)->where($condition)->save();
            $this->message(L('wechat_editor') . L('success'), url('modify'));
        }

        // 查询
        $data = $this->model->table('wechat')->where($condition)->find();
        $data['secret_key'] = isset($data['orgid']) && isset($data['appid']) ? $data['secret_key'] : '';
        $data['url'] = url('wechat/index/index', array('key' => $data['secret_key']), false, true);
        // 用*替换字符显示
        $data['appsecret'] = string_to_star($data['appsecret']);

        $this->assign('data', $data);
        $this->display();
    }


    /**
     * 删除公众号
     */
    /*
     * public function delete()
     * {
     * $condition['id'] = intval($_GET['id']);
     * $this->model->table('wechat')
     * ->where($condition)
     * ->delete();
     * $this->redirect('wechat/index');
     * }
     */

    /**
     * 公众号菜单
     */
    public function actionMenuList()
    {
        // 自定义菜单权限
        $this->admin_priv('menu');

        $list = $this->model->table('wechat_menu')->where(array('wechat_id' => $this->wechat_id))->order('sort asc')->select();
        $result = array();
        if (is_array($list)) {
            foreach ($list as $vo) {
                if ($vo['pid'] == 0) {
                    $vo['val'] = ($vo['type'] == 'click') ? $vo['key'] : $vo['url'];
                    $sub_button = array();
                    foreach ($list as $val) {
                        $val['val'] = ($val['type'] == 'click') ? $val['key'] : $val['url'];
                        if ($val['pid'] == $vo['id']) {
                            $sub_button[] = $val;
                        }
                    }
                    $vo['sub_button'] = $sub_button;
                    $result[] = $vo;
                }
            }
        }
        $this->assign('list', $result);
        $this->display();
    }

    /**
     * 编辑菜单
     */
    public function actionMenuEdit()
    {
        if (IS_POST) {
            $id = I('post.id');
            $data = I('post.data');
            $data['wechat_id'] = $this->wechat_id;
            if ('click' == $data['type']) {
                if (empty($data['key'])) {
                    exit(json_encode(array('status' => 0, 'msg' => L('menu_keyword') . L('empty'))));
                }
                $data['url'] = '';
            } else {
                if (empty($data['url'])) {
                    exit(json_encode(array('status' => 0, 'msg' => L('menu_url') . L('empty'))));
                }
                if(substr($data['url'], 0, 4) !== 'http'){
                    exit(json_encode(array('status' => 0,'msg' => L('menu_url') . L('link_err'))));
                }
                if(strlen($data['url']) > 120){
                    exit(json_encode(array('status' => 0, 'msg'=> L('menu_url_length'))));
                }
                $data['key'] = '';
            }
            // 编辑
            if (!empty($id)) {
                $this->model->table('wechat_menu')->data($data)->where(array('id' => $id))->save();
            } else {
                // 添加
                $this->model->table('wechat_menu')->data($data)->add();
            }

            exit(json_encode(array('status' => 1, 'msg' => L('menu_edit'). L('success'))));
        }
        $id = I('get.id');
        $info = array();
        // 顶级菜单
        $top_menu = $this->model->table('wechat_menu')->where(array('pid' => 0, 'wechat_id' => $this->wechat_id))->select();
        if (!empty($id)) {
            $info = $this->model->table('wechat_menu')->where(array('id' => $id))->find();
            // 顶级菜单
            $top_menu = $this->model->query("SELECT * FROM {pre}wechat_menu WHERE id <> $id AND pid = 0 AND wechat_id = $this->wechat_id");
        }
        // 默认值
        $info['status'] = 1;
        $info['sort'] = 0;
        $info['type'] = 'click';

        $this->assign('top_menu', $top_menu);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 删除菜单
     */
    public function actionMenuDel()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->message(L('menu_select_del'), NULL, 2);
        }
        $minfo = $this->model->table('wechat_menu')->field('id, pid')->where(array('id' => $id))->find();
        // 顶级栏目
        if ($minfo['pid'] == 0) {
            $this->model->table('wechat_menu')->where(array('pid' => $minfo['id']))->delete();
        }
        $this->model->table('wechat_menu')->where(array('id' => $minfo['id']))->delete();
        $this->message(L('drop') . L('success'), url('menu_list'));
    }

    /**
     * 生成自定义菜单
     */
    public function actionSysMenu()
    {
        $list = $this->model->table('wechat_menu')->where(array('status' => 1, 'wechat_id' => $this->wechat_id))->order('sort asc')->select();
        if (empty($list)) {
            $this->message(L('menu_empty'), NULL, 2);
        }
        $data = array();
        if (is_array($list)) {
            foreach ($list as $val) {
                if ($val['pid'] == 0) {
                    $sub_button = array();
                    foreach ($list as $v) {
                        if ($v['pid'] == $val['id']) {
                            $sub_button[] = $v;
                        }
                    }
                    $val['sub_button'] = $sub_button;
                    $data[] = $val;
                }
            }
        }
        $menu_list = array();
        foreach ($data as $key => $val) {
            if (empty($val['sub_button'])) {
                $menu_list['button'][$key]['type'] = $val['type'];
                $menu_list['button'][$key]['name'] = $val['name'];
                if ('click' == $val['type']) {
                    $menu_list['button'][$key]['key'] = $val['key'];
                } else {
                    $menu_list['button'][$key]['url'] = html_out($val['url']);
                }
            } else {
                $menu_list['button'][$key]['name'] = $val['name'];
                foreach ($val['sub_button'] as $k => $v) {
                    $menu_list['button'][$key]['sub_button'][$k]['type'] = $v['type'];
                    $menu_list['button'][$key]['sub_button'][$k]['name'] = $v['name'];
                    if ('click' == $v['type']) {
                        $menu_list['button'][$key]['sub_button'][$k]['key'] = $v['key'];
                    } else {
                        $menu_list['button'][$key]['sub_button'][$k]['url'] = html_out($v['url']);
                    }
                }
            }
        }
        /*
         * $data = array( 'button'=>array( array('type'=>'click', 'name'=>"今日歌曲", 'key'=>'MENU_KEY_MUSIC'), array('type'=>'view', 'name'=>"歌手简介", 'url'=>'http://www.qq.com/'), array('name'=>"菜单", 'sub_button'=>array(array('type'=>'click', 'name'=>'hello world', 'key'=>'MENU_KEY_MENU'))) ) );
         */

        $rs = $this->weObj->createMenu($menu_list);
        if (empty($rs)) {
            $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, NULL, 2);
        }
        $this->message(L('menu_create') . L('success'), url('menu_list'));
    }

    /**
     * 关注用户列表
     */
    public function actionSubscribeList()
    {
        // 粉丝管理权限
        $this->admin_priv('fans');

        // 分页
        $offset = $this->pageLimit(url('subscribe_list'), $this->page_num);

        $total = $this->model->table('wechat_user')->where(array('wechat_id' => $this->wechat_id, 'subscribe' => 1))->order('subscribe_time desc')->count();
        $sql = 'SELECT u.*, us.user_name FROM {pre}wechat_user u LEFT JOIN {pre}wechat_user_tag t ON u.openid = t.openid LEFT JOIN {pre}users us ON us.user_id = u.ect_uid where u.subscribe = 1 and u.wechat_id = ' . $this->wechat_id . ' group by u.uid order by u.subscribe_time desc limit ' . $offset;
        $list = $this->model->query($sql);

        foreach ($list as $key => $value) {
            $list[$key]['taglist'] = $this->get_user_tag($value['openid']); // 粉丝所属标签
        }
        // 标签
        $where['wechat_id'] = $this->wechat_id;
        $tag_list = $this->model->table('wechat_user_taglist')->field('id, tag_id, name, count')->where($where)->order('id, sort desc')->select();
        $this->assign('tag_list', $tag_list);

        // 分组
        $where1['wechat_id'] = $this->wechat_id;
        $group_list = $this->model->table('wechat_user_group')->field('id, group_id, name, count')->where($where1)->order('id, sort desc')->select();

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->assign('group_list', $group_list);
        $this->display();
    }

    /**
     * 关注用户列表搜索
     */
    public function actionSubscribeSearch()
    {
        $keywords = I('request.keywords', '', 'trim');
        $group_id = I('request.group_id', 0, 'intval');
        $tag_id = I('request.tag_id', 0, 'intval');
        $where = '';
        $where1 = '';
        if (!empty($keywords)) {
            $where .= ' and (u.nickname like "%' . $keywords . '%" or us.user_name like "%' . $keywords . '%")';
        }
        if (isset($group_id) && $group_id > 0) {
            $where .= ' and u.groupid = ' . $group_id;
        }
        if (isset($tag_id) && $tag_id > 0) {
            $where .= ' and t.tag_id = ' . $tag_id;
        }

        // 分页
        $filter['group_id'] = $group_id;
        $filter['tag_id'] = $tag_id;
        $filter['keywords'] = $keywords;
        $offset = $this->pageLimit(url('subscribe_search', $filter), $this->page_num);

        $sql = 'SELECT count(*) as number FROM {pre}wechat_user u
            LEFT JOIN {pre}wechat_user_tag t ON u.openid = t.openid
            LEFT JOIN {pre}users us ON us.user_id = u.ect_uid
            WHERE u.subscribe = 1 AND u.wechat_id = ' . $this->wechat_id . $where . ' order by u.subscribe_time desc';
        $total = $this->model->query($sql);

        $sql1 = 'SELECT u.*, us.user_name FROM {pre}wechat_user u
            LEFT JOIN {pre}wechat_user_tag t ON u.openid = t.openid
            LEFT JOIN {pre}users us ON us.user_id = u.ect_uid
            WHERE u.subscribe = 1 AND u.wechat_id = ' . $this->wechat_id . $where . ' group by u.uid order by u.subscribe_time desc limit ' . $offset;
        $list = $this->model->query($sql1);

        foreach ($list as $key => $value) {
            $list[$key]['taglist'] = $this->get_user_tag($value['openid']); // 粉丝所属标签
        }

        // 分组
        $where2['wechat_id'] = $this->wechat_id;
        $group_list = $this->model->table('wechat_user_group')->field('id, group_id, name, count')->where($where2)->order('id, sort desc')->select();

        // 标签
        $where3['wechat_id'] = $this->wechat_id;
        $tag_list = $this->model->table('wechat_user_taglist')->field('id, tag_id, name, count')->where($where3)->order('id, sort desc')->select();
        $this->assign('tag_list', $tag_list);

        $this->assign('page', $this->pageShow($total[0]['number']));
        $this->assign('list', $list);
        $this->assign('group_id', $group_id); //分组内搜索
        $this->assign('group_list', $group_list);

        $this->assign('tag_id', $tag_id);
        $this->assign('tag_list', $tag_list);

        $this->display('subscribelist');
    }

    // 获得用户的标签列表
    private function get_user_tag($openid = ''){
        $sql = "SELECT tl.tag_id, tl.name FROM {pre}wechat_user_taglist tl LEFT JOIN {pre}wechat_user_tag t ON tl.tag_id = t.tag_id LEFT JOIN {pre}wechat_user u ON u.openid = t.openid where u.openid = '" .$openid. "' and u.subscribe = 1 and tl.wechat_id = '" . $this->wechat_id . "' ";
        $tags = $this->model->query($sql);
        $num = count($tags);
        if($num < 3){
            // 查询微信端粉丝标签列表
            $rs = $this->weObj->getUserTaglist($openid);
            if(!empty($rs)){
                foreach ($rs as $key => $val) {
                    $data['wechat_id'] = $this->wechat_id;
                    $data['tag_id'] = $val;
                    $data['openid'] = $openid;
                    // 查询本地数据库已存在的标签列表
                    $where = array(
                        'tag_id' => $val,
                        'wechat_id' => $this->wechat_id
                        );
                    $tag_num = $this->model->table('wechat_user_tag')->where($where)->count();
                    if($tag_num == 0){
                        $this->model->table('wechat_user_tag')->data($data)->add();
                    }
                }
            }
        }
        return $tags;
    }

    /**
     * 移动关注用户分组
     */
    public function actionSubscribeMove()
    {
        if (IS_POST) {
            if (empty($this->wechat_id)) {
                $this->message(L('wechat_empty'), NULL, 2);
            }
            $group_id = I('post.group_id', 0, 'intval');
            $openid = I('post.id');
            if (is_array($openid)) {
                foreach ($openid as $v) {
                    // 微信端移动用户
                    $this->weObj->updateGroupMembers($group_id, $v);
                    // 数据处理
                    $this->model->table('wechat_user')->data(array('groupid' => $group_id))->where(array('openid' => $v, 'wechat_id' => $this->wechat_id))->save();
                }
                $this->message(L('sub_move_sucess'), url('subscribe_list'));
            } else {
                $this->message(L('select_please'), NULL, 2);
            }
        }
    }

    /**
     * 同步粉丝（直接插入数据，不能直接执行）
     */
    public function actionSysfans()
    {
        //微信用户
        $wechat_user = $this->weObj->getUserList();
        foreach ($wechat_user['data']['openid'] as $v) {
            $info = $this->weObj->getUserInfo($v);
            $info['wechat_id'] = $this->wechat_id;
            $this->model->table('wechat_user')->data($info)->add();
        }
        $this->redirect('subscribe_list', array('wechat_id' => $this->wechat_id));
    }

    /**
     * 更新用户信息
     */
    public function actionSubscribeUpdate()
    {
        if (empty($this->wechat_id)) {
            $this->message(L('wechat_empty'), NULL, 2);
        }
        // 本地数据
        $where['wechat_id'] = $this->wechat_id;
        $local_user = $this->model->table('wechat_user')->field('openid')->where($where)->select();
        if (empty($local_user)) {
            $local_user = array();
        }
        $user_list = array();
        foreach ($local_user as $v) {
            $user_list[] = $v['openid'];
        }
        // 微信端数据
        $wechat_user = $this->weObj->getUserList();

        if ($wechat_user['total'] <= 10000) {
            $wechat_user_list = $wechat_user['data']['openid'];
        } else {
            $num = ceil($wechat_user['total'] / 10000);
            $wechat_user_list = $wechat_user['data']['openid'];
            for ($i = 0; $i <= $num; $i++) {
                $wechat_user1 = $this->weObj->getUserList($wechat_user['next_openid']);
                $wechat_user_list = array_merge($wechat_user_list, $wechat_user1['data']['openid']);
            }
        }
        // 数据对比
        foreach ($local_user as $val) {
            // 数据在微信端存在
            if (in_array($val['openid'], $wechat_user_list)) {
                $info = $this->weObj->getUserInfo($val['openid']);
                $where1['openid'] = $val['openid'];
                $where1['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_user')->data($info)->where($where1)->save();
            } else {
                $data['subscribe'] = 0;
                $where2['openid'] = $val['openid'];
                $where2['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_user')->data($data)->where($where2)->save();
            }
        }
        // 数据不存在
        foreach ($wechat_user_list as $vs) {
            if (!in_array($vs, $user_list)) {
                $info = $this->weObj->getUserInfo($vs);
                $info['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_user')->data($info)->add();
            }
        }

        $this->redirect('subscribe_list');
    }

    /**
     * 发送客服消息
     */
    public function actionSendCustomMessage()
    {
        if (IS_POST) {
            $data = I('post.data');
            $openid = I('post.openid');
            $form = new Form();
            if (!$form->isEmpty($openid, 1)) {
                exit(json_encode(array('status' => 0, 'msg' => L('select_openid'))));
            }
            if (!$form->isEmpty($data['msg'], 1)) {
                exit(json_encode(array('status' => 0, 'msg' => L('message_content') . L('empty'))));
            }
            $data['send_time'] = gmtime();
            $data['wechat_id'] = $this->wechat_id;
            $data['wechat_admin_id'] = 1; //  默认微信公众号回复标识
            // 微信端发送消息
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'text',
                'text' => array(
                    'content' => $data['msg']
                )
            );
            $rs = $this->weObj->sendCustomMessage($msg);
            if (empty($rs)) {
                $errmsg = '仅48小时内给公众号发送过信息的粉丝才能接收到信息';
                // $errmsg = L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg;
                exit(json_encode(array('status' => 0, 'msg' => $errmsg)));
            }
            // 添加数据
            dao('wechat_custom_message')->data($data)->add();
            exit(json_encode(array('status' => 1)));
        }
        $uid = I('get.uid');
        $openid = I('get.openid');
        if ($openid) {
            $where['openid'] = $openid;
        } else {
            $where['uid'] = $uid;
        }
        $where['wechat_id'] = $this->wechat_id;
        $info = dao('wechat_user')->field('uid, headimgurl, nickname, openid')->where($where)->find();
        // 将大图转成64小图
        $info['headimgurl'] = substr($info['headimgurl'], 0, -1) . '64';

        // 最新发送的消息6条
        $list = dao('wechat_custom_message')
            ->field('msg, send_time, wechat_admin_id')
            ->where(array('uid' => $uid, 'wechat_id' => $this->wechat_id))
            ->order('send_time DESC, id DESC')
            ->limit(6)
            ->select();
        $list = array_reverse($list); // 倒序显示
        foreach ($list as $key => $value) {
            $list[$key]['send_time'] = local_date('Y-m-d H:i:s', $value['send_time']);
            $list[$key]['headimgurl'] = $info['headimgurl'];
            $list[$key]['wechat_headimgurl'] = __TPL__ . '/img/shop_app_icon.png';
        }
        $this->assign('list', $list);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 客服消息列表
     */
    public function actionCustomMessageList()
    {
        $uid = I('get.uid', 0, 'intval');
        if (empty($uid)) {
            $this->message(L('select_openid'), NULL, 2);
        }
        $nickname = $this->model->table('wechat_user')->where(array('uid' => $uid, 'wechat_id' => $this->wechat_id))->getField('nickname');
        // 分页
        $filter['uid'] = $uid;
        $offset = $this->pageLimit(url('custom_message_list', $filter), $this->page_num);
        $total = $this->model->table('wechat_custom_message')->where(array('uid' => $uid, 'wechat_id' => $this->wechat_id))->order('send_time desc')->count();
        $list = $this->model->table('wechat_custom_message')
            ->field('msg, send_time, wechat_id')
            ->where(array('uid' => $uid, 'wechat_id' => $this->wechat_id))
            ->order('send_time desc, id desc')
            ->limit($offset)
            ->select();

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->assign('nickname', $nickname);
        $this->display();
    }

    /**
     * 标签管理
     */
    public function actionTagsList()
    {
        $where['wechat_id'] = $this->wechat_id;
        $tag_list = $this->model->table('wechat_user_taglist')
            ->where($where)
            ->order('id, sort desc')
            ->select();
        $this->assign('list', $tag_list);
        $this->display();
    }

    /**
     * 同步标签
     */
    public function actionSysTags()
    {
        if (empty($this->wechat_id)) {
            $this->message(L('wechat_empty'), NULL, 2);
        }
        // 获取公众号已创建的标签
        $list = $this->weObj->getTags();
        if (empty($list)) {
            $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, NULL, 2);
        }
        // 本地标签列表
        // $where['wechat_id'] = $this->wechat_id;
        // $this->model->table('wechat_user_taglist')->where($where)->delete();
        foreach ($list['tags'] as $key => $val) {
            $data['wechat_id'] = $this->wechat_id;
            $data['tag_id'] = $val['id'];
            $data['name'] = $val['name'];
            $data['count'] = $val['count'];

            $where = array(
                'tag_id' => $val['id'],
                'wechat_id' => $this->wechat_id
                );
            $tag_num = $this->model->table('wechat_user_taglist')->where($where)->count();
            if($tag_num > 0){
                $this->model->table('wechat_user_taglist')->data($data)->where($where)->save();
            }else{
                $this->model->table('wechat_user_taglist')->data($data)->add();
            }
        }
        $this->redirect('subscribe_list');
    }

    /**
     * 添加、编辑标签
     */
    public function actionTagsEdit()
    {
        if (empty($this->wechat_id)) {
            $this->message(L('wechat_empty'), NULL, 2);
        }
        if (IS_POST) {
            $name = I('post.name');
            $id = I('post.id', 0, 'intval');
            $tag_id = I('post.tag_id', 0 , 'intval');
            if (empty($name)) {
                exit(json_encode(array('status' => 0, 'msg' => L('group_name') . L('empty'))));
            }
            $data['name'] = $name;
            if (!empty($id)) {
                // 微信端编辑标签名称
                $rs = $this->weObj->updateTags($tag_id, $name);
                if (empty($rs)) {
                    exit(json_encode(array('status' => 0, 'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg)));
                }
                // 本地数据更新
                $where['id'] = $id;
                $where['wechat_id'] = $this->wechat_id;
                $data['tag_id'] = !empty($rs['tag']['id']) ? $rs['tag']['id'] : $tag_id;
                $this->model->table('wechat_user_taglist')
                    ->data($data)
                    ->where($where)
                    ->save();
            } else {
                // 微信端新增创建标签
                $rs = $this->weObj->createTags($name);
                if (empty($rs)) {
                    exit(json_encode(array('status' => 0, 'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg)));
                }

                // 本地数据新增
                $data['tag_id'] = !empty($rs['tag']['id']) ? $rs['tag']['id'] : $tag_id;
                $data['name'] = $rs['tag']['name'] ;
                $data['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_user_taglist')
                    ->data($data)
                    ->add();
            }
            exit(json_encode(array('status' => 1)));
        }
        $id = I('get.id', 0, 'intval');
        $taglist = array();
        if (!empty($id)) {
            $where['id'] = $id;
            $where['wechat_id'] = $this->wechat_id;
            $taglist = $this->model->table('wechat_user_taglist')
                ->field('id, tag_id, name')
                ->where($where)
                ->find();
        }

        $this->assign('taglist', $taglist);
        $this->display();
    }

    /**
     * 批量为用户打标签
     */
    public function actionBatchTagging()
    {
        if (IS_POST) {
            if (empty($this->wechat_id)) {
                $this->message(L('wechat_empty'), NULL, 2);
            }
            $tag_id = I('post.tag_id', 0, 'intval');
            $openlist = I('post.id');
            if (is_array($openlist)) {
                // 微信端打标签
                $rs = $this->weObj->batchtaggingTagsMembers($tag_id, $openlist);
                if (empty($rs)) {
                    $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, url('subscribe_list'), 2);
                    // exit(json_encode(array('status' => 0, 'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg)));
                }
                // 本地数据处理
                $is_true = 0;
                foreach ($openlist as $v) {

                    $sql = "SELECT u.uid, count(t.openid) as openid_num FROM {pre}wechat_user_tag t LEFT JOIN {pre}wechat_user u ON t.openid = u.openid WHERE u.openid = '" .$v. "' AND u.subscribe = 1 AND u.wechat_id = ' ". $this->wechat_id . "' ";
                    $res = $this->model->query($sql);
                    if(!empty($res)){
                        // 每个用户最多加三个标签
                        if($res[0]['openid_num'] < 3){
                            $data['wechat_id'] = $this->wechat_id;
                            $data['tag_id'] = $tag_id;
                            $data['openid'] = $v;
                            // 不能重复加入相同标签
                            $where2 = array(
                                'tag_id' => $tag_id,
                                'openid' => $v
                                );
                            $tag_num = $this->model->table('wechat_user_tag')->where($where2)->count();
                            if($tag_num == 0){
                                $this->model->table('wechat_user_tag')->data($data)->add();
                            }else{
                                $is_true = 1;
                            }
                        }else{
                            $is_true = 3;
                        }
                    }
                }
                if($is_true == 0){
                    $this->message(L('tag_move_sucess'), url('subscribe_list'));
                }elseif($is_true == 1){
                    $this->message(L('tag_move_fail') . ", " . L('tag_move_exit'), url('subscribe_list'), 2);
                }elseif($is_true == 3){
                    $this->message(L('tag_move_fail') . ", " . L('tag_move_three'), url('subscribe_list'), 2);
                }
            } else {
                $this->message(L('select_please'), NULL, 2);
            }
        }
    }
    /**
     * 批量为用户取消标签
     */
    public function actionBatchUnTagging()
    {
        if (IS_POST) {
            if (empty($this->wechat_id)) {
                $this->message(L('wechat_empty'), NULL, 2);
            }
            $tag_id = I('post.tagid', 0, 'intval');
            $openid = I('post.openid');

            $openlist = array('0' => $openid);
            if (is_array($openlist)) {
                // 微信端取消标签
                $rs = $this->weObj->batchuntaggingTagsMembers($tag_id, $openlist);
                if (empty($rs)) {
                    exit(json_encode(array('status' => 0, 'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg)));
                }
                // 本地数据处理 删除标签
                foreach ($openlist as $v) {
                    $where = array(
                        'tag_id' => $tag_id,
                        'openid' => $v
                        );
                    $this->db->table('wechat_user_tag')->where($where)->delete();
                }
                exit(json_encode(array('status' => 1, 'msg' => L('tag_move_sucess'))));
            } else {
                exit(json_encode(array('status' => 0, 'msg' => L('select_please') . L('empty'))));
            }
        }
    }


    /**
     * 分组管理
     */
    public function actionGroupsList()
    {
        $where['wechat_id'] = $this->wechat_id;
        $local_list = $this->model->table('wechat_user_group')
            ->where($where)
            ->order('id, sort desc')
            ->select();
        $this->assign('list', $local_list);
        $this->display();
    }

    /**
     * 同步分组
     */
    public function actionSysGroups()
    {
        if (empty($this->wechat_id)) {
            $this->message(L('wechat_empty'), NULL, 2);
        }
        // 微信端分组列表
        $list = $this->weObj->getGroup();
        if (empty($list)) {
            $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, NULL, 2);
        }
        // 本地分组
        $where['wechat_id'] = $this->wechat_id;
        $this->model->table('wechat_user_group')->where($where)->delete();
        foreach ($list['groups'] as $key => $val) {
            $data['wechat_id'] = $this->wechat_id;
            $data['group_id'] = $val['id'];
            $data['name'] = $val['name'];
            $data['count'] = $val['count'];
            $this->model->table('wechat_user_group')->data($data)->add();
        }
        $this->redirect('subscribe_list');
    }

    /**
     * 添加、编辑分组
     */
    public function actionGroupsEdit()
    {
        if (empty($this->wechat_id)) {
            $this->message(L('wechat_empty'), NULL, 2);
        }
        if (IS_POST) {
            $name = I('post.name');
            $id = I('post.id', 0, 'intval');
            $group_id = I('post.group_id');
            if (empty($name)) {
                exit(json_encode(array('status' => 0, 'msg' => L('group_name') . L('empty'))));
            }
            $data['name'] = $name;
            if (!empty($id)) {
                // 微信端更新
                $rs = $this->weObj->updateGroup($group_id, $name);
                if (empty($rs)) {
                    exit(json_encode(array('status' => 0, 'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg)));
                }
                // 数据更新
                $where['id'] = $id;
                $where['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_user_group')
                    ->data($data)
                    ->where($where)
                    ->save();
            } else {
                // 微信端新增
                $rs = $this->weObj->createGroup($name);
                if (empty($rs)) {
                    exit(json_encode(array('status' => 0, 'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg)));
                }
                // 数据新增
                $data['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_user_group')
                    ->data($data)
                    ->add();
            }
            exit(json_encode(array('status' => 1)));
        }
        $id = I('get.id', 0, 'intval');
        $group = array();
        if (!empty($id)) {
            $where['id'] = $id;
            $where['wechat_id'] = $this->wechat_id;
            $group = $this->model->table('wechat_user_group')
                ->field('id, group_id, name')
                ->where($where)
                ->find();
        }

        $this->assign('group', $group);
        $this->display();
    }

    /**
     * 渠道二维码
     */
    public function actionQrcodeList()
    {
        // 二维码管理权限
        $this->admin_priv('qrcode');

        // 分页
        $offset = $this->pageLimit(url('qrcode_list'), $this->page_num);
        $total = $this->model->query("SELECT count(*) as count FROM {pre}wechat_qrcode WHERE username is null AND wechat_id = $this->wechat_id ");
        $list = $this->model->query("SELECT * FROM {pre}wechat_qrcode WHERE username is null AND wechat_id = $this->wechat_id ORDER BY sort DESC, id ASC");

        $this->assign('page', $this->pageShow($total[0]['count']));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 编辑二维码
     */
    public function actionQrcodeEdit()
    {
        if (IS_POST) {
            $data = I('post.data');
            $data['wechat_id'] = $this->wechat_id;
            // 验证数据
            $form = new Form();
            if (!$form->isEmpty($data['function'], 1)) {
                exit(json_encode(array('status' => 0, 'msg' => L('qrcode_function') . L('empty'))));
            }
            if (!$form->isEmpty($data['scene_id'], 1)) {
                exit(json_encode(array('status' => 0, 'msg' => L('qrcode_scene_value') . L('empty'))));
            }

            $rs = $this->model->table('wechat_qrcode')
                ->where(array('scene_id' => $data['scene_id'], 'wechat_id' => $this->wechat_id))
                ->count();
            if ($rs > 0) {
                exit(json_encode(array('status' => 0, 'msg' => L('qrcode_scene_limit'))));
            }
            $this->model->table('wechat_qrcode')
                ->data($data)
                ->add();
            exit(json_encode(array('status' => 1)));
        }
        $id = I('get.id', 0, 'intval');
        if (!empty($id)) {
            $status = I('get.status', 0, 'intval');
            $this->model->table('wechat_qrcode')
                ->data(array('status' => $status))
                ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
                ->save();
            $this->redirect('qrcode_list');
        }
        $this->display();
    }

    /**
     * 扫码引荐
     */
    public function actionShareList()
    {
        // 二维码管理权限
        $this->admin_priv('share');

        // 分页
        $offset = $this->pageLimit(url('share_list'), $this->page_num);
        $total = $this->model->query("SELECT count(*) as count FROM {pre}wechat_qrcode WHERE username is not null AND wechat_id = $this->wechat_id ");
        $list = $this->model->query("SELECT * FROM {pre}wechat_qrcode WHERE username is not null AND wechat_id = $this->wechat_id ORDER BY sort DESC, id ASC");

        // 成交量
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['share_account'] = $this->model->table('affiliate_log')
                    ->where(array('separate_type' => 0, 'user_id' => $val['scene_id']))
                    ->getField('sum(money)');
            }
        }
        $this->assign('page', $this->pageShow($total[0]['count']));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 编辑二维码
     */
    public function actionShareEdit()
    {
        if (IS_POST) {
            $data = I('post.data');
            $data['wechat_id'] = $this->wechat_id;
            // 验证数据
            $form = new Form();
            if (!$form->isEmpty($data['username'], 1)) {
                exit(json_encode(array('status' => 0, 'msg' => L('share_name') . L('empty'))));
            }
            if (!$form->isEmpty($data['scene_id'], 1)) {
                exit(json_encode(array('status' => 0, 'msg' => L('share_userid') . L('empty'))));
            }

            $rs = $this->model->table('wechat_qrcode')
                ->where(array('scene_id' => $data['scene_id'], 'wechat_id' => $this->wechat_id))
                ->count();
            if ($rs > 0) {
                exit(json_encode(array('status' => 0, 'msg' => L('qrcode_scene_limit'))));
            }

            if (empty($data['expire_seconds'])) {
                $data['type'] = 1;
            } else {
                $data['type'] = 0;
            }
            $this->model->table('wechat_qrcode')
                ->data($data)
                ->add();
            exit(json_encode(array('status' => 1)));
        }
        $this->display();
    }

    /**
     * 删除二维码
     */
    public function actionQrcodeDel()
    {
        $id = I('get.id', 0, 'intval');
        if (empty($id)) {
            $this->message(L('select_please') . L('qrcode'), NULL, 2);
        }
        $this->model->table('wechat_qrcode')
            ->where(array('id' => $id))
            ->delete();
        $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('qrcode_list');
        $this->message(L('qrcode') . L('drop') . L('success'), $url);
    }

    /**
     * 更新并获取二维码
     */
    public function actionQrcodeGet()
    {
        $id = I('get.id', 0, 'intval');
        if (empty($id)) {
            exit(json_encode(array('status' => 0, 'msg' => L('select_please') . L('qrcode'))));
        }
        $rs = $this->model->table('wechat_qrcode')
            ->field('type, scene_id, expire_seconds, qrcode_url, status')
            ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
            ->find();
        if (empty($rs['status'])) {
            exit(json_encode(array('status' => 0, 'msg' => L('qrcode_isdisabled'))));
        }
        if (empty($rs['qrcode_url'])) {
            // 获取二维码ticket
            $ticket = $this->weObj->getQRCode((int)$rs['scene_id'], $rs['type'], $rs['expire_seconds']);
            if (empty($ticket)) {
                exit(json_encode(array('status' => 0, 'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg)));
            }
            $data['ticket'] = $ticket['ticket'];
            $data['expire_seconds'] = $ticket['expire_seconds'];
            $data['endtime'] = gmtime() + $ticket['expire_seconds'];
            // 二维码地址
            $qrcode_url = $this->weObj->getQRUrl($ticket['ticket']);
            $data['qrcode_url'] = $qrcode_url;

            $this->model->table('wechat_qrcode')
                ->data($data)
                ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
                ->save();
        } else {
            $qrcode_url = $rs['qrcode_url'];
        }
        // 生成短链接
        $short_url = $this->weObj->getShortUrl($qrcode_url);
        $this->assign('short_url', $short_url);

        $this->assign('qrcode_url', $qrcode_url);
        $this->display();
    }

    /**
     * 图文回复(news)
     */
    public function actionArticle()
    {
        // 素材管理权限
        $this->admin_priv('media');

        // 分页
        $this->page_num = 15;
        $offset = $this->pageLimit(url('article'), $this->page_num);
        $where['wechat_id'] = $this->wechat_id;
        $where['type'] = 'news';
        $total = $this->model->table('wechat_media')->where($where)->count();

        $list = $this->model->table('wechat_media')
            ->field('id, title, file, digest, content, add_time, sort, article_id')
            ->where($where)
            ->order('sort DESC, add_time DESC')
            ->limit($offset)
            ->select();
        foreach ((array)$list as $key => $val) {
            // 多图文
            if (!empty($val['article_id'])) {
                $id = explode(',', $val['article_id']);
                foreach ($id as $k => $v) {
                    $list[$key]['articles'][] = $this->model->table('wechat_media')
                        ->field('id, title, file, add_time')
                        ->where(array('id' => $v, 'wechat_id' => $this->wechat_id))
                        ->find();
                    $list[$key]['articles'][$k]['file'] = get_wechat_image_path($list[$key]['articles'][$k]['file']);
                }
            }
            // 过滤抽奖活动图片
            if(!strstr($val['file'], 'app/modules/')){
                $list[$key]['file'] = get_wechat_image_path($val['file']);
            }else{
                $list[$key]['is_prize'] = 1;
            }
            $list[$key]['content'] = empty($val['digest']) ? sub_str(strip_tags(html_out($val['content'])), 50) : $val['digest'];
        }
        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 图文回复编辑
     */
    public function actionArticleEdit()
    {
        if (IS_POST) {
            $id = I('post.id');
            $data = I('post.data');
            $data['content'] = I('post.content', '', 'new_html_in');
            $pic_path = I('post.file_path');

            $form = new Form();
            if(!$form->isEmpty($data['title'], 1)){
                $this->message(L('title') . L('empty'), NULL, 2);
            }
            if(!$form->isEmpty($data['content'], 1)){
                $this->message(L('content') . L('empty'), NULL, 2);
            }
            /*if(!empty($data['link']) && !$form->isUrl($data['link'], 1)){
                $this->message(L('link_err'), NULL, 2);
            }*/
            $pic_path = edit_upload_image($pic_path);
            // 封面处理
            $cover = $_FILES['pic'];
            if ($cover['name']) {
                $type = array('image/jpeg','image/png');
                if(!in_array($_FILES['pic']['type'], $type)){
                    $this->message(L('not_file_type'), NULL, 2);
                }
                $result = $this->upload('data/attached/article', true);
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 2);
                }
                $data['file'] = $result['url'];
                $data['file_name'] = $cover['name'];
                $data['size'] = $cover['size'];
            } else {
                $data['file'] = $pic_path;
            }
            if(!$form->isEmpty($data['file'], 1)){
                $this->message(L('please_upload'), NULL, 2);
            }
            $data['wechat_id'] = $this->wechat_id;
            $data['type'] = 'news';

            if (!empty($id)) {
                // 删除原图片
                if ($pic_path != $data['file']) {
                    $this->remove($pic_path);
                }
                $data['edit_time'] = gmtime();
                $this->model->table('wechat_media')
                    ->data($data)
                    ->where(array('id' => $id))
                    ->save();
            } else {
                $data['add_time'] = gmtime();
                $this->model->table('wechat_media')
                    ->data($data)
                    ->add();
            }
            $this->message(L('wechat_editor') . L('success'), url('article'));
        }
        $id = I('get.id');
        if (!empty($id)) {
            $article = $this->model->table('wechat_media')
                ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
                ->find();
            $article['file'] = get_wechat_image_path($article['file']);
            $this->assign('article', $article);
        }
        $this->display();
    }

    /**
     * 多图文回复编辑
     */
    public function actionArticleEditNews()
    {
        if (IS_POST) {
            $id = I('post.id');
            $article_id = I('post.article');
            $data['sort'] = I('post.sort');
            if (is_array($article_id)) {
                $data['article_id'] = implode(',', $article_id);
                $data['wechat_id'] = $this->wechat_id;
                $data['type'] = 'news';

                if (!empty($id)) {
                    $data['edit_time'] = gmtime();
                    $this->model->table('wechat_media')
                        ->data($data)
                        ->where(array('id' => $id))
                        ->save();
                } else {
                    $data['add_time'] = gmtime();
                    $this->model->table('wechat_media')
                        ->data($data)
                        ->add();
                }

                $this->redirect('article');
            } else {
                $this->message(L('please_add_again'), NULL, 2);
            }
        }
        $id = I('get.id');
        if (!empty($id)) {
            $rs = $this->model->table('wechat_media')
                ->field('article_id, sort')
                ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
                ->find();
            if (!empty($rs['article_id'])) {
                $articles = array();
                $art = explode(',', $rs['article_id']);
                foreach ($art as $key => $val) {
                    $articles[] = $this->model->table('wechat_media')
                        ->field('id, title, file, add_time')
                        ->where(array('id' => $val))
                        ->find();
                    $articles[$key]['file'] = get_wechat_image_path($articles[$key]['file']);
                }
                $this->assign('articles', $articles);
            }
            $this->assign('sort', $rs['sort']);
        }

        $this->assign('id', $id);
        $this->display();
    }

    /**
     * 单图文列表供多图文选择
     */
    public function actionArticlesList()
    {
        // 分页
        $this->page_num = 4; // 显示4条 是鉴于微信消息4条正好铺满手机屏幕
        $offset = $this->pageLimit(url('articles_list'), $this->page_num);
        $total = $this->model->query("SELECT count(*) as count  FROM {pre}wechat_media WHERE wechat_id =  $this->wechat_id  and type = 'news' and article_id is NULL");
        // 图文信息
        $article = $this->model->query("SELECT id, title, file, digest, content, add_time FROM {pre}wechat_media WHERE wechat_id =  $this->wechat_id  and type = 'news' and article_id is NULL ORDER BY sort DESC, add_time DESC limit $offset");
        if (!empty($article)) {
            foreach ($article as $k => $v) {
                $article[$k]['file'] = get_wechat_image_path($v['file']);
                $article[$k]['content'] = empty($v['digest']) ? sub_str(strip_tags(html_out($v['content'])), 50) : $v['digest'];
            }
        }

        $this->assign('page', $this->pageShow($total[0]['count']));
        $this->assign('article', $article);
        $this->display();
    }

    /**
     * 多图文回复清空
     */
    public function actionArticleNewsDel()
    {
        $id = I('get.id');
        if (!empty($id)) {
            $this->model->table('wechat_media')
                ->data(array('article_id' => 0))
                ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
                ->save();
        }
        $this->redirect('article_edit_news');
    }

    /**
     * 图文回复删除
     */
    public function actionArticleDel()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->message(L('select_please') . L('article'), NULL, 2);
        }
        $pic = $this->model->table('wechat_media')
            ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
            ->getField('file');

        $this->model->table('wechat_media')
            ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
            ->delete();
        $this->remove($pic);

        $this->redirect('article');
    }

    /**
     * 图片管理(image)
     */
    public function actionPicture()
    {
        // 素材管理权限
        $this->admin_priv('media');

        if (IS_POST) {
            if ($_FILES['pic']['name']) {
                $type = array('image/jpeg','image/png'); // jpg, png
                if(!in_array($_FILES['pic']['type'], $type)){
                    $this->message(L('not_file_type'), url('picture'), 2);
                }
                $result = $this->upload('data/attached/article', true);
                if ($result['error'] > 0) {
                    $this->message($result['message'], url('picture'), 2);
                }
                $data['file'] = $result['url'];
                $data['file_name'] = $_FILES['pic']['name'];
                $data['size'] = $_FILES['pic']['size'];
                $data['type'] = 'image';
                $data['add_time'] = gmtime();
                $data['wechat_id'] = $this->wechat_id;

                $this->model->table('wechat_media')
                    ->data($data)
                    ->add();

                $this->redirect('picture');
            }
        }
        // 分页
        $offset = $this->pageLimit(url('picture'), $this->page_num);

        $total = $this->model->query("SELECT count(*) as count FROM {pre}wechat_media WHERE wechat_id = $this->wechat_id and file is NOT NULL and (type = 'image')");
        $list = $this->model->query("SELECT id, file, file_name, thumb, size FROM {pre}wechat_media WHERE wechat_id = $this->wechat_id and file is NOT NULL and (type = 'image') order by sort DESC, add_time DESC limit $offset");
        if (empty($list)) {
            $list = array();
        }
        foreach ($list as $key => $val) {
            if ($val['size'] > (1024 * 1024)) {
                $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
            } else {
                $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
            }
            // 过滤抽奖活动图片
            if(!strstr($val['file'], 'app/modules/')){
                $list[$key]['file'] = get_wechat_image_path($val['file']);
            }else{
                $list[$key]['is_prize'] = 1;
            }
        }

        $this->assign('page', $this->pageShow($total[0]['count']));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 语音
     */
    public function actionVoice()
    {
        // 素材管理权限
        $this->admin_priv('media');
        if (IS_POST) {
            if ($_FILES['voice']['name']) {
                $type = array('audio/amr', 'audio/x-mpeg'); // amr,mp3
                if(!in_array($_FILES['voice']['type'], $type)){
                    $this->message(L('not_file_type'), url('voice'), 2);
                }
                $result = $this->upload('data/attached/voice', true);
                if ($result['error'] > 0) {
                    $this->message($result['message'], url('voice'), 2);
                }
                $data['file'] = $result['url'];
                $data['file_name'] = $_FILES['voice']['name'];
                $data['size'] = $_FILES['voice']['size'];
                $data['type'] = 'voice';
                $data['add_time'] = gmtime();
                $data['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_media')
                    ->data($data)
                    ->add();

                //$url = $_SERVER['HTTP_REFERER'];
                $this->redirect('voice');
            }
        }
        // 分页
        $offset = $this->pageLimit(url('voice'), $this->page_num);
        $total = $this->model->table('wechat_media')
            ->where(array('wechat_id' => $this->wechat_id, 'type' => 'voice'))
            ->count();

        $list = $this->model->table('wechat_media')
            ->field('id, file, file_name, size')
            ->where(array('wechat_id' => $this->wechat_id, 'type' => 'voice'))
            ->order('add_time desc, sort asc')
            ->limit($offset)
            ->select();
        if (empty($list)) {
            $list = array();
        }
        foreach ($list as $key => $val) {
            if ($val['size'] > (1024 * 1024)) {
                $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
            } else {
                $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
            }
        }
        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 视频
     */
    public function actionVideo()
    {
        // 素材管理权限
        $this->admin_priv('media');

        // 分页
        $offset = $this->pageLimit(url('video'), $this->page_num);
        $total = $this->model->table('wechat_media')
            ->where(array('wechat_id' => $this->wechat_id, 'type' => 'video'))->count();

        $list = $this->model->table('wechat_media')
            ->field('id, file, file_name, size')
            ->where(array('wechat_id' => $this->wechat_id, 'type' => 'video'))
            ->order('add_time desc, sort asc')
            ->limit($offset)
            ->select();
        if (empty($list)) {
            $list = array();
        }
        foreach ($list as $key => $val) {
            if ($val['size'] > (1024 * 1024)) {
                $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
            } else {
                $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
            }
        }

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 视频编辑
     */
    public function actionVideoEdit()
    {
        if (IS_POST) {
            $data = I('post.data');
            $id = I('post.id');

            if (empty($data['file']) || empty($data['file_name']) || empty($data['size'])) {
                $this->message(L('video_empty'), NULL, 2);
            }
            $size = round(($data['size'] / (1024 * 1024)), 1);
            if ($size > 5) {
                $this->message(L('file_size_limit'), NULL, 2);
            }
            if (empty($data['title'])) {
                $this->message(L('title') . L('empty'), NULL, 2);
            }
            $data['type'] = 'video';
            $data['wechat_id'] = $this->wechat_id;
            if (! empty($id)) {
                $data['edit_time'] = gmtime();
                $this->model->table('wechat_media')
                    ->data($data)
                    ->where(array('id'=>$id))
                    ->save();
            } else {
                $data['add_time'] = gmtime();
                $this->model->table('wechat_media')
                    ->data($data)
                    ->add();
            }
             $this->message(L('upload_video') . L('success'), url('video'));
        }
        $id = I('get.id');
        if (!empty($id)) {
            $video = $this->model->table('wechat_media')
                ->field('id, file, file_name, size, title, content')
                ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
                ->find();

            $this->assign('video', $video);
        }
        $this->display();
    }

    /**
     * 视频上传webuploader
     */
    public function actionVideoUpload()
    {
        if (IS_POST && !empty($_FILES['file']['name'])) {
            $vid = I('post.vid');
            if (!empty($vid)) {
                $file = $this->model->table('wechat_media')
                    ->where(array('id' => $vid, 'wechat_id' => $this->wechat_id))
                    ->getField('file');
                $this->remove($file);
            }
            $result = $this->upload('data/attached/video', true, 5);
            if ($result['error'] > 0) {
                $data['errcode'] = 1;
                $data['errmsg'] = $result['message'];
                echo json_encode($data);
                exit();
            }
            $data['errcode'] = 0;
            $data['file'] = $result['url'];
            $data['file_name'] = $_FILES['file']['name'];
            $data['size'] = $_FILES['file']['size'];
            echo json_encode($data);
            exit();
        }
    }

    /**
     * 素材编辑
     */
    public function actionMediaEdit()
    {
        if (IS_POST) {
            $id = I('post.id');
            $pic_name = I('post.file_name');
            $form = new Form();
            if(!$form->isEmpty($id, 1)){
                $this->message(L('empty'), NULL, 2);
            }
            if(!$form->isEmpty($pic_name, 1)){
                $this->message(L('empty'), NULL, 2);
            }
            $data['file_name'] = $pic_name;
            $data['edit_time'] = gmtime();
            $num = $this->model->table('wechat_media')
                ->data($data)
                ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
                ->save();

            exit(json_encode(array('status' => $num)));
        }
        $id = I('get.id');
        $pic = $this->model->table('wechat_media')
            ->field('id, file_name')
            ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
            ->find();
        if (empty($pic)) {
            redirect($_SERVER['HTTP_REFERER']);
        }
        $this->assign('pic', $pic);
        $this->display();
    }

    /**
     * 素材删除
     */
    public function actionMediaDel()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->message(L('empty'), NULL, 2);
        }
        $pic = $this->model->table('wechat_media')
            ->field('file, thumb')
            ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
            ->find();
        if (!empty($pic)) {
            $this->model->table('wechat_media')
                ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
                ->delete();
        }
        // 删除图片
        $this->remove($pic['file']);
        $this->remove($pic['thumb']);

        redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * 下载
     */
    public function actionDownload()
    {
        $id = I('get.id');
        $pic = $this->model->table('wechat_media')
            ->field('file, file_name')
            ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
            ->find();
        $filename = dirname(ROOT_PATH) . '/' . $pic['file'];
        if (file_exists($filename)) {
            Http::download($filename, $pic['file_name']);
        } else {
            $this->message(L('file_not_exist'), NULL, 2);
        }
    }

    /**
     * 群发消息列表
     */
    public function actionMassList()
    {
        // 群发消息权限
        $this->admin_priv('mass_message');

        // 分页
        $offset = $this->pageLimit(url('mass_list'), $this->page_num);
        $total = $this->model->table('wechat_mass_history')
            ->where(array('wechat_id' => $this->wechat_id))
            ->count();
        $this->assign('page', $this->pageShow($total));

        $list = $this->model->table('wechat_mass_history')
            ->field('id, media_id, type, status, send_time, totalcount, sentcount, filtercount, errorcount')
            ->where(array('wechat_id' => $this->wechat_id))
            ->order('send_time desc')
            ->limit($offset)
            ->select();
        foreach ((array)$list as $key => $val) {
            $media = $this->model->table('wechat_media')
                ->field('title, digest, content, file, article_id')
                ->where(array('id' => $val['media_id']))
                ->find();
            if (!empty($media['article_id'])) {
                // 多图文
                $artids = explode(',', $media['article_id']);
                $artinfo = $this->model->table('wechat_media')
                    ->field('title, digest, content, file')
                    ->where(array('id' => $artids[0]))
                    ->find();
            } else {
                $artinfo = $media;
            }
            if ('news' == $val['type']) {
                $artinfo['type'] = '图文消息';
            }
            $artinfo['file'] = get_wechat_image_path($artinfo['file']);
            $artinfo['content'] = empty($artinfo['digest']) ? sub_str(strip_tags(html_out($artinfo['content'])), 50) : $artinfo['digest'];
            $list[$key]['artinfo'] = $artinfo;
        }

        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 群发消息
     */
    public function actionMassMessage()
    {
        if (IS_POST) {
            $tag_id = I('post.tag_id', '', 'intval');
            $media_id = I('post.media_id');
            if ((empty($tag_id) && $tag_id !== 0) || empty($media_id)) {
                $this->message(L('please_select'), NULL, 2);
            }

            $article = array();
            $article_info = $this->model->table('wechat_media')
                ->field('id, title, author, file, is_show, digest, content, link, type, article_id')
                ->where(array('id' => $media_id, 'wechat_id' => $this->wechat_id))
                ->find();
            // 多图文
            if (!empty($article_info['article_id'])) {
                $articles = explode(',', $article_info['article_id']);
                foreach ($articles as $key => $val) {
                    $artinfo = $this->model->table('wechat_media')
                        ->field('title, author, file, is_show, digest, content, link')
                        ->where(array('id' => $val, 'wechat_id' => $this->wechat_id))
                        ->find();
                    //$artinfo['content'] = strip_tags(html_out($artinfo['content']));
                    // 上传多媒体文件
                    $filename = dirname(ROOT_PATH) . '/' . $artinfo['file'];
                    $rs = $this->weObj->uploadMedia(array('media' => '@' . $filename), 'image');
                    if (empty($rs)) {
                        $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, NULL, 2);
                    }
                    // 重组数据
                    $article[$key]['thumb_media_id'] = $rs['media_id'];
                    $article[$key]['author'] = $artinfo['author'];
                    $article[$key]['title'] = $artinfo['title'];
                    $article[$key]['content_source_url'] = $artinfo['link'];
                    $article[$key]['content'] = html_out($artinfo['content']);
                    $article[$key]['digest'] = $artinfo['digest'];
                    $article[$key]['show_cover_pic'] = $artinfo['is_show'];
                }
            } else {
                // 单图文
                // 上传多媒体文件
                $filename = dirname(ROOT_PATH) . '/' . $article_info['file'];
                $rs = $this->weObj->uploadMedia(array('media' => '@' . $filename), 'image');
                if (empty($rs)) {
                    $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, NULL, 2);
                }
                //$article_info['content'] = strip_tags(html_out($article_info['content']));
                // 重组数据
                $article[0]['thumb_media_id'] = $rs['media_id'];
                $article[0]['author'] = $article_info['author'];
                $article[0]['title'] = $article_info['title'];
                $article[0]['content_source_url'] = $article_info['link'];
                $article[0]['content'] = html_out($article_info['content']);
                $article[0]['digest'] = $article_info['digest'];
                $article[0]['show_cover_pic'] = $article_info['is_show'];
            }
            $article_list = array('articles' => $article);
            // 图文消息上传
            $rs1 = $this->weObj->uploadArticles($article_list);
            if (empty($rs1)) {
                $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, NULL, 2);
            }
            // $rs1 = array('type'=>'image', 'media_id'=>'joUuDBc-9-sJp1U6vZpWYKiaS5XskqxJxGMm5HBf9q9Zs7DoKlSXVKUR3JIsfW_7', 'created_at'=>'1407482934');

            /**
             * 根据标签组进行群发sendGroupMassMessage
             * 群发接口新增原创校验流程
             * 当 send_ignore_reprint 参数设置为1时，文章被判定为转载时，将继续进行群发操作。
             * 当 send_ignore_reprint 参数设置为0时，文章被判定为转载时，将停止群发操作。
             * send_ignore_reprint 默认为0。
             *
             */
            $massmsg = array(
                'filter' => array(
                    'is_to_all' => false,
                    'tag_id' => $tag_id
                ),
                'mpnews' => array(
                    'media_id' => $rs1['media_id']
                ),
                'msgtype' => 'mpnews',
                'send_ignore_reprint' => 0
            );
            $rs2 = $this->weObj->sendGroupMassMessage($massmsg);
            if (empty($rs2)) {
                $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, NULL, 2);
            }

            // 数据处理
            $msg_data['wechat_id'] = $this->wechat_id;
            $msg_data['media_id'] = $article_info['id'];
            $msg_data['type'] = $article_info['type'];
            $msg_data['send_time'] = gmtime();
            $msg_data['msg_id'] = $rs2['msg_id'];
            $id = $this->model->table('wechat_mass_history')
                ->data($msg_data)
                ->add();

            $this->message(L('mass_sending_wait'), url('mass_message'));
        }
        // 标签组信息
        $tags = $this->model->table('wechat_user_taglist')
            ->field('tag_id, name')
            ->where(array('wechat_id' => $this->wechat_id))
            ->order('tag_id')
            ->select();
        // 图文信息
        $article = $this->model->table('wechat_media')
            ->field('id, title, file, content, article_id, add_time')
            ->where(array('wechat_id' => $this->wechat_id, 'type' => 'news'))
            ->order('sort asc, add_time desc')
            ->select();

        foreach ((array)$article as $key => $val) {
            if (!empty($val['article_id'])) {
                $id = explode(',', $val['article_id']);
                foreach ($id as $k => $v) {
                    $article[$key]['articles'][] = $this->model->table('wechat_media')
                        ->field('id, title, file, add_time')
                        ->where(array('id' => $v))
                        ->find();
                    $article[$key]['articles'][$k]['file'] = get_wechat_image_path($article[$key]['articles'][$k]['file']);
                }
            }
            // 多图文的子图文不重复处理
            if(empty($val['article_id'])){
                $article[$key]['file'] = get_wechat_image_path($val['file']);
            }
            $article[$key]['content'] = sub_str(strip_tags(html_out($val['content'])), 100);
        }
        $this->assign('tags', $tags);
        $this->assign('article', $article);
        $this->display();
    }

    /**
     * 群发消息删除
     */
    public function actionMassDel()
    {
        $id = I('get.id');
        $msg_id = $this->model->table('wechat_mass_history')
            ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
            ->getField('msg_id');
        if (empty($msg_id)) {
            $this->message(L('massage_not_exist'), NULL, 2);
        }
        $rs = $this->weObj->deleteMassMessage($msg_id);
        if (empty($rs)) {
            $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, NULL, 2);
        }

        $data['status'] = 'send success(已删除)';
        $this->model->table('wechat_mass_history')
            ->data($data)
            ->where(array('id' => $id))
            ->save();
        $this->redirect('mass_list');
    }

    /**
     * ajax获取图文信息
     */
    public function actionGetArticle()
    {
        if (IS_AJAX) {
            $data = I('post.article');
            $article = array();
            if (is_array($data)) {
                $id = implode(',', $data);
                $article = $this->model->query("SELECT id, title, file, link, digest, content, add_time FROM {pre}wechat_media WHERE id in ($id) AND wechat_id = $this->wechat_id ORDER BY sort DESC, add_time DESC");
                foreach ($article as $key => $val) {
                    $article[$key]['file'] = get_wechat_image_path($val['file']);
                    $article[$key]['add_time'] = date('Y年m月d日', $val['add_time']);
                    $article[$key]['content'] = empty($val['digest']) ? sub_str(strip_tags(html_out($val['content'])), 50) : $val['digest'];
                }
            }
            echo json_encode($article);
        }
    }

    /**
     * 自动回复
     */
    public function actionAutoReply()
    {
        // 自动回复权限
        $this->admin_priv('auto_reply');

        // 素材数据
        $type = I('get.type');
        if (!empty($type)) {
            // 分页
            $filter['type'] = $type;
            $offset = $this->pageLimit(url('auto_reply', $filter), $this->page_num);
            if ('image' == $type) {
                $where = 'wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and type = "image"';
                $list = $this->model->query('SELECT id, file, file_name, size, add_time, type FROM {pre}wechat_media WHERE ' . $where . ' ORDER BY sort DESC, add_time DESC limit ' . $offset);
            } elseif ('voice' == $type) {
                $where = 'wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and type = "voice"';
                $list = $this->model->query('SELECT id, file, file_name, size, add_time, type FROM {pre}wechat_media WHERE ' . $where . ' ORDER BY sort DESC, add_time DESC limit ' . $offset);
            } elseif ('video' == $type) {
                $where = 'wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and type = "video"';
                $list = $this->model->query('SELECT id, file, file_name, size, add_time, type FROM {pre}wechat_media WHERE ' . $where . ' ORDER BY sort DESC, add_time DESC limit ' . $offset);
            } elseif ('news' == $type) {
                //$offset = $this->pageLimit(url('auto_reply', $filter), 6);
                // 只显示单图文
                $no_list = I('get.no_list', 0, 'intval');
                $this->assign('no_list', $no_list);
                if (!empty($no_list)) {
                    $where = 'wechat_id = ' . $this->wechat_id . ' and type="news" and article_id is NULL';
                } else {
                    $where = 'wechat_id = ' . $this->wechat_id . ' and type="news"';
                }
                $list = $this->model->query('SELECT id, title, file, file_name, size, digest, content, add_time, type, article_id FROM {pre}wechat_media WHERE ' . $where . ' ORDER BY sort DESC, add_time DESC limit ' . $offset);
                foreach ((array)$list as $key => $val) {
                    if (!empty($val['article_id'])) {
                        $id = explode(',', $val['article_id']);
                        foreach ($id as $k => $v) {
                            $list[$key]['articles'][] = $this->model->table('wechat_media')
                                ->field('id, title, digest, file, add_time')
                                ->where(array('id' => $v))
                                ->find();
                            $list[$key]['articles'][$k]['file'] = get_wechat_image_path($list[$key]['articles'][$k]['file']);
                        }
                    }
                    $list[$key]['content'] = empty($val['digest']) ? sub_str(strip_tags(html_out($val['content'])), 50) : $val['digest'];
                }
            }

            foreach ((array)$list as $key => $val) {
                if ($val['size'] > (1024 * 1024)) {
                    $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
                } else {
                    $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
                }
                // 多图文的子图文不重复处理
                if(empty($val['article_id'])){
                    $list[$key]['file'] = get_wechat_image_path($val['file']);
                }
            }
            $total = $this->model->query("SELECT count(*) as count FROM {pre}wechat_media WHERE $where ");
            foreach ($total as $key => $value) {
                $num = $value['count'];
            }
            $this->assign('page', $this->pageShow($num));
            $this->assign('list', $list);
            $this->assign('type', $type);
            $this->display();
        }
    }


    /**
     * 关注回复(subscribe)
     */
    public function actionReplySubscribe()
    {
        // 自动回复权限
        $this->admin_priv('auto_reply');

        if (IS_POST) {
            $content_type = I('post.content_type');
            if ($content_type == 'text') {
                $data['content'] = I('post.content', '', 'new_html_in');
                $data['media_id'] = 0;
            } else {
                $data['media_id'] = I('post.media_id');
                $data['content'] = '';
            }
            $data['type'] = 'subscribe';
            if (is_array($data) && (!empty($data['media_id']) || !empty($data['content']))) {
                $where['type'] = $data['type'];
                $where['wechat_id'] = $this->wechat_id;
                $id = $this->model->table('wechat_reply')
                    ->where($where)
                    ->getField('id');
                if (!empty($id)) {
                    $this->model->table('wechat_reply')
                        ->data($data)
                        ->where($where)
                        ->save();
                } else {
                    $data['wechat_id'] = $this->wechat_id;
                    $this->model->table('wechat_reply')
                        ->data($data)
                        ->add();
                }
                $this->message(L('wechat_editor') . L('success'), url('reply_subscribe'));
            } else {
                $this->message(L('empty'), NULL, 2);
            }
        }
        // 自动回复数据
        $subscribe = $this->model->table('wechat_reply')
            ->where(array('type' => 'subscribe', 'wechat_id' => $this->wechat_id))
            ->find();
        if (!empty($subscribe['media_id'])) {
            $subscribe['media'] = $this->model->table('wechat_media')
                ->field('file, type, file_name')
                ->where(array('id' => $subscribe['media_id'], 'wechat_id' => $this->wechat_id))
                ->find();
            $subscribe['media']['file'] = get_wechat_image_path($subscribe['media']['file']);
        }
        $this->assign('subscribe', $subscribe);
        $this->display();
    }

    /**
     * 消息回复(msg)
     */
    public function actionReplyMsg()
    {
        // 自动回复权限
        $this->admin_priv('auto_reply');

        if (IS_POST) {
            $content_type = I('post.content_type');
            if ($content_type == 'text') {
                $data['content'] = I('post.content', '', 'new_html_in');
                $data['media_id'] = 0;
            } else {
                $data['media_id'] = I('post.media_id');
                $data['content'] = '';
            }
            $data['type'] = 'msg';
            if (is_array($data)) {
                $where['type'] = $data['type'];
                $where['wechat_id'] = $this->wechat_id;
                $id = $this->model->table('wechat_reply')
                    ->where($where)
                    ->getField('id');
                if (!empty($id)) {
                    $this->model->table('wechat_reply')
                        ->data($data)
                        ->where($where)
                        ->save();
                } else {
                    $data['wechat_id'] = $this->wechat_id;
                    $this->model->table('wechat_reply')
                        ->data($data)
                        ->add();
                }
                $this->message(L('wechat_editor') . L('success'), url('reply_msg'));
            } else {
                $this->message(L('empty'), NULL, 2);
            }
        }
        // 自动回复数据
        $msg = $this->model->table('wechat_reply')
            ->where(array('type' => 'msg', 'wechat_id' => $this->wechat_id))
            ->find();
        if (!empty($msg['media_id'])) {
            $msg['media'] = $this->model->table('wechat_media')
                ->field('file, type, file_name')
                ->where(array('id' => $msg['media_id']))
                ->find();
            $msg['media']['file'] = get_wechat_image_path($msg['media']['file']);
        }
        $this->assign('msg', $msg);
        $this->display();
    }

    /**
     * 关键词自动回复
     */
    public function actionReplyKeywords()
    {
        // 自动回复权限
        $this->admin_priv('auto_reply');

        $list = $this->model->table('wechat_reply')
            ->field('id, rule_name, content, media_id, reply_type')
            ->where(array('type' => 'keywords', 'wechat_id' => $this->wechat_id))
            ->order('add_time desc')
            ->select();
        foreach ((array)$list as $key => $val) {
            // 内容不是文本
            if (!empty($val['media_id'])) {
                $media = $this->model->table('wechat_media')
                    ->field('title, file, file_name, type, digest, content, add_time, article_id')
                    ->where(array('id' => $val['media_id'], 'wechat_id' => $this->wechat_id))
                    ->find();
                $media['file'] = get_wechat_image_path($media['file']);
                $media['content'] = empty($media['digest']) ? sub_str(strip_tags(html_out($media['content'])), 50) : $media['digest'];
                if (!empty($media['article_id'])) {
                    $artids = explode(',', $media['article_id']);
                    foreach ($artids as $k => $v) {
                        $list[$key]['medias'][] = $this->model->table('wechat_media')
                            ->field('title, file, add_time')
                            ->where(array('id' => $v, 'wechat_id' => $this->wechat_id))
                            ->find();
                        $list[$key]['medias'][$k]['file'] = get_wechat_image_path($list[$key]['medias'][$k]['file']);
                    }
                } else {
                    $list[$key]['media'] = $media;
                }
            }
            $keywords = $this->model->table('wechat_rule_keywords')
                ->field('rule_keywords')
                ->where(array('rid' => $val['id'], 'wechat_id' => $this->wechat_id))
                ->order('id desc')
                ->select();
            $list[$key]['rule_keywords'] = $keywords;
            // 编辑关键词时显示
            if (!empty($keywords)) {
                $rule_keywords = array();
                foreach ($keywords as $k => $v) {
                    $rule_keywords[] = $v['rule_keywords'];
                }
                $rule_keywords = implode(',', $rule_keywords);
                $list[$key]['rule_keywords_string'] = $rule_keywords;
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 关键词回复添加规则
     */
    public function actionRuleEdit()
    {
        if (IS_POST) {
            $id = I('post.id');
            $content_type = I('post.content_type');
            $rule_keywords = I('post.rule_keywords');
            // 主表数据
            $data['rule_name'] = I('post.rule_name','','trim');
            $data['media_id'] = I('post.media_id',0,'intval');
            $data['content'] = I('post.content', '', 'new_html_in');
            $data['reply_type'] = $content_type;
            if ($content_type == 'text') {
                $data['media_id'] = 0;
            } else {
                $data['content'] = '';
            }

            $form = new Form();
            if(!$form->isEmpty($data['rule_name'], 1)){
                $this->message(L('rule_name_empty'), NULL, 2);
            }
            if(!$form->isEmpty($rule_keywords, 1)){
                $this->message(L('rule_keywords_empty'), NULL, 2);
            }
            if (empty($data['content']) && empty($data['media_id'])) {
                $this->message(L('rule_content_empty'), NULL, 2);
            }
            if(strlen($data['rule_name']) > 60){
                $this->message(L('rule_name_length_limit'), NULL, 2);
            }
            $data['type'] = 'keywords';
            if (!empty($id)) {
                $this->model->table('wechat_reply')
                    ->data($data)
                    ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
                    ->save();
                $this->model->table('wechat_rule_keywords')
                    ->where(array('rid' => $id, 'wechat_id' => $this->wechat_id))
                    ->delete();
            } else {
                $data['add_time'] = gmtime();
                $data['wechat_id'] = $this->wechat_id;
                $id = $this->model->table('wechat_reply')
                    ->data($data)
                    ->add();
            }
            // 编辑关键词
            $rule_keywords = explode(',', $rule_keywords);
            foreach ($rule_keywords as $val) {
                $kdata['rid'] = $id;
                $kdata['rule_keywords'] = $val;
                $this->model->table('wechat_rule_keywords')
                    ->data($kdata)
                    ->add();
            }
            $this->message(L('wechat_editor') . L('success'), url('reply_keywords'));
        }
    }

    /**
     * 关键词回复规则删除
     */
    public function actionReplyDel()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->message(L('empty'), NULL, 2);
        }
        $this->model->table('wechat_reply')
            ->where(array('id' => $id, 'wechat_id' => $this->wechat_id))
            ->delete();
        $this->redirect('reply_keywords');
    }

    /**
     * 素材管理
     */
    public function actionMediaList()
    {
        $this->display();
    }

    /**
     * 提醒设置
     */
    public function actionRemind()
    {
        if (IS_POST) {
            $command = I('post.command');
            $data = I('post.data');
            $config = I('post.config');
            $info = Check::rule(array(
                Check::must($command),
                '关键词不正确'
            ));
            if ($info !== true) {
                $this->message($info, NULL, 2);
            }
            if (!empty($config)) {
                $data['config'] = serialize($config);
            }
            $data['wechat_id'] = $this->wechat_id;
            $num = $this->model->table('wechat_extend')
                ->where('command = "' . $command . '" and wechat_id = ' . $this->wechat_id)
                ->count();
            if ($num > 0) {
                $this->model->table('wechat_extend')
                    ->data($data)
                    ->where('command = "' . $command . '" and wechat_id = ' . $this->wechat_id)
                    ->save();
            } else {
                $data['command'] = $command;
                $this->model->table('wechat_extend')
                    ->data($data)
                    ->add();
            }

            redirect($_SERVER['HTTP_REFERER']);
        }

        $order_remind = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where('command = "order_remind" and wechat_id = ' . $this->wechat_id)
            ->find();
        if ($order_remind['config']) {
            $order_remind['config'] = unserialize($order_remind['config']);
        }
        $pay_remind = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where('command = "pay_remind" and wechat_id = ' . $this->wechat_id)
            ->find();
        if ($pay_remind['config']) {
            $pay_remind['config'] = unserialize($pay_remind['config']);
        }
        $send_remind = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where('command = "send_remind" and wechat_id = ' . $this->wechat_id)
            ->find();
        if ($send_remind['config']) {
            $send_remind['config'] = unserialize($send_remind['config']);
        }
        $register_remind = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where('command = "register_remind" and wechat_id = ' . $this->wechat_id)
            ->find();
        if ($register_remind['config']) {
            $register_remind['config'] = unserialize($register_remind['config']);
        }
        $this->assign('order_remind', $order_remind);
        $this->assign('pay_remind', $pay_remind);
        $this->assign('send_remind', $send_remind);
        $this->assign('register_remind', $register_remind);
        $this->display();
    }

    /**
     * 多客服设置
     */
    public function actionCustomerService()
    {
        $command = 'kefu';
        if (IS_POST) {
            $data = I('post.data');
            $config = I('post.config');

            if (!empty($config)) {
                $data['config'] = serialize($config);
            }
            $num = $this->model->table('wechat_extend')
                ->where(array('command' => $command, 'wechat_id' => $this->wechat_id))
                ->count();
            if ($num > 0) {
                $this->model->table('wechat_extend')
                    ->data($data)
                    ->where(array('command' => $command, 'wechat_id' => $this->wechat_id))
                    ->save();
            } else {
                $data['wechat_id'] = $this->wechat_id;
                $data['command'] = $command;
                $data['name'] = '多客服';
                $this->model->table('wechat_extend')
                    ->data($data)
                    ->add();
            }

            redirect($_SERVER['HTTP_REFERER']);
        }

        $customer_service = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where(array('command' => $command, 'wechat_id' => $this->wechat_id))
            ->find();
        if ($customer_service['config']) {
            $customer_service['config'] = unserialize($customer_service['config']);
        }
        $this->assign('customer_service', $customer_service);
        $this->display();
    }

    /**
     * 添加多客服
     */
    public function actionAddKf()
    {
        $account = 'test@gh_1ca465561479';
        $nickname = 'test';
        $password = '123123';
        $rs = $this->weObj->addKFAccount($account, $nickname, $password);
        echo $this->weObj->errMsg;
        dump($rs);


    }

    /**
     * 模板消息
     */
    public function actionTemplate()
    {
        // 模板消息权限
        $this->admin_priv('template');
        $condition['wechat_id'] = $this->wechat_id;
        $list = $this->model->table('wechat_template')->where($condition)->order('id asc')->select();
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['add_time'] = local_date('Y-m-d H:i', $val['add_time']);
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 编辑模板消息
     */
    public function actionEditTemplate()
    {
        if (IS_AJAX) {
            $id = I('post.id');
            $data = I('post.data', '', 'trim');
            if ($id) {
                $condition['id'] = $id;
                $condition['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_template')->data($data)->where($condition)->save();
                exit(json_encode(array('status' => 1)));
            } else {
                exit(json_encode(array('status' => 0, 'msg' => L('template_edit_fail'))));
            }
        }
        $id = I('get.id');
        if ($id) {
            $condition['id'] = $id;
            $condition['wechat_id'] = $this->wechat_id;
            $template = $this->model->table('wechat_template')->where($condition)->find();
            $this->assign('template', $template);
        }

        $this->display();
    }

    /**
     * 开关按钮
     */
    public function actionSwitch()
    {
        $id = I('get.id',0, 'intval');
        $status = I('get.status',0, 'intval');
        if(empty($id)){
            $this->message(L('empty'), NULL, 2);
        }
        $condition['id'] = $id;
        $condition['wechat_id'] = $this->wechat_id;

        // 启用模板消息
        if ($status == 1) {
            // 模板ID为空
            $template = $this->model->table('wechat_template')->field('template_id, code')->where($condition)->find();
            if (empty($template['template_id'])) {
                $template_id = $this->weObj->addTemplateMessage($template['code']);
                // 已经存在模板ID
                if($template_id){
                    $this->model->table('wechat_template')->data(array('template_id' => $template_id))->where($condition)->save();
                }else{
                    $this->message($this->weObj->errMsg, NULL, 2);
                }
            }
            // 重新启用 更新状态status
            $this->model->table('wechat_template')->data(array('status' => 1))->where($condition)->save();
        }else{
            // 禁用 更新状态status
            $this->model->table('wechat_template')->data(array('status' => 0))->where($condition)->save();
        }
        $this->redirect('template');
    }

    /**
     * 获取配置信息
     */
    private function get_config()
    {
        $without = array(
            'index',
            'append',
            'modify',
            'delete',
            'set_default'
        );

        if (!in_array(strtolower(ACTION_NAME), $without)) {
            // 公众号配置信息
            $where['id'] = $this->wechat_id;
            $wechat = $this->model->table('wechat')->field('token, appid, appsecret, type, status')->where($where)->find();
            if (empty($wechat)) {
                $wechat = array();
            }
            if (empty($wechat['status'])) {
                $this->message(L('open_wechat'), url('modify'), 2);
                exit;
            }
            $config = array();
            $config['token'] = $wechat['token'];
            $config['appid'] = $wechat['appid'];
            $config['appsecret'] = $wechat['appsecret'];

            $this->weObj = new Wechat($config);
            $this->assign('type', $wechat['type']);
        }
    }
}
