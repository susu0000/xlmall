<?php
namespace app\http\wechat\controllers;

use app\http\base\controllers\Frontend;

class Api extends Frontend
{
    // 会员ID
    public $user_id = 0;
    // 消息标题
    public $code = '';
    // 消息内容
    public $pushData = '';
    // 消息链接
    public $url = '';

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $this->user_id = I('get.user_id', 0, 'intval');
        $this->code = I('get.code','','trim');
        $this->pushData = I('get.pushData','','trim');
        $this->url = I('get.url','');
        $this->url = $this->url ? base64_decode(urldecode($this->url)) : '';
    }

    /**
     * 接口方法
     */
    public function actionIndex(){

        $user_id = $this->user_id;
        $code = $this->code;
        $this->pushData = stripslashes(urldecode($this->pushData));
        //转换成数组
        $pushData = unserialize($this->pushData);
        $url = $this->url;
        // 发送微信通模板消息
        push_template($code,$pushData,$url,$user_id);

    }


}
