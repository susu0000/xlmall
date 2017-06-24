<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="zh-cn">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $page_title; ?></title>
<?php echo global_assets('css', 'console', 1);?>
<script type="text/javascript">var ROOT_URL = '/mobile/';</script>
<?php echo global_assets('js', 'console', 1);?>
</head>

<body class="iframe_body">

<div class="warpper">
	<div class="title">手机 - 第三方登录插件</div>
	<div class="content">
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i>
                <h4>操作提示</h4>
                <span id="explanationZoom" title="收起提示"></span>
            </div>
            <ul>
                  <li>列表页展示了所有手机端支持的第三方登录插件。</li>
                  <li>安装插件时需先到各第三方应用中申请ID和KEY，其中微信登录需要申请微信公众号并在微信客户端中方可使用。</li>
            </ul>
        </div>
        <div class="wrapper-content" style="margin-top:20px;">
            <ul class="items-box">
                
                <?php $n=1; if(is_array($modules)) foreach($modules as $key => $vo) { ?>
                <li class="item_wrap">
                    <div class="plugin_item" style="clear:both">
                        <div class="plugin_icon" >
                            <img src="<?php echo elixir('img/oauth/sns_'.$vo['type'].'.png');?>" alt="">
                        </div>
                        <div class="plugin_status">
                        	<span class="status_txt">
	                        	<div class="list-div">
	                        		<div class="handle">
	                        			<div class="tDiv">
	                        				<?php if($vo['install'] == 1) { ?>
										    	<a href="<?php echo url('edit', array('type'=>$vo['type']));?>" class="btn_edit"><i class="icon icon-edit"></i>编辑</a>  <a href="<?php echo url('uninstall', array('type'=>$vo['type']));?>" class="btn_trash"><i class="icon icon-trash"></i>卸载</a>
										    	<?php } else { ?>
										    	<a href="<?php echo url('install', array('type'=>$vo['type']));?>"  class="btn_inst"><i class="sc_icon sc_icon_inst"></i>安装插件</a>
										    <?php } ?>
	                        			</div>
	                        		</div>
	                        	</div>
                        	</span>
                        </div>
                        <div class="plugin_content"><h3 class="title"><?php echo $vo['name']; ?></h3><p class="desc">版本:<?php echo $vo['version']; ?></p></div>
                    </div>
                </li>
                <?php $n++;}unset($n); ?>

            </ul>
        </div>

	</div>
</div>
<script>
	$(document).on("mouseenter",".list-div tbody td",function(){
		$(this).parents("tr").addClass("tr_bg_blue");
	});

	$(document).on("mouseleave",".list-div tbody td",function(){
		$(this).parents("tr").removeClass("tr_bg_blue");
	});


    $("#explanationZoom").on("click",function(){
        var explanation = $(this).parents(".explanation");
        var width = $(".content").width();
        if($(this).hasClass("shopUp")){
            $(this).removeClass("shopUp");
            $(this).attr("title","收起提示");
            explanation.find(".ex_tit").css("margin-bottom",10);
            explanation.animate({
                width:width-0
            },300,function(){
                $(".explanation").find("ul").show();
            });
        }else{
            $(this).addClass("shopUp");
            $(this).attr("title","提示相关设置操作时应注意的要点");
            explanation.find(".ex_tit").css("margin-bottom",0);
            explanation.animate({
                width:"115"
            },300);
            explanation.find("ul").hide();
        }
    });
</script>
<div id="footer" style="position:fixed; bottom: 0px;">
    <p><?php echo ($lang['copyright']); ?></p>
</div>
</body>
</html>