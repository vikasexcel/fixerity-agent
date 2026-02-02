@extends('admin.layout.super_admin')
@section('title')
    Banner Slider List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
@endsection
@section('page-content')

    <div class="pcoded-content">
        {{--<div class="external-horizontal-nav">
            @include('admin.include.store-horizontal-navbar')
        </div>--}}
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5> Home Page Slider Image List</h5>
                                    <span>All Slider Image List</span>
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
                                <h5>Slider Image List</h5>
                                <a href="{{route('get:admin:home_page_slider')}}" title="Add Slider Image"
                                   class="btn btn-primary m-b-0 btn-right render_link">
                                    Add Slider Image</a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>Slider Image</th>
                                            <th>Service Name</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($banner_image))
                                            @foreach($banner_image as $key => $banner)
                                                <tr>
                                                    {{--<td>{{ $key + 1 }}</td>--}}
                                                    <td>
                                                        <img src="{{ asset('/assets/images/home-banner/'.$banner->banner_image) }}" height="50px">
                                                    </td>
                                                    <td>
                                                        {{$banner->service_name}}
                                                    </td>
                                                    <td>
                                                <span class="toggle">
                                                    <label>
                                                        <input name="status"
                                                               class="form-control home_slider"
                                                               id="home_slider_id_{{$banner->id}}"
                                                               home_slider_id="{{$banner->id}}"
                                                               home_slider_status="{{$banner->status}}"
                                                               type="checkbox" {{ ("1" == $banner->status) ? 'checked' : '' }}>
                                                        <span class="button-indecator" data-toggle="tooltip"
                                                              data-placement="top"
                                                              id="title_status_{{$banner->id}}"
                                                              title="{{ ("1" == $banner->status) ? 'Active' : 'InActive' }}"></span>
                                                    </label>
                                                </span>
                                                    </td>
                                                    <td class="action">
                                                        <a class="render_link"
                                                           href="{{ route('get:admin:edit_home_page_slider',[$banner->id]) }}">
                                                            <img
                                                                src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                style="width:20px; height: 20px;"
                                                                data-toggle="tooltip"
                                                                data-placement="top" title="Edit">
                                                        </a>
                                                        <a class="delete" sliderid="{{ $banner->id }}">
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
            var id = $(this).attr('sliderid');
            var RemovetableRow = table.row($(this).parents('tr'));
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this app slider!",
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
                            url: '{{ route('get:admin:delete_home_slider') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == 0) {
                                    swal("Warning", "You can not delete this Service Slider", "success");
                                }
                                if (result.success == true) {
                                    RemovetableRow.remove().draw();
                                    swal("Success", "Service Slider Delete Successfully", "success");
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
        $(document).on('click', '.home_slider', function (e) {
            e.preventDefault();
            var id = $(this).attr('home_slider_id');
            var status = $(this).attr('home_slider_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Home Slider?";
                txt = "if press yes then disable app slider!";
            } else {
                title = "Enable Home Slider?";
                txt = "if press yes then enable app slider!";
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
                            url: '{{ route("get:admin:change_home_slider_status") }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var home_slider_id = '#home_slider_id_' + id;
                                    if (result.status == 1) {
                                        $(home_slider_id).prop("checked", true);
                                        $(home_slider_id).attr("home_slider_status", 1);
                                        swal("Success", "Enable Home Slider successfully", "success");
                                    } else {
                                        $(home_slider_id).prop("checked", false);
                                        $(home_slider_id).attr("home_slider_status", 0);
                                        swal("Success", "Disable Home Slider successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Home Slider is Enable", "error");
                        } else {
                            swal("Cancelled", "Home Slider is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection

