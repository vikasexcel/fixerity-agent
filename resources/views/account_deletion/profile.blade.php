@extends('account_deletion.layout.demo')
@section('title')
    Delete
    {{ isset($guard) ? ucfirst(str_replace("_", " ", $guard)) : "User" }} Details
@endsection
@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/css/plugin/mdtimepicker.min.css')}}" type="text/css">
    <style>
        .form-control:disabled, .form-control[readonly] {
            background-color: #ffffff;
        }

        #menu-wrap {
            z-index: 100 !important;
        }

        /*.toggle input[type="checkbox"] + .button-indecator:before {*/
        /*    font-size: 25px;*/
        /*}*/

        .discount {
            display: none;
        }

        .input-group-append .input-group-text {
            background-color: #2ed8b6;
        }

        /* store Type Select Radio Button Style*/
        input[type="radio"] {
            display: none;
        }

        input[type="radio"] + .label {
            position: relative;
            /*margin-left: 43%;*/
            /*display: block;*/
            padding-left: 25px;
            margin-right: 10px;
            cursor: pointer;
            /*line-height: 16px;*/
            color: black;
            font-size: 14px;
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
            color: black;
            cursor: pointer;
            border-radius: 50%;
            transition: all .3s ease;
        }

        input[type="radio"] + .label:before {
            /*box-shadow: inset 0 0 0 1px #666565, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #4099ff;*/
            box-shadow: 0 0 0 0 #CBDCFF, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #4099ff, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #4099ff;
        }

        input[type="radio"] + .label:hover {
            color: #4099ff;
        }

        input[type="radio"] + .label:hover:before {
            animation-duration: .5s;
            animation-name: change-size;
            animation-iteration-count: infinite;
            animation-direction: alternate;
            box-shadow: inset 0 0 0 1px #4099ff, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #4099ff;
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
            box-shadow: inset 0 0 0 1px #4099ff, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #4099ff;
        }

        @keyframes change-size {
            from {
                box-shadow: 0 0 0 0 #4099ff, inset 0 0 0 1px #4099ff, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #4099ff;
            }
            to {
                box-shadow: 0 0 0 1px #4099ff, inset 0 0 0 1px #4099ff, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #4099ff;
            }
        }

        @keyframes select-radio {
            0% {
                box-shadow: 0 0 0 0 #CBDCFF, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #4099ff, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #4099ff;
            }
            90% {
                box-shadow: 0 0 0 10px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #4099ff, inset 0 0 0 2px #FFFFFF, inset 0 0 0 16px #4099ff;
            }
            100% {
                box-shadow: 0 0 0 12px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #4099ff, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #4099ff;
            }
        }

        @media screen and (max-width: 576px) {
            input[type="radio"] + .label {
                margin-left: 48%;
                display: block;
            }

            .input-top-bottom-margin {
                margin: 15px 0;
            }
        }

        /*checkbox style*/
        .border-checkbox-section .border-checkbox-group .border-checkbox-label {
            height: 7px;
            /*padding-left: 20px;*/
            padding-left: 30px;
            margin-right: 7px;
        }

        .border-checkbox-section .border-checkbox-group {
            margin-right: 15px;
        }

        .border-checkbox-section .border-checkbox-group .checklbl {
            height: 0px;
        }

        /*image upload style*/
        .image {
            padding-top: 0;
        }

        #image-preview-1 {
            border: 1px solid #9e9e9e;
            width: 100%;
            height: 160px;
            position: relative;
            overflow: hidden;
            background-color: #ffffff;
            color: #ecf0f1;
            cursor: pointer;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        #image-preview-2 {
            border: 1px solid #9e9e9e;
            width: 100%;
            height: 200px;
            position: relative;
            overflow: hidden;
            background-color: #ffffff;
            color: #ecf0f1;
            cursor: pointer;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        #image-preview-1 input, #image-preview-2 input {
            line-height: 200px;
            font-size: 200px;
            position: absolute;
            opacity: 0;
            z-index: 10;
        }

        #image-preview-1 label, #image-preview-2 label {
            position: absolute;
            z-index: 5;
            opacity: 0.8;
            cursor: pointer !important;
            background-color: #000000d1;
            color: white;
            width: 200px;
            height: 50px;
            font-size: 20px;
            line-height: 50px;
            text-transform: uppercase;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            text-align: center;
        }

        .res-banner-label {
            margin-top: -20px;
            margin-bottom: 15px;
        }

        @if(isset($store_details) && $store_details->store_banner != Null)
            #image-preview-1 {
            background: url({{ asset('/assets/images/store-images/'.$store_details->store_banner) }}) no-repeat;
            background-size: cover;
            /*background-size: 350px 115px;*/
            background-position: center;
        }

        @endif
        @if(isset($store_details) && $store_details->profile_image != Null)
            #image-preview-2 {
            background: url({{ asset('/assets/images/profile-images/provider/'.$store_details->profile_image) }}) no-repeat;
            background-size: cover;
            /*background-size: 100px 100px;*/
            /*background-attachment: fixed;*/
            background-position: center;
        }

        @endif
        @if(isset($store_details) && $store_details->profile_image != Null && filter_var($store_details->profile_image, FILTER_VALIDATE_URL) == true)
            #image-preview-2 {
            background: url({{ $store_details->profile_image}}) no-repeat;
            background-size: cover;
            /*background-size: 100px 100px;*/
            /*background-attachment: fixed;*/
            background-position: center;
        }

        @endif

        .input-controls {
            margin-top: 10px;
            border: 1px solid transparent;
            border-radius: 2px 0 0 2px;
            box-sizing: border-box;
            -moz-box-sizing: border-box;
            height: 32px;
            outline: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        #searchInput {
            background-color: #fff;
            font-family: Roboto;
            font-size: 15px;
            font-weight: 300;
            margin-left: 12px;
            padding: 0 11px 0 13px;
            text-overflow: ellipsis;
            width: 50%;
        }

        #searchInput:focus {
            border-color: #4d90fe;
        }
    </style>
    {{--    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/country-code/demo.css?v=0.1')}}">--}}
    <style>
        #phone:focus {
            border-bottom: 2px solid #4099ff
        }
    </style>
@endsection
@section('page-content')

    <div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <div class="page-body">

                        <form id="main" method="post" action="{{ route('post:account:deletion:delete-account:logout') }}" enctype="multipart/form-data">
                            {{csrf_field() }}

                            <div class="row">
                                <div class="form-group col-sm-12">

                                    <div class="card">
                                        <div class="card-header mr-auto ml-auto" style="width: fit-content">
                                            <h5>{{isset($guard) ? ucfirst(str_replace("_", " ", $guard)) : "User"}} Details</h5>
                                        </div>
                                        <div class="card-block">
                                            <input type="hidden" name="id" value="{{ \Illuminate\Support\Facades\Auth::guard($guard)->id() }}">
                                            <input type="hidden" name="guard" value="{{$guard}}">

                                            <div class="row">
                                                <div class="form-group mr-auto ml-auto col-sm-6">

                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Name:
{{--                                                            <sup class="error">*</sup>--}}
                                                        </label>
                                                        <div class="col-sm-12">
                                                            <input type="text" class="form-control" name="store_name" id="store_name" readonly placeholder="Name"
                                                                   value="{{ (isset($store_details)) ? $store_details->store_name : ((isset($user_details) && $user_details->first_name) ? $user_details->first_name : old('store_name'))  }}">
                                                            <span class="error">{{ $errors->first('store_name') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Email:
{{--                                                            <sup class="error">*</sup>--}}
                                                        </label>
                                                        <div class="col-sm-12">
                                                            <input type="email" class="form-control" name="email" readonly
                                                                   id="email" placeholder="Unique Email"
                                                                   {{ ( (isset($user_details)) && $user_details->login_type != "email" ) ? "readonly" : "" }}
                                                                   value="{{ (isset($user_details)) ? App\Models\User::Email2Stars($user_details->email) : old('email') }}">
                                                            <span class="error">{{ $errors->first('email') }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Contact No.:
{{--                                                            <sup class="error">*</sup>--}}
                                                        </label>
                                                        <div class="col-sm-12">
                                                            <input type="text" class="form-control" name="contact_number"
                                                                   readonly
                                                                   id="phone" placeholder="Unique Contact Number" value="{{ (isset($user_details)) ? $user_details->country_code.App\Models\User::ContactNumber2Stars($user_details->contact_number) : '' }}">
                                                            <input type="hidden" id="contact_numbers" name="contact_numbers" value="{{ (isset($user_details)) ? $user_details->country_code.$user_details->contact_number : '' }}">
                                                            <input type="hidden" id="country_code" name="country_code" value="{{ (isset($user_details)) ? $user_details->country_code : '+222' }}">
                                                            <span id="phone_error" class="error">{{ $errors->first('contact_number') }}</span><br>
                                                            <span class="error">{{ $errors->first('full_number') }}</span><br>
                                                            <span class="error">{{ $errors->first('contact_numbers') }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Gender:
{{--                                                            <sup class="error">*</sup>--}}
                                                        </label>
                                                        <div class="col-sm-12">
                                                            <div class="form-radio">
                                                                <div class="radio radio-inline">
                                                                    <label>
                                                                        <input type="radio" value="1" readonly disabled
                                                                               name="gender" {{ (isset($user_details)) ? ($user_details->gender == 1)? "checked ": "" : ""}}>
                                                                        <i class="helper"></i>Male
                                                                    </label>
                                                                </div>
                                                                <div class="radio radio-inline">
                                                                    <label>
                                                                        <input type="radio" value="2" readonly disabled
                                                                               name="gender" {{ (isset($user_details)) ? ($user_details->gender == 2)? "checked ": "" : ""}}>
                                                                        <i class="helper"></i>Female
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <span class="error">{{ $errors->first('gender') }}</span>
                                                        </div>
                                                    </div>
                                                                                                      @if($guard == "on_demand")@endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <center><button type="submit" id="delete_submit" class="btn btn-primary m-b-0">Delete Account
                                                </button></center>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('page-js')
    <script>
        $(document).ready(function(){
            $("#delete_submit").click(function(e){
                e.preventDefault();
                swal({
                    title: "Are you sure you want to delete an account?",
                    // text: "your account will be permanently deleted!",
                    type: "warning",
                    showCancelButton: true,
                    // confirmButtonClass: "btn btn-danger",
                    confirmButtonText: "Yes, DELETE it!",
                    cancelButtonText: "No, cancel please!",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function(isConfirm) {
                    if (isConfirm) {
                        $('form').submit();
                    } else {
                        swal("Cancelled", "Account is safe :)", "error");
                    }
                });
            });
        });
    </script>
@endsection

