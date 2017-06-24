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
<section class="index-header ts-2">

    <!--search-->
        <section class="search box banner-search ts-3">
            <div class="text-all position-rel dis-box j-text-all" style="padding:.66rem 0">
                <?php if(C('shop.use_lbs')) { ?>
                <a href="<?php echo url('location/index/index');?>"><div class="dis-box search-logo"><div class="location-left-cont onelist-hidden"><?php echo ($current_city['region_name']); ?></div><div class="box-flex"><i class="iconfont icon-moreunfold"></i></div></div></a>
                <?php } ?>
               <div class="box-flex input-text n-input-text i-search-input">
                    <a href="<?php echo url('search/index/index');?>" class="a-search-input"></a>
                    <div class="j-input-text nav-soso"><i class="iconfont icon-sousuo"></i>商品/店铺搜索</div>
                </div>
				
				<!-- 屏蔽商城自带的客服系统，通过行聊沟通
                <div class="search-left index-xiaoxi position-rel">
                    <a href="<?php echo url('user/index/messagelist');?>"><i class="iconfont icon-xiaoxi1 isxiaoxi1color"></i><em class="position-abo active"></em></a>
                </div>
				-->
				
            </div>
        </section>
    </section>
    <div class="index-banner new-index-banner swiper-container box position-rel banner-first" id="new-index-banner">
        <div class="swiper-wrapper">
        <?php echo insert_ads(array('id'=>256, 'num'=>6));?>
        </div>
        <!-- 分页器 -->
        <div class="swiper-pagination br-half text-c banner-first-pagination">111</div>
        <!--linear-->
        <div class="linear"></div>
    </div>
    <!--menu-->
    <div class="nav-img">
        <!--<img class="img" id="nav-bg-img" src="/mobile/statics/img/ww.jpg">-->
        <nav class="ptb-1 index-nav" >
        <ul class="box ul-5 text-c">
            <?php $n=1;if(is_array($nav)) foreach($nav as $key=>$list) { ?>
            <li>
                <a href="<?php echo ($list['url']); ?>">
                    <img src="<?php echo get_data_path($list['pic'], 'attached/nav');?>">
                    <p class="m-top02"><?php echo ($list['name']); ?></p>
                </a>
            </li>
            <?php $n++;}unset($n); ?>
        </ul>
    </nav>
    </div>
    <!--index-new-->
    <div class="b-color-f mb-1" style="padding-bottom:1.1rem">
	<section class="dis-box index-new  position-rel">
		<div class="index-new-left">
			<div class="new-icon">
				<img src="<?php echo elixir('img/new-icon.png');?>">
			</div>
		</div>
		<div class="box-flex">
			<!-- Swiper -->
			<div class="swiper-container index-new-list">
				<div class="swiper-wrapper">
                    <?php $n=1;if(is_array($article)) foreach($article as $vo) { ?>
					<div class="swiper-slide swiper-no-swiping">
						<a class="product-div-link" href="<?php echo $vo['url']; ?>"></a>
						<div class="index-new-cont">
							<h4 class="col-3 f-04 onelist-hidden"><?php echo $vo['title']; ?></h4>
							<p class="col-9 f-02"><?php echo $vo['add_time']; ?></p>
						</div>
					</div>
                    <?php $n++;}unset($n); ?>
				</div>
			</div>
		</div>
		<div class="index-new-right">
			<!--<span class="t-jiantou t-remark fr"><a href="<?php echo url('article/index/index');?>">更多<i class="iconfont icon-more f-2"></i></a></span>-->
              <a href="<?php echo url('article/index/index');?>&category=23"><div class="index-jt-more" style="color:#444;right:0;padding: 0 1.1rem 0 .7rem;font-size: 1.2rem;">更多</div></a>
		</div>
        <div class="index-new-bor left-bor"></div>
        <div class="index-new-bor right-bor"></div>
	</section>
    </div>
    <script type="text/javascript">
        var gmt_end_time = <?php if($end_time) { echo $end_time; } else { ?>0<?php } ?>;
        var day = '<em class="color-whie hour">';
        var hour = '</em> : <em class="color-whie mini">';
        var minute = '</em> : <em class="color-whie sec">';
        var second = '</em>';
        var end = '';
        onload = function(){
            try {onload_leftTime();}
            catch (e) {}
        }
    </script>
    <!--discount-->
    <div class="box mb-1 title inx-ms" style="display:none;">
        <div class="box padding-all b-color-f wallet-bt p-r">
            <h3 class="sale-title position-rel" >
                <span class="fl inx-ms-time">限时<i class="iconfont">&#xe60b;</i>秒杀</span>
                <span class="color-dark" id="leftTime"></span>
            </h3>
            <a href="<?php echo url('category/index/search',array('intro'=>'promotion'));?>"><div class="index-jt-more sale-bg">更多  ></div></a>
        </div>
      <section class="b-color-f store-box-list">
                    <div class="product-one-list of-hidden scrollbar-none j-g-s-p-con swiper-container-horizontal">
                        <div class="swiper-wrapper ">
                             <!-- <?php $n=1; if(is_array($promotion_goods)) foreach($promotion_goods as $key => $promotion) { ?> -->
                            <li class="swiper-slide swiper-slide-active">
                                    <a href="<?php echo ($promotion['url']); ?>">
                                    <div class="index-store-cont">
                                        <img class="img" src="<?php echo ($promotion['goods_thumb']); ?>">
                                    </div>
                                    <p class="index-price text-c"><?php echo ($promotion['current_price']); ?><br><del><?php echo ($promotion['shop_price']); ?></del></p>
                                    </a>
                            </li>
                            <!-- <?php $n++;}unset($n); ?> -->
                            <div class="swiper-slide more-list-box">
                                <a href="<?php echo url('category/index/search',array('intro'=>'promotion'));?>">
                                    <p class="text-c">查看更多</p>
                                    <i class="iconfont icon-jiantou1 n-icon-jiantou1"></i>
                                </a>
                            </div>
                        </div>
                </div>
        </section>
    </div>
    <!--banner2-->
    <div class="index-banner swiper-container box mb-1 banner-second">
        <div class="swiper-wrapper">
            <?php echo insert_ads(array('id'=>257, 'num'=>3));?>
        </div>
    </div>
    <!--recom-->
    <div class="box mb-1 title inx-ms  m-top08">
        <div class="b-color-f p-r">
            <!-- <img class="img" src="/mobile/statics/img/guangao.png"> -->
			<div class="tit-mobile">精品推荐</div>
            <a href="<?php echo url('category/index/search',array('intro'=>'best'));?>"><div class="index-jt-more jinpin-bg">更多  ></div></a>   
        </div>
        <section class="product-list-medium">
            <ul>
              <!-- <?php $n=1; if(is_array($best_goods)) foreach($best_goods as $key => $best) { ?> -->
              <li>
                <div class="product-div">
                  <a class="product-div-link" href="<?php echo ($best['url']); ?>"></a>
                  <img class="product-list-img" src="<?php echo ($best['goods_thumb']); ?>" />
                  <div class="product-text  index-product-text">
                      <h4><?php echo ($best['goods_name']); ?></h4>
                      <p><span class="p-price color-red"><?php echo ($best['current_price']); ?></span></p>
                    <a style="display:none;" href="<?php echo ($best['link_url']); ?>"><div class="index-btn-xs">找相似</div></a>
					<a href="javascript:void(0)" onclick="addToCart_quick(<?php echo ($best['goods_id']); ?>)" class="icon-flow-cart fr j-goods-attr"><i class="iconfont icon-gouwuche"></i></a>
                  </div>
                </div>
              </li>
              <!-- <?php $n++;}unset($n); ?> -->
            </ul>
        </section>
    </div>
    <!--hot-->
    <div class="box mb-1 b-color-f" style="display:none;">
        <div class="box padding-all  wallet-bt">
            <h3 class="recom-title">
                热门大牌
                <span>最全最时尚的大牌汇集</span>
                <span class="t-jiantou t-remark fr">更多<i class="iconfont icon-more"></i></span>
            </h3>
        </div>
        <div class="hot-container-div">
            <i class="iconfont icon-back"></i>
            <div class="swiper-container hot-container">
                <ul class="swiper-wrapper">
                    <!-- <?php $n=1;if(is_array($brand_list)) foreach($brand_list as $vo) { ?> -->
                    <li class="swiper-slide ">
                        <a href="javascript:; ">
                            <img src="<?php echo ($vo['brand_logo']); ?>" />
                        </a>
                    </li>
                    <!-- <?php $n++;}unset($n); ?> -->
                </ul>
                <!-- 导航按钮 -->
            </div>	<i class="iconfont icon-more"></i>
        </div>
    </div>
    <!--banner3-->
    <div class="index-banner  swiper-container box mb-1 banner-third">
        <div class="swiper-wrapper">
        <?php echo insert_ads(array('id'=>258, 'num'=>3));?>
        </div>
    </div>
    <!--brand-->
    <?php if($store) { ?>
    <div class="p-r b-color-f m-top08">
            <!-- <img class="img" src="/mobile/statics/img/dianpujie.png"> -->
			<div class="tit-mobile">店铺街</div>
            <a href="<?php echo url('store/index/index');?>"><div class="index-jt-more dianpu-bg">更多 ></div></a>   
    </div>
    <section class="store-new-box b-color-f">
                <div class="of-hidden scrollbar-none j-g-s-p-con swiper-container-horizontal">
                    <div class="swiper-wrapper brand-list">
                     <!--<?php $n=1; if(is_array($store)) foreach($store as $key => $val) { ?>-->

                        <li class="swiper-slide swiper-slide-active">
                              <a href="<?php echo ($val['url']); ?>">
                            <div class="brand-img">
                                <img src="<?php echo ($val['street_thumb']); ?>" />
                                <div class="brand-logo">
                                    <img src="<?php echo ($val['brand_thumb']); ?>"/>
                                </div>
                            </div>
                             <ul class="ul-2 index-store-list">
                                <!--<?php $n=1; if(is_array($val['goods_list'])) foreach($val['goods_list'] as $k => $v) { ?>-->
                                <li>
                                    <img src="<?php echo ($v['goods_thumb']); ?>" />
                                </li>
                                <!--<?php $n++;}unset($n); ?>-->
                            </ul>   
                               </a>   

                        </li>
                         <!--<?php $n++;}unset($n); ?>-->
                          <div class="swiper-slide more-list-box">
                                <a href="<?php echo url('store/index/index');?>">
                                    <p class="text-c">查看更多</p>
                                    <i class="iconfont icon-jiantou1 n-icon-jiantou1"></i>
                                </a>
                          </div>
                    </div>
            </div>
    </section>
    <?php } ?>
    <!--like-->
     <div>
            <div class="tit-mobile tit-mobile-center">猜你喜欢</div>
            <a href="<?php echo url('store/index/index');?>"></a>   
    </div>
    <section class="product-list j-product-list product-list-medium" data="1">
		<script id="j-product" type="text/html">
		<ul>
			<%each list as goods%>
			<li>
				<div class="product-div">
					<a class="product-div-link" href="<%goods.url%>"></a>
					<img class="product-list-img" src="<%goods.goods_thumb%>" />
					<div class="product-text  index-product-text">
						  <h4><%goods.goods_name%></h4>
						  <p><span class="p-price color-red "><%#goods.current_price%><small></small></span></p>
						  <a style="display:none;" href="<%goods.link_url%>"><div class="index-btn-xs">找相似</div></a>
						  <a href="javascript:void(0)" onclick="addToCart_quick(<%goods.goods_id%>)" class="icon-flow-cart fr j-goods-attr"><i class="iconfont icon-gouwuche"></i></a>
					  </div>
					  <%if goods.shopnamesuffix == "旗舰店"%><span class="shop_sign_product"></span><%/if%>
				</div>
			</li>
			<%/each%>
		</ul>
		</script>
    </section>
    <div class="n-footer-box" style="display: none;">
        <ul class="n-footer-minbox">
            <li>
                <a href="http://m.ecmoban.com/index.php?m=touch&amp;c=index&amp;a=download">
                    <div class="n-footer-img-box"><img src="<?php echo elixir('img/footer-a.png');?>"></div>
                    <p class="footer-tit1">客户端</p>
                </a>
            </li>
            <li>
                <a href="javascript:;">
                    <div class="n-footer-img-box"><img src="<?php echo elixir('img/footer-c.png');?>"></div>
                    <p class="footer-tit">触屏版</p>
                </a>
            </li>
            <li>
                <a href="__HOST__/">
                    <div class="n-footer-img-box"><img src="<?php echo elixir('img/footer-b.png');?>"></div>
                    <p class="footer-tit1">电脑版</p>
                </a>
            </li>
        </ul>
    </div>

    <footer class="padding-all text-center t-remark" style="display: none;">
        <?php echo $copyright; ?>
        &copy; <?php echo date('Y');?> <?php echo C('shop.shop_name');?> 版权所有，并保留所有权利。
    </footer>
	<div class="footer-cont"><span><img src='<?php echo elixir('img/loading1.gif');?>'>正在加载……</span></div>
	
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
			    var x = r*Math.cos(90/n*a*(Math.PI/180));
				var y = -r*Math.sin(90/n*a*(Math.PI/180));

				li.eq(a).css({
					'transition-delay':""+(0.5*a)+"ms",
					'-webkit-transition-delay':""+(0.5*a)+"ms",
					'-o-transition-delay':""+(0.5*a)+"ms",
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
	
    <!--回到顶部
    <nav class="commom-nav dis-box ts-5"  id="commom-nav" style="top:77%">
        <div class="left-icon">
            <div class="filter-top filter-top-index" id="scrollUp">
                <i class="iconfont icon-jiantou"></i>
                <span>顶部</span>
            </div>
        </div>
    </nav>
	-->
	
	<!--
	<footer class="footer-nav dis-box" id="footer-nav">
		<a href="<?php echo url('/');?>" class="box-flex nav-list active">
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
		<a href="<?php echo url('drp/user/index');?>" class="box-flex nav-list">
			<i class="nav-box i-user"></i><span><?php echo $custom; ?>中心</span>
		</a>
		<?php } elseif ($community) { ?>
		<a href="<?php echo url('community/index/index');?>" class="box-flex nav-list">
			<i class="nav-box i-user"></i><span>社区</span>
		</a>
		<?php } else { ?>
		<a href="<?php echo url('user/index/index');?>" class="box-flex nav-list">
			<i class="nav-box i-user"></i><span>我</span>
		</a>
		<?php } ?>
	</footer>
	悬浮菜单e-->
</div>
<!--浏览记录-->
<div class="index-history dis-box" id="index-history">
  <div class="box-flex">
    <h4 class="f-03"><strong>刚刚您在浏览:</strong></h4>
    <p class="f-02 col-8 m-top04 onelist-hidden">"衣服"相关产品514</p>
  </div>
  <div class="right">
    <a class="history-title" href="javascript:;">继续浏览</a><label>关闭</label>
 </div>
</div>

  

<!-- <script src="/mobile/statics/js/jquery.cookie.js"></script>  -->
<script type="text/javascript" src="/mobile/statics/js/jquery.wheelmenu.min.js"></script>
<script type="text/javascript ">

    function getCookie(cookieName){  
        var cookieValue="";  
        if (document.cookie && document.cookie != '') {   
            var cookies = document.cookie.split(';');  
            for (var i = 0; i < cookies.length; i++) {   
                 var cookie = cookies[i];  
                 if (cookie.substring(0, cookieName.length + 2).trim() == cookieName.trim() + "=") {  
                       cookieValue = cookie.substring(cookieName.length + 2, cookie.length);   
                       break;  
                 }  
             }  
        }   
        return cookieValue;  
    }  

    $(function($) { 
        /** 有浏览商品则底部显示提示 */
        var cookie_histtory = getCookie('index-history');
        if(cookie_histtory != undefined && history.length == 1){
            cookie_histtory = JSON.parse(cookie_histtory);
            $('.index-history .onelist-hidden').text(cookie_histtory.name);
            $('.index-history .history-title').attr('href', cookie_histtory.url);
            $('.index-history').show();
            //
            $.cookie('index-history', null, {expires: -1});
        }else{
           $('.index-history').hide();
        }
        //浏览历史隐藏 
        var int=setTimeout("clock()",6000);
        $(".index-history .right label").click(function(){
            $(".index-history").hide();
        });
        //****
        //获取nav高度，赋值给背景
        function navList(){
            var nav = $("#index-nav").outerHeight() + "px" ;
            $("#nav-img").css("height",nav);
        }
        navList()
        //页面发生变化时触发事件
        $(window).resize(function(){
            navList()
        });
        var footerHeight = $("#footer-nav").outerHeight() + -2 +"px";
        $("#index-history").css("bottom",footerHeight);

        /*立即关注*/
        if ($(".ect-header-banner").hasClass("ect-header-banner")) {
                $(".new-index-banner").css({"paddingTop": "5rem",})
        } else {
                $(".new-index-banner").css({"paddingTop": "0rem",})
        }

    	var swiper = new Swiper('.index-new-list', {
					pagination: '.swiper-pagination',
					paginationClickable: true,
					direction: 'vertical',
					loop: true,
					grabCursor: true,
					autoplayDisableOnInteraction: false,
					autoplay: 4000,
					speed: 700,
					freeMode: true,
					noSwiping : true,
				});
        var mySwiper = new Swiper('.banner-first', {
            autoplay: 4000,
            loop: true,
            pagination: '.banner-first-pagination'
        });
        var mySwiper = new Swiper('.banner-second', {
            autoplay: 5000,
            pagination: '.banner-second-pagination'
        });
        var mySwiper = new Swiper('.banner-third', {
            autoplay: 5000,
            pagination: '.banner-third-pagination'
        });
        var mySwiper = new Swiper('.recom-container', {
            pagination: '.recom-pagination',
            slidesPerView: 1,
            paginationClickable: true
        });
        var mySwiper = new Swiper('.brand-container', {
            pagination: '.brand-pagination',
            slidesPerView: 1,
            paginationClickable: true
        });
        var mySwiper = new Swiper('.hot-container', {
            slidesPerView: 4,
            paginationClickable: true,
            nextButton: '.swiper-button-next',
            prevButton: '.swiper-button-prev'
        });

    });
	var url = "<?php echo url('async');?>";
	$('.product-list').infinite({url: url, template:'j-product'});
			$(document).ready(function() {
            $(window).scroll(function() {
                if ($(document).scrollTop() >= 0<$(document).height() - $(window).height()) {
                       $(".footer-cont").html("<span><img src='<?php echo elixir('img/loading1.gif');?>'>正在加载……</span>")
                }
                if ($(document).scrollTop() >= $(document).height() - $(window).height()) {

			        $(".footer-cont").html("<span>没有更多了</span>")

                }
            });
        });
    function clock(){
        $(".index-history").hide();
    }
</script>
   
</body>
</html>