@include('front.layout.login')
@include('front.layout.provider-register')

@if (\Request::routeIs('get:homepage') || \Request::routeIs('get:storehomepage') || \Request::routeIs('get:ride-booking') || \Request::routeIs('get:service-booking') )
    <!--banner header-->
    <div class="container-fluid banner-header" >
        <div class=" banner-header-top" id="header">
            <nav class="container navbar navbar-inverse">
                <div class="navbar-header">
                    <div id="logo" class="pull-left">
                        <h1>
                            <a href="{{ route('get:homepage') }}" class="scrollto">
                                <img alt="Fox Jek 2022" src="{{ isset($general_settings)? ($general_settings->website_logo != Null) ? asset('assets/images/website-logo-icon/'.$general_settings->website_logo) : asset('assets/images/website-logo-icon/logo.png') : asset('assets/images/website-logo-icon/logo.png') }}"/>
                            </a>
                        </h1>
                    </div>
                </div>
                <div id="mySidenav" class="sidenav">
                    @if (Illuminate\Support\Facades\Auth::guard("user")->check())
                        <div id="nav-menu-container" >
                            <ul class="nav-menu">
                                <li class="menu-active"><a href="{{ route('get:homepage') }}">Home</a></li>
                                <li class="nav-item dropdown">
                                    {!! \App\Models\User::getServiceList() !!}
                                </li>

                                <li><a href="{{ route('get:page_about_us') }}">About Us</a></li>
                                <li><a href="#contact">Contact Us</a></li>
                                <li class="dropdown">
                                    <button class="dropbtn"><a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                            <i class="fa fa-user pr-1"></i>
                                            @if(Illuminate\Support\Facades\Auth::guard("user")->check())
                                                {{ ucwords(Illuminate\Support\Facades\Auth::guard("user")->user()->first_name." ".Illuminate\Support\Facades\Auth::guard("user")->user()->last_name) }}<strong class="caret"></strong>
                                            @endif
                                        </a></button>
                                    <div class="dropdown-content">
                                        <a href="{{ route('get:user_profile') }}"><i class="fa fa-user pr-2" aria-hidden="true"></i> Profile</a>

                                        @if(Illuminate\Support\Facades\Auth::guard("user")->check() && Illuminate\Support\Facades\Auth::guard("user")->user()->login_type != "facebook" && Illuminate\Support\Facades\Auth::guard("user")->user()->login_type != "google")
                                            <a href="{{ route('get:user_change_password') }}"><i class="fa fa-pencil-alt pr-2" aria-hidden="true"></i> Change Password</a>

                                        @endif
                                        <a href="{{route('get:user_orders_all',["transport","all"]) }}"><i class="fa fa-shopping-basket pr-2" aria-hidden="true"></i> All Orders</a>
<!--                                        <a href="{{ route('get:user_offers') }}"><i class="fa fa-percent pr-2" aria-hidden="true"></i> Offers</a>-->
                                        <a href="{{ route('get:user_wallet') }}"><i class="fa fa-wallet pr-2" aria-hidden="true"></i> Manage Wallet</a>
                                        <a href="{{ route('get:user_payments') }}"><i class="fa fa-credit-card pr-2" aria-hidden="true"></i> Manage Cards</a>
                                        <a href="{{ route('get:user_address') }}"><i class="fa fa-map-marker pr-2" aria-hidden="true"></i> Addresses</a>
                                        <a href="{{ route('get:my_coupons') }}" title="My Coupons"><i class="fa fa-tag pr-2" aria-hidden="true"></i> My Coupons</a>
        {{--                                <a class="btn-log-in" href="{{ route('get:user_logout')}}"></a>--}}
                                            <a class="btn-log-in" href="{{ route('user:logout',[ 'user' ]) }}">
                                                <i class="fa fa-sign-out-alt pr-2" aria-hidden="true"></i> Logout
                                            </a>
                                    </div>
                                </li>

                                <li><a class="btn-log-in" href="{{ route('get:checkout') }}">
                                    <i class="fa fa-shopping-cart" aria-hidden="true"></i>

                                    <span class="count-cart">{{ \App\Models\User::navCartCount()}}</span>
                                </a>
                                </li>
                            </ul>
                        </div>
                    @else
                        <div id="nav-menu-container" >
                                    <ul class="nav-menu">
<!--                                        <li>
                                            <form>
                                                <input type="search" placeholder="Search">
                                            </form>
                                        </li>-->
