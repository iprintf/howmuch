//继续添加卡片
$("#card_add_card").click(function(){
    if (!isbasis() || !get_costing_val(1) || !verify_relate() || !isrising_cost())
        return false;

    var formObj = $(this).closest("form");
    formObj.find("#btnID_id").val("add_card");
    formObj.attr("vay", "");
    formObj.submit();
});

//保存草稿
$("#card_save_draft").click(function(){
    if (!isbasis())
        return false;

    var formObj = $(this).closest("form");
    formObj.find("#btnID_id").val("save_draft");

    var obj = formObj.find("#card_id");
    if (obj.val().length == 0)
    {
        alert("卡号不能为空!");
        obj.select();
        obj.focus();
        return false;
    }

/*
 *     var obj = formObj.find("#phone1_id");
 *     if (obj.val().length == 0)
 *     {
 *         alert("联系电话1不能为空!");
 *         obj.select();
 *         obj.focus();
 *         return false;
 *     }
 * 
 *     var obj = formObj.find("#eid_id");
 *     if (obj.val().length == 0)
 *     {
 *         alert("所属拓展员不能为空!");
 *         $("#eid_input").select();
 *         $("#eid_input").focus();
 *         return false;
 *     }
 */

    formObj.attr("vay", "vay");
    formObj.submit();
});

//确定添加卡片
$("#card_submit").click(function(){
    if (!isbasis() || !get_costing_val(1) || !verify_relate() || !isrising_cost())
        return false;

    var formObj = $(this).closest("form");
    formObj.find("#btnID_id").val("submit");
    formObj.attr("vay", "");
    formObj.submit();
    return false;
});

//判断信用卡关联业务逻辑
function verify_relate()
{
    var nrel = new Array("temp_amount", "aging", "auto_aging", "overdue", "insure", "exceed");
    var rel = new Array("alipay", "autopay", "tenpay", "quick_pay", "wxpay", "affiliate");

    for (x in nrel) 
    {
        if (get_list_chkval("input[type=radio][name="+nrel[x]+"]") == 1) 
        {
            var title = $("#"+nrel[x]+"_id0").attr("title");
            alert("暂不支持 "+ title +" 此类卡片!"); 
            return false;
        }
    }

    for (x in rel) 
    {
        if (get_list_chkval("input[type=radio][name="+rel[x]+"]") == 1) 
        {
            var title = $("#"+rel[x]+"_id0").attr("title");
            if (!confirm("此卡有 "+title+" 关联业务风险，是否继续提交?"))
                return false;
        }
    }

    return true;
}

//判断增值费率不允许有小数点
function isrising_cost()
{
    if ($("#rising_cost_id").val().indexOf(".") != -1)
    {
        alert("输入的增值费率不支持!"); 
        return false;
    }
    return true;
}

//判断客户是否存在，所有首要判断为项
function isbasis()
{
    if ($("#bid_id").val().length == 0)
    {
        alert("请先选择客户!");
        $("#bname_input").select();
        $("#bname_input").focus();
        return false;
    }
    return true;
}

//独立添加卡片获取客户
function get_basis()
{
    var val = $("#bname_id").val().split(",");
    var input_val = $("#bname_input").val().split(",");
    $("#bid_id").val(val[0]);
    $("#eid_id").val(val[1]);
    $("#eid_name_id").html(val[2]);
    if (val[3])
        $("#cost_id").val((val[3] * 100).toFixed(1));
    else
        $("#cost_id").val("");
    if (val[4])
        $("#rising_cost_id").val((val[4] * 100).toFixed(0));
    else
        $("#rising_cost_id").val("");
    $("#bname_input").val(input_val[0] + "  " + input_val[1]);
}

//额度、服务费率、交易成本百分比连动算交易成本金额
function get_costing_val(flag)
{
    var mval = $("#amount_id").val();
    var cval = $("#cost_id").val();
    var pval = $("#costing_per_id").val();
    var tag = $("#costing_id");

    if (!isbasis())
    {
        $(this).val("");
        return false; 
    }


    //如果金额、费率、支付方式都输入则计算服务期限
    if (flag != 1 && mval && cval && $("#pay_type_id").val() && 
            $(this).attr("id") != "costing_per_id")
    {
        if ($("#agreement_id").prop("nodeName") == "SELECT")
            oper_srv_fee("agreement_id");
        else
            oper_srv_fee("get_costing_val");
    }

    if ($("#cvv2_id").length == 0)
        return true;

    if (!(mval && cval && pval))
    {
        if (flag == 1)
            alert("你输入的额度和交易成本不能为空或选择，服务费率必须在一定范围内!");
        return false;
    }

    if (parseFloat(pval) < 0.4 || pval > parseFloat(cval * 0.85))
    {
        alert("你输入的月服务支配成本有误!");
        return false;
    }

    $("#costing_id").val(((parseFloat(pval) / 100 * mval).toFixed(0)) +".0");
    return true;
}

