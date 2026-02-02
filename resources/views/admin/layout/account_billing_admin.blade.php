<!DOCTYPE html>
<html lang="en-US">
{{--<html lang="pt" xml:lang="pt" xmlns="http://www.w3.org/1999/xhtml">--}}

<meta http-equiv="content-type" content="text/html;charset=UTF-8"/>
<head>
    <title>@yield('title')
    </title>
    <!--== META TAGS ==-->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>

    <!-- Favicon icon -->
    @php $icon = \App\Models\GeneralSettings::first() @endphp
    <link rel="icon"
          href="{{ isset($icon)? ($icon->website_favicon != Null) ? asset('assets/images/website-logo-icon/'.$icon->website_favicon) : '' : '' }}"
          type="image/x-icon">
    <!-- Google font-->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Quicksand:500,700" rel="stylesheet">
    <!-- Required Fremwork -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css')}}">
    <!-- waves.css -->
    <link rel="stylesheet" href="{{ asset('assets/css/waves.min.css')}}" type="text/css" media="all">
    <!-- feather icon -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/feather.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/font/font-awesome.min.css')}}">
    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert.min.css')}}">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css"
          integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
    @yield('extra-css-link')
<!-- Style.css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css?v=0.5')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/pages.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/extra.style.css?v=0.1')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/extra.style2.css')}}">
    @yield('page-css')

    <style>
        .currency:before {
            content: '$';
        }

        .bg-c-blue{
            background-color: {{$icon->theme_color}} !important;
        }
        .btn-primary, .sweet-alert button.confirm, .sweet-alert button.confirm:hover, .wizard > .actions a:hover{
            background-color: {{$icon->theme_color}} !important;
            color: #FFFFFF !important;
            border-color: {{$icon->theme_color}} !important;
        }
        .btn-primary:focus, .sweet-alert button.confirm:hover, .wizard > .actions a:hover{
            background-color: {{$icon->theme_color}} !important;
            color: #FFFFFF !important;
            border-color: {{$icon->theme_color}} !important;
            box-shadow: 0 0 0 0.2rem {{$icon->theme_color."60"}} !important;
        }
        .btn-primary:hover, .sweet-alert button.confirm:hover, .wizard > .actions a:hover{
            background-color: {{$icon->theme_color}} !important;
            color: #FFFFFF !important;
            border-color: {{$icon->theme_color}} !important;
        }
        .page-item.active .page-link {
            background-color: {{$icon->theme_color}};
            border-color: {{$icon->theme_color}};
        }
    </style>

    <!-- The core Firebase JS SDK is always required and must be listed first -->
    <script src="https://www.gstatic.com/firebasejs/8.7.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.7.1/firebase-messaging.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.7.1/firebase-database.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.7.1/firebase-auth.js"></script>

    <script>
        // Your web app's Firebase configuration
        // For Firebase JS SDK v7.20.0 and later, measurementId is optional
        const firebaseConfig = {
            apiKey: "AIzaSyCTZ2LUQ5uBXK_J6G0k2VPwifq_bO6rRhM",
            authDomain: "fixerity-app.firebaseapp.com",
            databaseURL: "https://fixerity-app-default-rtdb.firebaseio.com",
            projectId: "fixerity-app",
            storageBucket: "fixerity-app.firebasestorage.app",
            messagingSenderId: "1092489788909",
            appId: "1:1092489788909:web:f1a6d58b954f588dc87be4",
            measurementId: "G-3BN2L17DKN"
        };
        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);

    </script>
</head>

<body>
<div id="google_translate_element" style="position: absolute; right: 350px; top:12px; z-index: 1000000"></div>
<div id="render-css-link">
</div>
<div id="render-css">
</div>
<!-- [ Pre-loader ] start -->
<div class="loader-bg">
    <div class="loader-bar"></div>
