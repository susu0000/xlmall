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
<body>
        <div class="con" id="checkPage">
            <div class="category">
                <section class="search category-search">
                    <div class="text-all dis-box j-text-all text-all-back">
                        <a class="a-icon-back j-close-search" href="javascript:history.go(-1);"><i class="iconfont icon-back"></i></a>
                  <div class="box-flex input-text n-input-text i-search-input">
                        <a class="a-search-input" href="<?php echo url('search/index/index');?>"></a>
                        <div class="j-input-text nav-soso"><i class="iconfont icon-sousuo"></i><?php echo $page_title; ?></div>
                    </div>
                        <?php if($cat_id) { ?>
                        <a href="javascript:void(0)" class="s-filter j-s-filter">筛选</a>
                        <?php } ?>
                    </div>
                </section>
                <section class="product-sequence dis-box">
                    <?php if($order == 'ASC' && $sort == 'goods_id') { ?>
                    <a class="box-flex a-change active" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'goods_id', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">综合<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } elseif ($order == 'DESC' && $sort == 'goods_id') { ?>
                    <a class="box-flex active" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'goods_id', 'order'=>'ASC', 'keyword'=>$keywords));?>#goods_list">综合<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } else { ?>
                    <a class="box-flex" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'goods_id', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">综合<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } ?>

                    <?php if($order == 'ASC' && $sort == 'last_update') { ?>
                    <a class="box-flex a-change <?php if($sort == 'last_update') { ?>active<?php } ?>" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'last_update', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">新品<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } elseif ($order == 'DESC' && $sort == 'last_update') { ?>
                    <a class="box-flex <?php if($sort == 'last_update') { ?>active<?php } ?>" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'last_update', 'order'=>'ASC', 'keyword'=>$keywords));?>#goods_list">新品<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } else { ?>
                    <a class="box-flex <?php if($sort == 'last_update') { ?>active<?php } ?>" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'last_update', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">新品<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } ?>

                    <?php if($order == 'ASC' && $sort == 'sales_volume') { ?>
                    <a class="box-flex a-change  <?php if($sort == 'sales_volume') { ?>active<?php } ?>" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'sales_volume', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">销量<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } elseif ($order == 'DESC' && $sort == 'sales_volume') { ?>
                    <a class="box-flex <?php if($sort == 'sales_volume') { ?>active<?php } ?>" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'sales_volume', 'order'=>'ASC', 'keyword'=>$keywords));?>#goods_list">销量<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } else { ?>
                    <a class="box-flex <?php if($sort == 'sales_volume') { ?>active<?php } ?>" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'sales_volume', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">销量<i class="iconfont icon-xiajiantou"></i></a>
                     <?php } ?>

                    <?php if($order == 'ASC' && $sort == 'shop_price') { ?>
                    <a class="box-flex a-change active" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'shop_price', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">价格<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } elseif ($order == 'DESC' && $sort == 'shop_price') { ?>
                    <a class="box-flex active" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'shop_price', 'order'=>'ASC', 'keyword'=>$keywords));?>#goods_list">价格<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } else { ?>
                    <a class="box-flex" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'shop_price', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">价格<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } ?>
                    
                    <?php if($order == 'ASC' && $sort == 'comments_number') { ?>
                    <a class="box-flex a-change active" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'comments_number', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">点评<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } elseif ($order == 'DESC' && $sort == 'comments_number') { ?>
                    <a class="box-flex active" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'comments_number', 'order'=>'ASC', 'keyword'=>$keywords));?>#goods_list">点评<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } else { ?>
                    <a class="box-flex" href="<?php echo url('category/index/products', array('id'=>$category, 'intro'=>$intro, 'display'=>$display, 'brand'=>$brand_id, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'page'=>$page, 'sort'=>'comments_number', 'order'=>'DESC', 'keyword'=>$keywords));?>#goods_list">点评<i class="iconfont icon-xiajiantou"></i></a>
                    <?php } ?>
                    
                    <a class="a-sequence j-a-sequence"><i class="iconfont icon-viewlist" data="2"></i></a>
                </section>
                <!-- 内容 -->
                <section class="product-list j-product-list  product-list-small" data="2">
                    <script id="j-product" type="text/html">
                    <%if totalPage > 0%>
                    <ul>
                        <%each list as goods%>
                        <li>
                            <div class="product-div">
                                <a href="<%goods.url%>"><img class="product-list-img" src="<%goods.goods_thumb%>" /></a>
                                <div class="product-text">
                                    <a href="<%goods.url%>"><h4><%goods.goods_name%></h4></a>
                                    <p class="dis-box p-t-remark"><span class="box-flex">库存:
                                    <%if goods.goods_number%>
                                    <%goods.goods_number%><%goods.measure_unit%>
                                    <%else%>
                                      0
                                    <%/if%>
                                    </span><span class="box-flex">销量:<%goods.sales_volume%><%goods.measure_unit%></span></p>
                                    <p>
                                    <span class="p-price t-first ">
                                        <%if goods.promote_price%>
                                        <%#goods.promote_price%>
                                        <%else%>
                                        <%#goods.shop_price%>
                                        <%/if%>
                                        <small><del><%#goods.market_price%></del></small>
                                    </span>
                                    </p>
                                    <span class="icon-flow-cart fr j-goods-attr" onclick="addToCart(<%goods.goods_id%>)"><i class="iconfont icon-gouwuche" ></i></span>
                                </div>
                                <%if goods.shopNameSuffix == "旗舰店"%><span class="shop_sign_product"></span><%/if%>
                            </div>
                            </section>
                        </li>
                        <%/each%>
                    </ul>
                    <%else%>
                    <div class="no-div-message">
                        <i class="iconfont icon-biaoqingleiben"></i>
                        <p>亲，此处没有内容～！</p>
                    </div>
                    <%/if%>
                    </script>
                </section>
            </div>
            </div>
            <!-- 筛选 -->
            <div class="j-filter-div filter-div ts-5 c-filter-div" id="j-filter-div">
                <div class="mask-filter-div"></div>
                <section class="close-filter-div j-close-filter-div">
                    <div class="close-f-btn">
                        <i class="iconfont icon-fanhui"></i>
                        <span>关闭</span>
                    </div>
                </section>
                <form action="<?php echo url('category/index/products', array('id'=>$category, 'display'=>$display, 'sort'=>$sort, 'order'=>$order));?>" method="post" id="filter">
                <section class="con-filter-div ">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                    <div class="radio-switching padding-all j-radio-switching <?php if($isself) { ?>active<?php } ?>" data="<?php echo $isself; ?>">
                        <label class="fl">自营产品</label> <span class="fr"><em class="ts-1"></em><hr class="ts-1" /></span>
                    </div>
                    <div class="filter-city padding-all">
                        <!--address-start-->
                        <div id="editAddressForm">
                            <input type="hidden" id="province_id" name="province_region_id" value="<?php echo ($consignee_list['province_id']); ?>">
                            <input type="hidden" id="city_id" name="city_region_id" value="<?php echo ($consignee_list['city_id']); ?>">
                            <input type="hidden" id="district_id" name="district_region_id" value="<?php echo ($consignee_list['district_id']); ?>">
                            <input type="hidden" id="town_id" name="town_region_id" value="<?php echo ($consignee_list['town_region_id']); ?>">
                            <input type="hidden" id="village_id" name="village_region_id" value="">
                            <input type="hidden" id="region_id" name="region_id" value="">
                            <input type="hidden" id="address_id" name="address_id" value="<?php echo ($consignee_list['address_id']); ?>">
                            <div class="address-box" id="selectAddressBtn" region-data="">
                                    <label class="fl t-remark g-t-temark">所在地区</label>
                                    <span class="fl text-all-span f-05" id="addressLabelId"><?php echo ($consignee_list['province']); echo ($consignee_list['city']); echo ($consignee_list['district']); echo ($consignee_list['town']); echo ($consignee_list['village']); ?></span>
                                    <span class="t-jiantou fr"><i class="iconfont icon-jiantou tf-180"></i></span>
                            </div>
                        </div>
                         <!--address-end-->
                    </div>
                    <div class="padding-all select-one">
                        <ul class="dis-box j-get-more">
                            <!--<li class="ect-select box-flex">
                                <label class="ts-1 active dis-block">货到付款</label>
                            </li>-->
                            <li class="ect-select box-flex list-select hasgoods">
                                <label class="ts-1 dis-block">仅看有货</label>
                            </li>
                            <li class="ect-select box-flex list-select promotion">
                                <label class="ts-1 dis-block">促销</label>
                            </li>
                        </ul>
                    </div>
                    <div class="price-range padding-all">
                        <label class="price-range-label" data-min="<?php echo $price_range['min']; ?>" data-max="<?php echo $price_range['max']; ?>">价格区间</label>
                        <div class="price-slider">
                            <div id="slider-range" class="slider"></div>
                            <div class="slider-info">
                                <span id="slider-range-amount"></span>
                            </div>
                        </div>
                    </div>
                    <div class="select-two">
                        <a class="select-title padding-all j-menu-select">
                        <label class="fl">品牌</label>
                        <span class="fr t-jiantou j-t-jiantou" id="j-t-jiantou"><em class="fl"><?php echo $brand_name; ?></em><i class="iconfont icon-jiantou tf-180 ts-2"></i></span>
                    </a>
                        <ul class="j-sub-menu padding-all j-get-limit brand" data-istrue="true">
                            <?php $n=1;if(is_array($brands)) foreach($brands as $brand) { ?>
                            <li class="ect-select <?php if($brand['brand_id'] == 0) { ?>j-checkbox-all<?php } ?>" data-brand="<?php echo $brand['brand_id']; ?>">
                                <label class="ts-1 <?php if($brand['selected']) { ?>active<?php } ?>"><?php echo $brand['brand_name']; ?><i class="fr iconfont icon-gou ts-1"></i></label>
                            </li>
                            <?php $n++;}unset($n); ?>
                        </ul>
                        <?php $n=1;if(is_array($filter_attr_list)) foreach($filter_attr_list as $k=>$v) { ?>
                        <a class="select-title padding-all j-menu-select">
                            <label class="fl"><?php echo $v['filter_attr_name']; ?></label>
                            <span class="fr t-jiantou j-t-jiantou"><em class="fl"><?php echo $v['select_attr_name']; ?></em><i class="iconfont icon-jiantou tf-180 ts-2"></i></span>
                        </a>
                        <ul class="j-sub-menu padding-all j-get-limit filter_attr" data-key="<?php echo $k; ?>">
                            <?php $n=1;if(is_array($v['attr_list'])) foreach($v['attr_list'] as $attr) { ?>
                            <li class="ect-select <?php if($attr['attr_id'] == 0) { ?>j-checkbox-all<?php } ?>" data-attr="<?php echo $attr['attr_id']; ?>">
                                <label class="ts-1 <?php if($attr['selected']) { ?>active<?php } ?>"><?php echo $attr['attr_value']; ?><i class="fr iconfont icon-gou ts-1"></i></label>
                            </li>
                            <?php $n++;}unset($n); ?>
                        </ul>
                        <?php $n++;}unset($n); ?>
                    </div>
                    <div class="ect-button-more dis-box padding-all">
                        <input type="hidden" name="isself" value="<?php echo $isself; ?>" />
                        <input type="hidden" name="brand" value="<?php echo $brand_id; ?>" />
                        <input type="hidden" name="price_min" value="<?php echo $price_min; ?>" />
                        <input type="hidden" name="price_max" value="<?php echo $price_max; ?>" />
                        <input type="hidden" name="filter_attr" value="<?php echo $filter_attr; ?>"/>
                        <input type="hidden" name="keyword" value="<?php echo $keywords; ?>"/>
                        <input type="hidden" name="hasgoods" value="0"/>
                        <input type="hidden" name="promotion" value="0"/>
                        <input type="hidden" name="region_id" value="<?php echo $region_id; ?>" id="region_id"/>
                        <input type="hidden" name="area_id" value="<?php echo $area_id; ?>" id="area_id"/>
                        <a class="box-flex btn-reset j-filter-reset  min-btn" type="button">清空选项</a>
                        <a class="box-flex btn-submit  min-btn" type="submit" onclick="$('#filter').submit()">确定</a>
                    </div>
                    </div>
                </div>
                </section>
                </form>
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
    
          <!--地区选择 s-->
                 <div class="choose-address-page" id="chooseAddressPage">
	<div class="head-fix">
		<header>
			<div class="jd-index-header">
				<div class="jd-index-header-icon-back">
					<span id="goBack"></span>
				</div>
				<div class="jd-index-header-title"></div>
			</div>

		</header>
		<ul class="head-address-ul" id="headAddressUl">
			<li mytitle="0"></li>
			<li mytitle="1"></li>
			<li mytitle="2"></li>
			<li mytitle="3"></li>
			<li mytitle="4"><a href="#checkpage">返回</a></li>
		</ul>
	</div>
	<div class="address-container" id="addressContainer">
		<div class="address-content" id="addressContentDiv">
			<ul class="address-ul"></ul>
			<ul class="address-ul"></ul>
			<ul class="address-ul"></ul>
			<ul class="address-ul"></ul>
			<ul class="address-ul"></ul>
		</div>
	</div>
