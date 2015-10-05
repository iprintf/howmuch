
// $(document).off("change", ".rep_type");
// $(document).on("change", ".rep_type", function(){

$(".rep_type").change(function(){
    var obj = $("input[name='rep_type']:checked");
    var url = obj.attr("url") + "&type=" + obj.val();
    partial_refresh(url, obj.attr("tag"));
});


// $(document).off("click", "input[name='rep_name']");
// $(document).on("change", "input[name='rep_name']", function(){
$("input[name='rep_name']").click(function(){
    var sdate = $("#start_date_id").val();
    var edate = $("#end_date_id").val();
    var proxy = $("#proxy_id").val();
    var sub = $("#sub_id").val();
    var val = $(this).val();
    var grp = $(this).attr("grp");

    if (sdate == "" || edate == "")
    {
        alert("请选择起始日期或结束日期!");
        return false;
    }

    if (val == "")
    {
        alert("选择报表不存在，请联系管理员!");
        return false;
    }

    var pdata = "&small=" + $(this).val() + "&grp=" + grp;
    pdata += "&sdate=" + sdate + "&edate=" + edate;

    if (proxy)
        pdata += "&proxy=" + proxy;

    if (sub)
        pdata += "&sid=" + sub;

    var pop = $(this).attr("pop");

    // alert(pdata);

    $.ajax({url:$(this).attr("url") + pdata, dataType:"html", async:false,
          success:function(data)
          {
              popUp(data, pop);
          }});
});
