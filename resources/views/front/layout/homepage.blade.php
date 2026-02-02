@php $general_settings = \App\Models\GeneralSettings::first() @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ isset($general_settings)? (($general_settings->website_name != Null)?$general_settings->website_name:"Fixerity"):"Fixerity" }}</title>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicons -->
    <link rel="icon"
          href="{{ isset($general_settings)? ($general_settings->website_favicon != Null) ? asset('assets/images/website-logo-icon/'.$general_settings->website_favicon) : asset('assets/images/website-logo-icon/fevicon.ico')  : asset('assets/images/website-logo-icon/fevicon.ico') }}"
          type="image/x-icon">
    <link href="{{ isset($general_settings)? ($general_settings->website_favicon != Null) ? asset('assets/images/website-logo-icon/'.$general_settings->website_favicon) : asset('assets/images/website-logo-icon/fevicon.ico')  : asset('assets/images/website-logo-icon/fevicon.ico') }}" rel="fox-jek">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,700|Open+Sans:300,300i,400,400i,700,700i"
          rel="stylesheet">

    <!-- Bootstrap CSS File -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Libraries CSS Files -->
    <link href="{{ asset('assets/front/css/animate.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/front/css/ionicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/front/css/magnific-popup.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/front/js/jquery-3.2.1.min.js') }}" type="text/javascript"></script>
    <link href="{{ asset('assets/front/css/style_home.css?v=0.07') }}" rel="stylesheet">

    <!-- Main Stylesheet File -->
    <script src="//maps.googleapis.com/maps/api/js?key={{ isset($general_settings->map_key)? ($general_settings->map_key != Null)? $general_settings->map_key : 0 : 0 }}&libraries=places"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css"
          integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
    <style>
        #logo img {
            width: 180px;
        }

        .wow h1 {
            margin-top: 20px !important;
        }

        .product-screen-1 img, .product-screen-2 img, .product-screen-3 img {
            width: 280px;
        }

        #about .about-img {
            height: 450px;
        }

        .app-store {
            width: 200px;
        }

        .padding-3 {
            padding-top: 3px;
        }
    </style>
    @yield('page-css')
    <style>

        #header {
            background: white;
            padding: 16px 0;
            height: 80px;
            border-bottom: solid 1px #ddd;
        }

        #header.header-fixed {
            background: rgba(255, 255, 255, 0.8);
        }

        .nav-menu a {
            color: black;
        }

        #header #logo h1 {
            margin: -10px 0 0 0;
        }

        .btn-policy{
            padding: 0 !important;
            background: var(--sitecolor) !important;
            color: #000000 !important;
            text-align: center !important;
            /*padding-left: 20% !important;*/
        }
        .btn-policy:active, .btn-policy:focus, .btn-policy:hover {
            background: #FFF !important;
            color: var(--sitecolor) !important;
        }
        .btn-policy::after {
            content: "";
            padding-left: 0 !important;
        }
        /*contact*/
        #contact .contact-about h3 {
            color: var(--sitecolor);
        }

        #contact .info i {
            color: var(--sitecolor);
        }

        #contact .social-links a {
            color: var(--sitecolor);
            border: 1px solid var(--sitecolor);
        }

        #contact .social-links a:hover {
            background: var(--sitecolor);
            color: #fff;
        }

        /*back to top*/
        .back-to-top {
            background: linear-gradient(45deg, #aba7a7, var(--sitecolor));
        }

        .back-to-top:focus {
            background: linear-gradient(45deg, #ABA7A7, var(--sitecolor));
        }

        .back-to-top:hover {
            background: var(--sitecolor);
        }

        {{--/*new*/--}}
        {{--/*header*/--}}
        {{--#header #logo h1 {--}}
        {{--    margin: -12px 0 0 0;--}}
        {{--    /*margin: -4px 0 0 0;*/--}}
        {{--}--}}
{{--        #header.header-fixed {--}}
{{--            background: linear-gradient(45deg, #41e28e, var(--sitecolor));--}}
{{--        }--}}
{{--        /*intro*/--}}
        #intro {
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)) , url({{ asset('/assets/front/img/png/sileder_1.png') }}) center top no-repeat;
{{--            background:  url({{ asset('/assets/front/img/png/sileder_1.png') }}) center top no-repeat;--}}
            background-size: cover;
            position: relative;
            height: 780px;
            width: 100%;
            /* background-size: cover; */

        }
        .providerTextMainCls{
            background:url({{ asset('/assets/front/img/png/geddit-delivery-bg.jpg') }}) center top no-repeat;
            background-size: cover;
            position: relative;
            height: 500px;
            width: 100%;
        }

        #intro .btn-get-started:hover {
            color: var(--sitecolor);
            background: #fff;
        }

        /*call to action*/
        #call-to-action {
            overflow: hidden;
            {{--background: linear-gradient(rgba(188, 28, 145, 0.65), rgba(188, 28, 145, 0.2)), url({{asset('/assets/front/img/call-to-action-bg.jpg')}}) fixed center center;--}}
            background-color: #F7F7F7;
            background-size: cover;
            padding: 30px 0;
        }

        #call-to-action .cta-btn:hover {
            background: #ffffff;
            color: var(--sitecolor);
            border: 2px solid #ffffff;
        }

        /*section header divider*/
        .section-header .section-divider {
            background: linear-gradient(0deg, #f7b7e6 0%, var(--sitecolor) 100%);
        }
        /*copyright*/
        .copyright {
            text-align: center !important;
            width: 100%;
        }

        .copyright a {
            padding: 0;
            color: #cd57ae;
            font-size: 12px;
        }

        .copyright a:active, .copyright a:focus, .copyright a:hover {
            color: #000;
        }

        .copyright a::after {
            content: "|";
            padding-left: 5px;
        }

        .copyright a:last-of-type::after {
            content: "";

        }
        .action-btn{
            background-color: #FFAA01;
            color: #FFFFFF !important;
            padding: 5px 25px 5px 25px;
            border-radius: 25px;
        }
        .btn-action{
            text-align: left;
            list-style: none;
            padding-inline-start: 0;
        }
        .currency:before {
            content: '$';
            letter-spacing: 2px;
        }

    </style>
    <style>
        .signle-service:hover {
            /*box-shadow: 3px 3px 3px 3px rgb(0 0 0 / 20%);*/
        }
        .icon-color{
            color: #4caf50;
        }

    </style>

</head>
<body>


@include('front.layout.nav')


<!--==========================
  Intro Section
============================-->
<section id="intro">
    <div class="container col-xxl-8 px-4 header-main px-4 py-5">
        <div class="row flex-lg-row-reverse align-items-center g-5 py-4">
            <div class="col-10 col-sm-8 col-lg-6 col-md-6 product-screens">
                <img src="{{ asset('assets/front/img/png/Mockup8.png?v=0.3') }}" class="d-block mx-lg-auto " alt="Bootstrap Themes" height="590" loading="lazy">
            </div>
            <div class="col-lg-6 col-md-6 col-sm-4 ">
                <h1 class="display-5 fw-bold lh-1 mb-3 font-weight-bold">Your life made simple and convenient</h1>
                <p class="lead">Order a Taxi, Food, Groceries, Medicine, Delivery Of Parcels</p>
                <p class="lead">Delivered To You - Quick, Reliable And Affordable</p>
                <div class="header-btn">
                    <a class="mr-2 " href="{{ isset($general_settings)? (isset($general_settings->user_playstore_link) && $general_settings->user_playstore_link != Null)? $general_settings->user_playstore_link : "" : "" }}" target="_blank">
                        <img class="img-fluid  " src="{{ asset('assets/front/img/png/google-play-badge-black.png') }}">
                    </a>
                    <a  href="{{ isset($general_settings)? (isset($general_settings->user_appstore_link) && $general_settings->user_appstore_link != Null)? $general_settings->user_appstore_link : "" : "" }}" target="_blank">
                        <img class="img-fluid " src="{{ asset('assets/front/img/png/app-store-badge-black.png') }}">
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>


<main id="main">

    <!--==========================
    New Design
    ============================-->

    <div class="main-service">
        <div class="container">
            <div class="px-4 my-3 text-center border-bottom">
                <h1 class="service-main-title" >Our Services</h1>
                <div class="col-lg-6 mx-auto">
                    <p class="lead mb-4 main-desc">Dive into each service to understand them better. With this App, you’ll be able to make the most of each of these aspects.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-12 mb-4 wow fadeInLeft" data-wow-delay="0.1s">
                    <div class="card card-main" >
                        <div class="card-image">
                            <img src="{{ asset('assets/front/img/png/taxi.jpg') }}" class="card-img-top homepage-service-image" >
                            <div class="image-overlay"></div>
                            <div class="middle">
                                <a href="{{ route('get:transport-service-booking',['taxi-ride']) }}" class="booknow-btn">Place Order</a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h4 class="card-title card-main-title">Taxi Booking</h4>
                            <p class="card-text card-desc">With taxi booking services, customer has able to book their ride whenever and wherever they want.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-12 mb-4  wow fadeInDown" data-wow-delay="0.1s">
                    <div class="card card-main" >
                        <div class="card-image">
                            <img src="{{ asset('assets/front/img/png/bike.jpg') }}" class="card-img-top homepage-service-image" >
                            <div class="image-overlay"></div>
                            <div class="middle">
                                <a href="{{ route('get:transport-service-booking',['bike-ride']) }}" class="booknow-btn">Place Order</a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h4 class="card-title card-main-title">Bike Booking</h4>
                            <p class="card-text card-desc">Want to bike ride? book a bike and enjoy your ride without any hassle.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-12 mb-4 wow fadeInRight" data-wow-delay="0.1s">
                    <div class="card card-main" >
                        <div class="card-image">
                            <img src="{{ asset('assets/front/img/png/delivery.jpg') }}" class="card-img-top homepage-service-image" >
                            <div class="image-overlay"></div>
                            <div class="middle">
                                <a href="{{ route('get:storehomepage',['food-delivery']) }}" class="booknow-btn">Place Order</a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h4 class="card-title card-main-title">Food Delivery</h4>
                            <p class="card-text card-desc">Get the portion of your favourite meal at your doorstep using the food delivery service.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-12 mb-4 wow fadeInLeft" data-wow-delay="0.1s">
                    <div class="card card-main" >
                        <div class="card-image">
                            <img src="{{ asset('assets/front/img/png/courier.jpg') }}" class="card-img-top homepage-service-image" >
                            <div class="image-overlay"></div>
                            <div class="middle">
                                <a href="{{ route('get:transport-service-booking',['courier-service']) }}" class="booknow-btn">Place Order</a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h4 class="card-title card-main-title">Courier Delivery</h4>
                            <p class="card-text card-desc">Make sure to send/receive couriers and parcels across the cities or outside. </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-12 mb-4 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="card card-main" >
                        <div class="card-image">
                            <img src="{{ asset('assets/front/img/png/grocery.jpg') }}" class="card-img-top homepage-service-image" >
                            <div class="image-overlay"></div>
                            <div class="middle">
                                <a href="{{ route('get:storehomepage',['grocery-delivery']) }}" class="booknow-btn">Place Order</a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h4 class="card-title card-main-title">Grocery Delivery</h4>
                            <p class="card-text card-desc">With grocery delivery service, facilitate ordering fresh groceries from different stores.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-12 mb-4 wow fadeInRight" data-wow-delay="0.1s">
                    <div class="card card-main" >
                        <div class="card-image">
                            <img src="{{ asset('assets/front/img/png/cleaning.jpg') }}" class="card-img-top homepage-service-image" >
                            <div class="image-overlay"></div>
                            <div class="middle">
                                <a href="{{ route('get:service-booking',['home-cleaning']) }}" class="booknow-btn">Place Order</a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h4 class="card-title card-main-title">Home Cleaning</h4>
                            <p class="card-text card-desc">Need help to clean your home? Make your house clean and tidy with our house cleaning services.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{--    @include('front.layout.simple-home-texi-section')--}}

    <div class="main-service-second pb-5" >
        <div class="container">
            <div class="px-4 my-3 text-center border-bottom">
                <h1 class="service-main-title" >More Services</h1>
            </div>

            <div class="row style-alt wow zoomIn"  data-wow-delay="0.1s">
                @foreach($service_list as $singel_service_list)
                    <div class="col-sm-6 col-lg-2 col-md-2 col-6 signle-service">
                        <a href="{{ route('get:service-booking',[$singel_service_list->slug]) }}" title="{{ ucfirst($singel_service_list->name)  }}" target="_blank">
                            <div class="widget">
                                <div class="widget-simple">
                                    @if(file_exists(public_path('assets/images/service-category/'.$singel_service_list->icon_name)))
                                        <img src="{{ asset('assets/images/service-category/'.$singel_service_list->icon_name) }}" alt="avatar" class="widget-image" >
                                    @else
                                        <img src="{{ asset('assets/images/service-category/no_service_cateogry.png') }}" alt="avatar" class="widget-image" >
                                    @endif

                                    <h5 class="widget-content">
                                        {{ ucfirst($singel_service_list->name)  }}
                                    </h5>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if(count($store_details_new_data) > 0)
        @foreach($store_details_new_data as $keys=>$single_service)
            @if(!empty($single_service['data']))


                <div class="main-service-third mb-5 pt-5 wow {{ ($keys % 2 == 0)?"fadeInLeft":"fadeInRight" }} " data-wow-delay="0.1s">
                    <div id="food_delivery" class="container store-container">
                        <div class="row">
                            <div class="col-md-12">
                                <h3 class="cat-title">{{ $single_service['service_cat_name'] }}</h3>
                                <a href="{{ route('get:search_restaurants_list',[$single_service['service_cat_slug'],"all"]) }}" title="View All"><span class="view-all-link">view all</span></a>
                            </div>
                        </div>
                        <div class="row ">
                            @if($single_service['data'])
                                @php
                                    $flg_stp = 0;
                                @endphp
                                @foreach($single_service['data'] as $store_key =>$store)
                                    @php
                                        if($flg_stp < 3) {
                                    @endphp
                                        <div class="col-lg-4 col-md-6 col-sm-6 store-main-box ">
                                            <div class="card service-card card-height" >
                                                @if(file_exists(public_path('assets/images/store-images/'.$store['image'])))
                                                    <img src="{{ asset('assets/images/store-images/'.$store['image']) }}" class="card-img-top store-image" alt="{{ $store['name'] }}">
                                                @else
                                                    <img src="{{ asset('assets/images/store-images/no_store_img.png') }}" class="card-img-top store-image" alt="{{ $store['name'] }}">
                                                @endif
                                                <div class="card-body store-detail-body">
                                                    <a href="{{ route('get:restaurant_data',[$store['id']]) }}" target="_blank">
                                                        <h5 class="card-title store-name">{{ $store['name'] }}</h5>
                                                        <h6 class="card-subtitle mb-2 text-muted  ">
                                                            @if($single_service['cuisines'])

                                                                @foreach($single_service['cuisines'] as $keyss=>$cuisines_all)
                                                                    @foreach($cuisines_all as $key=>$cuisine)

                                                                        @if($cuisine['store_id'] == $store['id'])
                                                                            @if($key<3)
                                                                                {{ $cuisine['name']."," }}
                                                                            @elseif($key==3)
                                                                                , More..
                                                                            @endif
                                                                        @endif

                                                                    @endforeach
                                                                @endforeach

                                                            @endif
                                                        </h6>
                                                        <div class="about">
                                                            <span class="rate"><i class="fa fa-star"></i> {{ $store['ratings'] }} </span>
                                                            <p class="time">
                                                                @if($single_service['res_time'])
                                                                    {{ $single_service['res_time'][$keys][$store['id']] }}
                                                                @endif
                                                            </p>
                                                            <p class="contact"><i class="fa fa-phone icon-color "></i> {{ App\Models\User::ContactNumber2Stars($store['contact_details']) }}</p>
                                                        </div>

                                                        @if( $store['offer_min_amount'] != 0 && $store['offer_amount'] != 0)
                                                            <div class="discount">
                                                                @if($store['offer_type'] == 1)
                                                                    <p class="discount-string">
                                                                        <img src="{{asset('assets/front/img/icons/discount-24.png')}}" alt="{{ $store['name'] }}"> {{ "$ ".$store['offer_amount'] }}
                                                                        off on orders above $ {{ $store['offer_min_amount'] }}
                                                                    </p>
                                                                @else
                                                                    <p class="discount-string">
                                                                        <img src="{{asset('assets/front/img/icons/discount-24.png')}}" alt="{{ $store['name'] }}"> {{ $store['offer_amount'] . '%' }}
                                                                        off on orders above $ {{ $store['offer_min_amount'] }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        @elseif($store['offer_amount'] != 0 && $store['offer_min_amount'] == 0)
                                                            <div class="discount">
                                                                @if($store['offer_type'] == 1)
                                                                    <p class="discount-string">
                                                                        <img src="{{asset('assets/front/img/icons/discount-24.png')}}" alt="{{ $store['name'] }}"> {{ "$ ".$store['offer_amount'] }}
                                                                        off on all orders
                                                                    </p>
                                                                @else
                                                                    <p class="discount-string">
                                                                        <img src="{{asset('assets/front/img/icons/discount-24.png')}}" alt="{{ $store['name'] }}"> {{ $store['offer_amount'].'%' }}
                                                                        off on all orders
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @php
                                        }
                                        $flg_stp++;

                                    @endphp
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

            @endif
        @endforeach
    @endif



{{--    <div class="main-service-third mb-5 pt-2">--}}
{{--        <div id="food_delivery" class="container store-container">--}}
{{--            <div class="row">--}}
{{--                <div class="col-md-12">--}}
{{--                    <h3 class="cat-title">Food Delivery</h3>--}}
{{--                    <a href="{{ route('get:search_restaurants_list',['food-delivery',"all"]) }}" title="View All"><span class="view-all-link">view all</span></a>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="row">--}}
{{--                <div class="col-lg-4 col-md-6 col-sm-6 store-main-box">--}}
{{--                    <div class="card service-card card-height">--}}
{{--                        <img src="{{ asset('assets/front/img/png/f1.jpg') }}" class="card-img-top store-image" alt="McDonald's">--}}
{{--                        <div class="card-body store-detail-body">--}}
{{--                            <a href="#" target="_blank">--}}
{{--                                <h5 class="card-title store-name">McDonalds's</h5>--}}
{{--                                <h6 class="card-subtitle mb-2 text-muted  ">Asian,--}}
{{--                                </h6>--}}
{{--                                <div class="about">--}}
{{--                                    <span class="rate"><i class="fa fa-star"></i> 0 </span>--}}
{{--                                    <p class="time">--}}
{{--                                        Opening Times: 11:30 AM to 11:55 PM--}}
{{--                                    </p>--}}
{{--<!--                                    <p class="contact"><i class="fa fa-phone icon-color"></i> +23398765158</p>-->--}}
{{--                                </div>--}}

{{--                            </a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="col-lg-4 col-md-6 col-sm-6 store-main-box">--}}
{{--                    <div class="card service-card card-height">--}}
{{--                        <img src="{{ asset('assets/front/img/png/f2.jpg') }}" class="card-img-top store-image" alt="Subways">--}}
{{--                        <div class="card-body store-detail-body">--}}
{{--                            <a href="#" target="_blank">--}}
{{--                                <h5 class="card-title store-name">Subways</h5>--}}
{{--                                <h6 class="card-subtitle mb-2 text-muted  ">American,Chinese, Fast Food,</h6>--}}
{{--                                <div class="about">--}}
{{--                                    <span class="rate"><i class="fa fa-star"></i> 4.5 </span>--}}
{{--                                    <p class="time">--}}
{{--                                        Opening Times: 11:30 AM to 11:55 PM--}}
{{--                                    </p>--}}
{{--<!--                                    <p class="contact"><i class="fa fa-phone icon-color"></i> +23398765158</p>-->--}}
{{--                                </div>--}}
{{--                            </a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="col-lg-4 col-md-6 col-sm-6 store-main-box">--}}
{{--                    <div class="card service-card card-height">--}}
{{--                        <img src="{{ asset('assets/front/img/png/f3.jpg') }}" class="card-img-top store-image" alt="Starbuckes">--}}
{{--                        <div class="card-body store-detail-body">--}}
{{--                            <a href="#" target="_blank">--}}
{{--                                <h5 class="card-title store-name">Starbuckes</h5>--}}
{{--                                <h6 class="card-subtitle mb-2 text-muted  ">American,--}}
{{--                                </h6>--}}
{{--                                <div class="about">--}}
{{--                                    <span class="rate"><i class="fa fa-star"></i> 0 </span>--}}
{{--                                    <p class="time">--}}
{{--                                        Opening Times: 11:30 AM to 11:55 PM--}}
{{--                                    </p>--}}
{{--<!--                                    <p class="contact"><i class="fa fa-phone icon-color"></i> +23398765158</p>-->--}}
{{--                                </div>--}}

{{--                            </a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--    <div class="main-service-third mb-5 pt-2">--}}
{{--        <div id="food_delivery" class="container store-container">--}}
{{--            <div class="row">--}}
{{--                <div class="col-md-12">--}}
{{--                    <h3 class="cat-title">Grocery Delivery</h3>--}}
{{--                    <a href="{{ route('get:search_restaurants_list',['grocery-delivery',"all"]) }}" title="View All"><span class="view-all-link">view all</span></a>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="row">--}}
{{--                <div class="col-lg-4 col-md-6 col-sm-6 store-main-box">--}}
{{--                    <div class="card service-card card-height">--}}
{{--                        <img src="{{ asset('assets/front/img/png/g1.jpg') }}" class="card-img-top store-image" alt="Fresh Food">--}}
{{--                        <div class="card-body store-detail-body">--}}
{{--                            <a href="#" target="_blank">--}}
{{--                                <h5 class="card-title store-name">Fresh Food</h5>--}}
{{--                                <h6 class="card-subtitle mb-2 text-muted  ">All Food </h6>--}}
{{--                                <div class="about">--}}
{{--                                    <span class="rate"><i class="fa fa-star"></i> 3.8 </span>--}}
{{--                                    <p class="time">--}}
{{--                                        Opening Times: 11:30 AM to 11:55 PM--}}
{{--                                    </p>--}}
{{--<!--                                    <p class="contact"><i class="fa fa-phone icon-color"></i> +23398765124</p>-->--}}
{{--                                </div>--}}
{{--                            </a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="col-lg-4 col-md-6 col-sm-6 store-main-box">--}}
{{--                    <div class="card service-card card-height">--}}
{{--                        <img src="{{ asset('assets/front/img/png/g2.jpg') }}" class="card-img-top store-image" alt="FreshVeg Store">--}}
{{--                        <div class="card-body store-detail-body">--}}
{{--                            <a href="#" target="_blank">--}}
{{--                                <h5 class="card-title store-name">FreshVeg Store</h5>--}}
{{--                                <h6 class="card-subtitle mb-2 text-muted  ">Green Veg </h6>--}}
{{--                                <div class="about">--}}
{{--                                    <span class="rate"><i class="fa fa-star"></i> 3.8 </span>--}}
{{--                                    <p class="time">--}}
{{--                                        Opening Times: 11:30 AM to 11:55 PM--}}
{{--                                    </p>--}}
{{--<!--                                    <p class="contact"><i class="fa fa-phone icon-color"></i> +23398765189</p>-->--}}
{{--                                </div>--}}
{{--                            </a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="col-lg-4 col-md-6 col-sm-6 store-main-box">--}}
{{--                    <div class="card service-card card-height">--}}
{{--                        <img src="{{ asset('assets/front/img/png/g3.jpg') }}" class="card-img-top store-image" alt="Health Grow">--}}
{{--                        <div class="card-body store-detail-body">--}}
{{--                            <a href="#" target="_blank">--}}
{{--                                <h5 class="card-title store-name">Health Grow</h5>--}}
{{--                                <h6 class="card-subtitle mb-2 text-muted  ">Book Store,Pharmacy </h6>--}}
{{--                                <div class="about">--}}
{{--                                    <span class="rate"><i class="fa fa-star"></i> 3.8 </span>--}}
{{--                                    <p class="time">--}}
{{--                                        Opening Times: 11:30 AM to 11:55 PM--}}
{{--                                    </p>--}}
{{--<!--                                    <p class="contact"><i class="fa fa-phone icon-color"></i> +23398765158</p>-->--}}

{{--                                </div>--}}
{{--                            </a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

    <!--==========================
    End New Design
    --==========================-->

    @include('front.layout.how-it-works')

    @include('front.layout.provider-registration-section')



    @include('front.layout.footer')

</main>

<a href="#" class="back-to-top"><i class="fas fa-chevron-up"></i></a>

<!-- JavaScript Libraries -->
<script src="{{ asset('assets/front/js/jquery-migrate.min.js') }}"></script>
<script src="{{ asset('assets/front/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/front/js/easing.min.js') }}"></script>
<script src="{{ asset('assets/front/js/wow.min.js') }}"></script>
<script src="{{ asset('assets/front/js/hoverIntent.js') }}"></script>
<script src="{{ asset('assets/front/js/superfish.min.js') }}"></script>
<script src="{{ asset('assets/front/js/magnific-popup.min.js') }}"></script>


<!-- Template Main Javascript File -->
<script src="{{ asset('assets/front/js/main.js') }}"></script>
<script src="{{ asset('assets/front/js/user-login.js') }}" type="text/javascript"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-growl/1.0.0/jquery.bootstrap-growl.min.js"
        type="text/javascript"></script>
<script type="text/javascript">
    if (window.location.hash && window.location.hash == '#_=_') {
        window.location = '';
    }
</script>
<script>
    $(document).ready(function () {
        $('.user').easyResponsiveTabs({
            type: 'default', //Types: default, vertical, accordion
            width: 'auto', //auto or any width like 600px
            fit: true   // 100% fit in a container
        });

        @if (Session::has('success'))
        $('.bootstrap-growl').remove();
        $.bootstrapGrowl("{{ Session::get('success') }}",
            {
                type: "success", //info,success,warning and danger
                ele: "body",
                offset: {
                    from: "top",
                    amount: 20
                },
                align: "right",
                width: 300,
                delay: 5000,
                allow_dismiss: true,
                stackup_spacing: 10
            });
        @endif
        @if (Session::has('error'))
        $('.bootstrap-growl').remove();
        $.bootstrapGrowl("{{ Session::get('error') }}",
            {
                type: "danger", //info,success,warning and danger
                ele: "body",
                offset: {
                    from: "top",
                    amount: 20
                },
                align: "right",
                width: 300,
                delay: 5000,
                allow_dismiss: true,
                stackup_spacing: 10
            });
        @endif
        @if (Session::has('warning'))
        $('.bootstrap-growl').remove();
        $.bootstrapGrowl("{{ Session::get('warning') }}",
            {
                type: "warning", //info,success,warning and danger
                ele: "body",
                offset: {
                    from: "top",
                    amount: 20
                },
                align: "right",
                width: 300,
                delay: 10000000000000,
                position: "fixed",
                allow_dismiss: true,
                stackup_spacing: 10
            });
        @endif

        $('.user').easyResponsiveTabs({
            type: 'default', //Types: default, vertical, accordion
            width: 'auto', //auto or any width like 600px
            fit: true   // 100% fit in a container
        });


    });

    var modal = document.getElementById('user');

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if(event.target == modal) {
            modal.style.display = "none";
        }
    };

    var url = "{{ route('get:lang_change') }}";
    $(".Langchange").change(function(){
        window.location.href = url + "?lang="+ $(this).val();
    });

    @if (!Session::has('timezone'))
        const tzid = Intl.DateTimeFormat().resolvedOptions().timeZone;
        currtimezone = tzid.replace("Calcutta","Kolkata");
            $.ajax({
                type:'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url:'{{ route('post:setTimezone') }}',
                /*dataType:"json",*/
                data: {"timezone":currtimezone},
                success:function(msg){
                    console.log(msg);
                    /*if(msg.current_address!=""){
                        $("#source_address").val(msg.current_address);
                        $("#source_lat").val(msg.current_latitude);
                        $("#source_long").val(msg.current_longitude);
                    }else{
                        $("#source_address").val('');
                        $("#source_lat").val(0);
                        $("#source_long").val(0);
                    }*/
                }
            });
    @endif

</script>

</body>
</html>