<!--                                        <li class="menu-active">
                                            <select class="form-control Langchange">
                                                <option value="en" {{ session()->get('locale') == 'en' ? 'selected' : '' }}>English</option>
                                                <option value="pt" {{ session()->get('locale') == 'pt' ? 'selected' : '' }}>Portugal</option>
                                            </select>
                                        </li>-->
                                        <li class="menu-active"><a href="{{ route('get:homepage') }}">Home</a></li>
                                        <li class="nav-item dropdown">
                                            {!! \App\Models\User::getServiceList() !!}
                                        </li>
                                        <li><a href="{{ route('get:page_about_us') }}">About Us</a></li>
                                        <li><a href="#contact">Contact Us</a></li>
                                        <li><a class="btn-log-in" href="#"  onclick="document.getElementById('user').style.display='block'">
                                                Login / Register <i class="fa fa-user-circle" aria-hidden="true"></i></a>
                                        </li>
                                    </ul>
                                </div>
                    @endif
                </div>
                @if (Illuminate\Support\Facades\Auth::guard("user")->check())
                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav navbar-right nav-other users-dropdown">
                            <li>
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <i class="fa fa-user"></i>
                                    @if(Illuminate\Support\Facades\Auth::guard("user")->check())
                                        {{ ucwords(Illuminate\Support\Facades\Auth::guard("user")->user()->first_name." ".Illuminate\Support\Facades\Auth::guard("user")->user()->last_name) }}<strong class="caret"></strong>
                                    @endif
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="{{ route('get:user_profile') }}">Profile</a>
                                    </li>
                                    @if(Illuminate\Support\Facades\Auth::guard("user")->check() && Illuminate\Support\Facades\Auth::guard("user")->user()->login_type != "facebook" && Illuminate\Support\Facades\Auth::guard("user")->user()->login_type != "google")
                                        <li>
                                            <a href="{{ route('get:user_change_password') }}">Change Password</a>
                                        </li>
                                    @endif
                                    <li>
                                        <a href="{{route('get:user_orders_all',["transport","all"]) }}">Al Orders</a>
                                    </li>
<!--                                    <li>
                                        <a href="{{ route('get:user_offers') }}">Offers</a>
                                    </li>-->
                                    <li>
                                        <a href="{{ route('get:user_payments') }}">Payments</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('get:user_address') }}">Addresses</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('get:my_coupons') }}" title="My Coupons">My Coupons</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('get:user_logout') }}">LogOut</a>
                                        <a href="{{ route('user:logout',[ 'user' ]) }}">LogOut</a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                @else
                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav navbar-right">
                            <li class="btn-log-in"><a onclick="document.getElementById('user').style.display='block'">
                                    Login / Register</a>
                            </li>
                        </ul>
                    </div>
                @endif
            </nav>
            <div class="clearfix"></div>
        </div>
    </div>
@else
    <div class=" banner-header" >
        <div class=" banner-header-top" id="header">
            <nav class="container-fluid" >
                <div class="navbar-header">
                    <div id="logo" class="pull-left">
                        <h1>
                            <a href="{{ route('get:homepage') }}" class="scrollto" >
                                <img alt="Fox Jek 2022" src="{{ isset($general_settings)? ($general_settings->website_logo != Null) ? asset('assets/images/website-logo-icon/'.$general_settings->website_logo) : asset('assets/images/website-logo-icon/logo.png') : asset('assets/images/website-logo-icon/logo.png') }}"/>
                            </a>
                        </h1>
                    </div>
                </div>
                <div id="mySidenav" class="sidenav">
                    @if (Illuminate\Support\Facades\Auth::guard("user")->check())
                        <div id="nav-menu-container" >
                            <ul class="nav-menu">

                                <li class="menu-active"><a href="{{ route('get:homepage') }}">Home</a></li>
                                <li class="nav-item dropdown">
                                    {!! \App\Models\User::getServiceList() !!}
                                </li>
                                <li><a href="{{ route('get:page_about_us') }}">About Us</a></li>
                                <li><a href="#contact">Contact Us</a></li>

                                <li class="dropdown">
                                    <button class="dropbtn"><a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                            <i class="fa fa-user"></i>
                                            @if(Illuminate\Support\Facades\Auth::guard("user")->check())
                                                {{ ucwords(Illuminate\Support\Facades\Auth::guard("user")->user()->first_name." ".Illuminate\Support\Facades\Auth::guard("user")->user()->last_name) }}
                                            @endif
                                        </a></button>
                                    <div class="dropdown-content">
                                        <a href="{{ route('get:user_profile') }}"><i class="fa fa-user pr-2" aria-hidden="true"></i> Profile</a>

                                        @if(Illuminate\Support\Facades\Auth::guard("user")->check() && Illuminate\Support\Facades\Auth::guard("user")->user()->login_type != "facebook" && Illuminate\Support\Facades\Auth::guard("user")->user()->login_type != "google")
                                            <a href="{{ route('get:user_change_password') }}"><i class="fa fa-pencil-alt pr-2" aria-hidden="true"></i> Change Password</a>

                                        @endif
