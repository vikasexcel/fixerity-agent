@extends('admin.layout.super_admin')
@section('title')
    On Demand Service Slider List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
@endsection
@section('page-content')

    <div class="pcoded-content">
        <div class="other-service-horizontal-nav">
            @include('admin.include.other-service-horizontal-navbar')
        </div>
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5> On Demand Service Slider Image List</h5>
                                    <span>All On Demand Service Slider Image List</span>
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
                                <h5>On Demand Service Slider Image List</h5>
                                <a href="{{route('get:admin:add_on_demand_service_slider',$slug)}}" title="Add Slider Image"
                                   class="btn btn-primary m-b-0 btn-right render_link">
                                    Add On Demand Service Slider Image</a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>On Demand Service Slider Image</th>
                                            <th>On Demand Category Name</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($service_slider_list))
                                            @foreach($service_slider_list as $key => $banner)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>
                                                        <img src="{{ asset('/assets/images/service-slider-banner/'.$banner->banner_image) }}" height="50px">
                                                    </td>
                                                    <td>
                                                        {{$banner->on_demand_category_name}}
                                                    </td>
                                                    <td>
                                                        <span class="toggle">
                                                            <label>
                                                                <input name="status"
                                                                       class="form-control home_slider"
        {{--                                                               id="home_slider_id_{{$banner->id}}"--}}
        {{--                                                               home_slider_id="{{$banner->id}}"--}}
        {{--                                                               home_slider_status="{{$banner->status}}"--}}
                                                                       id="service_slider_id_{{$banner->id}}"
                                                                       service_slider_id="{{$banner->id}}"
                                                                       service_slider_status="{{$banner->status}}"
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
                                                           href="{{ route('get:admin:edit_on_demand_service_slider',['slug'=>$slug,'id'=>$banner->id]) }}">
                                                            <img
                                                                src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                style="width:20px; height: 20px;"
                                                                data-toggle="tooltip"
                                                                data-placement="top" title="Edit">
                                                        </a>
                                                        <a class="delete"
{{--                                                           sliderid="{{ $banner->id }}"--}}
                                                            service_slider_id="{{ $banner->id }}"
                                                        >
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
        var table = $('#new-cons').DataTable({
            "columnDefs": [
                { "orderable": false, "targets": [1, 3, 4] }
            ]
        });
    </script>

    <script type="text/javascript">
        var table = $('#new-cons').DataTable();
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('service_slider_id');
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
                            url: '{{ route('get:ajax:admin:delete_on_demand_service_slider') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == 0) {
                                    swal("Warning", "You can not delete this On Demand Service Slider", "success");
                                }
                                if (result.success == true) {
                                    RemovetableRow.remove().draw();
                                    swal("Success", "On Demand Service Slider Delete Successfully", "success");
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
            var id = $(this).attr('service_slider_id');
            var status = $(this).attr('service_slider_status');
            var txt, title;
            if (status == 1) {
                title = "Disable On Demand Service Slider?";
                txt = "if press yes then disable app slider!";
            } else {
                title = "Enable On Demand Service Slider?";
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
                            url: '{{ route("get:ajax:admin:update_on_demand_service_slider_status") }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var service_slider_id = '#service_slider_id_' + id;
                                    if (result.status == 1) {
                                        $(service_slider_id).prop("checked", true);
                                        $(service_slider_id).attr("service_slider_status", 1);
                                        swal("Success", "Enable On Demand Service Slider successfully", "success");
                                    } else {
                                        $(service_slider_id).prop("checked", false);
                                        $(service_slider_id).attr("service_slider_status", 0);
                                        swal("Success", "Disable On Demand Service Slider successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "On Demand Service Slider is Enable", "error");
                        } else {
                            swal("Cancelled", "On Demand Service Slider is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection

