<!-- [ navigation menu ] start -->
<nav class="pcoded-navbar" id="render-navbar">
    <div class="nav-list">
        <div class="pcoded-inner-navbar main-menu">
            <div class="pcoded-navigation-label">Navigation</div>
            <ul class="pcoded-item pcoded-left-item">
                <li class="@if(Request::segment(2) === 'dashboard') active pcoded-trigger @endif">
                    <a  href="{{ route('get:provider-admin:dashboard') }}"
                            class="waves-effect waves-dark">
                        <span class="pcoded-micon"><i class="fas fa-home" style="font-size: 16px"></i></span>
                        <span class="pcoded-mtext">Dashboard</span>
                    </a>
                </li>
            </ul>
            {{--<ul class="pcoded-item pcoded-left-item">--}}
            {{--<li class="">--}}
            {{--<a--}}
            {{--href="{{ route('get:store-admin:store_service_dispatcher','new') }}"--}}
            {{--class="waves-effect waves-dark">--}}
            {{--<span class="pcoded-micon"><i class="fas fa-clipboard-list" style="font-size: 18px"></i></span>--}}
            {{--<span class="pcoded-mtext">Dispatcher</span>--}}
            {{--</a>--}}
            {{--</li>--}}
            {{--</ul>--}}

            <ul class="pcoded-item pcoded-left-item">
                <li class="">
                    <a href="{{ route('get:provider-admin:services') }}"
                       class="waves-effect waves-dark">
                        <span class="pcoded-micon"><i class="fas fa-wrench"></i></span>
                        <span class="pcoded-mtext">Services</span>
                    </a>
                </li>
            </ul>
            @if(request()->get("general_settings")->card_payment == 1)
                <ul class="pcoded-item pcoded-left-item">
                    <li class="">
                        <a href="{{ route('get:provider-admin:manage_card') }}" class="waves-effect waves-dark">
                            <span class="pcoded-micon"><i class="fas fa-id-card"></i></span>
                            <span class="pcoded-mtext">Card</span>
                        </a>
                    </li>
                </ul>
            @endif
            @if(request()->get("general_settings")->wallet_payment == 1)
                <ul class="pcoded-item pcoded-left-item">
                    <li class="">
                        <a href="{{ route('get:provider-admin:wallet') }}" class="waves-effect waves-dark">
                            <span class="pcoded-micon"><i class="fa fas fa-money-bill-wave"></i></span>
                            <span class="pcoded-mtext">wallet</span>
                        </a>
                    </li>
                </ul>
            @endif
            {{--<ul class="pcoded-item pcoded-left-item">--}}
            {{--<li class="">--}}
            {{--<a--}}
            {{--href="{{ route('get:store-admin:store_product_list') }}"--}}
            {{--class="waves-effect waves-dark">--}}
            {{--<span class="pcoded-micon"><i class="fas fa-store"></i></span>--}}
            {{--<span class="pcoded-mtext">Product List</span>--}}
            {{--</a>--}}
            {{--</li>--}}
            {{--</ul>--}}

            {{--<div class="pcoded-navigation-label">Customers</div>--}}
            {{--<ul class="pcoded-item pcoded-left-item">--}}
            {{--<li class="">--}}
            {{--<a--}}
            {{--                            href="{{ route('get:store-admin:store_provider_document') }}"--}}
            {{--class="waves-effect waves-dark">--}}
            {{--<span class="pcoded-micon"><i class="fas fa-id-card"></i></span>--}}
            {{--<span class="pcoded-mtext">Document</span>--}}
            {{--</a>--}}
            {{--</li>--}}

            {{--</ul>--}}
            <ul class="pcoded-item pcoded-left-item">
                <li class="pcoded-hasmenu">
                    <a href="javascript:void(0)" class="waves-effect waves-dark">
                        <span class="pcoded-micon"><i class="fas fa-shopping-basket"></i></span>
                        <span class="pcoded-mtext">Order List</span>
                    </a>
                    <ul class="pcoded-submenu">
                        <li class="">
                            <a
                                    href="{{ route('get:provider-admin:other_service_all_order_list',["pending"]) }}"
                                    class="waves-effect waves-dark">
                                <span class="pcoded-mtext">Pending Order List</span>
                            </a>
                        </li>
                        <li class="">
                            <a
                                    href="{{ route('get:provider-admin:other_service_all_order_list',["approved"]) }}"
                                    class="waves-effect waves-dark">
                                <span class="pcoded-mtext">Approved Order List</span>
                            </a>
                        </li>
                        <li class="">
                            <a
                                    href="{{ route('get:provider-admin:other_service_all_order_list',["rejected"]) }}"
                                    class="waves-effect waves-dark">
                                <span class="pcoded-mtext">Rejected Order List</span>
                            </a>
                        </li>
                        <li class="">
                            <a
                                    href="{{ route('get:provider-admin:other_service_all_order_list',["ongoing"]) }}"
                                    class="waves-effect waves-dark">
                                <span class="pcoded-mtext">Ongoing Order List</span>
                            </a>
                        </li>
                        <li class="">
                            <a
                                    href="{{ route('get:provider-admin:other_service_all_order_list',["completed"]) }}"
                                    class="waves-effect waves-dark">
                                <span class="pcoded-mtext">Completed Order List</span>
                            </a>
                        </li>
                        <li class="">
                            <a
                                    href="{{ route('get:provider-admin:other_service_all_order_list',["cancelled"]) }}"
                                    class="waves-effect waves-dark">
                                <span class="pcoded-mtext">Cancelled Order List</span>
                            </a>
                        </li>
                        {{--<li class="">--}}
                        {{--<a href="{{ route('get:admin:provider_list',["provider-services"]) }}" class="waves-effect waves-dark">--}}
                        {{--<span class="pcoded-mtext">Other Service Providers List</span>--}}
                        {{--</a>--}}
                        {{--</li>--}}
                    </ul>
                </li>
            </ul>
            {{--<div class="pcoded-navigation-label">General Settings</div>--}}
            <ul class="pcoded-item pcoded-left-item">
                <li class="">
                    <a href="{{ route('get:provider-admin:edit-profile') }}"
                       class="waves-dark">
                        <span class="pcoded-micon"><i class="fas fa-user"></i></span>
                        <span class="pcoded-mtext">Edit Profile</span>
                    </a>
                </li>
            </ul>

            @if(Illuminate\Support\Facades\Auth::guard('on_demand')->user()->login_type == "email")
                <ul class="pcoded-item pcoded-left-item">
                    <li class="">
                        <a href="{{ route('get:provider-admin:change_password') }}"
                                class="waves-effect waves-dark">
                            <span class="pcoded-micon"><i class="fas fa-key"></i></span>
                            <span class="pcoded-mtext">Change Password</span>
                        </a>
                    </li>
                </ul>
            @endif
            {{--<ul class="pcoded-item pcoded-left-item">--}}
                {{--<li class="">--}}
                    {{--<a href="{{ route('get:provider-admin:other_service_earning_report') }}"--}}
                            {{--class="waves-effect waves-dark">--}}
                        {{--<span class="pcoded-micon"><i class="far fa-money-bill-alt"></i></span>--}}
                        {{--<span class="pcoded-mtext">Earning Report</span>--}}
                    {{--</a>--}}
                {{--</li>--}}
            {{--</ul>--}}
            {{--<ul class="pcoded-item pcoded-left-item">--}}
            {{--<li class="">--}}
            {{--<a href="{{ route('store:logout',[ 'store' ]) }}"--}}
            {{--class="waves-effect waves-dark">--}}
            {{--<span class="pcoded-micon"><i class="fas fa-sign-out-alt"></i></span>--}}
            {{--<span class="pcoded-mtext">Logout</span>--}}
            {{--</a>--}}
            {{--</li>--}}
            {{--</ul>--}}

        </div>
    </div>
</nav>
<!-- [ navigation menu ] end -->
