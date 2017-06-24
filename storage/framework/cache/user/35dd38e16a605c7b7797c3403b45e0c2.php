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
<div class="con">
			<div class="message">
				<div class="flow-done-con">
                    <!--<?php if($message['type'] == 'fail') { ?>-->
					<i class="iconfont icon-guanbi1"></i>
                    <!--<?php } ?>-->
                    <!--<?php if($message['type'] == 'success') { ?>-->
                    <i class="iconfont icon-hookring2"></i>
                    <!--<?php } ?>-->
                    <!--<?php if($message['type'] == 'warning') { ?>-->
                    <i class="iconfont icon-102"></i>
                    <!--<?php } ?>-->
					<p style="padding-left:1.5rem;padding-right:1.5rem"><?php echo ($message['content']); ?></p>
				</div>
				<div class="padding-all message-a">
                    <!--<?php $n=1; if(is_array($message['url_info'])) foreach($message['url_info'] as $info => $url) { ?>-->
                    <a class="back" href="<?php echo $url; ?>"><?php echo $info; ?></a>
                    <!--<?php $n++;}unset($n); ?>-->
				</div>

			</div>
	</div>
        <script>
            window.onload = function(){
                setTimeout(url,3000);//1000毫秒=1秒后执行test方法
            }
            function url(){
                var a = '';
                $(".back").each(function (){
                    window.location.href = $(this).attr("href");
                    return false;
                });
            }
       </script>
	</body>
</html>