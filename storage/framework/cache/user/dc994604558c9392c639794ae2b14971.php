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
<div class="con mb-7">
	<header class="user-header-box">
		<div class="padding-all">
			<a href="<?php echo url('profile/index');?>">
				<?php if($info['user_picture'] !=='' ) { ?>
				<div class="heaer-img"><img src="<?php if($info['user_picture'] == '/mobile/statics/img/no_image.jpg') { ?>/mobile/statics/img/no_head.png<?php } else { echo ($info['user_picture']); } ?>"></div>
				<?php } else { ?>
				<div class="heaer-img"><img src="<?php echo elixir('img/no_head.jpg');?>"></div>
				<?php } ?>
			</a>
			<a href="<?php echo url('profile/index');?>" class="box-flex">
				<div class="header-admin">

					<h4 class="ellipsis-one f-06"><?php echo ($info['nick_name']); ?></h4>
					<p class="hide color-whie f-02 m-top02"><?php echo ($rank['user_rank']['rank_name']); ?></p>
				</div>
			</a>
			<div class="header-icon">
				<a class="youxiang" href="<?php echo url('user/index/helpcenter');?>">
					<?php if($cache_info ) { ?>
					<div class="tishi-tag"></div>
					<?php } ?>
					<label class="f-02">帮助</label>
				</a>

				<a class="shezhi" href="<?php echo url('profile/index');?>">
					<i class="iconfont icon-shezhi"></i>
				</a>
			</div>
		</div>
		</a>
	</header>
	<!--order-list-->
	<section class="b-color-f user-function-list">
		<a href="<?php echo url('order/index',array('status'=>0));?>">
			<div class="dis-box padding-all wallet-bt">
				<h3 class="box-flex"><i class="iconfont icon-iconfontquanbudingdan color-red"></i>我的订单</h3>
				<div class="box-flex f-03 text-right onelist-hidden jian-top">全部订单</div>
				<span class="t-jiantou"><i class="iconfont icon-jiantou tf-180 jian-top"></i></span>
			</div>
		</a>
		<ul class="user-order-list g-s-i-title-2 dis-box text-center ">
			<a href="<?php echo url('user/order/index', array('status'=>1));?>" class="box-flex">

				<li>
					<h4><i class="iconfont icon-daifukuan"></i></h4>
					<p class="t-remark3">待付款</p>
					<?php if($pay_count > 0) { ?>
					<div class="user-list-num"><?php echo $pay_count; ?></div>
					<?php } ?>
				</li>
			</a>
			<?php if($team) { ?>
			<a href="<?php echo url('team/user/index');?>" class="box-flex">

				<li>
					<h4><i class="iconfont icon-daifukuan"></i></h4>
					<p class="t-remark3">待拼团</p>
					<?php if($team_num > 0) { ?>
					<div class="user-list-num"><?php echo $team_num; ?></div>
					<?php } ?>
				</li>
			</a>
			<?php } ?>
			<a href="<?php echo url('user/order/index',array('status'=>2));?>" class="box-flex">
				<li>
					<h4><i class="iconfont icon-wodetubiaosvg03"></i></h4>
					<p class="t-remark3">待收货</p>
					<?php if($confirmed_count > 0) { ?>
					<div class="user-list-num"><?php echo $confirmed_count; ?></div>
					<?php } ?>
				</li>
			</a>
			<a href="<?php echo url('user/index/comment_list');?>" class="box-flex">
				<li>
					<h4><i class="iconfont icon-daipingjia"></i></h4>
					<p class="t-remark3">待评价</p>
					<?php if($not_comment > 0) { ?>
					<div class="user-list-num"><?php echo $not_comment; ?></div>
					<?php } ?>
				</li>
			</a>
			<a href="<?php echo url('user/refound/index');?>" class="box-flex">
				<li>
					<h4><i class="iconfont icon-tuihuanhuo"></i></h4>
					<p class="t-remark3">退换货</p>
					<?php if($return_count > 0) { ?>
					<div class="user-list-num"><?php echo $return_count; ?></div>
					<?php } ?>
				</li>
			</a>
		</ul>
	</section>
	<!--money-list-->
	<section class="m-top08 user-function-list b-color-f">
		<a href="#" data-url="<?php echo url('user/account/index');?>">
			<div class="dis-box padding-all wallet-bt">
				<h3 class="box-flex"><i class="iconfont icon-qianbao  color-fe"></i>我的钱包</h3>
				<div class="hide box-flex f-03 text-right onelist-hidden jian-top">资金管理</div>
				<span class="t-jiantou hide"><i class="iconfont icon-jiantou tf-180 jian-top"></i></span>
			</div>
		</a>
		<ul class="user-order-list  dis-box text-center">
				<div style="display:none">
					<a href="<?php echo url('user/account/index');?>" class="box-flex">
						<li>
							<h4 class="ellipsis-one"><?php echo ($user_pay['user_money']); ?></h4>
							<p class="t-remark3">余额</p>
						</li>
					</a>
					<a href="<?php echo url('user/account/bonus');?>" class="box-flex">

						<li>
							<h4 class="ellipsis-one"><?php echo $bonus; ?></h4>
							<p class="t-remark3">红包</p>
						</li>
					</a>
				</div>
			<a href="javascript:;" class="box-flex">
				<li>
					<h4 class="ellipsis-one"><?php echo ($user_pay['pay_points']); ?></h4>
					<p class="t-remark3">积分</p>
				</li>
			</a>
			<a href="<?php echo url('user/account/coupont');?>" class="box-flex">
				<li>
					<?php if($couponses == '' ) { ?>
					<h4 class="ellipsis-one">0</h4> <?php } else { ?>
					<h4 class="ellipsis-one"><?php echo $couponses; ?></h4> <?php } ?>
					<p class="t-remark3">优惠券</p>
				</li>
			</a>
		</ul>
	</section>
	<!--function-nav-list-->
	<nav class="b-color-f user-nav-box m-top08">
		<div class="box ul-4 text-c b-color-f">
			<a href="<?php echo url('user/index/collectionlist');?>">
				<label><i class="iconfont icon-favorgoods color-fe"></i></label>
				<p class="f-02 col-7">收藏的商品</p>
			</a>
			<a href="<?php echo url('user/index/storelist');?>" class="">
				<label><i class="iconfont icon-collect-shop color-289"></i></label>
				<p class="f-02 col-7">关注的店铺</p>
			</a>

			<?php if($share) { ?>
			<a class="hide" href="<?php echo url('user/index/affiliate');?>">
				<label><i class="iconfont icon-fenxiang1 color-e72"></i></label>
				<p class="f-02 col-7">我的分享</p>
			</a>
			<?php } ?>
			<a class="hide" href="<?php echo url('index/messagelist');?>">
				<label><i class="iconfont icon-bangzhu color-f9c"></i></label>
				<p class="f-02 col-7">消息</p>
			</a>
			<?php if($drp) { ?>
			<a class="hide" href="<?php echo url('drp/index/index');?>">
				<label><i class="iconfont icon-dianpu1 color-red"></i></label>
				<p class="f-02 col-7">我的微店</p>
			</a>
			<?php } ?>
			<a class="hide" href="<?php echo url('user/crowd/index');?>">
				<label><i class="iconfont icon-zhongchouxuanzhong color-ff7"></i></label>
				<p class="f-02 col-7">微筹广场</p>
			</a>
			<?php if($team) { ?>
			<a href="<?php echo url('team/index/index');?>">
				<label><i class="iconfont icon-pintuan color-98 color-f9c"></i></label>
				<p class="f-02 col-7">拼团频道</p>
			</a>
			<?php } ?>
			<a href="<?php echo url('merchants/index/index');?>">
				<label><i class="iconfont icon-iconfontruzhu color-98"></i></label>
				<p class="f-02 col-7">商家入驻</p>
			</a>
			<a href="<?php echo url('user/index/history');?>">
				<label><i class="iconfont icon-liulanjilu color-c78"></i></label>
				<p class="f-02 col-7">浏览记录</p>
			</a>

		</div>
	</nav>

