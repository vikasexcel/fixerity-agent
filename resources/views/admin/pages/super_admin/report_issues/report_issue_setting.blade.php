@extends('admin.layout.super_admin')
@section('title')
    Report Issue Setting
@endsection
@section('page-css')
    <style>

    </style>
@endsection
@section('page-content')
    <div class="pcoded-content">
        <div class="other-service-horizontal-nav"></div>
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-list bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Report Issue Setting</h5>
                            <span>Edit Report Issue Setting</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <div class="page-body">
                        <form id="main" class="report_issue_setting" method="post" action="{{ route('post:admin:report_issue_setting') }}" enctype="multipart/form-data">
                            {{csrf_field() }}
                            <div class="row">
                                <div class="form-group col-sm-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Report Issue Chat History Delete Algorithm</h5>
                                        </div>
                                        <div class="card-block">
                                            <div class="row">
                                                <div class="form-group col-sm-10">
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Chat History delete:</label>
                                                        <div class="col-sm-8">
                                                            <select type="text" class="form-control report_chat_history_delete"
                                                                    name="report_chat_history_delete"
                                                                    required id="report_chat_history_delete">
                                                                <option value="1" {{ (isset($general_settings)) && $general_settings->report_chat_history_delete == 1 ? "selected" : '' }}>
                                                                    Yes
                                                                </option>
                                                                <option value="0" {{ (isset($general_settings)) && $general_settings->report_chat_history_delete == 0 ? "selected" : '' }}>
                                                                    No
                                                                </option>
                                                            </select>
                                                            <span class="error">{{ $errors->first('report_chat_history_delete') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- This is the section to show/hide -->
                                            <div class="row">
                                                <div class="form-group col-sm-10" id="chatDeletionDaysSection">
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">After how many days chat will be deleted after issue resolved:</label>
                                                        <div class="col-sm-8">
                                                            <input type="number" class="form-control"
                                                                   name="chat_deletion_days_after_issue_resolution"
                                                                   min="1"
                                                                   max="999"
                                                                   required step="1"
                                                                   id="chat_deletion_days_after_issue_resolution"
                                                                   placeholder="Chat deletion days after issue resolution"
                                                                   value="{{ (isset($general_settings)) ? $general_settings->chat_deletion_days_after_issue_resolution : old('chat_deletion_days_after_issue_resolution') }}">
                                                            <span class="error">{{ $errors->first('chat_deletion_days_after_issue_resolution') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                {{--  report issue general image    --}}
                                                <div class="form-group col-sm-10">
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">General Report Issue Image:</label>
                                                        <div class="col-sm-8">
                                                            <div class="col-sm-4">
                                                                <img id="upload-preview" src="{{ asset('/assets/images/report-issue/logo/'.$general_settings->general_report_issue_icon)}}" style="width: 50px; height: 50px">
                                                            </div>
                                                            <input type="file" class="form-control" name="general_report_issue_icon" id="general_report_issue_icon" @if(!isset($general_settings)) required @endif>
                                                            <span class="note">[Note: Upload only png,jpg,jpeg,webp icon max dimension 200*200]</span>
                                                            <span class="error">{{ $errors->first('general_report_issue_icon') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Min & Max Report Issue Image Upload Limit--}}
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Report Issue Image Upload Limit</h5>
                                        </div>
                                        <div class="card-block">
                                            <div class="row">
                                                <div class="form-group col-sm-6">
                                                    <label class="col-form-label">Min Report Issue Image Upload Limit:<sup class="error">*</sup></label>
                                                    <div>
                                                        <input type="number" class="form-control"
                                                               name="min_report_issue_image_upload"
                                                               id="min_report_issue_image_upload"
                                                               placeholder="Min Report Issue Image Upload Limit"
                                                               value="{{ (isset($general_settings)) ? $general_settings->min_report_issue_image_upload : old('min_report_issue_image_upload') }}">
                                                        <span class="error">{{ $errors->first('min_report_issue_image_upload') }}</span>
                                                    </div>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label class="col-form-label">Max Report Issue Image Upload Limit<sup class="error">*</sup></label>
                                                    <div>
                                                        <input type="number" class="form-control"
                                                               name="max_report_issue_image_upload"
                                                               required step="1"
                                                               id="max_report_issue_image_upload"
                                                               placeholder="Max Report Issue Image Upload Limit"
                                                               value="{{ (isset($general_settings)) ? $general_settings->max_report_issue_image_upload : old('max_report_issue_image_upload') }}">
                                                        <span class="error">{{ $errors->first('max_report_issue_image_upload') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <center>
                                                <button type="submit" class="btn btn-primary m-b-0 buttonloader">Save</button>
                                            </center>
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
    <!-- jquery validation js -->
    <script type="text/javascript" src="{{ asset('assets/js/validation/Admin/Super/custom-validate.js?v=0.3') }}"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            //script for After how many days chat will be deleted after issue resolved
            function toggleChatDeletionDays() {
                var chatDeleteOption = $('#report_chat_history_delete').val();
                if (chatDeleteOption == '1') {
                    $('#chatDeletionDaysSection').show(); // Show the section
                } else {
                    $('#chatDeletionDaysSection').hide(); // Hide the section
                }
            }

            // Call the function when the page loads
            toggleChatDeletionDays();

            // Call the function when the select box value changes
            $('#report_chat_history_delete').on('change', function() {
                toggleChatDeletionDays();
            });
        })
    </script>
    <script>
        //Image preview code
        if (window.File && window.FileList && window.FileReader) {
            $("#general_report_issue_icon").on("change", function(e) {
                var files = e.target.files,
                    filesLength = files.length;
                var j=0;
                for (var i = 0; i < filesLength; i++) {
                    var f = files[i]
                    var fileReader = new FileReader();
                    fileReader.onload = (function(e) {
                        var file = e.target;
                        $('#upload-preview').attr("src", e.target.result);
                    });
                    fileReader.readAsDataURL(f);
                }
            });
        } else {
            alert("Your browser doesn't support to File API")
        }
    </script>
    <script>
        /* start jquery validations for report issue setting */
        $(".report_issue_setting").validate({
            rules: {
                chat_deletion_days_after_issue_resolution: {
                    min : 1,
                    max : 999,
                },
                min_report_issue_image_upload:{
                    required : true,
                    min : 1
                },
                max_report_issue_image_upload:{
                    required : true,
                    min : 1
                }
            },
            submitHandler: function(form) {
                $('.buttonloader').attr("disabled", true);
                $('.buttonloader').html("<i class='fa fa-spinner fa-spin'></i>");
                form.submit();
            }
        });
        /* end jquery validations for report issue setting */
    </script>
@endsection

