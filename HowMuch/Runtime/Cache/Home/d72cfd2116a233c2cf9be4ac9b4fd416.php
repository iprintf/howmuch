<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML>
<html lang="en">
<head>
    <title>_</title>
    <meta charset="UTF-8" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link type="text/css" rel="stylesheet" href="/howmuch/Public/css/bootstrap.min.css" />
	<link type="text/css" href="/howmuch/Public/css/bootstrap-responsive.min.css" rel="stylesheet">
	<!--[if lte IE 6]>
	<link rel="stylesheet" type="text/css" href="/howmuch/Public/css/bootstrap-ie6.css">
	<![endif]-->
	<!--[if lte IE 7]>
	<link rel="stylesheet" type="text/css" href="/howmuch/Public/css/ie.css">
	<![endif]-->
	<link type="text/css" href="/howmuch/Public/css/jBox.css" rel="stylesheet">
	<link type="text/css" href="/howmuch/Public/css/jBox/TooltipBorder.css" rel="stylesheet">
	<link type="text/css" href="/howmuch/Public/css/jBox/ModalBorder.css" rel="stylesheet">
	<link type="text/css" href="/howmuch/Public/css/jBox/TooltipDark.css" rel="stylesheet">
	<link type="text/css" href="/howmuch/Public/css/jBox/NoticeBorder.css" rel="stylesheet">
	<link type="text/css" href="/howmuch/Public/summernote/font-awesome.min.css" rel="stylesheet">
	<link type="text/css" href="/howmuch/Public/summernote/summernote.css" rel="stylesheet">
	<link type="text/css" href="/howmuch/Public/css/bootstrap_multiselect.css" rel="stylesheet">
	<link type="text/css" href="/howmuch/Public/css/bootstrap_kyo.css" rel="stylesheet">
	<link type="text/css" href="/howmuch/Public/css/kyo.css" rel="stylesheet">
</head>
<body>
<div class="hidden-xs hidden-sm" style="height:50px;" id="top_padding"></div>
<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">     <!-- 固定不变永远置顶的导航栏 -->
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a href="<?php echo U('index');?>" class="navbar-brand">多少钱？</a>
        </div>
        
        <div class="collapse navbar-collapse">
            
    <ul class="nav navbar-nav">
    <li><a href="#" data="<?php echo U('add');?>">创建交易</a></li>
    <li><a href="<?php echo U('index');?>">记账交易</a></li>
    <li><a href="#" data="<?php echo U('did_transaction');?>">未结交易</a></li>
    <li><a href="#" data="<?php echo U('end_transaction');?>">结算交易</a></li>
    <li><a href="#" data="<?php echo U('user');?>">用户管理</a></li>
    <li><a href="#" data="<?php echo U('goods');?>">商品管理</a></li>
</ul>


        </div>
    </div>
</div>

<div id="file_progress_bar" class="col-md-4 progress progress-striped active hidden">
    <div class="progress-bar" role="progressbar" style="width: 100%">正中上传中...</div>
</div>

<div id="loading"><img src="/howmuch/Public/img/loading.gif" /></div>
<div id="ly" class="hidden"></div>
<div id="pop_win1" class="pop_win hidden">
    <div class="pop_title"><span class="pop_close">&times;</span></div>
    <div class="pop_body"></div>
</div>
<div id="pop_win2" class="pop_win hidden">
    <div class="pop_title"><span class="pop_close">&times;</span></div>
    <div class="pop_body"></div>
</div>
<div id="pop_win3" class="pop_win hidden">
    <div class="pop_title"><span class="pop_close">&times;</span></div>
    <div class="pop_body"></div>
</div>
<div id="pop_win4" class="pop_win hidden">
    <div class="pop_title"><span class="pop_close">&times;</span></div>
    <div class="pop_body"></div>
</div>
<div id="pop_win5" class="pop_win hidden">
    <div class="pop_title"><span class="pop_close">&times;</span></div>
    <div class="pop_body"></div>
</div>
<a href="" target="_blank" class="hidden"><span id="open_new_tag">open_new_pageTag</span></a>

<div id="body" class="container"> 
    
    <?php echo ($main_body); ?>

</div>

<iframe name="file_iframe" src="" class="form_iframe hidden"></iframe>

<!--[if lt IE 9]>
<script type="text/javascript" src="__STATIC__/jquery-1.10.2.min.js"></script>
<![endif]--><!--[if gte IE 9]><!-->
<script type="text/javascript" src="/howmuch/Public/js/jquery-2.1.1.min.js"></script>
<!-- <script type="text/javascript" src="/howmuch/Public/js/jquery.min.js"></script> -->
<script type="text/javascript" src="/howmuch/Public/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/howmuch/Public/js/bootstrap-multiselect.js"></script>
<script type="text/javascript" src="/howmuch/Public/js/jBox.js"></script>
<script type="text/javascript" src="/howmuch/Public/js/autocomplete.js"></script>
<script type="text/javascript" src="/howmuch/Public/js/date.js"></script>
<script type="text/javascript" src="/howmuch/Public/js/kyo_pop.js"></script>
<script type="text/javascript" src="/howmuch/Public/js/kyo_global.js"></script>
<script type="text/javascript" src="/howmuch/Public/js/kyo_form.js"></script>
<script type="text/javascript" src="/howmuch/Public/js/kyo_hint.js"></script>
<script type="text/javascript" charset="utf-8" src="/howmuch/Public/summernote/summernote.js"></script>

</body>
</html>