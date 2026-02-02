@extends('admin.layout.super_admin')
@section('title')
    Report Details
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
    <style>
        .Unresolved,.Resolved {
            padding: 7px 5px !important;
            margin-left: 12px;
            text-align: center
        }
    </style>
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
                            <span>Displaying all the Details of Report Issue</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class=" breadcrumb breadcrumb-title">
                            <li class="breadcrumb-item"><a href="{{route('get:admin:dashboard')}}"><i class="feather icon-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{route('get:admin:report_issue')}}">Report Issue</a></li>
                            <li class="breadcrumb-item"><a href="{{route('get:admin:detailed_report',$id)}}">Details of Issue</a></li>
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
                                        <h5>Issue Details of {{ $user_name }}</h5>
                                    </div>
                                    <div class="col-md-3" style="text-align:right">
                                        <a @if(isset($report_issue) && $report_issue->status == 0) href="{{route('get:admin:update_report_issue_status')}}" @endif   id="changeStatus" data-id="{{$id}}" class="btn btn-primary" style="@if(isset($report_issue) && $report_issue->status == 1)display:none @endif" >Resolve this issue</a>
                                    </div>
                                </div>
                            </div>

                            <div class="card-block">
                                <div class="main">
                                    <div class="row">
                                         <div class="col-sm-12">
                                             <label class="col-sm-6 col-form-label font-weight-bold">Title:</label>
                                             <p class="col-sm-6">{{$report_issue->title ?? ''}}</p>
                                         </div>
                                        <div class="col-sm-12">
                                            <label class="col-sm-6 col-form-label font-weight-bold">Description:</label>
                                            <p class="col-sm-12 col-form-label">{{$report_issue->description ?? ''}}</p>
                                        </div>
                                        <div class="col-sm-12">
                                            <label class="col-sm-12 col-form-label font-weight-bold">Status:</label>
                                            @if($report_issue)
                                                <p class="col-sm-2 @if(isset($report_issue) && $report_issue->status == 0) Unresolved @elseif(isset($report_issue) && $report_issue->status == 1) Resolved @endif">@if(isset($report_issue) && $report_issue->status == 0) Unresolved @elseif(isset($report_issue) && $report_issue->status == 1) Resolved @endif</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <div class="card">
                                <div class="card-header">
                                    <div class="row">
                                        <div class="col-md-9">
                                            <h5>View Report Issue Attachments</h5>
                                        </div>
                                    </div>
                                </div>
                            <div class="card-block">
                                <div class="main">
                                    <div class="row">
                                        @if(isset($documents))
                                            @foreach($documents as $key => $issue_doc)
                                                <div class="col-xl-3 col-md-6">
                                                    <div class="card comp-card">
                                                        <div class="card-body text-center">
                                                            <h6 class="m-b-20">View Attachment</h6>
                                                            @if((isset($issue_doc)) && ($issue_doc['image'] != Null) && file_exists(public_path('/img/assets/report-issues/'.$issue_doc['image'])))
                                                                <a href="{{ asset('/img/assets/report-issues/'.$issue_doc['image']) }}"
                                                                   target="_blank" class="btn btn-primary btn-sm"
                                                                   style="width:15px;padding:8px 21px 8px 10px">
                                                                    <i class="fas fa-eye document_view"
                                                                       data-toggle="tooltip" data-placement="top" title="View"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
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
                                    swal({
                                        title: "Success",
                                        text: "Report Issue status has been updated successfully!",
                                        icon: "success",
                                        type: "success"
                                    })
                                    location.reload();
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
