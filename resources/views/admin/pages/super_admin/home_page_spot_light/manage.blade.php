@extends('admin.layout.super_admin')
@section('title')
    Spot Light List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <!-- Data Table Excel Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/responsive/buttons.dataTables.min.css?v=0.1')}}">
    <style>
        .action a {
            /*margin: 0;*/
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
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5>Spot Light List</h5>
                                    <span>All Spot Light List</span>
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
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>Spot Light List</h5>
                                <a href="{{route('get:admin:add_home_page_spot_light')}}" title="Add Feature Store" class="btn btn-primary m-b-0 btn-right render_link">Add Provider </a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap" style="width:100%">
                                        <thead><tr>
                                            <th>Service Name</th>
                                            <th>Provider Name</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr></thead>
                                        <tbody>
                                        @if(isset($home_page_spot_light_list))
                                            @foreach($home_page_spot_light_list as $key => $get_home_page_spot_light)
                                                <tr>
                                                    <td>{{ $get_home_page_spot_light['service_cat_name'] }}</td>
                                                    <td>{{ $get_home_page_spot_light['provider_name'] }}</td>
                                                    <td>
                                                        <span class="toggle">
                                                            <label>
                                                                <input name="status" class="form-control spot_light_status"
                                                                       id="spot_light_id_{{ $get_home_page_spot_light['id'] }}"
                                                                       spot_light_id="{{ $get_home_page_spot_light['id'] }}"
                                                                       spot_light_status="{{ $get_home_page_spot_light['status'] }}"
                                                                       type="checkbox" {{ ("1" == $get_home_page_spot_light['status']) ? 'checked' : '' }}>
                                                                <span class="button-indecator" id="title_status_{{ $get_home_page_spot_light['id'] }}" title="{{ ("1" == $get_home_page_spot_light['status']) ? 'Active' : 'InActive' }}"></span>
                                                            </label>
                                                        </span>
                                                    </td>
                                                    <td class="action">
                                                        <a class="render_link" href="{{ route('get:admin:edit_home_page_spot_light',[ "id" => $get_home_page_spot_light['id'] ]) }}">
                                                            <img src="{{ asset('/assets/images/template-images/writing-1.png') }}" style="width:20px; height: 20px;" title="Edit">
                                                        </a>
                                                        <a class="delete" spot_light_id="{{ $get_home_page_spot_light['id'] }}"><img src="{{ asset('/assets/images/template-images/remove-1.png') }}" style="width:20px; height: 20px;" title="Delete"></a>
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
            var id = $(this).attr('spot_light_id');
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this app spot light!",
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
                            url: '{{ route('get:admin:delete_home_spot_light') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == 0) {
                                    swal("Warning", "You can not delete this Homepage spot light", "success");
                                }
                                if (result.success == true) {
                                    swal({
                                        type: "success",
                                        title: "Success",
                                        text: "Homepage spot light delete successfully"
                                    },function (){
                                        window.location.reload();
                                    });
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
        $(document).on('click', '.spot_light_status', function (e) {
            e.preventDefault();
            var id = $(this).attr('spot_light_id');
            var status = $(this).attr('spot_light_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Home Page Spot Light?";
                txt = "if press yes then disable app home page spot light!";
            } else {
                title = "Enable Home Page Spot Light?";
                txt = "if press yes then enable app home page spot light!";
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
                            url: '{{ route("get:admin:change_home_spot_light_status") }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                    var spot_light_id = '#spot_light_id_' + id;
                                    var title_status = '#title_status_' + id;
                                    if (result.status == 1) {
                                        $(spot_light_id).prop("checked", true);
                                        $(spot_light_id).attr("spot_light_status", 1);
                                        $(title_status).attr("title", "Active");
                                        swal("Success", "Enable Home Page Spot Light successfully", "success");
                                    } else {
                                        $(spot_light_id).prop("checked", false);
                                        $(spot_light_id).attr("spot_light_status", 0);
                                        $(title_status).attr("title", "InActive");
                                        swal("Success", "Disable Home Page Spot Light successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Home page spot light is Enable", "error");
                        } else {
                            swal("Cancelled", "Home page spot light is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection

