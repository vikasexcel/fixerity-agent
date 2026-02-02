<link rel="stylesheet" href="{{ asset('assets/css/country-code/intlTelInput.css')}}" type="text/css">
<style>
#contact_number,#signup-contact{
    border-radius: 5px !important;
    height: 40px !important;
}
</style>
<!--user login register start-->
<div id="user" class="modal">

    <div class="user">
        <span onclick="document.getElementById('user').style.display='none'" class="close" title="Close">&times;</span>
        <div class="col-md-6 col-md-offset-3 loginform">
            <ul class="resp-tabs-list">
                <li class="resp-tab-item" id="btn-login-reload-1"><span>Login</span></li>
                <li class="resp-tab-item" id="btn-signup-reload-1"><span>Register</span></li>
            </ul>
            <div class="resp-tabs-container">
                <div class="tab-1 resp-tab-content" id="btn-login-reload-2">
                    <div class="login-top">
                        <form action="{{ route('get:user_login') }}" method="post">
                            {{ csrf_field() }}

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="signup_contact" class="col-sm-12 col-form-label">Contact No : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="hidden" name="contact_numbers" class="contact" placeholder="Contact No" value="" autocomplete="off" required="" id="contact_numbers" maxlength="10" style="padding-left: 80px !important;" >
                                            <input type="text" name="contact" class="contact " placeholder="Contact No" value="" autocomplete="off" required="" id="contact_number" maxlength="10" style="padding-left: 80px !important;" >
                                            <span id="phone_error" class="error">{{ $errors->first('contact_number') }}</span>
                                            <input type="hidden" id="country_code" name="country_code" value="" >
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="store_signup_name" class="col-sm-12 col-form-label">Password : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="password" name="password" class="form-control password" placeholder="Enter Password" value="" required/>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="login-bottom">
                                <div class="clear"></div>
                            </div>
                            <input type="submit" value="Log In">
                        </form>
                        <div class="clearfix"></div>
                    </div>
                </div>
                <div class="tab-1 resp-tab-content" id="btn-signup-reload-2">
                    <div class="login-top sign-top">

                        <form method="post" id="signupform">

                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group row">
                                        <label for="store_signup_name" class="col-sm-12 col-form-label">User Name : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="text" id="signup_name" name="name" class="form-control password" placeholder="Enter User Name" value="" autocomplete="off" required/>
                                        </div>
                                    </div>
                                </div>
