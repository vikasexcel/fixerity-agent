<link rel="stylesheet" href="{{ asset('assets/css/country-code/intlTelInput.css')}}" type="text/css">
<style>
    #contact_number, #store-signup-contact {
        width: 100%;
        /*height: auto;*/
        padding-left: 90px !important;

        outline: none;
        font-size: 1em;
        color: #000;
        padding: 1px 5px;
        margin: 0;
        border: 1px solid #7a73735c;
        height: 42px;
        /*margin-bottom: 1em;*/
        margin-top: 15px;
        /*border-left: 4px solid #8702ae;*/
    }
    .iti,#btn-sign-up{
        /*margin-top: 1em !important;*/
        /*margin-bottom: 1em !important;*/
    }
    .error {
        color: red;
        font-weight: 500;
    }
    .red-star {
        color: red;
        font-weight: 1000;
    }
    #store-register .resp-tab-item{
        width: 100%;
    }
</style>
<!--user login register start-->
<!--<div id="cover-spin">
</div>-->
<div id="store-register" class="modal">

    <div class="user">
        <span  class="close closeBtn" title="Close">&times;</span>
        <div class="col-md-6 col-md-offset-3 loginform">
            <ul class="resp-tabs-list">
                <li class="resp-tab-item" id="btn-login-reload-1"><span>Register</span></li>
            </ul>
            <div class="resp-tabs-container">

                <div class="tab-1 resp-tab-content" id="btn-signup-reload-2">
                    <div class="login-top sign-top">
                        <form method="post" id="storesignupform" action="javascript:void(0)" >
                            @csrf
                            <span class="error"></span>
                            <input type="hidden" name="registerType" id="registerType" value="0">

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="store_signup_name" class="col-sm-12 col-form-label">Name : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" name="name" id="store_signup_name" placeholder="Please enter name" >
                                        </div>
                                    </div>
                                </div>


                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="signup_contact" class="col-sm-12 col-form-label">Contact No : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">

                                            <input type="text" class="form-control" name="store_signup_contact" placeholder="Contact No " autocomplete="off"  id="store-signup-contact" value="">
                                            <input type="hidden" id="store_contact_numbers" name="store_contact_numbers" value="">
                                            <input type="hidden" id="store_country_code" name="store_country_code" >
                                            <span id="phone_error" class="error">{{ $errors->first('store_contact_number') }}</span>
                                            <span class="error">{{ $errors->first('store_full_number') }}</span>
                                            <span class="error">{{ $errors->first('store_contact_numbers') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="store_signup_email" class="col-sm-12 col-form-label">Please enter email : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="email" class="form-control" name="email" id="store_signup_email" placeholder="Please enter email" >
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="request_your_city" class="col-sm-12 col-form-label">Gender:</label>
                                        <div class="col-sm-12">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input genderRadio" type="radio" name="gender" id="male" value="1" checked>
                                                <label class="form-check-label" for="inlineRadio1">Male</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input genderRadio" type="radio" name="gender" id="female" value="2">
                                                <label class="form-check-label" for="inlineRadio2">Female</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="password" class="col-sm-12 col-form-label">Password : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="password" class="form-control" name="password" id="store_signup_password" placeholder="Please enter password" >
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="retype_password" class="col-sm-12 col-form-label">Confirm Password : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="password" class="form-control" name="retype_password" id="store_signup_retype_password" placeholder="Please enter confirm password" >
                                        </div>
                                    </div>
                                    <span id='password-error'></span>
                                </div>


                                <div class="form-group col-12">
                                    <div class="text-center">
                                        <input type="submit" value="Register" id="store-btn-sign-up" class="text-center mx-auto btn btn-lg">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
</div>
<!--user login register end-->

<script type="text/javascript" src="{{ asset('assets/js/country-code/intlTelInput.min.js')}}"></script>

<script type="text/javascript">
    var storeinput = document.querySelector("#store-signup-contact");
    var storeini = window.intlTelInput(storeinput, {
        // allowDropdown: true,
        // autoHideDialCode: true,
        // autoPlaceholder: "off",
        // dropdownContainer: document.body,
        // excludeCountries: ["us"],
        // formatOnDisplay: false,
        // geoIpLookup: function(callback) {
        //   $.get("http://ipinfo.io", function() {}, "jsonp").always(function(resp) {
        //     var countryCode = (resp && resp.country) ? resp.country : "";
        //     callback(countryCode);
        //   });
        // },
        hiddenInput: "store_full_number",
        initialCountry: "us",
        // localizedCountries: { 'de': 'Deutschland' },
        // nationalMode: false,
        // onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
        // placeholderNumberType: "MOBILE",
        preferredCountries: ['us'],
        separateDialCode: true,
        // initialCountry: "auto",
        // geoIpLookup: function(success, failure) {
        //     $.get("https://ipinfo.io", function() {}, "jsonp").always(function(resp) {
        //         var countryCode = (resp && resp.country) ? resp.country : "";
        //         success(countryCode);
        //     });
        // },
        utilsScript: "{{ asset('assets/js/country-code/utils.js')}}",
    });
    var store_country_code = storeini.getSelectedCountryData()['dialCode'];

    if(store_country_code > 0){
        store_country_code = "+"+store_country_code;
        document.getElementById("store_contact_numbers").innerHTML = '';
    }else{
        store_country_code = "+1";
        document.getElementById("store_contact_numbers").innerHTML = 'Invalid Country Code';
    }
    $("#store_country_code").val(store_country_code);
    storeinput.addEventListener("countrychange", function() {
        console.log(storeini.getSelectedCountryData()['dialCode']);
        var store_country_code = storeini.getSelectedCountryData()['dialCode'];

        if(store_country_code > 0){
            store_country_code = "+"+store_country_code;
            document.getElementById("store_contact_numbers").innerHTML = '';
        }else{
            store_country_code = "+1";
            document.getElementById("store_contact_numbers").innerHTML = 'Invalid Country Code';
        }
        $("#store_country_code").val(store_country_code);
    });
    $("#store-signup-contact").on('keyup', function (event) {
        var contact_number = $(this).val();
        var n = contact_number.indexOf("0", 0);
        // var n = contact_number.charAt(contact_number);
        if (n == 0) {
            document.getElementById("phone_error").innerHTML = 'Invalid Contact Number';
            document.getElementById("store_contact_numbers").value = "";
        } else {
            document.getElementById("store_contact_numbers").value = contact_number;
            document.getElementById("phone_error").innerHTML = '';
            console.log(contact_number);
        }
    });
</script>
<script rel="stylesheet" src="{{ asset('assets/js/validation/jquery.validate.js')}}"></script>
<script>
    $(document).ready(function (){
       $(document).on("click",".closeBtn",function (){
           $("#storesignupform")[0].reset();
           $("label.error").hide();

           document.getElementById('store-register').style.display='none';


       });

        $(document).on("click",".registerFormBtn",function (){
            var providerType = $(this).attr("data_type");
            document.getElementById('store-register').style.display='block';
            if(providerType == "store"){
                $("#registerType").val(0);
            }else{
                $("#registerType").val(1);
            }

        });


        jQuery.validator.addMethod("emailfull", function(value, element) {
            return this.optional(element) || /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i.test(value);
        }, "Please enter valid email address!");

        $("#storesignupform").validate({
            rules: {
                name: {
                    required: true,
                },
                store_signup_contact :{
                    required :"Please enter contact number",
                    number :"Please enter valid contact number",
                },
                email: {
                    required : true,
                    emailfull : true,
                },
                password: {
                    required : true,
                },
                retype_password: {
                    required : true,
                    equalTo : "#store_signup_password"
                }
            },
            messages: {
                name: {
                    required :"Please enter name",
                },
                store_signup_contact :{
                    required :"Please enter contact number",
                    number :"Please enter valid contact number",
                },
                email: {
                    required :"Please enter email",
                    emailfull :"Please enter valid email",
                },
                password: {
                    required :"{{ __('messages.242') }}",
                },
                retype_password: {
                    required :"{{ __('messages.52') }}",
                    equalTo : "{{ __('messages.56') }}"
                }

            },
            errorPlacement: function(error, element) {
                if(element.attr("name") == "store_signup_contact") {
                    error.insertAfter( element.parent("div .iti"));
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                form.submit();
            }
        });

        $('#store-btn-sign-up').on('click', function () {
            if($("#storesignupform").valid() ==  true){

                $("#cover-spin").css('display',"block");
                intv = setTimeout(function () {
                var formdata = $("#storesignupform").serialize();


                    $.ajax({
                    url: '{{ route('post:provider-web:register') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formdata,
                    // data: form,
                    // ache: false,
                    // contentType: false,
                    // processData: false,
                    success: function (data) {
                        // console.log(data);
                        if (data.success == true) {
                            $('#storesignupform').trigger("reset");

                            $('.bootstrap-growl').remove();
                            $.bootstrapGrowl(data.message, // Messages
                                { // options
                                    type: "success", // info, success, warning and danger
                                    ele: "body", // parent container
                                    offset: {
                                        from: "top",
                                        amount: 20
                                    },
                                    align: "right", // right, left or center
                                    width: 300,
                                    delay: 5000,
                                    allow_dismiss: true, // add a close button to the message
                                    stackup_spacing: 10
                                });
                            window.location.href = data.redirect;
                        }
                        else {
                            //$('#signupform .error').text(data.error);
                            window.scrollTo({top: 0, behavior: 'smooth'});
                            $("#cover-spin").css('display',"none");
                            $('.bootstrap-growl').remove();
                            $.bootstrapGrowl(data.error, // Messages
                                { // options
                                    type: "danger", // info, success, warning and danger
                                    ele: "body", // parent container
                                    offset: {
                                        from: "top",
                                        amount: 20
                                    },
                                    align: "right", // right, left or center
                                    width: 300,
                                    delay: 5000,
                                    allow_dismiss: true, // add a close button to the message
                                    stackup_spacing: 10
                                });
                        }
                    }
                });
                }, 1000);
            }
            else {
                return false;
            }
        });
    });
</script>
<script>
    {{--$(document).ready(function () {--}}
    {{--    /*var route = "{!! url()->current()  !!}";--}}
    {{--    $('.nav-menu li a').each(function(){--}}
    {{--        var href = $(this).attr('href');--}}
    {{--        if(href == route)--}}
    {{--        {--}}
    {{--            $(this).attr('style','font-weight: 600 !important;\n' +--}}
    {{--                'text-transform: uppercase !important;\n' +--}}
    {{--                'color: #8702ae !important;');--}}
    {{--        }--}}
    {{--    });*/--}}
    {{--});--}}
</script>
