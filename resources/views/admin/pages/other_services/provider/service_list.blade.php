@extends('admin.layout.other_service')
@section('title')
    Provider Service List
@endsection
@php $theme_color = request()->get('general_settings')->theme_color @endphp
@section('page-css')
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css"
          integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('/assets/css/widget/widget.css') }}">
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 45px;
            height: 20px;
            margin-top: 10px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e2e2e2;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 12px;
            width: 15px;
            left: 5px;
            bottom: 4px;
            /*background-color: #FF5370;*/
            background-color: red;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 34px;
        }

        .switch input:checked + .slider:before {
            /*background-color: #55d192;*/
            background-color: green;
        }

        .switch input:checked + .slider {
            background-color: #e2e2e2;
        }

        input:checked + .slider {
            background-color: white;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px white;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(20px);
            -ms-transform: translateX(20px);
            transform: translateX(20px);
        }

        .cat-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 0;
        }

        .sos-st-card h4 {
            /*text-align: center;*/
            font-size: 16px;
            margin: 0 -15px;
        }

        .card-body {
            padding: 15px 15px 0;
            width: 100%;
        }

        .icon-list-demo i {
            width: auto;
        }

        .btn i {
            margin: 0;
            text-align: center;
        }

        .btn-outline-purple {
            color: #aa5de2;
            background-color: #fff;
            background-color: transparent;
        }

        .btn-purple {
            border-color: #c179f5;
        }

        .dashboard, .provides, .carts {
            border-radius: 7px;
            padding: 5px 8px;
            width: 35px;
            border: 1px solid gainsboro;
            color: #ababab;
        }

        .card-body a {
            margin-right: 10px;
        }

        .services .card-block {
            padding: 1.25rem !important;
        }

        .card-block .col-auto, .col {
            padding: 0 15px;
        }

        a {
            font-size: 15px;
        }

        .btn-outline-purple:hover {
            color: white;
            background-color: #c179f5;
            border-color: #c179f5;
        }

        .text_0 {
            color: #007bff;
        }

        .text_1 {
            color: #28a745;
        }

        .text_2 {
            color: #dc3545;
        }

        .text_3 {
            color: #ffc107;
        }

        .btn-warning:hover, .btn-warning:focus, .btn-warning:active {
            background-color: {{$theme_color}} !important;
            box-shadow: 0 0 0 0.2rem {{$theme_color."60"}} !important;
            border-color: {{$theme_color}} !important;
        }
    </style>
@endsection
@section('page-content')
    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title ">
                        <i class="feather icon-list bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Provider Services</h5>
                            <span>Provider Selected Services List</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">

                    <a href="{{ route('get:provider-admin:add_services') }}" style="{{(isset($total_service_category) &&  $total_service_category == count($services) ? 'display:none' : '')}}"
                       class="btn btn-primary m-b-0 btn-right render_link">Add Service</a>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->
        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="row services">
                            @if(isset($services) && (count($services) > 0))
                                @foreach($services as $service)
                                    <div class="col-md-4" style="float: left" id="hide_{{$service->id}}">
                                        <div class="card sos-st-card facebook">
                                            <div class="card-block">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <h3 class="m-b-0">
                                                            @if($service->category_type == 3 || $service->category_type == 4)
                                                                <a title="Home" class="render_link"
                                                                   href="{{ route('get:provider-admin:service-dashboard', $service->slug) }}">
                                                                    <img src="{{ asset('/assets/images/service-category/'.$service->icon_name)}}"
                                                                         height="40px" width="40px">
                                                                </a>
                                                            @endif
                                                        </h3>
                                                    </div>
                                                    <div class="col">
                                                        <h4 class="m-b-0">
                                                            @if($service->category_type == 3 || $service->category_type == 4)
                                                                <a title="Home" class="render_link"
                                                                   href="{{ route('get:provider-admin:service-dashboard', $service->slug) }}">
                                                                    {{$service->service_name}}
                                                                </a>
                                                                <br>
                                                                <span style="font-size: 14px;"
                                                                      class="{{ ($service->status == 0) ? 'text_0' : (($service->status == 1) ? 'text_1' : (($service->status == 2) ? 'text_2' : 'text_3') ) }}">{{ ($service->status == 0) ? "Pending" : (($service->status == 1) ? "Approved" : (($service->status == 2) ? "Blocked" : "Rejected") ) }}</span>
                                                            @endif
                                                        </h4>
                                                    </div>
                                                    <div class="col-auto">
                                                        <label class="switch">
                                                            <input type="checkbox" class="service-cat-status"
                                                                   id="service_cat_{{$service->id}}"
                                                                   service_id="{{$service->id}}"
                                                                   service_status="{{$service->current_status}}"
                                                                   @if($service->current_status == 1)checked @endif>
                                                            <span class="slider" title="Service On/Off"></span>
                                                        </label>
                                                    </div>
                                                    <div class="card-body">
                                                        @if($service->category_type == 3 || $service->category_type == 4)
                                                            <a href="{{ route('get:provider-admin:service-dashboard', $service->slug) }}"
                                                               class="btn waves-effect waves-dark dashboard btn-warning btn-outline-warning render_link"
                                                               title="Home">
                                                                <i class="fas fa-home"></i>
                                                            </a>
                                                            {{--<a--}}
                                                            {{--href="{{ route('get:admin:other_service_provider_list',[$service->slug,"approved"]) }}"--}}
                                                            {{--class="btn waves-effect waves-dark provides @if($service->category_type == 3) btn-warning btn-outline-warning @elseif($service->category_type == 4) btn-danger btn-outline-danger @endif render_link"--}}
                                                            {{--title="Providers">--}}
                                                            {{--<i class="fas fa-user-tie"></i>--}}
                                                            {{--</a>--}}
                                                            <a
                                                               href="{{ route('get:provider-admin:other_service_order_list',[$service->slug,"all"]) }}"
                                                               class="btn waves-effect waves-dark carts btn-warning btn-outline-warning render_link"
                                                               title="Orders">
                                                                <i class="fas fa-briefcase"></i>
                                                            </a>
                                                            <a service_id="{{$service->id}}" href=""
                                                               {{--href="{{ route('get:admin:other_service_order_list',[$service->slug,"all"]) }}"--}}
                                                               class="btn waves-effect waves-dark carts delete btn-warning btn-outline-warning render_link"
                                                               title="Remove">
                                                                <i class="fas fa-times-circle"></i>
                                                            </a>
                                                            {{--<a--}}
                                                            {{--href="{{ route('get:admin:edit_service_category',[$service->slug]) }}"--}}
                                                            {{--class="btn waves-effect waves-dark carts @if($service->category_type == 3) btn-warning btn-outline-warning @elseif($service->category_type == 4) btn-danger btn-outline-danger @endif render_link"--}}
                                                            {{--title="Orders">--}}
                                                            {{--<i class="fas fa-pencil-alt"></i>--}}
                                                            {{--</a>--}}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-md"  >
                                    <div class="card sos-st-card facebook">
                                        <div class="card-block">
                                            <div class="row align-items-center">
                                                <div class="form-group col-sm-12">

                                                    <div class="col-sm-12 text-center">
                                                        <div class="border-checkbox-section">
                                                            <label class="col-sm-3 col-form-label">Sorry.. Currently No Service Available..!!</label>
                                                        </div>

                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <!-- Page body end -->
            </div>
        </div>
    </div>
@endsection
@section('page-js')
    <script type="text/javascript">
        $(document).on('click', '.service-cat-status', function (e) {
            e.preventDefault();
            var id = $(this).attr('service_id');
            var status = $(this).attr('service_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Service?";
                txt = "if press yes then disable this service!";
            } else {
                title = "Enable Service?";
                txt = "if press yes then enable this service!";
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
                            url: '{{ route('get:provider-admin:update_service_current_status') }}',
                            data: {id: id},
                            success: function (result) {
                                var new_id = '#service_cat_' + id;
                                if (result.status == 1) {
                                    $(new_id).prop("checked", true);
                                    $(new_id).attr("service_status", 1);
                                    swal("Success", "Enable Service Successfully", "success");
                                } else {
                                    $(new_id).prop("checked", false);
                                    $(new_id).attr("service_status", 0);
                                    swal("Success", "Disable Service Successfully", "success");
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Service is Enable", "error");
                        } else {
                            swal("Cancelled", "Service is Disable", "error");
                        }
                    }
                });
        });
        $(document).on('click', '.delete', function (e) {
            e.preventDefault();
            var id = $(this).attr('service_id');
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
                            url: '{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('') :route('post:provider-admin:delete_service') }}',
                            data: {id: id},
                            success: function (result) {
                                if (result.success == true) {
                                   location.reload();
                                    swal("Success", "Service remove Successfully", "success");
                                    $('#hide_' + id).hide();
                                }
                                else{
                                    swal("Warning", result.message, "warning");
                                }
                            }
                        })
                    } else {
                        swal("Cancelled", "Your Data is safe :)", "error");
                    }
                });
        });
    </script>
@endsection