</div>
{{--without refresh page--}}
<div class="pre-loader"></div>
<!-- [ Pre-loader ] end -->
<div id="pcoded" class="pcoded">
    <div class="pcoded-overlay-box"></div>
    <div class="pcoded-container navbar-wrapper">

        {{--navbar start--}}
        @include('admin.include.navbar')
        {{--navbar end--}}

        {{--chat start--}}
        @include('admin.include.chat')
        {{--chat end--}}

        <div class="pcoded-main-container">
            <div class="pcoded-wrapper">
                {{--sidebar start--}}
                @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                    @if(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 3)
                        @include('admin.include.account_billing_sidebar')
                    @else
                        @include('admin.include.sidebar')
                    @endif
                @endif
                {{--sidebar end--}}

                {{--content start--}}

                @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                    @if(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1 || Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 3 || Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 4)
                        <div id="render-content">
                            @yield('page-content')
                        </div>
                    @else
                        <script>window.location = "{{route('get:admin:dashboard')}}";</script>
                    @endif
                @endif
                {{--content end--}}
            </div>
        </div>
    </div>
</div>
<div id="render-js">
</div>

{{--</div>--}}
<!--======== SCRIPT FILES =========-->

<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');
    }
</script>
<script type="text/javascript"
        src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery-ui.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/popper.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/bootstrap.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/waves.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.slimscroll.js')}}"></script>
{{--@php $map_key = \App\Models\GeneralSettings::first() @endphp--}}
{{--<script src="//maps.googleapis.com/maps/api/js?key={{ isset($map_key)? ($map_key->map_key != Null)? $map_key->map_key : 0 : 0 }}&libraries=places"></script>--}}
@yield('page-js')
<script type="text/javascript" src="{{ asset('assets/js/pcoded.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/script.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/vertical-layout.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.bootstrap-growl.min.js')}}"></script>
{{--<script src="//maps.googleapis.com/maps/api/js?key=AIzaSyC703aCvZmrdfFlNxArFXzBL_OBNuF4AC4&libraries=places"></script>--}}
<script type="text/javascript" src="{{ asset('assets/js/custom.js')}}"></script>
<script>
    $("[data-toggle='tooltip']").click(function () {
        var $this = $(this);
        $(".tooltip").fadeOut("fast", function () {
            $this.blur();
        });
    });
    $(document).ready(function ($) {
        //Use this inside your document ready jQuery
        $(window).on('popstate', function () {
            location.reload(true);
        });
    });
    @if (Session::has('success'))
    $('.bootstrap-growl').remove();
    $.bootstrapGrowl("{{ Session::get('success') }}", // Messages
        { // options
            type: "success", // info, success, warning and danger
            ele: "body", // parent container
            offset: {
                from: "top",
                amount: 20
            },
            align: "right", // right, left or center
            width: 300,
            delay: 4000,
            allow_dismiss: true, // add a close button to the message
            stackup_spacing: 10
        });
    @endif
    @if (Session::has('error'))
    $('.bootstrap-growl').remove();
    $.bootstrapGrowl("{{ Session::get('error') }}", // Messages
        { // options
            type: "danger", // info, success, warning and danger
            ele: "body", // parent container
            offset: {
                from: "top",
                amount: 20
            },
            align: "right", // right, left or center
            width: 300,
            delay: 4000,
            allow_dismiss: true, // add a close button to the message
            stackup_spacing: 10
        });
    @endif
</script>
{{--<script>--}}
    {{--var load = function (url) {--}}
        {{--$.get(url).done(function (data) {--}}
        {{--//$.get(url);--}}
        {{--//$(".pcoded-content").html(data.content);--}}
            {{--$("#render-content").html(data.content);--}}
            {{--$("#render-css").html(data.extra_css);--}}
            {{--$("#render-js").html(data.extra_js);--}}
            {{--document.title = data.title;--}}
        {{--})--}}
    {{--};--}}
    {{--$(document).on('click', '#render-navbar .waves-effect', function (e) {--}}
        {{--e.preventDefault();--}}
        {{--var $this = $(this),--}}
            {{--url = $this.attr("href"),--}}
            {{--title = $this.attr('title');--}}
        {{--history.pushState({--}}
            {{--url: url,--}}
            {{--title: title--}}
        {{--}, title, url);--}}
        {{--load(url);--}}
    {{--});--}}
    {{--$(document).on('click', '.render_link', function (e) {--}}
        {{--e.preventDefault();--}}
        {{--var $this = $(this),--}}
            {{--url = $this.attr("href"),--}}
            {{--title = $this.attr('title');--}}
        {{--history.pushState({--}}
            {{--url: url,--}}
            {{--title: title--}}
        {{--}, title, url);--}}
        {{--load(url);--}}
    {{--});--}}
{{--</script>--}}
</body>
</html>
