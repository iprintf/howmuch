
$("#code_id").change(function(){

    if ($(this).val().length == 0)
        return;


    var v = $("#code_input").val().split(",");
    $("#code_input").val(v[0]);
    $("#name_id").val(v[1]);

    v = $(this).val().split(",");

    $("#merchant_id").val(v[0]);
    $("#unit_price_id").val(v[1]);
    $("#unit_id").val(v[2]);
    $("#label_id").val(v[3]);
    $("#gid_id").val(v[4]);

    $(this).val($("#code_input").val());

});

$("#quantity_id").change(function(){
    $("#total_id").val(($(this).val() * $("#unit_price_id").val()).toFixed(2));
});

$("#unit_price_id").change(function(){
    $("#total_id").val(($(this).val() * $("#quantity_id").val()).toFixed(2));
});

// $("#name_id").click(function(){
    //alert($("#code_id").val());
// });
