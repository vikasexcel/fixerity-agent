@extends('admin.layout.other_service')
@section('title')
    Add Package
@endsection
@section('page-css')
    <style>
        .toggle input[type="checkbox"] + .button-indecator:before {
            font-size: 25px;
        }

        .discount {
            display: none;
        }

    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="external-horizontal-nav">
            @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                @include('admin.include.other-service-horizontal-navbar')
            @else
                @include('admin.include.other-service-provider-navbar')
            @endif
        </div>
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Other Service Package</h5>
                            <span>@if(!isset($service_category))Add @else Edit @endif Other Service Package</span>
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
                    <form id="main" method="post" action="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('post:admin:provider_update_package',$slug) : route('get:provider-admin:update-package',$slug)}}"
                          enctype="multipart/form-data">
                        {{csrf_field() }}
                        <div class="page-body">
                            <div class="card">
                                <div class="card-header">
                                    <h5>@if(!isset($service_category))Add @else Edit @endif Other Service Package</h5>
                                    {{--<a href="{{ route('get:other_service:package_list',$slug) }}"--}}
                                    {{--class="btn btn-primary m-b-0 btn-right render_link"> Back</a>--}}
                                </div>


                                <div class="card-block">
                                    @if(isset($package))
                                        <input type="hidden" name="id" value="{{$package->id}}">
                                    @endif
                                    @if(isset($provider_id))
                                        <input type="hidden" name="provider_id" value="{{$provider_id}}">
                                    @endif
                                    <div class="row">
                                        <div class="form-group col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Category:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <select name="category" id="category" class="form-control"
                                                            required>
                                                        @if(isset($service_category_list))
                                                            <option disabled selected value="">Package Category</option>
                                                            @foreach($service_category_list as $category)
                                                                @if(isset($package)) {{$selected = $package->sub_cat_id == $category->id ? "selected":""}} @endif
                                                                <option value="{{ $category->id }}" @if(isset($package)) {{ $selected }} @endif>{{ $category->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="error">{{ $errors->first('category') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Name:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="package_name" required
                                                           id="package_name" placeholder="Package Name"
                                                           value="{{ (isset($package)) ? $package->name : old('package_name') }}">
                                                    <span class="error">{{ $errors->first('package_name') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Amount:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="number" class="form-control" name="package_price"
                                                           required
                                                           id="package_price" placeholder="Amount Value" step="0.01"
                                                           value="{{ (isset($package)) ? $package->price : old('package_price') }}">
                                                    <span class="error">{{ $errors->first('package_price') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Status:</label>
                                                <div class="col-sm-8">
                                                    <select name="status" id="status" class="form-control"
                                                            required>
                                                        @if(isset($package) && $package->status==0)
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
                                        <div class="form-group col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Max Book Quantity:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <select name="max_book_quantity" id="max_book_quantity"
                                                            class="form-control"
                                                            required>
                                                        <option disabled selected value="">Package Max Book Quantity
                                                        </option>
                                                        @for($i=1;$i<=6;$i++)
                                                            {{--@if(isset($package)) {{$selected = $package->max_book_quantity == $i ? "selected":""}} @endif--}}
                                                            <option value="{{ $i }}" {{ (isset($package) && ($package->max_book_quantity == $i)) ? "selected":""}}>{{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                    <span class="error">{{ $errors->first('max_book_quantity') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Description:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <textarea name="description" id="description" rows="6"
                                                              class="form-control" placeholder="Package Description"
                                                              required> @if(isset($package)){{$package->description}} @endif</textarea>
                                                    <span class="error">{{ $errors->first('description') }}</span>
                                                </div>
                                            </div>
                                            {{--<div class="form-group row">--}}
                                            {{--<label class="col-sm-4 col-form-label">Long Description:</label>--}}
                                            {{--<div class="col-sm-8">--}}
                                            {{--<textarea name="long_description" id="long_description" rows="4"--}}
                                            {{--class="form-control"--}}
                                            {{--placeholder="Product Long Description"></textarea>--}}
                                            {{--<span class="error">{{ $errors->first('long_description') }}</span>--}}
                                            {{--</div>--}}
                                            {{--</div>--}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <center>
                                <button type="submit" class="btn btn-primary m-b-0 buttonloader">Save</button>
                            </center>
                        </div>
                    </form>
                </div>
                <!-- Page body end -->
            </div>
        </div>
    </div>

@endsection
@section('page-js')
    <script>
        //jquery Validations
        $("#main").validate({
            rules: {
                category: {
                    required : true,
                },
                package_name: {
                    required : true,
                },
                package_price: {
                    required : true,
                    number : true
                },
                max_book_quantity: {
                    required : true,
                    number : true
                },
                description: {
                    required : true,
                }
            },
            messages: {
                package_price: {
                    number: "Please enter valid amount."
                }
            },
            submitHandler: function(form) {
                $('.buttonloader').attr("disabled", true);
                $('.buttonloader').html("<i class='fa fa-spinner fa-spin'></i>");
                form.submit();
            }
        });
    </script>
@endsection

