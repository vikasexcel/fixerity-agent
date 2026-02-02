@extends('admin.layout.super_admin')
@section('title')
    @if(isset($service_category))
    {{ ucwords(strtolower($service_category->name)) }} Category
    @endif
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
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
                                    {{ ucwords(strtolower($service_category->name)) }} Category
                                @endif</h5>
                            <span>All @if(isset($service_category))
                                    {{ ucwords(strtolower($service_category->name)) }} Category
                                @endif List</span>
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
                                        {{ ucwords(strtolower($service_category->name)) }} Category
                                    @endif List</h5>
                                {{--<a href="{{ route('get:admin:add_other_service_sub_category',$slug) }}"--}}
                                   {{--class="btn btn-primary m-b-0 btn-right render_link">Add Sub Category</a>--}}
                                <a href="{{ route('get:admin:add_other_service_sub_category',$slug) }}"
                                   class="btn btn-primary m-b-0 btn-right">Add Category</a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Icon</th>
                                            <th>Category Name</th>
                                            <th>Service Name</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($sub_categories))
                                            @foreach($sub_categories as $key => $sub_category)
                                                <tr id="delete_sub_cat_{{$sub_category->id}}">
                                                    <td>{{$key+1}}</td>
                                                    <td>
                                                        <img src="{{ asset('/assets/images/service-category/other-service-sub-category/'.$sub_category->icon_name) }}"
                                                             width="26" height="26">
                                                    </td>
                                                    <td>{{$sub_category->name}}</td>
                                                    <td>{{ucwords(strtolower($sub_category->service_category_name))}}</td>
                                                    <td>
                                                <span class="toggle">
                                                    <label>
                                                        <input name="manual_assign"
                                                               class="form-control sub_cat_status"
                                                               id="sub_cat_{{$sub_category->id}}"
                                                               sub_cat_id="{{$sub_category->id}}"
                                                               sub_cat_status="{{$sub_category->status}}"
                                                               type="checkbox" {{ ("1" == $sub_category->status) ? 'checked' : '' }}>
                                                        <span class="button-indecator" data-toggle="tooltip" data-placement="top" title="{{ ("1" == $sub_category->status) ? 'Active' : 'InActive' }}"></span>
                                                    </label>
                                                </span></td>
                                                    <td class="action">
                                                        <a href="{{ route('get:admin:edit_other_service_sub_category',[$slug,$sub_category->id]) }}"
                                                           class="render_link">
                                                            <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                 style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Edit">
                                                        </a>
                                                        <a class="delete" sub_category_id="{{$sub_category->id}}">
                                                            <img src="{{ asset('/assets/images/template-images/remove-1.png') }}"
                                                                 style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Delete">
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
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('sub_category_id');
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
                            url: '{{ route('get:admin:delete_other_service_sub_category') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    var new_id = "#delete_sub_cat_" + id;
                                    swal("Success", "sub category remove successfully", "success");
                                    $(new_id).hide();
                                }
                                else {
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
        $(document).on('click', '.sub_cat_status', function (e) {
            e.preventDefault();
            var id = $(this).attr('sub_cat_id');
            var status = $(this).attr('sub_cat_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Category?";
                txt = "if press yes then disable this category and not display in Application";
            }
            else {
                title = "Enable Category?";
                txt = "if press yes then enable this category and display in Application";
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
                            url: '{{ route('get:admin:other_service_sub_category_change_status') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    var new_id = '#sub_cat_' + id;
                                    if (result.status == 1) {
                                        $(new_id).prop("checked", true);
                                        $(new_id).attr("sub_cat_status", 1);
                                        swal("Success", "Enable Category Successfully", "success");
                                    }
                                    else {
                                        $(new_id).prop("checked", false);
                                        $(new_id).attr("sub_cat_status", 0);
                                        swal("Success", "Disable Category Successfully", "success");
                                    }
                                } else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Category is Enable", "error");
                        }
                        else {
                            swal("Cancelled", "Category is Disable", "error");
                        }
                    }
                });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#new-cons').DataTable({
                "columnDefs": [
                    { "orderable": false, "targets": [1, 3, 4, 5] }
                ]
            });
        });
    </script>
@endsection

