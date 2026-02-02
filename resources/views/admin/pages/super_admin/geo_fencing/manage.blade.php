@extends('admin.layout.super_admin')
@section('title')
    Geo Fenced Restricted Area
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <style>
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
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5>Restricted Area List</h5>
                                    <span>All Restricted Area</span>
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
        <!-- [ breadcrumb ] end -->

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>Restricted Area</h5>

                                <a href="{{ route('get:admin:add_restricted_area') }}"
                                   class="btn btn-primary m-b-0 btn-right render_link">
                                    Add Restricted Area</a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Area</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($area_list))
                                                @foreach($area_list as $key => $area )
                                                    <tr id="delete_customer_{{$area->id}}">
                                                        <td>{{ $key + 1 }}</td>
                                                        <td>
                                                            {{ $area->name }}
                                                        </td>
                                                        <td>
                                                            <span class="toggle">
                                                                <label>
                                                                    <input name="status"
                                                                           class="form-control user"
                                                                           id="user_id_{{$area->id}}"
                                                                           user_id="{{$area->id}}"
                                                                           user_status="{{$area->status}}"
                                                                           type="checkbox" {{ ("1" == $area->status) ? 'checked' : '' }}>
                                                                    <span class="button-indecator" data-toggle="tooltip"
                                                                          data-placement="top"
                                                                          id="title_status_{{$area->id}}"
                                                                          title="{{ ("1" == $area->status) ? 'Active' : 'InActive' }}"></span>
                                                                </label>
                                                            </span>
                                                        </td>
                                                        <td class="action">
                                                            <a class="render_link"
                                                               href="{{ route('get:admin:edit_restricted_area',$area->id) }}">
                                                                <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                     style="width:20px; height: 20px;" data-toggle="tooltip"
                                                                     data-placement="top" title="Edit">
                                                            </a>
                                                            <a class="delete" userid="{{ $area->id }}">
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
                            url: '{{ route('get:admin:delete_restricted_area') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    // RemovetableRow.remove().draw();
                                    // swal("Success", result.message, "success");
                                    // location.reload();
                                    var new_id = "#delete_customer_" + id;
                                    swal("Success", "area remove successfully", "success");
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
                title = "Disable area?";
                txt = "if press yes then disable area!";
            } else {
                title = "Enable area?";
                txt = "if press yes then enable area!";
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
                            url: '{{ route("get:admin:update_restricted_area_status") }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var user_id_ = '#user_id_' + id;
                                    var title_status = '#title_status_' + id;
                                    if (result.status == 1) {
                                        $(user_id_).prop("checked", true);
                                        $(user_id_).attr("user_status", 1);
                                        swal("Success", "Enable Area successfully", "success");
                                    } else {
                                        $(user_id_).prop("checked", false);
                                        $(user_id_).attr("user_status", 0);
                                        swal("Success", "Disable Area successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Area is Enable", "error");
                        } else {
                            swal("Cancelled", "Area is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection

