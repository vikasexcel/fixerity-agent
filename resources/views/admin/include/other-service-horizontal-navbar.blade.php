<style>
    /*.pcoded .pcoded-header{*/
    /*box-shadow: none;*/
    /*}*/
    #menu, #menu ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    #menu {
        width: 100%;
        /* margin: 60px auto; */
        border: 1px solid #fefefe;
        /*background: #fefefe;*/
        background: #ffffff;
        /* background: linear-gradient(#1ebdbe, #107677); */
        /*border-radius: 6px;*/
        box-shadow: 0 2px 2px #ccc;
    }

    #menu:before, #menu:after {
        content: "";
        display: block;
    }

    #menu:after {
        clear: both;
    }

    #menu li {
        float: left;
        /*background: url(../images/sep.png) no-repeat;*/
        position: relative;
    }

    #menu a {
        float: left;
        padding: 12px 30px;
        color: #666;
        text-decoration: none;
        /* text-transform: uppercase; */
        /* font: bold 12px Arial, Helvetica; */
    }

    #menu a i {
        width: 20px;
        font-size: 15px;
    }

    @php $category_type = \App\Http\Controllers\OtherServiceController::checkCategoryType($slug); @endphp
    @if($category_type == 3)
    #menu a:hover {
        color: #FFB64D;
        cursor: pointer;
    }

    @elseif($category_type == 4)
    #menu a:hover {
        color: #FF5370;
        cursor: pointer;
    }

    @else
    #menu a:hover {
        color: #42a5f5;
        cursor: pointer;
    }

    @endif

    #menu ul {
        margin: 20px 0 0 0;
        opacity: 0;
        visibility: hidden;
        position: absolute;
        top: 40px;
        left: 0;
        z-index: 99;
        /*background: #fefefe;*/
        background: #ffffff;
        /*background: linear-gradient(#1ebdbe, #107677);*/
        /*box-shadow: 0px -1px 0px rgba(255, 255, 255, .3);*/
        box-shadow: 0 2px 10px #ccc;
        border-radius: 3px;
        transition: all .2s ease-in-out;
    }

    #menu li:hover > ul {
        opacity: 1;
        visibility: visible;
        margin: 0;
    }

    #menu ul a i {
        width: 17px;
        font-size: 15px;
    }

    #menu ul ul {
        top: 0;
        /*left: 150px;*/
        position: absolute;
        left: 100%;
        margin: 0 0 0 20px;
        box-shadow: 0 2px 10px #ccc;
        /*box-shadow: 0px -1px 0px rgba(255, 255, 255, .3);*/
    }

    #menu ul a {
        padding: 10px;
        /*width: 130px;*/
        min-width: 200px;
        display: block;
        white-space: nowrap;
        float: none;
        text-transform: none;
    }

    @if($category_type == 3)
    #menu ul li a:hover i {
        /*background: linear-gradient(#1ebdbe, #107677);*/
        color: #FFB64D;
        position: relative;
        transition: 0.5s;
        transform: translateX(8px);
    }

    @elseif($category_type == 4)
    #menu ul li a:hover i {
        /*background: linear-gradient(#1ebdbe, #107677);*/
        color: #FF5370;
        position: relative;
        transition: 0.5s;
        transform: translateX(8px);
    }

    @else
    #menu ul li a:hover i {
        /*background: linear-gradient(#1ebdbe, #107677);*/
        color: #42a5f5;
        position: relative;
        transition: 0.5s;
        transform: translateX(8px);
    }

    @endif

    #menu ul li {
        display: block;
        position: relative;
    }

    #menu ul li:first-child > a:after {
        content: '';
        position: absolute;
        left: 40px;
        top: -6px;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-bottom: 6px solid #26354494;
        /*border-bottom: 6px solid #1ebdbe;*/
    }

    #menu ul ul li:first-child > a:after {
        left: -12px;
        top: 50%;
        margin-top: -6px;
        border-top: 6px solid transparent;
        border-right: 6px solid #26354494;
        /*border-right: 6px solid #107677;*/
        border-bottom: 6px solid transparent;
    }

    #menu-trigger { /* hide initially */
        display: none;
    }

    #menu-wrap {
        /*position: fixed;*/
        width: 100%;
        display: block;
        z-index: 1;
    }

    .page-header.card {
        /*margin: 70px 35px 15px;*/
        margin: 35px 35px 10px;
    }

    @media (max-width: 700px) {

        /*#menu-wrap {*/
        /*position: relative;*/
        /*}*/
        #menu-wrap * {
            box-sizing: border-box;
        }

        #menu-trigger {
            display: block;
            height: 40px;
            line-height: 40px;
            cursor: pointer;
            padding: 0 0 0 35px;
            border: 1px solid #fff;
            color: #666;
            font-weight: bold;
            background-color: #fefefe;
            box-shadow: 0 2px 2px #ccc;
            border-radius: 6px;
        }

        #menu {
            margin: 0;
            padding: 10px;
            position: absolute;
            top: 40px;
            width: 100%;
            z-index: 1;
            display: none;
            box-shadow: none;
        }

        #menu:after {
            content: '';
            position: absolute;
            left: 25px;
            top: -8px;
        }

        #menu ul {
            width: 92%;
            margin: 20px 0 0 0;
            opacity: 0;
            visibility: hidden;
            position: absolute;
            top: 38px;
            left: 4%;
            z-index: 99;
            background: #fefefe;
            /*background: linear-gradient(#1ebdbe, #107677);*/
            /*box-shadow: 0px -1px 0px rgba(255, 255, 255, .3);*/
            box-shadow: 0 2px 10px #ccc;
            border-radius: 3px;
            transition: all .2s ease-in-out;
        }

        #menu ul ul {
            top: 38px;
            /*left: 150px;*/
            position: absolute;
            left: 0;
            margin: 0 0 0 20px;
            box-shadow: 0 2px 10px #ccc;
            /*box-shadow: 0px -1px 0px rgba(255, 255, 255, .3);*/
        }

        #menu ul ul li:first-child > a:after {
            content: '';
            position: absolute;
            left: 40px;
            top: -6px;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-bottom: 6px solid #26354494;
        }

        #menu li, #menu a {
            float: none;
            position: relative;
            display: block;
            font-size: 15px !important;
        }

    }
