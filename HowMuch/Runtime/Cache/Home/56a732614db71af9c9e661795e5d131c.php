<?php if (!defined('THINK_PATH')) exit();?><form id="kform_id" name="kform" kajax="true"
                    action="/howmuch/index.php?s=/Home/Index/goods.html&form=add" method="post" enctype="multipart/form-data"
                    target="" class="form-horizontal kyo_form" me=""
                    ktype="form"  callback="" vay=""><div class="form-group form_element_row "><label for="name_id" class="col-sm-3 col-md-3 control-label form_element_title ">商品名称:</label><div class="kyo_string col-sm-8 col-md-8 "><input type="text" id="name_id" name="name" class="form-control  " value="" min="" maxlength="30" placeholder="请输入商品名称" kform="kform" autocomplete="off"  required="required"  title="商品名称" /></div></div><div class="form-group form_element_row "><label for="label_id" class="col-sm-3 col-md-3 control-label form_element_title ">商品标签:</label><div class="kyo_string col-sm-8 col-md-8 "><input type="text" id="label_id" name="label" class="form-control  " value="" min="" maxlength="15" placeholder="请输入商品标签" kform="kform" autocomplete="off"  required="required"  title="商品标签" /></div>* 标签以逗号分隔</div><div class="form-group form_element_row "><label for="unit_price_id" class="col-sm-3 col-md-3 control-label form_element_title ">商品单价:</label><div class="kyo_num col-sm-8 col-md-8 "><div id="hint_unit_price" class="hint_show"></div><div class="input-group"><input type="text" id="unit_price_id" name="unit_price" class="form-control kyo_hint " value="" min="" maxlength="15" placeholder="请输入商品单价"  hint="money" kform="kform" autocomplete="off"  required="required"  title="商品单价" /><span class="input-group-addon">元</span></div></div></div><div class="form-group form_ctrl_btn form_ctrl_btn_down"><button class="btn btn-primary "  url="add"  type="submit" >添加</button>&nbsp;&nbsp;</div></form>