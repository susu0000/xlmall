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
<section class="b-color-f  my-nav-box">
	<a href="javascript:;">
		<div class="s-user-top">
			<div class="user-bg-box-1"><img src="<?php echo elixir('img/user-1.png');?>"></div>
			<div class="user-bg2-box-1"><img src="<?php echo elixir('img/user-2.png');?>"></div>
			<div class="dis-box s-xian-box s-user-top-1 p-r">
				<img src="" alt="" id="userimg" />
				<h3 class="box-flex text-all-span my-u-title-size s-user-img">头像</h3>
				<div class="box-flex t-goods1 text-right onelist-hidden jian-top" for="logo">
					<input id="headimg" type="file" class="user-profile-img" style="display:none;" />
					<?php if($info['user_picture'] !=='' ) { ?>
					<div class="user-head-img-box-1"><img src="<?php if($info['user_picture'] == '/mobile/statics/img/no_image.jpg') { ?>/mobile/statics/img/no_head.png<?php } else { echo ($info['user_picture']); } ?>"></div>
                    <?php } else { ?>
                    <div class="user-head-img-box-1"><img src="<?php echo elixir('img/no_image.jpg');?>"></div>
                    <?php } ?>
				</div>
			</div>

		</div>
	</a>

	<div class="s-user-top">
		<div class="dis-box s-xian-box s-user-top-1">
			<h3 class="box-flex text-all-span my-u-title-size">用户名</h3>
			<div class="box-flex t-goods1 text-right onelist-hidden jian-top"><?php echo ($info['username']); ?></div>
		</div>
	</div>
	<div class="s-user-top onclik-sex">
		<div class="dis-box s-xian-box s-user-top-1">
			<h3 class="box-flex text-all-span my-u-title-size">性别</h3>
                      
			<div id="sex" class="box-flex t-goods1 text-right onelist-hidden jian-top"><?php echo ($info['sex']); ?></div>

			<span class="t-jiantou"><i class="iconfont icon-jiantou tf-180 jian-top"></i></span>
		</div>
	</div>
	<a href="<?php echo url('user/profile/user_edit_mobile');?>">
		<div class="s-user-top onclik-admin">
			<div class="dis-box s-user-top-1">
				<h3 class="box-flex text-all-span my-u-title-size">手机</h3>
                                        <?php if($info['mobile_phone'] =='') { ?>
				<div class="box-flex t-goods1 text-right onelist-hidden jian-top">未绑定</div>
                                        <?php } else { ?>
                                        <div class="box-flex t-goods1 text-right onelist-hidden jian-top"><?php echo ($info['mobile_phone']); ?></div>
                                        <?php } ?>
				<span class="t-jiantou"><i class="iconfont icon-jiantou tf-180 jian-top"></i></span>
			</div>
		</div>
	</a>
	<a href="<?php echo url('user/profile/user_edit_email');?>">
		<div class="s-user-top">
			<div class="dis-box s-user-top-1">
				<h3 class="box-flex text-all-span my-u-title-size">邮箱</h3>
                                         <?php if($info['email'] =='') { ?>
				<div class="box-flex t-goods1 text-right onelist-hidden jian-top">未绑定</div>
                                        <?php } else { ?>
                                        <div class="box-flex t-goods1 text-right onelist-hidden jian-top"><?php echo ($info['email']); ?></div>
                                        <?php } ?>
				<span class="t-jiantou"><i class="iconfont icon-jiantou tf-180 jian-top"></i></span>
			</div>
		</div>
	</a>
</section>
<section class="b-color-f my-nav-box m-top10">
	<a href="<?php echo url('user/index/edit_password');?>">
		<div class="s-user-top">
			<div class="dis-box s-user-top-1">
				<h3 class="box-flex text-all-span my-u-title-size">修改密码</h3>
				<span class="t-jiantou"><i class="iconfont icon-jiantou tf-180 jian-top"></i></span>
			</div>
		</div>
	</a>
	<a href="<?php echo url('index/addresslist');?>">
		<div class="s-user-top">
			<div class="dis-box s-user-top-1">
				<h3 class="box-flex text-all-span my-u-title-size">收货地址</h3>
				<span class="t-jiantou"><i class="iconfont icon-jiantou tf-180 jian-top"></i></span>
			</div>
		</div>
	</a>
	<a href="<?php echo url('user/profile/Realname');?>">
		<div class="s-user-top">
			<div class="dis-box s-user-top-1">
				<h3 class="box-flex text-all-span my-u-title-size">实名认证</h3>
				<span class="t-jiantou"><i class="iconfont icon-jiantou tf-180 jian-top"></i></span>
			</div>
		</div>
	</a>
</section>
<div class="ect-button-more padding-all">
    <a class="btn-submit box-flex br-5 min-btn" type="button" href="<?php echo url('user/login/logout');?>">退出</a>
</div>
<!--updata-admin-->
<div class=" my-sex-box">
	<div class="flow-consignee margin-lr">
			<ul class="g-s-i-title-3 dis-box text-center user-sex">
				<li class="box-flex  sex-default-color">
					<a href="javascript:;" class="<?php if($user_sex == 1) { ?>active<?php } ?>">
						<i class="iconfont icon-nan my-sex-size" ></i>
                    	<input type="radio" name="sex" value="1"/><h4 class="ellipsis-one f-03">男</h4>
					</a>
				</li>
				<li class="box-flex sex-default-color" >
					<a href="javascript:;" class="sex-nv <?php if($user_sex == 2) { ?>active<?php } ?>">
						<i class="iconfont icon-nv my-sex-size" ></i>
                      	<input type="radio" name="sex" value="2"/><h4 class="ellipsis-one f-03">女</h4>
					</a>
				<li>
			</ul>
			<div class="ect-button-more dis-box m-top12 updata-top my-sex-close">
              <button  class="btn-submit box-flex br-5 min-btn" type="submit" value="submit">确定</button>
			</div>
	</div>
</div>  

<!--快捷导航-->
     <div class="filter-top filter-top-index" id="scrollUp">
                <i class="iconfont icon-jiantou"></i>
                <span>顶部</span>
</div>
<ul id="navs" data-open="+" data-close="+">
	<li><a href="<?php echo url('user/index/index');?>"><i class="iconfont icon-geren"></i></a> </li>
	<li><a href="<?php echo url('search/index/index');?>"><i class="iconfont icon-sousuo"></i></a> </li>
	<li><a href="<?php echo url('category/index/index');?>"><i class="iconfont icon-caidan"></i></a> </li>
	<li><a href="<?php echo url('cart/index/index');?>"><i class="iconfont icon-gouwuche"></i></a> </li>
	<li><a href="<?php echo url('/');?>"><i class="iconfont icon-home"></i></a> </li>
</ul>

<script>
   (function(){
	var ul=$("#navs"),li=$("#navs li"),i=li.length,n=i-1,r=120;
	ul.click(function(){
		$(this).toggleClass('active');
		if($(this).hasClass('active')){
			for(var a=0;a<i;a++){
			    var x = -r*Math.cos(90/n*a*(Math.PI/180));
				var y = -r*Math.sin(90/n*a*(Math.PI/180));

				li.eq(a).css({
					'transition-delay':""+(10*a)+"ms",
					'-webkit-transition-delay':""+(10*a)+"ms",
					'-o-transition-delay':""+(10*a)+"ms",
				/*
   				    'transform':"translate("+ x +"px,"+ y +"px)",
					'-webkit-transform':"translate("+x+"px,"+ y +"px)",
					'-o-transform':"translate("+ x +"px,"+ y +"px)",
					'-ms-transform':"translate("+ x +"px,"+ y +"px)",
				*/
					'margin-left': x + 'px',
					'margin-top': y + 'px',
				});
			}
		}else{
			li.removeAttr('style');
		}
	});
})($);
</script>

<nav class="commom-nav dis-box ts-5">
        <div class="left-icon">
            <div class="nav-icon"><i class="iconfont icon-jiantou"></i></div>
            <div class="filter-top filter-top-index" id="scrollUp2">
                <i class="iconfont icon-jiantou"></i>
                <span>顶部</span>
            </div>
        </div>
        <div class="right-cont box-flex">
            <ul class="nav-cont">
                <li>
                      <a href="<?php echo url('/');?>">
                        <i class="iconfont icon-home"></i>
                        <p>首页</p>
                      </a>  
                </li>
                <li>
                    <a href="<?php echo url('search/index/index');?>">
                         <i class="iconfont icon-sousuo"></i>
                         <p>搜索</p>
                    </a>  
                </li>
                <li>
                     <a href="<?php echo url('category/index/index');?>">
                         <i class="iconfont icon-caidan"></i>
                         <p>分类</p>
                     </a> 
                </li>
                <li>
                     <a href="<?php echo url('cart/index/index');?>">
                         <i class="iconfont icon-gouwuche"></i>
                         <p>购物车</p>
                      </a> 
                </li>
                <li>
                    <a href="<?php echo url('user/index/index');?>">
                         <i class="iconfont icon-geren"></i>
                         <p>个人中心</p>
                    </a> 
                </li>

				
				
				 
                </ul>
        </div>
    </nav>
    <div class="common-show"></div>

<script>
	$(function($) {
		$(".onclik-sex").click(function() {
			$(".my-sex-box").addClass("current");
		});
		$(".my-sex-close").click(function() {
			$(".my-sex-box").removeClass("current");
		});
	});
	$(function() {
		$('.sex-default-color').click(function() {
			for (var i = 0; i < $('.sex-default-color').size(); i++) {
				if (this == $('.sex-default-color').get(i)) {
					$('.sex-default-color').eq(i).children('a').addClass('active');
				} else {
					$('.sex-default-color').eq(i).children('a').removeClass('active');
				}
			}
		})
	})

$(".user-sex").click(function(){
	var sex = $(".active input[name='sex']").val();
   
	$.post("<?php echo url('user/profile/editprofile');?>",{sex:sex},function(data){
		$("#sex").text(data.sex);
	},"json");
	
})
<?php if($is_wechat == 0) { ?>
/* 修改头像*/
$('.user-head-img-box-1 img').click(function(){
	$('#headimg').click();
});
$('#headimg').change(function(e){
	var file = e.target.files[0];
	var form = new FormData();
	form.append('img', file);

	var xhr;
	if(window.ActiveXObject)
	{
		xhr = new ActiveXObject("Microsoft.XMLHTTP");
	}
	else if(window.XMLHttpRequest)
	{
		xhr = new XMLHttpRequest();
	}
	xhr.onreadystatechange = function(){
		if(xhr.readyState == 4){
			if(xhr.status == 200 || xhr.status == 0){
				var res = xhr.responseText;
				res = JSON.parse(res);
				if(res.error == 0){
					$('.user-head-img-box-1 img').attr('src', '../' + res.path);
				}
			}
		}
	};
	xhr.open("post", "<?php echo url('changeheader');?>", true);
	xhr.send(form);
});
<?php } ?>
</script>
</body>
</html>