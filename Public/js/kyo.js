// JavaScript Document
//AJAX使用

//保存当前url再跳到指定页面，用于返回上一页操作
function save_url(link)
{
    createxmlhttp("../main.php?op=save_url&url='"+window.location.href+"'", function(text){
    }, false);    
    if (arguments[1] != true)
        location.href = link;
}

//通用显示文本框内容
function show_pass_text(title, name)
{
    var obj = document.getElementById(name);
    var content = '<div class="pop_title">'+title+'<span class="close" onclick=\'pop_close()\' >&times;</span></div>';
    content += '<div class="pop_main" style="height:200px;overflow-y:auto;">'+obj.innerHTML+'</div>';
	pop_up_window(false, content, 480, 272);
}


// 批量操作提示对话框
function chkfrm(form)
{
    if (form.batch.value == "del")
        var str = '此操作将彻底删除已选中的信息，点击"确定"删除!';
    else if (form.batch.value == "reset")
        var str = '此操作将重置已选中的用户密码，点击"确定"重置密码!';
    else 
    {
        var str = '';
        return true;
    }
    if (confirm(str))
        return true;
    else
        return false;
}
function check_img(path, file_name)
{
    var type = "img";
    if (arguments[2])
    {
        if (!confirm("重新上传会删除原图并无法还原，继续点击确定!"))
            return false;
        type = arguments[2];
    }
    createxmlhttp("../main.php?op="+type+"&file_name="+file_name+"&path="+path, function(text)
    {
        document.getElementById(file_name+"_td").innerHTML = text; 
    });
}

function show_card_pic(title, path)
{
    dishtml = '<img src="'+encodeURI(path)+'" style="width:420px;height:270px" />';
    pop_up_window(title, dishtml, 443, 345);
}

function show_card_img(title, path)
{
    var dishtml = '<img src="'+encodeURI(path)+'_front.jpg" style="width:420px;height:270px" />&nbsp;';
    dishtml += '<img src="'+encodeURI(path)+'_back.jpg" style="width:420px;height:270px" />';
    pop_up_window(title, dishtml, 870, 360);
}

function del_verify(url)
{
    if (confirm("删除操作无法恢复，确定删除吗？"))
        location.href = url;
}

function jump_url(url)
{
    createxmlhttp(url, function (text){
        $("#body").html(text);
    });    
}
