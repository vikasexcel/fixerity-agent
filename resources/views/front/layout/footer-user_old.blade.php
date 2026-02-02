@php $general_settings = \App\Models\GeneralSettings::first() @endphp
<div class="footer-front">
    <section id="contact ">
        <div class="container contact-bg">
            <div class="row wow fadeInUp">

                <div class="col-lg-4 col-md-4">
                    <div class="">
                        <h3 class="text-white">Quick Links</h3>
                        <ul class="alt text-white" >
                            <li><a href="#" class="text-white">Menu1</a></li>
                            <li><a href="#" class="text-white">Menu1</a></li>
                            <li><a href="#" class="text-white">Menu1</a></li>
                            <li><a href="#" class="text-white">Menu1</a></li>
                            <li><a href="#" class="text-white">Menu1</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-4 col-md-4">
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
                </div>

                <div class="col-lg-4 col-md-4">
                    <div class="form">
                        <h3 class="text-white">Social Links</h3>
                        @if(isset($general_settings))
                            <div class="social-links ">
                                <a href="{{ (isset($general_settings->twitter_link) && $general_settings->twitter_link != Null)? $general_settings->twitter_link : "#" }}"
                                   class="twitter"><i class="fab fa-twitter"></i></a>
                                <a href="{{ (isset($general_settings->facebook_link) && $general_settings->facebook_link != Null)? $general_settings->facebook_link : "#" }}"
                                   class="facebook"><i class="fab fa-facebook-f"></i></a>
{{--                                <a href="{{ (isset($general_settings->instagram_link) && $general_settings->instagram_link != Null)? $general_settings->instagram_link : "#" }}"--}}
{{--                                   class="instagram"><i class="fab fa-instagram"></i></a>--}}
                                <a href="{{ (isset($general_settings->google_link) && $general_settings->google_link != Null)? $general_settings->google_link : "#" }}" class="google-plus"><i class="fab fa-google-plus-g"></i></a>
{{--                                <a href="{{ (isset($general_settings->linkedin_link) && $general_settings->linkedin_link != Null)? $general_settings->linkedin_link : "#" }}"--}}
{{--                                   class="linkedin"><i class="fab fa-linkedin-in"></i></a>--}}
                            </div>
                            <div class="">
                                <a href="{{ isset($general_settings)? (isset($general_settings->user_playstore_link) && $general_settings->user_playstore_link != Null)? $general_settings->user_playstore_link : "" : "" }}" target="_blank">
                                    <img class=" footer-app-store padding-3 mb-2" src="{{ asset('assets/front_old/img/png/google-play-badge.png') }}">
                                </a>
                                <br>
                                <a href="{{ isset($general_settings)? (isset($general_settings->user_appstore_link) && $general_settings->user_appstore_link != Null)? $general_settings->user_appstore_link : "" : "" }}" target="_blank">
                                    <img class=" footer-app-store" src="{{ asset('assets/front_old/img/png/app-store-badge.png') }}">
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>
    </section>
    <!-- #contact -->

    <footer id="footer">
        <div class="container copy-bg">
            <div class="row">
                <div class="col-lg-12 text-lg-right text-center">
                    <div class="row">
                        <div class="col-xl-12 col-sm-12 col-md-12 col-lg-12 text-lg-right text-center">
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
