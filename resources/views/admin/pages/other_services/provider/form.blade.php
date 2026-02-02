@extends('admin.layout.other_service')
@section('title')
    @if(isset($provider)) Edit @else Add @endif Profile
@endsection
@section('page-css')
    <style>
        .toggle input[type="checkbox"] + .button-indecator:before {
            font-size: 25px;
        }
        .discount {
            display: none;
        }
        /*Gender select radio button style*/
        .input-group-append .input-group-text {
            background-color: #2ed8b6;
        }
        input[type="radio"] {
            display: none;
        }
        input[type="radio"] + .label {
            position: relative;
            padding-left: 25px;
            margin-right: 10px;
            cursor: pointer;
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

        /*checkbox style*/
        .border-checkbox-section .border-checkbox-group .border-checkbox-label {
            height: 7px;
            padding-left: 20px;
            margin-right: 7px;
        }

        .border-checkbox-section .border-checkbox-group {
            margin-right: 15px;
        }

        /*upload image style*/
        #upload-image-preview {
            height: 120px;
            background: no-repeat;
            background-size: contain !important;
            background-position: center !important;
        }

        #upload-image-preview label {
            width: 150px;
            height: 40px;
            font-size: 16px;
            line-height: 40px;
            z-index: 0;
        }

        @if(isset($provider))
        #upload-image-preview {
            background: url({{ asset('/assets/images/profile-images/provider/'.$provider->avatar) }}) no-repeat;
            background-size: contain;
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

        #searchInput,#suggestions {
            background-color: #fff;
            font-family: Roboto;
            font-size: 15px;
            font-weight: 300;
            /*margin-left: 12px;*/
            padding: 0 11px 0 13px;
            text-overflow: ellipsis;
            width: 100%;
        }

        #searchInput:focus {
            border-color: #4d90fe;
        }

        .btn-success:hover {
            color: #fff;
            background-color: #157347;
            border-color: #146c43;
        }
        .img-fluid {
            max-width: 70% !important;
            height: auto;
        }
    </style>
    <style>
        /*Map styling on full screen view*/
        .mapzoom{
            z-index: 9999999 !important;
            top: 42px !important;   //your input height
            left: 0 !important;
        }
    </style>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/country-code/intlTelInput.css')}}">
{{--    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">--}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <style>
        #phone:focus {
            border-bottom: 2px solid #4099ff
        }
        .font_clr {
            background-color: white;
            /*color: #0b5ed7;*/
            color: #3cb97e;
            width: 190px;
        }
        .btn-check:active+.btn-success, .btn-check:checked+.btn-success, .btn-success.active, .btn-success:active, .show>.btn-success.dropdown-toggle {
            color: #fff;
            background-color: #3cb97e !important;
            border-color: #3cb97e !important;
        }

        .toggle input[type="checkbox"]:checked + .button-indecator:before , .nav-link ,.nav-link:focus, .nav-link:hover,.toggle input[type="checkbox"] + .button-indecator:before{
            color: #3cb97e;
        }
        .nav-pills .nav-link.active, .nav-pills .nav-link.active:focus, .nav-pills .nav-link.active:hover, .nav-pills .nav-link.active, .nav-pills .nav-link.active:focus, .nav-pills .nav-link.active:hover, .nav-pills .nav-link.active.active, .nav-pills .nav-link.active.active:focus, .nav-pills .nav-link.active.active:hover {
            color: #3cb97e;
            border-bottom: 1px solid #3cb97e;
        }
        .btn-check:active+.btn-success, .btn-check:checked+.btn-success, .btn-success.active, .btn-success:active, .show>.btn-success.dropdown-toggle{
            color: white !important;
            background-color: #3cb97e;
            border-color: #3cb97e;
        }
        .btn-check:focus+.btn-success {
            color: #3cb97e;
            background-color: white;
            /*border-color: #3cb97e;*/
        }
        .header-navbar{
            padding: 0;
        }
        .card{
            border: none;
        }
        .nav-fill .nav-item, .nav-fill>.nav-link {
            flex: 1 1 auto;
            text-align: center;
        }
        .btn-check{
            display: contents;
        }
    </style>
@endsection
@section('page-content')
    <div class="pcoded-content">
        @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
            <div class="external-horizontal-nav">
                @include('admin.include.other-service-horizontal-navbar')
            </div>
        @endif
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>@if(isset($service_category)) {{ ucwords(strtolower($service_category->name)) }} @else Service @endif Provider Profile</h5>
                            <span>@if(isset($provider)) Edit @else Add @endif @if(isset($service_category)) {{ ucwords(strtolower($service_category->name)) }} @else Service @endif Provider Profile</span>
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
                        <form id="main" method="post"
                              action="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('post:admin:update_other_service_provider',[$slug]) : route('post:provider-admin:edit-profile')}}"
                              enctype="multipart/form-data">
                            {{csrf_field() }}
                            @if(isset($provider))
                                <input type="hidden" name="id" value="{{$provider->id}}">
                            @endif
                            <div class="row">
                                <div class="form-group col-sm-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>@if(!isset($provider))Add @else Edit @endif
                                                @if(isset($service_category) && $service_category->name != Null) {{ ucwords(strtolower($service_category->name)) }} @endif Provider
                                            </h5>
                                        </div>
                                        <div class="card-block">
                                            <div class="row">
                                                <div class="form-group col-sm-6">
                                                    @if(isset($service_category))
                                                        <input type="hidden" name="service_cat_id" id="service_cat_id" value="{{$service_category->id}}">
                                                        <span class="error">{{ $errors->first('service_cat_id') }}</span>
                                                    @endif

                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Provider Full Name:<sup class="error">*</sup></label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" name="first_name" required id="first_name" placeholder="Provider Full Name" value="{{ (isset($provider)) ? $provider->first_name : old('first_name') }}">
                                                            <span class="error">{{ $errors->first('first_name') }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Email:<sup class="error">*</sup></label>
                                                        <div class="col-sm-8">
                                                            <input type="email" class="form-control" name="email" required {{ (isset($provider) && $provider->login_type != "email") ? "" : "" }} id="email" placeholder="Unique Email" value="{{ (isset($provider)) ? App\Models\User::Email2Stars($provider->email) : old('email') }}">
                                                            <span class="error">{{ $errors->first('email') }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Contact Number:<sup class="error">*</sup></label>
                                                        <div class="col-sm-8">

                                                            <input type="text" class="form-control phone"
                                                                   name="contact_number"
                                                                   required
                                                                   id="phone"
                                                                   placeholder="Unique Contact Number"
                                                                   value="{{ (isset($provider)) ? $provider->country_code.App\Models\User::ContactNumber2Stars($provider->contact_number) : '' }}">
                                                            <input type="hidden" id="contact_numbers"
                                                                   name="contact_numbers"
                                                                   value="{{ (isset($provider)) ? $provider->country_code.$provider->contact_number : '' }}">
                                                            <input type="hidden" id="country_code" name="country_code"
                                                                   value="{{ (isset($provider)) ? $provider->country_code : '+1' }}">
                                                            <span id="phone_error"
                                                                  class="error">{{ $errors->first('contact_number') }}</span><br>
{{--                                                            <span--}}
{{--                                                                class="error">{{ $errors->first('contact_numbers') }}</span>--}}
                                                        </div>
                                                    </div>

                                                    @if(!(isset($provider)))
                                                        <div class="form-group row">
                                                            <label class="col-sm-4 col-form-label">Password:<sup class="error">*</sup></label>
                                                            <div class="col-sm-8">
                                                                <input type="password" class="form-control" name="pass" id="pass" placeholder="Password" required minlength="6" maxlength="16" value="{{ old('pass') }}">
                                                                <span class="error">{{ $errors->first('pass') }}</span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label class="col-sm-4 col-form-label">Confirm Password:<sup class="error">*</sup></label>
                                                            <div class="col-sm-8">
                                                                <input type="password" class="form-control" name="confirm_password" required id="confirm_password" placeholder="Confirm Password" value="{{ old('confirm_password') }}">
                                                                <span class="error">{{ $errors->first('confirm_password') }}</span>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Gender:<sup class="error">*</sup></label>
                                                        <div class="col-sm-8">
                                                            <div class="radio radio-outline radio-inline">
                                                                <input type="radio" id="featured-male" name="gender" {{ ( (isset($provider)) && $provider->gender == 1 )? "checked" : ""}} value="1">
                                                                <label class="label" for="featured-male"> Male</label>
                                                            </div>
                                                            <div class="radio radio-outline radio-inline">
                                                                <input type="radio" id="featured-female" name="gender" {{ ( (isset($provider)) && $provider->gender == 2 )? "checked" : ""}} value="2">
                                                                <label class="label" for="featured-female"> Female</label>
                                                            </div>
                                                            <span class="error">{{ $errors->first('gender') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label image image-label">Profile Image:</label>
                                                        <div class="col-sm-8">
                                                            <div id="upload-image-preview">
                                                                @if(isset($provider))
                                                                    <label for="image-upload" id="image-label">Change Image</label>
                                                                    <input type="file" id="image-upload" name="avatar" accept=".jpg,.jpeg,.png"/>
                                                                @else
                                                                    <label for="image-upload" id="image-label">Upload Image</label>
                                                                    <input type="file" id="image-upload" name="avatar" accept=".jpg,.jpeg,.png"/>
                                                                @endif
                                                            </div>
                                                            {{--<div class=" col-sm-12">--}}
                                                            <span class="note">[Note: Upload only png, jpeg and jpg file.]</span>
                                                            <span class="error">{{ $errors->first('avatar') }}</span>
                                                            {{--</div>--}}
                                                        </div>
                                                    </div>

                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Provider Service Radius:<sup class="error">*</sup></label>
                                                        <div class="col-sm-8">
                                                            <select type="text" class="form-control" name="service_radius" required id="service_radius">
                                                                <option disabled value="" selected>Select Service Radius</option>
                                                                <option value="1" @if(isset($provider) && $provider->service_radius == 1) selected @endif>1 KM</option>
                                                                <option value="5" @if(isset($provider) && $provider->service_radius == 5) selected @endif>5 KM</option>
                                                                <option value="10" @if(isset($provider) && $provider->service_radius == 10) selected @endif>10 KM</option>
                                                                <option value="15" @if(isset($provider) && $provider->service_radius == 15) selected @endif>15 KM</option>
                                                                <option value="20" @if(isset($provider) && $provider->service_radius == 20) selected @endif>20 KM</option>
                                                                <option value="25" @if(isset($provider) && $provider->service_radius == 25) selected @endif>25 KM</option>
                                                                <option value="30" @if(isset($provider) && $provider->service_radius == 30) selected @endif>30 KM</option>
                                                                <option value="40" @if(isset($provider) && $provider->service_radius == 40) selected @endif>40 KM</option>
                                                            </select>
                                                            <span class="0error">{{ $errors->first('service_radius') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Landmark:<sup
                                                                class="error">*</sup></label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" name="landmark" required
                                                                   id="landmark" placeholder="Landmark"
                                                                   value="{{ (isset($provider_other_details)) ? $provider_other_details->landmark : old('landmark') }}">
                                                            <span class="error">{{ $errors->first('landmark') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Minimum Order:</label>
                                                        <div class="col-sm-8">
                                                            <input type="number" class="form-control" min="0" step="0.01" name="min_order" id="min_order" placeholder="Minimum Order" value="{{ (isset($provider_other_details)) ? $provider_other_details->min_order : old('min_order') }}">
                                                            <span class="error">{{ $errors->first('min_order') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Address on Map</h5>
                                        </div>
                                        <div class="card-block">
                                            <div class="row">
                                                <div class="form-group col-sm-12">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Address:</label>
                                                        <div class="col-sm-12">
                                                            <input id="getaddress" name="address" class="form-control" value="{{ (isset($provider_other_details)) ? $provider_other_details->address : old('address')}}" type="text" placeholder="get Address from map" style="opacity: 1;" required hidden>
                                                            <input type="hidden" value="{{ (isset($provider_other_details)) ? $provider_other_details->lat : old('lat')}}" name="lat" id="lat">
                                                            <input type="hidden" value="{{ (isset($provider_other_details)) ? $provider_other_details->long : old('long')}}" name="long" id="lng">
                                                            <input id="searchInput" name="address" class="input-controls"
                                                                   value="{{ isset($provider_other_details) ? $provider_other_details->address : old('address') }}"
                                                                   type="text" placeholder="Enter a location" autocomplete="off">
                                                            <span class="error address-error">{{ $errors->first('address') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <div class="col-sm-12">
                                                            <!-- Suggestions dropdown -->
                                                            <div id="suggestions"></div>
                                                            <div class="map" id="map" style="width: 100%; height: 300px;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Service Time  @if(isset($provider)) {{isset($provider->time_zone)?App\Models\User::timezonedetails($provider->time_zone):""}}@endif </h5>
                                            <div style="display: block; float: right;">
                                                <span class="toggle">
                                                    <label>
                                                        <input name="open_time_status" value="1" class="form-control store_status block" type="checkbox" {{ isset($time_status) ? ( $time_status == 1 ? "checked" : "" ) : "checked"}} >
                                                        <span class="button-indecator" title="Active"></span>
                                                    </label>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="card-block">
                                            <div class="row">
                                                <div class="form-group col-sm-12" style="margin-bottom: -15px;">
                                                    <div class="form-group row">
                                                        <label class="col-sm-2 col-form-label">Service Time:<sup class="error">*</sup></label>
                                                        <div class="col-sm-10">
                                                            <div class="col-sm-12">
                                                                @include('admin.pages.other_services.provider.date_time')
                                                                <span class="error">{{ $errors->first('open_timing') }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>@if(!isset($bank_details))Add @else Edit @endif Bank Details</h5>
                                        </div>
                                        <div class="card-block">
                                            <div class="row">
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Bank Name:</label>
                                                        <div class="col-sm-12">
                                                            <input type="text" class="form-control" name="bank_name" id="bank_name" placeholder="Bank Name" value="{{ (isset($bank_details)) ? $bank_details->bank_name : old('bank_name') }}">
                                                            <span class="error">{{ $errors->first('bank_name') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Account Number:</label>
                                                        <div class="col-sm-12">
                                                            <input type="number" class="form-control" name="account_number" id="account_number" placeholder="Account Number" value="{{ (isset($bank_details)) ? $bank_details->account_number : old('account_number') }}">
                                                            <span class="error">{{ $errors->first('account_number') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Payment Email Address:</label>
                                                        <div class="col-sm-12">
                                                            <input type="email" class="form-control" name="payment_email" id="payment_email" placeholder="Email Address for Payment Notification" value="{{ (isset($bank_details)) ? $bank_details->payment_email : old('payment_email') }}">
                                                            <span class="error">{{ $errors->first('payment_email') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Bank Location(City Name):</label>
                                                        <div class="col-sm-12">
                                                            <input type="text" class="form-control" name="bank_location" id="bank_location" placeholder="Bank Location(City Name)" value="{{ (isset($bank_details)) ? $bank_details->bank_location : old('bank_location') }}">
                                                            <span class="error">{{ $errors->first('bank_location') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Account Holder Name:</label>
                                                        <div class="col-sm-12">
                                                            <input type="text" class="form-control" name="holder_name" id="holder_name" placeholder="Account Holder Name" value="{{ (isset($bank_details)) ? $bank_details->holder_name : old('holder_name') }}">
                                                            <span class="error">{{ $errors->first('holder_name') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">BIC Code:</label>
                                                        <div class="col-sm-12">
                                                            <input type="text" class="form-control" name="bic_swift_code" id="bic_swift_code" placeholder="BIC Code" value="{{ (isset($bank_details)) ? $bank_details->bic_swift_code : old('bic_swift_code') }}">
                                                            <span class="error">{{ $errors->first('bic_swift_code') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <center>
                                                <button type="submit" class="btn btn-primary m-b-0 buttonloader">Save</button>
                                            </center>
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

    <script type="text/javascript" src="{{ asset('assets/js/upload_image.js')}}"></script>


    <script type="text/javascript">
        $(document).ready(function () {
            @if(!isset($all_day) || isset($all_day) && $all_day == 1)
            $("#every_d").show();
            $("#single_day").hide();
            @else
            $("#single_day").show();
            @endif
        });
        $("#checkbox_every").on("click",function (){
            if ($(this).is(":checked")) {
                $("#every_d").show();
                $("#single_day").hide();
            }else{
                $("#single_day").show();
            }
        });
        $(".nav-link").on("click",function (){
            var activeDay = $(this).data("id");
            $("#activeday").val(activeDay);
        });
    </script>
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

            $('.open_time').on('click', function () {
                var get_id = $(this).attr('id');
                var isChecked = $(this).prop("checked");
                console.log(isChecked);
                if (isChecked == true) {
                    $('.' + get_id).css({
                        // 'backgroundColor': '#0b5ed7',
                        'backgroundColor': '#3cb97e',
                        'color': 'white',
                    });
                } else {
                    $('.' + get_id).css({
                        'backgroundColor': 'white',
                        // 'color': '#0b5ed7',
                        'color': '#3cb97e',
                    });
                }
            });
        });
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $general_settings->map_key }}&v=weekly&callback=initialize" async defer></script>
    {{-- varable for the map --}}
    <script>
        var lati = parseFloat('{{ isset($provider_other_details) && $provider_other_details->lat != null ? $provider_other_details->lat : ($general_settings->map_lat ?? "0") }}');
        var longi = parseFloat('{{ isset($provider_other_details) && $provider_other_details->long != null ? $provider_other_details->long : ($general_settings->map_long ?? "0") }}');
        // Set address dynamically in the input field using JavaScript
        var address = @json(isset($provider_other_details) ? $provider_other_details->address : old('address'));
        var apiKey = '{{ $general_settings->map_key }}';
    </script>
    {{-- JS for the map --}}
    <script type="text/javascript" src="{{ asset('assets/js/google-map-autocomplete.js')}}"></script>
    <script>
        $("input").not("#submit").keydown(function (event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
    </script>
    <script type="text/javascript" src="{{ asset('assets/js/country-code/intlTelInput.min.js')}}"></script>
    <script>

        var input = document.querySelector("#phone");
        var iti = window.intlTelInput(input, {
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
            preferredCountries: ['ph'],
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
            input.addEventListener("countrychange", function () {
                var country_code = iti.getSelectedCountryData()['dialCode'];
                if (country_code > 0) {
                    country_code = "+" + country_code;
                    document.getElementById("phone_error").innerHTML = '';
                } else {
                    country_code = "+1";
                    document.getElementById("phone_error").innerHTML = 'Invalid Country Code';
                }
                $("#country_code").val(country_code);
            });

            $("#phone").on('keyup', function (event) {
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
        });
        jQuery.validator.addMethod("validateEmail", function(value, element) {
                var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                if(value != ""){
                    return regex.test(value);
                }
                return true;
            }, "Please enter a valid email address."
        );

        $("#main").validate({
            rules: {
                first_name: {
                    required : true,
                },
                email: {
                    required : true,
                    email : true,
                },
                contact_number: {
                    required : true,
                    number : true
                },
                landmark: {
                    required : true,
                },
                min_order: {
                    required : false,
                    number:true
                },
                payment_email : {
                    email : true,
                    validateEmail: true
                }
            },
            errorPlacement: function(error, element) {
                if (element.attr("name") == "contact_number") {
                    error.insertAfter(".iti");
                }else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                $(".address-error").text('');
                if($("#getaddress").val() === "" || $("#getaddress").val() === null){
                      $(".address-error").text('Please select a appropriate location.');
                      return false;
                }
                $('.buttonloader').attr("disabled", true);
                $('.buttonloader').html("<i class='fa fa-spinner fa-spin'></i>");
                form.submit();
            }
        });
    </script>
    <script>
        $(document).ready(function () {
            document.onfullscreenchange = function ( event ) {
                $('.pac-container').addClass('mapzoom');
                let target = event.target;
                let pacContainerElements = document.getElementsByClassName("pac-container");
                if (pacContainerElements.length > 0) {
                    let pacContainer = document.getElementsByClassName("pac-container")[0];
                    if (pacContainer.parentElement === target) {
                        console.log("Exiting FULL SCREEN - moving pacContainer to body");
                        document.getElementsByTagName("body")[0].appendChild(pacContainer);
                    } else {
                        console.log("Entering FULL SCREEN - moving pacContainer to target element");
                        target.appendChild(pacContainer);
                    }
                } else {
                    console.log("FULL SCREEN change - no pacContainer found");

                }};

            if (document.addEventListener) {
                document.addEventListener('webkitfullscreenchange', exitHandler, false);
                document.addEventListener('mozfullscreenchange', exitHandler, false);
                document.addEventListener('fullscreenchange', exitHandler, false);
                document.addEventListener('MSFullscreenChange', exitHandler, false);
            }
        });

        function exitHandler() {
            if (!document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement) {
                $('.pac-container').removeClass('mapzoom');
            }
        }
    </script>
@endsection
