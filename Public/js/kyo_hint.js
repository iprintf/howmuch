//
//转换金额显示
//  s   要转换的字符串
//  n   保留小数点位数
function fmoney(s, n) 
{
    if (s.length == 0)
        return 0;

	s = parseFloat((s + "").replace(/[^\d\.-]/g, "")).toFixed(n) + "";
	var l = s.split(".")[0].split("").reverse();
    var r = n == 0 ? "" : "." + s.split(".")[1];
	var t = "";

	for (var i = 0; i < l.length; i++) {
		t += l[i] + ((i + 1) % 3 == 0 && (i + 1) != l.length ? "," : "");
	}
	return t.split("").reverse().join("") + r;
}

function fcard(s)
{
    if (s.length <= 4)
        return s;

    s = s.split("");
    var t = "";

    for (var i = 0, l = s.length; i < l; i++)
    {
        t += s[i] + ((i + 1) % 4 == 0 ? " " : "");
    }
    return t;
}

// $("form div .kyo_hint").click(function(){
$(document).off('focus click', 'div .kyo_hint');
$(document).on('focus click', 'div .kyo_hint', function(){
    var hint = $("#hint_" + $(this).attr("name"));
    if (hint.css("display") != "none")
        return true;

    //如果有此属性则代码提示框在输入框上方显示, 否则下方显示
    if ($(this).attr("kdirent"))
        hint.css("top", -55);
    else
        hint.css("top", 35);
    hint.css("left", -18);

    mlen = $(this).attr("maxlength");

    switch ($(this).attr("hint"))
    {
        case "string":
        case "card":
            if (mlen == 16)
                hint.css("width", 285);
            else if (mlen == 18)
                hint.css("width", 310);
            else if (mlen == 11)
                hint.css("width", 235);
            else
                hint.css("width", 350);
            hint.css("left", -46);
            maxlength = 20;
            break;
        case "phone":
            hint.css("width", 235);
            $(this).attr("maxlength", 11);
            maxlength = 11;
            break;
        case "money":
            hint.css("width", 245);
            $(this).attr("maxlength", 8);
            break;
        case "id":
            hint.css("width", 310);
            hint.css("left", -55);
            $(this).attr("maxlength", 18);
            break;
        case "float":
        default:
            hint.css("width", 200);
            maxlength = 11;
            break;
    }
    if (!$(this).attr("maxlength"))
        $(this).attr("maxlength", maxlength);

    hint.css("z-index", 9900);
    hint.show();
});

$(document).off("keyup", "form div .kyo_hint");
$(document).on("keyup", "form div .kyo_hint", function(event){
    var hint = $("#hint_" + $(this).attr("name"));
    var val = $(this).val();
    var type = $(this).attr("hint");
    var keycode = event.which;

    if (keycode == 13 || keycode == 18 || keycode == 27 || 
        keycode == 9 || keycode == 46 || keycode == 16 || keycode == 17 || 
        keycode == 20 || type != "id"  && type != "string" && 
        (keycode != 8 && !validity($(this), "num")))
        return false;

    if(val.length > $(this).attr("maxlength"))
    {
        alert("此字段必须在"+$(this).attr("maxlength")+"位之内!");
        return false;
    }

    switch (type)
    {
        case "string":
        case "card":
            hint.html(fcard(val));
            break;
        case "id":
            if (val.length < 6)
                hint.html(val);
            else if (val.length < 14)
                hint.html(val.substr(0, 6) + " " + val.substr(6, 8));
            else
                hint.html(val.substr(0, 6) + " " + val.substr(6, 8) + " " + val.substr(14, 4));
            break;
        case "phone":
            if (val.length < 4)
                hint.html(val);
            else if (val.length < 8)
                hint.html(val.substr(0, 3)+"-"+val.substr(3, 4));
            else
                hint.html(val.substr(0, 3)+"-"+val.substr(3, 4)+"-"+val.substr(7, 4));
            break;
        case "money":
            hint.html('￥'+fmoney(val, 2));
            break;
        case "float":
            hint.html(fmoney(val, 2));
            break;
        default:
            hint.html(fmoney(val, 0));
            break;
    }
});

$(document).off("blur", "form div .kyo_hint");
$(document).on("blur", "form div .kyo_hint", function(){
    $("#hint_" + $(this).attr("name")).hide();
});

