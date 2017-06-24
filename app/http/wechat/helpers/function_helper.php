<?php

// 获得后台商家ID
function get_admin_ru_id_seller() {

    $self = explode("/", substr(PHP_SELF, 1));
    $count = count($self);

    if ($count > 1) {
        $real_path = $self[$count - 2];
        if ($real_path == 'mobile') {
            $admin_id = $_SESSION['seller_id'];
        }

        if (isset($admin_id)) {
            $sql = "select ru_id from " . $GLOBALS['ecs']->table('admin_user') . " where user_id = '$admin_id'";
            return $GLOBALS['db']->getRow($sql);
        }
    }
}


//设置商家菜单
function set_seller_menu() {

    define('IN_ECS', true);
    define('MOBILE_WECHAT',            ROOT_PATH . 'app/http/wechat'); //微商城目录
    include_once(dirname(ROOT_PATH) . '/seller/' . 'includes/inc_priv.php');
    include_once(dirname(ROOT_PATH) . '/seller/' . 'includes/inc_menu.php');
    require(dirname(ROOT_PATH) . '/' . 'languages/' . C('shop.lang') . '/admin/common_merchants.php');

    //菜单排序
    foreach ($modules AS $key => $value) {
        ksort($modules[$key]);
    }
    ksort($modules);

    //商家权限
    $action_list = explode(',', $_SESSION['seller_action_list']);

    //权限子菜单
    $action_menu = array();
    foreach ($purview as $key => $val) {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                if (in_array($v, $action_list)) {
                    $action_menu[$key] = $v;
                }
            }
        } else {
            if (in_array($val, $action_list)) {
                $action_menu[$key] = $val;
            }
        }
    }

    //匹配父菜单
    foreach ($modules as $key => $val) {
        foreach ($val as $k => $v) {
            if (!array_key_exists($k, $action_menu)) {
                unset($modules[$key][$k]);
            }
        }

        if (empty($modules[$key])) {
            unset($modules[$key]);
        }
    }

    //菜单赋值
    $menu = array();
    $i = 0;
    foreach ($modules as $key => $val) {
        if($key == '22_wechat'){
            $menu[$i] = array(
                'action' => $key,
                'label' => get_menu_url(reset($val), $_LANG[$key]),
                'url' => get_wechat_menu_url(reset($val)),
                'children' => array()
            );

            foreach ($val as $k => $v) {
                $menu[$i]['children'][] = array(
                    'action' => $k,
                    'label' => get_menu_url($v, $_LANG[$k]),
                    'url' => get_wechat_menu_url($v),
                    'status' => get_user_menu_status($k)
                );
            }
        }else{
            $menu[$i] = array(
                'action' => $key,
                'label' => get_menu_url(reset($val), $_LANG[$key]),
                'url' => get_menu_url(reset($val)),
                'children' => array()
            );

            foreach ($val as $k => $v) {
                $menu[$i]['children'][] = array(
                    'action' => $k,
                    'label' => get_menu_url($v, $_LANG[$k]),
                    'url' => get_menu_url($v),
                    'status' => get_user_menu_status($k)
                );
            }
        }

        $i++;
    }


    unset($modules, $purview); //用完后清空，避免影响其他功能
    return $menu;
}

// 返回商家菜单链接
function get_menu_url($url = '', $name = '') {

    if ($url) {
        $url = '../seller/' . $url;
        $url_arr = explode('?', $url);
        if (!$url_arr[0] || !is_file($url_arr[0])) {
            $url = '#';
            if ($name) {
                $name = '<span style="text-decoration: line-through; color:#ccc; ">' . $name . '</span>';
            }
        }
    }

    if ($name) {
        return $name;
    } else {
        return $url;
    }
}
// 返回商家微信通菜单链接
function get_wechat_menu_url($url = '', $name = '') {

    if ($url) {
        $url_arr = explode('?', $url);
        if (!$url_arr[0] || !is_file($url_arr[0])) {
            $url = '#';
            if ($name) {
                $name = '<span style="text-decoration: line-through; color:#ccc; ">' . $name . '</span>';
            }
        }
    }

    if ($name) {
        return $name;
    } else {
        return $url;
    }
}

