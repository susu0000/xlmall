<?php
defined('IN_ECTOUCH') or die('Deny Access');

use ectouch\Http;

class wxpay
{
    private $parameters; // cft 参数
    private $payment; // 配置信息

    /**
     * 生成支付代码
     * @param   array $order 订单信息
     * @param   array $payment 支付方式信息
     */
    public function get_code($order, $payment)
    {
        include_once(BASE_PATH . 'helpers/payment_helper.php');
		
		$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        if( !preg_match('/micromessenger/', $ua)){
            return '<div style="text-align:center"><button class="btn btn-primary" type="button" disabled>请在微信中支付</button></div>';
        }
        
		
        // 配置参数
        $this->payment = $payment;
        // 网页授权获取用户openid
        $openid = '';
        if (isset($_SESSION['openid']) && !empty($_SESSION['openid'])) {
            $openid = $_SESSION['openid'];
        } elseif (isset($_SESSION['openid_base']) && !empty($_SESSION['openid_base'])) {
            $openid = $_SESSION['openid_base'];
		} elseif (isset($_SESSION['wxpay_jspay_openid']) && !empty($_SESSION['wxpay_jspay_openid'])) {
            $openid = $_SESSION['wxpay_jspay_openid'];	
        } else {
			
            //return false;
        }
		if(!isset($openid) || empty($openid) ){
            return '<div style="text-align:center"><button class="btn btn-primary" type="button" disabled>未得权限</button></div>';
        }
        // 设置必填参数
        // 根目录url
        $order_amount = $order['order_amount'] * 100;
        $this->setParameter("openid", "$openid"); // 商品描述
        $this->setParameter("body", $order['order_sn']); // 商品描述
        $this->setParameter("out_trade_no", $order['order_sn'] . $order_amount . 'O' . $order['log_id']); // 商户订单号
        $this->setParameter("total_fee", $order_amount); // 总金额
        $this->setParameter("notify_url", notify_url(basename(__FILE__, '.php'))); // 通知地址
        $this->setParameter("trade_type", "JSAPI"); // 交易类型
        $prepay_id = $this->getPrepayId();
        $jsApiParameters = $this->getParameters($prepay_id);
        // wxjsbridge
        $js = '<script language="javascript">
            function jsApiCall(){WeixinJSBridge.invoke("getBrandWCPayRequest",' . $jsApiParameters . ',function(res){if(res.err_msg == "get_brand_wcpay_request:ok"){location.href="' . return_url(basename(__FILE__, '.php')) . '&status=1"}else{location.href="' . return_url(basename(__FILE__, '.php')) . '&status=0"}})};function callpay(){if (typeof WeixinJSBridge == "undefined"){if( document.addEventListener ){document.addEventListener("WeixinJSBridgeReady", jsApiCall, false);}else if (document.attachEvent){document.attachEvent("WeixinJSBridgeReady", jsApiCall);document.attachEvent("onWeixinJSBridgeReady", jsApiCall);}}else{jsApiCall();}}
            </script>';

        $button = '<a class="box-flex btn-submit" type="button" onclick="callpay()">微信支付</a>' . $js;

        return $button;
    }

