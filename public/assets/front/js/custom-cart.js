console.log(APP_URL);
///////// start code topping data html //////////////////
//opne modal with topping data

function  openModalWithToppings(prodId) {
    var restId = $('#restaurant_id').val();
    if(prodId > 0 && restId > 0){
        $.ajax({
            url: APP_URL+'/get-product-toppings',
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {"prodId":prodId,"rest_id":restId,'cart_id':0},
            async:false,
            cache: false,
            // contentType: false,
            // processData: false,
            success: function (data) {
                if (data.success == true) {
                    setmodalhtml(data.product_data);
                } else {
                    // location.reload();
                    var type = "danger";
                    var message = "Sorry currently this product is not availabel";
                    errorMessageDisplay(type,message);
                }
            }
        });
    }else{
        /*alert("Please Select Porper Product ")
        location.reload();*/
        var type = "danger";
        var message = "Sorry currently this product is not availabel";
        errorMessageDisplay(type,message);
    }
}
function setmodalhtml(data){

    /*var typeCls ="veg";
    var type = "Veg";
    if(data.food_type == 2){
        typeCls ="non-veg";
        var type = "Non-Veg";
    }*/
    console.log(parseFloat(data.product_amount).toFixed(2));
    var title ='' +
        '<div class="container-fluid">' +
        '   <div class="row">' +
        '       <div class="col-md-8 prodTitle"> '+data.product_name+'</div>' +
        '       <div class="col-md-4 pl-0 pr-0"> <span class="currency"></span> '+parseFloat(data.product_amount).toFixed(2)+'</div>' +
        '   </div>' +
        '   <div class="col-md-12">' +
        '       <p class="m-0" style="font-size: 16px;">'+data.description+'</p>' +
        '   </div>' +
        '</div>';
    var html = "";
    $('.modal-title').html(title);
    if(data.customize_list.length > 0)
    {
        html += '<form method="post" name="add_cart" enctype="multipart/form-data"  id="add_cart"><input type="hidden" name="productId" id="productId" value="'+data.product_id+'" /> <input type="hidden" name="cart_id" id="cart_id" value="0" /> ' +
            '<div class="container" style="padding-left: 15px; font-size: unset;">';
        $.each(data.customize_list, function () {

            var customize_type = this.customize_type;
            var category_id = this.category_id;
            var category_name = this.category_name;
            category_name = category_name.replace(/\s+/g, '_').toLowerCase();

            // console.log(customize_type);
            var cnt = 0;
            var chked = "";
            html += '<div class="row"> ' +
                '       <div class="col-md-12 col-xs-12">' +
                '           <span class="topping-header">'+this.category_name+'</span>' +
                '       </div>' +
                '       <div class="col-md-12 col-xs-12">';

            $.each(this.options,function (){
                // html +="cat_name"+this.name
                //code for quantity

                if(customize_type === 1 || customize_type === "1"  ){
                    if(this.checked == "1"){
                        chked = "checked";
                    }else{
                        if(cnt === 0){
                            chked = "checked";
                        }else{
                            chked = "";
                        }
                    }
                    cnt++;
                    html += '<div class="row"> ' +
                        '       <div class="col-md-8"> ' +
                        '           <div class="radio">' +
                        '               <label class="cart_label">' +
                        '                   <input type="radio" ' + chked + ' class="pricecalc" data-type="qty" name="qty_' + category_id + '" data-price="' + this.amount + '" id="qty_' + this.id + '" value="' + this.id + '">' +
                        '                   <span class="cr"><i class="cr-icon fa fa-circle"></i></span>' + this.name +
                        '               </label>' +
                        '           </div>' +
                        '       </div>' +
                        // '<div class="col-md-4 col-xs-4"> <span class="singlePrice"> '+parseFloat(this.discount_amount).toFixed(2)+' <del> '+this.amount+'</del></span></div></div>'
                        '       <div class="col-md-4">' +
                        '           <span class="singlePrice currency">'  + parseFloat(this.amount).toFixed(2) + ' <del class="currency"> ' + (this.discount_amount) + '</del></span>' +
                        '       </div>' +
                        '    </div>';
                }
                //code for options
                if(customize_type === 2 || customize_type === "2"  ){
                    if(this.checked == "1"){
                        chked = "checked";
                    }else{
                        // chked = "";
                        if(cnt === 0){
                            chked = "checked";
                        }else{
                            chked = "";
                        }
                    }
                    html +='<div class="row">' +
                        '       <div class="col-md-8">' +
                        '           <div class="radio">' +
                        '               <label class="cart_label">' +
                        '                   <input type="radio" '+chked+' class="pricecalc" data-type="opt" name="opt_'+category_id+'" data-price="'+this.amount+'" id="opt_'+this.id+'" value="'+this.id+'">' +
                        '                   <span class="cr"><i class="cr-icon fa fa-circle"></i></span>'+ this.name +'' +
                        '               </label>' +
                        '           </div>' +
                        '       </div>'+
                        '       <div class="col-md-4"> <span class="currency"></span>' +parseFloat(this.amount).toFixed(2)+'</div>' +
                        '   </div>';
                }
                //code for toppings
                if(customize_type === 3 || customize_type === "3"  ){
                    if(this.checked == "1"){
                        chked = "checked";
                    }else{
                        chked = "";
                    }
                    html +='<div class="row">' +
                        '       <div class="col-md-8">' +
                        '           <div class="checkbox">' +
                        '               <label class="cart_label">' +
                        '                   <input type="checkbox" '+chked+' class="pricecalc" data-type="top" name="top_'+category_id+'[]" data-price="'+this.amount+'" id="top_'+this.id+'" value="'+this.id+'">' +
                        '                   <span class="cr"><i class="cr-icon fa fa-check"></i></span>'+ this.name +
                        '               </label>' +
                        '           </div>' +
                        '       </div>'+
                        '       <div class="col-md-4"> <span class="currency"></span> '+parseFloat(this.amount).toFixed(2)+'</div>' +
                        '   </div>';
                }
            });
            html += '</div></div>';
        });
        html += '</div></form>';
    }
    else{
        html += '<form method="post" name="add_cart" enctype="multipart/form-data"  id="add_cart"><input type="hidden" name="productId" id="productId" value="'+data.product_id+'" /> <input type="hidden" name="cart_id" id="cart_id" value="0" /> ';
        html += '<div class="col-md-12 col-sm-12 col-lg-12 ">'+messages_130+'</div>';
        html += '</form>';
    }
    $('.modal-body').html(html);
    var footer = '<div class="row"><div class="col-md-12 col-xs-12"><span type="button" style="width: 100%" class="btn btn-success addToCart btn-lg btn-md"><div class="col-md-6 col-sm-6 col-xs-62 add_cart_item_left" > '+messages_131+' </div>   <input type="hidden" id="finalpriceAmt" value="'+data.product_amount+'" /> <div class="col-md-6 col-sm-6 col-xs-12 finalpriceAmtTxt add_cart_item_right">'+messages_131+' : '+parseFloat(data.product_amount).toFixed(2)+'</div></span></div></div>';
    $('.modal-footer').html(footer);
    $('#myModal').modal('show');
    updateFinalPrice()
}
$(document).ready(function() {

    $(document).on('change', '.pricecalc', function () {
        var selectedIdData = $(this).attr('id');
        var selectedid = $(this).val();
        var selectedPrice = $(this).attr('data-price');
        var finalPriceAmt = $("#finalpriceAmt").val();
        updateFinalPrice()
    });
    //click add to cart button
    $(document).on("click",".addToCart",function(){
        var prodcutId = $("#productId").val();
        if(prodcutId > 0){

            // addItems(prodcutId);

            //new code for cart items
            var restaurant_id = $('#restaurant_id').val();

            var form = $("#add_cart");
            // console.log(form.serialize());
            form = form.serialize();
            $.ajax({
                url: APP_URL+'/temp-cart-store',
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: form,
                cache: false,
                async:false,
                /*contentType: false,
                processData: false,*/
                success: function (data) {
                    if (data.success == true) {
                         console.log(data);
                         console.log('success');
                        $(".close").click();
                        var cart_element = $(".product-cart-list");
                        $("#cart_store_id").val(data.cart_store_id);
                        cart_element.empty();
                        cart_element.append(data.cart_item_data);
                        $(".badge").empty();
                        $(".badge").append(data.cart_item);
                        $(".count-cart").empty();
                        $(".count-cart").append(data.cart_item);
                        /*$("#item-total").empty();
                        $("#item-total").append(data.cart_item_amount);

                        $("#total-discount").empty();
                        $("#total-discount").text(data.discount);

                        $("#item-tax").empty();
                        $("#item-tax").text(data.tax);

                        $("#item-delivery-charges").empty();
                        $("#item-delivery-charges").text(data.delivery_charges);

                        $("#item-packaging-charges").empty();
                        $("#item-packaging-charges").text(data.packaging_charges);*/

                        /*$("#to-pay-grand-total").empty();
                        $("#to-pay-grand-total").text(data.total_pay); */

                        $("#all-item-total").empty();
                        $("#all-item-total").text(data.all_store_total_pay);

                        $("#store_service_cat").empty();
                        $("#store_service_cat").val(data.store_service_cat);

                        $("#all-store-to-pay-grand-total").empty();
                        $("#all-store-to-pay-grand-total").append(data.all_store_total_pay);

                        // $("#form_address_delivery").val("");

                    } else {
                        /*console.log(data);*/
                        $(window).scrollTop(0);
                        location.reload();
                    }
                }
            });

            $(".close").click();
        }else{

            $(".close").click();
            var type = "danger";
            var message = "Sorry currently this product is not availabel";
            errorMessageDisplay(type,message);
        }
    });

    //new code for remove,add,minus product
    $(document).on("click",".removeProduct",function () {
        var operationData = $(this).attr("id");
        performUpdateOperation(operationData);
    });
    //new code for add product
    $(document).on("click",".plusProduct",function () {
        var operationData = $(this).attr("id");
        performUpdateOperation(operationData);
    });
    //new code for minus product
    $(document).on("click",".minusProduct",function () {
        var operationData = $(this).attr("id");
        performUpdateOperation(operationData);
    });

    //modify cart product items
    $(document).on("click",".productTitle",function () {
        var cartId =    $(this).attr("id") ;
        var prodId =    $(this).attr("data-productId") ;
        var storeId =    $(this).attr("data-storeId") ;
        openModalWithToppingsUpdate(prodId,storeId,cartId)
    });


});

