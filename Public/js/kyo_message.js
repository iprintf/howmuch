
$(document).ready(function(){
    $("#summernote").summernote({
        height:200,
        minHeight:100,
        maxHeight:200,
        focus:true,
        toolbar:[
           ['style', ['style']],
           ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
           ['fontsize', ['fontsize']],
           ['color', ['color']],
           ['height', ['height']],
           ['para', ['ul', 'ol', 'paragraph']],
           ['table', ['table']],
           ['insert', ['link', 'picture', 'hr']],
           ['misc', ['fullscreen']],
        ]
    });
    
    //初始化，默认选择所有人，所以将没有隐藏的分站和分组选择框隐藏
    if (!$("input[id='proxy_sel_id']:checked").val())
    {
        if (!$(".sub_class_sel").hasClass("hidden"))
            $(".sub_class_sel").addClass("hidden");
        if (!$(".grp_class_sel").hasClass("hidden"))
            $(".grp_class_sel").addClass("hidden");
    }

    //点击选择代理 选择代理后显示分站和分组选择框
    $("input[id='proxy_sel_id']").click(function(){
        var val = $(this).val();
        var obj = $(this);
        if (val == 3)
        {
            if (!$(".grp_class_sel").hasClass("hidden"))
                $(".grp_class_sel").addClass("hidden");
            if (!$(".sub_class_sel").hasClass("hidden"))
                $(".sub_class_sel").addClass("hidden");
        }

        run_jBox($(this), 480, 130, function(){
            return  obj.attr("url") + "&val=" + val;
        });
         
    });

    //选择所有代理显示分组框
    $("input[id='proxy_all_sel_id']").click(function(){
        //如果选择所有代理，则分站不能选择，默认为所有分站
        $("#sub_all_sel_id").click();
        //分站选择框如果隐藏则显示
        if ($(".sub_class_sel").hasClass("hidden"))
            $(".sub_class_sel").removeClass("hidden");
        //分组选择框如果隐藏则显示
        if ($(".grp_class_sel").hasClass("hidden"))
            $(".grp_class_sel").removeClass("hidden");

        //如果选择所有代理 则分组里的代理商才可以看到
        if ($(".grp_proxy_sel").hasClass("hidden"))
        {
            $(".grp_proxy_sel").removeClass("hidden");
            $(".grp_proxy_sel input").prop("name", "grp_sel");
        }
    });


    //选择所有人和助理 则隐藏没有隐藏的分站和分组选择框
    $("input[id='main_sel_id']").click(function(){
        if (!$(".grp_class_sel").hasClass("hidden"))
            $(".grp_class_sel").addClass("hidden");
        if (!$(".sub_class_sel").hasClass("hidden"))
            $(".sub_class_sel").addClass("hidden");
    });

    //如果选择了选择了代理把值和文本同步显示并且关闭列表窗口
    $(document).off("click", "input[id='proxymem_sel_id']");
    $(document).on("click", "input[id='proxymem_sel_id']", function(){
        $("#proxy_sel_id").val($(this).val());
        $("#label_proxy_sel_id").html($(this).next().html());
        //分站选择框如果隐藏则显示
        if ($(".sub_class_sel").hasClass("hidden"))
            $(".sub_class_sel").removeClass("hidden");
        //分组选择框如果隐藏则显示
        if ($(".grp_class_sel").hasClass("hidden"))
            $(".grp_class_sel").removeClass("hidden");
        $(document).click();
    });

    //点击所有分站，则显示分组代理商
    $("#sub_all_sel_id").click(function(){
        //如果选择所有代理 则分组里的代理商才可以看到
        if ($(".grp_proxy_sel").hasClass("hidden"))
        {
            $(".grp_proxy_sel").removeClass("hidden");
            $(".grp_proxy_sel input").prop("name", "grp_sel");
        }
    });

    //点击选择分站 选择分站弹出分站列表
    $("input[id='sub_sel_id']").click(function(){
        var proxy = $("#proxy_sel_id").val();
        var obj = $(this);
        if (proxy == 3 || $("input[id='proxy_all_sel_id']:checked").val())
            return false;

        //如果选择了某个分站，则隐藏分组的代理商
        if ($(this).val() != 1 && !$(".grp_proxy_sel").hasClass("hidden"))
        {
            $(".grp_proxy_sel input").prop("name", "hidden_el_name");
            $(".grp_proxy_sel").addClass("hidden");
        }
        
        run_jBox($(this), 480, 130, function(){
            var url = obj.attr("url") + "&proxy=" + $("#proxy_sel_id").val();
            url += "&val=" + obj.val();
            return url;
        });

    });

    //如果选择了选择了分站把值和文本同步显示并且关闭列表窗口
    $(document).off("click", "input[id='submem_sel_id']");
    $(document).on("click", "input[id='submem_sel_id']", function(){
        //如果选择了某个分站，则隐藏分组的代理商
        if (!$(".grp_proxy_sel").hasClass("hidden"))
        {
            $(".grp_proxy_sel input").prop("name", "hidden_el_name");
            $(".grp_proxy_sel").addClass("hidden");
        }
        $("#sub_sel_id").val($(this).val());
        $("#label_sub_sel_id").html($(this).next().html());
        $(document).click();
    });

    $("input[name='grp_sel'][id!='grp_all_sel_id']").click(function(){
        var obj = $(this);
        var id = $(this).attr("id");
        var isSel = false;    //临时保存除当前对象的其它对象是否有选中
        var curSel = $(this).is(":checked");  //当前点击对象是否选中

        //循环分组所有元素判断是否有选中项, 除了产生事件对象外
        $("input[name='grp_sel'][id!='grp_all_sel_id']").each(function(){
            if ($(this).attr("id") != id && $(this).is(":checked"))
            {
                isSel = true;
                return false;
            }
        });

        $("#usrlist_id").val(0);

        if (!curSel)   //只要组里有取消选中的就把所有组取消
            $("#grp_all_sel_id").prop("checked", false);

        if (!curSel || isSel || 
                $("#proxy_all_sel_id").is(":checked") || 
                $("#sub_all_sel_id").is(":checked") ||
                $("#proxy_sel_id").val() == 3 ||
                $("#sub_sel_id").val() == 1 ||
                !$(this).attr("url"))
            return true;

        run_jBox($(this), 420, 200, function(){
            var url = obj.attr("url");
            url += "&sid=" + $("#sub_sel_id").val();
            url += "&proxy=" + $("#proxy_sel_id").val();
            url += "&val=" + $("#usrlist_id").val();
            return url;
        });
    });

    //选中所有组和取消所有组
    $("input[id='grp_all_sel_id']").click(function(){

        var isSel = $(this).is(":checked");
        if (isSel) 
            $("input[name='grp_sel']").prop("checked", true);
        else
            $("input[name='grp_sel']").prop("checked", false);
        $("#usrlist_id").val(0);
    });

    //如果选择了选择了人员把值和文本同步显示并且关闭列表窗口
    $(document).off("click", "input[id*='grpmem_sel']");
    $(document).on("click", "input[id*='grpmem_sel']", function(){
        var val = "|";
        var isAll = true;
        var name = $(this).attr("name");

        $("input[name='"+name+"']").each(function(){
            if ($(this).is(":checked") && $(this).val() != 0) 
                val += $(this).val() + "|";
            else 
                isAll = false;
        });

        if (!isAll)
            $("input[id='grpmem_all_sel_id"+name.substr(10, 1)+"']").prop("checked", false);

        if (val == "|")
            $("#usrlist_id").val(0);
        else
            $("#usrlist_id").val(val);
    });


    //选中所有人和取消所有人选择
    $(document).off("click", "input[id*='grpmem_all_sel_id']");
    $(document).on("click", "input[id*='grpmem_all_sel_id']", function(){
        var isSel = $(this).is(":checked");
        var name = $(this).attr("name");
        if (isSel) 
            $("input[name='"+name+"']").prop("checked", true);
        else
            $("input[name='"+name+"']").prop("checked", false);
    });

    $("#assist_sel_id").click(function(){
        $("#usrlist_id").val(3);
    });

});
