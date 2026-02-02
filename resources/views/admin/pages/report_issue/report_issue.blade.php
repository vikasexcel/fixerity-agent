@extends('admin.layout.super_admin')
@section('title')
    Report Issue
@endsection
@section('page-css')
    <!-- Data Table Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('admin/datatable/css/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('admin/datatable/css/responsive.bootstrap4.min.css')}}">
    <!-- Switch Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('admin/custom/switch.css')}}">
    <!-- Custom Datatable Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('admin/datatable/css/custom.css')}}">
    <!-- Form Error css -->
    <link rel="stylesheet" type="text/css" href="{{asset('admin/custom/form_error.css')}}">
    <!-- Status css -->
    <link rel="stylesheet" type="text/css" href="{{asset('admin/custom/status.css')}}">
@endsection
@section('content')
    <div class="pcoded-content">
        <!-- Breadcrumb  Start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="fa fa-bug bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Report Issue</h5>
                            <span>Displaying all the records of Report Issue</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class=" breadcrumb breadcrumb-title">
                            <li class="breadcrumb-item"><a href="{{route('get:admin:dashboard')}}"><i class="feather icon-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{route('get:admin:report_issue')}}">Report Issue</a></li>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- Breadcrumb End -->
        <div class="pcoded-inner-content">
            <!-- Main-body start -->
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page-body start -->
                    <div class="page-body">
                        <!-- DOM/Jquery Table Start -->
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-9">
                                        <h5>Report Issue</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="card-block">
                                <div class="table-responsive dt-responsive">
                                    <table id="dom-jqry" class="stripe table" style="width: 100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Title</th>
                                            <th>Issue Date</th>
                                            <th>View</th>
                                            <th>Chat</th>
                                            <th>Status</th>
                                        </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div> <!-- Card End -->
                    </div><!-- Page-body End -->
                </div>
            </div><!-- Main-body End -->
        </div>
    </div>
@endsection
@section("page-js")
    <!-- data-table js -->
    <script src="{{asset('admin/datatable/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('admin/datatable/js/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{asset('admin/datatable/js/dataTables.responsive.min.js')}}"></script>
    <!--Used when datatable is formed using loop instead of ajax call-->
    {{--<script src="{{asset('admin/datatable/js/responsive-custom.js')}}"></script>--}}
    <!-- Confirm Delete Sweetalert -->
    <script type="text/javascript" src="{{asset('admin/sweetalert/sweetalert.js')}}"></script>
    <!-- Script To fetch User Via Ajax Call -->
    <script type="text/javascript">
        $(document).ready(function(){
            // DataTable
            var table = $('#dom-jqry').DataTable({
                responsive:true,
                processing: true,
                serverSide: true,
                pageLength: 20,
                lengthMenu: [10, 20, 50, 100],
                ajax: "{{route('get:admin:get_report_issue')}}",
                columns: [
                    { data: 'id' },
                    { data: 'first_name'},
                    { data: 'last_name'},
                    { data: 'title' },
                    { data: 'issue_date' },
                    { data: 'view' },
                    { data: 'chat', sortable:false },
                    { data: 'status' },
                    { data: 'created_at' },
                ],
                "columnDefs": [
                    { "orderable": false, "targets": [0,5,6,7] },
                    { "searchable": false, "targets": [0,3,4,5,6] },
                    { 'visible': false, 'targets': [8] },//hide the column
                ],
                "order": [[8, 'desc']],
            });
        });
    </script>
    <!-- Confirm Status Sweetalert -->
    <script type="text/javascript">
        $(document).on('click','#changeStatus',function(event) {
            event.preventDefault();
            swal({
                title: "Are you sure you want to resolved this issue?",
                text: "If you change this, it will be saved.",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
                .then((willDelete) => {
                    if (willDelete) {
                        var id = $(this).data('id');
                        $.ajax({
                            type: "GET",
                            dataType: "json",
                            url: '{{route('get:admin:update_report_issue_status')}}',
                            data: {'id': id},
                            success: function(result){
                                if (result.success == true) {
                                    var table = $('#dom-jqry').DataTable();
                                    table.draw();
                                    swal({
                                        title: "Success",
                                        text: "Report Issue status has been updated successfully!",
                                        icon: "success",
                                        type: "success"
                                    })
                                }
                                else {
                                    swal({
                                        title: "Info",
                                        text: "Something went wrong!",
                                        icon: "info",
                                        type: "info"
                                    })
                                }
                            },
                        });
                    }
                    else {
                        swal({
                            title: "Cancelled",
                            text: "Report Issue status remains as it is!",
                            icon: "error",
                            type: "error"
                        })
                    }
                });
        });
    </script>
@endsection
