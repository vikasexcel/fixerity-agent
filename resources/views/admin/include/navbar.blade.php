<!-- [ Header ] start -->
<nav class="navbar header-navbar pcoded-header">
    <div class="navbar-wrapper">
        <div class="navbar-logo">
            <a class="render_link" @if(Illuminate\Support\Facades\Auth::guard("admin")->check()) href="{{ route('get:admin:dashboard') }} @else  href="{{ route('get:provider-admin:dashboard') }} @endif">
                @php $map_key = \App\Models\GeneralSettings::first() @endphp
                @if(isset($map_key) && $map_key->website_logo != Null)
                    <img class="img-fluid" src="{{ asset('assets/images/website-logo-icon/'.$map_key->website_logo)}}"
                         alt="{{$map_key->website_logo}}">
                @endif
            </a>
            <a class="mobile-menu" id="mobile-collapse">
                <i class="feather icon-menu icon-toggle-right" style="cursor: pointer;"></i>
            </a>
            <a class="mobile-options waves-effect waves-light">
                <i class="feather icon-more-horizontal"></i>
            </a>
        </div>

        <div class="navbar-container container-fluid">
            <ul class="nav-left">
                {{--<li class="header-search">--}}
                {{--<div class="main-search morphsearch-search">--}}
                {{--<div class="input-group">--}}
                {{--<span class="input-group-prepend search-close">--}}
                {{--<i class="feather icon-x input-group-text"></i>--}}
                {{--</span>--}}
                {{--<input type="text" class="form-control" placeholder="Enter Keyword">--}}
                {{--<span class="input-group-append search-btn">--}}
                {{--<i class="feather icon-search input-group-text"></i>--}}
                {{--</span>--}}
                {{--</div>--}}
                {{--</div>--}}
                {{--</li>--}}
                {{--<li>--}}
                {{--<a href="" onclick="javascript:toggleFullScreen()" class="waves-effect waves-light">--}}
                {{--<i class="full-screen feather icon-maximize"></i>--}}
                {{--</a>--}}
                {{--</li>--}}
            </ul>
            <ul class="nav-right">
                {{--<li class="header-notification">--}}
                {{--<div class="dropdown-primary dropdown">--}}
                {{--<div class="dropdown-toggle" data-toggle="dropdown">--}}
                {{--<i class="feather icon-bell"></i>--}}
                {{--<span class="badge bg-c-red">5</span>--}}
                {{--</div>--}}
                {{--<ul class="show-notification notification-view dropdown-menu"--}}
                {{--data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">--}}
                {{--<li>--}}
                {{--<h6>Notifications</h6>--}}
                {{--<label class="label label-danger">New</label>--}}
                {{--</li>--}}
                {{--<li>--}}
                {{--<div class="media">--}}
                {{--<img class="img-radius" src="{{ asset('assets/images/avatar-4.jpg')}}"--}}
                {{--alt="Generic placeholder image">--}}
                {{--<div class="media-body">--}}
                {{--<h5 class="notification-user">John Doe</h5>--}}
                {{--<p class="notification-msg">Lorem ipsum dolor sit amet, consectetuer--}}
                {{--elit.</p>--}}
                {{--<span class="notification-time">30 minutes ago</span>--}}
                {{--</div>--}}
                {{--</div>--}}
                {{--</li>--}}
                {{--<li>--}}
                {{--<div class="media">--}}
                {{--<img class="img-radius" src="{{ asset('assets/images/avatar-3.jpg')}}"--}}
                {{--alt="Generic placeholder image">--}}
                {{--<div class="media-body">--}}
                {{--<h5 class="notification-user">Joseph William</h5>--}}
                {{--<p class="notification-msg">Lorem ipsum dolor sit amet, consectetuer--}}
                {{--elit.</p>--}}
                {{--<span class="notification-time">30 minutes ago</span>--}}
                {{--</div>--}}
                {{--</div>--}}
                {{--</li>--}}
                {{--<li>--}}
                {{--<div class="media">--}}
                {{--<img class="img-radius" src="{{ asset('assets/images/avatar-4.jpg')}}"--}}
                {{--alt="Generic placeholder image">--}}
                {{--<div class="media-body">--}}
                {{--<h5 class="notification-user">Sara Soudein</h5>--}}
                {{--<p class="notification-msg">Lorem ipsum dolor sit amet, consectetuer--}}
                {{--elit.</p>--}}
                {{--<span class="notification-time">30 minutes ago</span>--}}
                {{--</div>--}}
                {{--</div>--}}
                {{--</li>--}}
                {{--</ul>--}}
                {{--</div>--}}
                {{--</li>--}}
                {{--<li class="header-notification">--}}
                {{--<div class="dropdown-primary dropdown">--}}
                {{--<div class="displayChatbox dropdown-toggle" data-toggle="dropdown">--}}
                {{--<i class="feather icon-message-square"></i>--}}
                {{--<span class="badge bg-c-green">3</span>--}}
                {{--</div>--}}
                {{--</div>--}}
                {{--</li>--}}
                <li class="user-profile header-notification">

                    <div class="dropdown-primary dropdown">
                        <div class="dropdown-toggle" data-toggle="dropdown">
                            @if(Illuminate\Support\Facades\Auth::guard("on_demand")->check())
                                @if(isset(Illuminate\Support\Facades\Auth::guard()->user()->avatar) && Illuminate\Support\Facades\Auth::guard()->user()->avatar != Null)
                                    <img src="{{ asset('assets/images/profile-images/provider/'.Illuminate\Support\Facades\Auth::guard()->user()->avatar)}}" class="img-radius"
                                         alt="User-Profile-Image">
                                @else
                                    <img src="{{ asset('assets/images/website-logo-icon/user.png') }}" class="img-radius"
                                         alt="User-Profile-Image">
                                @endif
                            @else
                                <img src="{{ asset('assets/images/website-logo-icon/user.png') }}" class="img-radius"
                                     alt="User-Profile-Image">
                            @endif

                            @if(Illuminate\Support\Facades\Auth::guard("on_demand")->check())
                                <span>{{ Illuminate\Support\Facades\Auth::guard()->user()->first_name }}</span>
                            @else
                                <span>{{ Illuminate\Support\Facades\Auth::guard()->user()->name }}</span>
                            @endif
                            <i class="feather icon-chevron-down"></i>
                        </div>
                        <ul class="show-notification profile-notification dropdown-menu"
                            data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">

                                @if(Illuminate\Support\Facades\Auth::guard("admin")->check() && Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1)
                                <li>
                                    <a href="@if(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1 || Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 4) {{ route('get:admin:change_password') }} @elseif(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 2) {{ route('get:dispatcher:change_password') }} @elseif(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 3) {{ route('get:account:change_password') }} @endif">
                                        <i class="feather icon-edit"></i> Change Password
                                    </a>
                                </li>
                                @elseif(Illuminate\Support\Facades\Auth::guard("on_demand")->check())
<!--                                    <li>
                                        <a href="{{ route('get:provider-admin:change_password') }}">
                                            <i class="feather icon-edit"></i> Change Password
                                        </a>
                                    </li>-->
                                @endif

                            <li>
                                @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                                    <a href="{{ route('admin:logout',[ 'admin' ]) }}"><i
                                                class="feather icon-log-out"></i>Logout</a>
                                @elseif(Illuminate\Support\Facades\Auth::guard("on_demand")->check())
                                    <a href="{{ route('provider:logout',[ 'on_demand' ]) }}"><i
                                                class="feather icon-log-out"></i>Logout</a>
                                @endif
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- [ Header ] end -->
