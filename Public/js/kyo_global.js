//全局链接操作
function linkop()
{
    var url = $(this).attr("url");
    // alert(url);

    //如果是自定义操作或是href为真并且不等于#代表为a link链接，如果Url为空代表什么操作都没有
    if ($(this).attr("me") || $(this).attr("type") == "submit" ||
        ($(this)[0].tagName == "A" && $(this).attr("href") && $(this).attr("href") != "#") ||
        !$(this).attr("url"))
        return true;

    //点击链接或按钮弹出确定框接口
    if ($(this).attr("confirm") && !confirm($(this).attr("confirm")))
        return false;

    //点击链接或按钮弹出信息提示框接口
    if ($(this).attr("alert"))
        alert($(this).attr("alert"));

    //点击链接或按钮执行自定义回调函数
    if ($(this).attr("callback"))
    {
        // alert($(this).attr("callback"));
        eval($(this).attr("callback"));
    }

    //如果是下拉选框或自动完成框改变值事件产生给Url把当前选中值组合进去
    if ($(this)[0].tagName == "SELECT" || $(this).hasClass("input_autocomplete"))
        url = encodeURI(url + "&val=" + $(this).val());

    //针对批量操作的链接或按钮组
    if ($(this).attr("name") == "batch")
    {
        var selector = ".kyo_data_list [name=opChkId]:checkbox";

        var ids = "id";
        if ($("#chkall_id").attr("chktype") == 1)
            ids = "code";

        var code = get_list_chkval(selector, ids);

        if (!code)
        {
            alert("没有选择要操作的记录!");
            return false;
        }

        //打开新网页标签加载对应的网页并且传参
        if ($(this).attr("kopen"))
        {
            $("#open_new_tag").parent().attr("href", url + "&where='"+ids+" in ("+code+")'");
            $("#open_new_tag").click();
            return false;
        }

        if ($(this).attr("href") == "#")
        {
            // if ($(this).attr("url").indexOf(code) == -1)
            url += "&where='"+ids+" in ("+code+")'";
        }
        else
        {
            if ($(this).attr("href").indexOf(code) == -1)
                $(this).attr("href", $(this).attr("href")+"&where='"+ids+" in ("+code+")'");
            return true;
        }
    }

    //针对数据列表点击标题排序的接口
    if ($(this).attr("sort"))
    {
        if ($(this).children("span").hasClass("caret_up"))
            url += "&order="+$(this).attr("sort")+" desc";
            // $(this).attr("url", $(this).attr("url")+"&order="+$(this).attr("sort")+" desc");
        else
            url += "&order="+$(this).attr("sort");
            // $(this).attr("url", $(this).attr("url")+"&order="+$(this).attr("sort"));
    }

    // alert(url);

    // alert($(this).attr("url"));

    //局部刷新某个目标
    var tag = $(this).attr("tag");
    if (tag)
    {
        // alert($(this).attr("url"));
        // alert($(this).attr("tag"));
        // alert($($(this).attr("tag")).html());
        // alert(linkObj.responseText);
        // alert(linkObj.responseText.replace(/</g, '&lt;').replace(/>/g, '&gt;'));
        // var nhtml = '<pre>' + linkObj.responseText.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre>' + linkObj.responseText;
        // pop_up_window("aa", linkObj.responseText, 800, 700);
        // alert(url + " " + tag + " " + $(tag).length);
        $.ajax({url:url, dataType:"html", async:true,
                          success:function(data){
                              // alert($(tag).html());
                            $(tag).html(data);
                          }});

        return false;
    }

    //弹出窗口
    var pop = $(this).attr("pop");
    if (pop)
    {
                              // alert(url + " " + pop);
         $.ajax({url:url, dataType:"html", async:true,
                          success:function(data)
                          {
                              popUp(data, pop);
                          }});
        return false;
    }

    var jbox = $(this).attr("jbox");
    if (jbox)
    {
        jarr = jbox.split(",");
        run_jBox($(jarr[2]), jarr[0], jarr[1], function(){
            return url;
        });
    }


    //打开新标签加载url对应的网页
    if ($(this).attr("kopen"))
    {
        $("#open_new_tag").parent().attr("href", url);
        $("#open_new_tag").click();
        return false;
    }

    if ($(this)[0].tagName == "BUTTON" && $(this).attr("blink"))
    {
        location.href = url;
        return false;
    }

    //如果即不是弹窗也不是刷部刷新，则执行url地址，做某些服务器操作
    $.get(url, success, "json");
    // alert(url + " " + $(this).attr("href"));

    if ($(this).attr("href") != "" && $(this)[0].tagName == "A")
        return true;
    else
        return false;

    function success(data){
        if (data.echo)
            alert(data.info);
        if (data.close)
            $("#pop_win1").find(".pop_close").click();
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
        if (data.callback)
            eval(data.callback);
    }
}