</style>
<nav id="menu-wrap">
    <ul id="menu">
        <li><a class="render_link" href="{{ route('get:admin:other_service_dashboard',$slug) }}"><i
                        class="fa fa-home"></i>Summary</a></li>
        {{--<li><a class="render_link" href=""><i--}}
        {{--class="fas fa-list-alt"></i>Dispatcher </a></li>--}}
        <li>
            <a class="render_link" href="{{ route('get:admin:other_service_sub_category_list',$slug) }}">
                <i class="fas fa-list-alt"></i>Category
            </a>
        </li>
        <li>
            <a><i class="fa fa-user"></i>Providers</a>
            <ul>
                <li><a class="render_link"
                       href="{{ route('get:admin:other_service_add_provider',[$slug]) }}"><i
                                class="fa fa-angle-double-right"></i> Add Provider</a></li>
                <li><a class="render_link"
                       href="{{ route('get:admin:other_service_provider_list',[$slug,"approved"]) }}"><i
                                class="fa fa-angle-double-right"></i> Providers List</a></li>
                <li><a class="render_link"
                       href="{{ route('get:admin:other_service_provider_list',[$slug,"unapproved"]) }}"><i
                                class="fa fa-angle-double-right"></i> Un-Approved Providers</a></li>
                <li><a class="render_link"
                       href="{{ route('get:admin:other_service_provider_list',[$slug,"blocked"]) }}"><i
                                class="fa fa-angle-double-right"></i> Blocked Providers</a></li>
                <li><a class="render_link"
                       href="{{ route('get:admin:other_service_provider_list',[$slug,"rejected"]) }}"><i
                                class="fa fa-angle-double-right"></i> Rejected Providers</a></li>
            </ul>
        </li>
        <li><a><i class="fas fa-shopping-bag"></i>Orders</a>
            <ul>
                <li><a class="render_link" href="{{ route('get:admin:other_service_order_list',[$slug,"all"]) }}"><i
                                class="fa fa-angle-double-right"></i> All Orders</a></li>
                <li><a class="render_link" href="{{ route('get:admin:other_service_order_list',[$slug,"pending"]) }}"><i
                                class="fa fa-angle-double-right"></i> Pending Orders</a></li>
                <li><a class="render_link"
                       href="{{ route('get:admin:other_service_order_list',[$slug,"approved"]) }}"><i
                                class="fa fa-angle-double-right"></i> Approved Orders</a></li>
                <li><a class="render_link"
                       href="{{ route('get:admin:other_service_order_list',[$slug,"rejected"]) }}"><i
                                class="fa fa-angle-double-right"></i> Rejected Orders</a></li>
                <li><a class="render_link" href="{{ route('get:admin:other_service_order_list',[$slug,"ongoing"]) }}"><i
                                class="fa fa-angle-double-right"></i> Ongoing Orders</a></li>
                <li><a class="render_link" href="{{ route('get:admin:other_service_order_list',[$slug,"completed"]) }}"><i
                                class="fa fa-angle-double-right"></i> completed Orders</a></li>
                <li><a class="render_link" href="{{ route('get:admin:other_service_order_list',[$slug,"cancelled"]) }}"><i
                                class="fa fa-angle-double-right"></i> cancelled Orders</a></li>
            </ul>
        </li>
        <li>
            <a class="render_link" href="{{ route('get:admin:other_service_earning_report',$slug) }}">
                <i class="fa fa-file-text"></i> Earnings Reports
            </a>
        </li>
        <li>
            <a class="render_link" href="{{ route('get:admin:other_service_required_document_list',$slug) }}">
                <i class="fa fa-file-text"></i> Required Documents
            </a>
        </li>
        {{--<li>
            <a class="render_link" href="{{ route('get:admin:other_service_setting',$slug) }}">
                <i class="fa fa-file-text"></i> Service Settings
            </a>
        </li>--}}
        <li><a><i class="fa fa-cog"></i> Settings</a>
            <ul>
                <li><a class="render_link" href="{{ route('get:admin:other:promocode_list',$slug) }}"><i
                            class="fa fa-angle-double-right"></i> Promocode</a></li>
                <li><a class="render_link" href="{{ route('get:admin:other_service_setting',$slug) }}"><i
                            class="fa fa-angle-double-right"></i> Service Settings</a></li>
                <li><a class="render_link" href="{{ route('get:admin:on_demand_service_slider_list',$slug) }}"><i class="fa fa-angle-double-right"></i> Service Slider</a></li>
            </ul>
        </li>

    </ul>
</nav>
<script src="https://code.jquery.com/jquery-1.10.2.js"></script>
<script>
    $('#menu-wrap').prepend('<div id="menu-trigger">Menu</div>');
    $('#menu-trigger').on('click', function () {
        $('#menu').slideToggle();
    });
</script>
