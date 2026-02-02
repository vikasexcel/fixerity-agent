@extends('admin.layout.auth')
@section('title')
    Account Verification Pending
@endsection
@section('page-css')
    @if(Illuminate\Support\Facades\Auth::guard('on_demand')->user()->status == 3)
    <style>
        .pcoded-content{
            margin-left: 0 !important;
        }
    </style>
    @endif
    @if(Illuminate\Support\Facades\Auth::guard('on_demand')->user()->verified_at == Null)
        <style>
            .pcoded-content{
                margin-left: 0 !important;
            }
        </style>
    @endif
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
        .auth-box{
            margin: 20px auto 0 auto;
        }

        #google_translate_element{
            top:0;
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
                              action="{{ route('post:provider-admin:account_verification_approval')}}"
                              enctype="multipart/form-data">
                            {{csrf_field() }}
                            <div class="text-center webLogo" >
                                @if(isset($general_settings) && $general_settings->website_logo != Null)
                                    <img src="{{ asset('assets/images/website-logo-icon/'.$general_settings->website_logo)}}" alt="{{$general_settings->website_logo}}">
                                @endif
                            </div>
                            <div class="auth-box">
                                <center>
                                    <div class="alert alert-success background-success" style="width: 65%;margin-bottom: 1rem">
                                        Please enter a verification code you had received in your contact number.
                                    </div>

                                </center>
                                <div class="row">
                                    <div class="form-group col-sm-12 otp-field">
                                        <center>
                                            <input type="hidden" class="form-control " name="provider_id"
                                                   required id="provider_name"
                                                   placeholder="*" maxlength="1"
                                                   value="{{ Illuminate\Support\Facades\Auth::guard('on_demand')->user()->id }}">
                                            <input type="text" class="form-control otp" name="otp_1" required id="provider_name" autofocus placeholder="*" maxlength="1"
                                                   value="" autocomplete="off" tabindex="1"
                                                   oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"  onKeyPress="if(this.value.length==1) return false;">
                                            <input type="text" class="form-control otp" name="otp_2" required id="provider_name" placeholder="*" maxlength="1"
                                                   value="" autocomplete="off" tabindex="2"
                                                   oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"  onKeyPress="if(this.value.length==1) return false;">
                                            <input type="text" class="form-control otp" name="otp_3" required id="provider_name" placeholder="*" maxlength="1"
                                                   value="" autocomplete="off" tabindex="3"
                                                   oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"  onKeyPress="if(this.value.length==1) return false;">
                                            <input type="text" class="form-control otp" name="otp_4" required id="provider_name" placeholder="*" maxlength="1"
                                                   value="" autocomplete="off" tabindex="4"
                                                   oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"  onKeyPress="if(this.value.length==1) return false;">
                                        </center>
                                    </div>
                                </div>
                                <center>
                                    <button type="submit" class="btn btn-primary btn-round waves-effect waves-light">Verification OTP</button>
                                    <a href="{{ route('get:provider-admin:resend_verification_link') }}"
                                       class="btn btn-primary btn-round waves-effect waves-light">Resend Verification OTP</a>
                                </center>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="styleSelector"></div>

@endsection
@section('page-js')
<script type="text/javascript">

</script>
@endsection

