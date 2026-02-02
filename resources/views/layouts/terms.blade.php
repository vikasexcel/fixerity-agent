<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>
    @php $site_setting = \App\Models\GeneralSettings::first() @endphp
    <link rel="icon"
          href="{{ (isset($site_setting) && $site_setting->website_favicon != Null) ? asset('assets/images/website-logo-icon/'.$site_setting->website_favicon) : "" }}"
          type="image/x-icon">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- GetButton.io widget -->
    <script type="text/javascript">
        // (function () {
        //     var options = {
        //         viber: "+639104627859", // Viber number
        //         email: "sales@whitelabelfox.com", // Email
        //         call_to_action: "Message us", // Call to action
        //         button_color: "#E74339", // Color of button
        //         position: "right", // Position may be 'right' or 'left'
        //         order: "viber,email", // Order of buttons
        //     };
        //     var proto = document.location.protocol, host = "getbutton.io", url = proto + "//static." + host;
        //     var s = document.createElement('script'); s.type = 'text/javascript'; s.async = true; s.src = url + '/widget-send-button/js/init.js';
        //     s.onload = function () { WhWidgetSendButton.init(host, proto, options); };
        //     var x = document.getElementsByTagName('script')[0]; x.parentNode.insertBefore(s, x);
        // })();
    </script>
    <!-- /GetButton.io widget -->
</head>
<body>
<div id="app">
    <main class="py-4">
        @yield('content')
    </main>
</div>
</body>
</html>
