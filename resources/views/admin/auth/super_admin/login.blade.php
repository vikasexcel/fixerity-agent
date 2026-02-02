@extends('admin.layout.auth')
@section('title')
    Super Admin Login
@endsection
@section('page-css')
    <style>
        .login-block {
            margin: 4% auto 0;
            /*margin: 80px auto;*/
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
            font-family: Times;
            border-radius: 50%;
            transition: all .3s ease;
        }

        input[type="radio"] + .label:before {
            /*box-shadow: inset 0 0 0 1px #666565, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #F5AA00;*/
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
            height: 64px;
            cursor: pointer;
        }

        .roles-wrapper-box:hover .roles-label-box {
            color: white;
            cursor: pointer;
            background-color: #F5AA00;
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
        .btn-success {
            background-color: #F5AA00;
            border-color: #BC8200;
        }

        .btn-success:hover, .btn-success:active, .btn-success:focus {
            background-color: #F5AA00 !important;
            border-color: #BC8200 !important;
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
                    <form class="md-float-material form-material"
                          method="post" action="{{ route('post:admin:update_super_admin_login') }}">
                        {{ csrf_field() }}
                        <div class="text-center webLogo">
                            @php $map_key = \App\Models\GeneralSettings::first() @endphp
                            @if(isset($map_key) && $map_key->website_logo != Null)
                                <img src="{{ asset('assets/images/website-logo-icon/'.$map_key->website_logo)}}"
                                     alt="{{$map_key->website_logo}}">
                            @endif

                            {{--<h3 class="text-center txt-primary">Gojek</h3>--}}
                        </div>
                        <div class="auth-box card">
                            <div class="card-block">
                                <div class="row m-b-20">
                                    <div class="col-md-12">
                                        <h3 class="text-center txt-primary">Admin Login</h3>
                                    </div>
                                </div>
                                {{--<div class="row m-b-20">--}}
                                {{--<div class="col-md-6">--}}
                                {{--<button class="btn btn-facebook m-b-20 btn-block"><i--}}
                                {{--class="icofont icofont-social-facebook"></i>facebook--}}
                                {{--</button>--}}
                                {{--</div>--}}
                                {{--<div class="col-md-6">--}}
                                {{--<button class="btn btn-twitter m-b-20 btn-block"><i--}}
                                {{--class="icofont icofont-social-twitter"></i>twitter--}}
                                {{--</button>--}}
                                {{--</div>--}}
                                {{--</div>--}}
                                {{--<p class="text-muted text-center p-b-5">Sign in with your regular account</p>--}}
                                <div class="form-group form-primary fill-data">
                                    <input type="email" name="email" id="email"
                                           class="form-control {{ $errors->first()!= Null ? "fill" : 'fill' }}" required
                                           autofocus value="">
                                    <span class="form-bar"></span>
                                    <label class="float-label">Email</label>
                                    <span class="error">{{ $errors->first('email') }}</span>
                                </div>
                                <div class="form-group form-primary fill-data">
                                    <input type="password" name="password" id="password"
                                           class="form-control {{ $errors->first()!= Null ? "fill" : 'fill' }}" required
                                           autofocus value="">
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
                                <p class="text-inverse text-left"><b>Select Your Roles </b></p>

                                <div class="row">
                                    <div class="col-xl-auto col-xl-6 col-sm-6 col-md-6 roles-box">
                                        <div class="roles-wrapper-box">
                                            <input type="radio" id="featured-1" name="roles"
                                                   value="1" checked>
                                            <label class="label" for="featured-1"></label>
                                            <label class="roles-label-box" for="featured-1">Super/Sub <br> Admin</label>
                                        </div>
                                    </div>
{{--                                    <div class="col-xl-4 col-sm-4 col-md-4 roles-box">--}}
{{--                                        <div class="roles-wrapper-box">--}}
{{--                                            <input type="radio" id="featured-2" name="roles" value="2">--}}
{{--                                            <label class="label" for="featured-2"></label>--}}
{{--                                            <label class="roles-label-box" for="featured-2">Dispatcher <br> Admin</label>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
                                    <div class="col-xl-6 col-sm-6 col-md-6 roles-box roles-box-last-child">
                                        <div class="roles-wrapper-box">
                                            <input type="radio" id="featured-3" name="roles" value="3">
                                            <label class="label" for="featured-3"></label>
                                            <label class="roles-label-box" for="featured-3">Billing <br> Admin</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row m-t-10">
                                    <div class="auth-box col-xl-auto col-xl-5 col-sm-5 col-md-5 col-lg-5">
                                        <button type="submit"
                                                class="btn btn-success btn-md btn-block waves-effect text-center m-b-20">
                                            LOGIN
                                        </button>

                                    </div>
                                </div>
                                {{--<p class="text-inverse text-left">Don't have an account?<a--}}
                                {{--href="auth-sign-up-social.html">--}}
                                {{--<b>Register here </b></a>for free!</p>--}}
                            </div>
                        </div>
                    </form>
                    <!-- Authentication card end of form -->
                </div>
                <!-- end of col-sm-12 -->
            </div>
            <!-- end of row -->
        </div>
        <!-- end of container-fluid -->
    </section>
@endsection
@section('page-js')
    <script>
        $(document).on('click', '#featured-1', function (e) {
            var email = "#email";
            var password = "#password";
            $(email).val('');
            $(email).addClass('fill');
            $(password).val('');
            $(password).addClass('fill');
        });

        $(document).on('click', '#featured-2', function (e) {
            var email = "#email";
            var password = "#password";
            $(email).val('');
            $(email).addClass('fill');
            $(password).val('');
            $(password).addClass('fill');
        });


        $(document).on('click', '#featured-3', function (e) {
            var email = "#email";
            var password = "#password";
            $(email).val('');
            $(email).addClass('fill');
            $(password).val('');
            $(password).addClass('fill');
        });
    </script>
@endsection