//获取数据列表中选中的值
function get_list_chkval(selector)
{
    var code = "";
    if (arguments[1])
        var ids = arguments[1];

    $(selector).each(function(){
        var val = $(this).val();
        if ($(this).is(":checked") && val)
        {
            if (isNaN(val) || ids == "code")   //如果为字符串则为真
                code += "'"+val + "',";
            else
                code += val + ",";
        }
    });

   return code.substring(0, code.length - 1);
}

//动画效果返回到顶部
function anime_top()
{
    (window.opera) ?
        (document.compatMode=="CSS1Compat" ? $('html') : $('body'))
        : $('html,body').animate({
                'scrollTop': 0
            }, 500);
}

//局部刷新函数
function partial_refresh(url, tag)
{
    // alert(url + tag);
    if ($(tag).length == 0 || url == "")
        return false;

    $.ajax({url:url, dataType:"html", async:true,
                      success:function(data){
                        $(tag).html(data);
                        anime_top();
                      }});
}

//弹出窗口，获取url内部显示在弹出窗口上
function kpop_win_url(url, w, h, n, t)
{
    var pop = "{w:"+w+",h:"+h;
    if (arguments[5])
        pop += ",c:"+arguments[5];
    if (arguments[6])
        pop += ",b:"+arguments[6];
    pop += ",n:'"+n+"',t:"+t+"}";

    $.ajax({url:url, dataType:"html", async:false,
              success:function(data)
              {
                  popUp(data, pop);
              }});
}

//弹出窗口，直接把内部放到弹出窗口上
function kpop_win_text(text, w, h, n, t)
{
    var pop = "{w:"+w+",h:"+h;
    if (arguments[5])
        pop += ",c:"+arguments[5];
    if (arguments[6])
        pop += ",b:"+arguments[6];
    pop += ",n:'"+n+"',t:"+t+"}";

    popUp(text, pop);
}

//自动调节宽高
function autobodysize()
{
    var show_height = $(window).height(); //获取当前屏幕可视高度
    var body_height = $("#body").height();  //获取body高度
    var doc_height = $(document).height();

    // alert("window:"+$(window).width()+"x"+$(window).height()+", document:"+$(document).width()+"x"+$(document).height());
    //如果新调节的可视屏幕比#body高，则调节body ly loding高度
    if (show_height > body_height)   //如果主体内容不够屏幕可视高度
    {
        $("#body").css("min-height", show_height);
        doc_height = show_height + 70;
    }
    // else
        // $("#body").css("min-height", doc_height);

    $("#loading").height(doc_height);
    $("#ly").height(doc_height);
}

// alert(screen.availWidth+"+"+screen.availHeight+":"+navigator.userAgent+":"+window.name);
// window.open(location.href, "_", "width="+screen.width+", height="+screen.height);


//Jbox弹出框
function run_jBox(tag, w, h, callback)
{
    var jbox = new jBox('Tooltip', {
        target: tag,
        closeOnClick:'body',
        theme: 'TooltipBorder',
        // animation: 'pulse',
        position: {
            x: 'center',
            y: 'bottom'
        },
        offset: {x: 25},
        pointer: 'left:25',
        trigger:'click',
        adjustPosition:true,
        adjustTracker:true,
        onInit:function() {
            this.options.height = h;
            this.options.width = w;
        },
        onOpen:function() {
            var url = callback();
            var obj = this;
            $.ajax({url:url, dataType:"html", async:true,
                          success:function(data)
                          {
                              obj.setContent(data);
                          }});
        },
        onClose: function() {
            this.setContent("");
        }
    });

    jbox.open();
}

//监听浏览器大小改变
$(window).resize(function(){
    var nw = $(window).width();

    $("#ly").width(nw);
    $("#loading").width(nw);

    autobodysize();
});


//监听#body内容是否改变，如果改变则#body的高度自适应
// $(document).on("DOMNodeInserted", "#body", function(){
$(document).off("DOMSubtreeModified", "#body");
$(document).on("DOMSubtreeModified", "#body", function(){
    $("#body").css("min-height", 0);
    autobodysize();
});

//AJAX提交开始，显示正在加载当中。。。
$(document).ajaxStart(function(){
    var show_height = $(window).height() - 70; //获取当前屏幕可视高度，除导航栏
    var body_height = $("#body").height();  //获取body高度
    if ($("#body").length == 0)         //如果#body不存在，则读文档高度
        body_height = $(document).height();

    $("#loading").width($(window).width());
    $("#loading").height(body_height + 82);
    if (body_height > show_height + 100)
    {
        $("#loading img").css("top", (show_height - 124) / 2);
        $("#loading img").css("margin-top", 0);
    }
    else
    {
        $("#loading img").css("top", "50%");
        $("#loading img").css("margin-top", -62);
    }

    $("#loading").show();
});

//AJAX提交完成，隐藏正在加载当中
$(document).ajaxStop(function(){
    // $("#loading").hide();
    $("#loading").fadeOut();
    // $("#loading").slideUp();
});


function mousefilter()
{
    //禁用鼠标右键
    if (event.button == 2)
        return false;
}

function keyfilter()
{
    //完全禁用ctrl键
    // if (event.ctrlKey)
        // return false;

    if (!window.event)
        return false;

    var code = window.event.keyCode;
    //除了ctrl + c(67) ctrl + v(86)，其它的ctrl+快捷键全禁用
    if (event.ctrlKey && code != 67 && code != 86)
        return false;

    if (code >= 112 && code <= 115 ||
            code >= 117 && code <= 123)
        return false;
}

//通用提醒框窗口
function noticePop(info)
{
    if (!info)
        return false;

    new jBox('Notice', {
        animation: 'tada',
        audio: 'Public/audio/beep3',
        color: 'blue',
        autoClose: 30000,
        volume: 100,
        trigger: 'click',
        attributes: {
            x: 'right',
            y: 'bottom'
        },
        content: '<span style="cursor:pointer;"><strong>' + info + '</strong></span>'
    });
}

//更新站内信收件数字和残值查询数字
function updateSrvData()
{
    var ref = 0;
    if (arguments[0])
        ref = arguments[0];
    var url = "/index.php?s=/Home/Index/autoUpdateNum.html&ref="+ref;

    $.ajax({url:url, dataType:"json", global:false, async:false,
            success:function(data){
                $(".top_mess_num").html(data.msg.num);
                noticePop(data.msg.info);

                if (typeof(data.surplus) != "undefined")
                {
                    $(".top_surplus_num").html(data.surplus.num);
                    noticePop(data.surplus.info);
                }
        }});

    //自动更新收件信数字和残值列表数  每240秒更新一次
    // setTimeout("updateSrvData()", 3000);
}

