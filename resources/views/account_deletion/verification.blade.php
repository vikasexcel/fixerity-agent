@extends('account_deletion.layout.demo')
@section('title')
    Account Verification Pending
@endsection
@section('page-css')
    <style>
        .otp {
            width: 35px;
            display: inline-block;
        }
        .otp::-webkit-outer-spin-button,
        .otp::-webkit-inner-spin-button {
            -webkit-appearance: none;
            -moz-appearance: none;
            margin: 0;
        }

        .pcoded[theme-layout="vertical"][vertical-placement="left"][vertical-nav-type="expanded"][vertical-effect="shrink"] .pcoded-content {
            margin-left: 0;
        }
    </style>
@endsection
@section('page-content')
    {{--sidebar start--}}
    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="page-header-title">

                        <form id="main" method="post"
                              action="{{ route('post:account:deletion:verification')}}"
                              enctype="multipart/form-data">
                            {{csrf_field() }}
                            <center>
                                <div class="alert alert-success background-success"
                                     style="width: 65%;margin-bottom: 1rem">
                                    We have sent verification OTP to your register contact number. Please Enter OTP and verify your account.
                                </div>

                            </center>
                            <div class="row">
                                <div class="form-group col-sm-12">
                                    <center>
                                        <input type="hidden" class="form-control " name="store_id"
                                               required id="store_name"
                                               placeholder="*" maxlength="1"
                                               @if(isset($guard))
                                               value="{{ Illuminate\Support\Facades\Auth::guard($guard)->user()->id }}">
                                               @endif
                                        <input type="number" class="form-control otp" name="otp_1" required id="store_name" autofocus placeholder="*" maxlength="1"
                                               value="" autocomplete="off"
                                               oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"  onKeyPress="if(this.value.length==1) return false;">
                                        <input type="number" class="form-control otp" name="otp_2" required id="store_name" placeholder="*" maxlength="1"
                                               value="" autocomplete="off"
                                               oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"  onKeyPress="if(this.value.length==1) return false;">
                                        <input type="number" class="form-control otp" name="otp_3" required id="store_name" placeholder="*" maxlength="1"
                                               value="" autocomplete="off"
                                               oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"  onKeyPress="if(this.value.length==1) return false;">
                                        <input type="number" class="form-control otp" name="otp_4" required id="store_name" placeholder="*" maxlength="1"
                                               value="" autocomplete="off"
                                               oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"  onKeyPress="if(this.value.length==1) return false;">
                                    </center>
                                </div>
                            </div>
                            <center>
                                <button type="submit" class="btn btn-primary btn-round waves-effect waves-light">
                                    Verification OTP
                                </button>
                                <a href="{{ route('get:account:deletion:resend-verification-code') }}"
                                   class="btn btn-primary btn-round waves-effect waves-light">Resend Verification OTP</a>
                            </center>
                        </form>

                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->
    </div>
    <!-- [ style Customizer ] start -->
    <div id="styleSelector">
    </div>
    <!-- [ style Customizer ] end -->
    {{--sidebar end--}}

@endsection
@section('page-js')

@endsection