//返回快捷菜单选中状态
function get_user_menu_status($action = '') {
    $user_menu_arr = get_user_menu_list();
    if ($user_menu_arr && in_array($action, $user_menu_arr)) {
        return 1;
    } else {
        return 0;
    }
}
//返回快捷菜单列表
function get_user_menu_list() {
    $adminru = get_admin_ru_id_seller();
    if ($adminru['ru_id'] > 0) {
        $sql = " SELECT user_menu FROM " . $GLOBALS['ecs']->table('seller_shopinfo') . " WHERE ru_id = '" . $adminru['ru_id'] . "' ";
        $user_menu_str = $GLOBALS['db']->getOne($sql);
        if ($user_menu_str) {
            $user_menu_arr = explode(',', $user_menu_str);
            return $user_menu_arr;
        }
    }
    return false;
}

// 获得当前选中的菜单
function get_select_menu() {

    $left_menu = array(

      '22_wechat' =>
      array (
        '01_wechat_admin' => 'm=wechat&c=seller&a=modify',
        '02_mass_message' => 'm=wechat&c=seller&a=mass_message',
        '02_mass_message_01' => 'm=wechat&c=seller&a=mass_list',
        '03_auto_reply' => 'm=wechat&c=seller&a=reply_subscribe',
        '03_auto_reply_01' => 'm=wechat&c=seller&a=reply_msg',
        '03_auto_reply_02' => 'm=wechat&c=seller&a=reply_keywords',
        '04_menu' => 'm=wechat&c=seller&a=menu_list',
        '04_menu_01' => 'm=wechat&c=seller&a=menu_edit',
        '05_fans' => 'm=wechat&c=seller&a=subscribe_list',
        '05_fans_01' => 'm=wechat&c=seller&a=custom_message_list',
        '05_fans_02' => 'm=wechat&c=seller&a=subscribe_search',
        '06_media' => 'm=wechat&c=seller&a=article',
        '06_media_01' => 'm=wechat&c=seller&a=article_edit',
        '06_media_02' => 'm=wechat&c=seller&a=article_edit_news',
        '06_media_03' => 'm=wechat&c=seller&a=picture',
        '06_media_04' => 'm=wechat&c=seller&a=voice',
        '06_media_05' => 'm=wechat&c=seller&a=video',
        '06_media_06' => 'm=wechat&c=seller&a=video_edit',
        '07_qrcode' => 'm=wechat&c=seller&a=qrcode_list',
        '07_qrcode_01' => 'm=wechat&c=seller&a=qrcode_edit',
        '09_extend' => 'm=wechat&c=sellerextend&a=index',
        // '09_extend_01' => 'm=wechat/sellerextend/edit',
        // '09_extend_02' => 'm=wechat/sellerextend/winnerlist',
      )

    );

    $url = isset($_SERVER["QUERY_STRING"]) ? trim($_SERVER["QUERY_STRING"]) : '';

    // 匹配功能扩展下选择的菜单列表
    $sellerextend = strstr($url,"sellerextend");
    if($sellerextend){
        $url = 'm=wechat&c=sellerextend&a=index';
    }else{
        $info = get_url_query($url);
        $url = match_url($url, $info['a']);
    }

    $menu_arr = get_menu_arr($url, $left_menu);

    return $menu_arr;
}
/**
 * 匹配带详情的链接 如 article_edit&id=1，article_edit_news&id=1 等等
 * @param  string $url  链接 如 m=wechat&c=seller&a=subscribe_search&tag_id=1
 * @param  string $fuction_a 方法名 如 a=article_edit
 * @return [type]
 */
function match_url($url = '', $fuction_a = '', $prefix = 'm=wechat&c=seller&a='){
    $is_match = strstr($url, $fuction_a);
    if($is_match){
        $url = $prefix . $fuction_a;
    }
    return $url;
}

