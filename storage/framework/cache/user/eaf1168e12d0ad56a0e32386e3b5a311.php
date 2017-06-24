<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta charset="utf-8">
    <meta name="description" content="<?php echo $description; ?>"/>
    <meta name="keywords" content="<?php echo $keywords; ?>"/>
    <title><?php echo ($current_city['region_name']); ?>-<?php echo $page_title; ?></title>
    <?php echo global_assets('css');?>
    <script type="text/javascript">var ROOT_URL = '/mobile/';</script>
    <?php echo global_assets('js');?>
    <?php if($is_wechat) { ?>
    <script type="text/javascript" src="https://res.wx.qq.com/open/js/jweixin-1.1.0.js"></script>
    <script type="text/javascript">
        // 分享内容
        var shareContent = {
            title: '<?php echo $page_title; ?>',
            desc: '<?php echo $description; ?>',
            link: '',
            imgUrl: '<?php if($page_img) { echo $page_img; } else { echo elixir("img/wxsdk.png", true); } ?>'
        };
        $(function(){
            var url = window.location.href;
            var jsConfig = {
                debug: false,
                jsApiList: [
                    'onMenuShareTimeline',
                    'onMenuShareAppMessage',
                    'onMenuShareQQ',
                    'onMenuShareWeibo',
                    'onMenuShareQZone'
                ]
            };
            $.post('<?php echo url("wechat/jssdk/index");?>', {url: url}, function (res) {
                if(res.status == 200){
                    jsConfig.appId = res.data.appId;
                    jsConfig.timestamp = res.data.timestamp;
                    jsConfig.nonceStr = res.data.nonceStr;
                    jsConfig.signature = res.data.signature;
                    // 配置注入
                    wx.config(jsConfig);
                    // 事件注入
                    wx.ready(function () {
                        wx.onMenuShareTimeline(shareContent);
                        wx.onMenuShareAppMessage(shareContent);
                        wx.onMenuShareQQ(shareContent);
                        wx.onMenuShareWeibo(shareContent);
                        wx.onMenuShareQZone(shareContent);
                    });
                }
            }, 'json');
        })
    </script>
    <?php } ?>
</head>
<body>
<p style="text-align:right; display:none;"><?php echo config('shop.stats_code');?></p>
<div id="loading"><img src="<?php echo elixir('img/loading.gif');?>" /></div>
<div class="con b-color-f">
    <section class="user-center user-login margin-lr">
        <form class="login-form validation" action="<?php echo url('index');?>" method="post">
            <div class="text-all dis-box j-text-all" name="usernamediv">
                <label>账 号</label>
                <div class="box-flex input-text">
                    <input class="j-input-text" name="username" datatype="*" nullmsg="请输入用户名"
                           type="text" placeholder="用户名/手机号"/>
                    <i class="iconfont icon-guanbi1 is-null j-is-null"></i>
                </div>
            </div>
            <div class="text-all dis-box j-text-all" name="passworddiv">
                <label>密 码</label>
                <div class="box-flex input-text">
                    <input class="j-input-text" name="password" type="password" datatype="*" nullmsg="请输入密码" placeholder="请输入密码"/>
                    <i class="iconfont icon-guanbi1 is-null j-is-null"></i>
                </div>
                <i class="iconfont icon-yanjing is-yanjing j-yanjing disabled"></i>
            </div>
            <input type="hidden" name="back_act" value="<?php echo $back_act; ?>"/>
            <a class="fr t-remark" href="<?php echo url('user/login/get_password');?>">忘记密码？</a>
            <button type="submit" class="btn-submit min-btn br-5">登录</button>

        </form>
        <a class="a-first u-l-register" href="<?php echo url('user/login/register');?>">新用户注册</a>

        <?php if($oauth_list) { ?>
        <div class="other-login">
            <h4 class="title-hrbg"><span>第三方登录</span>
                <hr/>
            </h4>
            <ul class="dis-box">
                <?php $n=1;if(is_array($oauth_list)) foreach($oauth_list as $vo) { ?>
                    <li class="box-flex"><a href="<?php echo url('oauth/index/index', array('type' => $vo['type'],'back_url' => $back_act));?>">
                        <img src="<?php echo elixir('img/oauth/sns_'.$vo['type'].'.png');?>" alt="" width="64">
                        </a>
                    </li>
                <?php $n++;}unset($n); ?>
				<li class="hide box-flex"><a href="/mobile/index.php?m=oauth&type=weixin&back_url=http%3a%2f%2fmall.ixingliao.com%2fmobile%2findex.php%3fm%3duser">
                        <img src="/mobile/statics/img/oauth/sns_wechat.png" alt="" width="64">
                        </a>
                </li>
            </ul>
        </div>
        <?php } ?>
    </section>
</div>
<script>
    $(function () {
        $.Tipmsg.r = null;
        $(".validation").Validform({
            tiptype: function (msg) {
                d_messages(msg);
            },
            tipSweep: true,
            ajaxPost: true,
            callback: function (data) {
                // {"info":"demo info","status":"y"}
                if (data.status === 'y') {
                    window.location.href = data.url;
                } else {
                    d_messages(data.info);
                }
            }
        });
    })
</script>
</body>

</html>