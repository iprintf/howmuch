//pos商户类型选择连动特效
function getPosType()
{
    var url = $("#mcc_input").attr("url")+"&val="+$("#mcc_input").val();

    $.ajax({url:url, dataType:"json", async:true,
        success:function(data)
        {
            $("#cost_id").val(data.mcc_cost);
            $("#month_max_id").val(data.month_max);
            $("#day_max_id").val(data.day_max);
            $("#dealtime_id").html(data.time);
        }});
// });
}

$(document).off("change", "#month_max_id");
$(document).on("change", "#month_max_id", function(){
    $("#day_max_id").val(parseInt($(this).val() / 30 / 10) + "0");
});
