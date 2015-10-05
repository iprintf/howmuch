
$("#basis_manage_card").click(function(){

});
$("#basis_add_card").click(function(){
    var formObj = $(this).closest("form");
    formObj.find("#btnID_id").val("add_card");
    formObj.attr("vay", "");
    formObj.submit();
});

$("#basis_save_draft").click(function(){
    var formObj = $(this).closest("form");
    formObj.find("#btnID_id").val("save_draft");

    var obj = formObj.find("#name_id");
    if (obj.val().length == 0)
    {
        alert("客户姓名不能为空!");
        obj.select();
        obj.focus();
        return false;
    }

    var obj = formObj.find("#phone1_id");
    if (obj.val().length == 0)
    {
        alert("联系电话1不能为空!");
        obj.select();
        obj.focus();
        return false;
    }

    var obj = formObj.find("#eid_id");
    if (obj.val().length == 0)
    {
        alert("所属拓展员不能为空!");
        $("#eid_input").select();
        $("#eid_input").focus();
        return false;
    }

    formObj.attr("vay", "vay");
    formObj.submit();
    
});

$("#basis_submit").click(function(){

    if ($("#name_id").val() != $("#card_name_id").val())
    {
        alert("借记卡与客户姓名必需保持一致!");
        $("#card_name_id").select();
        $("#card_name_id").focus();
        return false;
    }

    var formObj = $(this).closest("form");
    formObj.find("#btnID_id").val("submit");
    formObj.attr("vay", "");
    formObj.submit();
    return false;
});

//客户添加完成后刷新客户列表，弹出客户信息框，询问是否添加卡片
function basis_add_finish(iurl, ipop, lurl, curl, cpop)
{
    //刷新客户列表
    $.ajax({url:lurl, dataType:"html", async:true,
              success:function(data)
              {
                $(".card").html(data);
             }});
    //弹出客户信息框
    $.ajax({url:iurl, dataType:"html", async:true,
              success:function(data)
              {
                  popUp(data, ipop);
             }});

    if (!confirm("是否为此客户添加信用卡?"))
        return true;

    $.ajax({url:curl, dataType:"html", async:true,
              success:function(data)
              {
                  popUp(data, cpop);
             }});

    return true;
}

