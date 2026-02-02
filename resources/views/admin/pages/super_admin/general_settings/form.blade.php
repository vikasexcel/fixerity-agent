@extends('admin.layout.super_admin')
@section('title')
    Site Setting
@endsection
@section('page-css')
    <style>
        .image {
            padding-top: 0;
        }

        #image-preview-1, #image-preview-2 {
            border: 1px solid #9e9e9e;
            width: 100%;
            height: 200px;
            position: relative;
            overflow: hidden;
            background-color: #ffffff;
            color: #ecf0f1;
            cursor: pointer;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        #image-preview-1 input, #image-preview-2 input {
            line-height: 200px;
            font-size: 200px;
            position: absolute;
            opacity: 0;
            z-index: 10;
        }

        #image-preview-1 label, #image-preview-2 label {
            position: absolute;
            z-index: 5;
            opacity: 0.8;
            cursor: pointer !important;
            background-color: #000000d1;
            color: white;
            width: 200px;
            height: 50px;
            font-size: 20px;
            line-height: 50px;
            text-transform: uppercase;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            text-align: center;
        }
        .pencil-image-card {
            left: auto !important;
            bottom: auto !important;
            width: fit-content !important;
            padding-right: 3% !important;
        }

        .res-banner-label {
            margin-top: -20px;
            margin-bottom: 15px;
        }
        .cash_out_card{
            display: none;
        }
        @if(isset($general_settings) && $general_settings->website_logo != Null)
            #image-preview-1 {
            background: url({{ asset('/assets/images/website-logo-icon/'.$general_settings->website_logo) }}) no-repeat;
            width: 100%;
            height: 200px;
            background-size: 350px 115px;
            /*background-size: cover;*/
            background-position: center;
        }

        @endif
