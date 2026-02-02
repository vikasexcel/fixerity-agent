
function errormesssageDisplay($message="Something went to wrong",$error_type="warning"){
    $(".bootstrap-growl .close").click();
    $.bootstrapGrowl($message,
        {
            type: $error_type, //info,success,warning and danger
            ele: "body",
            offset: {
                from: "top",
                amount: 20
            },
            align: "right",
            width: 300,
            delay: 2000,
            position: "fixed",
            allow_dismiss: true,
            stackup_spacing: 10
        });
}