<!--                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="store_signup_name" class="col-sm-12 col-form-label">Last Name : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="text" name="last_name" id="signup_last_name"  class="form-control" placeholder="Enter Last Name" autocomplete="off" required/>
                                        </div>
                                    </div>
                                </div>-->

                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="signup_contact" class="col-sm-12 col-form-label">Contact No : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="hidden" name="signup_contact_numbers" class="contact" placeholder="Contact No" autocomplete="off" required="" id="signup_contact_numbers" maxlength="10" style="padding-left: 80px !important;" >
                                            <input type="hidden" id="signup_country_code" name="signup_country_code" >
                                            <input type="text" name="signup_contact" placeholder="Contact No" autocomplete="off" required="" id="signup-contact" maxlength="10" style="padding-left: 80px !important;" >
                                            <span id="signup_phone_error" class="error">{{ $errors->first('signup_contact_numbers') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="store_signup_name" class="col-sm-12 col-form-label">Email : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="email" name="email" id="signup_email"  class="form-control" placeholder="Enter Email" autocomplete="off" required/>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="store_signup_name" class="col-sm-12 col-form-label">Password : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="password" name="password" id="signup-password" class="form-control password" placeholder="Enter Password" value="" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group row">
                                        <label for="store_signup_name" class="col-sm-12 col-form-label">Re-Type Password : <span class="red-star">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="password" name="retype_password" class="form-control" id="signup_retype_password" placeholder="Retype Password" required=""/>
                                        </div>
                                    </div>
                                </div>

                            </div>


                            <input type="submit" value="Sign Up" id="btn-sign-up">
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

<script>
    //login
    var input = document.querySelector("#contact_number");
    var iti = window.intlTelInput(input, {
        // allowDropdown: false,
        // autoHideDialCode: false,
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
        hiddenInput: "full_number",
        initialCountry: "us",
        // localizedCountries: { 'de': 'Deutschland' },
        // nationalMode: false,
        // onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
        // placeholderNumberType: "MOBILE",
        // preferredCountries: ['cn', 'jp'],
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
    var country_code = iti.getSelectedCountryData()['dialCode'];
    if(country_code > 0){
        country_code = "+"+country_code;
        document.getElementById("phone_error").innerHTML = '';
    }else{
        country_code = "+1";
        document.getElementById("phone_error").innerHTML = 'Invalid Country Code';
    }
    $("#country_code").val(country_code);
    input.addEventListener("countrychange", function() {
        //console.log(iti.getSelectedCountryData()['dialCode']);
        var country_code = iti.getSelectedCountryData()['dialCode'];
        if(country_code > 0){
            country_code = "+"+country_code;
            document.getElementById("phone_error").innerHTML = '';
        }else{
            country_code = "+1";
            document.getElementById("phone_error").innerHTML = 'Invalid Country Code';
        }
        $("#country_code").val(country_code);
    });
    $("#contact_number").on('keyup', function (event) {
        var contact_number = $(this).val();
        var n = contact_number.indexOf("0", 0);
        // var n = contact_number.charAt(contact_number);
        if (n == 0) {
            document.getElementById("phone_error").innerHTML = 'Invalid Contact Number';
            document.getElementById("contact_numbers").value = "";
        } else {
            document.getElementById("contact_numbers").value = contact_number;
            document.getElementById("phone_error").innerHTML = '';
            console.log(contact_number);
        }
    });
</script>
<script>
    //register
    var sinput = document.querySelector("#signup-contact");
    var sniti = window.intlTelInput(sinput, {
        // allowDropdown: false,
        // autoHideDialCode: false,
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
        hiddenInput: "sfull_number",
        initialCountry: "us",
        // localizedCountries: { 'de': 'Deutschland' },
        // nationalMode: false,
        // onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
        // placeholderNumberType: "MOBILE",
        // preferredCountries: ['cn', 'jp'],
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
    var scountry_code = sniti.getSelectedCountryData()['dialCode'];
    if(scountry_code > 0){
        scountry_code = "+"+scountry_code;
        document.getElementById("signup_phone_error").innerHTML = '';
    }else{
        scountry_code = "+1";
        document.getElementById("signup_phone_error").innerHTML = 'Invalid Country Code';
    }
    $("#signup_country_code").val(scountry_code);
    sinput.addEventListener("countrychange", function() {
        //console.log(iti.getSelectedCountryData()['dialCode']);
        var scountry_code = sniti.getSelectedCountryData()['dialCode'];
        alert(scountry_code);
        if(scountry_code > 0){
            scountry_code = "+"+scountry_code;
            document.getElementById("signup_phone_error").innerHTML = '';
        }else{
            scountry_code = "+1";
            document.getElementById("signup_phone_error").innerHTML = 'Invalid Country Code';
        }
        $("#signup_country_code").val(scountry_code);
    });
    $("#signup-contact").on('keyup', function (event) {
        var scontact_number = $(this).val();
        var n = scontact_number.indexOf("0", 0);
        if (n == 0) {
            document.getElementById("signup_phone_error").innerHTML = 'Invalid Contact Number';
            document.getElementById("signup_contact_numbers").value = "";
        } else {
            document.getElementById("signup_contact_numbers").value = scontact_number;
            document.getElementById("signup_phone_error").innerHTML = '';
            console.log(scontact_number);
        }
    });
</script>
<script rel="stylesheet" src="{{ asset('assets/js/validation/jquery.validate.js')}}"></script>
<script>
    $(document).ready(function (){
        jQuery.validator.addMethod("emailfull", function(value, element) {
            return this.optional(element) || /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i.test(value);
        }, "Please enter valid email address!");

        $("#signupform").validate({
            rules: {
                name: {
                    required : true,
                },
                /*last_name: {
                    required : true,
                },*/
                signup_contact: {
                    required : true,
                    number : true,
                },
                email: {
                    required : true,
                    emailfull : true,
                },
                password: {
                    required : true,
                    minlength : 6,
                    maxlength : 16
                },
                retype_password: {
                    required : true,
                    equalTo : "#signup-password"
                },
            },
            messages: {
                name: {
                    required :"Please enter user name",
                },
                /*last_name: {
                    required :"Please enter last name",
                },*/
                signup_contact: {
                    required :"Please enter contact number",
                    number :"please enter valid number",
                },
                email: {
                    required :"Please enter email",
                    emailfull :"Please enter valid email address!",
                },
                password: {
                    required :"Please enter password",
                    minlength : "Please enter at least 6 character in password",
                    maxlength :"Please enter at most 16 character in password",
                },
                retype_password: {
                    required :"Please enter retype password",
                    equalTo : "Password and retype password not match"
                },
            },
            errorPlacement: function(error, element) {
                if(element.attr("name") == "signup_contact") {
                    error.insertAfter( element.parent("div .iti"));
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                // form.submit();
            }
        });

        $('#btn-sign-up').on('click', function () {
            if($("#signupform").valid() ==  true){
                var form = $("#signupform");

                var name = $('#signup_name').val();
                // var last_name = $('#signup_last_name').val();

                var contact = $('#signup_contact_numbers').val();
                var email = $('#signup_email').val();
                var password = $('#signup_retype_password').val();
                var signup_country_code = $('#signup_country_code').val();
                var city = $('#city').val();
                console.log(name);

                console.log(contact);
                console.log(email);
                console.log(password);
                console.log(signup_country_code);
                console.log(city);

                $.ajax({
                    url: '{{ route('get:user_signup') }}',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        'name':name,
                        'contact':contact,
                        'scountry_code':signup_country_code,
                        'city':city,
                        'email':email,
                        'password':password
                    },
                    // data: form,
                    // ache: false,
                    // contentType: false,
                    // processData: false,
                    success: function (data) {
                        console.log(data);
                        if (data.success == true) {

                            if(data.verify == 0){
                                url = '{{ route('get:otp-verify') }}';
                                window.location.href = url;
                            }

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
                        }
                        else {
                            $('.bootstrap-growl').remove();
                            //$('#signupform .error').text(data.error);
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
            }
            else {
                return false;
            }
        });
    });
</script>
