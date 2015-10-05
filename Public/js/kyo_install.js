
$(document).off('focus click', ".kyo_date input");
$(document).on('focus click', ".kyo_date input", KYO.setday);

$(document).ready(function(){

$("#perm_type_id").change(function()
{
    var v = $(this).val();

    if (v > 0 && v < 7)
    {
        $("#sub_id").closest("div").removeClass("hidden");
        $.ajax({url:$("#sub_id").attr("url"), dataType:"html", async:true,
                          success:function(data)
                          {
                              $("#sub_id").closest("div").html(data);
                          }});
    }
    else
    {
        if (!$("#sub_id").closest("div").hasClass("hidden"))
            $("#sub_id").closest("div").addClass("hidden");
    }
    if (!$("#member_id").closest("div").hasClass("hidden"))
         $("#member_id").closest("div").addClass("hidden");
});

$(document).off("change", "#sub_id");
$(document).on("change", "#sub_id", function(){

    var sid = $(this).val();
    var type = $("#perm_type_id").val();

    if (type > 0 && type < 6)
    {
        $("#member_id").closest("div").removeClass("hidden");
        $.ajax({url:$("#member_id").attr("url")+"&type="+type+"&sid="+sid, dataType:"html", async:true,
                          success:function(data)
                          {
                              $("#member_id").closest("div").html(data);
                          }});
    }
    else
    {
        if (!$("#member_id").closest("div").hasClass("hidden"))
            $("#member_id").closest("div").addClass("hidden");
    }
});

function buttonLink()
{
    $("#alink").parent().attr("href", $(this).attr("url"));
    $("#alink").click();
    return true;
}

$("#btn_home,#btn_build,#btn_submit").click(buttonLink);


$(document).off("click", ".btn_global");
$(document).on('click', '.btn_global', linkop);

//通过提交表单操作
$("form[kajax='true']").submit(function(){

    var ret = false;
    var type = "";
    var formID = $(this).attr("id");
    var subBtn = $(this).find("[type=submit]");

    if ($(this).attr("me"))
        return true;

    var purl = "";

    //循环表单元素 验证字段和序列化字段
    $(this).find("[kform='"+$(this).attr("name")+"']").each(function()
    {
        var label = $(this).attr("title");
        var itype = $(this).attr("type");
        var val = $(this).val();

        if ($(this).attr("required"))
        {
            if (!val)
            {
                alert(label + "还没有填写或选择!"); 
                $(this).focus();
                $(this).select();
                ret = true;
                return false;
            }
        }

        if (val)
            purl += $(this).attr("name")+"="+val+"&";
    });

    if (ret)
        return false;

    if (purl == "")
    {
        alert("此表单没有元素，无法提交!"); 
        return false;
    }

    purl = purl.substring(0, purl.length - 1);
    // alert($(this).attr("action"));
    purl = purl.replace(/\//g, ",");
    // alert($(this).attr("action")+"     "+formID+"    "+encodeURI(purl));

    // $.post($(this).attr("action"), encodeURI(purl), success, "html");
    $.post($(this).attr("action"), encodeURI(purl), success, "json");
    return false;

    function success(data){

        // alert(data);
        // return;
        if (data.echo)
            alert(data.info);
        if (data.close)
            $("#"+formID).closest(".pop_win").find(".pop_close").click();
        else
            subBtn.attr("disabled", false);

        if (data.url)
        {
            if (data.tag)
            {
                var linkObj = $.ajax({url:data.url, async:false});
                $(data.tag).html(linkObj.responseText);
            }
            else
                window.location.href = data.url;
        }
        else if (data.tag && data.html)
        {
            if (data.append)
                $(data.tag).html($(data.tag).html() + data.html);
            else
                $(data.tag).html(data.html);
        }
        if (data.callback)
            eval(data.callback);
    }
});

});