@if(isset($general_settings) && $general_settings->website_favicon != Null)
#image-preview-2 {
            background: url({{ asset('/assets/images/website-logo-icon/'.$general_settings->website_favicon) }}) no-repeat;
            width: 100%;
            height: 200px;
            /*background-size: cover;*/
            background-size: 100px 100px;
            /*background-attachment: fixed;*/
            background-position: center;
        }

        @endif

        .form-group {
            margin-bottom: 10px;
        }

        #on_demand_start_service_time-error{
            padding-left: 15px !important;
            padding-top: 10px !important;
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Site Setting</h5>
                            <span>@if(!isset($general_settings))Add @else Edit @endif Site Setting</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <form id="main" method="post" action="{{route('post:admin:update_general_setting')}}"
                              enctype="multipart/form-data">
                            {{csrf_field() }}
                            @if(isset($general_settings))
                                <input type="hidden" name="id" value="{{$general_settings->id}}">
                            @endif

                            <div class="card">
                                <div class="card-header">
                                    <h5>@if(!isset($general_settings))Add @else Edit @endif Site Setting</h5>
                                </div>
                                <div class="card-block">
                                    <div class="row">
                                        <div class="form-group col-sm-12">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Website Name:<sup
                                                            class="error">*</sup></label>
                                                <div class="col-sm-12">
                                                    <input type="text" class="form-control" name="website_name" required
                                                           id="website_name" placeholder="Website Name"
                                                           value="{{ (isset($general_settings)) ? $general_settings->website_name : old('website_name') }}">
                                                    <span class="error">{{ $errors->first('website_name') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-sm-6">
                                                    <div class="row">
                                                        <label class="col-sm-12 col-form-label image">Website
                                                            Logo:</label>
                                                        <div class="col-sm-12">
                                                            <div id="image-preview-1">
                                                                @if(isset($general_settings))
                                                                    <label for="image-upload-1"
                                                                           class="bg-transparent pencil-image-card"
                                                                           id="image-label">
                                                                        <i class="fas text-dark fa-pencil-alt"></i>
                                                                    </label>
                                                                    {{--<img id="pre-img-res" src="{{ asset('restaurant/'.$restaurant->image) }}">--}}
                                                                @else
                                                                    <label for="image-upload-1"
                                                                           class="bg-transparent pencil-image-card"
                                                                           id="image-label">
                                                                        <i class="fas text-dark fa-pencil-alt"></i>
                                                                    </label>
                                                                @endif
                                                                <input type="file" id="image-upload-1"
                                                                       name="website_logo"/>
                                                            </div>
                                                            <span class="note">[Note: Upload only png file dimension between 300*300 to 500*500 & max size 100kb.]</span>
                                                            <span
                                                                class="error">{{ $errors->first('website_logo') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="row">
                                                        <label class="col-sm-12 col-form-label image">Website
                                                            Favicon:</label>
                                                        <div class="col-sm-12">
                                                            <div id="image-preview-2">
                                                                @if(isset($general_settings))
                                                                    <label for="image-upload-2"
                                                                           class="bg-transparent pencil-image-card"
                                                                           id="image-label">
                                                                        <i class="fas text-dark fa-pencil-alt"></i>
                                                                    </label>
                                                                    {{--<img id="pre-img-res" src="{{ asset('restaurant/'.$restaurant->image) }}">--}}
                                                                @else
                                                                    <label for="image-upload-2"
                                                                           class="bg-transparent pencil-image-card"
                                                                           id="image-label">
                                                                        <i class="fas text-dark fa-pencil-alt text-dark"></i>
                                                                    </label>
                                                                @endif
                                                                <input type="file" id="image-upload-2"
                                                                       name="website_favicon"/>
                                                            </div>
                                                            <span class="note">[Note: Upload only ico file dimension max 50*50 & max size 100kb.]</span>
                                                            <span
                                                                class="error">{{ $errors->first('website_favicon') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Address:</label>
                                                <div class="col-sm-12">
                                                    <textarea name="address" id="address"
                                                              class="form-control"
                                                              placeholder="address">{{ (isset($general_settings)) ? $general_settings->address : old('address') }}</textarea>
                                                    <span class="error">{{ $errors->first('address') }}</span>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <div class="col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Admin Receive Email:</label>
                                                        <div class="col-sm-12">
                                                            <input type="email" class="form-control" name="send_receive_email"
                                                                   id="send_receive_email" placeholder="Admin Receive Email"
                                                                   value="{{ (isset($general_settings)) ? App\Models\User::Email2Stars($general_settings->send_receive_email) : old('send_receive_email') }}">
                                                            <span class="error">{{ $errors->first('send_receive_email') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Email:</label>
                                                        <div class="col-sm-12">
                                                            <input type="email" class="form-control" name="email"
                                                                   id="email" placeholder="Email"
                                                                   value="{{ (isset($general_settings)) ? App\Models\User::Email2Stars($general_settings->email) : old('email') }}">
                                                            <span class="error">{{ $errors->first('email') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Contact No:</label>
                                                        <div class="col-sm-12">
                                                            <input type="text" class="form-control" name="contact_no"
                                                                   id="contact_no" placeholder="Contact No"
                                                                   value="{{ (isset($general_settings)) ? App\Models\User::ContactNumber2Stars($general_settings->contact_no) : old('contact_no') }}">
                                                            <span class="error">{{ $errors->first('contact_no') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Copy Right
                                                            Content:</label>
                                                        <div class="col-sm-12">
                                                            <input type="text" class="form-control" name="copy_right"
                                                                   id="copy_right" placeholder="Copy Right Content"
                                                                   value="{{ (isset($general_settings)) ? $general_settings->copy_right : old('copy_right') }}">
                                                            <span class="error">{{ $errors->first('copy_right') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5>Social Link</h5>
                                </div>
                                <div class="card-block">
                                    <div class="row">
                                        <div class="form-group col-sm-12">
                                            <div class="form-group row">
                                                <div class="col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Facebook Link:</label>
                                                        <div class="col-sm-12">
                                                            <input type="url" class="form-control" name="facebook_link"
                                                                   id="facebook_link" placeholder="Facebook Link"
                                                                   value="{{ (isset($general_settings)) ? $general_settings->facebook_link : old('facebook_link') }}">
                                                            <span class="error">{{ $errors->first('facebook_link') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Twitter Link:</label>
                                                        <div class="col-sm-12">
                                                            <input type="url" class="form-control" name="twitter_link"
                                                                   id="twitter_link" placeholder="Twitter Link"
                                                                   value="{{ (isset($general_settings)) ? $general_settings->twitter_link : old('twitter_link') }}">
                                                            <span class="error">{{ $errors->first('twitter_link') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Linkedin Link:</label>
                                                        <div class="col-sm-12">
                                                            <input type="url" class="form-control" name="linkedin_link"
                                                                   id="linkedin_link" placeholder="Linkedin Link"
                                                                   value="{{ (isset($general_settings)) ? $general_settings->linkedin_link : old('linkedin_link') }}">
                                                            <span class="error">{{ $errors->first('linkedin_link') }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label class="col-sm-12 col-form-label">Instagram Link:</label>
                                                        <div class="col-sm-12">
                                                            <input type="url" class="form-control" name="instagram_link"
                                                                   id="instagram_link" placeholder="Instagram Link"
                                                                   value="{{ (isset($general_settings)) ? $general_settings->instagram_link : old('instagram_link') }}">
                                                            <span class="error">{{ $errors->first('instagram_link') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5>App-Store and Play-Store Link</h5>
                                </div>
                                <div class="card-block">
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">User Playstore
                                                    Link:</label>
                                                <div class="col-sm-12">
                                                    <input type="url" class="form-control"
                                                           name="user_playstore_link"
                                                           id="user_playstore_link"
                                                           placeholder="User Playstore Link"
                                                           value="{{ (isset($general_settings)) ? $general_settings->user_playstore_link : old('user_playstore_link') }}">
                                                    <span
                                                            class="error">{{ $errors->first('user_playstore_link') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">User Appstore
                                                    Link:</label>
                                                <div class="col-sm-12">
                                                    <input type="url" class="form-control"
                                                           name="user_appstore_link"
                                                           id="user_appstore_link"
                                                           placeholder="User Appstore Link"
                                                           value="{{ (isset($general_settings)) ? $general_settings->user_appstore_link : old('user_appstore_link') }}">
                                                    <span
                                                            class="error">{{ $errors->first('user_appstore_link') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Provider Playstore
                                                    Link:</label>
                                                <div class="col-sm-12">
                                                    <input type="url" class="form-control"
                                                           name="provider_playstore_link"
                                                           id="provider_playstore_link"
                                                           placeholder="Provider Playstore Link"
                                                           value="{{ (isset($general_settings)) ? $general_settings->provider_playstore_link : old('provider_playstore_link') }}">
                                                    <span
                                                            class="error">{{ $errors->first('provider_playstore_link') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Provider Appstore
                                                    Link:</label>
                                                <div class="col-sm-12">
                                                    <input type="url" class="form-control"
                                                           name="provider_appstore_link"
                                                           id="provider_appstore_link"
                                                           placeholder="Provider Appstore Link"
                                                           value="{{ (isset($general_settings)) ? $general_settings->provider_appstore_link : old('provider_appstore_link') }}">
                                                    <span
                                                            class="error">{{ $errors->first('provider_appstore_link') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5>User Refer Discount</h5>
                                </div>
                                <div class="card-block">
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Used User
                                                    Discount:</label>
                                                <div class="col-sm-12">
                                                    <input type="number" class="form-control"
                                                           name="used_user_discount" step="0.01"
                                                           min="0"
                                                           id="used_user_discount"
                                                           placeholder="Used User Discount"
                                                           value="{{ (isset($general_settings)) ? $general_settings->used_user_discount : old('used_user_discount') }}">
                                                    <span
                                                            class="error">{{ $errors->first('used_user_discount') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Refer User
                                                    Discount:</label>
                                                <div class="col-sm-12">
                                                    <input type="number" class="form-control"
                                                           min="0"
                                                           name="refer_user_discount" step="0.01"
                                                           id="refer_user_discount"
                                                           placeholder="Refer User Discount"
                                                           value="{{ (isset($general_settings)) ? $general_settings->refer_user_discount : old('refer_user_discount') }}">
                                                    <span
                                                            class="error">{{ $errors->first('refer_user_discount') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Used User Discount
                                                    Type:</label>
                                                <div class="col-sm-12">
                                                    <select type="text" class="form-control"
                                                            name="used_user_discount_type"
                                                            id="used_user_discount_type"
                                                            placeholder="Used User Discount Type"
                                                            value="{{ (isset($general_settings)) ? $general_settings->used_user_discount_type : old('used_user_discount_type') }}">
                                                        <option value="">Select Used User Discount Type</option>
                                                        <option
                                                                value="1" {{ (isset($general_settings)) ? ($general_settings->used_user_discount_type == 1 ? "selected" : "") : "" }}>
                                                            Amount
                                                        </option>
                                                        <option
                                                                value="2" {{ (isset($general_settings)) ? ($general_settings->used_user_discount_type == 2 ? "selected" : "") : "" }}>
                                                            Percentage
                                                        </option>
                                                    </select>

                                                    <span
                                                            class="error">{{ $errors->first('used_user_discount_type') }}</span>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Refer User Discount
                                                    Type:</label>
                                                <div class="col-sm-12">
                                                    <select type="text" class="form-control"
                                                            name="refer_user_discount_type"
                                                            id="refer_user_discount_type"
                                                            placeholder="Refer User Discount Type">
                                                        <option value="">Select Refer User Discount Type
                                                        </option>
                                                        <option
                                                                value="1" {{ (isset($general_settings)) ? ($general_settings->refer_user_discount_type == 1 ? "selected" : "") : "" }}>
                                                            Amount
                                                        </option>
                                                        <option
                                                                value="2" {{ (isset($general_settings)) ? ($general_settings->refer_user_discount_type == 2 ? "selected" : "") : "" }}>
                                                            Percentage
                                                        </option>
                                                    </select>

                                                    <span
                                                            class="error">{{ $errors->first('refer_user_discount_type') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5>On-Demand Start Service Time</h5>
                                </div>
                                <div class="card-block">
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            Provider can start the service before
                                            <input
                                                type="number"
                                                class="form-control"
                                                name="on_demand_start_service_time"
                                                id="on_demand_start_service_time"
                                                placeholder="Time"
                                                value="{{ isset($general_settings) ? $general_settings->on_demand_start_service_time : old('on_demand_start_service_time') }}"
                                                required
                                                min="1"
                                                style="width: 30%; display: inline-block;"
                                            >
                                            <span>minutes of requested time.</span>

                                            <!-- REMOVE manual error span -->
                                            {{-- No need to manually add <span> for error here --}}
                                        </div>
                                        <span
                                            class="error">{{ $errors->first('on_demand_start_service_time') }}</span>
                                        <label id="on_demand_start_service_time-error" class="error" for="on_demand_start_service_time"></label>
                                    </div>

                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5>Wallet Module</h5>
                                </div>
                                <div class="card-block">
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Wallet Module :</label>
                                                <div class="col-sm-12">
                                                    <select class="js-example-basic-single col-sm-12 form-control" name="walletModule" id="walletModule">
                                                        <option style="padding-left: 8px;" value="0" <?php echo($general_settings->wallet_payment == 0) ? 'selected="selected"':"" ?>>Off</option>
                                                        <option value="1" <?php echo($general_settings->wallet_payment == 1) ? 'selected="selected"':"" ?>>On</option>
                                                    </select>
                                                    @if($errors->has('walletModule'))
                                                        <div class="error">{{ $errors->first('walletModule') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 showOnWallet">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label" id="auto_settle_wallet_label">Auto Settle Module:</label>
                                                <div class="col-sm-12">
                                                    <select class="js-example-basic-single col-sm-12 form-control" name="auto_settle_wallet" id="auto_settle_wallet">
                                                        <option style="padding-left: 8px;" value="0" <?php echo($general_settings->auto_settle_wallet == 0) ? 'selected="selected"':"" ?>>Off</option>
                                                        <option value="1" <?php echo($general_settings->auto_settle_wallet == 1) ? 'selected="selected"':"" ?>>On</option>
                                                    </select>
                                                    @if($errors->has('auto_settle_wallet'))
                                                        <div class="error">{{ $errors->first('auto_settle_wallet') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 showOnAutoSettle">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label" id="provider_min_amount_label">Minimum wallet Required Amount for Request (Provider):</label>
                                                <div class="col-sm-12">
                                                    <input type="text" class="form-control"  name="provider_min_amount"
                                                           id="provider_min_amount"
                                                           placeholder="Minimum wallet Amount"
                                                           value="{{ (isset($general_settings)) ? $general_settings->provider_min_amount : old('provider_min_amount') }}">
                                                    <span class="error">{{ $errors->first('provider_min_amount') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 showOnAutoSettle" id="min_cash">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label" id="min_cashout_label">Minimum Cashout:</label>
                                                <div class="col-sm-12">
                                                    <input type="number" class="form-control" name="min_cashout" min="0" id="min_cashout" placeholder="Minimum Cashout" value="{{ (isset($general_settings)) ? $general_settings->min_cashout : old('min_cashout') }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row showOnWallet">
                                        <div class="col-sm-6 showOnAutoSettle" id="max_cash">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label" id="max_cashout_label">Maximum Cashout:</label>
                                                <div class="col-sm-12">
                                                    <input type="number" class="form-control" name="max_cashout" min="0" id="max_cashout" placeholder="Maximum Cashout" value="{{ (isset($general_settings)) ? $general_settings->max_cashout : old('max_cashout') }}">
                                                    <span class="error max_cashout-error">{{ $errors->first('max_cashout') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- code for Payment Methods --}}
                            <div class="card">
                                <div class="card-header">
                                    <h5>Payment Methods</h5>
                                </div>
                                <div class="card-block">
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Cash<sup class="error">*</sup></label>
                                                <div class="col-sm-12">
                                                    <select type="text" class="form-control"
                                                            name="cash_payment"
                                                            required id="cash_payment">
                                                        <option
                                                            value="1" {{ (isset($general_settings)) && $general_settings->cash_payment == 1 ? "selected" : '' }}>
                                                            On
                                                        </option>
                                                        <option
                                                            value="0" {{ (isset($general_settings)) && $general_settings->cash_payment == 0 ? "selected" : '' }}>
                                                            Off
                                                        </option>
                                                    </select>
                                                    <span class="error">{{ $errors->first('cash_payment') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Card<sup class="error">*</sup></label>
                                                <div class="col-sm-12">
                                                    <select type="text" class="form-control"
                                                            name="card_payment"
                                                            required id="card_payment">
                                                        <option
                                                            value="1" {{ (isset($general_settings)) && $general_settings->card_payment == 1 ? "selected" : '' }}>
                                                            On
                                                        </option>
                                                        <option
                                                            value="0" {{ (isset($general_settings)) && $general_settings->card_payment == 0 ? "selected" : '' }}>
                                                            Off
                                                        </option>
                                                    </select>
                                                    <span class="error">{{ $errors->first('card_payment') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-block">
                                    <div class="form-group row">
                                        <div class="col-sm-10">
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
    </div>

@endsection
@section('page-js')
    <script src="{{ asset('assets/js/upload_image.js') }}"></script>
    <script src="{{ asset('assets/js/validation/jquery.validate.js')}}"></script>
    <script>

        //show auto settle module if wallet on
        $('#walletModule').on('change', function() {
            if(this.value == 0){
                $('#auto_settle_wallet').hide();
                $('#auto_settle_wallet').val(0);
                $('#auto_settle_wallet_label').hide();
                $('#provider_min_amount').hide();
                $('#provider_min_amount').val(0);
                $('#min_cashout').val(0);
                $('#max_cashout').val(0);
                $('#provider_min_amount_label').hide();
                $('#min_cashout_label').hide();
                $('#max_cashout_label').hide();
                $('#min_cashout').hide();
                $('#max_cashout').hide();
                $('.max_cashout-error').hide();
            }
            else{
                $('.showOnWallet').show();
                $('#auto_settle_wallet').show();
                $('#auto_settle_wallet_label').show();
                // show cash out min max on auto settle "on"
                $('#auto_settle_wallet').on('change',function (){
                    if(this.value == 0){
                        $('#min_cashout_label').hide();
                        $('#max_cashout_label').hide();
                        $('#min_cashout').hide();
                        $('#max_cashout').hide();
                    }else {
                        $('#provider_min_amount').show();
                        $('#provider_min_amount_label').show();
                        $('#min_cash').removeClass('showOnAutoSettle');
                        $('#max_cash').removeClass('showOnAutoSettle');
                        $('#min_cashout_label').show();
                        $('#max_cashout_label').show();
                        $('#min_cashout').show();
                        $('#max_cashout').show();
                    }
                })
            }
        });

        @if($general_settings->wallet_payment == 1)
        $('#auto_settle_wallet').show();
        $('#auto_settle_wallet_label').show();
        $('#provider_min_amount').show();
        $('#provider_min_amount_label').show();
        $('.showOnWallet').show();
        $('#min_cash').removeClass('showOnAutoSettle');
        $('#max_cash').removeClass('showOnAutoSettle');
        $('#min_cashout_label').show();
        $('#max_cashout_label').show();
        $('#min_cashout').show();
        $('#max_cashout').show();
        @else
        $('#auto_settle_wallet').hide();
        $('#auto_settle_wallet_label').hide();
        $('#provider_min_amount').hide();
        $('#provider_min_amount_label').hide();
        $('#min_cashout_label').hide();
        $('#max_cashout_label').hide();
        $('#min_cashout').hide();
        $('#max_cashout').hide();
        @endif

        @if($general_settings->auto_settle_wallet == 1)
        $('#max_cash').show();
        $('#min_cash').show();
        $('#max_cash').removeClass('showOnAutoSettle');
        $('#max_cash').removeClass('showOnAutoSettle');
        @else
        $('#provider_min_amount').hide();
        $('#provider_min_amount_label').hide();
        $('#min_cashout_label').hide();
        $('#max_cashout_label').hide();
        $('#min_cashout').hide();
        $('#max_cashout').hide();
        @endif

        // changes on show of auto_settle_wallet cash out min max
        $('#auto_settle_wallet').on('change',function (){
            if(this.value == 0){
                $('#min_cashout_label').hide();
                $('#max_cashout_label').hide();
                $('#provider_min_amount').hide();
                $('#provider_min_amount').val(0);
                $('#provider_min_amount_label').hide();
                $('#min_cashout').hide();
                $('#max_cashout').hide();
                $('#min_cashout').val(0);
                $('#max_cashout').val(0);
                $('.max_cashout-error').hide();

            }else {
                $('#min_cash').removeClass('showOnAutoSettle');
                $('#max_cash').removeClass('showOnAutoSettle');
                $('#provider_min_amount').show();
                $('#provider_min_amount_label').show();
                $('#min_cashout_label').show();
                $('#max_cashout_label').show();
                $('#min_cashout').show();
                $('#max_cashout').show();
            }
        })
    </script>

    <script type="text/javascript">
        $.validator.addMethod("greaterThanMin", function(value, element) {
            var minCashout = parseFloat($("#min_cashout").val());
            var maxCashout = parseFloat(value);
            return !isNaN(maxCashout) && !isNaN(minCashout) && maxCashout > minCashout;
        }, "Maximum Cashout must be greater than to Minimum Cashout.");


        //jquery Validations
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
        $("#main").validate({
            rules: {
                website_name: {
                    required : true,
                },
                // on_demand_start_service_time: {
                //     required: true,
                //     number: true,
                //     min: 1
                // },
                min_cashout:{
                    min : 0.1,
                },
                max_cashout:{
                    greaterThanMin: true
                },
                provider_min_amount:{
                    min : 0.1,
                },
            },
            messages: {
                min_cashout:{
                    min:"Please enter a number greater than 0."
                },
                provider_min_amount:{
                    min:"Please enter a number greater than 0."
                },
                // on_demand_start_service_time: {
                //   required: "Please enter start service before time",
                //   number: "Please enter numeric value.",
                //   min: "Start Service before time must be greater than 0.",
                //                 }
            },
            submitHandler: function(form) {
                $('.buttonloader').attr("disabled", true);
                $('.buttonloader').html("<i class='fa fa-spinner fa-spin'></i>");
                form.submit();
            },
            errorPlacement: function (error, element) {
                error.addClass('text-danger d-block mt-1'); // Bootstrap styling
                error.insertAfter(element); // 👈 Important: place after input
            }
        });

        $(document).ready(function () {
            $.uploadPreview({
                input_field: "#image-upload-1", // Default: .image-upload
                preview_box: "#image-preview-1", // Default: .image-preview
                label_field: "#image-label-1", // Default: .image-label
                label_default: "Choose Image", // Default: Choose File
                label_selected: "Change Image", // Default: Change File
                no_label: false // Default: false
            });
            $.uploadPreview({
                input_field: "#image-upload-2", // Default: .image-upload
                preview_box: "#image-preview-2", // Default: .image-preview
                label_field: "#image-label-2", // Default: .image-label
                label_default: "Choose Image", // Default: Choose File
                label_selected: "Change Image", // Default: Change File
                no_label: false // Default: false
            });
        });
    </script>
    {{--<script type="text/javascript">--}}
    {{--$(document).ready(function () {--}}
    {{--$('#discount').on('click', function () {--}}
    {{--var discount_value = $(this).val();--}}
    {{--if (discount_value == 1) {--}}
    {{--$(this).val(0);--}}
    {{--$(this).removeAttr("checked");--}}
    {{--$('.discount').hide();--}}
    {{--$('#discount_amount').removeAttr("required");--}}
    {{--$('#discount_type').removeAttr("required");--}}
    {{--}--}}
    {{--else {--}}
    {{--$(this).attr("checked", "checked");--}}
    {{--$(this).val(1);--}}
    {{--$('.discount').show();--}}
    {{--$('#discount_amount').attr("required", "required");--}}
    {{--$('#discount_type').attr("required", "required");--}}
    {{--}--}}
    {{--});--}}
    {{--$('#food_type').on('click', function () {--}}
    {{--var food_type = $(this).val();--}}
    {{--if (food_type == 1) {--}}
    {{--$(this).val(2);--}}
    {{--}--}}
    {{--else {--}}
    {{--$(this).val(1);--}}
    {{--}--}}
    {{--});--}}
    {{--});--}}
    {{--</script>--}}
@endsection

