var autocomplete_name = "";

function add_auto_html(name, input, out, obj, div_index, data, show_count)
{
    //创建一行显示记录载体
    var newdiv = $("<div>").attr("id", "auto_"+name+"_row"+div_index);
    //添加鼠标移动到此行上的样式
    newdiv.css("cursor", "pointer");
    newdiv.css("overflow", "auto");
    newdiv.css("word-break", "break-all");
    //如果有多列，自动算出每列占多宽
    var col_width = 100 / show_count;
    //循环此行显示的字段集
    for (var i = 0, l = data.length; i < l; i++)
    {
        //创建一列显示记录载体
        var newcol = $("<div>").attr("class", "auto_"+name+"_col"+i);
        //设置此列宽高等样式
        newcol.css("width", col_width+"%");
        newcol.css("height", 22);
        newcol.css("float", "left");
        newcol.css("padding-left", 5);
        newcol.css("border-radius", 5);
        //判断此列是否要显示出来
        if (i >= show_count)
            newcol.hide();
        //把此列添加到行载体中
        newcol.html(data[i]).appendTo(newdiv);
    }
    
    //把此行添加到显示区域载体中
    newdiv.appendTo(out);
    
    newdiv.mouseover(function() {//鼠标进入此行效果
//         $(this).css("background-color","#CCC");
        var child = $(this).children("div");
        for (var i = 0, l = show_count; i < l; i++)
        {
            child[i].style.backgroundColor = "#E0E0E0";
        }
	});
	newdiv.mouseout(function(){//鼠标移除此行效果
// 	    $(this).css("background-color","white");
        var child = $(this).children("div");
        for (var i = 0, l = show_count; i < l; i++)
        {
            child[i].style.backgroundColor = "white";
        }
    });

    //单击此行效果
    newdiv.click(function(){
        //获取此行中所有子元素
        var child = $(this).children("div");
        var str = "";

        //如果子元素只有一个,则把子元素内容去除关键词颜色后赋给输入框
        if (child.length == 1)
        {
            var str = child[0].innerHTML.replace(new RegExp("<span style=\"color:red;\">(.*)</span>"), "$1");
            input.val(str);
            input.change();
            if (input.attr("callback"))
                eval(input.attr("callback"));
        }
        else
        {
            //如果子元素有多少，则循环子元素
            for (var i = 0, l = child.length; i < l; i++)
            {
                //组合子元素内容到临时变量中
                str += child[i].innerHTML;
                //判断给输入框的内容是否组合完成，如果组合完成则去除颜色赋给输入框
                //给输入框赋值后清空临时变量，代表接下来要组合提交数据框的内容
                if (i == show_count - 1)
                {
                    input.val(str.replace(new RegExp("<span style=\"color:red;\">(.*)</span>"), "$1"));
                    input.change();
                    if (input.attr("callback"))
                        eval(input.attr("callback"));
                    if (i != l - 1)
                        str = "";
                }
                //如果不是最后一个元素内容，则以,为分割符组合子元素内容
                else if (i != l - 1)
                    str += ",";
            }
        }
        //把匹配的记录赋给提交数据框，并触发改变数据事件，隐藏显示区域
        obj.val(str);
        obj.change();
        if (input.attr("val_callback"))
            eval(input.attr("val_callback"));
        out.hide();
    });
}

