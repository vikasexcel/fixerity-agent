@php $general_settings = \App\Models\GeneralSettings::first() @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <!--== META TAGS ==-->
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
    @yield('title')
    @yield('meta-tags')

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (\Request::is('/'))
        <link rel="canonical" href="{{ \Request::url().'/'}}"/>
    @else
        <link rel="canonical" href="{{ \Request::url()}}"/>
    @endif
<!--== FAV ICON ==-->
    <link rel="icon"
          href="{{ isset($general_settings)? ($general_settings->website_favicon != Null) ? asset('assets/images/website-logo-icon/'.$general_settings->website_favicon) : asset('assets/images/website-logo-icon/fevicon.ico')  : asset('assets/images/website-logo-icon/fevicon.ico') }}"
          type="image/x-icon">
    <link href="{{ isset($general_settings)? ($general_settings->website_favicon != Null) ? asset('assets/images/website-logo-icon/'.$general_settings->website_favicon) : asset('assets/images/website-logo-icon/fevicon.ico')  : asset('assets/images/website-logo-icon/fevicon.ico') }}" rel="AngleOrder">
    <!--== ALL CSS FILES ==-->
    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert.min.css')}}">
    <link rel="stylesheet" href="{{ asset('assets/css/font/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/style_home.css?v=0.22') }}">
    <link href="{{ asset('assets/front/css/animate.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/front/css/ionicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/front/css/magnific-popup.css') }}" rel="stylesheet">
<!--    <link rel="stylesheet" href="{{--{{ asset('assets/front/css/style.css?v=0.11') }}--}}">-->

    <script src="{{ asset('assets/front/js/jquery-3.2.1.min.js') }}" type="text/javascript"></script>
{{--    <link rel="stylesheet" href="{{ asset('assets/front/css/user-login.css?v=0.1') }}">--}}
    <link href="{{ asset('assets/front/css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet" media="screen">

    <script src="//maps.googleapis.com/maps/api/js?key={{ isset($general_settings->map_key)? ($general_settings->map_key != Null)? $general_settings->map_key : 0 : 0 }}&libraries=places"></script>

    <!-- Libraries CSS Files -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css"
          integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Main Stylesheet File -->


    @yield('page-css')
    <style>
        .navbar-inverse .navbar-nav>li>a {
            color: #ffffff;
            border-color: white;
        }

        .nav_bar {
            padding: 0;
            margin: 0 !important;
            border-radius: 0px !important;
        }
        .header-navbar-inverse{
            background-color: #FFA000;
            border-color: #FFA000;
            margin-bottom: 0 !important;
            border-radius: 0 !important;
        }
        .home_search {
            padding: 100px;
        }

        .btn-log-in {
            cursor: pointer;
        }

        /*.navbar-brand img{*/
        /*width: 200px;*/
        /*margin-top: -20px;*/
        /*}*/
        .navbar-brand img {
            width: 150px;
            margin: 0 auto;
            margin-top: -11px;
        }
    </style>
    <script>
        //page reload for cart update
        if (!!window.performance && window.performance.navigation.type === 2) {
            console.log('Reloading');
            window.location.reload();
        }
        var APP_URL = {!! json_encode(url('/')) !!}

    </script>
{{--    //NEW CSS OF GOFERR--}}
    <style>
        #logo img {
            width: 180px;
        }

        .mockup-image {
            height: 500px;
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
    <style>

        #header {
            background: white;
            padding: 24px 0;
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
            background: #bc1c91 !important;
            color: #000000 !important;
            text-align: center !important;
            /*padding-left: 20% !important;*/
        }
        .btn-policy:active, .btn-policy:focus, .btn-policy:hover {
            background: #FFF !important;
            color: #bc1c91 !important;
        }
        .btn-policy::after {
            content: "";
            padding-left: 0 !important;
        }
        /*contact*/

        #header {
            position:inherit !important;
        }
        .currency:before {
            content: '$';
            letter-spacing: 2px;
        }
    </style>
</head>

<body data-spy="scroll" data-target="#myScrollspy" data-offset="20">
<!--== MAIN CONTRAINER ==-->
@if (Illuminate\Support\Facades\Auth::guard("admin")->check())
    {{ App\Models\User::AdminLogout() }}
@else
    @if(Illuminate\Support\Facades\Auth::guard("user")->check())
        @if(Illuminate\Support\Facades\Auth::guard("user")->user()->status != 1)
            {{ App\Models\User::UserLogout() }}
        @endif
    @else
        {{ App\Models\User::UserLogout() }}
    @endif
@endif
@if(Request::segment(1) != 'terms-conditionsa')
    @include('front.layout.nav')
@endif
@yield('page-content')

@if(Request::segment(1) != 'terms-conditionsa')
    @include('front.layout.footer')
