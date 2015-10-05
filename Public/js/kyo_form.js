//验证身份证的有效性
function validity_identity(id)
{
    //地区编号
    var vcity={ 11:"北京",12:"天津",13:"河北",14:"山西",15:"内蒙古",
        21:"辽宁",22:"吉林",23:"黑龙江",31:"上海",32:"江苏",
        33:"浙江",34:"安徽",35:"福建",36:"江西",37:"山东",41:"河南",
        42:"湖北",43:"湖南",44:"广东",45:"广西",46:"海南",50:"重庆",
        51:"四川",52:"贵州",53:"云南",54:"西藏",61:"陕西",62:"甘肃",
        63:"青海",64:"宁夏",65:"新疆",71:"台湾",81:"香港",82:"澳门",91:"国外"
       };
    // var patten = new RegExp(/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/);
    //判断身份证格式和位数正确性  18位数字或17位数字加一个X字符
    var patten = new RegExp(/(^\d{18}$)|(^\d{17}(\d|X|x)$)/);
    if (!patten.test(id))
        return false;

    //判断身份证地区编码是否有误，地区编码点首两位
    var province = id.substr(0, 2);
    if (vcity[province] == undefined)
    {
        // alert("地区不对!");
        return false;
    }

    //判断身份证生日有效性
    var year = id.substr(6, 4);
    var month = id.substr(10, 2);
    var day = id.substr(12, 2);
    var now = new Date();
    var now_year = now.getFullYear();
    var birthday = new Date(year + "/" + month + "/" + day);
    if (birthday.getFullYear() != year || 
            (birthday.getMonth() + 1) != month ||
            birthday.getDate() != day ||
            year < 1900 || year >= now_year)
    {
        // alert("生日不对!");
        return false;
    }

    //判断身份证效验码正确性
    var arrInt = new Array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
    var arrCh = new Array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
    var temp = 0, i, valnum;

    for (i = 0; i < 17; i++)
    {
        temp += id.substr(i, 1) * arrInt[i];
    }
    valnum = arrCh[temp % 11];
    if (valnum != id.substr(17, 1))
        return false;

    return true;
}

//验证信用卡卡号正确性
function validity_card(card)
{
    if (card.length < 13 || card.length > 16)
        return false;

    var clist = new Array("4", "51", "52", "53", "54", "55", "6", "3");

    for (var i in clist)
    {
        if (card.substr(0, clist[i].length) == clist[i])
            return true;
    }

    return false;
}

// validity_card("222233334444555");

function validity(obj, type)
{
    var ret = false;

    switch (type)
    {
        case "date":
            ret = true;
            // ret = !isNaN(Date.parse(obj.val()));
            break;
        case "card":
            ret = validity_card(obj.val());
            break;
        case "email":
             var patten = new RegExp(/^[\w-]+(\.[\w-]+)*@([\w-]+\.)+[a-zA-Z]+$/);
             ret = patten.test(obj.val());
            break;
        case "identity":
            ret = validity_identity(obj.val());
            break;
        case "num":
            // ret = !isNaN(parseFloat(obj.val()));
            var patten = new RegExp(/^([+-]?)[0-9.]+$/);
            ret = patten.test(obj.val());
            var min = obj.attr("min");
            var max = obj.attr("max");
            if (ret && min && max)
            {
                var val = parseFloat(obj.val());
                if (val >= parseFloat(min) && val <= parseFloat(max))
                    ret = true;
                else
                {
                    alert(obj.parent().closest("[class*=kyo]").prev().html().replace(":", "") + "输入必须在"+ min +"到" + max +"范围内!"); 
                    obj.focus();
                    obj.select();
                    return false;
                }
            }
            break;
        case "phone":
             var patten = new RegExp(/^1[3|4|7|5|8][0-9]\d{8}$/);
             ret = patten.test(obj.val());
            break;
        case "string":
            var len = obj.val().length;
            var min = obj.attr("min");
            if (!min)
                min = 0;
            var max = obj.attr("maxlength");
            if (!max)
                max = 500;

            if (len >= min && len <= max)
                ret = true;
            else
            {
                if (min == max)
                    alert(obj.parent().prev().html().replace(":", "") + "输入长度必须为"+ min +"个字符!"); 
                else
                    alert(obj.parent().prev().html().replace(":", "") + "输入长度必须在"+ min +"到" + max +"范围内!"); 
                obj.focus();
                obj.select();
                return false;
            }
            break;
        default:
            ret = true;
            break;
    }
    if (ret == false)
    {
        /*
         * var txtObj = obj.parent().prev();
         * if (txtObj.hasClass("form_element_title"))
         *     alert(txtObj.html().replace(":", "") + "输入格式不对!"); 
         * else
         */
        alert(obj.attr("title")+"输入格式不正确!");

        obj.focus();
        // obj.select();
        obj.val("");  //如果输入格式不对，直接清除
    }
    return ret;
}