</div>
<!--悬浮菜单s-->
<div class="filter-top" id="scrollUp">
	<i class="iconfont icon-jiantou"></i>
</div>
<footer class="footer-nav dis-box">
	<a href="<?php echo url('/');?>" class="box-flex nav-list">
		<i class="nav-box i-home"></i><span>首页</span>
	</a>
	<a href="<?php echo url('category/index/index');?>" class="box-flex nav-list">
		<i class="nav-box i-cate"></i><span>分类</span>
	</a>
	<a href="<?php echo url('search/index/index');?>" class="box-flex nav-list">
		<i class="nav-box i-shop"></i><span>搜索</span>
	</a>
	<a href="<?php echo url('cart/index/index');?>" class="box-flex position-rel nav-list">
		<i class="nav-box i-flow"></i><span>购物车</span>
	</a>
	<?php if($filter) { ?>
	<a href="<?php echo url('drp/user/index');?>" class="box-flex nav-list active">
		<i class="nav-box i-user"></i><span><?php echo $custom; ?>中心</span>
	</a>
	<?php } elseif ($community) { ?>
	<a href="<?php echo url('community/index/index');?>" class="box-flex nav-list active">
		<i class="nav-box i-user"></i><span>社区</span>
	</a>
	<?php } else { ?>
	<a href="<?php echo url('user/index/index');?>" class="box-flex nav-list active">
		<i class="nav-box i-user"></i><span>我</span>
	</a>
	<?php } ?>
</footer>
<!--悬浮菜单e-->
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

<!--悬浮菜单e-->
		<script>
			/*店铺信息商品滚动*/
			var swiper = new Swiper('.j-g-s-p-con', {
				scrollbarHide: true,
				slidesPerView: 'auto',
				centeredSlides: false,
				grabCursor: true
			});

       $(function(){
        //清除搜索记录
        var history = <?php if($history) { echo $history; } else { ?>""<?php } ?>;
        $(".clear_history").click(function(){
            if(history){
	            $.get("<?php echo url('user/index/clear_history');?>", '', function(data){
	        		if(data.status == 1){
			            $(".clearHistory").remove();
	                }
	            }, 'json');
            }
        });
    })
</script>
</body>

</html>