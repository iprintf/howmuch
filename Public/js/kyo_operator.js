//关闭推荐pos窗口保存关闭时间和刷新刷卡列表
function saveclosetime(id)
{
    // alert("pop"+id);
    var btn = $("#pos_find_btn_id");

    if (btn.attr("kclose"))
        //执行保存关闭时间
        // alert(btn.attr("url"));
        $.ajax({url:btn.attr("url"), dataType:"html", async:true, 
                success:function(data){
                    // alert(data); 
                }});
    
    if (id != -1)
    {
        // alert(btn.attr("krefresh"));
        // 刷新刷卡列表
        $.ajax({url:btn.attr("krefresh"), dataType:"html", async:true,
                    success:function(data){
                        $(".operlist").html(data);
                    // alert(data); 
                    }
        });
    }
    $("#oper_find_code_id").val("");
    $("#oper_find_code_id").focus();
}

//弹出推荐pos窗口
function open_pos_win(input, type)
{
    var code = input.val().replace(/ /g, "");

     // alert($("#pos_find_btn_id").length);

    if (!code)
    {
        alert("请输入信用卡的内部代码!");
        input.focus();
        return false;
    }

    var url = $("#oper_find_btn_id").attr("url") + "&code="+code;
    var pop = $("#oper_find_btn_id").attr("pop");
    if (type == 2)
        saveclosetime(-1);
    // alert("url");
    // 弹出推荐pos窗口，根据type的值来选择是覆盖还是新建
    $.ajax({url:url, dataType:"html", async:true,
                              success:function(data)
                              {
                                 if (type == 2)
                                    $(".posinfo").html(data);
                                  else
                                      popUp(data, pop);
                              }});
    return false; 
}

//主页查询模式点击清空验证卡片提示信息
$("#oper_find_type_id0, #oper_find_type_id1").click(function(){
    $("#oper_find_txt_id").html("");
});

//主页查询按钮弹出推荐pos窗口
$("#oper_find_btn_id").click(function(){
    var type = get_list_chkval("input[name='oper_find_type']");
    type = parseInt(type.substring(1, type.length - 1));
    input = $("#oper_find_code_id");

    input.val(input.val().replace(/ /g, ""));

    if (type == 1)
    {
        open_pos_win(input, 1);
        input.val("");
    }
    else
    {
        if (!input.val())
        {
            alert("请输入信用卡的内部代码!");
            input.focus();
            return false;
        }
        var url = input.attr("url")+"&code="+input.val();
        // alert(url);
        $.ajax({url:url, dataType:"html", async:true,
                  success:function(data)
                  {
                    $("#oper_find_txt_id").html(data);
                    input.val("");
                    input.focus();
                 }});
        return true;
    }
});

//主页查询表单不允许提交
$("form[name='operform']").submit(function(){
    return false;
});

//在主页查询输入框按回车一样弹出推荐pos窗口
$("#oper_find_code_id").keyup(function(event){
    if (event.which == 13)
        open_pos_win($(this), 1);
    return false;
});

////////////////////////////////////////////////////

//主页查询表单不允许提交
$(document).off("submit", "#cardfindform_id");
$(document).on("submit", "#cardfindform_id", function(){
    return false;
});

//在pos推荐窗口查询按钮
$(document).off("click", "#pos_find_btn_id");
$(document).on("click", "#pos_find_btn_id", function(){
    open_pos_win($("#pos_find_code_id"), 2);
    return true;
});

//在pos推荐窗口查询输入框回车操作
$(document).off("keyup", "#pos_find_code_id");
$(document).on("keyup", "#pos_find_code_id", function(event){
    if (event.which == 13)
        open_pos_win($(this), 2);
    return true;
});

