@extends('admin.layout.all_admin')
@section('title')
    Change Password
@endsection
@section('page-css')
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
                            <h5>
                                @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                                    Admin
                                @endif
                                @if(Illuminate\Support\Facades\Auth::guard("on_demand")->check())
                                    Provider
                                @endif
                            </h5>
                            <span>Change Password</span>
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
                                <h5>Change Password</h5>
                                {{--<a href="" class="btn btn-primary m-b-0 btn-right"> Back</a>--}}
                            </div>
                            <div class="card-block">
                                <form id="main" method="post"
                                      action="@if(Illuminate\Support\Facades\Auth::guard("admin")->check())@if(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1){{ route('post:admin:change_password') }}@elseif(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 2){{ route('post:dispatcher:change_password') }}@elseif(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 3){{ route('post:account:change_password') }}@endif @elseif(Illuminate\Support\Facades\Auth::guard("on_demand")->check()) {{ route('post:provider-admin:change_password') }} @else {{ route('post:store-admin:change_password') }} @endif"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}
                                    <div class="row">
                                        <div class="form-group col-sm-8">
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label"> Current Password:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="password" class="form-control" name="old_password"
                                                           required
                                                           id="old_password" placeholder="Old Password"
                                                           value="{{ (isset($service_category)) ? $service_category->password : old('old_password') }}">
                                                    <span class="error">{{ $errors->first('old_password') }}</span>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label"> New Password:<sup class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="password" class="form-control" name="new_password"
                                                           required
                                                           id="new_password" placeholder="New Password"
                                                           value="{{ (isset($service_category)) ? $service_category->password : old('new_password') }}">
                                                    <span class="error">{{ $errors->first('new_password') }}</span>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label"> Re-Type Password:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="password" class="form-control" name="confirm_password"
                                                           required
                                                           id="confirm_password" placeholder="Confirm Password"
                                                           value="{{ (isset($service_category)) ? $service_category->password : old('confirm_password') }}">
                                                    <span class="error">{{ $errors->first('confirm_password') }}</span>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label"></label>
                                                <div class="col-sm-8">
                                                    <button type="submit" class="btn btn-primary m-b-0 button_loader">Save</button>
                                                </div>
                                            </div>
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
    <script>
        // Disable button after form submission
        $('#main').on('submit', function() {
            $('.button_loader').attr('disabled', true);  // Disable the button
        });
    </script>
@endsection

