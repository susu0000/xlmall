<?php

namespace app\http\ecapi\controllers;

use app\http\base\controllers\Frontend;

/**
 * Class Index
 * @package app\http\ecapi\controllers
 * @method    String    是    API接口名称。
 * @app_key    String    是    分配给应用的AppKey。
 * @sign_method    String    是    签名的摘要算法，可选值为：md5。
 * @sign    String    是    API输入参数签名结果。
 * @session    String    否    用户登录授权成功后，颁发给应用的授权信息。当此API的标签上注明：“需要授权”，则此参数必传；“不需要授权”，则此参数不需要传；“可选授权”，则此参数为可选。
 * @timestamp    String    是    时间戳，格式为yyyy-MM-dd HH:mm:ss，时区为GMT+8，例如：2015-01-01 12:00:00。API服务端允许客户端请求最大时间误差为10分钟。
 * @format    String    否    响应格式。默认为xml格式，可选值：xml，json。
 * @v    String    是    API协议版本，可选值：2.0。
 */
class Index extends Frontend
{

    /**
     *
     */
    public function actionIndex()
    {
        $method = 'method=ecapi.goods.img.get&app_key=123&sign_method=md5&sign=111&timestamp=2015-01-01 12:00:00&format=json&v=1';

        $params = array(
            'id' => 11
        );

        $instance = $this->getMethod($method);
        if (class_exists($instance['class'])) {
            $handler = new $instance['class'];
            $data = $handler->$instance['method']($params);
            $this->response($data);
        } else {
            $this->response(array('msg' => 'api not found.'), 'json', 404);
        }
    }

    /**
     * @param $method
     * @param string $version
     * @return mixed
     */
    private function getMethod($method, $version = 'v1')
    {
        $method = str_replace('ecapi', 'app.repository.' . $version, $method);

        $class = '\\';
        $res = explode('.', $method);
        $length = count($res);

        foreach ($res as $key => $vo) {
            if ($key > 2 && $key < $length - 1) {
                $class .= ucfirst($vo);
            } else {
                $class .= $vo . '\\';
            }
            if ($length - 2 == $key) break;
        }
        return array('class' => $class, 'method' => end($res));
    }

    /**
     * @param $str
     * @param bool $ucfirst
     * @return mixed|string
     */
    private function convertUnderline($str, $ucfirst = true)
    {
        $str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ', '', lcfirst($str));
        return $ucfirst ? ucfirst($str) : $str;
    }
}