    /**
     * 同步通知
     * @param $data
     * @return mixed
     */
    public function callback($data)
    {
        if ($_GET['status'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 异步通知
     * @param $data
     * @return mixed
     */
    public function notify($data)
    {
        include_once(BASE_PATH . 'helpers/payment_helper.php');
        $_POST['postStr'] = file_get_contents("php://input");
        if (!empty($_POST['postStr'])) {
            $payment = get_payment($data['code']);
            $postdata = json_decode(json_encode(simplexml_load_string($_POST['postStr'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
            // 微信端签名
            $wxsign = $postdata['sign'];
            unset($postdata['sign']);

            foreach ($postdata as $k => $v) {
                $Parameters[$k] = $v;
            }
            // 签名步骤一：按字典序排序参数
            ksort($Parameters);

            $buff = "";
            foreach ($Parameters as $k => $v) {
                $buff .= $k . "=" . $v . "&";
            }
            $String = '';
            if (strlen($buff) > 0) {
                $String = substr($buff, 0, strlen($buff) - 1);
            }
            // 签名步骤二：在string后加入KEY
            $String = $String . "&key=" . $payment['wxpay_key'];
            // 签名步骤三：MD5加密
            $String = md5($String);
            // 签名步骤四：所有字符转为大写
            $sign = strtoupper($String);
            // 验证成功
            if ($wxsign == $sign) {
                // 交易成功
                if ($postdata['result_code'] == 'SUCCESS') {
                    // 获取log_id
                    $out_trade_no = explode('O', $postdata['out_trade_no']);
                    $order_sn = $out_trade_no[1]; // 订单号log_id
                    // 修改订单信息(openid，tranid)
                    dao('pay_log')
                        ->data(array('openid' => $postdata['openid'], 'transid' => $postdata['transaction_id']))
                        ->where(array('log_id' => $order_sn))
                        ->save();
                    // 改变订单状态
                    order_paid($order_sn, 2);

                    /*if(method_exists('WechatController', 'do_oauth')){
                        // 如果需要，微信通知 wanglu
                        $order_id = model('Base')->model->table('order_info')
                            ->field('order_id')
                            ->where('order_sn = "' . $out_trade_no[0] . '"')
                            ->getOne();
                        $order_url = __HOST__ . url('user/order_detail', array(
                            'order_id' => $order_id
                        ));
                        $order_url = urlencode(base64_encode($order_url));
                        send_wechat_message('pay_remind', '', $out_trade_no[0] . ' 订单已支付', $order_url, $out_trade_no[0]);
                    }*/
                }
                $returndata['return_code'] = 'SUCCESS';
            } else {
                $returndata['return_code'] = 'FAIL';
                $returndata['return_msg'] = '签名失败';
            }
        } else {
            $returndata['return_code'] = 'FAIL';
            $returndata['return_msg'] = '无数据返回';
        }
        // 数组转化为xml
        $xml = "<xml>";
        foreach ($returndata as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";

        exit($xml);
    }

    function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 作用：产生随机字符串，不长于32位
     */
    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 作用：设置请求参数
     */
    function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);

        $buff = "";
        foreach ($Parameters as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $String;
        if (strlen($buff) > 0) {
            $String = substr($buff, 0, strlen($buff) - 1);
        }
        // echo '【string1】'.$String.'</br>';
        // 签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->payment['wxpay_key'];
        // echo "【string2】".$String."</br>";
        // 签名步骤三：MD5加密
        $String = md5($String);
        // echo "【string3】 ".$String."</br>";
        // 签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        // echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     * 作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml, $url, $second = 30)
    {
        // 初始化curl
        $ch = curl_init();
        // 设置超时
        curl_setopt($ch, CURLOP_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        // 运行curl
        $data = curl_exec($ch);
        curl_close($ch);
        // 返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 获取prepay_id
     */
    function getPrepayId()
    {
        // 设置接口链接
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        try {
            // 检测必填参数
            if ($this->parameters["out_trade_no"] == null) {
                throw new Exception("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($this->parameters["body"] == null) {
                throw new Exception("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($this->parameters["total_fee"] == null) {
                throw new Exception("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($this->parameters["notify_url"] == null) {
                throw new Exception("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($this->parameters["trade_type"] == null) {
                throw new Exception("缺少统一支付接口必填参数trade_type！" . "<br>");
            } elseif ($this->parameters["trade_type"] == "JSAPI" && $this->parameters["openid"] == NULL) {
                throw new Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！" . "<br>");
            }
            $this->parameters["appid"] = $this->payment['wxpay_appid']; // 公众账号ID
            $this->parameters["mch_id"] = $this->payment['wxpay_mchid']; // 商户号
            $this->parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR']; // 终端ip
            $this->parameters["nonce_str"] = $this->createNoncestr(); // 随机字符串
            $this->parameters["sign"] = $this->getSign($this->parameters); // 签名
            $xml = "<xml>";
            foreach ($this->parameters as $key => $val) {
                if (is_numeric($val)) {
                    $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
                } else {
                    $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
                }
            }
            $xml .= "</xml>";
        } catch (Exception $e) {
            die($e->getMessage());
        }

        // $response = $this->postXmlCurl($xml, $url, 30);
        $response = Http::doPost($url, $xml, 30);
        $result = json_decode(json_encode(simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $prepay_id = $result["prepay_id"];
        return $prepay_id;
    }

    /**
     * 作用：设置jsapi的参数
     */
    public function getParameters($prepay_id)
    {
        $jsApiObj["appId"] = $this->payment['wxpay_appid'];
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=$prepay_id";
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $this->parameters = json_encode($jsApiObj);

        return $this->parameters;
    }

    /**
     * 订单查询
     * @return mixed
     */
    public function query($order, $payment)
    {

    }

    /**
     * 获取openid
     */
    public function getOpenid($payment)
    {
        $this->payment = $payment;
	
		if(isset($_GET['state']) && $_GET['state']=="getOpenid"){
            
			 $code = $_GET['code'];
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->payment['wxpay_appid'] . '&secret=' . $this->payment['wxpay_appsecret'] . '&code=' . $code . '&grant_type=authorization_code';
			
			$wxJsonUrl="https://api.weixin.qq.com/sns/oauth2/access_token?";
			$wxJsonUrl.='appid='.$payment['wxpay_appid'];
			$wxJsonUrl.='&secret='.$payment['wxpay_appsecret'];
			$wxJsonUrl.='&code='.$code;
			$wxJsonUrl.='&grant_type=authorization_code';

			if (extension_loaded('curl') && function_exists('curl_init') && function_exists('curl_exec')){
				$result=$this->curl_https($wxJsonUrl);
			}elseif(extension_loaded  ('openssl')){
				$result = file_get_contents ( $wxJsonUrl );
			}else{
				//return false;
				//$this->logResult("error::getOpenId::curl或openssl未开启");
			}
			
	           // $result = \Http::doGet($url);
            if ($result) {
                $json = json_decode($result,true);
	
                if (isset($json['errCode']) && $json['errCode']) {
                    return false;
                }
                $_SESSION['openid_base'] = $json['openid'];
                return $json['openid'];
            }
			unset($_GET['code']);
            return false;
			
        } else {
			$redirectUrl = urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . $_SERVER['QUERY_STRING']);
			$redirectUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $payment['wxpay_appid'] . '&redirect_uri=' . $redirectUrl . '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
			
            $wxUrl='https://open.weixin.qq.com/connect/oauth2/authorize?';
            $wxUrl.='appid='.$payment['wxpay_appid'];
            $wxUrl.='&redirect_uri='.urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            $wxUrl.='&response_type=code&scope=snsapi_base';
            $wxUrl.='&state=getOpenid';
            $wxUrl.='#wechat_redirect';

            ob_end_clean();
			
            header("Location: $wxUrl");
            exit;
		
           
        }
    }

    function logResult($word = '',$var=array()) {
        if( true || !WXPAY_DEBUG){
            return true;
        }
        $output= strftime("%Y%m%d %H:%M:%S", time()) . "\n" ;
        $output .= $word."\n" ;
        if(!empty($var)){
            $output .= print_r($var, true)."\n";
        }
        $output.="\n";
        file_put_contents(ROOT_PATH . "/data/log/wx.txt", $output, FILE_APPEND | LOCK_EX);
    }
	
	
    function curl_https($url, $header=array(), $timeout=30){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $response = curl_exec($ch);
        if(!$response){
            $error=curl_error($ch);
            $this->logResult("error::curl_https::error_code".$error);
        }
        curl_close($ch);

        return $response;

    }
	
}