// 匹配选择的菜单
function get_menu_arr($url = '', $list = array()) {
    static $menu_arr = array();
    static $menu_key = NULL;
    foreach ($list as $key => $val) {
        if (is_array($val)) {
            $menu_key = $key;
            get_menu_arr($url, $val);
        } else {
            if ($val == $url) {
                $menu_arr['action'] = $menu_key;
                $menu_arr['current'] = $key;
                // 其他子菜单匹配
                $key_2 = substr($key, 0, -3);
                $menu_arr['current_2'] = $key_2;
            }
        }
    }
    return $menu_arr;
}

/**
 * 获得URL参数
 * @param string $url URL表达式，格式：'?参数1=值1&参数2=值2...'
 * @return array *
 */
function get_url_query($url = '')
{
    // 解析URL
    $info = parse_url($url);

    if(isset($info['path'])) { // 解析地址里面path参数
        parse_str($info['path'],$params);
    }elseif(isset($info['query'])) { // 解析地址里面query参数
        parse_str($info['query'],$params);
    }
    return $params;
}

/**
 * 处理编辑素材时上传保存图片
 * 配合 get_wechat_image_path 方法使用 ,将网站本地图片绝对路径地址 转换为 相对路径
 * 保存到数据库的值 为相对路径 data/attached/..... or oss完整路径
 * @param  string $url
 * @param  string $no_path 默认 'app/modules'
 * @return $url
 */
function edit_upload_image($url = '', $no_path = 'app/modules')
{
    if(strpos($url, $no_path)){
        $prex_patch = __HOST__ . __ROOT__;
    }else{
        $prex_patch = __HOST__ . __STATIC__;
    }

    $prex_patch = rtrim($prex_patch, '/') . '/';
    $url = str_replace($prex_patch, '', $url);
    return $url;
}

/**
 * 处理URL 加上后缀参数 如 ?id=1  &id=1
 * @param string $url URL表达式，格式：'?参数1=值1&参数2=值2...'
 * @param string|array $vars 传入的参数，支持数组和字符串
 * @return string $url
 */
function add_url_suffix($url = '', $vars = '')
{
    // 解析URL
    $info   =  parse_url($url);
    $path    =  !empty($info['path']) ? $info['path'] : '';
    // 解析参数
    if(is_string($vars)) { // aaa=1&bbb=2 转换成数组
        parse_str($vars,$vars);
    }elseif(!is_array($vars)){
        $vars = array();
    }
    if(isset($info['query'])) { // 解析地址里面参数 合并到vars
        $info['query'] = htmlspecialchars_decode($info['query']); // 处理html字符 &amp, 导致的参数重复
        parse_str($info['query'],$params);
        $vars = array_merge($params,$vars);
    }
    $depr = '?';
    if (!empty($vars)) {
        $vars = http_build_query($vars);
        $path .= $depr . $vars;
    }
    $url = $info['host'] . $path;
    // $url = rtrim($url, '&');
    // 添加https http头
    if(!preg_match("/^(http|https):/", $url)){
      $url = (is_ssl() ? 'https://' : 'http://') . $url;
    }
    return strtolower($url);
}


/**
 * 生成密钥文件
 * @param string $content
 */
function file_write($filename, $content = '')
{
    $fp = fopen(ROOT_PATH . 'storage/certs/' . $filename, "w+"); // 读写，每次修改会覆盖原内容
    flock($fp, LOCK_EX);
    fwrite($fp, $content);
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 *  处理post get输入参数 可使用此函数 配合TP的I方法 如I('post.content','','new_htm_in');
 *  兼容php5.4以上magic_quotes_gpc后默认开启后 处理重复转义的问题
 * @return [string] $str
 */
function new_html_in($str)
{
    $str = htmlspecialchars($str);
    // magic_quotes_gpc 默认On
    if (get_magic_quotes_gpc()) {
        $str = stripslashes($str);
    }
    return $str;
}

/**
 * 将字符串以 * 号格式显示 配合msubstr_ect函数使用(所在文件common_helper.php)
 * @param  string $string
 * @return [string]
 */
function string_to_star($string = '')
{
    $lenth = strlen($string) -2;
    for ($x = 1; $x <= $lenth; $x ++) {
      $star_length .= "*";
    }
    $result = msubstr_ect($string, 0, 1, 'utf-8', $star_length);
    return $result;
}