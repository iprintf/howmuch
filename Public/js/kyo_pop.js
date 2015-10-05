//不自动创建div弹出窗口 自适应宽高有问题
function popUp(text, option)   
{
    var w = 0;              //宽
    var h = 0;              //高
    var name = "";          //用于局部刷新目标名
    var title = false;      //标题
    var coverage = 0;       //是否覆盖或覆盖第几层
    var popObj = "";        //弹窗对象
    var lyObj = "";         //背景对象
    var titleObj = "";      //标题对象
    var bodyObj = "";       //弹窗主体内容对象
    var callback = "";      //关闭窗口的回调函数
    var i = 1;              //代表当前弹窗为第几层 

    var init = function()
    {
        var dw = $(window).width();
        //窗口宽
        var tmp = option.match(/w:(\d+)/);
        if (tmp)
            w = tmp[1];

        if (w >= dw)
            w = dw - 40;

        //窗口高
        tmp = option.match(/h:(\d+)/);
        if (tmp)
            h = tmp[1];

        //是否覆盖原来弹出框，0为不覆盖, 1代表覆盖最上面弹窗，以此类推
        tmp = option.match(/c:(\d+)/);      
        if (tmp)
            coverage = tmp[1];

        tmp = option.match(/n:'([a-zA-Z_]+)'/);    //目标名必须用单引号引起来
        if (tmp)
            name = tmp[1];

        tmp = option.match(/b:(.*),/);     //回调函数名
        if (tmp)
            callback = tmp[1];

        tmp = option.match(/t:(.*)}$/);     //标题必须放到最后写，写到}为止
        if (tmp)
            title = tmp[1];

        // alert(option + " " + name + " " + title + " back = " + callback);
    }

    var Obj = function()
    {
        lyObj = $("#ly");

        for (; i < 6; i++)
        {
            popObj = $("#pop_win" + i);
            if (popObj.hasClass("hidden"))
                break;
        }

        if (coverage)
        {
            if (i > 1)
                i--;
            popObj = $("#pop_win" + i);
        }

        if (!popObj)
            return false;

        return true;
    }


    var setup = function()
    {
        popObj.width(w);
        popObj.height(h);

        if (title)
        {
            titleObj = popObj.children(".pop_title");
            titleObj.html(title + '<span class="pop_close">&times;</span>');
            bodyObj = popObj.children(".pop_body");

            bodyObj.width(w - 10);
            bodyObj.height(h - 80);
            bodyObj.css("overflow", "auto");
            bodyObj.css("word-break", "break-all");
            bodyObj.css("padding", 5);
            bodyObj.css("margin", "auto");
            
            // alert(popObj.width() + " " + popObj.height() + ", body:"+ bodyObj.width() + " " + bodyObj.height());

            bodyObj.html(text);

            // alert(name);   //调试窗口句柄
            bodyObj.addClass(name);
        }
        else
        {
            popObj.html(text);
            popObj.addClass(name);
        }


        lyObj.css("z-index", 1042 + i);
        popObj.css("z-index", 1042 + i);


        lyObj.removeClass("hidden");
        popObj.removeClass("hidden");
        //动画效果返回到顶部
        anime_top();
    }

    var autosize = function()
    {
        /*
         * alert("bodyheight = "+ bodyObj.height() + ", outheight = " + popObj.outerHeight(true) +
         *        ", bodyWidth = "+ bodyObj.width() + ", outwidth = " + popObj.outerWidth(true));
         */
        // popObj.width(popObj.outerWidth() + 200 + Math.random()*100 + 1);
/*
 *         if (!w || w == 0)
 *             popObj.css("min-width", bodyObj.width() + 100);
 *         else
 *             popObj.width(w);
 * 
 *         if (!h || h == 0)
 *             popObj.css("min-height", bodyObj.height() + 100);
 *         else
 *             popObj.height(h);
 * 
 */
        /*
         * alert("bodyheight = "+ bodyObj.height() + ", outheight = " + popObj.outerHeight() +
         *        ", bodyWidth = "+ bodyObj.width() + ", outwidth = " + popObj.outerWidth());
         */

        // alert($(window).height());
        /*
         * if ((parseInt($(window).height()) - 70) < parseInt(popObj.height()))
         * {
         *     popObj.css("top", 70);
         * }
         * else
         */
        popObj.css("top", "50%");
        popObj.css("margin-top", "-" + (popObj.height() / 2) + "px");
        popObj.css("margin-left", "-" + (popObj.width() / 2) + "px");
        var offsetTop = popObj.offset().top;
        var pop_h = popObj.height();

        if (offsetTop < 75)
        {
            popObj.css("top", 40);   //弹出框距离顶宽度
            popObj.css("margin-top", 0);
        }

        //如果pop高度大于body的高，则调节body的高
        if (pop_h > $("#body").height() - 40)
        {
            // $("#body").css("min-height", popObj.height());
            $("#body").css("min-height", $(document).height() - 50);
        }


        lyObj.width($(window).width());
        lyObj.height($(document).height());
    }
    
    init();

    if (!Obj())
        return false;

    setup();
    autosize();


    // $(document).off("click", ".pop_win .pop_close");
    // $(document).on("click", ".pop_close", function(){
    $(".pop_win .pop_close").click(function(){
        // var popObj = $(this).parent().parent();
        if (callback)
        {
            if (callback.indexOf("(") == -1)
                callback += "('"+popObj.attr("id")+"')";
            eval(callback);
        }

        popObj.addClass("hidden");
        if (title)
        {
            // bodyObj.width("auto");
            // bodyObj.height("auto");
            // bodyObj.css("overflow", "visible");
            titleObj.html(titleObj.html().replace(title, ""));
            bodyObj.removeClass(name);
            bodyObj.html("");
        }
        else
        {
            popObj.removeClass(name);
            popObj.html("");
        }
        // alert(popObj.attr("id").replace("pop_win", ""));
        lyObj.css("z-index", parseInt(lyObj.css("z-index")) - 1);

        if (i === 1)
            lyObj.addClass("hidden");

    });
    return true;
}