// name 最终输入框名字  
// data_id 数据域ID 
// show_count  要显示的字段数
// op    按键按下是否要清除最终输入框数据
// type  0为鼠标点击  1为按键按下
function autocomplete_run(name, inputObj, showObj, show_count, type)
{
    //获取数据对象
    var dataObj = $("#"+name+"_data");    
    //获取提交数据框对象
    var lastObj = $("#"+name+"_id");    
    //获取输入框内容
    var val = inputObj.val();

   //只要键盘有输入，则把提交数据框清空
    if (type)
        lastObj.val("");
    
    //清空自动完成显示区域
    showObj.empty();

    //分割数据集
    var dlist = dataObj.text().split("|");

    //循环数据集
    for (var i = 0, c = 0, l = dlist.length; i < l; i++)  
    {
        //如果是多字段显示，则再分割字段集
        var field = dlist[i].split(",");
        //循环字段集
        for (var j = 0, n = field.length > show_count ? show_count : field.length ; j < n; j++)
        {
            //判断如果是鼠标点击或输入字符匹配到字段, 则往自动完成显示区域添加
            if (type == 0 || field[j].indexOf(val) != -1)
            {
                //判断输入框本来就有数据或输入了数据
                if (val != "")
                {
                    //如果输入框数据和字段集结果一致
                    //这种情况代表用户完全输入
                    if (val == field[j])
                    {
                        //把用户输入赋值给提交数据框
                        var ival = val;
                        if (field.length != 1)  //如果数据字段不只一个则循环把非显示字段组合赋值到提交数据框
                            ival = "";

                        for (var k = n; k < field.length; k++)
                        {
                            ival += field[k];
                            if (k != field.length - 1)
                                ival += ",";
                        }
                        lastObj.val(ival);
                        //如果是完全键盘输入，触发提交数据框数据改变事件
                        if (type == 1)
                            lastObj.change();
                    }
                    //如果键盘输入并且输入结果与字段集并不完全相符
                    //这种情况代表用户并没有输入完，此时提交数据框为空  
                    //如果手动输入和字段匹配成功，则也不清
                    else if (type == 1 && val != lastObj.val())
                    {
                        lastObj.val("");
                        lastObj.change();
                    }
                    //给匹配到的关键词上色
                    field[j] = field[j].replace(val, '<span style="color:red;">'+val+'</span>');
                }
                //把结果添加到显示区域
                add_auto_html(name, inputObj, showObj, lastObj, i, field, show_count);

                //查询结果集最多为30条
                if (++c > 30)
                    return true;
                break;
            }
        }
    }
}


function autocomplete_start(obj, type)
{
    // alert(autocomplete_name);

    if (autocomplete_name != "")
        autocomplete_close();

    autocomplete_name = obj.attr("id").replace("_input", "");
    var showObj = $("#" + autocomplete_name + "_show");
    var count = obj.attr("count") ? obj.attr("count") : 1;

    if (showObj.css("display") == "none")
        showObj.css("width", obj.css("width"));

    if ( $("#" + autocomplete_name + "_data").text().length == 0)
    {
        autocomplete_name = "";
        var err = $("#" + autocomplete_name + "_input").attr("errInfo");
        if (err)
            alert(err);
        return false;
    }

    autocomplete_run(autocomplete_name, obj, showObj, count, type);

    if (showObj.css("display") == "none")
        showObj.show();
}

//自动完成窗口关闭处理函数
function autocomplete_close()
{
    if (autocomplete_name != "")
    {
        // alert(autocomplete_name);
        var obj = $("#"+autocomplete_name+"_input");
        if (obj.attr("sync"))   //如果输入内部在列表中不存在，对象sync属性为真则同步输入框内容
        {
            // alert(obj.val());
            $("#"+autocomplete_name+"_id").val(obj.val());
            $("#"+autocomplete_name+"_id").change();
        }
        $("#"+autocomplete_name+"_show").hide();
        autocomplete_name = "";
    }
}

$(document).click(function(event){
    //判断是否点击自动完成窗口，如果不是则判断是否隐藏
    // if ($(event.target).closest(".kyo_autocomplete,.kyo_combobox").length == 0)
    var tag = $(event.target);
    if (!(tag.hasClass("input_autocomplete") || tag.hasClass("autocomplete_show") || tag.hasClass("input_autocomplete_btn")))
        autocomplete_close();
});

$(document).off("click", ".input_autocomplete");
$(document).on("click", ".input_autocomplete", function(){
    autocomplete_start($(this), 0);
});

$(document).off("click", ".input_autocomplete_btn");
$(document).on("click", ".input_autocomplete_btn", function(){
    autocomplete_start($(this).parent().prev(), 0);
});

$(document).off("keyup", ".input_autocomplete");
$(document).on("keyup", ".input_autocomplete", function(){
    autocomplete_start($(this), 1);
});