@endif
<a href="#" class="back-to-top"><i class="fas fa-chevron-up"></i></a>
<!--======== SCRIPT FILES =========-->
<!-- modal code for topping -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modaldisplayClass">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Modal title</h4>

            </div>
            <div class="modal-body modalToppingBody"></div>
            <div class="modal-footer modaldisplayClass">
            </div>
        </div>
    </div>
</div>
<!-- ENd modal code for toppings -->

<script src="{{ asset('assets/front/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/front/js/easing.min.js') }}"></script>
<script src="{{ asset('assets/front/js/wow.min.js') }}"></script>
<script src="{{ asset('assets/front/js/hoverIntent.js') }}"></script>
<script src="{{ asset('assets/front/js/superfish.min.js') }}"></script>
<script src="{{ asset('assets/front/js/magnific-popup.min.js') }}"></script>
<script src="{{ asset('assets/front/js/bootstrap.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/front/js/wow.min.js') }}"></script>
<script src="{{ asset('assets/front/js/superfish.min.js') }}"></script>
<script src="{{ asset('assets/front/js/magnific-popup.min.js') }}"></script>

{{--<script src="{{ asset('assets/js/front/jquery.lazy.min.js') }}" type="text/javascript"></script>--}}
<script src="{{ asset('assets/front/js/main.js') }}"></script>
<script src="{{ asset('assets/front/js/user-login.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/front/js/common.js?v=0.1') }}" type="text/javascript"></script>
<script type="text/javascript" src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/front/js/jquery.bootstrap-growl.min.js') }}"
        type="text/javascript"></script>
<script type="text/javascript" src="{{ asset('assets/front/js/bootstrap-datetimepicker.js') }}" charset="UTF-8"></script>

<script type="text/javascript">
    if (window.location.hash && window.location.hash == '#_=_') {
        window.location = '';
    }
</script>
<script type="text/javascript">
    //    $(document).ready(function () {
    //        $('img').lazy();
    //    });
    @if (Session::has('success'))
    $('.bootstrap-growl').remove();
    $.bootstrapGrowl("{!!   Session::get('success')!!}",
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
    $.bootstrapGrowl("{!!  Session::get('error')  !!}",
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
            position: "fixed",
            allow_dismiss: true,
            stackup_spacing: 10
        });
    @endif
    @if (Session::has('warning'))
    $('.bootstrap-growl').remove();
    $.bootstrapGrowl("{!!  Session::get('warning')  !!}",
        {
            type: "warning", //info,success,warning and danger
            ele: "body",
            offset: {
                from: "top",
                amount: 20
            },
            align: "right",
            width: 300,
            delay: 5000,
            position: "fixed",
            allow_dismiss: true,
            stackup_spacing: 10
        });
    @endif

    $(document).ready(function () {
        $('.user').easyResponsiveTabs({
            type: 'default', //Types: default, vertical, accordion
            width: 'auto', //auto or any width like 600px
            fit: true   //100% fit in a container
        });




    });

    var modal = document.getElementById('user');

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

    function openNav() {
        document.getElementById("mySidenav").style.width = "250px";
    }

    function closeNav() {
        document.getElementById("mySidenav").style.width = "0";
    }

    /*var defaultBounds = new google.maps.LatLngBounds(
        new google.maps.LatLng(-33.8902, 151.1759),
        new google.maps.LatLng(-33.8474, 151.2631));

    var input = document.getElementById('stuff');
    var options = {
        bounds: defaultBounds,
        types: ['establishment']
    };

    autocomplete = new google.maps.places.Autocomplete(input, options);*/

    //page reload for cart update
    //    if (!!window.performance && window.performance.navigation.type === 2) {
    //        console.log('Reloading');
    //        window.location.reload();
    //    }

    @if($current_address == "")
        $(document).ready(function(){
            if(navigator.geolocation){
                navigator.geolocation.getCurrentPosition(showLocation);
            }else{
                $('#source_address').val('');
            }
        });
    @endif

        $(document).ready(function(){
            $(document).on("click",".curr_loc",function (){
                if(navigator.geolocation){
                    navigator.geolocation.getCurrentPosition(showLocation);
                }else{
                    $('#source_address').val('');
                }
            });

        });

        function showLocation(position){
            var latitude = position.coords.latitude;
            var longitude = position.coords.longitude;
            $.ajax({
                type:'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url:'{{ route('post:getLocation') }}',
                /*dataType:"json",*/
                data: {"latitude":latitude,"longitude":longitude},
                success:function(msg){
                    console.log(msg);
                    if(msg.current_address!=""){
                        $("#source_address").val(msg.current_address);
                        $("#source_lat").val(msg.current_latitude);
                        $("#source_long").val(msg.current_longitude);
                    }else{
                        $("#source_address").val('');
                        $("#source_lat").val(0);
                        $("#source_long").val(0);
                    }
                }
            });
        }

</script>

@yield('page-js')

</body>
</html>
