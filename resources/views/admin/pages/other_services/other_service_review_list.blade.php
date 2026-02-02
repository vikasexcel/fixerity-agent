@extends('admin.layout.super_admin')
@section('title')
    @if(isset($service_category))
        {{ ucwords(strtolower($service_category->name)) }}
    @endif Service Review List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <!-- Data Table Excel Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/responsive/buttons.dataTables.min.css?v=0.1')}}">
    <style>
        .icon-list-demo i {
            height: auto;
            line-height: 10px;
            border: none;
            margin-right: 0;
            color: #4099ff;
            font-size: 18px;
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
        <div class="external-horizontal-nav">
            @include('admin.include.other-service-horizontal-navbar')
        </div>
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-list bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>@if(isset($service_category))
                                    {{ ucwords(strtolower($service_category->name)) }}
                                @endif Service Review List</h5>
                            <span>All @if(isset($service_category))
                                    {{ ucwords(strtolower($service_category->name)) }}
                                @endif Service Review List</span>
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
                                <h5>@if(isset($service_category))
                                        {{ ucwords(strtolower($service_category->name)) }}
                                    @endif Service Review List</h5>
                                <a href="javascript:history.back(1)"
                                   class="btn btn-primary m-b-0 btn-right render_link">Back</a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Customer Name</th>
                                            <th>Rating</th>
                                            <th>Comments</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($ratings as $key => $rating)
                                            <tr id="review_{{ $rating->id }}">
                                                <td>{{ $key+1 }}</td>
                                                <td>{{ $rating->first_name .' '. $rating->last_name }}</td>
                                                <td>
                                                    <div class="data-table-main icon-list-demo">
                                                        <i class="fa fa-star"></i> {{ $rating->rating }}
                                                    </div>
                                                </td>
                                                <td>
                                                    {{ $rating->comment }}
                                                </td>
                                                <td class="action">
                                                        <span class="toggle">
                                                    <label>
                                                        <input name="manual_assign" class="form-control rating_status"
                                                               type="checkbox" reviewid="{{$rating->id}}"
                                                               id="review_id_{{$rating->id}}"
                                                               reviewstatus="{{$rating->status}}" {{ ("1" == $rating->status) ? 'checked' : '' }}>
                                                        <span class="button-indecator" data-toggle="tooltip"
                                                              data-placement="top" title="Active"></span>
                                                    </label>
                                                </span>
                                                    <a class="delete" reviewid="{{$rating->id}}">
                                                        <img src="{{ asset('/assets/images/template-images/remove-1.png') }}"
                                                             style="width:20px; height: 20px;" data-toggle="tooltip"
                                                             data-placement="top" title="Delete">
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
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

@endsection
@section('page-js')
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}" type="text/javascript"></script>
    <!-- CDN for the Excel file -->
    <script src="{{asset('assets/js/responsive/dataTables.buttons.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/jszip.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/buttons.html5.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/buttons.print.min.js')}}"></script>
    <script type="text/javascript">
        var newcs = $('#new-cons').DataTable({
            dom: '<"top"lBf>rt<"bottom"pi><"clear">',
            buttons: [{
                extend: 'excel',
                text: 'Download Excel'
            }],
        });
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('reviewid');
            console.log(id);
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this Data!",
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
                            url: '{{ route('get:admin:delete_provider_review') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    var new_id = '#review_' + id;
                                    swal("Success", "review remove Successfully", "success");
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

        {{--$(document).on('click', '.rating_status', function (e) {--}}
        {{--e.preventDefault();--}}
        {{--var id = $(this).attr('reviewid');--}}
        {{--swal({--}}
        {{--title: "Disable Rating?",--}}
        {{--text: "if press yes then disable rating!",--}}
        {{--type: "warning",--}}
        {{--showCancelButton: true,--}}
        {{--confirmButtonClass: "btn-danger",--}}
        {{--confirmButtonText: "Yes, delete it!",--}}
        {{--cancelButtonText: "No, cancel!",--}}
        {{--closeOnConfirm: false,--}}
        {{--closeOnCancel: false--}}
        {{--},--}}
        {{--function (isConfirm) {--}}
        {{--if (isConfirm) {--}}
        {{--$.ajax({--}}
        {{--type: 'get',--}}
        {{--url: '{{ route('get:admin:provider_review_change_status') }}',--}}
        {{--data: {id: id},--}}
        {{--success: function (result) {--}}
        {{--if (result.success == true) {--}}
        {{--//                                    location.reload();--}}
        {{--var new_id = "#review_id_" + id;--}}
        {{--swal("Success", "category remove successfully", "success");--}}
        {{--$(new_id).hide();--}}
        {{--} else {--}}
        {{--console.log(result);--}}
        {{--}--}}
        {{--}--}}
        {{--})--}}
        {{--} else {--}}
        {{--swal("Cancelled", "rating is enable", "error");--}}
        {{--}--}}
        {{--});--}}
        {{--});--}}
        $(document).on('click', '.rating_status', function (e) {
            e.preventDefault();
            var id = $(this).attr('reviewid');
            var status = $(this).attr('reviewstatus');
            var txt, title;
            if (status == 1) {
                title = "Disable Review?";
                txt = "if press yes then disable this review and not display in user panel!";
            } else {
                title = "Enable Review?";
                txt = "if press yes then enable this review and display in user panel!";
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
                            url: '{{ route('get:admin:provider_review_change_status') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    var new_id = '#review_id_' + id;
                                    if (result.status == 1) {
                                        $(new_id).prop("checked", true);
                                        $(new_id).attr("reviewstatus", 1);
                                        swal("Success", "Enable Review Successfully", "success");
                                    } else {
                                        $(new_id).prop("checked", false);
                                        $(new_id).attr("reviewstatus", 0);
                                        swal("Success", "Disable Review Successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Review is Enable", "error");
                        } else {
                            swal("Cancelled", "Review is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection
