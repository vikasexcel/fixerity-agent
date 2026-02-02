@extends('admin.layout.super_admin')
@section('title')
    @if(isset($service_slider_banner)) Edit @else Add @endif On Demand Service Slider List
@endsection
@section('page-css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet"/>
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

        #upload-image-preview {
            height: 160px;
            background-size: unset!important;
            background-repeat: no-repeat;
        }

        @if(isset($service_slider_banner))
        #upload-image-preview {
            background: url({{ asset('/assets/images/service-slider-banner/'.$service_slider_banner->banner_image) }}) no-repeat;
            background-size: contain;
            background-position: center;
        }
        @endif


        /*start select style*/
        .select2-container {
            width: 100% !important;
            vertical-align: unset;
        }
        .select2-container--default .select2-selection--single {
            height: auto;
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            /*padding-top: 1px;*/
            padding: 4px 30px 4px 20px;
            background-color: transparent;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 34px;
        }
        /*end select style*/
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="other-service-horizontal-nav">
            @include('admin.include.other-service-horizontal-navbar')
        </div>
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>On Demand Service Slider</h5>
                            <span>@if(isset($service_slider_banner)) Edit @else Add @endif On Demand Service Slider</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <a href="{{ route('get:admin:on_demand_service_slider_list',$slug) }}"
                       class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                </div>
            </div>
        </div>


        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <form id="main" method="post" action="{{ route('post:admin:update_on_demand_service_slider',$slug) }}"
                              enctype="multipart/form-data">
                            {{csrf_field() }}
                            <input type="hidden" name="banner_id" value="{{ isset($service_slider_banner) ? $service_slider_banner->id : '' }}">
                            <div class="card">
                                <div class="card-header">
                                    <h5>@if(isset($service_slider_banner)) Edit @else Add @endif On Demand Service  Banner Image</h5>
                                </div>
                                <div class="card-block">

                                    <div class="row">
                                        <div class="form-group col-sm-12">

                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label image">Select Category:<sup
                                                        class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <select id="store" name="ondemand_category_id" class="js-example-placeholder-single1 js-states form-control" required>
                                                        <option disabled selected value=""></option>
                                                        <option disabled>Select a Category</option>
                                                        @if(isset($ondemand_category_list))
                                                            @if(!$ondemand_category_list->isEmpty())
                                                                @foreach($ondemand_category_list as $key => $store)
                                                                    {{ $selected = isset($service_slider_banner) ? ($service_slider_banner->ondemand_cat_id == $store->id)?  "selected" : "" : "" }}
                                                                    <option value="{{ $store->id }}" {{ $selected }}>{{ ucwords($store->ondemand_category_name)  }}</option>
                                                                @endforeach
                                                            @endif
                                                        @endif
                                                    </select>
                                                    <span class="error">{{ $errors->first('ondemand_category_id') }}</span>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label image">Banner Image:<sup
                                                        class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <div id="upload-image-preview">
                                                        @if(isset($service_slider_banner))
                                                            <label for="image-upload" id="image-label">Change
                                                                Image</label>
                                                            <input type="file" id="image-upload" name="image" accept="image/*"/>
                                                        @else
                                                            <label for="image-upload" id="image-label">Upload
                                                                Image</label>
                                                            <input type="file" id="image-upload" name="image" accept="image/*"
                                                                   required/>
                                                        @endif
                                                    </div>
                                                    <span class="note">[Note: Upload only png and jpg file dimension  500*150  & max size 100kb.]</span>
                                                    <span class="error">{{ $errors->first('image') }}</span>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <center>
                                        <button type="submit" class="btn btn-primary m-b-0 button_loader">Save</button>
                                    </center>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
    <script>
        $("#store").select2({
            placeholder: "Select a Category",
            allowClear: true,
        });

        // Disable button after form submission
        $('#main').on('submit', function() {
            $('.button_loader').attr('disabled', true);  // Disable the button
        });
    </script>
@endsection