function upload_complete(name, value, error, url, del)
{
    var lyObj = $("#ly");
    var bar = $("#file_progress_bar");
    if (!lyObj.attr("hidden_flag"))
        lyObj.addClass("hidden");
    else
        lyObj.attr("hidden_flag", false);

    lyObj.css("z-index", 9999 - lyObj.css("z-index"));
    bar.addClass("hidden");

    clearTimeout(lyObj.attr("timeID"));

    if (error)
    {
        alert(error);
        $("#"+name+"_id").val("");
        return false;
    }

    $("#file_"+name+"_id").next().find(".cat_img").attr("url", url);
    $("#file_"+name+"_id").next().find(".reset_upload").attr("del", del);
    $("#file_"+name+"_id").addClass("hidden");
    $("#file_"+name+"_id").next().removeClass("hidden");
    $("#"+name+"_id").val(value);
    alert("上传成功!");
}

$(document).off('focus click', ".kyo_date input");
$(document).off('change', ".kyo_num input");
$(document).off('change', ".kyo_card input");
$(document).off('change', ".kyo_phone input");
$(document).off('change', ".kyo_email input");
$(document).off('change', ".kyo_identity input");
$(document).off('change', ".kyo_string input");

$(document).on('focus click', ".kyo_date input", KYO.setday);

$(document).on('change', ".kyo_card input", function(){
    return validity($(this), "card");
}); 

$(document).on('change', ".kyo_num input", function(){
    return validity($(this), "num");
}); 

$(document).on('change', ".kyo_phone input", function(){
    return validity($(this), "phone");
}); 

$(document).on('change', ".kyo_email input", function(){
    return validity($(this), "email");
}); 

$(document).on('change', ".kyo_identity input", function(){
    return validity($(this), "identity");
}); 

$(document).on('change', ".kyo_string input", function(){
    return validity($(this), "string");
}); 

//查看图片
$(document).off("click", ".kyo_file .cat_img");
$(document).on("click", ".kyo_file .cat_img", function(){
    var t = $(this).attr("title");
    $.get($(this).attr("url"), function(data){
        popUp(data, "{w:560,h:420,n:'show_pic',t:"+t+"}");
    });
});

//重新上传图片
$(document).off("click", ".kyo_file .reset_upload");
$(document).on("click", ".kyo_file .reset_upload", function(){

    if ($(this).attr("del"))
    {
        if (confirm("重新上传会删除原图并且无法恢复！点击确定继续!"))
            $.get($(this).attr("del")); 
        else
            return false;
    }

    var p = $(this).parent();
    p.addClass("hidden");
    p.prev().val("");
    p.next().val("");
    p.prev().removeClass("hidden");

});

