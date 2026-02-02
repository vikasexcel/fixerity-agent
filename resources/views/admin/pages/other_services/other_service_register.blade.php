@extends('admin.layout.other_service')
@section('title')
    Add Provider Services
@endsection
@section('page-css')
    <link href="{{ asset('/assets/css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet" media="screen">

    <link rel="stylesheet" href="{{ asset('assets/css/jquery.steps.css?v=0.6')}}" type="text/css">
    <link rel="stylesheet" href="{{ asset('/assets/css/widget/widget.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/file-upload/jquery.filer-dragdropbox-theme.css') }}">

    <style>
        .input-group-append .input-group-text {
            background-color: #2ed8b6;
        }

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
            color: #2184be;
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

        .hide {
            display: none;
        }

        .show {
            display: flex;
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
                box-shadow: 0 0 0 0 #44BB6E, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
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

        /*button style*/
        /*.btn-success {*/
        /*    background-color: #5dc271;*/
        /*    border-color: #4db560;*/
        /*}*/

        /*.btn-success:hover, .btn-success:active, .btn-success:focus {*/
        /*    background-color: #44af5a;*/
        /*    border-color: #44af5a;*/
        /*}*/

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


        /*Modal style */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 50%;
            /*height: 100%; !* Full height *!*/
            height: fit-content;
            margin: auto;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
        }

        /* Modal Content */
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 10px;
            /*padding: 20px;*/
            border: 1px solid #888;
            /*width: 80%;*/
        }

        .model_overlay {
            position: fixed;
            /*display: none;*/
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /*provider service checkbox style*/
        .border-checkbox-section .border-checkbox-group .border-checkbox-label {
            padding-left: 20px;
            margin-right: 7px;
        }

        .border-checkbox-section .border-checkbox-group {
            margin-right: 15px;
        }

        .provider_check_icon {
            color: red;
            margin-right: 7px;
        }

        .md_close {
            border: 1px solid black;
        }

    </style>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/country-code/intlTelInput.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/country-code/demo.css?v=0.1')}}">
    <style>
        #phone:focus {
            border-bottom: 2px solid #00aff0
        }

        .form_datetime {
            margin-bottom: 0;
        }

        #model_year {
            margin-bottom: 8px;
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
    <link rel="stylesheet" href="{{ asset('assets/css/plugin/mdtimepicker.min.css')}}" type="text/css">
{{--    <link rel="stylesheet" href="{{ asset('assets/css/jquery.steps.css?v=0.2')}}" type="text/css">--}}
    <link rel="stylesheet" href="{{ asset('/assets/css/widget/widget.css') }}">
    <!--    <link rel="stylesheet" href="{{ asset('/assets/css/file-upload/jquery.filer.css') }}">-->
    <link rel="stylesheet" href="{{ asset('/assets/css/file-upload/jquery.filer-dragdropbox-theme.css') }}">
    <style>
        #contact_number {
            width: 85%;
            height: 38px;
            padding: 15px;
        }
        .form-material #contact_number:focus {
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
        .wizard ul > li, .tabcontrol ul > li{
            padding: 5px !important;
        }
        .wizard > .content {
            background: #ffffff;
        }

        .pcoded[theme-layout="vertical"][vertical-placement="left"][vertical-nav-type="expanded"][vertical-effect="shrink"] .pcoded-content {
            margin-left: 0;
        }

        .form-control:disabled, .form-control[readonly] {
            background: transparent;
        }

        .disabled{
            box-shadow: 0 0 0 0 !important;
        }
        .wizard > .content > .body {
            position: relative;
            height: auto;
            width: 100%;
        }

        .wizard > .content {
            min-height: 230px;
        }

        .wizard > .content > .body input {
            border: 1px solid #ccc !important;
        }

        .image {
            padding-top: 0;
        }

        #image-preview-1 {
            border: 1px solid #9e9e9e;
            width: 100%;
            height: 220px;
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

        .wizard > .content > .body label.error {
            margin-left: 0px;
        }

        .radio, .radio label {
            cursor: pointer;
        }

        .wizard > .content > .body input[type="checkbox"] {
            display: none;
        }

        .border-checkbox-section {
            margin: 0;
        }

        .every_d_opn_time, .every_d_cls_time {
            margin-top: 5px;
        }

        .border-checkbox-section .border-checkbox-group .border-checkbox-label:before {
            top: 11px;
        }

        .border-checkbox-section .border-checkbox-group .border-checkbox-label:after {
            top: 21px;
        }

        .comp-card i {
            width: 35px !important;
            height: 35px !important;
            padding: 8px 0 !important;
        }

        .comp-card:hover i {
            border-radius: 5px !important;
        }

        .comp-card i:hover {
            border-radius: 50% !important;
        }

        .document_view, .document_status {
            cursor: pointer;
        }

        .doc_service_cat {
            display: none;
        }

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

        .doc_service_cat {
            display: none;
        }

        @if(isset($required_document))
        @foreach($required_document as $documents)
            @foreach($documents as $document)
        #document-preview-{{$document->id}}   {
            border: 1px solid #9e9e9e;
            width: 100%;
            height: 220px;
            position: relative;
            overflow: hidden;
            background-color: #ffffff;
            color: #ecf0f1;
            cursor: pointer;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        #document-preview-{{$document->id}} input, #document-preview-{{$document->id}} input {
            line-height: 200px;
            font-size: 200px;
            position: absolute;
            opacity: 0;
            z-index: 10;
        }

        #document-preview-{{$document->id}} label, #document-preview-{{$document->id}} label {
            position: absolute;
            z-index: 5;
            opacity: 0.8;
            cursor: pointer !important;
            background-color: #000000d1;
            color: white;
            width: 200px;
            height: 50px;
            font-size: 16px;
            line-height: 50px;
            text-transform: uppercase;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            text-align: center;
        }
        @endforeach
        @endforeach
        @endif
        /* Absolute Center Spinner */
        .loadingNew {
            /*display: none;*/
            position: absolute;
            z-index: 999;
            /*overflow: show;*/
            margin: auto;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            width: 50px;
            height: 50px;
        }

        /* Transparent Overlay */
        .loadingNew:before {
            content: '';
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255,255,255,0.5);
        }

        /* :not(:required) hides these rules from IE9 and below */
        .loadingNew:not(:required) {
            /* hide "loadingNew..." text */
            font: 0/0 a;
            color: transparent;
            text-shadow: none;
            background-color: transparent;
            border: 0;
        }
        .loadingNew:not(:required):after {
            content: '';
            display: block;
            font-size: 10px;
            width: 50px;
            height: 50px;
            margin-top: -0.5em;
            border: 15px solid rgba(33, 150, 243, 1.0);
            border-radius: 100%;
            border-bottom-color: transparent;
            -webkit-animation: spinner 1s linear 0s infinite;
            animation: spinner 1s linear 0s infinite;
        }

        /* Animation */
        @-webkit-keyframes spinner {
            0% {
                -webkit-transform: rotate(0deg);
                -moz-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                -moz-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
        @-moz-keyframes spinner {
            0% {
                -webkit-transform: rotate(0deg);
                -moz-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                -moz-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
        @-o-keyframes spinner {
            0% {
                -webkit-transform: rotate(0deg);
                -moz-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                -moz-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
        @keyframes spinner {
            0% {
                -webkit-transform: rotate(0deg);
                -moz-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                -moz-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
        a[href="#finish"] {
            height: 35px !important;
            width: 70px !important;
            text-align: center !important;
        }
    </style>

@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="fas fa-users bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Provider Registration Details</h5>
                            <span>New Provider Details</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <div class="page-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Required Provider Registration Details</h5>
                                        <span>Add class of <code>.form-control</code> with <code>&lt;input&gt;</code> tag</span>
                                    </div>
                                    <div class="card-block">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div id="wizard">
                                                    <section>
                                                        <form class="wizard-form" id="example-advanced-form" action="{{ route('post:provider-admin:service-register') }}" method="post" enctype="multipart/form-data">
                                                            {{csrf_field() }}
                                                            <input type="hidden" name="provider_id" id="provider_id" value="{{ Illuminate\Support\Facades\Auth::guard("on_demand")->user()->id }}">
                                                            <h3> Contact Person Details </h3>
                                                            <fieldset>
                                                                <div class="form-group row">
                                                                    <div class="col-md-3">
                                                                        <label for="user_name" class="block">Contact Person Name<span class="error">*</span></label>
                                                                    </div>
                                                                    <div class="col-md-9">
                                                                        <input id="user_name" name="user_name" type="text" required value="{{ Illuminate\Support\Facades\Auth::guard("on_demand")->user()->first_name }}" class="required form-control" aria-invalid="false">
                                                                        <span  class="error">{{ $errors->first('user_name') }}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group row">
                                                                    <div class="col-md-3">
                                                                        <label for="email" class="block">Email Address<span class="error">*</span></label>
                                                                    </div>
                                                                    <div class="col-md-9">
                                                                        <input id="email" name="email" type="email" @if(Illuminate\Support\Facades\Auth::guard("on_demand")->user()->email != Null) readonly @endif value="{{ Illuminate\Support\Facades\Auth::guard("on_demand")->user()->email }}" class="required form-control">
                                                                        <span  class="error">{{ $errors->first('email') }}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group row">
                                                                    <div class="col-md-3">
                                                                        <label for="contact_number" class="block">Contact Number<span class="error">*</span></label>
                                                                    </div>
                                                                    <div class="col-md-9">
                                                                        <div class="input-group">
                                                                            <input type="tel" class="form-control" name="contact_number" required
                                                                                   @if(Illuminate\Support\Facades\Auth::guard("on_demand")->user()->contact_number != Null) readonly @endif
                                                                                   id="contact_number" placeholder="Unique Contact Number"
                                                                                   value="{{ Illuminate\Support\Facades\Auth::guard("on_demand")->user()->country_code.Illuminate\Support\Facades\Auth::guard("on_demand")->user()->contact_number }}"
                                                                                   oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1').replace(/^0[^.]/, '0');">
                                                                            <input type="hidden" id="contact_numbers" name="contact_numbers" value="{{ (isset(Illuminate\Support\Facades\Auth::guard("on_demand")->user()->contact_number)) ?Illuminate\Support\Facades\Auth::guard("on_demand")->user()->contact_number : '' }}">
                                                                            <input type="hidden" id="country_code" name="country_code" value="{{ (isset(Illuminate\Support\Facades\Auth::guard("on_demand")->user()->country_code)) ? Illuminate\Support\Facades\Auth::guard("on_demand")->user()->country_code : '+1' }}">
                                                                            <span id="phone_error" class="error">{{ $errors->first('contact_number') }}</span>
                                                                            <span class="error">{{ $errors->first('contact_numbers') }}</span>
                                                                        </div>
                                                                        <span id="phone_error" class="error">{{ $errors->first('contact_number') }}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group row">
                                                                    <div class="col-md-3">
                                                                        <label for="gender" class="block">Gender <span class="error">*</span></label>
                                                                    </div>
                                                                    <div class="col-md-9">
                                                                        <div class="@if(isset($store_details)) col-sm-6 @else col-sm-8 @endif">
                                                                            <div class="form-radio">
                                                                                <div class="radio radio-inline">
                                                                                    <label>
                                                                                        <input type="radio" name="gender" class="form-control required" checked="checked" value="1" @if(Illuminate\Support\Facades\Auth::guard("on_demand")->user()->gender == 1) checked="checked"@endif>
                                                                                        <i class="helper"></i>Male
                                                                                    </label>
                                                                                </div>
                                                                                <div class="radio radio-inline">
                                                                                    <label>
                                                                                        <input type="radio" name="gender" value="2" class="form-control required" @if(Illuminate\Support\Facades\Auth::guard("on_demand")->user()->gender == 2) checked="checked" @endif >
                                                                                        <i class="helper"></i>Female
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </fieldset>
                                                            <h3> Select Service </h3>
                                                            <fieldset>
                                                                <div class="loadingNew"  style="display: none">Loading;</div>
                                                                <div class="form-group col-sm-12">
                                                                    <div class="row">
                                                                        <div class="form-group col-sm-6">
                                                                            <div class="form-group row">
                                                                                <label class="col-sm-4 col-form-label">Landmark:<sup class="error" style="color: red;">*</sup></label>
                                                                                <div class="col-sm-8">
                                                                                    <input type="text" class="form-control" name="landmark" required id="landmark" placeholder="Landmark" autocomplete="off" value="{{ (isset($driver_detials)) ? $driver_detials->landmark : old('landmark') }}">
                                                                                    <span class="error" style="color: red;">{{ $errors->first('landmark') }}</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group row">
                                                                                <label class="col-sm-4 col-form-label">Location:<sup class="error"style="color: red;">*</sup></label>
                                                                                <div class="col-sm-8">
                                                                                    <input id="getaddress" name="address" class="form-control" value="{{ old('address')}}" type="text" placeholder="get Address from map" style="opacity: 1;" required hidden>
                                                                                    <input id="searchInput" name="address" class="form-control" value="{{ old('address')}}" type="text" placeholder="get Address from map" style="opacity: 1;" required>
                                                                                    <!-- Suggestions dropdown -->
                                                                                    <div id="suggestions"></div>
                                                                                    <input type="hidden" value="{{ old('lat') }}" name="lat" id="lat">
                                                                                    <input type="hidden" value="{{ old('long') }}" name="long" id="lng">
                                                                                    <span class="address-error" style="color: red;">{{ $errors->first('address') }}</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group row"  >
                                                                                <label class="col-sm-4 col-form-label">Service Radius:<sup class="error" style="color: red;">*</sup></label>
                                                                                <div class="col-sm-8">
                                                                                    <select name="service_radius" id="service_radius" class="form-control required"  >
                                                                                        <option  selected value="">Select Service Radius</option>
                                                                                        {{--                                                                                        @for($i=1; $i<=8; $i++)--}}
                                                                                        {{--                                                                                            <option value="{{ $i*5 }}" {{ ($i == 1)?"selected":"" }}>{{ $i*5 ." km " }}</option>--}}
                                                                                        {{--                                                                                        @endfor--}}
                                                                                        <option value="1">1 KM</option>
                                                                                        <option value="5">5 KM</option>
                                                                                        <option value="10">10 KM</option>
                                                                                        <option value="15">15 KM</option>
                                                                                        <option value="20">20 KM</option>
                                                                                        <option value="25">25 KM</option>
                                                                                        <option value="30">30 KM</option>
                                                                                        <option value="40">40 KM</option>
                                                                                    </select>
                                                                                    <span class="error" style="color: red;">{{ $errors->first('service_radius') }}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group col-sm-6">
                                                                            <div class="form-group row">
{{--                                                                                <input id="searchInput" name="address" class="input-controls" value="{{ old('address') }}" required type="text" placeholder="Enter a location">--}}
                                                                                <div class="map" id="map" style="width: 100%; height: 300px;"></div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group col-sm-12">
                                                                    <div class="form-group row">
                                                                        <label class="col-sm-2 col-form-label">Select Service :<sup class="error" style="color: red;">*</sup></label>
                                                                        <div class="col-sm-10">
                                                                            <div class="row">
                                                                                @if(isset($service_category))
                                                                                    @foreach($service_category as $key=>$service)
                                                                                        @if(in_array($service->category_type,[3,4]))
                                                                                            <div class="col-md-3 col-sm-4">
                                                                                                <div class="form-radio">
                                                                                                    <div class="radio radio-inline ">
                                                                                                        <label>
                                                                                                            <div>
                                                                                                                <input type="radio" name="service_category" value="{{ $service->id }}" @if($key == 0) checked @endif class="form-control required service_category" service_category_type="{{ $service->category_type }}" service_category_id="{{ $service->id }}">
                                                                                                                <i class="helper"></i>{{ $service->name }}
                                                                                                            </div>
                                                                                                        </label>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        @endif
                                                                                    @endforeach
                                                                                @endif
                                                                            </div>
                                                                            <span class="error" style="color: red;">{{ $errors->first('service_cat_id') }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            </fieldset>
                                                            <h3 id="service_package">Service Package</h3>
                                                            <fieldset>
                                                                <div class="loadingNew">Loading;</div>
                                                                <div class="form-group col-sm-12">
                                                                    <div class="card">
                                                                        <div class="card-header">
                                                                            <h5 id="add_service_package">Add Service Package</h5>
                                                                        </div>
                                                                        <div class="card-block">
                                                                            <div class="row">
                                                                                <div class="form-group col-sm-6">
                                                                                    <div class="form-group row"  >
                                                                                        <input type="hidden" name="tab_service_cat_id" id="tab_service_cat_id" value="0">
                                                                                        <label class="col-sm-4 col-form-label">Service Category:<sup class="error" style="color: red;">*</sup></label>
                                                                                        <div class="col-sm-8">
                                                                                            <select name="service_sub_category" id="service_sub_category" class="form-control"  required>
                                                                                                <option disabled selected>Select Service Category</option>
                                                                                            </select>
                                                                                            <span class="error" style="color: red;">{{ $errors->first('service_sub_category') }}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="form-group row ">
                                                                                        <label class="col-sm-4 col-form-label">Package Name:<sup class="error" style="color: red;">*</sup></label>
                                                                                        <div class="col-sm-8">
                                                                                            <input type="text" class="form-control" name="package_name" required id="package_name" placeholder="Package Name" value="{{ (isset($driver_detials)) ? $driver_detials->package_name : old('package_name') }}">
                                                                                            <span class="error" style="color: red;">{{ $errors->first('package_name') }}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="form-group row">
                                                                                        <label class="col-sm-4 col-form-label">Package Price:<sup class="error" style="color: red;">*</sup></label>
                                                                                        <div class="col-sm-8">
                                                                                            <input type="number" step="0.01" class="form-control" name="package_price" required id="package_price" placeholder="Package Price" value="{{ (isset($driver_detials)) ? $driver_detials->package_price : old('package_price') }}">
                                                                                            <span class="error" style="color: red;">{{ $errors->first('package_price') }}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="form-group row hide_with_video">
                                                                                        <label class="col-sm-4 col-form-label">Max. Book Quantity:<sup class="error" style="color: red;">*</sup></label>
                                                                                        <div class="col-sm-8">
                                                                                            <select name="max_book_quantity" id="max_book_quantity" class="form-control" >
                                                                                                <option disabled selected>Select Max. Book Quantity</option>
                                                                                                @for($i=1; $i<=6; $i++)
                                                                                                    <option value="{{ $i}}" {{ ($i == 1)?"selected":"" }}>{{ $i }}</option>
                                                                                                @endfor
                                                                                            </select>
                                                                                            <span class="error" style="color: red;">{{ $errors->first('max_book_quantity') }}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="form-group col-sm-6">
                                                                                    <div class="form-group row">
                                                                                        <label class="col-sm-4 col-form-label">Package Description:<sup class="error" style="color: red;">*</sup></label>
                                                                                        <div class="col-sm-8">
                                                                                            <textarea name="description" id="description" rows="6" class="form-control fill" placeholder="Package Description" required="" spellcheck="false"></textarea>
                                                                                            <span class="error" style="color: red;">{{ $errors->first('description') }}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </fieldset>
                                                            <h3> Documents </h3>
                                                            <fieldset>
                                                                {{--<div >--}}
                                                                {{--    <div class="col-xl-3 col-md-6">--}}
                                                                {{--        <div class="card comp-card">--}}
                                                                {{--            <div class="card-body text-center">--}}
                                                                {{--                <h6 class="m-b-20">Pet Care Certificate</h6>--}}
                                                                {{--                <div id="document-preview-6">--}}
                                                                {{--                    <label for="document-upload-6" id="document-label-6">Upload--}}
                                                                {{--                        Document</label>--}}
                                                                {{--                    <input type="file" id="document-upload-6" data-id="6" name="documents[6]" class="form-control documents_11 unless_doc" required="">--}}
                                                                {{--                </div>--}}
                                                                {{--            </div>--}}
                                                                {{--        </div>--}}
                                                                {{--    </div>--}}
                                                                {{--</div>--}}
                                                                <div class="row documentLists"></div>
                                                            </fieldset>
                                                            <input type="hidden" name="is_doc_show" value="1" id="is_doc_show">
                                                            <input type="submit" id="form-submit" style="display: none">
                                                        </form>
                                                    </section>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('page-js')
    <script type="text/javascript" src="{{ asset('assets/js/jquery.steps.js?v=0.1')}}"></script>
    {{--    <script rel="stylesheet" src="{{ asset('assets/js/waves.min.js')}}"></script>--}}
{{--        <script type="text/javascript" src="{{ asset('assets/js/form-wizard.js?v=0.2')}}"></script>--}}
    <script type="text/javascript" src="{{ asset('assets/js/validation/jquery.validate.js')}}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/upload_image.js')}}"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            var form = $("#example-advanced-form").show();
            form.steps({
                headerTag: "h3",
                bodyTag: "fieldset",
                transitionEffect: "slideLeft",
                onStepChanging: function(event, currentIndex, newIndex) {


                    // Allways allow previous action even if the current form is not valid!
                    if (currentIndex > newIndex) {
                        return true;
                    }
                    // Needed in some cases if the user went back (clean up)
                    if (currentIndex < newIndex) {
                        // To remove error styles
                        form.find(".body:eq(" + newIndex + ") label.error").remove();
                        form.find(".body:eq(" + newIndex + ") .error").removeClass("error");
                    }
                    // form.validate().settings.ignore = ":disabled,:hidden";
                    // console.log(form.validate());
                    form.validate({
                        ignore: ":disabled,:hidden",
                        errorPlacement: function(error, element) {
                            if (element.attr("name") == "address"  ) {
                                error.insertAfter($("#address"));
                            } else if (element.attr("name") == "contact_number") {
                                error.insertAfter(".iti");
                            }else {
                                error.insertAfter(element);
                            }
                        },
                        rules: {
                            package_price: {
                                required : true,
                                number: true,
                            },
                        },
                        messages: {
                            package_price: {
                                number: "Please enter valid package price."
                            }
                        },
                    });

                    if (currentIndex === 1 && newIndex === 2) {
                        $(".address-error").text('');
                        const address = $("#getaddress").val();
                        if (!address || address.trim() === "") {
                            $(".address-error").text('Please select an appropriate location.');
                            return false; // Block navigation
                        }
                    }

                    return form.valid(); // Only move if valid

                    if(form.valid()){

                        return form.valid();
                    }



                },
                onStepChanged: function(event, currentIndex, priorIndex) {
                    // console.log("gggg");
                    // console.log("currentIndex => " +currentIndex);
                    if (currentIndex === 2){
                        var service_category = $(".service_category:checked").val();
                        console.clear()
                        console.log('service_category')
                        console.log(service_category)
                        var tab_service_cat_id = $("#tab_service_cat_id").val();
                        console.log(tab_service_cat_id + "hsdjfuhsdu")
                        if(service_category != tab_service_cat_id)
                        {
                            console.log("yes");
                            $.ajax({
                                type: 'get',
                                async : false,
                                url: '{{ route('get:provider-admin:get-service-sub-category-document') }}',
                                data: {service_category: service_category},
                                success: function (result) {
                                    console.log(result);
                                    $(".loadingNew").show();
                                    setTimeout(function (){
                                        if(result.success){
                                            console.log(result.document_count);
                                            if(result.document_count > 0){
                                                $("#service_sub_category").empty();
                                                $("#service_sub_category").append(result.option_data);
                                                $(".documentLists").empty();
                                                $(".documentLists").append(result.document_data);
                                                $("#tab_service_cat_id").val(service_category);
                                                $('#example-advanced-form-t-2').parent().removeClass('last');
                                                $('#example-advanced-form-t-3').parent().addClass('last');
                                                $('#example-advanced-form-t-3').parent().show();
                                                $('#example-advanced-form-t-3').show();
                                                $("#is_doc_show").val('1');
                                            }else{
                                                $("#service_sub_category").empty();
                                                $("#service_sub_category").append(result.option_data);
                                                $(".documentLists").empty();
                                                $("#tab_service_cat_id").val(service_category);
                                                $('#example-advanced-form-p-3').hide();
                                                $('#example-advanced-form-t-3').parent().closest('disabled').addClass('last');
                                                $('#example-advanced-form-t-3').parent().removeClass('last');
                                                $('#example-advanced-form-t-3').parent().hide();
                                                $('#example-advanced-form-t-3').hide();
                                                $("#is_doc_show").val('0');
                                            }
                                            var is_doc_show = $("#is_doc_show").val();
                                            if(is_doc_show == 0){
                                                console.log("hsdjh");
                                                $('a[href = "#next"]').parents('li').css('display','none');
                                                $('a[href = "#finish"]').parents('li').removeAttr('style');
                                            }else{
                                                console.log("else1");
                                                $('a[href = "#finish"]').parents('li').css('display','none');
                                                $('a[href = "#next"]').parents('li').removeAttr('style');
                                            }
                                        }
                                        $(".loadingNew").hide();
                                    },1000)
                                }
                            });
                        }
                        var is_doc_show = $("#is_doc_show").val();
                        if(is_doc_show == 0){
                            console.log("hsdjh");
                            $('a[href = "#next"]').parents('li').css('display','none');
                            $('a[href = "#finish"]').parents('li').removeAttr('style');
                        }else{
                            console.log("else1");
                            $('a[href = "#finish"]').parents('li').css('display','none');
                            $('a[href = "#next"]').parents('li').removeAttr('style');
                        }
                    }
                    // Used to skip the "Warning" step if the user is old enough.
                    // if (currentIndex === 2 && Number($("#age-2").val()) >= 18) {
                    //     form.steps("next");
                    // }
                    // // Used to skip the "Warning" step if the user is old enough and wants to the previous step.
                    // if (currentIndex === 2 && priorIndex === 3  && priorIndex === 4 ) {
                    //     form.steps("previous");
                    // }
                },
            });

            $('.service_category').on('change', function (e) {
                var service_category_type = $(".service_category:checked").attr('service_category_type');
                $("#package_name").val('');
                $("#package_price").val('');
                $("#description").val('');
                $("#max_book_quantity").val(1).change();
                if(service_category_type == 6 || service_category_type == "6"){
                    $("#service_package").html("Provider Category");
                    $("#add_service_package").html("Add Provider Category");
                    $(".hide_with_video").css("display","none");
                    $("#package_name").prop("required","false");
                    $("#max_book_quantity").prop("required","false");
                }else{
                    $("#service_package").html("Service Package");
                    $("#add_service_package").html("Add Service Package");
                    $(".hide_with_video").css("display","");
                    $("#package_name").prop("required","true");
                    $("#max_book_quantity").prop("required","true");
                }

                var service_category = $(".service_category:checked").val();

                $.ajax({
                    type: 'get',
                    async : false,
                    url: '{{ route('get:provider-admin:get-service-sub-category-document') }}',
                    data: {service_category: service_category},
                    success: function (result) {
                        console.log(result);
                        $(".loadingNew").show();
                        setTimeout(function (){
                            if(result.success){
                                if(result.document_count > 0){
                                    $("#service_sub_category").empty();
                                    $("#service_sub_category").append(result.option_data);
                                    $(".documentLists").empty();
                                    $(".documentLists").append(result.document_data);
                                    $("#tab_service_cat_id").val(service_category);
                                    $('#example-advanced-form-t-2').parent().removeClass('last');
                                    $('#example-advanced-form-t-3').parent().addClass('last');
                                    $('#example-advanced-form-t-3').parent().show();
                                    $('#example-advanced-form-t-3').show();
                                    $("#is_doc_show").val('1');
                                }else{
                                    $("#service_sub_category").empty();
                                    $("#service_sub_category").append(result.option_data);
                                    $(".documentLists").empty();
                                    $("#tab_service_cat_id").val(service_category);
                                    $('#example-advanced-form-p-3').hide();
                                    $('#example-advanced-form-t-3').parent().closest('disabled').addClass('last');
                                    $('#example-advanced-form-t-3').parent().removeClass('last');
                                    $('#example-advanced-form-t-3').parent().hide();
                                    $('#example-advanced-form-t-3').hide();
                                    $("#is_doc_show").val('0');
                                }
                            }
                            $(".loadingNew").hide();
                        },1000)
                    }
                });

            });
            $(document).on("click",".unless_doc",function (){
                var docID = $(this).data("id");
                $.uploadPreview({
                    input_field: "#document-upload-"+docID, // Default: .image-upload
                    preview_box: "#document-preview-"+docID, // Default: .image-preview
                    label_field: "#document-label-"+docID, // Default: .image-label
                    label_default: "Upload Document", // Default: Choose File
                    label_selected: "Upload Document", // Default: Change File
                    no_label: false // Default: false
                });
            })

        });
    </script>


    <script type="text/javascript" src="{{ asset('assets/js/country-code/intlTelInput.min.js')}}"></script>
    <script>
        $(document).ready(function () {
            var default_country_code = "{{ ((isset($general_settings)) && $general_settings->default_country_code != Null ) ? $general_settings->default_country_code : 'us' }}";
            var input = document.querySelector("#contact_number");
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
                initialCountry: default_country_code,
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
                    $(".loginBtn").prop('disabled', true);
                    document.getElementById("contact_numbers").value = "";
                } else {
                    document.getElementById("contact_numbers").value = contact_number;
                    document.getElementById("phone_error").innerHTML = '';
                    $(".loginBtn").prop('disabled', false);
                }
            });
        });
    </script>
    <script type="text/javascript">

        $(document).on('click', 'a[href="#finish"]', function () {
            $('#form-submit').click();
            if($("#example-advanced-form").valid()){
                $(this).addClass('buttonloader');
                $(this).html("<i class='fa fa-spinner fa-spin'></i>");
            }
        });

    </script>

    <script>
        var lati = parseFloat('{{
        isset($store_details) && $store_details->address_lat != null
            ? $store_details->address_lat
            : (isset($provider_other_details) && $provider_other_details->lat != null
                ? $provider_other_details->lat
                : ($general_settings->map_lat ?? "0"))
    }}');

        var longi = parseFloat('{{
        isset($store_details) && $store_details->address_long != null
            ? $store_details->address_long
            : (isset($provider_other_details) && $provider_other_details->long != null
                ? $provider_other_details->long
                : ($general_settings->map_long ?? "0"))
    }}');

        // Set address dynamically in the input field using JavaScript
        var address = @json(
        isset($provider_other_details) && $provider_other_details->address
            ? $provider_other_details->address
            : old('address')
    );

        var apiKey = '{{ $general_settings->map_key }}';
    </script>
    {{-- JS for the map --}}
    <script type="text/javascript" src="{{ asset('assets/js/google-map-autocomplete.js')}}"></script>
    <script>
        $(document).ready(function () {
            initialize();
        });
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
    <script src="https://maps.googleapis.com/maps/api/js?key={{ isset($general_settings)? ($general_settings->map_key != Null)? $general_settings->map_key : 0 : 0 }}&v=weekly&callback=initialize" async defer></script>

@endsection

