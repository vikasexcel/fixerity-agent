@extends('admin.layout.super_admin')
@section('title')
    App Version Setting
@endsection
@section('page-css')
    <style>
        /*checkbox style*/
        .border-checkbox-section .border-checkbox-group .border-checkbox-label {
            height: 7px;
            padding-left: 30px;
            margin-right: 7px;
        }
        .border-checkbox-section .border-checkbox-group {
            margin-right: 15px;
        }
        .border-checkbox-section .border-checkbox-group .checklbl {
            height: 0;
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
                            <h5>App Version Setting</h5>
                            <span>@if(!isset($general_settings)) Add @else Edit @endif App Version Setting</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <div class="page-body">
                        <form id="main" method="post" action="{{route('post:admin:update_app_version_setting')}}" enctype="multipart/form-data">
                            {{csrf_field() }}

                            {{--User App Setting--}}
                            <div class="card">
                                <div class="card-header">
                                    <h5>User App Version Setting</h5>
                                </div>
                                <div class="card-block">
                                    {{--android flutter user app--}}
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Android Flutter User App Current Version:</label>
                                                <div class="col-sm-12">
                                                    <select type="text" class="form-control" name="android_flutter_user_app_version" id="android_flutter_user_app_version">
                                                        <option value="" disabled selected>Select Android Flutter User App Current Version</option>
                                                        @if( (isset($android_flutter_user_app_version_list)) && $android_flutter_user_app_version_list != Null)
                                                            @foreach($android_flutter_user_app_version_list as $key => $android_flutter_user_app_version)
                                                                <option value="{{ $android_flutter_user_app_version->id }}" {{ ( ($android_flutter_user_app_version->is_selected == 1) ? "selected" : "") }}>{{ $android_flutter_user_app_version->version_name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="error">{{ $errors->first('android_flutter_user_app_version') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Do you want?</label>
                                                <div class="col-sm-12">
                                                    <div class="border-checkbox-section">
                                                        <div class="border-checkbox-group border-checkbox-group-primary">
                                                            <input name="update_forcefully_android_flutter_user_app"
                                                                   class="border-checkbox"
                                                                   @if((isset($is_android_flutter_user_app_version_check)) && $is_android_flutter_user_app_version_check > 0 )
                                                                        checked
                                                                   @endif
                                                                   type="checkbox" id="update_forcefully_android_flutter_user_app">
                                                            <label class="border-checkbox-label" for="update_forcefully_android_flutter_user_app"> </label>
                                                            <span>Update Forcefully Android Flutter User App?</span>
                                                        </div>
                                                        <span class="error">{{ $errors->first('update_forcefully_android_flutter_user_app') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{--ios flutter user app--}}
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Ios Flutter User App Current Version:</label>
                                                <div class="col-sm-12">
                                                    <select type="text" class="form-control" name="ios_flutter_user_app_version" id="ios_flutter_user_app_version">
                                                        <option value="" disabled selected>Select Ios Flutter User App Current Version</option>
                                                        @if( (isset($ios_flutter_user_app_version_list)) && $ios_flutter_user_app_version_list != Null)
                                                            @foreach($ios_flutter_user_app_version_list as $key => $ios_flutter_user_app_version)
                                                                <option value="{{ $ios_flutter_user_app_version->id }}" {{ ( ($ios_flutter_user_app_version->is_selected == 1) ? "selected" : "") }}>{{ $ios_flutter_user_app_version->version_name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="error">{{ $errors->first('ios_flutter_user_app_version') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Do you want?</label>
                                                <div class="col-sm-12">
                                                    <div class="border-checkbox-section">
                                                        <div class="border-checkbox-group border-checkbox-group-primary">
                                                            <input name="update_forcefully_ios_flutter_user_app"
                                                                   class="border-checkbox"
                                                                   @if((isset($is_ios_flutter_user_app_version_check)) && $is_ios_flutter_user_app_version_check > 0 )
                                                                        checked
                                                                   @endif
                                                                   type="checkbox" id="update_forcefully_ios_flutter_user_app">
                                                            <label class="border-checkbox-label" for="update_forcefully_ios_flutter_user_app"> </label>
                                                            <span>Update Forcefully Ios Flutter User App?</span>
                                                        </div>
                                                        <span class="error">{{ $errors->first('update_forcefully_ios_flutter_user_app') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{--Provider App Setting--}}
                            <div class="card">
                                <div class="card-header">
                                    <h5>Provider App Version Setting</h5>
                                </div>
                                <div class="card-block">
                                    {{--android flutter provider app--}}
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Android Flutter Provider App Current Version:</label>
                                                <div class="col-sm-12">
                                                    <select type="text" class="form-control" name="android_flutter_provider_app_version" id="android_flutter_provider_app_version">
                                                        <option value="" disabled selected>Select Android Flutter Provider App Current Version</option>
                                                        @if( (isset($android_flutter_provider_app_version_list)) && $android_flutter_provider_app_version_list != Null)
                                                            @foreach($android_flutter_provider_app_version_list as $key => $android_flutter_provider_app_version)
                                                                <option value="{{ $android_flutter_provider_app_version->id }}" {{ ( ($android_flutter_provider_app_version->is_selected == 1) ? "selected" : "") }}>{{ $android_flutter_provider_app_version->version_name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="error">{{ $errors->first('android_flutter_provider_app_version') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Do you want?</label>
                                                <div class="col-sm-12">
                                                    <div class="border-checkbox-section">
                                                        <div class="border-checkbox-group border-checkbox-group-primary">
                                                            <input name="update_forcefully_android_flutter_provider_app" class="border-checkbox"
                                                                   @if((isset($is_android_flutter_provider_app_version_check)) && $is_android_flutter_provider_app_version_check > 0 )
                                                                   checked
                                                                   @endif
                                                                   type="checkbox" id="update_forcefully_android_flutter_provider_app">
                                                            <label class="border-checkbox-label" for="update_forcefully_android_flutter_provider_app"> </label>
                                                            <span>Update Forcefully Android Flutter Provider App?</span>
                                                        </div>
                                                        <span class="error">{{ $errors->first('update_forcefully_android_flutter_provider_app') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{--ios flutter provider app--}}
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Ios Flutter Provider App Current Version:</label>
                                                <div class="col-sm-12">
                                                    <select type="text" class="form-control" name="ios_flutter_provider_app_version" id="ios_flutter_provider_app_version">
                                                        <option value="" disabled selected>Select Ios Flutter Provider App Current Version</option>
                                                        @if( (isset($ios_flutter_provider_app_version_list)) && $ios_flutter_provider_app_version_list != Null)
                                                            @foreach($ios_flutter_provider_app_version_list as $key => $ios_flutter_provider_app_version)
                                                                <option value="{{ $ios_flutter_provider_app_version->id }}" {{ ( ($ios_flutter_provider_app_version->is_selected == 1) ? "selected" : "") }}>{{ $ios_flutter_provider_app_version->version_name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="error">{{ $errors->first('ios_flutter_provider_app_version') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label class="col-sm-12 col-form-label">Do you want?</label>
                                                <div class="col-sm-12">
                                                    <div class="border-checkbox-section">
                                                        <div class="border-checkbox-group border-checkbox-group-primary">
                                                            <input name="update_forcefully_ios_flutter_provider_app"
                                                                   class="border-checkbox"
                                                                   @if((isset($is_ios_flutter_provider_app_version_check)) && $is_ios_flutter_provider_app_version_check > 0 )
                                                                   checked
                                                                   @endif
                                                                   type="checkbox" id="update_forcefully_ios_flutter_provider_app">
                                                            <label class="border-checkbox-label" for="update_forcefully_ios_flutter_provider_app"> </label>
                                                            <span>Update Forcefully Ios Flutter Provider App?</span>
                                                        </div>
                                                        <span class="error">{{ $errors->first('update_forcefully_ios_flutter_provider_app') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="card">
                                <div class="card-block">
                                    <div class="col-sm-10">
                                        <button type="submit" class="btn btn-primary m-b-0 button_loader">Save</button>
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
    <script>
        // Disable button after form submission
        $('#main').on('submit', function() {
            $('.button_loader').attr('disabled', true);  // Disable the button
        });
    </script>
@endsection

