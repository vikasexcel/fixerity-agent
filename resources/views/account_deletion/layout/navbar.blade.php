<!-- [ Header ] start -->
<nav class="navbar header-navbar pcoded-header">
    <div class="navbar-wrapper">
        <div class="navbar-logo">
            {{--            <a class="render_link" href="{{ route('get:admin:dashboard') }}">--}}
            <a class="render_link" href="">
                @php $map_key = \App\Models\GeneralSettings::first() @endphp
                @if(isset($map_key) && $map_key->website_logo != Null)
                    <img class="img-fluid" src="{{ asset('assets/images/website-logo-icon/'.$map_key->website_logo)}}"
                         alt="{{$map_key->website_logo}}" style="max-width: 85%">
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
            </ul>
            <ul class="nav-right">
                <li class="user-profile header-notification">

                    <div class="dropdown-primary dropdown">
                        <div class="dropdown-toggle" data-toggle="dropdown">
                            <img src="{{ asset('assets/images/website-logo-icon/user.png')}}" class="img-radius"
                                 alt="User-Profile-Image">
                            <i class="feather icon-chevron-down"></i>
                        </div>
                        <ul class="show-notification profile-notification dropdown-menu"
                            data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">

                            <li>
                                @if(Illuminate\Support\Facades\Auth::guard("user")->check())
                                    <a href="{{ route('get:account:deletion:logout',[ 'guard' =>'user' ]) }}"><i
                                            class="feather icon-log-out"></i>Logout</a>
                                @elseif(Illuminate\Support\Facades\Auth::guard("on_demand")->check())
                                    <a href="{{ route('get:account:deletion:logout',[ 'guard' =>'on_demand' ]) }}"><i
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
