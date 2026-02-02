@extends('admin.layout.other_service')
@section('title')
    Add Provider Services
@endsection
@section('page-css')
    <style>
        /*checkbox style*/
        .border-checkbox-section .border-checkbox-group .border-checkbox-label {
            height: 7px;
            padding-left: 20px;
            margin-right: 7px;
        }

        .border-checkbox-section .border-checkbox-group {
            margin-right: 15px;
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
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Provider Services</h5>
                            <span>Add Provider Services</span>
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
                                <h5>Add Provider Services</h5>
                                @if(Illuminate\Support\Facades\Auth::guard("on_demand")->check())
                                    <a href="{{ route('get:provider-admin:services') }}"
                                       class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                                @else
                                    <a href="{{ route('get:admin:provider_list',["provider-services"]) }}"
                                       class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                                @endif
                            </div>
                            <div class="card-block">
                                <form id="main" method="post" action="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? '' : ((Illuminate\Support\Facades\Auth::guard("on_demand")->check()) ? route('post:provider-admin:add_services') : '') }}"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}

                                    @if(isset($service_category))
                                        <input type="hidden" name="id" value="{{$service_category->id}}">
                                    @endif
                                    <div class="row">

                                        <div class="form-group col-sm-12">
                                            {{--<div class="form-group row">--}}
                                            {{--<label class="col-sm-3 col-form-label">Service Category List:<sup--}}
                                            {{--class="error">*</sup></label>--}}
                                            {{--<div class="col-sm-9">--}}
                                            {{--<select name="vehicle_type_id" id="vehicle_type_id"--}}
                                            {{--class="form-control">--}}
                                            {{--<option disabled selected>Select Vehicle Type</option>--}}
                                            {{--@if(isset($service_category_multiple))--}}
                                            {{--@foreach($service_category_multiple as $key => $service_category_details)--}}
                                            {{--{{ $selected = (isset($service_category) && $service_category->id == $service_category_details->id ) ? "selected" : "" }}--}}
                                            {{--<option value="{{ $service_category_details->id }}" {{ $selected }}>{{ $service_category_details->name }}</option>--}}
                                            {{--@endforeach--}}
                                            {{--@endif--}}
                                            {{--</select>--}}
                                            {{--<span class="error">{{ $errors->first('vehicle_type_id') }}</span>--}}
                                            {{--</div>--}}
                                            {{--</div>--}}
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Select Services:<sup
                                                            class="error">*</sup></label>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-sm-12">
                                                    <div class="border-checkbox-section row">
                                                        @if(isset($service_category_multiple))
                                                            @foreach($service_category_multiple as $key => $service_category_details)
                                                                <div class="col-sm-4" style="margin: 10px 0">
                                                                    <div class="border-checkbox-group border-checkbox-group-primary">
                                                                        <input name="provider_services[]"
                                                                               value="{{ $service_category_details->id }}"
                                                                               class="border-checkbox"
                                                                               {{ (isset($service_category) && $service_category->id == $service_category_details->id ) ? "checked" : "" }}
                                                                               type="checkbox"
                                                                               id="checkbox{{ $service_category_details->id }}">
                                                                        <label class="border-checkbox-label"
                                                                               for="checkbox{{ $service_category_details->id }}"></label>
                                                                        {{ $service_category_details->name }}
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                    <span class="error">{{ $errors->first('delivery_boy') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="row">
                                        {{--<label class="col-sm-2"></label>--}}
                                        <div class="col-sm-12">
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
                label_default: "Choose Image",   // Default: Choose File
                label_selected: "Change Image",  // Default: Change File
                no_label: false                 // Default: false
            });

            // Disable button after form submission
            $('#main').on('submit', function() {
                $('.button_loader').attr('disabled', true);  // Disable the button
            });
        });
    </script>
@endsection

