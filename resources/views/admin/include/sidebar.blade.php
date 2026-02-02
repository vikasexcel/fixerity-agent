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
            @foreach($admin_main_menu_list as $single_menu)
                {{--@foreach($single_menu['parent_menu'] as $parent)
                            <h6>{{$parent}}</h6>
                @endforeach--}}
                {{--<h6>{{$single_menu['parent_menu']['name']}}</h6>--}}
                <ul class="pcoded-item pcoded-left-item ">
                    <li class="@if(Request::segment(2) ===$single_menu['parent_menu']['module_name'] ) active pcoded-trigger @endif  {{{(count($single_menu['child_menu']) > 0)?"pcoded-hasmenu":""}}} " >
                        @if(count($single_menu['child_menu']) > 0)
                            <a href="javascript:void(0)" class="waves-effect waves-dark render_link">
                                <span class="pcoded-micon"><i class="fa fa-{{ $single_menu['parent_menu']['image']}} "></i></span>
                                <span class="pcoded-mtext">{{$single_menu['parent_menu']['name']}}</span>
                            </a>
                        @else
                            <a href="{{ route($single_menu['parent_menu']['route_path']) }}" class="waves-effect waves-dark render_link">
                                <span class="pcoded-micon"><i class="fa fa-{{ $single_menu['parent_menu']['image']}} "></i></span>
                                <span class="pcoded-mtext">{{$single_menu['parent_menu']['name']}}</span>
                            </a>
                        @endif
                        {{-- code check submenu--}}
                        @if(count($single_menu['child_menu']) > 0)
                            <ul class="pcoded-submenu">
                                @foreach($single_menu['child_menu'] as $child_menu)
                                    <li class="">
                                        @if(!empty($child_menu["route_path_arr"]))
                                            <a href="{{ route( $child_menu['route_path'],[$child_menu["route_path_arr"]] ) }}" class="waves-effect waves-dark">
                                                <span class="pcoded-mtext">{{$child_menu['name']}} </span>
                                            </a>
                                        @else
                                            <a href="{{ route( $child_menu['route_path']) }}" class="waves-effect waves-dark">
                                                <span class="pcoded-mtext">{{$child_menu['name']}} </span>
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        {{-- End code check submenu--}}
                    </li>
                </ul>
            @endforeach
            @if($admin_role == 1)
                <ul class="pcoded-item pcoded-left-item">
                    <li class="">
                        <a href="{{ route('get:admin:sub_admin_list') }}" class="waves-effect waves-dark">
                            <span class="pcoded-micon"><i class="fa fa-users"></i></span>
                            <span class="pcoded-mtext">Sub Admin</span>
                        </a>
                    </li>
                </ul>
            @endif
            {{--@foreach($admin_main_menu_list as $single_menu)

                <ul class="pcoded-item pcoded-left-item">
                    <li class="@if(Request::segment(2) === $single_menu->module_name) active pcoded-trigger @endif " >
                        <a href="{{ route($single_menu->route_path) }}" class="waves-effect waves-dark render_link">
                            <span class="pcoded-micon"><i class="feather {{$single_menu->image}} "></i></span>
                            <span class="pcoded-mtext">{{$single_menu->name}}</span>
                        </a>
                    </li>
                </ul>

            @endforeach--}}
        </div>
    </div>
</nav>
<!-- [ navigation menu ] end -->
