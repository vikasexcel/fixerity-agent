@extends('admin.layout.super_admin')
@section('title')
    Admin List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <style>
        table th, table td {
            word-wrap: break-word !important;
            white-space: normal;
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
    {{--<div class="other-service-horizontal-nav">--}}
    {{--@include('admin.include.store-horizontal-navbar')--}}
    {{--</div>--}}
    <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-list bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Admin List</h5>
                            <span>All Admin List</span>
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
                                <h5>Admin List</h5>
                                <a href="{{ route('get:admin:add_sub_admin') }}"
                                   class="btn btn-primary m-b-0 btn-right render_link">Add Sub Admin</a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons"
                                           class="table table-striped table-bordered"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th style="width: 30px !important;">No</th>
                                            <th style="width:600px !important;">Name</th>
                                            <th style="width:600px !important;">Email</th>
                                            <th style="width: 60px !important;">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($sub_admin_list))
                                            @foreach($sub_admin_list as $key => $admin)
                                                <tr id="hide_{{$admin->id}}">
                                                    <td>{{ $key+1 }}</td>
                                                    <td>{{ $admin->name }}</td>
                                                    <td>{{ $admin->email }}</td>
                                                    <td class="action">


                                                        <a href="{{ route('get:admin:edit_sub_admin',[$admin->id]) }}"
                                                                {{--class="render_link"--}}>
                                                            <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                 style="width:20px; height: 20px;"
                                                                 data-toggle="tooltip"
                                                                 data-placement="top" title="Edit">
                                                        </a>


                                                        <a class="delete" adminid="{{$admin->id}}">
                                                            <img src="{{ asset('/assets/images/template-images/remove-1.png') }}"
                                                                 style="width:20px; height: 20px;"
                                                                 data-toggle="tooltip"
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
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}" type="text/javascript"></script>
    <script>
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('adminid');
            swal({
                    title: "Sub admin Remove?",
                    text: "if press yes then sub admin is remove!",
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
                            url: '{{ route('get:admin:delete_sub_admin') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    var new_id = "#hide_" + id;
                                    swal("Success", "Sub admin removed successfully", "success");
                                    $(new_id).hide();
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        swal("Cancelled", "Sub admin not removed", "error");
                    }
                });
        });
    </script>
@endsection
