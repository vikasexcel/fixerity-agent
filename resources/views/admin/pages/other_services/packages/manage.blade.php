@extends('admin.layout.other_service')
@section('title')
    @if(isset($service_category))
        {{ ucwords(strtolower($service_category->name)) }}
    @endif Services Packages List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <style>
        .document a {
            color: #4099ff;
            font-weight: bold;
            /*font-size: 15px;*/
        }

        .product a {
            background: #4099ff;
            color: white;
            padding: 2px 5px;
            border-radius: 25px;
        }

        .icon-list-demo i {
            height: auto;
            line-height: 10px;
            border: none;
            margin-right: 5px;
            color: #4099ff;
        }

    </style>
@endsection
@section('page-content')
    <div class="pcoded-content">
        <div class="external-horizontal-nav">
            @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                @include('admin.include.other-service-horizontal-navbar')
            @else
                @include('admin.include.other-service-provider-navbar')
            @endif
        </div>
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5>@if(isset($service_category))
                                            {{ ucwords(strtolower($service_category->name)) }}
                                        @endif Services Packages List</h5>
                                    <span>All @if(isset($service_category))
                                            {{ ucwords(strtolower($service_category->name)) }}
                                        @endif Services Packages List</span>
                                </div>
                            </div>
                        </div>
                        {{--<div class="col-lg-4">--}}
                        {{--<a href="{{ route('get:admin:other_service_provider_list') }}"--}}
                        {{--class="btn btn-primary m-b-0 btn-right render_link">Back</a>--}}
                        {{--</div>--}}
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
                                <h5>@if(isset($service_category))
                                        {{ ucwords(strtolower($service_category->name)) }}
                                    @endif Services Packages List</h5>
                                <a
                                        href="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('get:admin:provider_add_package',[$slug,$provider_id]) : route('get:provider-admin:add-package',[$slug])}}"
                                        class="btn btn-primary m-b-0 btn-right render_link">Add Package</a>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Package Name</th>
                                            <th>Category</th>
                                            <th>Max Book Quantity</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($package_list))
                                            @foreach($package_list as $key => $package)
                                                <tr id="delete_package_{{$package->id}}">
                                                    <td> {{ $key+1 }} </td>
                                                    <td> {{$package->package_name}} </td>
                                                    <td> {{$package->sub_cat_name}} </td>
                                                    <td> {{$package->max_book_quantity}} </td>
                                                    <td class=""><span class="currency"></span> {{ number_format($package->price,2) }}</td>
                                                    <td>
                                                <span class="toggle">
                                                    <label>
                                                        <input name="manual_assign" class="form-control package_status"
                                                               id="package_{{$package->id}}"
                                                               package_id="{{$package->id}}"
                                                               package_status="{{$package->status}}"
                                                               type="checkbox" {{ ("1" == $package->status) ? 'checked' : '' }}>
                                                        <span class="button-indecator" data-toggle="tooltip"
                                                              data-placement="top" title="Active"></span>
                                                    </label>
                                                </span></td>
                                                    <td class="action">
                                                        <a href="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('get:admin:provider_edit_package',[$slug,$package->id]) : route('get:provider-admin:edit-package',[$slug,$package->id]) }}"
                                                           class="render_link">
                                                            <img src="{{ asset('/assets/images/template-images/writing-1.png') }}"
                                                                 style="width:20px; height: 20px;"
                                                                {{--data-toggle="tooltip" data-placement="top" --}}
                                                                 title="Edit">
                                                        </a>
                                                        <a class="delete" package_id="{{$package->id}}" provider_service_id="{{$package->provider_service_id}}">
                                                            <img src="{{ asset('/assets/images/template-images/remove-1.png') }}"
                                                                 style="width:20px; height: 20px;"
                                                                {{--data-toggle="tooltip" data-placement="top" --}}
                                                                 title="Delete">
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
    <script type="text/javascript">
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('package_id');
            var provider_service_id = $(this).attr('provider_service_id');

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
                            url: '{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('get:admin:provider_delete_package') :route('get:provider-admin:delete-package') }}',
                            data: {id: id, provider_service_id: provider_service_id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    swal("Success", "Package remove Successfully", "success");
                                    $('#delete_package_' + id).hide();
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
        $(document).on('click', '.package_status', function (e) {
            e.preventDefault();
            var id = $(this).attr('package_id');
            var status = $(this).attr('package_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Package Service?";
                txt = "if press yes then disable this Package service and not display in user Application";
            } else {
                title = "Enable Package Service?";
                txt = "if press yes then enable this Package service and display in user Application";
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
                            url: '{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('get:admin:provider_package_change_status',$slug) : route('get:provider-admin:update-package-status') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
//                                    location.reload();
                                    var new_id = '#package_' + id;
                                    if (result.status == 1) {
                                        $(new_id).prop("checked", true);
                                        $(new_id).attr("package_status", 1);
                                        swal("Success", "Enable Service Successfully", "success");
                                    } else {
                                        $(new_id).prop("checked", false);
                                        $(new_id).attr("package_status", 0);
                                        swal("Success", "Disable Service Successfully", "success");
                                    }
                                }else {
                                    swal("Warning", result.message, "warning");
                                    console.log(result);
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Category is Enable", "error");
                        } else {
                            swal("Cancelled", "Category is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection
