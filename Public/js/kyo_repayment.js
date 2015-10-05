//还款列表提交确认提示信息
function repayment_verify(formID)
{
    obj = $("#"+formID);
    
    if (!confirm("温馨提示：请详细检查资料录入是否正确，点击确定继续操作!"))
        return false;

    var fact = obj.find("#fact_amount_id").val();
    var put = obj.find("#put_amount_id").val();
    // if (parseFloat(fact) < 0)
    // {
        // alert("你输入的实还金额有误!");
        // fact.val("");
        // return false;
    // }

    if (parseFloat(fact) != parseFloat(put) &&
            !confirm("温馨提示：你修改了还款金额，是否继续操作!"))
        return false;
    return true;
}

function repayment_removeTr(id)
{
    num = $(".fin_repay_num").html();
    $(".fin_repay_num").html(parseInt(num) - 1);

    $("#verBtn"+id).closest("tr").remove();
    $("#verBtn"+(id+1)).removeClass("hidden");
}

$("#btn_repay_verify").click(function(){
    $("#verBtn"+$(this).attr("startVal")).removeClass("hidden");
});
