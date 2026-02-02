@extends('admin.layout.super_admin')
@section('title')
    Cashout List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <style>
        table.dataTable.dtr-inline.collapsed > tbody > tr > td:first-child:before, table.dataTable.dtr-inline.collapsed > tbody > tr > th:first-child:before {
            background: #55d090;
        }

        .page-item.active .page-link {
            background: #55d090;
            border-color: #55d090;
        }

        /*datatable td link*/
        .cashout-status a {
            color: #55d090;
            font-weight: bold;
            font-size: 14px;
        }

        .cashout-status i {
            display: inline-block;
            font-size: 20px;
        }

        .icon-list-demo i {
            height: auto;
            line-height: 10px;
            border: none;
            margin-right: 5px;
            color: #55d090;
            width: unset;
        }

        .page-link {
            color: #55d090;
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-list bg-c-green"></i>
                        <div class="d-inline">
                            <h5>Cashout List</h5>
                            <span>All Cashout List
                            </span>
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
                        <div class="card">
                            <div class="card-header">
                                <h5>Cashout List</h5>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="cashouts" class="table table-striped table-bordered nowrap" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Provider Name</th>
                                            <th>Amount</th>
                                            <th>Bank Name</th>
                                            <th>Account Number</th>
                                            <th>Payment Email</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
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

@endsection
@section('page-js')
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}" type="text/javascript"></script>
    <script>
        $(document).ready(function () {
            //fetch all the records of cash out payments
            var table = $('#cashouts').DataTable({
                processing: true,
                language : {
                    loadingRecords : '&nbsp;',
                    processing: "<img src='{{ asset('/assets/images/website-logo-icon/loader.gif')}}' style='width: 50px; height: 50px;' />",
                },
                serverSide: true,
                pageLength: 25,
                responsive: true,
                ajax: {
                    url: "{{route('get:admin:provider_cash_out_list_new')}}",
                },
                columns: [
                    {data: 'no','sortable': false},
                    {data: 'user_name'},
                    {data: 'amount'},
                    {data: 'bank_name'},
                    {data: 'account_number'},
                    {data: 'payment_email'},
                    {data: 'status', 'sortable': false},
                    {data: 'actions', 'sortable': false},
                ]
            });
            //approve the cash out payment
            $(document).on('click', '.approve', function (e) {
                // e.preventDefault();
                var id = $(this).attr('id');
                swal({
                        title: "Approve Cashout?",
                        text: "if press yes then Cashout will be approved!",
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
                                url: '{{ route("get:admin:provider_update_cash_out_status") }}',
                                data: {id: id,request_for: 1},
                                success: function (result) {
                                    if (result.success == true) {
                                        var new_id = "#hide_" + id;
                                        swal("Success", "Cashout approve successfully", "success");
                                        $(new_id).hide();
                                        $('#status_'+id).removeClass();
                                        $('#status_'+id).addClass('approved');
                                        $('#status_'+id).html('approved');
                                        $('#approve_remove_'+id).empty();
                                    } else {
                                        swal("Warning", result.message, "warning");
                                        console.log(result);
                                    }
                                }
                            })
                        } else {
                            swal("Cancelled", "Cashout status not change", "error");
                        }
                    });
            });
            //reject the cash out payment
            $(document).on('click', '.reject', function (e) {
                // e.preventDefault();
                var id = $(this).attr('id');
                swal({
                        title: "Reject Cashout?",
                        text: "if press yes then Cashout will be rejected!",
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
                                url: '{{ route("get:admin:provider_update_cash_out_status") }}',
                                data: {id: id,request_for: 2},
                                success: function (result) {
                                    if (result.success == true) {
                                        var new_id = "#hide_" + id;
                                        swal("Success", "cashout reject successfully", "success");
                                        $(new_id).hide();
                                        $('#status_'+id).removeClass();
                                        $('#status_'+id).addClass('rejected');
                                        $('#status_'+id).html('rejected');
                                        $('#approve_remove_'+id).empty();
                                        $('#reject_remove_'+id).empty();
                                    } else {
                                        swal("Warning", result.message, "warning");
                                        console.log(result);
                                    }
                                }
                            })
                        } else {
                            swal("Cancelled", "Cashout status not change", "error");
                        }
                    });
            });
        });
    </script>
@endsection
