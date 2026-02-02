@extends('admin.layout.super_admin')
@section('title')
    @if(isset($user_details)) Edit @else Add @endif Customer
@endsection
@section('page-css')
    <style>
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
            /*box-shadow: inset 0 0 0 1px #666565, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;*/
            box-shadow: 0 0 0 0 #91DEAC, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
        }

        input[type="radio"] + .label:hover {
            color: #44BB6E;
        }

        input[type="radio"] + .label:hover:before {
            animation-duration: .5s;
            animation-name: change-size;
            animation-iteration-count: infinite;
            animation-direction: alternate;
            box-shadow: inset 0 0 0 1px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
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
            box-shadow: inset 0 0 0 1px #44BB6E, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #44BB6E;

        }

        @keyframes change-size {
            from {
                box-shadow: 0 0 0 0 #44BB6E, inset 0 0 0 1px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
            to {
                box-shadow: 0 0 0 1px #44BB6E, inset 0 0 0 1px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
        }

        @keyframes select-radio {
            0% {
                box-shadow: 0 0 0 0 #91DEAC, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
            90% {
                box-shadow: 0 0 0 10px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #44BB6E, inset 0 0 0 2px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
            100% {
                box-shadow: 0 0 0 12px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #44BB6E, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
        }

        @media screen and (max-width: 576px) {
            input[type="radio"] + .label {
                margin-left: 48%;
                display: block;
            }
        }

        @if(isset($user_details))
        #upload-image-preview {
            background: url({{ asset('/assets/images/profile-images/customer/'.$user_details->avatar) }}) no-repeat;
            background-size: contain;
            background-position: center;
        }
        @endif
    </style>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/country-code/intlTelInput.css')}}">
    {{--<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/country-code/demo.css?v=0.1')}}">--}}
    <style>
        #phone:focus {
            border-bottom: 2px solid #4099ff
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Customer</h5>
                            <span>@if(isset($user_details)) Edit @else Add @endif Customer</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>@if(isset($user_details)) Edit @else Add @endif Customer</h5>
                                <a href="{{ route('get:admin:user_list') }}"
                                   class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                            </div>
                            <div class="card-block">
                                <form id="main" method="post" action="{{ route('post:admin:update_user') }}" enctype="multipart/form-data">
                                    {{csrf_field() }}

                                    @if(isset($user_details))
                                        <input type="hidden" name="id" value="{{$user_details->id}}">
                                    @endif
                                    <div class="row">
                                        <div class="form-group col-sm-7">
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Customer Full Name:<sup class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="first_name" required
                                                           id="first_name" placeholder="Customer Full Name"
                                                           value="{{ (isset($user_details)) ? $user_details->first_name : old('first_name') }}">
                                                    <span class="error">{{ $errors->first('first_name') }}</span>
                                                </div>
                                            </div>
<!--                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Customer Last Name:<sup class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="last_name" required
                                                           id="last_name" placeholder="Customer Last Name"
                                                           value="{{ (isset($user_details)) ? $user_details->last_name : old('last_name') }}">
                                                    <span class="error">{{ $errors->first('last_name') }}</span>
                                                </div>
                                            </div>-->
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Email:<sup class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="email" class="form-control" name="email" required
                                                           id="email" placeholder="Unique Email"
                                                           {{ ( (isset($user_details)) && $user_details->login_type != "email" ) ? "" : "" }}
                                                           value="{{ (isset($user_details)) ? App\Models\User::Email2Stars($user_details->email) : old('email') }}">
                                                    <span class="error">{{ $errors->first('email') }}</span>
                                                </div>
                                            </div>

                                            @if(!isset($user_details))
                                                <div class="form-group row">
                                                    <label class="col-sm-4 col-form-label">Password:<sup class="error">*</sup></label>
                                                    <div class="col-sm-8">
                                                        <input type="password" class="form-control" name="password"
                                                               required minlength="6" maxlength="16"
                                                               id="password" placeholder="Password"
                                                               value="{{ old('password') }}">
                                                        <span class="error">{{ $errors->first('password') }}</span>
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-4 col-form-label">Re-Type Password:<sup class="error">*</sup></label>
                                                    <div class="col-sm-8">
                                                        <input type="password" class="form-control"
                                                               name="re_type_password"
                                                               required
                                                               id="re_type_password" placeholder="Re-Type Password"
                                                               value="{{ old('re_type_password') }}">
                                                        <span class="error">{{ $errors->first('re_type_password') }}</span>
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Contact No.:<sup class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="contact_number" required
                                                           @if(isset($store_details)) readonly @endif
                                                           id="phone" placeholder="Unique Contact Number" value="{{ (isset($user_details)) ? $user_details->country_code.App\Models\User::ContactNumber2Stars($user_details->contact_number) : '' }}">
                                                    <input type="hidden" id="contact_numbers" name="contact_numbers" value="{{ (isset($user_details)) ? $user_details->country_code.$user_details->contact_number : '' }}">
                                                    <input type="hidden" id="country_code" name="country_code" value="{{ (isset($user_details)) ? $user_details->country_code : '+1' }}">
                                                    <span id="phone_error" class="error">{{ $errors->first('contact_number') }}</span><br>
                                                    <span class="error">{{ $errors->first('full_number') }}</span><br>
                                                    <span class="error">{{ $errors->first('contact_numbers') }}</span>
                                                </div>
                                            </div>
{{--                                            <div class="form-group row">--}}
{{--                                                <label class="col-sm-4 col-form-label">Gender:<sup--}}
{{--                                                            class="error">*</sup></label>--}}
{{--                                                <div class="col-sm-8">--}}
{{--                                                    <div class="form-radio">--}}
{{--                                                        <div class="radio radio-inline">--}}
{{--                                                            <label>--}}
{{--                                                                <input type="radio" value="1"--}}
{{--                                                                       name="gender" {{ (isset($user_details)) ? ($user_details->gender == 1)? "checked ": "" : ""}}>--}}
{{--                                                                <i class="helper"></i>Male--}}
{{--                                                            </label>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="radio radio-inline">--}}
{{--                                                            <label>--}}
{{--                                                                <input type="radio" value="2"--}}
{{--                                                                       name="gender" {{ (isset($user_details)) ? ($user_details->gender == 2)? "checked ": "" : ""}}>--}}
{{--                                                                <i class="helper"></i>Female--}}
{{--                                                            </label>--}}
{{--                                                        </div>--}}
{{--                                                    </div>--}}
{{--                                                    <span class="error">{{ $errors->first('gender') }}</span>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
                                        </div>
                                        <div class="form-group col-sm-5">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label image">Profile Image:</label>
                                                <div class="col-sm-12">
                                                    <div id="upload-image-preview">
                                                        @if(isset($user_details))
                                                            <label for="image-upload" id="image-label">Change Image</label>
                                                            <input type="file" id="image-upload" name="avatar"/>
                                                        @else
                                                            <label for="image-upload" id="image-label">Upload Image</label>
                                                            <input type="file" id="image-upload" name="avatar"/>
                                                        @endif
                                                    </div>
                                                    <span class="note">[Note: Upload only png and jpg file dimension between 250*250 to 500*500 & max size 100kb.]</span>
                                                    <span class="error">{{ $errors->first('icon') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-sm-2"></label>
                                        <div class="col-sm-10">
                                            <button type="submit" class="btn btn-primary m-b-0 button_loader">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('page-js')
    <script type="text/javascript" src="{{ asset('assets/js/upload_image.js')}}"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $.uploadPreview({
                input_field: "#image-upload",   // Default: .image-upload
                preview_box: "#upload-image-preview",  // Default: .image-preview
                label_field: "#image-label",    // Default: .image-label
                label_default: "Choose Image",   // Default: Choose File
                label_selected: "Change Image",  // Default: Change File
                no_label: false                 // Default: false
            });
        });
    </script>

    <script type="text/javascript" src="{{ asset('assets/js/country-code/intlTelInput.min.js')}}"></script>
    <script type="text/javascript">

        var input = document.querySelector("#phone");
        var iti  = window.intlTelInput(input, {
            // allowDropdown: false,
            // autoHideDialCode: false,
            // autoPlaceholder: "off",
            // dropdownContainer: document.body,
            // excludeCountries: ["us"],
            formatOnDisplay: false,
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
            preferredCountries: ['ph','us'],
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
        $(document).ready(function () {
            input.addEventListener("countrychange", function() {
                console.log(iti.getSelectedCountryData()['dialCode'])
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

            $("#phone").on('keyup', function (event) {
                var contact_number = $(this).val();
                // var n = contact_number.indexOf("0", 0);
                // var n = contact_number.charAt(contact_number);
                // if (n == 0) {
                //     document.getElementById("phone_error").innerHTML = 'Invalid Contact Number';
                //     document.getElementById("contact_numbers").value = "";
                // } else {
                //     document.getElementById("contact_numbers").value = contact_number;
                //     document.getElementById("phone_error").innerHTML = '';
                //     // console.log(contact_number);
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

            // Disable button after form submission
            $('#main').on('submit', function() {
                $('.button_loader').attr('disabled', true);  // Disable the button
            });
        });
    </script>
@endsection