$("#amount_id, #cost_id, #costing_per_id").change(get_costing_val);


//选择操作员后输入框内的数字
function opid_input_dis()
{
    var val = $("#opid_input").val().split(",");
    $("#opid_input").val(val[0]);
}

//根据服务期限算服务费 或 根据服务费算服务期限
function oper_srv_fee()
{
    var mval = $("#amount_id").val();
    var cval = $("#cost_id").val();
    var pval = $("#pay_type_id").val();

    if (arguments[0] == "agreement_id" || $(this).attr("id") == "agreement_id")
        var val = $("#agreement_id").val();
    else
    {
        var val = $("#fee_id").val();
        if (!val)
            return false;
    }

    if (!(mval && cval && pval))
    {
        alert("请先填写卡片额度、服务费率和支付方式!");
        $("#fee_id").val("");
        return false;
    }

    var msrv = (parseFloat(cval) / 100 * mval).toFixed(3);

    //如果传了第一个参数为agreement_id或服务期限修改了则执行按服务期限算服务费用
    if (arguments[0] == "agreement_id" || $(this).attr("id") == "agreement_id")
    {
        val = (msrv * $("#agreement_id").val()).toFixed(0);
        $("#fee_id").val(val);
        $("#save_amount_id").val(0);
        $("#fact_save_id").val(0);
    }
    else
    {
        if (val < 0)
            var mper = parseFloat(val) * -1 / msrv;
        else
            var mper = parseFloat(val) / msrv;
        var month = parseInt(mper);
        if (month <= 0 || month > 12)
        {
            alert("输入服务费用错误，计算的服务期限只支持1到12个月!");
            $("#fee_id").val("");
            $("#fee_id").focus();
            return false; 
        }
        var pret = ((mper - month) * msrv).toFixed(0);
        $("#agreement_id").val(month);
        $("#save_amount_id").val(pret);
        $("#fact_save_id").val(pret);
    }
    //如果是费用另议则把服务费用改成负数
    if (pval == 3)
    {
        if (val < 0)
            $("#fee_id").val(val);
        else
            $("#fee_id").val(val * -1);
    }

    return true;
}

//服务费用如果修改则连动计算服务期限和保底、实保金额
$("#fee_id").change(oper_srv_fee);
//服务限期修改则连动计算服务费用
$(document).off("change", "#agreement_id");
$(document).on("change", "#agreement_id", oper_srv_fee);

//支付方式修改则根据值禁用和开户服务期限选择和服务费用输入
$("#pay_type_id").change(function(){
    if ($(this).val() == 3)
    {
        partial_refresh($(this).attr("gurl"), ".sel_agreement");
        $("#fee_id").val("");
        $("#fee_id").attr("readonly", true);
        $("#save_amount_id").val("");
        $("#fact_save_id").val("");
    }
    else
    {
        if ($("#agreement_id").prop("nodeName") == "SELECT")
        {
            $("#fee_id").val("");
            $("#save_amount_id").val("");
            $("#fact_save_id").val("");
            partial_refresh($(this).attr("gurl") + "&type=1", ".sel_agreement");
            $("#fee_id").attr("readonly", false);
        }
    }
});

//实保金额修改事件 如果支付方式为费用另议则把实保金额同步保底金额
$("#fact_save_id").change(function(){
    var smval = $("#save_amount_id").val();
    var val = $(this).val();

    if ($("#pay_type_id").val() == 3)
        $("#save_amount_id").val(val);
    else if (parseInt(val) > parseInt(smval))
    {
        alert("实保金额不能大于保底金额!");
        $(this).val("");
        $(this).focus();
        return false;
    }
});


//卡种选择窗口自动完成选择卡种
function selcardtype(url, tag)
{
    val = $("#find_card_type_id").val();
    if (!val)
        return false;

    partial_refresh(url+"&val="+val, tag);
}

//选择已有银行弹出卡种选择窗口
function getcardtype(url)
{
    val = $("#bank_id").val();
    if (!val)
        return false;

    $.ajax({url:url + "&bank="+val, dataType:"html", async:false,
              success:function(data)
              {
                  popUp(data, "{"+$("#bank_input").attr("bpop")+"}");
              }});
}

$(document).off("click", "#btn_find_cardtype_id");
$(document).on("click", "#btn_find_cardtype_id", function(){
    val = $("#find_card_type_input").val();
    if (val == "")
        return false;
    url = $(this).attr("turl") + "&val=" + $("#find_card_type_input").val();
    tag = $(this).attr("tTag");
    partial_refresh(url, tag);
});

//选择银行卡种名称和类型
$(document).off("click", ".cardtypeImg");
$(document).on("click", ".cardtypeImg", function()
{
    $("#card_type_id").val($(this).attr("kctype"));
    $("#card_type_name_id").val($(this).attr("kcname"));
    $(this).closest(".pop_body").prev().children(".pop_close").click();
});

