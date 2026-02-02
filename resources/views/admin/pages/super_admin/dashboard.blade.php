@extends('admin.layout.account_billing_admin')
@section('title')
    Dashboard
@endsection
@section('page-css')
    <link rel="stylesheet" href="{{ asset('/assets/css/widget/widget.css') }}">
    <style>
        .sos-st-card h5:after {
            width: 0;
        }

        .sos-st-card.green h3 span {
            /*border-color: #2ed8b678;*/
        }

        .sos-st-card.green h3, .sos-st-card.green h5 {
            color: #2ed8b6;
        }

        .sos-st-card.purple h3 span {
            /*border-color: #b881e67a;*/
        }

        .sos-st-card.purple h3, .sos-st-card.purple h5 {
            color: #b881e6;
        }

        .sos-st-card.blue h3 span {
            /*border-color: #4099ff85;*/
        }

        .sos-st-card.blue h3, .sos-st-card.blue h5 {
            color: #4099ff;
        }

        .sos-st-card.yellow h3 span {
            /*border-color: #ffb64d85;*/
        }

        .sos-st-card.yellow h3, .sos-st-card.yellow h5 {
            color: #FFB64D;
        }

        .sos-st-card.red h3 span {
            /*border-color: #ff537085;*/
        }

        .sos-st-card.red h3, .sos-st-card.red h5 {
            color: #FF5370;
        }

        .services .col-xl-3 {
            max-width: 20%;
        }

        .services .card-block {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .services h3 {
            font-size: 10px;
            font-weight: bold;
            /*padding-top: 10px;*/
        }

        .services h5 {
            padding-top: 5px;
            font-size: 16px;
        }

        .services .col-auto {
            padding: 0;
            padding-left: 5px;
        }

        .services .text-right {
            padding: 0 5px;
            padding-left: 7px;
        }

        .services .text-right {
            display: inline-block;
        }

        .sos-st-card h5:after {
            width: 0;
        }

        .latest-update-card .card-block .latest-update-box:after {
            width: 0;
        }

        .latest-update-box p {
            margin-bottom: .5rem;
        }

        .latest-update-box p span.offers {
            font-size: 16px;
            font-weight: 500;
            font-family: "Quicksand", sans-serif;
        }

        .dashboard-box {
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 5px 0 rgba(43, 43, 43, 0.1), 0 2px 6px -7px rgba(43, 43, 43, 0.1) !important;
        }

        .dashboard-title {
            padding: 1rem !important;
        }

        .dashboard-title h5 {
            font-size: 16px;
        }

        .order-list td, .table th {
            padding: 0.7rem 0.75rem !important;
        }
    </style>
@endsection
@section('page-content')

    {{--sidebar start--}}
    <div class="pcoded-content">
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-home bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Dashboard</h5>
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

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <div class="page-body">

                        <div class="row">
                            <div class="col-xl-12 col-md-12">
                                <div class="card dashboard-box">
                                    <div class="card-block dashboard-title">
                                        <h5>Today Summary</h5>
                                    </div>
                                </div>
                            </div>

                            <!-- product profit start -->
                            <div class="col-xl-3 col-md-6">
                                <div class="card prod-p-card card-blue">
                                    <div class="card-body">
                                        <div class="row align-items-center m-b-30" style="">
{{--                                        <div class="row align-items-center m-b-30" style="-webkit-margin-collapse: discard;">--}}
                                            <div class="col">
                                                <h6 class="m-b-5 text-white">Total Revenue</h6>
                                                <h3 class="m-b-0 f-w-700 text-white ">
                                                    <span class="currency"></span>
                                                    {{ isset($total_revenue) ? $total_revenue : 0 }}
{{--                                                    {{ isset($total_revenue) ? "400000.01 K" : 0 }}--}}
                                                </h3>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fa fa-money text-c-blue f-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card prod-p-card card-green">
                                    <div class="card-body">
                                        <div class="row align-items-center m-b-30">
                                            <div class="col">
                                                <h6 class="m-b-5 text-white">Complete Orders</h6>
                                                <h3 class="m-b-0 f-w-700 text-white">{{ isset($total_completed_order) ? $total_completed_order : 0 }}</h3>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-clipboard-check text-c-green f-18"></i>
                                                {{--<i class="fa fa-database text-c-blue f-18"></i>--}}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card prod-p-card card-red">
                                    <div class="card-body">
                                        <div class="row align-items-center m-b-30">
                                            <div class="col">
                                                <h6 class="m-b-5 text-white">Cancelled Orders</h6>
                                                <h3 class="m-b-0 f-w-700 text-white">{{ isset($total_cancelled_order) ? $total_cancelled_order : 0 }}</h3>
                                            </div>
                                            <div class="col-auto">
                                                {{--<i class="fa fa-dollar text-c-green f-18"></i>--}}
                                                {{--<i class="fa fa-database text-c-green f-18"></i>--}}
                                                <i class="fas fa-cart-arrow-down text-c-red f-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card prod-p-card card-yellow">
                                    <div class="card-body">
                                        <div class="row align-items-center m-b-30">
                                            <div class="col">
                                                <h6 class="m-b-5 text-white">Total Orders</h6>
                                                <h3 class="m-b-0 f-w-700 text-white">{{ isset($total_order) ? $total_order : 0 }}</h3>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fa fa-tags text-c-yellow f-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- product profit end -->

                            <div class="col-xl-12 col-md-12">
                                <div class="card dashboard-box">
                                    <div class="card-block dashboard-title">
                                        <h5>Total Order Of Services</h5>
                                    </div>
                                </div>
                            </div>
                            <!-- social statusric start -->
                            <div class="services col-xl-12">
                                <div class="row">

                                    @if(isset($service_category))
                                        @foreach($service_category as $category)
                                            <div class="col-xl-3 col-md-6">
                                                @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                                                    <a data-toggle="tooltip" data-placement="top" title="{{ strtoupper($category->name) }}" class="render_link"
                                                        @if(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1)
                                                            @if(isset($category) && in_array($category->category_type,[3,4]))
                                                                href="{{ route('get:admin:other_service_dashboard', $category->slug) }}"
                                                            @endif
                                                        @endif>
{{--                                                        <div class="card sos-st-card @if(isset($category) && $category->category_type == 1) blue @elseif(isset($category) && $category->category_type == 5) purple @elseif(isset($category) && $category->category_type == 2) red @elseif(isset($category) && $category->category_type == 3) green @elseif(isset($category) && $category->category_type == 4) green @endif ">--}}
                                                        <div class="card sos-st-card">

                                                            <div class="card-block">
                                                                <div class="row align-items-center">
                                                                    <div class="col-auto col-xl-4">

                                                                        <h3 class="m-b-0">
                                                                            <span>
                                                                                @if(file_exists(public_path('assets/images/service-category/'.$category->icon_name)))
                                                                                    <img src="{{ asset('assets/images/service-category/'.$category->icon_name."?v=0.1") }}" height="40" width="40">
                                                                                @else
                                                                                    <img src="{{ asset('assets/images/no_img.png') }}"  height="40" width="40">
                                                                                @endif
                                                                            </span>
                                                                        </h3>
                                                                    </div>
                                                                    <div class="col text-right col-xl-8">
                                                                        <h5 class="m-b-0">{{ isset($order_count[$category->id]) ? $order_count[$category->id] : 0 }}</h5>
                                                                        <h3 class="m-b-0">{{ strtoupper($category->name) }}</h3>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif

                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a data-toggle="tooltip" data-placement="top" title="Home"--}}

                                    {{--class="render_link"--}}
                                    {{--href="{{ route('get:admin:transport_service_dashboard', 'bike-ride') }}">--}}
                                    {{--<div class="card sos-st-card green">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}

                                    {{--<img src="{{ asset('assets/images/service-category/bike-ride-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">BIKE RIDE</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:transport_service_dashboard', 'taxi-ride') }}">--}}
                                    {{--<div class="card sos-st-card green">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/taxi-ride-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">TAXI RIDE</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:transport_service_dashboard', 'courier-service') }}">--}}
                                    {{--<div class="card sos-st-card purple">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/courier-service-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">COURIER SERVICE</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}

                                    {{--<!--delivery-->--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:store_service_dashboard', 'food-delivery') }}">--}}
                                    {{--<div class="card sos-st-card blue">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/food-delivery-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">FOOD DELIVERY</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:store_service_dashboard', 'grocery-delivery') }}">--}}
                                    {{--<div class="card sos-st-card blue">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/grocery-delivery-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">GROCERY DELIVERY</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:store_service_dashboard', 'liquor-delivery') }}">--}}
                                    {{--<div class="card sos-st-card blue">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/liquor-delivery-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">LIQUOR DELIVERY</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:store_service_dashboard', 'flower-delivery') }}">--}}
                                    {{--<div class="card sos-st-card blue">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/flower-delivery-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">FLOWER DELIVERY</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:store_service_dashboard', 'water-delivery') }}">--}}
                                    {{--<div class="card sos-st-card blue">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/water-delivery-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">WATER DELIVERY</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:store_service_dashboard', 'medicine-delivery') }}">--}}
                                    {{--<div class="card sos-st-card blue">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/medicine-delivery-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">MEDICINE DELIVERY</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}

                                    {{--<!--on demand part 1-->--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'dog-walking') }}">--}}
                                    {{--<div class="card sos-st-card yellow">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/dog-walking-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">DOG WALKING</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'baby-care') }}">--}}
                                    {{--<div class="card sos-st-card yellow">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/baby-care-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">BABY CARE</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'pet-care') }}">--}}
                                    {{--<div class="card sos-st-card yellow">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/pet-care-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">PET CARE</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'workout-trainer') }}">--}}
                                    {{--<div class="card sos-st-card yellow">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/workout-trainer-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">WORKOUT TRAINER</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'security-service') }}">--}}
                                    {{--<div class="card sos-st-card yellow">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/security-service-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">SECURITY SERVICE</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'tutors') }}">--}}
                                    {{--<div class="card sos-st-card yellow">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/tutors-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">TUTORS</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'beauty-services') }}">--}}
                                    {{--<div class="card sos-st-card yellow">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/beauty-services-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">BEAUTY SERVICES</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'massages-services') }}">--}}
                                    {{--<div class="card sos-st-card yellow">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/massages-service-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">MASSAGES SERVICES</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}

                                    {{--<!--on demand part 2-->--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'home-cleaning') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/home-cleaning-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">HOME CLEANING</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'gardening') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/gardening-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">GARDENING</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'snow-blowers') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/snow-blowers-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">SNOW BLOWERS</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'laundry-service') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/laundry-service-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">LAUNDRY SERVICE</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'maid-service') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/maid-service-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">MAID SERVICE</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'pest-control') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/pest-control-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">PEST CONTROL</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'ac-repair' ) }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/ac-repair-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">AC REPAIR</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'electricians') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/electricians-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">ELECTRICIANS</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'car-wash') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/car-wash-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">CAR WASH</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'car-repair') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/car-repair-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">CAR REPAIR</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'tow-truck') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/tow-truck-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">TOW TRUCK</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}
                                    {{--<div class="col-xl-3 col-md-6">--}}
                                    {{--<a title="Home" class="render_link"--}}
                                    {{--href="{{ route('get:admin:other_service_dashboard', 'plum-bers') }}">--}}
                                    {{--<div class="card sos-st-card red">--}}
                                    {{--<div class="card-block">--}}
                                    {{--<div class="row align-items-center">--}}
                                    {{--<div class="col-auto col-xl-4">--}}
                                    {{--<h3 class="m-b-0">--}}
                                    {{--<span>--}}
                                    {{--<img src="{{ asset('assets/images/service-category/plumbers-s.png') }}">--}}
                                    {{--</span>--}}
                                    {{--</h3>--}}
                                    {{--</div>--}}
                                    {{--<div class="col text-right col-xl-8">--}}
                                    {{--<h5 class="m-b-0">50</h5>--}}
                                    {{--<h3 class="m-b-0">PLUMBERS</h3>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    {{--</a>--}}
                                    {{--</div>--}}

                                </div>
                            </div>
                            <!-- social statusric end -->


                            <!-- sale 2 card start -->
                            {{--<div class="col-md-12 col-xl-4">--}}
                            {{--<div class="card card-blue text-white">--}}
                            {{--<div class="card-block p-b-0">--}}
                            {{--<div class="row m-b-50">--}}
                            {{--<div class="col">--}}
                            {{--<h6 class="m-b-5">Sales In July</h6>--}}
                            {{--<h5 class="m-b-0 f-w-700">$2665.00</h5>--}}
                            {{--</div>--}}
                            {{--<div class="col-auto text-center">--}}
                            {{--<p class="m-b-5">Direct Sale</p>--}}
                            {{--<h6 class="m-b-0">$1768</h6>--}}
                            {{--</div>--}}

                            {{--<div class="col-auto text-center">--}}
                            {{--<p class="m-b-5">Referal</p>--}}
                            {{--<h6 class="m-b-0">$897</h6>--}}
                            {{--</div>--}}
                            {{--</div>--}}

                            {{--<div id="sec-ecommerce-chart-line" class=""--}}
                            {{--style="height:60px"></div>--}}
                            {{--<div id="sec-ecommerce-chart-bar" style="height:195px"></div>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="col-xl-4 col-md-12">--}}
                            {{--<div class="card latest-update-card">--}}
                            {{--<div class="card-header">--}}
                            {{--<h5>What’s New</h5>--}}
                            {{--<div class="card-header-right">--}}
                            {{--<ul class="list-unstyled card-option">--}}
                            {{--<li class="first-opt"><i--}}
                            {{--class="feather icon-chevron-left open-card-option"></i>--}}
                            {{--</li>--}}
                            {{--<li><i class="feather icon-maximize full-card"></i></li>--}}
                            {{--<li><i class="feather icon-minus minimize-card"></i>--}}
                            {{--</li>--}}
                            {{--<li><i class="feather icon-refresh-cw reload-card"></i>--}}
                            {{--</li>--}}
                            {{--<li><i class="feather icon-trash close-card"></i></li>--}}
                            {{--<li>--}}
                            {{--<i class="feather icon-chevron-left open-card-option"></i>--}}
                            {{--</li>--}}
                            {{--</ul>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="card-block">--}}
                            {{--<div class="scroll-widget">--}}
                            {{--<div class="latest-update-box">--}}
                            {{--<div class="row p-t-20 p-b-30">--}}
                            {{--<div class="col-auto text-right update-meta p-r-0">--}}
                            {{--<i class="fa fa-gift bg-c-red update-icon"></i>--}}
                            {{--</div>--}}
                            {{--<div class="col p-l-5">--}}
                            {{--<a href="#!"><p><span class="offers">Diwali Sale</span> <span--}}
                            {{--class="pull-right text-mute">12-14 Oct</span>--}}
                            {{--</p></a>--}}
                            {{--<p class="text-muted m-b-0">100% CashBack offers </p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="row p-b-30">--}}
                            {{--<div class="col-auto text-right update-meta p-r-0">--}}
                            {{--<i class="fa fa-shopping-cart bg-c-green update-icon"></i>--}}
                            {{--</div>--}}
                            {{--<div class="col p-l-5">--}}
                            {{--<a href="#!"><p><span class="offers">Big Sale</span> <span--}}
                            {{--class="pull-right text-mute">17-21 Sept</span>--}}
                            {{--</p></a>--}}
                            {{--<p class="text-muted m-b-0">40% Off</p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="row p-b-30">--}}
                            {{--<div class="col-auto text-right update-meta p-r-0">--}}
                            {{--<i class="fa fa-gift bg-c-red update-icon"></i>--}}
                            {{--</div>--}}
                            {{--<div class="col p-l-5">--}}
                            {{--<a href="#!"><p><span class="offers">Diwali Sale</span> <span--}}
                            {{--class="pull-right text-mute">12-14 Oct</span>--}}
                            {{--</p></a>--}}
                            {{--<p class="text-muted m-b-0">100% CashBack offers </p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="row p-b-30">--}}
                            {{--<div class="col-auto text-right update-meta p-r-0">--}}
                            {{--<i class="fa fa-gift bg-c-yellow update-icon"></i>--}}
                            {{--</div>--}}
                            {{--<div class="col p-l-5">--}}
                            {{--<a href="#!"><p><span class="offers">Christmas Offers</span>--}}
                            {{--<span class="pull-right text-mute">24-28 Dec</span>--}}
                            {{--</p></a>--}}
                            {{--<p class="text-muted m-b-0">40% Off</p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="row p-b-30">--}}
                            {{--<div class="col-auto text-right update-meta p-r-0">--}}
                            {{--<i class="fa fa-shopping-cart bg-c-green update-icon"></i>--}}
                            {{--</div>--}}
                            {{--<div class="col p-l-5">--}}
                            {{--<a href="#!"><p><span class="offers">Big Sale</span> <span--}}
                            {{--class="pull-right text-mute">17-21 Sept</span>--}}
                            {{--</p></a>--}}
                            {{--<p class="text-muted m-b-0">40% Off</p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="row p-b-30">--}}
                            {{--<div class="col-auto text-right update-meta p-r-0">--}}
                            {{--<i class="fa fa-gift bg-c-yellow update-icon"></i>--}}
                            {{--</div>--}}
                            {{--<div class="col p-l-5">--}}
                            {{--<a href="#!"><p><span class="offers">Christmas Offers</span>--}}
                            {{--<span class="pull-right text-mute">24-28 Dec</span>--}}
                            {{--</p></a>--}}
                            {{--<p class="text-muted m-b-0">40% Off</p>--}}
                            {{--</div>--}}
                            {{--</div>--}}


                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="col-xl-4 col-md-6">--}}
                            {{--<div class="card latest-update-card">--}}
                            {{--<div class="card-header">--}}
                            {{--<h5>Recent Order</h5>--}}
                            {{--<div class="card-header-right">--}}
                            {{--<ul class="list-unstyled card-option">--}}
                            {{--<li class="first-opt"><i--}}
                            {{--class="feather icon-chevron-left open-card-option"></i>--}}
                            {{--</li>--}}
                            {{--<li><i class="feather icon-maximize full-card"></i></li>--}}
                            {{--<li><i class="feather icon-minus minimize-card"></i>--}}
                            {{--</li>--}}
                            {{--<li><i class="feather icon-refresh-cw reload-card"></i>--}}
                            {{--</li>--}}
                            {{--<li><i class="feather icon-trash close-card"></i></li>--}}
                            {{--<li>--}}
                            {{--<i class="feather icon-chevron-left open-card-option"></i>--}}
                            {{--</li>--}}
                            {{--</ul>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="card-block">--}}
                            {{--<div class="scroll-widget">--}}

                            {{--<div class="table-responsive">--}}
                            {{--<table class="table table-hover m-b-0 without-header order-list">--}}
                            {{--<tbody>--}}
                            {{--<tr>--}}
                            {{--<td>--}}
                            {{--<div class="d-inline-block align-middle">--}}
                            {{--<img src="{{ asset('assets/images/avatar-4.jpg') }}"--}}
                            {{--alt="user image"--}}
                            {{--class=" img-40 align-top m-r-15">--}}
                            {{--<div class="d-inline-block">--}}
                            {{--<h6>Lorem Ipsum</h6>--}}
                            {{--<p class="text-muted m-b-0">$450.001</p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</td>--}}
                            {{--</tr>--}}
                            {{--<tr>--}}
                            {{--<td>--}}
                            {{--<div class="d-inline-block align-middle">--}}
                            {{--<img src="{{ asset('assets/images/avatar-4.jpg') }}"--}}
                            {{--alt="user image"--}}
                            {{--class=" img-40 align-top m-r-15">--}}
                            {{--<div class="d-inline-block">--}}
                            {{--<h6>Lorem Ipsum</h6>--}}
                            {{--<p class="text-muted m-b-0">$450.001</p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</td>--}}
                            {{--</tr>--}}
                            {{--<tr>--}}
                            {{--<td>--}}
                            {{--<div class="d-inline-block align-middle">--}}
                            {{--<img src="{{ asset('assets/images/avatar-4.jpg') }}"--}}
                            {{--alt="user image"--}}
                            {{--class=" img-40 align-top m-r-15">--}}
                            {{--<div class="d-inline-block">--}}
                            {{--<h6>Lorem Ipsum</h6>--}}
                            {{--<p class="text-muted m-b-0">$450.001</p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</td>--}}
                            {{--</tr>--}}

                            {{--<tr>--}}
                            {{--<td>--}}
                            {{--<div class="d-inline-block align-middle">--}}
                            {{--<img src="{{ asset('assets/images/avatar-4.jpg') }}"--}}
                            {{--alt="user image"--}}
                            {{--class=" img-40 align-top m-r-15">--}}
                            {{--<div class="d-inline-block">--}}
                            {{--<h6>Lorem Ipsum</h6>--}}
                            {{--<p class="text-muted m-b-0">$450.001</p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</td>--}}
                            {{--</tr>--}}
                            {{--<tr>--}}
                            {{--<td>--}}
                            {{--<div class="d-inline-block align-middle">--}}
                            {{--<img src="{{ asset('assets/images/avatar-4.jpg') }}"--}}
                            {{--alt="user image"--}}
                            {{--class=" img-40 align-top m-r-15">--}}
                            {{--<div class="d-inline-block">--}}
                            {{--<h6>Lorem Ipsum</h6>--}}
                            {{--<p class="text-muted m-b-0">$450.001</p>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</td>--}}
                            {{--</tr>--}}
                            {{--</tbody>--}}
                            {{--</table>--}}
                            {{--</div>--}}

                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ style Customizer ] start -->
    <div id="styleSelector">
    </div>
    <!-- [ style Customizer ] end -->
    {{--sidebar end--}}

@endsection
@section('page-js')
    @include('admin.pages.report_issue.chat_script')
    <script type="text/javascript" src="{{ asset('assets/js/chart/jquery.flot.js')}}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/chart/amcharts.js')}}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/chart/serial.js')}}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/chart/light.js')}}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/chart/custom-dashboard.min.js')}}"></script>

    <script>
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip({'placement': 'top'});
        });
    </script>
@endsection