</div>  
        <!--地区选择 e-->
        <script type="text/javascript">
            <?php if($cat_id) { ?>
            var Sliders = function() {
                // 筛选价格区间 js
                $("#slider-range").slider({
                    range: true,
                    min: <?php echo $price_range['min']; ?>,
                    max: <?php echo $price_range['max']; ?>,
                    step: 100,
                    values: [<?php echo $price_min; ?>, <?php if($price_max) { echo $price_max; } else { echo $price_range['max']; } ?>],
                    slide: function(event, ui) {
                        $("#slider-range-amount").text(ui.values[0] + " ~ " + ui.values[1]);
                        $("input[name=price_min]").val(ui.values[0]);
                        $("input[name=price_max]").val(ui.values[1]);
                    }
                });
                $("#slider-range-amount").text($("#slider-range").slider("values", 0) + " ~ " + $("#slider-range").slider("values", 1));
            }();
            <?php } ?>
            //异步数据
            $(function(){
                // 通过埋点判断是否使用缓存
                var categoryPageFlag = window.BuryingPoint.search(/category/);
                if(categoryPageFlag >= 0){
                    var is_cache = false;
                }else{
                    var is_cache = true;
                }
                // 缓存ID
                var cache_prefix = "<?php echo md5(serialize(array('id'=>$category, 'display'=>$display, 'brand'=>$brand_id, 'intro'=>$intro, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'sort'=>$sort, 'order'=>$order, 'keyword'=>$keywords, 'isself'=>$isself, 'hasgoods'=>$hasgoods, 'promotion'=>$promotion)));?>";
                // 请求URL
                var url = "<?php echo url('category/index/products', array('id'=>$category, 'display'=>$display, 'brand'=>$brand_id, 'intro'=>$intro, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$filter_attr, 'sort'=>$sort, 'order'=>$order, 'keyword'=>$keywords, 'isself'=>$isself, 'hasgoods'=>$hasgoods, 'promotion'=>$promotion));?>#goods_list";
                //$('#J_list').more({'address': url, 'trigger':'.get_more', 'amount':'<?php echo $size; ?>'});
                //订单列表
                $('.product-list').infinite({url: url, template:'j-product', cache: is_cache, cache_prefix: cache_prefix});
            })
        </script>

    </body>

</html>