$(document).ready(function()
{
//过滤鼠标和键盘键值
//document.onmousedown = mousefilter;
//禁用右键菜单
//document.oncontextmenu = function(e){return false;}
//禁用拖曳
//document.ondragstart  = function(){return false;}
//禁用选取内容
// document.onselectstart = function(e){return false;}
//document.onkeydown = keyfilter;

//主页首次进入调节#body宽高
autobodysize();

// var work = new Worker("Public/js/kyo_workers.js");
// work.onmessage = function(event){
    // alert(event.data);
    // eval(event.data);
// };

//初始更新数字
//updateSrvData(1);

//自动更新收件信数字和残值列表数  每240秒更新一次
//setInterval("updateSrvData()", 240000);
/*
 * if (typeof(EventSource) != "undefined")
 * {
 *     var source = new EventSource("/eclipse/Think_Card/index.php?s=/Home/Index/autoUpdateNum.html");
 *     source.onmessage = function(event)
 *     {
 *         var num = event.data.split("\n");
 *         // alert(event.data);
 *         $(".top_mess_num").html(num[0]);
 *         $(".top_surplus_num").html(num[1]);
 *     };
 * }
 */

//=================================== 导航开始 ============================
    //如果点击navbar-brand大标题时，清除所有导航active的样式,
    //并且获取当前的data自定义属性值进行AJAX获取数据显示到#body载体
    $(".navbar-brand").click(function(){
        $(".nav.navbar-nav li").each(function(){
            $(this).removeClass("active");
        });
        //htmlobj = $.ajax({url:$(this).attr("data"), async:false});
        //$("#body").html(htmlobj.responseText);
    });

    //如果点击导航中任意li中的a时执行
    $(".nav.navbar-nav li a").click(function(){
        //获取ul父结点，如果父结点不是ul继续向上匹配, 匹配到获取ul的classname
        //如果不是dropdown-menu则代表点击不是下拉菜单中的a，则不改变active样式
        //否则删除所有导航li中的active样式
        if ($(this).closest("ul").attr("class") != "dropdown-menu")
        {
            $(".nav.navbar-nav li").each(function(){
                $(this).removeClass("active");
            });
        }
        //设定li父结点为active样式
        $(this).parent("li").addClass("active");
        //如果当前a有data自定义属性，则获取数据显示到#body载体中
        if ($(this).attr("data"))
        {
            // $("#body").height(0);
            // alert($("#body").height());
            var htmlobj = "";
            var htmlUrl = $(this).attr("data");
            htmlobj = $.ajax({url:htmlUrl,
                              async:true,
                              dataType:"html",
                              success:function(data)
                              {
                                if (data.indexOf("<body") == -1)
                                {
                                    $("#body").html(data);
                                        //alert($(document).width());
                                    if ($(document).width() < 770)
                                    {
                                        $(".navbar-toggle").click();
                                    }
                                // if (htmlobj.responseText.indexOf("<body") == -1)
                                    // $("#body").html(htmlobj.responseText);
                                }
                                else
                                    location.href = htmlUrl;
                              }
            });
        }
    });


    // $(document).off("click", "button");
    // $(document).on("click", "button", function(){
        // $("button").prop("disabled", true);
        // setTimeout(function(){
            // $("button").prop("disabled", false);
        // }, 10000);
    // });

    //监控所有a和button还和input button的点击事件
    //防止重复绑定，先解绑原来绑定过的
    $(document).off('click', '#body a');
    $(document).off('click', '#body button[type!=submit][me!="me"]');
    $(document).off('click', '#body input[type=button]');
    $(document).off('click', '.pop_win a');
    $(document).off('click', '.pop_win button[type!=submit][me!="me"]');
    $(document).off('click', '.pop_win input[type=button]');
    $(document).off("click", ".kyo_img_linkop");
    $(document).off('change', '.kyo_select select');
    $(document).off("change", ".input_autocomplete");

    $(document).on('click', '#body a', linkop);
    $(document).on('click', '#body button[type!=submit][me!="me"]', linkop);
    $(document).on('click', '#body input[type=button]', linkop);

    $(document).on('click', '.pop_win a', linkop);
    $(document).on('click', '.pop_win button[type!=submit][me!="me"]', linkop);
    $(document).on('click', '.pop_win input[type=button]', linkop);
    $(document).on("click", ".kyo_img_linkop", linkop);
    $(document).on('change', '.kyo_select select', linkop);
    $(document).on("change", ".input_autocomplete", linkop);


    //监听查询按钮是否按下，如果按下则执行下列代码
    $(document).off("click", '.kyo_search_win #find');
    $(document).on("click", '.kyo_search_win #find', function() {
        var formObj = $(this).closest("form");
        var tag = $(this).attr("tag");
        // alert(formObj.attr("action") + formObj.serialize());
        // alert(formObj.attr("action") + " tag=" + tag);
        var pdata = "";
        var quit = false;

        formObj.find("select[name!='search_type']:visible").each(function(){
            if ($(this).val() != 0)
                pdata += $(this).attr("name") + "=" + $(this).val() + "&";
        });

        formObj.find("input[name!='search_key'][name$='_start']:visible").each(function(){
            var name = $(this).attr("id").replace("_id_start", "");
            // alert(name + $("#" + name + "_end").val());
            if ($(this).val() && $("#" + name + "_id_end").val())
            {
                pdata += name + "=" + $(this).val() + "," + $("#" + name + "_id_end").val() + "&";
                return false;
            }
            else
            {
                alert("请完善查询范围值！");
                quit = true;
                return false;
            }
        });

        if (quit)
            return false;

        var keyval = formObj.find("input[name='search_key']:visible").val();
        var typeval = formObj.find("select[name='search_type']:visible").val();


        if (typeof(keyval) != "undefined")
            keyval = keyval.replace(/ /g, "");
        formObj.find("input[name='search_key']:visible").val(keyval);

        if (typeval == 0 && keyval)
        {
            alert("请选择查询选项!");
            return false;
        }
        if (keyval && typeval)
        {
            pdata += "search_type=" + typeval + "&search_key=" + keyval;
        }
        else if (pdata != "")
            pdata = pdata.substring(0, pdata.length - 1);
        /*
         * else
         * {
         *     alert("没有设置任何查询选项!");
         *     return false;
         * }
         */

        // alert(pdata+"  ||| "+formObj.serialize());

        // return false;
        // alert(pdata + " " + formObj.attr("action"));
        // return true;
        // $.post(formObj.attr("action"), formObj.serialize(), function(data){
        $.post(formObj.attr("action"), pdata, function(data){
            // alert(data);
            $(tag).html(data);
        });
        return false;
    });

    //通用查询选项连动隐藏设置
    $(document).off('change', '.kyo_search_win select:visible');
    $(document).on('change', '.kyo_search_win select:visible', function(){
        if ($(this).attr("me"))
            return false;

        //获取当前选中值
        var setval = $(this).val();
        //获取查询输入框对象
        var inputObj = $(this).closest("form").find("input[name='search_key']");

        //循环此select的option列表
        $(this).children("option").each(function(){

            var opObj = $("#"+$(this).val()+"_id_start");
            var isNext = true;

            if (opObj.length <= 0)
            {
                opObj = $("#"+$(this).val()+"_id");
                isNext = false;
            }

            var liObj = opObj.parent("li");
            var liHide = liObj.hasClass("hidden");

            if (opObj.length > 0)
            {
                if ($(this).val() == setval && liHide)
                {
                     liObj.removeClass("hidden");
                     if (isNext)
                         liObj.next().removeClass("hidden");
                     if (!inputObj.hasClass("hidden"))
                         inputObj.addClass("hidden");
                }
                else if (!liObj.hasClass("hidden"))
                {
                    liObj.addClass("hidden");
                     if (isNext)
                        liObj.next().addClass("hidden");
                }
            }
            else if ($(this).val() == setval)
            {
                inputObj.removeClass("hidden");
            }
        });
    });

    //数据列表操作全选/全不选功能
    $(document).off('click', ".kyo_data_list th :checkbox");
    $(document).on('click', ".kyo_data_list th :checkbox", function(){
        // alert($(this).closest("table").children("tbody").find("[name=opChkId]:checkbox").length);
        // alert($(this).is(":checked"));
        var chk = $(this).is(":checked");
        $(this).closest("table").children("tbody").find("[name=opChkId]:checkbox").each(function(){
            // alert($(this).is(":checked") + " value = " + $(this).val() +", chkall = " + chk);
            $(this).prop("checked", chk);
        });
        // $("[name=opChkId]").attr("checked", true);
        // alert($(this).parent("table").children("input").attr("name", true));
        // alert($(this).parent("table").find(":checkbox").attr("name"));
    });


    //日期选择器
    $(document).off('focus click', ".kyo_form_date");
    $(document).on('focus click', ".kyo_form_date", KYO.setday);

    //主体内容上面工具栏和导航栏控制
    $(document).off("click", ".top_ctrl_up");
    $(document).on("click", ".top_ctrl_up", function (){
        if ($(this).hasClass("top_ctrl_down"))
        {
            $(".body_top").slideDown();
            // $(".navbar-inverse").removeClass("hidden");
            // $("body").css("padding-top", 50);
            $(this).parent().removeClass("hidden_top");
            $(this).removeClass("top_ctrl_down");
            $(this).children("span").removeClass("glyphicon-chevron-down");
            $(this).blur();
        }
        else
        {
            $(".body_top").slideUp();
            // $(".navbar-inverse").addClass("hidden");
            // $("body").css("padding-top", 0);
            $(this).parent().addClass("hidden_top");
            $(this).addClass("top_ctrl_down");
            $(this).children("span").addClass("glyphicon-chevron-down");
            $(this).blur();
        }
    });

    $(document).off("click", "[me=time_minus]");
    $(document).on("click", "[me=time_minus]", function(){
        $(this).closest("div").remove();
    });


    //审核拒绝原因选择处理
    $(document).off("change", "#audittype_id");
    $(document).on("change", "#audittype_id", function(){
        $("#remark_id").val($(this).val());
    });

});
