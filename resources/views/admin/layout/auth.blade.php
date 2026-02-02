<!DOCTYPE html>
<html lang="en-US">
<head>
    <title>@yield('title')</title>
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
    <!-- Style.css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css?v=0.11')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/pages.css')}}">
    @yield('page-css')

    <style>
        .btn-md {
            padding: 5px 16px;
        }
    </style>
</head>

<body>
<!-- Pre-loader start -->

<div id="google_translate_element" style="position: absolute; right: 30px; top:-30px"></div>
<div class="theme-loader">
    <div class="loader-track">
        <div class="preloader-wrapper">
            <div class="spinner-layer spinner-blue">
                <div class="circle-clipper left">
                    <div class="circle"></div>
                </div>
                <div class="gap-patch">
                    <div class="circle"></div>
                </div>
                <div class="circle-clipper right">
                    <div class="circle"></div>
                </div>
            </div>
            <div class="spinner-layer spinner-red">
                <div class="circle-clipper left">
                    <div class="circle"></div>
                </div>
                <div class="gap-patch">
                    <div class="circle"></div>
                </div>
                <div class="circle-clipper right">
                    <div class="circle"></div>
                </div>
            </div>

            <div class="spinner-layer spinner-yellow">
                <div class="circle-clipper left">
                    <div class="circle"></div>
                </div>
                <div class="gap-patch">
                    <div class="circle"></div>
                </div>
                <div class="circle-clipper right">
                    <div class="circle"></div>
                </div>
            </div>

            <div class="spinner-layer spinner-green">
                <div class="circle-clipper left">
                    <div class="circle"></div>
                </div>
                <div class="gap-patch">
                    <div class="circle"></div>
                </div>
                <div class="circle-clipper right">
                    <div class="circle"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Pre-loader end -->
@yield('page-content')

{{--<!-- Required Jquery -->--}}
<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({pageLanguage: 'pt'}, 'google_translate_element');
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
<script type="text/javascript" src="{{ asset('assets/js/pcoded.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/vertical-layout.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/script.min.js')}}"></script>

<script language="JavaScript" src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-sweetalert/1.0.1/sweetalert.min.js"
        type="text/javascript"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.bootstrap-growl.min.js')}}"></script>
<script rel="stylesheet" src="{{ asset('assets/js/validation/jquery.validate.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/otpNextPrevMove.js')}}"></script>

@yield('page-js')
<script>
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

</body>
</html>
