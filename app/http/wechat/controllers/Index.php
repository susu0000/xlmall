<?php
namespace app\http\wechat\controllers;

use app\http\base\controllers\Frontend;
use ectouch\Wechat;

class Index extends Frontend
{
    private $weObj = '';
    private $secret_key = '';
    private $wechat_id = 0;
    private $ru_id = 0;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        // 获取公众号配置
        $this->secret_key = I('get.key', '', 'trim');
        if ($this->secret_key) {
            $this->load_helper('passport');
            $wxinfo = $this->get_config($this->secret_key);
            $this->wechat_id = $wxinfo['id'];
            $this->ru_id = $wxinfo['ru_id'];
            if($this->ru_id) session('ru_id', $this->ru_id);
            $config['token'] = $wxinfo['token'];
            $config['appid'] = $wxinfo['appid'];
            $config['appsecret'] = $wxinfo['appsecret'];
            $config['encodingaeskey'] = $wxinfo['encodingaeskey'];
            // $config['debug'] = APP_DEBUG;
            $this->weObj = new Wechat($config);
            $this->weObj->valid();
        }
    }

    /**
     * 执行方法
     */
    public function actionIndex()
    {
        // 事件类型
        $type = $this->weObj->getRev()->getRevType();
        $wedata = $this->weObj->getRev()->getRevData();
        $keywords = '';
        // 微信消息日志队列之存入数据库
        if($wedata['Event'] != 'unsubscribe' && $wedata['Event'] != 'LOCATION' && $wedata['Event'] != 'VIEW'){
           $this->message_log_alignment_add($wedata);
        }
        // 接收消息
        switch($type) {
            // 文本消息
            case Wechat::MSGTYPE_TEXT:
                $keywords = $wedata['Content'];
                break;
            // 事件推送
            case Wechat::MSGTYPE_EVENT:
                // 关注事件
                if ($wedata['Event'] == Wechat::EVENT_SUBSCRIBE) {
                    $scene_id = 0;
                    $flag = false;
                    // 用户扫描带参数二维码(未关注)
                    if (isset($wedata['Ticket']) && !empty($wedata['Ticket'])) {
                        $scene_id = $this->weObj->getRevSceneId();
                        $flag = true;
                        // 关注
                        $this->subscribe($wedata['FromUserName'], $scene_id);
                    } else {
                        // 关注
                        $this->subscribe($wedata['FromUserName']);
                    }
                    // 关注自动回复信息
                    $this->msg_reply('subscribe');
                } elseif ($wedata['Event'] == Wechat::EVENT_UNSUBSCRIBE) {
                    // 取消关注事件
                    $this->unsubscribe($wedata['FromUserName']);
                    exit();
                } elseif ($wedata['Event'] == Wechat::EVENT_SCAN) {
                    // 扫描带参数二维码(用户已关注)
                    $scene_id = $this->weObj->getRevSceneId();
                } elseif ($wedata['Event'] == Wechat::EVENT_MENU_CLICK) {
                    // 自定义菜单事件 点击菜单拉取消息
                    $keywords = $wedata['EventKey'];
                } elseif ($wedata['Event'] == Wechat::EVENT_MENU_VIEW) {
                    // 自定义菜单事件 点击菜单跳转链接
                    redirect($wedata['EventKey']);
                } elseif ($wedata['Event'] == Wechat::EVENT_LOCATION) {
                    // 上报地理位置事件
                    exit();
                } elseif ($wedata['Event'] == 'kf_create_session') {
                    // 多客服接入
                } elseif ($wedata['Event'] == 'kf_close_session') {
                    // 多客服关闭
                } elseif ($wedata['Event'] == 'kf_switch_session') {
                    // 多客服转接
                } elseif ($wedata['Event'] == 'MASSSENDJOBFINISH') {
                    // 更新群发消息结果
                    $data['status'] = $wedata['Status'];
                    $data['totalcount'] = $wedata['TotalCount'];
                    $data['filtercount'] = $wedata['FilterCount'];
                    $data['sentcount'] = $wedata['SentCount'];
                    $data['errorcount'] = $wedata['ErrorCount'];
                    // 更新群发结果
                    dao('wechat_mass_history')->data($data)->where(array('msg_id' => $wedata['MsgID'], 'wechat_id' => $this->wechat_id))->save();
                    exit();
                }
                break;
            // 图片消息
            case Wechat::MSGTYPE_IMAGE:
                exit();
                break;
            // 语音消息
            case Wechat::MSGTYPE_VOICE:
                exit();
                break;
            // 视频消息
            case Wechat::MSGTYPE_VIDEO:
                exit();
                break;
            // 小视频消息
            case Wechat::MSGTYPE_SHORTVIDEO:
                exit();
                break;
            // 地理位置消息
            case Wechat::MSGTYPE_LOCATION:
                exit();
                break;
            // 链接消息
            case Wechat::MSGTYPE_LINK:
                exit();
                break;
            default:
                $this->msg_reply('msg'); // 消息自动回复
                exit();
        }

        // 扫描二维码
        if (!empty($scene_id)) {
            $keywords = $this->do_qrcode_subscribe($scene_id, $flag);
        }

        // 回复消息
        // 查询发送状态
        if($wedata['MsgType'] == 'event'){
            $where = array(
                'fromusername' => $wedata['FromUserName'],
                'createtime' => $wedata['CreateTime'],
                'is_send' => 0
            );
        }else{
            $where = array(
                'msgid' => $wedata['MsgId'],
                'is_send' => 0
            );
        }

        $contents = dao('wechat_message_log')->field('fromusername, createtime, keywords, msgid')->where($where)->find();
        if (!empty($contents)) {
            $keyword = html_in($contents['keywords']);
            $fromusername = $contents['fromusername'];
            // 多客服
            $rs = $this->customer_service($fromusername, $keyword);
            if (empty($rs)) {
                // 功能插件
                $rs1 = $this->get_function($fromusername, $keyword);
                // 微信营销
                $rs3 = $this->get_marketing($fromusername, $keyword);
                if (empty($rs1) || empty($rs3)) {
                    // 关键词回复
                    $rs2 = $this->keywords_reply($keyword);
                    if (empty($rs2)) {
                        // 消息自动回复
                        $this->msg_reply('msg');
                    }
                }
            }
            // 记录用户操作信息
            $this->record_msg($fromusername, $keyword);
            // 微信消息日志队列之处理发送状态
            $this->message_log_alignment_send($fromusername, $keyword, $contents);
        }

    }

    /**
     * 关注处理
     *
     * @param array $info
     */
    private function subscribe($openid = '', $scene_id = 0)
    {
        if (!empty($openid)) {
            // 获取微信用户信息
            $info = $this->weObj->getUserInfo($openid);
            if (empty($info)) {
                $this->weObj->resetAuth();
                exit('null');
            }

            // 组合数据
            $data['wechat_id'] = $this->wechat_id;
            $data['subscribe'] = $info['subscribe'];
            $data['openid'] = $info['openid'];
            $data['nickname'] = $info['nickname'];
            $data['sex'] = $info['sex'];
            $data['language'] = $info['language'];
            $data['city'] = $info['city'];
            $data['province'] = $info['province'];
            $data['country'] = $info['country'];
            $data['headimgurl'] = $info['headimgurl'];
            $data['subscribe_time'] = $info['subscribe_time'];
            $data['remark'] = $info['remark'];
            $data['groupid'] = isset($info['groupid']) ? $info['groupid'] : $this->weObj->getUserGroup($openid);
            $data['unionid'] = isset($info['unionid']) ? $info['unionid'] : '';

            // 公众号启用微信开发者平台，检查unionid
            $identify = !empty($data['unionid']) ? $data['unionid'] : $data['openid'];
            // 已关注用户基本信息
            $condition = array('openid' => $data['openid']);
            $result = dao('wechat_user')->field('ect_uid, unionid')->where($condition)->find();
            // 查找用户是否存在
            if (isset($result['ect_uid'])) {
                $users = dao('users')->where(array('user_id' => $result['ect_uid']))->find();
                if (empty($users) || empty($result['ect_uid'])) {
                    dao('wechat_user')->where($condition)->delete();
                    $result = array();
                    unset($_SESSION['user_id']);
                }
            }
            // 未关注
            if (empty($result)) {
                // 其他平台(PC,APP)是否注册
                // 1.兼容老用户授权登录
                $pc_condition = array('aite_id' => 'wechat_' . $identify);
                $userinfo = dao('users')->field('user_id, parent_id')->where($pc_condition)->find();
                // 2.兼容原 users_auth 表
                $condition2 = array('identifier' => 'wechat_' . $identify);
                $userAuthInfo = dao('users_auth')->field('user_name, user_id')->where($condition2)->find();
                if(!empty($userinfo)){
                    // 清空原始表aite_id
                    $user_data = array('aite_id' => '');
                    dao('users')->data($user_data)->where($pc_condition)->save();
                    // 同步社会化登录用户信息表
                    $res = array(
                        'user_id' => $userinfo['user_id'],
                        'openid' => $identify
                        );
                    $this->update_connnect_user($res, 'wechat');
                }elseif (!empty($userAuthInfo)){
                    // 同步社会化登录用户信息表
                    $res = array(
                        'user_id' => $userAuthInfo['user_id'],
                        'openid' => $identify
                        );
                    $this->update_connnect_user($res, 'wechat');
                    $userinfo = $userAuthInfo;
                }
                // 商家不走注册
                if($this->ru_id == 0){
                    if(empty($userinfo)){
                        // 设置的用户注册信息
                        $username = substr(md5($identify), -2) . time() . rand(100, 999);
                        $password = mt_rand(100000, 999999);
                        $email = $username . '@qq.com';
                        // 查询推荐人ID
                        if (!empty($scene_id)) {
                            $scene_user_id = dao('users')->where(array('user_id' => $scene_id))->getField('user_id');
                        }
                        $scene_user_id = empty($scene_user_id) ? 0 : $scene_user_id;
                        // 用户注册
                        $extend = array(
                            'parent_id' => $scene_user_id,
                            'nick_name' => $data['nickname'],
                            'sex' => $data['sex'],
                            'user_picture' => $data['headimgurl']
                        );
                        if (register($username, $password, $email, $extend) !== false) {
                            // 同步社会化登录用户信息表
                            $res = array(
                                'user_id' => $_SESSION['user_id'],
                                'openid' => $identify
                                );
                            $this->update_connnect_user($res, 'wechat');
                        }else{
                            exit('null');
                        }
                        // 注册微信资料
                        $data['ect_uid'] = $_SESSION['user_id'];
                        $data['parent_id'] = $scene_user_id;
                    }else{
                        // 更新微信资料
                        $data['ect_uid'] = $userinfo['user_id'];
                        $data['parent_id'] = $userinfo['parent_id'];
                    }
                }

                // 新增微信粉丝
                dao('wechat_user')->data($data)->add();
                if($this->ru_id == 0){
                    // 新用户送红包
                    $data1['user_id'] = $_SESSION['user_id'];
                    $bonus_num = dao('user_bonus')->where($data1)->count();
                    if ($bonus_num <= 0) {
                        $content = $this->send_message($openid, 'bonus', $this->weObj, 1);
                        $bonus_msg = empty($content) ? '' : $content['content'];
                        if (!empty($bonus_msg)) {
                            // 微信端发送消息
                            $msg = array(
                                'touser' => $openid,
                                'msgtype' => 'text',
                                'text' => array(
                                    'content' => $bonus_msg
                                )
                            );
                            $this->weObj->sendCustomMessage($msg);
                        }
                    }
                }
            } else {
                $template = $data['nickname'] .  '，欢迎您再次回来';
                // 微信端发送消息
                $this->send_custom_message($openid, 'text', $template);
                // 更新微信用户资料
                $condition = array('openid' => $data['openid'], 'wechat_id' => $this->wechat_id);
                dao('wechat_user')->data($data)->where($condition)->save();

                if($result['ect_uid'] > 0){
                    // 同步社会化登录用户信息表
                    $res = array(
                        'user_id' => $result['ect_uid'],
                        'openid' => $identify
                        );
                    $this->update_connnect_user($res, 'wechat');
                }
            }
        }
    }

    /**
     * 取消关注
     *
     * @param string $openid
     */
    public function unsubscribe($openid = '')
    {
        // 未关注
        $where['openid'] = $openid;
        $where['wechat_id'] = $this->wechat_id;
        $rs = dao('wechat_user')->where($where)->count();
        // 修改关注状态
        if ($rs > 0) {
            $data['subscribe'] = 0;
            dao('wechat_user')->data($data)->where($where)->save();
        }
    }

    // 关注二维码处理
    private function do_qrcode_subscribe($scene_id, $flag = false)
    {
        $qrcode_fun = dao('wechat_qrcode')->where(array('scene_id' => $scene_id, 'wechat_id' => $this->wechat_id))->getField('function');
        //扫码引荐
        if ($flag == true) {
            //增加扫描量
            $this->db->query("UPDATE {pre}wechat_qrcode SET scan_num = scan_num + 1 WHERE scene_id = $scene_id and wechat_id = $this->wechat_id");
        }
        return $qrcode_fun;
    }

    /**
     * 被动关注，消息回复
     *
     * @param string $type
     * @param string $return
     */
    private function msg_reply($type, $return = 0)
    {
        $replyInfo = $this->db->table('wechat_reply')
            ->field('content, media_id')
            ->where(array('type' => $type, 'wechat_id' => $this->wechat_id))
            ->find();
        if (!empty($replyInfo)) {
            if (!empty($replyInfo['media_id'])) {
                $replyInfo['media'] = $this->db->table('wechat_media')
                    ->field('title, content, file, type, file_name')
                    ->where(array('id' => $replyInfo['media_id']))
                    ->find();
                if ($replyInfo['media']['type'] == 'news') {
                    $replyInfo['media']['type'] = 'image';
                }
                // 上传多媒体文件
                $rs = $this->weObj->uploadMedia(array('media' => '@' . dirname(ROOT_PATH) . '/' . $replyInfo['media']['file']), $replyInfo['media']['type']);
                // 回复数据重组
                if ($rs['type'] == 'image' || $rs['type'] == 'voice') {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array('MediaId' => $rs['media_id'])
                    );
                } elseif ('video' == $rs['type']) {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            'Title' => $replyInfo['media']['title'],
                            'Description' => strip_tags($replyInfo['media']['content'])
                        )
                    );
                }
                if ($return) {
                    return array('type' => 'media', 'content' => $replyData);
                }
                $this->weObj->reply($replyData);
                //记录用户操作信息
                $this->record_msg($this->weObj->getRev()->getRevTo(), '图文信息', 1);
            } else {
                // 文本回复
                $replyInfo['content'] = html_out($replyInfo['content']);
                if ($return) {
                    return array('type' => 'text', 'content' => $replyInfo['content']);
                }
                $this->weObj->text($replyInfo['content'])->reply();
                //记录用户操作信息
                $this->record_msg($this->weObj->getRev()->getRevTo(), $replyInfo['content'], 1);
            }
        }
    }

    /**
     * 关键词回复
     *
     * @param string $keywords
     * @return boolean
     */
    private function keywords_reply($keywords)
    {
        $endrs = false;
        $sql = 'SELECT r.content, r.media_id, r.reply_type FROM {pre}wechat_reply r LEFT JOIN {pre}wechat_rule_keywords k ON r.id = k.rid WHERE k.rule_keywords = "' . $keywords . '" and r.wechat_id = ' . $this->wechat_id . ' order by r.add_time desc LIMIT 1';
        $result = $this->db->query($sql);
        if (!empty($result)) {
            // 素材回复
            if (!empty($result[0]['media_id'])) {
                $mediaInfo = $this->db->table('wechat_media')
                    ->field('id, title, digest, content, file, type, file_name, article_id, link')
                    ->where(array('id' => $result[0]['media_id']))
                    ->find();
                // 回复数据重组
                if ($result[0]['reply_type'] == 'image' || $result[0]['reply_type'] == 'voice') {
                    // 上传多媒体文件
                    $rs = $this->weObj->uploadMedia(array('media' => '@' . dirname(ROOT_PATH) . '/' . $mediaInfo['file']), $result[0]['reply_type']);
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array('MediaId' => $rs['media_id'])
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('video' == $result[0]['reply_type']) {
                    // 上传多媒体文件
                    $rs = $this->weObj->uploadMedia(array('media' => '@' . dirname(ROOT_PATH) . '/' . $mediaInfo['file']), $result[0]['reply_type']);
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            'Title' => $replyInfo['media']['title'],
                            'Description' => strip_tags($replyInfo['media']['content'])
                        )
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('news' == $result[0]['reply_type']) {
                    // 图文素材
                    $articles = array();
                    if (!empty($mediaInfo['article_id'])) {
                        $artids = explode(',', $mediaInfo['article_id']);
                        foreach ($artids as $key => $val) {
                            $artinfo = $this->db->table('wechat_media')
                                ->field('id, title, file, digest, content, link')
                                ->where(array('id' => $val))
                                ->find();
                            $artinfo['content'] = sub_str(strip_tags(html_out($artinfo['content'])), 100);
                            $articles[$key]['Title'] = $artinfo['title'];
                            $articles[$key]['Description'] = empty($artinfo['digest']) ? $artinfo['content'] : $artinfo['digest'];
                            $articles[$key]['PicUrl'] = get_wechat_image_path($artinfo['file']);
                            $articles[$key]['Url'] = empty($artinfo['link']) ? __HOST__ . url('article/index/wechat', array('id' => $artinfo['id'])) : strip_tags(html_out($artinfo['link']));
                        }
                    } else {
                        $articles[0]['Title'] = $mediaInfo['title'];
                        $articles[0]['Description'] = empty($mediaInfo['digest']) ? sub_str(strip_tags(html_out($mediaInfo['content'])), 100) : $mediaInfo['digest'];
                        $articles[0]['PicUrl'] = get_wechat_image_path($mediaInfo['file']);
                        $articles[0]['Url'] = empty($mediaInfo['link']) ? __HOST__ . url('article/index/wechat', array('id' => $mediaInfo['id'])) : strip_tags(html_out($mediaInfo['link']));
                    }
                    // 回复
                    $this->weObj->news($articles)->reply();
                    //记录用户操作信息
                    $this->record_msg($this->weObj->getRev()->getRevTo(), '图文信息', 1);
                    $endrs = true;
                }
            } else {
                // 文本回复
                $result[0]['content'] = html_out($result[0]['content']);
                $this->weObj->text($result[0]['content'])->reply();
                //记录用户操作信息
                $this->record_msg($this->weObj->getRev()->getRevTo(), $result[0]['content'], 1);
                $endrs = true;
            }
        }
        return $endrs;
    }

    /**
     * 功能变量查询
     *
     * @param unknown $tousername
     * @param unknown $fromusername
     * @param unknown $keywords
     * @return boolean
     */
    public function get_function($fromusername, $keywords)
    {
        $return = false;
        $rs = $this->db->query("SELECT name, keywords, command, config FROM {pre}wechat_extend WHERE keywords like '%$keywords%' and enable = 1 and wechat_id = $this->wechat_id ORDER BY id ASC LIMIT 6");
        if (empty($rs)) {
            $rs = $this->db->query("SELECT name, keywords, command, config FROM {pre}wechat_extend WHERE command = 'search' and enable = 1 and wechat_id = $this->wechat_id ORDER BY id ASC LIMIT 6");
        }
        $info = reset($rs);
        $info['user_keywords'] = $keywords;
        /*if($rs){
            $key = explode(',', $rs['keywords']);
            if(!in_array($keywords, $key)){
                return $return;
            }
        }*/
        $plugin_type = $this->ru_id > 0 ? 'wechatseller' : 'wechat';
        $file = ADDONS_PATH . $plugin_type . '/' . $info['command'] . '/' . ucfirst($info['command']) . '.php';
        if (file_exists($file)) {
            require_once($file);
            $new_command = '\\app\\modules\\'. $plugin_type . '\\' . $info['command'] . '\\' . ucfirst($info['command']);
            $cfg = array('ru_id' => $this->ru_id);
            $wechat = new $new_command($cfg);
            $data = $wechat->returnData($fromusername, $info);
            if (!empty($data)) {
                // 数据回复类型
                if ($data['type'] == 'text') {
                    $this->weObj->text($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, $data['content'], 1);
                } elseif ($data['type'] == 'news') {
                    $this->weObj->news($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, '图文消息', 1);
                } elseif($data['type'] == 'image'){
                    // 上传多媒体文件
                    $rs = $this->weObj->uploadMedia(array('media' => '@' . $data['path']), 'image');
                    $this->weObj->image($rs['media_id'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, '图片', 1);
                }
                $return = true;
            }
        }
        return $return;
    }

    /**
     * 微信营销功能查询
     *
     * @param unknown $tousername
     * @param unknown $fromusername
     * @param unknown $keywords
     * @return boolean
     */
    public function get_marketing($fromusername, $keywords)
    {
        $return = false;
        $sql = "SELECT id, name, keywords, command, background, description, status FROM {pre}wechat_marketing WHERE (keywords like '%$keywords%' OR command = '" . $keywords . "') AND wechat_id = '".$this->wechat_id ."' ORDER BY id DESC ";
        $rs = $this->db->query($sql);

        $rs = reset($rs);
        if($rs){
            $match_kewords = explode(',', $rs['keywords']);
            if(!in_array($keywords, $match_kewords) && $rs['command'] != $keywords){
                return $return;
            }
            $where = array(
                'id' => $rs['id'],
                'command' => $rs['command'],
                'wechat_id' => $this->wechat_id,
            );
            $result = dao('wechat_marketing')->field('id, name, background, description, status')->where($where)->find();
        }
        if($result){
            $articles = array('type' => 'text', 'content' => '活动未启用');
            if ($result['status'] == 1) {
                $articles = array();
                // 数据
                $articles['type'] = 'news';
                $articles['content'][0]['Title'] = $result['name'];
                $articles['content'][0]['Description'] = $result['description'];
                $articles['content'][0]['PicUrl'] = get_wechat_image_path($result['background']);
                $articles['content'][0]['Url'] = __HOST__ . url('wechat/wall/wall_user_wechat', array('wall_id' => $result['id']));
            }

            // 数据回复类型
            if ($articles['type'] == 'text') {
                $this->weObj->text($articles['content'])->reply();
                //记录用户操作信息
                $this->record_msg($fromusername, $articles['content'], 1);
            } elseif ($articles['type'] == 'news') {
                $this->weObj->news($articles['content'])->reply();
                //记录用户操作信息
                $this->record_msg($fromusername, '图文消息', 1);
            }
            $return = true;
        }

        return $return;
    }

    /**
     * 主动发送信息
     *
     * @param unknown $tousername
     * @param unknown $fromusername
     * @param unknown $keywords
     * @param unknown $weObj
     * @param unknown $return
     * @return boolean
     */
    public function send_message($fromusername, $keywords, $weObj, $return = 0)
    {
        $result = false;
        $condition = array('command' => $keywords, 'enable' => 1, 'wechat_id' => $this->wechat_id);
        $rs = dao('wechat_extend')->field('name, command, config')->where($condition)->find();
        $plugin_type = $this->ru_id > 0 ? 'wechatseller' : 'wechat';
        $file = ADDONS_PATH . $plugin_type . '/' . $rs['command'] . '/' . ucfirst($rs['command']) . '.php';
        if (file_exists($file)) {
            require_once($file);
            $new_command = '\\app\\modules\\'. $plugin_type . '\\' . $rs['command'] . '\\' . ucfirst($rs['command']);
            $cfg = array('ru_id' => $this->ru_id);
            $wechat = new $new_command($cfg);
            $data = $wechat->returnData($fromusername, $rs);
            if (!empty($data)) {
                if ($return) {
                    $result = $data;
                } else {
                    $weObj->sendCustomMessage($data['content']);
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * 多客服
     *
     * @param unknown $fromusername
     * @param unknown $keywords
     */
    public function customer_service($fromusername, $keywords)
    {
        $result = false;
        // 是否处在多客服流程
        $kfsession = $this->weObj->getKFSession($fromusername);
        if (empty($kfsession) || empty($kfsession['kf_account'])) {
            $kefu = dao('wechat_user')->where(array('openid' => $fromusername, 'wechat_id' => $this->wechat_id))->getField('openid');
            if ($kefu && $keywords == 'kefu') {
                $rs = $this->db->table('wechat_extend')->where(array('command' => 'kefu', 'enable' => 1, 'wechat_id' => $this->wechat_id))->getField('config');
                if (!empty($rs)) {
                    $config = unserialize($rs);
                    $msg = array(
                        'touser' => $fromusername,
                        'msgtype' => 'text',
                        'text' => array(
                            'content' => '欢迎进入多客服系统'
                        )
                    );
                    $this->weObj->sendCustomMessage($msg);
                    //记录用户操作信息
                    $this->record_msg($fromusername, $msg['text']['content'], 1);
                    // 在线客服列表
                    $online_list = $this->weObj->getCustomServiceOnlineKFlist();
                    if ($online_list['kf_online_list']) {
                        foreach ($online_list['kf_online_list'] as $key => $val) {
                            if ($config['customer'] == $val['kf_account'] && $val['status'] > 0 && $val['accepted_case'] < $val['auto_accept']) {
                                $customer = $config['customer'];
                            } else {
                                $customer = '';
                            }
                        }
                    }
                    // 转发客服消息
                    $this->weObj->transfer_customer_service($customer)->reply();
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * 关闭多客服菜单
     */
    public function close_kf($openid, $keywords)
    {
        $openid = $this->model->table('wechat_user')->where(array('openid' => $openid, 'wechat_id' => $this->wechat_id))->getField('openid');
        if ($openid) {
            $kfsession = $this->weObj->getKFSession($openid);
            if ($keywords == 'q' && isset($kfsession['kf_account']) && !empty($kfsession['kf_account'])) {
                $rs = $this->weObj->closeKFSession($openid, $kfsession['kf_account'], '客户已主动关闭多客服');
                if ($rs) {
                    $msg = array(
                        'touser' => $openid,
                        'msgtype' => 'text',
                        'text' => array(
                            'content' => '您已退出多客服系统'
                        )
                    );
                    $this->weObj->sendCustomMessage($msg);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 记录用户操作信息
     */
    public function record_msg($fromusername, $keywords, $wechat_admin_id = 0)
    {
        $uid = dao('wechat_user')->where(array('openid' => $fromusername, 'wechat_id' => $this->wechat_id))->getField('uid');
        if ($uid) {
            $data['uid'] = $uid;
            $data['msg'] = $keywords;
            $data['send_time'] = gmtime();
            // 微信公众号回复标识
            if ($wechat_admin_id) {
                $data['wechat_admin_id'] = $wechat_admin_id;
            }
            dao('wechat_custom_message')->data($data)->add();
        }
    }

    /**
     * 插件页面显示方法
     *
     * @param string $plugin
     */
    public function actionPluginShow()
    {
        if (is_wechat_browser() && ((!isset($_SESSION['openid']) || empty($_SESSION['openid'])) || empty($_SESSION['user_id']))) {
            $redirect_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $this->redirect('oauth/index/index', array('type' => 'wechat', 'back_url' => urlencode($redirect_url)));
        }
        $plugin_name = I('get.name', '', 'trim');
        $ru_id = I('get.ru_id', 0, 'intval');
        $ru_id = !empty($ru_id) ? $ru_id : $_SESSION['ru_id'];
        $plugin_type = $ru_id > 0 ? 'wechatseller' : 'wechat';
        $file = ADDONS_PATH . $plugin_type . '/' . $plugin_name . '/' . ucfirst($plugin_name) . '.php';
        if (file_exists($file)) {
            include_once($file);
            $new_plugin = '\\app\\modules\\'. $plugin_type . '\\' . $plugin_name . '\\' . ucfirst($plugin_name);
            $cfg = array('ru_id' => $ru_id);
            $wechat = new $new_plugin($cfg);
            $wechat->html_show();
        }
    }

    /**
     * 插件处理方法
     *
     * @param string $plugin
     */
    public function actionPluginAction()
    {
        $plugin_name = I('get.name', '', 'trim');
        $ru_id = I('get.ru_id', 0, 'intval');
        $ru_id = !empty($ru_id) ? $ru_id : $_SESSION['ru_id'];
        $plugin_type = $ru_id > 0 ? 'wechatseller' : 'wechat';
        $file = ADDONS_PATH . $plugin_type . '/' . $plugin_name . '/' . ucfirst($plugin_name) . '.php';
        if (file_exists($file)) {
            include_once($file);
            $new_plugin = '\\app\\modules\\'. $plugin_type . '\\' . $plugin_name . '\\' . ucfirst($plugin_name);
            $cfg = array('ru_id' => $ru_id);
            $wechat = new $new_plugin($cfg);
            $wechat->executeAction();
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

    /**
     * 获取access_token的接口
     * @return [type] [description]
     */
    public function check_auth()
    {
        $appid = I('get.appid');
        $appsecret = I('get.appsecret');
        if (empty($appid) || empty($appsecret)) {
            echo json_encode(array('errmsg' => '信息不完整，请提供完整信息', 'errcode' => 1));
            exit;
        }
        $config = dao('wechat')
            ->field('token, appid, appsecret')
            ->where(array('appid' => $appid, 'appsecret' => $appsecret, 'status' => 1))
            ->find();
        if (empty($config)) {
            echo json_encode(array('errmsg' => '信息错误，请检查提供的信息', 'errcode' => 1));
            exit;
        }
        $obj = new Wechat($config);
        $access_token = $obj->checkAuth();
        if ($access_token) {
            echo json_encode(array('access_token' => $access_token, 'errcode' => 0));
            exit;
        } else {
            echo json_encode(array('errmsg' => $obj->errmsg, 'errcode' => $obj->errcode));
            exit;
        }
    }

    /**
     * 同步社会化登录用户信息表
     * @param  [type] $res, $type:qq,sina,wechat
     * @return
     */
    private function update_connnect_user($res, $type = '')
    {
        $data = array(
            'connect_code' => 'sns_' . $type,
            'user_id' => $res['user_id'],
            'open_id' => $res['openid'],
            'profile' => serialize($res),
            'create_at' => gmtime()
        );
        // 查询是否绑定
        $where = array('user_id' => $res['user_id']);
        $connect_userinfo = dao('connect_user')->field('open_id')->where($where)->find();

        if (empty($connect_userinfo)) {
            // 未绑定插入记录
            dao('connect_user')->data($data)->add();
        } else {
            // 已经绑定更新记录
            dao('connect_user')->data($data)->where($where)->save();
        }
    }

    public static function snsapi_base($ru_id = 0)
    {
        $where = array('ru_id' => $ru_id);
        $wxinfo = dao('wechat')->field('token, appid, appsecret, status')->where($where)->find();
        if(!empty($wxinfo) && $wxinfo['status'] == 1 && is_wechat_browser() && (empty($_SESSION['seller_openid']) || empty($_COOKIE['seller_openid']))){
            $config = array(
                'appid' => $wxinfo['appid'],
                'appsecret' => $wxinfo['appsecret'],
                'token' => $wxinfo['token'],
                );
            $obj = new Wechat($config);
            // 用code换token
            if(isset($_GET['code']) && $_GET['state'] == 'repeat'){
                $token = $obj->getOauthAccessToken();
                $_SESSION['seller_openid'] = $token['openid'];
                cookie('seller_openid', $token['openid'], 3600 * 24);
            }
            // 生成请求链接
            $callback = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $url = $obj->getOauthRedirect($callback, 'repeat', 'snsapi_base');
            // 授权开始
            redirect($url);
        }
    }

    /**
     * 主动发送客服消息 统一方法
     */
    public function send_custom_message($openid = 0, $msgtype = '', $data){
        $msg = array();
        if($msgtype == 'text'){
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'text',
                'text' => array(
                    'content' => $data
                )
            );
        }elseif($msgtype == 'image'){
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'image',
                'image' => array(
                    'media_id' => $data
                )
            );
        }elseif($msgtype == 'voice'){
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'voice',
                'voice' => array(
                    'media_id' => $data
                )
            );
        }elseif($msgtype == 'video'){
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'video',
                'video' => array(
                    'media_id' => $data['media_id'],
                    'thumb_media_id' => $data['media_id'],
                    'title' => $data['title'],
                    'description' => $data['description']
                )
            );
        }elseif($msgtype == 'music'){
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'music',
                'music' => array(
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'musicurl' => $data['musicurl'],
                    'hqmusicurl' => $data['hqmusicurl'],
                    'thumb_media_id' => $data['thumb_media_id']
                )
            );
        }elseif($msgtype == 'news'){
            /**
             * $newsData 数组结构:
             *  array(
             *      "0"=>array(
             *          'title'=>'msg title',
             *          'description'=>'summary text',
             *          'picurl'=>'http://www.domain.com/1.jpg',
             *          'url'=>'http://www.domain.com/1.html'
             *      ),
             *      "1"=>....
             *  )
             */
            $newsData = $data;
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'news',
                'news' => array(
                    'articles' => $newsData,
                )
            );
        }

        $this->weObj->sendCustomMessage($msg);
    }

    // 微信消息日志队列之存入数据库
    public function message_log_alignment_add($wedata = array())
    {
        //判断菜单点击事件
        if($wedata['MsgType'] == 'event'){
            $data = array(
                'wechat_id' => $this->wechat_id,
                'fromusername' => $wedata['FromUserName'],
                'createtime' => $wedata['CreateTime'],
                'msgtype' => $wedata['MsgType'],
                'keywords' => $wedata['EventKey'],
                );
            // 使用FromUserName + CreateTime 排重
            $where = array(
                'fromusername' => $wedata['FromUserName'],
                'createtime' => $wedata['CreateTime']
                );
        }else{
            $data = array(
                'wechat_id' => $this->wechat_id,
                'fromusername' => $wedata['FromUserName'],
                'createtime' => $wedata['CreateTime'],
                'msgtype' => $wedata['MsgType'],
                'keywords' => $wedata['Content'],
                'msgid' => $wedata['MsgId'],
                );
            // 使用msgid排重
            $where = array(
                'msgid' => $data['msgid']
                );
        }
        // 插入
        $rs = dao('wechat_message_log')->where($where)->find();
        if(empty($rs)){
            dao('wechat_message_log')->data($data)->add();
        }
    }
    // 微信消息日志队列之处理发送状态
    public function message_log_alignment_send($fromusername, $keyword, $contents)
    {
        // 查询并更新发送状态
        if($contents['msgtype'] == 'event'){
            // 使用FromUserName + CreateTime 排重
            $where = array(
                'wechat_id' => $this->wechat_id,
                'fromusername' => $contents['fromusername'],
                'createtime' => $contents['createtime'],
                'is_send' => 0
                );
        }else{
            // 使用msgid排重
            $where = array(
                'wechat_id' => $this->wechat_id,
                'msgid' => $contents['msgid'],
                'is_send' => 0
                );
        }

        dao('wechat_message_log')->data(array('is_send' => 1))->where($where)->save();

        $is_send_number = dao('wechat_message_log')->where(array('is_send' => 1, 'wechat_id' => $this->wechat_id))->count();
        if($is_send_number > 1000){
            dao('wechat_message_log')->where(array('is_send' => 1))->delete();
        }
    }

}