function  openModalWithToppingsUpdate(prodId,storeId,cartId) {

    if(prodId > 0 && storeId > 0){
        $.ajax({
            url: APP_URL+'/get-product-toppings',
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {"prodId":prodId,"rest_id":storeId,'cart_id':cartId},
            async:false,
            cache: false,
            // contentType: false,
            // processData: false,
            success: function (data) {
                if (data.success == true) {
                    setmodalhtml(data.product_data);
                    $("#cart_id").val(cartId);
                } else {
                    // location.reload();
                    var type = "danger";
                    var message = "Sorry currently this product is not availabel";
                    errorMessageDisplay(type,message);
                }
            }
        });
    }else{
        /*alert("Please Select Porper Product ")
        location.reload();*/
        var type = "danger";
        var message = "Sorry currently this product is not availabel";
        errorMessageDisplay(type,message);
    }
}
function updateFinalPrice() {

    var tot = 0;
    $('.pricecalc:checked').each(function(){
        price = parseFloat($(this).attr('data-price'));
        type =$(this).attr('data-type');

        if(type === "qty"){
            //code set finalprice base on quantity select
            $("#finalpriceAmt").val(price);
        }else{
            if(price > 0){
                tot += price;
            }
        }
    });
    var mainPirce = parseFloat($("#finalpriceAmt").val());
    mainPirce +=tot;
    $(".finalpriceAmtTxt").text(messages_131 +" : $ " +parseFloat(mainPirce).toFixed(2));
}
function errorMessageDisplay(type,message) {
    $(".bootstrap-growl .close").click();
    $('.bootstrap-growl').remove();
    $.bootstrapGrowl(message, // Messages
        { // options
            type: type, // info, success, warning and danger
            ele: "body", // parent container
            offset: {
                from: "top",
                amount: 20
            },
            align: "right", // right, left or center
            width: 300,
            delay: 2000,
            position: "fixed",
            allow_dismiss: true, // add a close button to the message
            stackup_spacing: 10,
            zIndex:999999
        });
}

