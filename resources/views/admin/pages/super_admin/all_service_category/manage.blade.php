@extends('admin.layout.super_admin')
@section('title')
    Services
@endsection
@section('page-css')
    {{--<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">--}}
    {{--<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">--}}
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

    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-grid bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Services</h5>
                            <span>Select the services you want to offer to the user</span>
                        </div>
                    </div>
                </div>
                {{--<div class="col-lg-4">--}}
                {{--<div class="page-header-breadcrumb">--}}
                {{--<ul class=" breadcrumb breadcrumb-title">--}}
                {{--<li class="breadcrumb-item">--}}
                {{--<a href="{{ route('get:admin:dashboard') }}"><i class="feather icon-home"></i> Dashboard</a>--}}
                {{--</li>--}}
                {{--<li class="breadcrumb-item"><a href="">Service Category List</a>--}}
                {{--</li>--}}
                {{--</ul>--}}
                {{--</div>--}}
                {{--</div>--}}
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="row services">
                            @if(isset($service_categories))
                                @foreach($service_categories as $service_category)
                                    {{--<div class="cat-card">--}}
                                    {{--<div class="card @if($service_category->category_type==1) card-green @elseif($service_category->category_type==2) card-blue @elseif($service_category->category_type==3) card-yellow @elseif($service_category->category_type==4) card-red @elseif($service_category->category_type==5) card-purple @endif st-cir-card text-white">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<h2 class="cat-title">{{ $service_category->name }}</h2>--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-sm-4 offset-sm-4">--}}
                                    {{--<div class="row">--}}
                                    {{--<div id="status-round-1" class="chart-shadow st-cir-chart">--}}
                                    {{--<h5 class="cat-icon">--}}
                                    {{--<img src="{{ asset('/assets/images/service-category/'.$service_category->icon_name)}}"--}}
                                    {{--height="40px" width="40px"></h5>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-center col-sm-12">--}}
                                    {{--<span class="m-b-0">--}}
                                    {{--<label class="switch">--}}
                                    {{--<input type="checkbox" class="service-cat-status"--}}
                                    {{--service_id="{{$service_category->id}}"--}}
                                    {{--service_status="{{$service_category->status}}"--}}
                                    {{--@if($service_category->status == 1)checked @endif>--}}
                                    {{--<span class="slider"></span>--}}
                                    {{--</label>--}}
                                    {{--</span>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    @if($service_category->id == 3)
                                    @else
                                        <div class="col-md-4" style="float: left">
                                            <div class="card sos-st-card facebook">
                                                <div class="card-block">
                                                    <div class="row align-items-center">
                                                        <div class="col-auto">
                                                            <h3 class="m-b-0">
                                                                @if($service_category->category_type == 1 || $service_category->category_type == 5 || $service_category->category_type == 6)
                                                                    <a title="Home" class="render_link" href="{{ route('get:admin:transport_service_dashboard', $service_category->slug) }}">
                                                                        <img src="{{ asset('/assets/images/service-category/'.$service_category->icon_name)}}"
                                                                             height="40px" width="40px">
                                                                    </a>
                                                                @elseif($service_category->category_type == 2)
                                                                    <a title="Home" class="render_link" href="{{ route('get:admin:store_service_dashboard', $service_category->slug) }}">
                                                                        <img src="{{ asset('/assets/images/service-category/'.$service_category->icon_name)}}"
                                                                             height="40px" width="40px">
                                                                    </a>
                                                                @elseif($service_category->category_type == 3 || $service_category->category_type == 4)
                                                                    <a title="Home" class="render_link" href="{{ route('get:admin:other_service_dashboard', $service_category->slug) }}">
                                                                        <img src="{{ asset('/assets/images/service-category/'.$service_category->icon_name)}}"
                                                                             height="40px" width="40px">
                                                                    </a>
                                                                @endif
                                                                {{--<img src="{{ asset('/assets/images/service-category/'.$service_category->icon_name)}}"--}}
                                                                     {{--height="40px" width="40px">--}}
                                                            </h3>
                                                        </div>
                                                        <div class="col">
                                                            <h4 class="m-b-0">
                                                                @if($service_category->category_type == 1 || $service_category->category_type == 5 || $service_category->category_type == 6 )
                                                                    <a title="Home" class="render_link" href="{{ route('get:admin:transport_service_dashboard', $service_category->slug) }}">
                                                                        {{$service_category->name}}
                                                                    </a>
                                                                @elseif($service_category->category_type == 2)
                                                                    <a title="Home" class="render_link" href="{{ route('get:admin:store_service_dashboard', $service_category->slug) }}">
                                                                        {{$service_category->name}}
                                                                    </a>
                                                                @elseif($service_category->category_type == 3 || $service_category->category_type == 4)
                                                                    <a title="Home" class="render_link" href="{{ route('get:admin:other_service_dashboard', $service_category->slug) }}">
                                                                        {{$service_category->name}}
                                                                    </a>
                                                                @endif
                                                                {{--<a href="">--}}
                                                                    {{--{{$service_category->name}}--}}
                                                                {{--</a>--}}
                                                            </h4>
                                                        </div>
                                                        <div class="col-auto">
                                                            <label class="switch">
                                                                <input type="checkbox" class="service-cat-status"
                                                                       id="service_cat_{{$service_category->id}}"
                                                                       service_id="{{$service_category->id}}"
                                                                       service_status="{{$service_category->status}}"
                                                                       @if($service_category->status == 1)checked @endif>
                                                                <span class="slider"></span>
                                                            </label>
                                                        </div>
                                                        <div class="card-body">
                                                            @if($service_category->category_type == 1 || $service_category->category_type == 6)
                                                                <a href="{{ route('get:admin:transport_service_dashboard', $service_category->slug) }}"
                                                                   class="btn waves-effect waves-dark dashboard btn-success btn-outline-success render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Home">
                                                                    <i class="fas fa-home"></i>
                                                                </a>
                                                                <a href="{{ route('get:admin:transport_provider_list',['id' => $service_category->slug ]) }}"
                                                                   class="btn waves-effect waves-dark provides btn-success btn-outline-success render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Drivers">
                                                                    <i class="fas fa-user-tie"></i>
                                                                </a>
                                                                <a href="{{ route('get:admin:transport_service_category_ride_list',['id' => $service_category->slug ]) }}"
                                                                   class="btn waves-effect waves-dark carts btn-success btn-outline-success render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Orders">
                                                                    <i class="fas fa-briefcase"></i>
                                                                </a>
                                                            @elseif($service_category->category_type == 2)
                                                                <a href="{{ route('get:admin:store_service_dashboard', $service_category->slug) }}"
                                                                   class="btn waves-effect waves-dark dashboard btn-primary btn-outline-primary render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Home">
                                                                    <i class="fas fa-home"></i>
                                                                </a>
                                                                <a href="{{ route('get:admin:store_list',[ $service_category->slug,"approved"]) }}"
                                                                   class="btn waves-effect waves-dark provides btn-primary btn-outline-primary render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Stores">
                                                                    <i class="fas fa-store"></i>
                                                                </a>
                                                                <a href="{{ route('get:admin:store_provider_order_list',[$service_category->slug,"all"]) }}"
                                                                   class="btn waves-effect waves-dark carts btn-primary btn-outline-primary render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Orders">
                                                                    <i class="fas fa-briefcase"></i>
                                                                </a>

                                                            @elseif($service_category->category_type == 3 || $service_category->category_type == 4)
                                                                <a href="{{ route('get:admin:other_service_dashboard', $service_category->slug) }}"
                                                                   class="btn waves-effect waves-dark dashboard @if($service_category->category_type == 3) btn-warning btn-outline-warning @elseif($service_category->category_type == 4) btn-danger btn-outline-danger @endif render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Home">
                                                                    <i class="fas fa-home"></i>
                                                                </a>
                                                                <a href="{{ route('get:admin:other_service_provider_list',[$service_category->slug,"approved"]) }}"
                                                                   class="btn waves-effect waves-dark provides @if($service_category->category_type == 3) btn-warning btn-outline-warning @elseif($service_category->category_type == 4) btn-danger btn-outline-danger @endif render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Providers">
                                                                    <i class="fas fa-user-tie"></i>
                                                                </a>
                                                                <a href="{{ route('get:admin:other_service_order_list',[$service_category->slug,"all"]) }}"
                                                                   class="btn waves-effect waves-dark carts @if($service_category->category_type == 3) btn-warning btn-outline-warning @elseif($service_category->category_type == 4) btn-danger btn-outline-danger @endif render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Orders">
                                                                    <i class="fas fa-briefcase"></i>
                                                                </a>
                                                            @elseif($service_category->category_type == 5)
                                                                <a href="{{ route('get:admin:transport_service_dashboard', $service_category->slug) }}"
                                                                   class="btn waves-effect waves-dark dashboard btn-purple btn-outline-purple render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Home">
                                                                    <i class="fas fa-home"></i>
                                                                </a>
                                                                <a href="{{ route('get:admin:transport_provider_list',['id' => $service_category->slug ]) }}"
                                                                   class="btn waves-effect waves-dark provides btn-purple btn-outline-purple render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Providers">
                                                                    <i class="fas fa-user-tie"></i>
                                                                </a>
                                                                <a href="{{ route('get:admin:transport_service_category_ride_list',['id' => $service_category->slug ]) }}"
                                                                   class="btn waves-effect waves-dark carts btn-purple btn-outline-purple render_link"
                                                                   {{--data-toggle="tooltip" data-placement="bottom"--}}
                                                                   title="Orders">
                                                                    <i class="fas fa-briefcase"></i>
                                                                </a>
                                                            @endif

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
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
{{--    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>--}}
{{--    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>--}}
{{--    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>--}}
    {{--<script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>--}}
    <script type="text/javascript">
        $(document).on('click', '.service-cat-status', function (e) {
            e.preventDefault();
            var id = $(this).attr('service_id');
            var status = $(this).attr('service_status');
            var txt, title;
            if (status == 1) {
                title = "Disable Service?";
                txt = "if press yes then disable this service and not display in user panel!";
            }
            else {
                title = "Enable Service?";
                txt = "if press yes then enable this service and display in user panel!";
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
                            url: '{{ route('get:admin:service_category_change_status') }}',
                            data: {id: id},
                            success: function (result) {
//                                if (result.success == true) {
//                                    location.reload();
//                                }
                                var new_id = '#service_cat_' + id;
                                if (result.status == 1) {
                                    $(new_id).prop("checked", true);
                                    $(new_id).attr("service_status", 1);
                                    swal("Success", "Enable Service Successfully", "success");
                                }
                                else {
                                    $(new_id).prop("checked", false);
                                    $(new_id).attr("service_status", 0);
                                    swal("Success", "Disable Service Successfully", "success");
                                }
                            }
                        })
                    } else {
                        if (status == 1) {
                            swal("Cancelled", "Service is Enable", "error");
                        }
                        else {
                            swal("Cancelled", "Service is Disable", "error");
                        }
                    }
                });
        });
    </script>
@endsection

