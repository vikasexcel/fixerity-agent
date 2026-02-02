@extends('admin.layout.auth')
@section('title')
    Provider Admin Login
@endsection
@section('page-css')
    <style>
        .btn-success {
            background-color: #F5AA00;
            border-color: #BC8200;
        }

        .btn-success:hover, .btn-success:active, .btn-success:focus {
            background-color: #F5AA00;
            border-color: #BC8200;
        }
        #contact_number {
            width: 85%;
            height: 38px;
            padding: 15px;
        }

        .form-material #contact_number:focus {
            /*border-color: transparent;*/
            border-bottom: 1px solid #4099ff;
            outline: none;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            -webkit-box-shadow: none;
            box-shadow: none;
        }
        .iti__flag-container {
            z-index: 9;
        }
        .error {
            color: red;
        }
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .text-divider {
            --text-divider-gap: 1rem;
            display: flex;
            align-items: center;
            font-size: 0.9375rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .text-divider::before, .text-divider::after {
            content: '';
            height: 1px;
            background-color: silver;
            flex-grow: 1;
        }

        .text-divider::before {
            margin-right: var(--text-divider-gap);
        }

        .text-divider::after {
            margin-left: var(--text-divider-gap);
        }
    </style>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/country-code/intlTelInput.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/country-code/demo.css?v=0.1')}}">
    <style>
        #phone:focus {
            border-bottom: 2px solid #00aff0
        }
    </style>
@endsection
@section('page-content')
    {{--<input type="tel" name="email" id="phone">--}}

    <section class="login-block">
        <!-- Container-fluid starts -->
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <!-- Authentication card start -->
                    <form class="md-float-material form-material main" method="post"
                          action="{{ route('post:provider-admin:login') }}">
                        {{ csrf_field() }}
                        <div class="text-center webLogo">
                            @php $map_key = \App\Models\GeneralSettings::first() @endphp
                            @if(isset($map_key) && $map_key->website_logo != Null)
                                <img src="{{ asset('assets/images/website-logo-icon/'.$map_key->website_logo)}}"
                                     alt="{{$map_key->website_logo}}">
                            @endif
                        </div>
                        <div class="auth-box card">
                            <div class="card-block">
                                <div class="row m-b-20">
                                    <div class="col-md-12">
                                        <h3 class="text-center txt-primary">Provider Admin</h3>
                                        <h3 class="text-center txt-primary">Sign In</h3>
                                    </div>
                                </div>
                                {{--<div class="row m-b-20">--}}
                                {{--<div class="col-md-6">--}}
                                {{--<a href="{{ url('/store-admin/auth/facebook') }}" class="btn btn-facebook m-b-20 btn-block"><i class="icofont icofont-social-facebook"></i> Facebook</a>--}}
                                {{--<button class="btn btn-facebook m-b-20 btn-block"><i class="icofont icofont-social-facebook"></i>facebook</button>--}}
                                {{--</div>--}}
                                {{--<div class="col-md-6">--}}
                                {{--<a href="{{ url('/store-admin/auth/google') }}" class="btn btn-google-plus m-b-20 btn-block"><i class="icofont icofont-social-google"></i> Google</a>--}}
                                {{--<button class="btn btn-google-plus m-b-20 btn-block">--}}
                                {{--<i class="icofont icofont-social-google"></i>Google--}}
                                {{--</button>--}}
                                {{--</div>--}}
                                {{--</div>--}}
                                {{--<p class="text-muted text-center p-b-5">Sign in with your regular account</p>--}}
{{--                                <div class="form-group form-primary">--}}
{{--                                    <input type="text" name="email" id="phone"--}}
{{--                                    class="form-control {{ $errors->first()!= Null ? "fill" : 'fill' }}" required="" autofocus>--}}
{{--                                    <span class="form-bar"></span>--}}
{{--                                    <label class="float-label">Email</label>--}}
{{--                                    <span class="error">{{ $errors->first('email') }}</span>--}}
{{--                                </div>--}}
                                <div class="form-group form-primary">
                                    <div class="input-group">
                                        <input type="text" onkeypress="return isNumber()" name="contact_number" style="width: 100%;"
                                               class="form-control" required="" id="contact_number"
                                               maxlength="10" value="{{old('contact_number') }}"
                                               placeholder="Contact Number" >
                                    </div>
                                    <span id="phone_error" class="error">{{ $errors->first('contact_number') }}</span>
                                    <input type="hidden" id="country_code" name="country_code" value="" >
                                </div>
                                <div class="form-group form-primary">
                                    <input type="password" name="password"
                                           class="form-control {{ $errors->first()!= Null ? "fill" : '' }}" required="">
                                    <span class="form-bar"></span>
                                    <label class="float-label">Password</label>
                                    <span class="error">{{ $errors->first('password') }}</span>
                                </div>
                                {{--<div class="row m-t-25 text-left">--}}
                                {{--<div class="col-12">--}}
                                {{--<div class="checkbox-fade fade-in-primary">--}}
                                {{--<label>--}}
                                {{--<input type="checkbox" value="">--}}
                                {{--<span class="cr"><i--}}
                                {{--class="cr-icon icofont icofont-ui-check txt-primary"></i></span>--}}
                                {{--<span class="text-inverse">Remember me</span>--}}
                                {{--</label>--}}
                                {{--</div>--}}
                                {{--<div class="forgot-phone text-right float-right">--}}
                                {{--<a href="auth-reset-password.html" class="text-right f-w-600"> Forgot--}}
                                {{--Password?</a>--}}
                                {{--</div>--}}
                                {{--</div>--}}
                                {{--</div>--}}
                                <div class="row m-t-30">
                                    <div class="col-md-12">
                                        <button type="submit"
                                                class="btn btn-success btn-md btn-block waves-effect text-center m-b-20">
                                            LOGIN
                                        </button>

                                    </div>
                                </div>
                                @if($general_settings && ($general_settings->is_google_login == 1 || $general_settings->is_facebook_login == 1))
                                    <div class="text-divider pb-3">OR</div>
                                    <div class="row m-b-20">
                                        @if($general_settings->is_facebook_login  == 1)
                                            <div class="col-md-{{ ($general_settings->is_google_login == 1 && $general_settings->is_facebook_login == 1)?"6":"12" }}">
                                                <a href="{{ url('provider/auth/facebook') }}" class="btn btn-facebook m-b-20 paddingSeven btn-block buttonloader"><i class="icofont icofont-social-facebook"></i> Facebook</a>
                                            </div>
                                        @endif
                                        @if($general_settings->is_google_login  == 1)
                                            <div class="col-md-{{ ($general_settings->is_google_login == 1 && $general_settings->is_facebook_login == 1)?"6":"12" }}">
                                                <a href="{{ route("get:social_auth",['guards'=>'on_demand','provider'=>'google']) }}" class="btn btn-google-plus paddingSeven btn-block google_button_loader"><i class="icofont icofont-social-google"></i>Login With Google</a>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                <p class="text-inverse text-left">Don't have an account?
                                    <a href="{{ route('get:provider-admin:register') }}">
                                        <b style="color: #F5AA00">Register here </b></a>for free!</p>
                            </div>
                        </div>
                    </form>
                    <!-- end of form -->
                </div>
                <!-- Authentication card end -->
            </div>
            <!-- end of col-sm-12 -->
        </div>
        <!-- end of row -->
        </div>
        <!-- end of container-fluid -->
    </section>