//上传图片
$(document).off("change", ".kyo_file input");
$(document).on("change", ".kyo_file input", function(){
    var form = $(this).closest("form");
    var old_action = form.attr("action");
    form.attr("action", $(this).attr("action"));
    form.attr("target", "file_iframe");
    form.submit();
    form.attr("target", "_self");
    form.attr("action", old_action);

    var lyObj = $("#ly");
    var bar = $("#file_progress_bar");
    if (lyObj.hasClass("hidden"))
        lyObj.removeClass("hidden");
    else
        lyObj.attr("hidden_flag", true);
    
    lyObj.css("z-index", 9999 - lyObj.css("z-index"));
    lyObj.width($(document).width());
    lyObj.height($(document).height());
    bar.css("margin-top", 50);
    bar.css("margin-left", 1);
    bar.css("top", (lyObj.height() - bar.height()) / 2);
    bar.css("left", (lyObj.width() - bar.width()) / 2);
    bar.css("z-index", lyObj.css("z-index") + 1);
    bar.removeClass("hidden");

    var name = $(this).attr("name").replace("file_", "");
    var tid = setTimeout(function(){
         upload_complete(name, "", "上传超时!", "", "");
    }, 180000);
    lyObj.attr("timeID", tid);
});

function validity_form(obj)
{
    var ret = false;
    var purl = "";
    var formID = obj.attr("id");
    var vay = obj.attr("vay");

    //循环表单元素 验证字段和序列化字段
    obj.find("[kform='"+obj.attr("name")+"']").each(function()
    {
        var label = $(this).attr("title");
        var itype = $(this).attr("type");
        var val = $(this).val();


        if (itype == "radio" || itype == "checkbox")
        {
            val = get_list_chkval("#"+formID+" input[name='"+$(this).attr("name")+"']");
            if (purl.indexOf($(this).attr("name")+"="+val) != -1)
                return true;
        }
        if (!vay && $(this).attr("required"))
        {
            if (!val)
            {
                //判断是否为autocomplete或combobox
                var autoInput = $("#"+$(this).attr("name")+"_input");
                if (autoInput.length == 1)
                    alert(label + "没有填写或填写内容不在列表内!");
                else
                    alert(label + "还没有填写或选择!"); 
                $(this).focus();
                $(this).select();
                if (autoInput.length == 1)
                {
                    autoInput.focus();
                    autoInput.select();
                    autoInput.val("");
                }
                ret = true;
                return false;
            }
            else if ($(this).prop("nodeName") == "INPUT" && itype == "text")
            {
                var val_type = $(this).parent().closest("[class*=kyo]").attr("class").match(/kyo_([a-zA-Z_]+)/);
                if (validity($(this), val_type[1]) == false)
                {
                    ret = true; 
                    return false
                }    
            }
        }

        if (val)
            purl += $(this).attr("name")+"="+encodeURIComponent(val)+"&";
    });

    if (ret)
        return false;

    if (purl == "")
    {
        alert("此表单没有元素，无法提交!"); 
        return false;
    }

    purl = purl.substring(0, purl.length - 1);

    return purl;
}

//通过提交表单操作
$(document).off("submit", "form[kajax='true']");
$(document).on("submit", "form[kajax='true']", function(){
    var formID = $(this).attr("id");
    var subBtn = $(this).find("[type=submit]");

    //
    //如果表单
    if ($(this).attr("ktype") == "info")
        return false;

    //如果me属性存在不为空，则代表此表单属性自定义操作
    if ($(this).attr("me"))
        return true;

    //如果target为file_iframe，则代表上传文件, 直接提交就OK
    if ($(this).attr("target") == "file_iframe")
        return true;


    var callback = $(this).attr("callback");
    if (callback)
    {
        callback += "('"+formID+"')";
        if (!eval(callback))
            return false;
    }


    var purl = validity_form($(this));
    if (purl == false)
        return false;

    //subBtn.prop("disabled", true);

    // alert($(this).attr("action"));
    // alert($(this).attr("action")+"     "+formID+"    "+encodeURI(purl));

    // $.post($(this).attr("action"), purl, success, "html");
    $.post($(this).attr("action"), purl, success, "json");
    return false;

    function success(data){

        $("#loading").fadeOut();
        //alert(data.url);
        //return;

        if (data.echo)
            alert(data.info);
        if (data.close)
            $("#"+formID).closest(".pop_win").find(".pop_close").click();
        else
            subBtn.prop("disabled", false);


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
