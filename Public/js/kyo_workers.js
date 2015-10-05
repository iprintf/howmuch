
importScripts("Public/js/jquery.min.js");

//更新站内信收件数字和残值查询数字
function updateSrvData()
{
    var url = "/index.php?s=/Home/Index/autoUpdateNum.html";

    $.ajax({url:url, dataType:"json", global:false, async:false,
            success:function(data){
                var ret = '$(".top_mess_num").html('+data.msg.num+');';
                ret += 'noticePop('+data.msg.info+');';
                ret += '$(".top_surplus_num").html('+data.surplus.num+');';
                ret += 'noticePop('+data.surplus.info+');';
                postMessage(ret);
                //
                // $(".top_mess_num").html(data.msg.num);
                // noticePop(data.msg.info);

                // $(".top_surplus_num").html(data.surplus.num);
                // noticePop(data.surplus.info);
        }});

    //自动更新收件信数字和残值列表数  每240秒更新一次
    setTimeout("updateSrvData()", 3000);
}

//初始更新数字
updateSrvData();