@endsection
@section('page-js')
    <script type="text/javascript" src="{{ asset('assets/js/country-code/intlTelInput.min.js')}}"></script>
    <script>
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
            // preferredCountries: ['us'],
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
        var country_code = iti.getSelectedCountryData()['dialCode']
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
            var country_code = iti.getSelectedCountryData()['dialCode']
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
            // var n = contact_number.indexOf("0", 0);
            // // var n = contact_number.charAt(contact_number);
            // if (n == 0) {
            //     document.getElementById("phone_error").innerHTML = 'Invalid Contact Number';
            //     document.getElementById("contact_numbers").value = "";
            // } else {
            //     document.getElementById("contact_numbers").value = contact_number;
            //     document.getElementById("phone_error").innerHTML = '';
            //     console.log(contact_number);
            // }

            //check code for numeric value
            if (isNaN(contact_number)) {
                document.getElementById("phone_error").innerHTML = 'Invalid Contact Number';
                document.getElementById("contact_numbers").value = "";
            } else {
                document.getElementById("contact_numbers").value = contact_number;
                document.getElementById("phone_error").innerHTML = '';
            }
        });

        /* allow only number in phone */
        function isNumber(evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }
        $("#main").validate({
            rules: {
                contact_number : {
                    required : true,
                    digits: true
                },
                password : {
                    required : true
                }
            },
            submitHandler: function(form) {
                $('.buttonloader').attr("disabled", true);
                $('.buttonloader').html("<i class='fa fa-spinner fa-spin'></i>");
                form.submit();
            }
        });

        $(".google_button_loader").click(function () {
            $('.google_button_loader').attr("disabled", true);
            $('.google_button_loader').html("<i class='fa fa-spinner fa-spin'></i>");
        });
    </script>
@endsection