//function for update opration del,minus,plus product
function performUpdateOperation(operationData) {
    var address_id = $("#form_address_delivery").val();
    if(address_id != "")
    {
        address_id = address_id;
    }else{
        // address_id = 0;
        address_id = "";
    }
    console.log(address_id);
    $.ajax({
        url: APP_URL+'/temp-cart-update',
        type: 'post',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {"data":operationData,'address_id':address_id},
        cache: false,
        async:false,
        /*contentType: false,
        processData: false,*/
        success: function (data) {
            if (data.success == true) {
                // console.log(data);
                $(".close").click();
                var cart_element = $(".product-cart-list");
                cart_element.empty();
                cart_element.append(data.cart_item_data);
                $(".badge").empty();
                $(".badge").append(data.cart_item);
                $(".count-cart").empty();
                $(".count-cart").append(data.cart_item);

                /*$("#item-total").empty();
                $("#item-total").append(data.cart_item_amount);

                $("#total-discount").empty();
                $("#total-discount").text(data.discount);

                $("#item-tax").empty();
                $("#item-tax").text(data.tax);

                $("#item-delivery-charges").empty();
                $("#item-delivery-charges").text(data.delivery_charges);

                $("#item-packaging-charges").empty();
                $("#item-packaging-charges").text(data.packaging_charges);

                $("#to-pay-grand-total").empty();
                $("#to-pay-grand-total").text(data.total_pay);*/

                $("#all-item-total").empty();
                $("#all-item-total").text(data.all_store_total_pay);

                $("#store_service_cat").empty();
                $("#store_service_cat").val(data.store_service_cat);

                $("#all-store-to-pay-grand-total").empty();
                $("#all-store-to-pay-grand-total").append(data.all_store_total_pay);

                // $("#form_address_delivery").val("");


            } else {
                // console.log(data);
                // alert("wrong data");
            }
        }
    });
}


