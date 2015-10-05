/* 登陆表单获取焦点变色 */
$(".login-form").on("focus", "input", function(){
    $(this).closest('.item').addClass('focus');
}).on("blur","input",function(){
    $(this).closest('.item').removeClass('focus');
});

function clear_input(flag)
{
    if (flag == 1)
        $("input[name='password']").val("");
    else
    {
        $("input").each(function(){
            $(this).val("");
        });
    }
}

//表单提交
$(document).ajaxStart(function(){
        $("button:submit").addClass("log-in").attr("disabled", true);
    });
$(document).ajaxStop(function(){
        $("button:submit").removeClass("log-in").attr("disabled", false);
    });

$("form").submit(function(){
    var self = $(this);
    var empty = false;

    $("input").each(function(){
        if ($(this).val().length == 0)
        {
            $(".check-tips").text('所有字段都不能为空!');
            $(this).focus();
            empty = true;    //返回值
            return false;    //退出循环
        }
    });

    if (empty)
        return false;
    
    $.post(window.location.href, self.serialize(), success, "json");
    return false;

    function success(data){
        if(data.status)
            window.location.href = data.url;
        else
        {
            self.find(".check-tips").text(data.info);
            $("input[name='verify']").val("");
            $("input[name='verify']").focus();
            //刷新验证码
            $(".reloadverify").click();
        }
        if (data.callback)
            eval(data.callback); 
    }
});

$(function(){
    //初始化选中用户名输入框
    $("#itemBox").find("input[name=username]").focus();
    //刷新验证码
    var verifyimg = $(".verifyimg").attr("src");
    $(".reloadverify").click(function(){
        if( verifyimg.indexOf('?')>0){
            $(".verifyimg").attr("src", verifyimg+'&random='+Math.random());
        }else{
            $(".verifyimg").attr("src", verifyimg.replace(/\?.*$/,'')+'?'+Math.random());
        }
    });
    
    $("input[name='verify']").focus(function(){
        if( verifyimg.indexOf('?')>0){
            $(".verifyimg").attr("src", verifyimg+'&random='+Math.random());
        }else{
            $(".verifyimg").attr("src", verifyimg.replace(/\?.*$/,'')+'?'+Math.random());
        }
    });

    //placeholder兼容性
        //如果支持 
    function isPlaceholer(){
        var input = document.createElement('input');
        return "placeholder" in input;
    }
        //如果不支持
    if(!isPlaceholer()){
        $(".placeholder_copy").css({
            display:'block'
        })
        $("#itemBox input").keydown(function(){
            $(this).parents(".item").next(".placeholder_copy").css({
                display:'none'
            })                    
        })
        $("#itemBox input").blur(function(){
            if($(this).val()==""){
                $(this).parents(".item").next(".placeholder_copy").css({
                    display:'block'
                })                      
            }
        })
        
        
    }
});