<!--                                        <a href="{{ route('get:user_orders') }}"><i class="fa fa-shopping-basket" aria-hidden="true"></i> Orders</a>-->
                                        <a href="{{route('get:user_orders_all',["transport","all"]) }}"><i class="fa fa-shopping-basket pr-2" aria-hidden="true"></i> All Orders</a>

<!--                                        <a href="{{ route('get:user_offers') }}"><i class="fa fa-percent pr-2" aria-hidden="true"></i> Offers</a>-->

                                        <a href="{{ route('get:user_wallet') }}"><i class="fa fa-wallet pr-2" aria-hidden="true"></i> Manage Wallet</a>
                                        <a href="{{ route('get:user_payments') }}"><i class="fa fa-credit-card pr-2" aria-hidden="true"></i> Manage Cards</a>
                                        <a href="{{ route('get:user_address') }}"><i class="fa fa-map-marker pr-2" aria-hidden="true"></i> Addresses</a>
                                        <a href="{{ route('get:my_coupons') }}" title="My Coupons"><i class="fa fa-tag pr-2" aria-hidden="true"></i> My Coupons</a>
                                        {{--                                <a class="btn-log-in" href="{{ route('get:user_logout')}}"></a>--}}
                                        <a class="btn-log-in" href="{{ route('user:logout',[ 'user' ]) }}">
                                            <i class="fa fa-sign-out-alt pr-2" aria-hidden="true"></i> Logout
                                        </a>
                                    </div>
                                </li>

                                <li><a class="btn-log-in" href="{{ route('get:checkout') }}">
                                        <i class="fa fa-shopping-cart" aria-hidden="true"></i>

                                        <span class="count-cart">{{ \App\Models\User::navCartCount()}}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    @else
                        <div id="nav-menu-container" >
                            <ul class="nav-menu">
<!--                                <li>
                                    <form>
                                        <input type="search" placeholder="Search">
                                    </form>
                                </li>-->
                                <li class="menu-active"><a href="{{ route('get:homepage') }}">Home</a></li>
                                <li class="nav-item dropdown">
                                    {!! \App\Models\User::getServiceList() !!}
                                </li>
                                <li><a href="{{ route('get:page_about_us') }}">About Us</a></li>
                                <li><a href="#contact">Contact Us</a></li>
                                <li><a class="btn-log-in" href="#"  onclick="document.getElementById('user').style.display='block'">
                                        Login / Register <i class="fa fa-user-circle" aria-hidden="true"></i></a>
                                </li>
                            </ul>
                        </div>
                    @endif
                </div>
                @if (Illuminate\Support\Facades\Auth::guard("user")->check())

<!--                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav navbar-right nav-other users-dropdown">
                            <li>
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <i class="fa fa-user"></i>
                                    @if(Illuminate\Support\Facades\Auth::guard("user")->check())
                                        {{ ucwords(Illuminate\Support\Facades\Auth::guard("user")->user()->first_name." ".Illuminate\Support\Facades\Auth::guard("user")->user()->last_name) }}<strong class="caret"></strong>
                                    @endif
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="{{ route('get:user_profile') }}">Profile</a>
                                    </li>
                                    @if(Illuminate\Support\Facades\Auth::guard("user")->check() && Illuminate\Support\Facades\Auth::guard("user")->user()->login_type != "facebook" && Illuminate\Support\Facades\Auth::guard("user")->user()->login_type != "google")
                                        <li>
                                            <a href="{{ route('get:user_change_password') }}">Change Password</a>
                                        </li>
                                    @endif
                                    <li>
                                        <a href="{{ route('get:user_orders') }}">Orders</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('get:user_offers') }}">Offers</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('get:user_payments') }}">Payments</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('get:user_address') }}">Addresses</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('user:logout',[ 'user' ]) }}">LogOut</a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>-->

                @else

                   <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav navbar-right">
                            <li class="btn-log-in"><a onclick="document.getElementById('user').style.display='block'">
                                    Login / Register</a>
                            </li>
                        </ul>
                    </div>

                @endif
            </nav>
            <div class="clearfix"></div>
        </div>
    </div>
@endif
