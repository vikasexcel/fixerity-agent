@extends('admin.layout.super_admin')
@section('title')
    @if(!isset($service_sub_category))
        Add Other Service Category
    @else
        Edit Other Service Category
    @endif
@endsection
@section('page-css')
    <style>
        .toggle input[type="checkbox"] + .button-indecator:before {
            font-size: 25px;
        }

        .discount {
            display: none;
        }

        #upload-image-preview {
            height: 120px;
            background-size: contain !important;
            background-position: center !important;
        }

        #upload-image-preview label {
            width: 150px;
            height: 40px;
            font-size: 16px;
            line-height: 40px;
        }
        @if(isset($service_sub_category))
        #upload-image-preview {
            background: url({{ asset('/assets/images/service-category/other-service-sub-category/'.$service_sub_category->icon_name) }}) no-repeat;
            background-size: contain;
            background-position: center;
        }
        @endif
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="external-horizontal-nav">
            @include('admin.include.other-service-horizontal-navbar')
        </div>
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Other Service Category</h5>
                            <span>@if(!isset($service_sub_category))Add @else Edit @endif
                                Other Service Category</span>
                        </div>
                    </div>
                </div>
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
                                <h5>@if(!isset($service_sub_category))Add @else Edit @endif Other Service
                                    Category</h5>
                                <a href="{{ route('get:admin:other_service_sub_category_list',$slug) }}"
                                   class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                            </div>
                            <div class="card-block">
                                <form id="main" method="post"
                                      action="{{route('get:admin:update_other_service_sub_category',$slug)}}"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}
                                    @if(isset($service_sub_category))
                                        <input type="hidden" name="id" value="{{$service_sub_category->id}}">
                                    @endif
                                    <div class="row">
                                        <div class="form-group col-sm-7">
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Category Name:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" name="name" required
                                                           id="name" placeholder="Category Name"
                                                           value="{{ (isset($service_sub_category)) ? $service_sub_category->name : old('name') }}">
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
                                                        <label class="col-sm-3 col-form-label">Unique Category Name (in {{ $language_name }}):<sup class="error">*</sup></label>
                                                        <div class="col-sm-9">
                                                            <input type="text" class="form-control" name="{{$col_name}}" required
                                                                   id="{{$col_name}}" placeholder="Unique Category Name in {{$language_name}}"
                                                                   value="{{ (isset($service_sub_category)) ? $service_sub_category->$col_name : old('ar_name') }}">
                                                            <span class="error">{{ $errors->first($col_name) }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Product Status:</label>
                                                <div class="col-sm-9">
                                                    <select name="status" id="status" class="form-control"
                                                            required>
                                                        @if(isset($service_sub_category) && $service_sub_category->status==0)
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
                                        <div class="form-group col-sm-5">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label image image-label">Category Icon:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <div id="upload-image-preview">
                                                        @if(isset($service_sub_category))
                                                            <label for="image-upload" id="image-label">Change
                                                                Icon</label>
                                                            <input type="file" id="image-upload" name="category_icon" accept=".jpg,.jpeg,.png"/>
                                                        @else
                                                            <label for="image-upload" id="image-label">Upload
                                                                Icon</label>
                                                            <input type="file" id="image-upload" name="category_icon"
                                                                   required accept=".jpg,.jpeg,.png"/>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-sm-12">
                                                    <span class="note">[Note: Upload only png and jpg file max dimension 250*250.]</span>
                                                    <span class="error">{{ $errors->first('category_icon') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row">
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
                label_default: "Choose Icon",   // Default: Choose File
                label_selected: "Change Icon",  // Default: Change File
                no_label: false                 // Default: false
            });

            // Disable button after form submission
            $('#main').on('submit', function() {
                $('.button_loader').attr('disabled', true);  // Disable the button
            });
        });
    </script>
@endsection

