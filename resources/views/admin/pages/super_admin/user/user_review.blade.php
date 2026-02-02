@extends('admin.layout.super_admin')
@section('title')
    {{ isset($user_details)?ucfirst($user_details->first_name):"" }} Customer Review List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5> {{ isset($user_details)?ucfirst($user_details->first_name):"" }} Customer Review List</h5>
                                    <span>All Review List</span>
                                </div>
                            </div>
                        </div>
                        {{--<div class="col-lg-4">--}}
                        {{--<a href="{{ route('get:admin:user_review_lists') }}"--}}
                        {{--class="btn btn-primary m-b-0 btn-right render_link">Back</a>--}}
                        {{--</div>--}}
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
                                <h5>{{ isset($user_details)?ucfirst($user_details->first_name):"" }} Customer Review List</h5>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Provider Name</th>
                                            <th>Provider Service</th>
                                            <th>Rating</th>
                                            <th>Comments</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        @if(isset($user_review_lists))
                                            @foreach($user_review_lists as $key => $user_review )
                                                <tr id="delete_customer_{{$user_review->id}}">
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>
                                                            <h6 style="color: #4099ff">
                                                                <span data-toggle="tooltip" data-placement="top"
                                                                      >{{ ucwords(strtolower($user_review->provider_name))}}</span>
                                                            </h6>
                                                    </td>
                                                    <td>
                                                        {{ ucwords(strtolower($user_review->service_name))}}
                                                    </td>
                                                    <td class="icon-url-link">
                                                            <div class="data-table-main icon-list-demo">
                                                                <i class="fa fa-star"></i>{{ $user_review->rating }}
                                                            </div>
                                                    </td>
                                                    <td>
                                                         {{ ($user_review->comment!="")?$user_review->comment:"--" }}
                                                    </td>
                                                    <td class="action">
                                                        <span class="toggle">
                                                            <label>
                                                                <input name="status"
                                                                       class="form-control userreview"
                                                                       id="review_id_{{$user_review->id}}"
                                                                       review_id="{{$user_review->id}}"
                                                                       review_status="{{$user_review->status}}"
                                                                       type="checkbox" {{ ("1" == $user_review->status) ? 'checked' : '' }}>
                                                                <span class="button-indecator" data-toggle="tooltip"
                                                                      data-placement="top"
                                                                      id="title_status_{{$user_review->id}}"
                                                                      title="{{ ("1" == $user_review->status) ? 'Active' : 'InActive' }}"></span>
                                                            </label>
                                                        </span>
                                                        <a class="delete" userid="{{ $user_review->id }}">
                                                            <img src="{{ asset('/assets/images/template-images/remove-1.png') }}"
                                                                 style="width:20px; height: 20px;" data-toggle="tooltip"
                                                                 data-placement="top" title="Delete">
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
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
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>

    <script type="text/javascript">
        var table = $('#new-cons').DataTable();
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('userid');
            var RemovetableRow = table.row($(this).parents('tr'));
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
                            url: '{{ route('get:admin:delete_user_review') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    // RemovetableRow.remove().draw();
                                    // swal("Success", result.message, "success");
                                    // location.reload();
                                    var new_id = "#delete_customer_" + id;
                                    swal("Success", "customer remove successfully", "success");
                                    $(new_id).hide();
                                }
                            }
                        })
                    } else {
                        swal("Cancelled", "Your Data is safe :)", "error");
                    }
                });
        });
        $(document).on('click', '.userreview', function (e) {
            e.preventDefault();
            var id = $(this).attr('review_id');
            var status = $(this).attr('review_status');
            var txt, title;
            if (status == 1) {
                title = "Disable user review ?";
                txt = "if press yes then disable user review!";
            } else {
                title = "Enable user review?";
                txt = "if press yes then enable user review!";
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
                            url: '{{ route("get:ajax:admin:update_user_review_status") }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var review_id_ = '#review_id_' + id;
                                    var title_status = '#title_status_' + id;
                                    if (result.status == 1) {
                                        $(review_id_).prop("checked", true);
                                        $(review_id_).attr("review_status", 1);
                                        // $(title_status).attr("title", "Active");
                                        swal("Success", "Enable User review successfully", "success");
                                    } else {
                                        $(review_id_).prop("checked", false);
                                        $(review_id_).attr("review_status", 0);
                                        // $(title_status).attr("title", "InActive");
                                        swal("Success", "Disable User review successfully", "success");
                                    }
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
@endsection

