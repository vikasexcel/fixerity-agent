@extends('admin.layout.super_admin')
@section('title')
    Email Templates List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
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
                                    <h5> Email Templates List</h5>
                                    <span>All Email Templates List</span>
                                </div>
                            </div>
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
                                <h5>Email Templates List</h5>
{{--                                <a href="{{ route('get:admin:add_email_templates') }}"--}}
{{--                                   class="btn btn-primary m-b-0 btn-right render_link">Add Template</a>--}}
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="users" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($email_templates))
                                            @foreach($email_templates as $key => $template )
                                                <tr id="delete_customer_{{$template->id}}">
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>
                                                        {{ $template->title }}
                                                    </td>
                                                    <td>
                                                        <span class="toggle">
                                                            <label>
                                                                <input name="status"
                                                                       class="form-control user"
                                                                       id="user_id_{{$template->id}}"
                                                                       user_id="{{$template->id}}"
                                                                       user_status="{{$template->status}}"
                                                                       type="checkbox" {{ ("1" == $template->status) ? 'checked' : '' }}>
                                                                <span class="button-indecator" data-toggle="tooltip"
                                                                      data-placement="top"
                                                                      id="title_status_{{$template->id}}"
                                                                      title="{{ ("1" == $template->status) ? 'Active' : 'InActive' }}"></span>
                                                            </label>
                                                        </span>
                                                    </td>
                                                    <td class="action">
                                                        <a class="render_link"
                                                           href="{{ route('get:admin:edit_email_templates',$template->id) }}">
                                                            <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                 style="width:20px; height: 20px;" data-toggle="tooltip"
                                                                 data-placement="top" title="Edit">
                                                        </a>
                                                        <a class="delete" userid="{{ $template->id }}">
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

    <script>
        $(document).ready(function () {
            var table = $('#users').DataTable();
        });
    </script>

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
                            url: '{{ route('get:admin:delete_email_templates') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
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
                title = "Disable Template?";
                txt = "if press yes then disable template!";
            } else {
                title = "Enable Template?";
                txt = "if press yes then enable template!";
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
                            url: '{{ route("get:admin:update_email_templates_status") }}',
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
@endsection

