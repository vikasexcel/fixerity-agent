@extends('admin.layout.super_admin')
@section('title')
    {{ ucwords(strtolower($service_category->name)) }}
@endsection
@section('page-css')
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css"
          integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('/assets/css/widget/widget.css') }}">
    <style>
        .card-padding {
            padding: 15px 10px;
            float: left;
        }

        .card-body {
            padding: 1.25rem;
        }

        .card-transparent {
            background-color: transparent;
            border: none;
            box-shadow: none;
            margin: -15px;
        }

        .card-main-padding {
            padding: 8px;
        }

        .card-padding .col, .card-padding .col-auto {
            padding: 0 10px;
        }

        @php $category_type = \App\Http\Controllers\OtherServiceController::checkCategoryType($slug); @endphp
    @if($category_type == 3)
        #menu a:hover {
            color: #ffb64d;
            cursor: pointer;
        }

        #menu ul li a:hover i {
            color: #ffb64d;
        }

        @elseif($category_type == 4)
            #menu a:hover {
            color: #FF5370;
            cursor: pointer;
        }

        #menu ul li a:hover i {
            color: #FF5370;
        }

        @else
            #menu a:hover {
            color: #42a5f5;
            cursor: pointer;
        }

        #menu ul li a:hover i {
            color: #42a5f5;
        }
        @endif
    </style>
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
                        <i class="feather icon-home bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>{{ ucwords(strtolower($service_category->name)) }}</h5>
                            <span>Summary</span>
                        </div>
                    </div>
                </div>
                {{--<div class="col-lg-4">--}}
                {{--<div class="page-header-breadcrumb">--}}
                {{--<ul class=" breadcrumb breadcrumb-title">--}}
                {{--<li class="breadcrumb-item">--}}
                {{--<a href=""><i class="feather icon-home"></i> Dashboard</a>--}}
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
                    <div class="page-body">
                        <!-- [ page content ] start -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card sale-card card-transparent">
                                    {{--<div class="card-header">--}}
                                    {{--<h5>Deals Analytics</h5>--}}
                                    {{--</div>--}}
                                    {{--<div class="card-block">--}}
                                    <div class="col-md-12 card-main-padding">
                                        <div class="col-md-3 card-padding">
                                            <div class="card prod-p-card">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h6 class="m-b-5">Approved Providers</h6>
                                                            <h3 class="m-b-0 f-w-700"> {{ isset($approved_provider) ? $approved_provider : 0 }} </h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-user-check @if($service_category->category_type == 3) text-c-yellow @else text-c-red @endif f-18"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 card-padding">
                                            <div class="card prod-p-card">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h6 class="m-b-5">Live Providers</h6>
                                                            <h3 class="m-b-0 f-w-700"> {{ isset($live_provider) ? $live_provider : 0 }} </h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-business-time @if($service_category->category_type == 3) text-c-yellow @else text-c-red @endif f-18"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 card-padding">
                                            <div class="card prod-p-card">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h6 class="m-b-5">Pending Providers</h6>
                                                            <h3 class="m-b-0 f-w-700"> {{ isset($pending_provider) ? $pending_provider : 0 }} </h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-user-plus @if($service_category->category_type == 3) text-c-yellow @else text-c-red @endif f-18"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 card-padding">
                                            <div class="card prod-p-card">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h6 class="m-b-5">Earnings
                                                                {{--Last 7-Days--}}
                                                            </h6>
                                                            <h3 class="m-b-0 f-w-700 "> <span class="currency"></span>{{ isset($total_amount) ? $total_amount : 0 }} </h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-money-bill-alt @if($service_category->category_type == 3) text-c-yellow @else text-c-red @endif f-18"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{--</div>--}}
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card sale-card card-transparent">
                                    <div class="card-header">
                                        <h5>Last 7-Days {{ ucwords(strtolower($service_category->name)) }}
                                            Statistics</h5>
                                    </div>
                                    {{--<div class="card-block">--}}
                                    <div class="col-md-12 card-main-padding">
                                        <div class="col-md-3 card-padding">
                                            <div class="card prod-p-card">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h6 class="m-b-5">Total Orders</h6>
                                                            <h3 class="m-b-0 f-w-700"> {{ isset($total_orders) ? $total_orders : 0 }} </h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-shopping-bag @if($service_category->category_type == 3) text-c-yellow @else text-c-red @endif f-18"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 card-padding">
                                            <div class="card prod-p-card">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h6 class="m-b-5">Completed Orders</h6>
                                                            <h3 class="m-b-0 f-w-700"> {{ isset($completed_orders) ? $completed_orders : 0 }} </h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-check @if($service_category->category_type == 3) text-c-yellow @else text-c-red @endif f-18"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 card-padding">
                                            <div class="card prod-p-card">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h6 class="m-b-5">Running Orders</h6>
                                                            <h3 class="m-b-0 f-w-700"> {{ isset($running_orders) ? $running_orders : 0 }} </h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-spinner @if($service_category->category_type == 3) text-c-yellow @else text-c-red @endif f-18"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 card-padding">
                                            <div class="card prod-p-card">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col">
                                                            <h6 class="m-b-5">Cancelled Orders</h6>
                                                            <h3 class="m-b-0 f-w-700"> {{ isset($cancelled_orders) ? $cancelled_orders : 0 }} </h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-times @if($service_category->category_type == 3) text-c-yellow @else text-c-red @endif f-18"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{--</div>--}}
                                </div>
                            </div>
                        </div>
                        <!-- [ page content ] end -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('page-js')
    {{--    <script type="text/javascript" src="{{ asset('assets/js/chart/jquery.flot.js')}}"></script>--}}
    {{--    <script type="text/javascript" src="{{ asset('assets/js/chart/amcharts.js')}}"></script>--}}
    {{--    <script type="text/javascript" src="{{ asset('assets/js/chart/serial.js')}}"></script>--}}
    {{--    <script type="text/javascript" src="{{ asset('assets/js/chart/light.js')}}"></script>--}}
    {{--    <script type="text/javascript" src="{{ asset('assets/js/chart/custom-dashboard.min.js')}}"></script>--}}
@endsection

