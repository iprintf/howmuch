<?php if (!defined('THINK_PATH')) exit();?><form id="kform_id" name="kform" kajax="true"
                    action="/howmuch/index.php?s=/Home/Index/add_handle.html" method="post" enctype="multipart/form-data"
                    target="" class="form-horizontal main_first_row" me=""
                    ktype="form"  callback="" vay=""><div class="form-group form_element_row "><div class="kyo_group cols-sm-12 cols-md-12 "><div class="page-header kyo_form_group ">添加交易</div></div></div><div class="form-group form_element_row "><label for="name_id" class="col-xs-3 col-sm-3 col-md-3 control-label form_element_title  hidden-ss">交易名称:</label><div class="kyo_string col-ss-12 col-xs-8 col-sm-8 col-md-8 "><input type="text" id="name_id" name="name" class="form-control  " value="" min="" maxlength="15" placeholder="请输入交易名称" kform="kform" autocomplete="off"  required="required"  title="交易名称" /></div></div><div class="form-group form_element_row "><label for="total_id" class="col-xs-3 col-sm-3 col-md-3 control-label form_element_title  hidden-ss">交易金额:</label><div class="kyo_num col-ss-12 col-xs-8 col-sm-8 col-md-8 "><div class="input-group"><input type="text" id="total_id" name="total" class="form-control  " value="" min="" maxlength="15" placeholder="请输入交易金额" kform="kform" autocomplete="off"  required="required"  title="交易金额" /><span class="input-group-addon">元</span></div></div></div><div class="form-group form_element_row "><label for="attender_id" class="col-xs-3 col-sm-3 col-md-3 control-label form_element_title  hidden-ss">参与者:</label><div class="kyo_multiselect col-ss-12 col-xs-8 col-sm-8 col-md-8 "><select id="attender_id" name="attender" title="参与者"
                        kform="kform" class="form-control "   
                        multiple="multiple"><option value="2">朱龙</option><option value="3">陈仲林</option><option value="6">1111</option></select><script>$("#attender_id").multiselect({buttonWidth: "230px",includeSelectAllOption: true,nonSelectedText: "请选择参与者...",enableFiltering: true});</script></div></div><div class="form-group form_element_row "><label for="comment_id" class="col-xs-3 col-sm-3 col-md-3 control-label form_element_title  hidden-ss">备注:</label><div class="kyo_textarea col-ss-12 col-xs-8 col-sm-8 col-md-8 "><textarea id="comment_id" name="comment" kform="kform"
                             title="备注" class="form-control " rows="3"
                             ></textarea></div></div><div class="form-group form_ctrl_btn form_ctrl_btn_down"><button class="btn btn-primary "  url="add"  type="submit" >添加</button>&nbsp;&nbsp;<button class="btn btn-primary "  onclick="location.href='/howmuch/index.php?s=/Home/Index/index.html'" >记账</button>&nbsp;&nbsp;</div></form>