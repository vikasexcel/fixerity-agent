@extends('admin.layout.auth')
@section('title')
    Login
@endsection
@section('page-css')
    <style>
        .login-block {
            margin: 4% auto 0;
            min-height: 0;
        }
        /*admin role box radio button style*/
        input[type="radio"] {
            display: none;
        }
        input[type="radio"] + .label {
            position: relative;
            margin-left: 43%;
            display: block;
            padding-left: 20px;
            margin-right: 10px;
            cursor: pointer;
            line-height: 16px;
            transition: all .2s ease-in-out;
            margin-bottom: 10px;
        }
        input[type="radio"] + .label:before, input[type="radio"] > .label:after {
            content: '';
            position: absolute;
            top: -1px;
            left: 0;
            width: 20px;
            height: 20px;
            text-align: center;
            color: #f5f5f5;
            border-radius: 50%;
            transition: all .3s ease;
        }
        input[type="radio"] + .label:before {
            box-shadow: 0 0 0 0 #F5AA00, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #F5AA00, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #F5AA00;
        }
        input[type="radio"] + .label:hover {
            color: #F5AA00;
        }
        input[type="radio"] + .label:hover:before {
            animation-duration: .5s;
            animation-name: change-size;
            animation-iteration-count: infinite;
            animation-direction: alternate;
            box-shadow: inset 0 0 0 1px #F5AA00, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #F5AA00;
        }
        input[type="radio"]:checked + .label:hover {
            color: #333333;
            cursor: default;
        }
        input[type="radio"]:checked + .label:before {
            animation-duration: .2s;
            animation-name: select-radio;
            animation-iteration-count: 1;
            animation-direction: Normal;
            box-shadow: inset 0 0 0 1px #F5AA00, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #F5AA00;
        }
        @keyframes change-size {
            from {
                box-shadow: 0 0 0 0 #F5AA00, inset 0 0 0 1px #F5AA00, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #F5AA00;
            }
            to {
                box-shadow: 0 0 0 1px #F5AA00, inset 0 0 0 1px #F5AA00, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #F5AA00;
            }
        }
        @keyframes select-radio {
            0% {
                box-shadow: 0 0 0 0 #F5AA00, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #F5AA00, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #F5AA00;
            }
            90% {
                box-shadow: 0 0 0 10px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #F5AA00, inset 0 0 0 2px #FFFFFF, inset 0 0 0 16px #F5AA00;
            }
            100% {
                box-shadow: 0 0 0 12px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #F5AA00, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #F5AA00;
            }
        }

        /*admin roles box style*/
        .roles-box {
            margin-bottom: 15px;
        }
        .roles-box-last-child {
            margin-bottom: 0;
        }
        .roles-wrapper-box {
            align-content: center;
            width: auto;
            height: 52px;
            cursor: pointer;
        }
        .roles-wrapper-box:hover .roles-label-box {
            color: white;
            cursor: pointer;
            background-color:#F5AA00;
            animation-duration: .5s;
            animation-name: change-label-size;
            animation-iteration-count: infinite;
            animation-direction: alternate;
            box-shadow: 0 0 0 1px #F5AA00, 0 0 0 16px #FFFFFF, 0 0 0 16px #F5AA00;
        }
        .roles-label-box {
            display: block;
            text-align: center;
            margin-top: -10px;
            padding: 15px 5px 5px 5px;
            border-radius: 7px;
            height: 100%;
            border: 1px solid #F5AA00;
        }
        @keyframes change-label-size {
            from {
                box-shadow: 0 0 0 0 #F5AA00, inset 0 0 0 1px #F5AA00, inset 0 0 0 0 #FFFFFF, inset 0 0 0 0 #F5AA00;
            }
            to {
                box-shadow: 0 0 0 1px #F5AA00, inset 0 0 0 1px #F5AA00, inset 0 0 0 0 #FFFFFF, inset 0 0 0 0 #F5AA00;
            }
        }
        @media screen and (max-width: 576px) {
            input[type="radio"] + .label {
                margin-left: 48%;
                display: block;
            }
        }
        /*button style*/
         .btn-success:hover, .btn-success:active, .btn-success:focus {
            background-color: #62c18f !important;
            border-color: #62c18f !important;
        }
            background-color: #62c18f !important;
            border-color: #62c18f !important;
            filter: brightness(var(--hover-brightness));
        }
    </style>
    <style>
        .btn-success {
                  background-color: #F5AA00;
                  border-color: #62c18f;
              }
              .btn-success:hover, .btn-success:active, .btn-success:focus {
                  background-color: #F5AA00;
                  border-color: #F5AA00;
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
              /* Chrome, Safari, Edge, Opera */
              input::-webkit-outer-spin-button,
              input::-webkit-inner-spin-button {
                  -webkit-appearance: none;
                  margin: 0;
              }
              .error {
                  color: red;
              }
              /* Firefox */
              input[type=number] {
                  -moz-appearance: textfield;
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
    <section class="login-block">
        <!-- Container-fluid starts -->
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <!-- Authentication card start -->
                    <form class="md-float-material form-material" method="post"
                          action="{{ route('post:account:deletion:login') }}">
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
{{--                                        <h3 class="text-center txt-primary">Select User</h3>--}}
                                        <h3 class="text-center txt-primary">Sign In</h3>
                                    </div>
                                </div>
                                <div class="form-group form-primary">
                                    <div class="input-group">
                                        <input type="tel" name="contact_number" style="width: 100%;"
                                               class="form-control" required="" id="contact_number"
                                               value="{{old('contact_number') }}"
                                               placeholder="Contact Number" >
                                    </div>
                                    <span id="phone_error"></span>
                                    <span id="phone_error" class="error">{{ $errors->first('contact_number') }}</span>
                                    <input type="hidden" id="country_code" name="country_code" value="{{ old('country_code') }}" >
                                </div>
                                <div class="form-group form-primary fill-data">
                                    <input type="password" name="password" id="password"
                                           class="form-control {{ $errors->first()!= Null ? "fill" : 'fill' }}" required
                                           autofocus value="">
                                    <span class="form-bar"></span>
                                    <label class="float-label">Password</label>
                                    <span class="error">{{ $errors->first('password') }}</span>
                                </div>

                                <p class="text-inverse text-left"><b>Select Your Roles </b></p>

                                <div class="row">
                                    <div class="col-xl-auto col-xl-6 col-sm-6 col-md-6 roles-box">
                                        <div class="roles-wrapper-box">
                                            <input type="radio" id="featured-1" name="roles" onclick="userRole('user')"
                                                   value="0" checked>
                                            <label class="label" for="featured-1"></label>
                                            <label class="roles-label-box" for="featured-1">User</label>
                                        </div>
                                    </div>

{{--                                    <div class="col-xl-4 col-sm-4 col-md-4 roles-box">--}}
{{--                                        <div class="roles-wrapper-box">--}}
{{--                                            <input type="radio" id="featured-2" onclick="userRole('store')" name="roles" value="1">--}}
{{--                                            <label class="label" for="featured-2"></label>--}}
{{--                                            <label class="roles-label-box" for="featured-2">Store</label>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
                                    <div class="col-xl-6 col-sm-6 col-md-6 roles-box roles-box-last-child">
                                        <div class="roles-wrapper-box">
                                            <input type="radio" id="featured-3" onclick="userRole('on_demand')" name="roles" value="3">
                                            <label class="label" for="featured-3"></label>
                                            <label class="roles-label-box" for="featured-3">Provider</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row m-t-30">
                                    <div class="col-md-12">
                                        <button type="submit"
                                                class="btn btn-success btn-md btn-block waves-effect text-center m-b-20">
                                            LOGIN
                                        </button>
                                    </div>
                                </div>

                                <input type="hidden" name="user_roles" id="user-role" />
                                <p class="text-inverse text-center">or</p>
                                <div class="row m-b-20 social-login">

{{--                                    <div class="col-md-6">--}}
{{--                                        <a href="{{ "" }}" class="btn btn-facebook m-b-20 btn-block"><i class="icofont icofont-social-facebook"></i> Facebook</a>--}}
{{--                                    </div>--}}
                                    <div class="col-md-12">
                                        <a href="{{ "" }}" class="btn btn-google-plus m-b-20 btn-block"><i class="icofont icofont-social-google"></i> Google</a>
                                    </div>
                                </div>

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

        <!-- end of container-fluid -->
    </section>
@endsection
@section('page-js')
    <script type="text/javascript" src="{{ asset('assets/js/country-code/intlTelInput.min.js')}}"></script>
    <script>
        {{--var default_country_code = "{{ ((isset($general_settings)) && $general_settings->default_country_code != Null ) ? $general_settings->default_country_code : 'us'  }}";--}}

        var input = document.querySelector("#contact_number");
        var iti = window.intlTelInput(input, {
            hiddenInput: "full_number",
                initialCountry: "ca",
            separateDialCode: true,
            utilsScript: "{{ asset('assets/js/country-code/utils.js')}}",
        });
        var country_code = iti.getSelectedCountryData()['dialCode']
        if(country_code > 0){
            country_code = "+1";
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
                country_code = "+1";
                document.getElementById("phone_error").innerHTML = '';
            }else{
                country_code = "+1";
                document.getElementById("phone_error").innerHTML = 'Invalid Country Code';
            }
            $("#country_code").val(country_code);
        });
        $("#contact_number").on('keyup', function (event) {
            var contact_number = $(this).val();

            //check code for numeric value
            if (isNaN(contact_number)) {
                document.getElementById("phone_error").innerHTML = 'Invalid Contact Number';
                document.getElementById("contact_numbers").value = "";
            } else {
                document.getElementById("contact_numbers").value = contact_number;
                document.getElementById("phone_error").innerHTML = '';
            }
        });
    </script>

    <script>
        $(document).ready(function () {
            $('.btn-facebook').attr('href',"{{url('user/auth/facebook')}}")
            $('.btn-google-plus').attr('href',"{{url('user/auth/google')}}")
        });
        function userRole(role){
            if (role == "store") {
                $('.social-login').addClass('d-none')
            } else {
                $('.social-login').removeClass('d-none')
            }
            let urlFacebook = '{{url(':user/auth/facebook')}}'
            let urlGoogle = '{{url(':user/auth/google')}}'

            $('.btn-facebook').attr('href',urlFacebook.replace(':user',role))
            $('.btn-google-plus').attr('href',urlGoogle.replace(':user',role))

            $('#user-role').val(role)
        }
    </script>
@endsection

