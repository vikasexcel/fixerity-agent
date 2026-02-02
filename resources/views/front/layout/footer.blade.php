    @php $general_settings = \App\Models\GeneralSettings::first() @endphp

<style>
    #contact .nav-menu > li{
        float: none !important;
    }
    #contact .nav-menu .nav-item , #contact .nav-menu .nav-item > a
    {
        margin: 0;
        padding: 0;
    }
    #contact .nav-menu .nav-item > a{
        font-size: 16px;
    }
    #contact .social-links a {
         color: #363636;
         border: 1px solid #ffffff;
    }
    #contact .social-links a:hover {
        background: #363636;
        color: #ffffff;
    }
</style>
<div class="footerfix">
<section id="contact" >
    <div class="container">
        <div class="row wow fadeInUp">

            <div class="col-lg-4 col-md-4">
                <div class="">
                    <h3 class="text-white">Quick Links</h3>
                    <ul class="alt text-white pl-2" >
                        <li><a class="text-white" href="{{ route('get:page_about_us') }}">About Us</a></li>
<!--                        <li><a class="text-white" href="{{--{{ route('get:page_faq') }}--}}">FAQ</a></li>-->
                        <li><a class="text-white" href="{{ route('get:page_terms_conditions') }}">Terms &amp; Conditions</a></li>
                        <li><a class="text-white" href="{{ route('get:page_privacy') }}">Privacy</a></li>
<!--                        <li><a class="text-white" href="{{ route('get:contact-us') }}">Contact Us</a></li>-->
<!--                        <li><a class="text-white" href="{{ route('get:page_privacy') }}">FAQ</a></li>
                        <li><a class="text-white" href="{{ route('get:page_privacy') }}">Security</a></li>-->
                    </ul>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="">
                    <a class="text-white" href="{{ route('get:contact-us') }}"><h3 class="text-white">Contact Us</h3></a>
                    <ul class="alt text-white pl-2" >
                        @if(isset($general_settings))
                            @if($general_settings->address != Null)
                                <li class="text-white">
                                    <a style="margin-bottom: 20px;font-weight: 500"><i class="fa fa-address-book text-white "> </i> {{ $general_settings->address }}</a>
                                </li>
                            @endif
                            @if($general_settings->email != Null)
                                <li class="text-white">
                                    <a style="margin-bottom: 20px;font-weight: 500"><i class="fa fa-envelope text-white "> </i> {{ $general_settings->email }}</a>
                                </li>
                            @endif
                            @if($general_settings->contact_no != Null)
                                <li class="text-white">
                                    <a style="margin-bottom: 20px;font-weight: 500"><i class="fa fa-phone text-white "> </i> {{ $general_settings->contact_no }}</a>
                                </li>
                            @endif
                        @endif
                    </ul>
                </div>
            </div>

<!--            <div class="col-lg-4 col-md-4">
                <div class="info">
                    @if(isset($general_settings))
                        @if($general_settings->address != Null || $general_settings->email != Null ||$general_settings->contact_no != Null)
                            <h3 class="text-white">Contact Us</h3>
                        @endif
                        @if($general_settings->address != Null)
                            <div class="text-white">
                                <i class="fa fa-address-book text-white "></i>

                                <p style="margin-bottom: 0; padding-bottom: 20px">{{ $general_settings->address }}</p>
                            </div>
                        @endif
                        @if($general_settings->email != Null)
                            <div class="text-white">
                                <i class="fa fa-envelope text-white "></i>
                                <p style="margin-bottom: 0; padding-bottom: 20px">{{ $general_settings->email }}</p>
                            </div>
                        @endif
                        @if($general_settings->contact_no != Null)
                            <div class="text-white">
                                <i class="fa fa-phone text-white "></i>
                                <p style="margin-bottom: 0; padding-bottom: 20px">{{ $general_settings->contact_no }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>-->

            <div class="col-lg-4 col-md-4">
                <div class="form">
                    <h3 class="text-white">Social Links</h3>
                    @if(isset($general_settings))
                        <div class="social-links ">

                            <a href="{{ (isset($general_settings->twitter_link) && $general_settings->twitter_link != Null)? $general_settings->twitter_link : "#" }}"
                               class="twitter"><i class="fab fa-twitter"></i></a>
                            <a href="{{ (isset($general_settings->facebook_link) && $general_settings->facebook_link != Null)? $general_settings->facebook_link : "#" }}"
                               class="facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="{{ (isset($general_settings->instagram_link) && $general_settings->instagram_link != Null)? $general_settings->instagram_link : "#" }}"
                               class="facebook"><i class="fab fa-instagram"></i></a>
                        </div>
                        <div class="">
                            <a href="{{ isset($general_settings)? (isset($general_settings->user_playstore_link) && $general_settings->user_playstore_link != Null)? $general_settings->user_playstore_link : "" : "" }}" target="_blank">
                                <img class=" footer-app-store padding-3 mb-2" src="{{ asset('assets/front/img/png/google-play-badge.png') }}">
                            </a>
                            <br>
                            <a href="{{ isset($general_settings)? (isset($general_settings->user_appstore_link) && $general_settings->user_appstore_link != Null)? $general_settings->user_appstore_link : "" : "" }}" target="_blank">
                                <img class=" footer-app-store" src="{{ asset('assets/front/img/png/app-store-badge.png') }}">
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
</section>
<footer id="footer" >
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-lg-right text-center">
                <div class="row">
                    <div class="col-xl-12 col-sm-12 col-md-12 col-lg-12  text-center">
                        <div class="copyright">
                            <span>
                                @if(isset($general_settings))
                                    @if($general_settings->copy_right != Null)
                                        {{ $general_settings->copy_right }}
                                    @endif
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
</div>
