<?php if (!defined('THINK_PATH')) exit();?><form id="kform_id" name="kform" kajax="true"
                    action="/howmuch/index.php?s=/Home/Index/user.html&form=add" method="post" enctype="multipart/form-data"
                    target="" class="form-horizontal kyo_form" me=""
                    ktype="form"  callback="" vay=""><div class="form-group form_element_row "><label for="name_id" class="col-sm-3 col-md-3 control-label form_element_title ">姓名:</label><div class="kyo_string col-sm-8 col-md-8 "><input type="text" id="name_id" name="name" class="form-control  " value="" min="" maxlength="10" placeholder="请输入姓名" kform="kform" autocomplete="off"  uniq="uniq"  required="required"  title="姓名" /></div></div><div class="form-group form_element_row "><label for="sex_id" class="col-sm-3 col-md-3 control-label form_element_title ">性别:</label><div class="kyo_radio col-sm-8 col-md-8 "><label class="radio-inline "><input type="radio" kform="kform" title="性别"
                                    id="sex_id0" name="sex" value="1"   required="required"   />男</label><label class="radio-inline "><input type="radio" kform="kform" title="性别"
                                    id="sex_id1" name="sex" value="2"   required="required"   />女</label></div></div><div class="form-group form_element_row "><label for="cellphone_id" class="col-sm-3 col-md-3 control-label form_element_title ">手机号:</label><div class="kyo_phone col-sm-8 col-md-8 "><div id="hint_cellphone" class="hint_show"></div><input type="text" id="cellphone_id" name="cellphone" class="form-control kyo_hint " value="" min="" maxlength="11" placeholder="请输入手机号"  hint="phone" kform="kform" autocomplete="off"  required="required"  title="手机号" /></div></div><div class="form-group form_ctrl_btn form_ctrl_btn_down"><button class="btn btn-primary "  url="add"  type="submit" >添加</button>&nbsp;&nbsp;</div></form>