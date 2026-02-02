@extends('admin.layout.super_admin')
@section('title')
    @if(!isset($service_category))Add @else Edit @endif Service
@endsection
@section('page-css')
    <style>
        .yellow .radio .helper::after, .yellow .radio .helper::before {
            border: 0.125rem solid #FFB64D;
        }

        .yellow .radio .helper::after {
            -webkit-transform: scale(0);
            transform: scale(0);
            background-color: #FFB64D;
            border-color: #FFB64D;
        }

        .red .radio .helper::after, .red .radio .helper::before {
            border: 0.125rem solid #FF5370;
        }

        .red .radio .helper::after {
            -webkit-transform: scale(0);
            transform: scale(0);
            background-color: #FF5370;
            border-color: #FF5370;
        }
        @if(isset($service_category))
            #upload-image-preview {
                background: url({{ asset('/assets/images/provider-banners/'.$service_category->banner_image) }}) no-repeat;
                background-size: contain;
                background-position: center;
            }
        @endif
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Service</h5>
                            <span>@if(!isset($service_category))Add @else Edit @endif </span>
                        </div>
                    </div>
                </div>
                {{--<div class="col-lg-4">--}}
                {{--<div class="page-header-breadcrumb">--}}
                {{--<ul class=" breadcrumb breadcrumb-title">--}}
                {{--<li class="breadcrumb-item">--}}
                {{--<a href="{{ route('get:admin:dashboard') }}"><i class="feather icon-home"></i> Dashboard</a>--}}
                {{--</li>--}}
                {{--<li class="breadcrumb-item"><a href="">@if(!isset($service_category))Add @else Edit @endif--}}
                {{--Service Category</a>--}}
                {{--</li>--}}
                {{--</ul>--}}
                {{--</div>--}}
                {{--</div>--}}
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>@if(!isset($service_category))Add @else Edit @endif Service</h5>
                                <a href="{{ route('get:admin:other_service_list') }}"
                                   class="btn btn-primary m-b-0 btn-right"> Back</a>
                            </div>
                            <div class="card-block">
                                <form id="main" method="post" action="{{ route('post:admin:update_service_category') }}"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}
                                    @if(isset($service_category))
                                        <input type="hidden" name="id" value="{{$service_category->id}}">
                                    @endif
                                    <div class="row">
                                        <div class="form-group col-sm-8">
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Name:<sup class="error">*</sup></label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" name="name" required
                                                           id="name" placeholder="Unique Category Name"
                                                           value="{{ (isset($service_category)) ? $service_category->name : old('name') }}">
                                                    <span class="error">{{ $errors->first('name') }}</span>
                                                </div>
                                            </div>

                                            @if(isset($language_lists))

                                                @foreach($language_lists as $single_lang)
                                                    @php
                                                        $language_name =  isset($single_lang->language_name)?$single_lang->language_name:"";
                                                        $language_code =  isset($single_lang->language_code)?$single_lang->language_code:"";
                                                        $col_name = $language_code."_name";

                                                    @endphp
                                                    <div class="form-group row">
                                                        <label class="col-sm-3 col-form-label">Name (in {{ $language_name }}):<sup class="error">*</sup></label>
                                                        <div class="col-sm-9">
                                                            <input type="text" class="form-control" name="{{$col_name}}" required
                                                                   id="{{$col_name}}" placeholder="Unique Name in {{$language_name}}"
                                                                   value="{{ (isset($service_category)) ? $service_category->$col_name : old('ar_name') }}">
                                                            <span class="error">{{ $errors->first($col_name) }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Icon Image:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-9">
                                                    @if(isset($service_category))
                                                        <div class="col-sm-4">
                                                            <img src="{{ asset('/assets/images/service-category/'.$service_category->icon_name)}}"
                                                                 style="width: 50px; height: 50px">
                                                        </div>
                                                    @endif
                                                    <input type="file" class="form-control" name="icon"
                                                           id="icon" @if(!isset($service_category)) required @endif>
                                                    {{--<span class="note">[Note: Upload only png icon dimension between 50*50 to 100*100 & max size 15kb.]</span>--}}
                                                    <span class="note">[Note: Upload only png icon max dimension 200*200]</span>
                                                    <span class="error">{{ $errors->first('icon') }}</span>
                                                </div>
                                            </div>
{{--                                            <div class="form-group row">--}}
{{--                                                <label class="col-sm-3 col-form-label">Icon Color:<sup--}}
{{--                                                            class="error">*</sup></label>--}}
{{--                                                <div class="col-sm-9">--}}
{{--                                                    <div class="form-radio">--}}
{{--                                                        <div class="yellow" style="float: left;">--}}
{{--                                                            <div class="radio radio-inline">--}}
{{--                                                                <label>--}}
{{--                                                                    <input type="radio" value="3" required--}}
{{--                                                                           name="icon_type" {{ (isset($service_category)) ? ($service_category->category_type == 3)? "checked ": "" : ""}}>--}}
{{--                                                                    <i class="helper"></i>Yellow--}}
{{--                                                                </label>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="red" style="float: left;">--}}
{{--                                                            <div class="radio radio-inline">--}}
{{--                                                                <label>--}}
{{--                                                                    <input type="radio" value="4"--}}
{{--                                                                           name="icon_type" {{ (isset($service_category)) ? ($service_category->category_type == 4)? "checked ": "" : ""}}>--}}
{{--                                                                    <i class="helper"></i>Red--}}
{{--                                                                </label>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}
{{--                                                    </div>--}}
{{--                                                    <span class="error">{{ $errors->first('icon_type') }}</span>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                            <div class="form-group row">--}}
{{--                                                <label class="col-sm-3 col-form-label image">Banner Image:<sup--}}
{{--                                                        class="error">*</sup></label>--}}
{{--                                                <div class="col-sm-9">--}}
{{--                                                    <div id="upload-image-preview">--}}
{{--                                                        @if(isset($service_category))--}}
{{--                                                            <label for="image-upload" id="image-label">Change--}}
{{--                                                                Image</label>--}}
{{--                                                            <input type="file" id="image-upload" name="banner_image" accept="image/*"/>--}}
{{--                                                        @else--}}
{{--                                                            <label for="image-upload" id="image-label">Upload--}}
{{--                                                                Image</label>--}}
{{--                                                            <input type="file" id="image-upload" name="banner_image" accept="image/*"--}}
{{--                                                                   required--}}
{{--                                                            />--}}
{{--                                                        @endif--}}
{{--                                                    </div>--}}
{{--                                                    <span class="note">[Note: Upload only png and jpg file dimension  500*150  & max size 100kb.]</span>--}}
{{--                                                    <span class="error">{{ $errors->first('banner_image') }}</span>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Status:</label>
                                                <div class="col-sm-9">
                                                    <select name="status" id="status" class="form-control"
                                                            required>
                                                        @if(isset($service_category) && $service_category->status==0)
                                                            <option value="1">Activate</option>
                                                            <option value="0" selected>Deactivate</option>
                                                        @else
                                                            <option value="1" selected>Activate</option>
                                                            <option value="0">Deactivate</option>
                                                        @endif
                                                    </select>
                                                    <span class="error">{{ $errors->first('status') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-2"></label>
                                        <div class="col-sm-10">
                                            <button type="submit" class="btn btn-primary m-b-0">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Page body end -->
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
@endsection

