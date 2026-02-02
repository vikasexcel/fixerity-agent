@extends('admin.layout.all_admin')
@section('title')
    Profile
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
                            <h5>@if(isset($admin_details)) {{ $admin_details->name }} @endif Profile</h5>
                            <span>Profile</span>
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
                                <h5>Profile</h5>
                                {{--<a href="{{ route('get:admin:user_list') }}"--}}
                                {{--class="btn btn-primary m-b-0 btn-right render_link"> Back</a>--}}
                            </div>
                            <div class="card-block">
                                <form id="main" method="post"
                                      action="@if(Illuminate\Support\Facades\Auth::guard("admin")->check()) @if(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 2) {{ route('post:dispatcher:profile') }} @elseif(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 3) {{ route('post:account:profile') }} @endif @endif"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}
                                    <div class="row">
                                        <div class="form-group col-sm-7">
                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Name:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="name" required
                                                           id="name" placeholder="Name"
                                                           value="{{ (isset($admin_details)) ? $admin_details->name : old('name') }}">
                                                    <span class="error">{{ $errors->first('name') }}</span>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-4 col-form-label">Email:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="email" required
                                                           id="email" placeholder="Unique Email" readonly
                                                           value="{{ (isset($admin_details)) ? $admin_details->email : old('email') }}">
                                                    <span class="error">{{ $errors->first('email') }}</span>
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

