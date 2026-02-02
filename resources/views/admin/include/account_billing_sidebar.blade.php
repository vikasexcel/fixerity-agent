<!-- [ navigation menu ] start -->
<nav class="pcoded-navbar" id="render-navbar">
    <div class="nav-list">
        <div class="pcoded-inner-navbar main-menu">
            <div class="pcoded-navigation-label">Navigation</div>

            <ul class="pcoded-item pcoded-left-item">
                <li class="@if(Request::segment(2) === 'dashboard') active pcoded-trigger @endif">
                    <a href="{{ route('get:admin:dashboard') }}" class="waves-effect waves-dark">
                        <span class="pcoded-micon"><i class="feather icon-home"></i></span>
                        <span class="pcoded-mtext">Dashboard</span>
                    </a>
                </li>
            </ul>

            <ul class="pcoded-item pcoded-left-item">
                <li class="pcoded-hasmenu">
                    <a href="javascript:void(0)" class="waves-effect waves-dark">
                        <span class="pcoded-micon"><i class="fas fa-shopping-bag"></i></span>
                        <span class="pcoded-mtext">Service List</span>
                    </a>
                    <ul class="pcoded-submenu">
                        <li class="">
                            <a href="{{ route('get:account:other_service_order_list') }}"
                               class="waves-effect waves-dark">
                                <span class="pcoded-mtext">All Services(Orders) List</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <ul class="pcoded-item pcoded-left-item">
                <li class="pcoded-hasmenu">
                    <a href="javascript:void(0)" class="waves-effect waves-dark">
                        <span class="pcoded-micon"><i class="fas fa-list-alt"></i></span>
                        <span class="pcoded-mtext">Payment Reports</span>
                    </a>
                    <ul class="pcoded-submenu">
                        <li class="">
                            <a href="{{ route('get:account:other_service_earning_report') }}" class="waves-effect waves-dark">
                                <span class="pcoded-mtext">On-demand Services Reports</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <ul class="pcoded-item pcoded-left-item">
                <li class="pcoded-hasmenu">
                    <a href="javascript:void(0)" class="waves-effect waves-dark">
                        <span class="pcoded-micon"><i class="fas fa-users-cog"></i></span>
                        <span class="pcoded-mtext">Setting</span>
                    </a>
                    <ul class="pcoded-submenu">
                        <li class="">
                            <a href="{{ route('get:account:profile') }}"
                               class="waves-effect waves-dark">
                                <span class="pcoded-mtext">Profile</span>
                            </a>
                        </li>
                        <li class="">
                            <a href="{{ route('get:account:change_password') }}" class="waves-effect waves-dark">
                                <span class="pcoded-mtext">Change Password</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- [ navigation menu ] end -->
