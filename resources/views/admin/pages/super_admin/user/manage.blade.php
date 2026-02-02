@extends('admin.layout.super_admin')
@section('title')
    Customer List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <!-- Data Table Excel Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/responsive/buttons.dataTables.min.css?v=0.1')}}">
    <style>
        /* Vehicle type styles for the modal */
        .md-perspective,
        .md-perspective body {
            height: 100%;
            overflow: hidden;
        }
        .md-perspective body {
            background: #222;
            -webkit-perspective: 600px;
            -moz-perspective: 600px;
            perspective: 600px;
        }
        .md-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            /*width: 50%;*/
            width: 30%;
            max-width: 630px;
            min-width: 300px;
            height: auto;
            z-index: 2000;
            visibility: hidden;
            -webkit-backface-visibility: hidden;
            -moz-backface-visibility: hidden;
            backface-visibility: hidden;
            -webkit-transform: translateX(-50%) translateY(-50%);
            -moz-transform: translateX(-50%) translateY(-50%);
            -ms-transform: translateX(-50%) translateY(-50%);
            transform: translateX(-50%) translateY(-50%);
        }
        .md-show {
            visibility: visible;
        }
        .md-overlay {
            position: fixed;
            width: 100%;
            height: 100%;
            visibility: hidden;
            top: 0;
            left: 0;
            z-index: 1000;
            opacity: 0;
            background: rgba(55, 58, 60, 0.65);
            -webkit-transition: all 0.3s;
            -moz-transition: all 0.3s;
            transition: all 0.3s;
        }
        .md-show ~ .md-overlay {
            opacity: 1;
            visibility: visible;
        }
        /* Content styles */
        .md-content {
            color: #666666;
            background: #fff;
            position: relative;
            border-radius: 3px;
            margin: 0 auto;
        }
        .md-content h3 {
            color: #fff;
            margin: 0;
            /*padding: 0.4em;*/
            padding: 0.6em 0.4em 0.6em 1em;
            text-align: left;
            font-weight: 400;
            font-size: 1.5em;
            opacity: 0.8;
            border-radius: 3px 3px 0 0;
        }
        .md-content > .wrapper {
            padding: 15px 25px 30px 25px;
            margin: 0;
            font-size: 1em;
        }
        /* Individual modal styles with animations/transitions */
        .md-effect-1 .md-content {
            -webkit-transform: scale(0.7);
            -moz-transform: scale(0.7);
            -ms-transform: scale(0.7);
            transform: scale(0.7);
            opacity: 0;
            -webkit-transition: all 0.3s;
            -moz-transition: all 0.3s;
            transition: all 0.3s;
        }
        .md-show.md-effect-1 .md-content {
            -webkit-transform: scale(1);
            -moz-transform: scale(1);
            -ms-transform: scale(1);
            transform: scale(1);
            opacity: 1;
        }
        .md-trigger:hover {
            color: #64b0f2;
            cursor: pointer;
        }
        .md-trigger img:hover {
            opacity: 0.7;
            cursor: pointer;
        }

        .btn_model_send {
            /*background: #6f09f5 !important;*/
            min-width: unset !important;
            padding: 5px 18px !important;
        }

        .btn_model_close {
            min-width: unset !important;
            padding: 5px 18px !important;
        }
        .pass{
            color: #f5090a;
        }
        .pass:focus, .pass:hover {
            text-decoration: none;
            color: #4099ff
        }
        .error {
            color: red;
            font-weight: 500;
        }

        .text-model {
            margin-bottom: 10px;
        }
        .approve, .reject {
            cursor: pointer;
        }
        @if(isset($status) && $status==2 || isset($status) && $status==3)
            .toggle input[type="checkbox"]:checked + .button-indecator:before {
            color: red;
        }
        @endif

        #cover-spin   {
            position:fixed;
            width:100%;
            left:0;right:0;top:0;bottom:0;
            background-color: rgba(255, 255, 255, 0.7);
            z-index:9999;
            /*display:none;*/
        }
        #cover-spin::after {
            content:'';
            display:block;
            position:absolute;
            left:48%;
            top:40%;
            width:50px;
            height:50px;
            border-style:solid;
            border-color:black;
            border-top-color:transparent;
            border-width: 4px;
            border-radius:50%;
            -webkit-animation: spin .8s linear infinite;
            animation: spin .8s linear infinite;
        }
        @-webkit-keyframes spin {
            from {-webkit-transform:rotate(0deg);}
            to {-webkit-transform:rotate(360deg);}
        }

        @keyframes spin {
            from {transform:rotate(0deg);}
            to {transform:rotate(360deg);}
        }
        .top {
            display: flex;
        }
        .dataTables_filter {
            margin-left: auto;
        }
        .dt-buttons {
            margin-left: 1em;
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5> Customer List</h5>
                                    <span>All Customer List</span>
                                </div>
                            </div>
                        </div>
                        {{--<div class="col-lg-4">--}}
                        {{--<a href="{{ route('get:admin:user_list') }}"--}}
                        {{--class="btn btn-primary m-b-0 btn-right render_link">Back</a>--}}
                        {{--</div>--}}
                    </div>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>Customer List</h5>
                                <a href="{{ route('get:admin:add_user') }}"
                                   class="btn btn-primary m-b-0 btn-right render_link">Add Customer</a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="users" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Customer Name</th>
                                            <th>Email</th>
                                            <th>Contact No.</th>
                                            @if($general_settings->wallet_payment  == 1)
                                            <th>Wallet Balance</th>
                                            @endif
                                            <th>Status</th>
                                            <th>App Version</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        {{--@if(isset($user_list))
                                            @foreach($user_list as $key => $user )
                                                <tr id="delete_customer_{{$user->id}}">
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>
                                                        <a href="{{ route('post:admin:customer_order_list',$user->id) }}">
                                                            <h6 style="color: #4099ff">
                                                                <span data-toggle="tooltip" data-placement="top"
                                                                      title="Order List">{{ ucwords(strtolower($user->first_name." ".$user->last_name))}}</span>
                                                            </h6>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        {{ $user->email }}
                                                    </td>
                                                    <td>
                                                        {{ $user->country_code.$user->contact_number }}
                                                    </td>
                                                    <td>{{ isset($user_wallet_balance) ? ( isset($user_wallet_balance[$user->id]) ? $user_wallet_balance[$user->id] : 0) : 0}}<a class="render_link"
                                                           href="{{ route('post:admin:customer_wallet_transaction',$user->id) }}">
                                                            <img src="{{ asset('/assets/images/template-images/wallet-history3.png') }}"
                                                                 style="width:22px; height: 22px; margin-left: 12px;"
                                                                 data-toggle="tooltip"
                                                                 data-placement="top" title="Wallet Transaction">
                                                        </a>
                                                    </td>
                                                    <td class="icon-url-link">
                                                        <a href="{{ route('get:admin:user_review_list',$user->id) }}"
                                                           target="_blank">
                                                            <div class="data-table-main icon-list-demo">
                                                                <i class="fa fa-star"></i>{{ $user->rating }}
                                                            </div>
                                                        </a>
                                                    </td>
                                                    <td>
                                                <span class="toggle">
                                                    <label>
                                                        <input name="status"
                                                               class="form-control user"
                                                               id="user_id_{{$user->id}}"
                                                               user_id="{{$user->id}}"
                                                               user_status="{{$user->status}}"
                                                               type="checkbox" {{ ("1" == $user->status) ? 'checked' : '' }}>
                                                        <span class="button-indecator" data-toggle="tooltip"
                                                              data-placement="top"
                                                              id="title_status_{{$user->id}}"
                                                              title="{{ ("1" == $user->status) ? 'Active' : 'InActive' }}"></span>
                                                    </label>
                                                </span>
                                                    </td>
                                                    <td class="action">
                                                        <a class="render_link"
                                                           href="{{ route('get:admin:edit_user',$user->id) }}">
                                                            <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                 style="width:20px; height: 20px;" data-toggle="tooltip"
                                                                 data-placement="top" title="Edit">
                                                        </a>
                                                        <a class="delete" userid="{{ $user->id }}">
                                                            <img src="{{ asset('/assets/images/template-images/remove-1.png') }}"
                                                                 style="width:20px; height: 20px;" data-toggle="tooltip"
                                                                 data-placement="top" title="Delete">
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif--}}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Page body end -->
            </div>
        </div>
    </div>
    <div class="md-modal md-effect-1" id="modal-2">
        <div class="md-content">
            <h3 class="bg-c-blue">Change Password</h3>
            <div class="wrapper">
                <div id="cover-spin" style="display: none"></div>
                <form method="get" id="change_password_form">
                    <p id="send_message" class="text-success font-weight-bold"></p>
                    <input type="hidden" class="form-control" name="user_id" id="user_id" placeholder="User id" value="">
                    <div class="form-group">
                        <label class="col-form-label">Password:</label>
                        <input type="password" name="password" class="form-control border-r-top-left-right" required id="password" value="{{ old('password') }}" placeholder="Enter new password">
                    </div>
                    <div class="form-group">
                        <label class="col-form-label">Confirm Password:</label>
                        <input type="password" name="confirm_password" class="form-control border-r-top-left-right" required id="confirm_password" value="{{ old('forgot_confirm_password') }}" placeholder="Confirm Password">
                    </div>

                    <div class="form-group">
                        <p id="fail_message" class="text-danger"></p>
                    </div>
                    <button type="submit" class="btn btn-primary btn_model_send">Submit</button>
                    <button type="button" class="btn btn-login btn_model_close md-close">Close</button>

                </form>
            </div>
        </div>
    </div>
    <div class="md-modal md-effect-1" id="modal-3">
        <div class="md-content">
            <h3 class="bg-c-blue">Wallet</h3>
            <div class="wrapper">
                <div class="cover-spin" style="display: none"></div>
                <form method="get" id="wallet_transaction_form">
                    <p id="send_message_1" class="text-success font-weight-bold"></p>
                    <input type="hidden" class="form-control" name="user_id" id="wallet_user_id" placeholder="User id" value="">
                    <div class="form-group">
                        <label class="col-form-label">Wallet Amount:</label>
                        <input type="text" name="wallet_amount" class="form-control border-r-top-left-right" required id="wallet_amount" value="{{ old('wallet_amount') }}" placeholder="Enter Wallet Amount">
                    </div>
                    <div class="form-group">
                        <label class="col-form-label">Choose Option (Add or Deduct Money):</label>
                        <select name="choose_option" class="form-control border-r-top-left-right" required>
                            <option value="1">Add Money</option>
                            <option value="2">Deduct Money</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <p id="fail_message_1" class="text-danger"></p>
                    </div>
                    <button type="submit" class="btn btn-primary btn_model_send_1">Submit</button>
                    <button type="button" class="btn btn-login btn_model_close_1 md-close-1">Close</button>

                </form>
            </div>
        </div>
    </div>
    <div class="md-overlay"></div>

@endsection
@section('page-js')
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>
    <!-- CDN for the Excel file -->
    <script src="{{asset('assets/js/responsive/dataTables.buttons.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/jszip.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/buttons.html5.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/buttons.print.min.js')}}"></script>
    <script src="{{asset('assets/js/datatablecommonfunction.js')}}"></script>
<!--    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>-->



    <script>
        $(document).ready(function () {
            var table = $('#users').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                responsive: true,
                ajax: "{{route('get:admin:user_list_new')}}",
                columns: [
                    {data: 'id' },
                    {data: 'first_name'},
                    {data: 'email'},
                    {data: 'contact_number'},
                    @if($general_settings->wallet_payment == 1)
                    {data: 'wallet_balance'},
                    @endif
                    {data: 'status', 'sortable': false},
                    {data: 'user_app_version', 'sortable': false},
                    {data: 'action', 'sortable': false},
                ],
                "order": [[ 0, "desc" ]],
                dom: '<"top"lBf>rt<"bottom"pi><"clear">',
                buttons: [{
                    extend: 'excel',
                    exportOptions: {
                        columns: ':not(.notexport)',
                        modifier: {
                            page: 'all',
                        },
                    },
                    text: 'Download Excel',
                    "action": newexportaction,
                }],
            });
        });

        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('userid');
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this data!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "No, cancel!",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $.ajax({
                            type: 'get',
                            url: '{{ route('get:admin:delete_user') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var table = $('#users').DataTable();
                                    table.ajax.reload( null, false );
                                    // RemovetableRow.remove().draw();
                                    // swal("Success", result.message, "success");
                                    // location.reload();
                                    var new_id = "#delete_customer_" + id;
                                    swal("Success", "customer remove successfully", "success");
                                    $(new_id).hide();
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        swal("Cancelled", "Your Data is safe :)", "error");
                    }
                });
        });
        $(document).on('click', '.user', function (e) {
            e.preventDefault();
            var id = $(this).attr('user_id');
            var status = $(this).attr('user_status');
            var txt, title;
            if (status == 1) {
                title = "Disable user?";
                txt = "if press yes then disable user!";
            } else {
                title = "Enable user?";
                txt = "if press yes then enable user!";
            }
            swal({
                    title: title,
                    text: txt,
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes",
                    cancelButtonText: "No",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $.ajax({
                            type: 'get',
                            url: '{{ route("get:ajax:admin:update_user_status") }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var user_id_ = '#user_id_' + id;
                                    var title_status = '#title_status_' + id;
                                    if (result.status == 1) {
                                        $(user_id_).prop("checked", true);
                                        $(user_id_).attr("user_status", 1);
                                        // $(title_status).attr("title", "Active");
                                        swal("Success", "Enable User successfully", "success");
                                    } else {
                                        $(user_id_).prop("checked", false);
                                        $(user_id_).attr("user_status", 0);
                                        // $(title_status).attr("title", "InActive");
                                        swal("Success", "Disable User successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "User is Enable", "error");
                        } else {
                            swal("Cancelled", "User is Disable", "error");
                        }
                    }
                });
        });
    </script>

    {{--Model Script type detials--}}
    <script src="{{ asset('assets/js/classie.js')}}" type="text/javascript"></script>
    <script>
        $(document).ready(function (){

            $(document).on("click",".md-trigger",function (){
                var overlay = document.querySelector('.md-overlay');
                var data_modal = $(this).attr('data-modal');
                var userid = $(this).attr('userid');
                $("#user_id").val(userid);
                var modal = document.querySelector('#modal-2');
                close = modal.querySelector('.md-close');
                classie.add(modal, 'md-show');
                $("#send_message").hide();
                $("#change_password_form").validate().resetForm();
            });
            $(document).on("click",".md-close",function (){
                var modal = document.querySelector('#modal-2');
                $("#change_password_form")[0].reset();
                classie.remove(modal, 'md-show');
                $("#send_message").hide();
                $('#fail_message').text("");
                $("#fail_message").show();
            })
            $(document).on("click",".md-trigger-1",function (){
                var overlay = document.querySelector('.md-overlay');
                var data_modal = $(this).attr('data-modal');
                var userid = $(this).attr('userid');
                $("#wallet_user_id").val(userid);
                var modal = document.querySelector('#modal-3');
                close = modal.querySelector('.md-close-1');
                classie.add(modal, 'md-show');
                $("#wallet_amount").val();
                $("#send_message_1").hide();
                $("#wallet_transaction_form").validate().resetForm();
            });
            $(document).on("click",".md-close-1",function (){
                var modal = document.querySelector('#modal-3');
                $("#wallet_transaction_form")[0].reset();
                classie.remove(modal, 'md-show');
                $("#wallet_amount").val('');
                $("#send_message_1").hide();
                $("label.error").hide();
                $(".error").removeClass("error");
            })

        });
    </script>

    <script rel="stylesheet" src="{{ asset('assets/js/validation/jquery.validate.js')}}"></script>
    <script type="text/javascript">
        $("#change_password_form").validate({
            rules: {
                password: {
                    required : true,
                    minlength:6,
                    maxlength:16,
                },
                confirm_password: {
                    required : true,
                    equalTo : "#password"
                },
            },
            messages: {
                password: {
                    required :"Password field is required",
                },
                confirm_password: {
                    required :"Confirm Password field is required",
                    equalTo : "Password and confirm password not match!"
                },
            },
            submitHandler: function (form) {
                var form_data = $("#change_password_form").serialize();
                $.ajax({
                    type: 'get',
                    url: '{{ route('get:admin:user_change_password') }}',
                    data: form_data,
                    async: false,
                    cache: false,
                    success: function (result) {
                        $("#cover-spin").css('display', "block");
                        setTimeout(function () {
                            if (result.success == true) {
                                $('#fail_message').text("");
                                $("#fail_message").hide();

                                $("#send_message").text("");
                                $("#send_message").show();
                                $('#send_message').text(result.message);

                                var modal = document.querySelector('#modal-2');
                                classie.remove(modal, 'md-show');

                                $('#password').val("");
                                $('#confirm_password').val("");

                                $("#cover-spin").css('display', "none");
                                // location.reload();
                            } else {
                                $("#send_message").text("");
                                $("#send_message").hide();

                                $('#fail_message').text("");
                                $("#fail_message").show();
                                $('#fail_message').text(result.message);
                                $("#cover-spin").css('display', "none");
                            }
                        }, 900);
                        // $("#change_password_form").reset();
                    }
                });
            }
        });
        $("#wallet_transaction_form").validate({
            rules: {
                wallet_amount: {
                    required : true,
                    number: true,
                    min : 1
                },
                choose_option: {
                    required : true,
                },
            },
            messages: {
                wallet_amount: {
                    required :"Wallet Amount field is required",
                    number: "Please enter valid amount field is required",
                },
                choose_option: {
                    required :"Choose Option field is required",
                },
            },
            submitHandler: function(form) {
                var form_data = $("#wallet_transaction_form").serialize();
                $.ajax({
                    type: 'get',
                    url: '{{ route('get:admin:update_customer_wallet_transaction') }}',
                    data: form_data,
                    async:false,
                    cache:false,
                    success: function (result) {
                        $(".cover-spin").css('display',"block");
                        setTimeout(function (){
                            if (result.success == true) {
                                $('#fail_message_1').text("");
                                $("#fail_message_1").hide();

                                $("#send_message_1").text("");
                                $("#send_message_1").show();
                                $('#send_message_1').text(result.message);

                                $('#wallet_amount').val("");
                                $('#choose_option').val("");

                                $(".cover-spin").css('display',"none");
                                var modal = document.querySelector('#modal-3');
                                classie.remove(modal, 'md-show');
                                $('#change_wallet_'+result.user_id).text(result.last_amount);
                                table.ajax.reload();
                                // location.reload();
                            } else {
                                $("#send_message_1").text("");
                                $("#send_message_1").hide();

                                $('#fail_message_1').text("");
                                $("#fail_message_1").show();
                                $('#fail_message_1').text(result.message);
                                $(".cover-spin").css('display',"none");
                            }
                        },900);
                    }
                });
            }
        });
    </script>
@endsection

