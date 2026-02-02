@extends('admin.layout.super_admin')
@section('title')
    @if(!isset($required_document))Add @else Edit @endif Required Document
@endsection
@section('page-css')
@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ other service horizontal navbar ] start -->
        <div class="other-service-horizontal-nav">
            @if($segment === 'provider-services')
                @include('admin.include.other-service-horizontal-navbar')
            @endif
        </div>
        <!-- [ other service horizontal navbar ] end -->

        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Required Document</h5>
                            <span>@if(!isset($required_document))Add @else Edit @endif Required Document
                                @if(isset($service_category) && $service_category->name != Null)
                                    of {{ ucwords(strtolower($service_category->name)) }} @endif
                            </span>
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
                                <h5>@if(!isset($required_document))Add @else Edit @endif Required
                                    Document @if(isset($service_category) && $service_category->name != Null)
                                        of {{ ucwords(strtolower($service_category->name)) }} @endif
                                </h5>
                                {{--<a href="{{ route('get:admin:required_documents_list') }}"--}}
                                {{--class="btn btn-primary m-b-0 btn-right render_link"> Back</a>--}}
                            </div>
                            <div class="card-block">
                                <form id="main" method="post"
                                      action="@if($segment === 'store') {{ route('post:admin:store_update_required_document',[$slug]) }} @elseif($segment === 'provider-services') {{ route('post:admin:other_service_update_required_document',[$slug]) }} @elseif($segment === 'transport') {{ route('post:admin:transport_update_required_document',[$slug]) }} @else {{ route('post:admin:transport_update_required_document',[$slug]) }} @endif"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}

                                    @if(isset($required_document))
                                        <input type="hidden" name="id" value="{{$required_document->id}}">
                                    @endif
                                    <div class="row">
                                        <div class="form-group col-sm-8">
                                            @if(isset($service_category))
                                                <input type="hidden" name="service_cat_id"
                                                       value="{{$service_category->id}}">
                                            @endif
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Name:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="name" required
                                                           id="name" placeholder="Document Name"
                                                           value="{{ (isset($required_document)) ? $required_document->name : old('name') }}">
                                                    <span class="error">{{ $errors->first('name') }}</span>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Status:</label>
                                                <div class="col-sm-8">
                                                    <select name="status" id="status" class="form-control"
                                                            required>
                                                        @if(isset($required_document) && $required_document->status==0)
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
                                    <div class="row">
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

