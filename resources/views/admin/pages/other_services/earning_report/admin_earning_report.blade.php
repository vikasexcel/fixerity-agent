@extends('admin.layout.super_admin')
@section('title')
    Earnings Report
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">

    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet"/>
    <style>
        .document a {
            color: #4099ff;
            font-weight: bold;
        }
        .product a {
            background: #4099ff;
            color: white;
            padding: 2px 5px;
            border-radius: 25px;
        }

        .icon-list-demo i {
            height: auto;
            line-height: 10px;
            border: none;
            margin-right: 5px;
            color: #4099ff;
        }

        table th, table td {
            /*padding: 5px !important;*/
            padding-top: 15px !important;
            padding-left: 5px !important;
            padding-right: 5px !important;
            padding-bottom: 5px !important;
            font-size: 12px;
            /*max-width: 20px !important;*/
            word-wrap: break-word !important;
            white-space: pre-line;
        }

        table .th-checkbox {
            max-width: 14px !important;
            padding: 0;
        }

        ul.set-date {
            list-style-type: none;
            margin: 0;
            padding: 0;
            overflow: hidden;
            /*background-color: #333;*/
        }

        ul.set-date li {
            float: left;
            margin-left: 20px;
            padding-right: 16px;
            border-right: 2px solid #21252a;
        }

        ul.set-date li:last-of-type {
            border-right: none;
        }

        ul.set-date li:first-of-type {
            margin-left: 0;
        }

        ul.set-date li a {
            display: block;
            color: #2a455f !important;
            text-align: center;
            /*padding: 14px 16px;*/
            text-decoration: underline !important;
            /*border: 1px solid green;*/
        }

        /*day selection style*/

        ul.set-date li a:hover {
            color: #07C !important;
            text-decoration: none !important;
            cursor: pointer;
        }

        .select2-container {
            width: 100% !important;
            vertical-align: unset;
        }

        .select2-container--default .select2-selection--single {
            height: auto;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            /*padding-top: 1px;*/
            padding: 4px 30px 4px 20px;
            background-color: transparent;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 34px;
        }

        .datetimepicker-dropdown-bottom-left {
            width: 250px;
        }

        .datetimepicker table {
            width: 100%;
        }

        /*.gray_text {*/
        /*    color: #999;*/
        /*}*/

        .select_border, select.form-control, select.form-control:focus, select.form-control:hover {
            border: 1px solid #aaa;
            border-radius: 4px;
            padding: 5px;
        }

        #to_date, #from_date {
            background: white;
            border: 1px solid #aaa;
            border-radius: 4px;
        }

        .black_text {
            color: black;
        }

        .order_checkbox {
            padding-top: 15px !important;
            padding-bottom: 0 !important;

        }

        .checkbox_container {
            display: block;
            position: relative;
            /*padding-left: 35px;*/
            /*margin-bottom: 12px;*/
            height: 0px;
            margin-top: -25px;
            cursor: pointer;
            font-size: 22px;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* Hide the browser's default checkbox */
        .checkbox_container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        /* Create a custom checkbox */
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 18px;
            width: 18px;
            background-color: white;
            border: 1px solid #2196F3;
        }

        /* On mouse-over, add a grey background color */
        .checkbox_container:hover input ~ .checkmark {
            /*background-color: #ccc;*/
            background-color: #42a5f54f;
        }

        /* When the checkbox is checked, add a blue background */
        .checkbox_container input:checked ~ .checkmark {
            background-color: #2196F3;
        }

        /* Create the checkmark/indicator (hidden when not checked) */
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        /* Show the checkmark when checked */
        .checkbox_container input:checked ~ .checkmark:after {
            display: block;
        }

        /* Style the checkmark/indicator */
        .checkbox_container .checkmark:after {
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 3px 3px 0;
            -webkit-transform: rotate(45deg);
            -ms-transform: rotate(45deg);
            transform: rotate(45deg);
        }
        .datetimepicker.datetimepicker-dropdown-bottom-right.dropdown-menu {
            display: none !important;
        }
    </style>
@endsection
@section('page-content')
    <div class="pcoded-content">
        @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
            @if(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1 || Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 4)
                <div class="external-horizontal-nav">
                    @include('admin.include.other-service-horizontal-navbar')
                </div>
            @endif
        @endif
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5>Earnings Report</h5>
                                    <span>All Earnings Report</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>@if(isset($service_category)) {{ ucwords(strtolower($service_category->name)) }} @endif Provider Earnings Report</h5>
                            </div>
                            <div class="card-block">
                                <form method="post" id="search_form" action="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? (Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1 || Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 4)? route('post:admin:other_service_earning_report',$slug) : '' : '' }}">
                                    {{ csrf_field() }}
                                    <div class="row">
                                        <div class="col-lg-12 date-wrapper">
                                            <div class="form-group">
                                                <ul class="set-date">
                                                    <li><a id="today">Today</a></li>
                                                    <li><a id="yesterday">Yesterday</a></li>
                                                    <li><a id="this_week">This Week</a></li>
                                                    <li><a id="this_month">This Month</a></li>
                                                    <li><a id="last_month">Last Month</a></li>
                                                    <li><a id="this_year">This Year</a></li>
                                                    <li><a id="last_year">Last Year</a></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!--from-->
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <div class="input-group date form_datetime">
                                                    <input name="from_date" class="form-control category"
                                                           value="{{isset($from_date)? ($from_date != Null)?  date('d-m-Y',strtotime($from_date)) : old('from_date') : old('from_date') }}"
                                                           placeholder="From Date"
                                                           id="from_date"
                                                           type="text" readonly>
                                                    <span class="input-group-append" id="basic-addon3">
                                                <label class="bg-c-green input-group-text">
                                                    <span class="fa fa-remove remove_from_date "></span>
                                                </label>
                                                </span>
                                                    <span class="input-group-append" id="basic-addon3">
                                                    <label class="bg-c-green input-group-text">
                                                        <span class="fa fa-th"></span>
                                                    </label>
                                                </span>
                                                </div>
                                            </div>
                                        </div>
                                        <!--to-->
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <div class="input-group date to_datetime">
                                                    <input name="to_date" class="form-control category"
                                                           value="{{isset($to_date)? ($to_date != Null)?  date('d-m-Y',strtotime($to_date)) : old('to_date') : old('to_date') }}"
                                                           placeholder="To Date"
                                                           id="to_date"
                                                           type="text" readonly>
                                                    <span class="input-group-append" id="basic-addon3">
                                                <label class="bg-c-green input-group-text">
                                                    <span class="fa fa-remove remove_to_date"></span>
                                                </label>
                                                </span>
                                                    <span class="input-group-append" id="basic-addon3">
                                                    <label class="bg-c-green input-group-text">
                                                        <span class="fa fa-th"></span>
                                                    </label>
                                                </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <select id="js-example2" name="user" class="js-example-placeholder-single1 js-states form-control">
                                                    <option disabled selected value=""></option>
                                                    <option disabled>Select Customer</option>
                                                    @if(isset($user_list))
                                                        @if(!$user_list->isEmpty())
                                                            @foreach($user_list as $key => $user_details)
                                                                {{ $selected = isset($user)? ($user != Null)? ($user == $user_details->id)?  "selected" : "" : "" : "" }}
                                                                <option value="{{ $user_details->id }}"
                                                                    {{ $selected }}>
                                                                    {{ $user_details->contact_number." ".ucwords($user_details->first_name." ".$user_details->last_name) }}</option>
                                                            @endforeach
                                                        @endif
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <select name="payment_type" class="form-control select_border gray_text" id="payment_type" onchange="this.className=this.options[this.selectedIndex].className">
                                                    <option class="form-control select_border gray_text" value="">Select Payment Type</option>
                                                    <option value="1" {{ $selected = ( (isset($payment_type) && $payment_type == "1")? "selected" : "" ) }} class="form-control select_border black_text">Cash</option>
                                                    <option value="2" {{ $selected = ( (isset($payment_type) && $payment_type == "2")? "selected" : "" ) }} class="form-control select_border black_text">Card</option>
                                                    <option value="3" {{ $selected = ( (isset($payment_type) && $payment_type == "3")? "selected" : "" ) }} class="form-control select_border black_text">Wallet</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <select id="js-example1" name="provider" class="js-example-placeholder-single1 js-states form-control">
                                                    <option disabled selected value=""></option>
                                                    <option disabled>Select Provider</option>
                                                    @if(isset($provider_list))
                                                        @if(!$provider_list->isEmpty())
                                                            @foreach($provider_list as $key => $provider_details)
                                                                {{ $selected = isset($provider)? ($provider != Null)? ($provider == $provider_details->provider_id)?  "selected" : "" : "" : "" }}
                                                                <option value="{{ $provider_details->provider_id }}" {{ $selected }}>{{ $provider_details->contact_number." ".ucwords($provider_details->name)  }}</option>
                                                            @endforeach
                                                        @endif
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <select name="provider_pay_type" class="form-control select_border gray_text" id="provider_pay_type" onchange="this.className=this.options[this.selectedIndex].className">
                                                    <option class="form-control select_border gray_text" value="">Select Provider Payment Status</option>
                                                    @if(isset($provider_pay_type) && $provider_pay_type == 1)
                                                        <option value="1" selected class="form-control select_border black_text">Settled</option>
                                                        <option value="0" class="form-control select_border black_text">Unsettled</option>
                                                    @elseif(isset($provider_pay_type) && $provider_pay_type == 0)
                                                        <option value="1" class="form-control select_border black_text">Settled</option>
                                                        <option value="0" selected class="form-control select_border black_text">Unsettled</option>
                                                    @else
                                                        <option value="1" class="form-control select_border black_text">Settled</option>
                                                        <option value="0" class="form-control select_border black_text">Unsettled</option>
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-12 text-center">
                                            <div class="form-group">
                                                <input type="submit" class="btn btn-default" value="Search">
                                                <a href="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? (Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1 || Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 4)? route('get:admin:other_service_earning_report',$slug) : '' : ''}}" class="render_link">
                                                    <input type="button" id="reset" class="btn btn-default" value="Clear">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <form>
                                    {{ csrf_field() }}
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="dt-responsive table-responsive" style="{{(isset($package_order_list) && !$package_order_list->isEmpty())? '' : 'display:none' }}">
                                                <table id="new-cons" style="max-width:100% !important;" class="table table-bordered">
                                                    <thead>
                                                    <tr>
                                                        <th id="th-checkbox" style="min-width: 30px;">
                                                            <label class="checkbox_container">
                                                                <input type="checkbox" id="all_check">
                                                                <span class="checkmark"></span>
                                                            </label>
                                                        </th>
                                                        <th class="extra-4">Provider Payment Status</th>
                                                        <th class="extra-4">Customer Name</th>
                                                        <th class="extra-2">Provider Name</th>
                                                        <th class="extra-3">Order Id</th>
                                                        <th>Order Date</th>
                                                        {{--<th>Order Type</th>--}}
                                                        <th class="extra">Total Order Amount</th>
                                                        <th class="extra">Promo</th>
                                                        <th class="extra">Extra Amount</th>
                                                        <th>Tax</th>
                                                        <th>Tip</th>
                                                        <th>Refer Discount</th>
                                                        <th>Total Paid by User</th>
                                                        <th>Site Commission</th>
                                                        <th>Pay to Provider</th>
                                                        <th class="extra">Collect From Provider</th>
                                                        <th>Type</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @if(isset($package_order_list))
                                                        @foreach($package_order_list as $order)
                                                            @php $service_time = explode("-", $order->service_time);
                                                                $start_time = date("H:i",strtotime(trim($service_time[0])));
                                                                $end_time = date("H:i",strtotime(trim($service_time[1]))); @endphp
                                                            <tr>
                                                                <td id="th-checkbox" class="order_checkbox">
                                                                    @if($order->provider_pay_settle_status != 1)
                                                                        <label class="checkbox_container">
                                                                            <input type="checkbox" class="order_check"
                                                                                   provider_pay_settle_type="{{ $order->provider_pay_settle_status == 1 ? 1 : 0 }}"
                                                                                   {{--                                                                                   provider_pay_amount="{{ $pay_payment[$order->id] }}"--}}
                                                                                   {{--                                                                                   provider_collect_amount="{{ $collect_payment[$order->id] }}"--}}
                                                                                   provider_pay_amount="{{ ($order->payment_type == 1)?0:$order->provider_amount }}"
                                                                                   provider_collect_amount="{{ ($order->payment_type == 1)?$order->total_pay - $order->provider_amount:0 }}"
                                                                                   order_id="{{ $order->id }}"
                                                                                   name="order_id[{{$order->id}}]">
                                                                            <span class="checkmark"></span>
                                                                        </label>
                                                                    @endif
                                                                </td>
                                                                <td class="extra-4">{{ ($order->provider_pay_settle_status == 1) ? "Settled" : "Unsettled" }}</td>
                                                                <th class="extra-4">{{ $order->user_name }}</th>
                                                                <th class="extra-2">{{ $order->provider_name }}</th>
                                                                <th class="extra-2">{{ $order->order_no }}</th>
                                                                <td>{{ \Carbon\Carbon::parse($order->service_date_time)->format('d/m/y') ." \n ". $start_time . " - " . $end_time }}</td>
                                                                {{--<td class="extra-3">{{ $order->order_type == 1 ? "Schedule" : "Order Now" }}</td>--}}
                                                                <td class="extra "><span class="currency"></span> {{ $order->total_item_cost }}
                                                                <td><span class=""><span class="currency"></span> {{ isset($order->discount_amount) ? $order->discount_amount : 0 }}</span></td>
                                                                <td><span class=""><span class="currency"></span> {{ isset($order->extra_amount) ? round($order->extra_amount,2) : 0 }}</span></td>
                                                                <td class="extra "><span class="currency"></span> {{ $order->tax }}</td>
                                                                <td class="extra "><span class="currency"></span> {{ $order->tip }}</td>
                                                                <td class="extra "><span class="currency"></span> {{ $order->refer_discount }}</td>
                                                                <td class="extra "><span class="currency"></span> {{ $order->total_pay }}</td>
                                                                <td class="extra "><span class="currency"></span> {{ $order->admin_commission }}</td>
                                                                <td class=""><span class="currency"></span> {{ $order->payment_type != 1 ? $order->provider_amount : 0 }}</td>
                                                                <td class=""><span class="currency"></span> {{ $order->payment_type == 1 ? ($order->total_pay - $order->provider_amount) : 0 }}</td>
                                                                <td>{{ $order->payment_type != 1 ? ($order->payment_type == 2 ? "Card" : "Wallet") : "Cash" }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                    </tbody>
                                                    <tfoot>
                                                    @if(isset($package_order_list) && !$package_order_list->isEmpty())
                                                        <tr>
                                                            <th colspan="15" style="text-align: right">Total Amount</th>
                                                            <td class=""><span class="currency"></span> {{ isset($total_amount) ? $total_amount : 0 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="15" style="text-align: right">Total Site Commission</th>
                                                            <td class=""><span class="currency"></span> {{ isset($site_commission) ? $site_commission : 0 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="15" style="text-align: right">Total Provider Earning</th>
                                                            <td class=""><span class="currency"></span> {{ isset($provider_earning) ? $provider_earning : 0 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="15" style="text-align: right">Total Refer Discount</th>
                                                            <td class=""><span class="currency"></span> {{ isset($refer_discount) ? $refer_discount : 0 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="15" style="text-align: right">Total Promo Code Discount</th>
                                                            <td class=""><span class="currency"></span> {{ isset($total_discount) ? $total_discount : 0 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="15" style="text-align: right">Collect From Provider</th>
                                                            <td class=""><span class="currency"></span> {{ isset($collect_from_provider) ? $collect_from_provider : 0 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="15" style="text-align:right">Total Provider Outstanding Amount:</th>
                                                            <th class=" "><span class="currency"></span> @if(isset($total_provider_outstanding_amount)) {{ $total_provider_outstanding_amount }}  @else 0 @endif </th>
                                                        </tr>
                                                    @endif
                                                    </tfoot>
                                                </table>
                                            </div>
                                            <div class="text-center">
                                                @if(isset($package_order_list) && !$package_order_list->isEmpty())
                                                    <div class="text-center">
                                                        <span class="btn btn-success provider_payment"><b>Mark As Settle</b></span>
                                                    </div>
                                                @else
                                                    <span>No Records Found</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}" type="text/javascript"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.print.min.js"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}" type="text/javascript"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            $('#new-cons').DataTable({
                dom: 'Bfrtip',
                searching: false,
                bPaginate: false,
                buttons: [{
                    extend: 'excel',
                    text: 'Download Excel',
                    footer: true
                }]
            });
        });
    </script>

    {{--Model Script type detials--}}
    <script src="{{ asset('assets/js/classie.js')}}" type="text/javascript"></script>

    <script src="{{ asset("assets/js/select2.js") }}"></script>
    <script>
        $('#all_check').on('click', function () {
            $('.order_check').not(this).prop('checked', this.checked);
        });
        $("#js-example1").select2({
            placeholder: "Select a Provider",
            allowClear: true,
        });
        $("#js-example2").select2({
            placeholder: "Select a Customer",
            allowClear: true,
        });

        $('#all_settled').change(function () {
            var oTable = $('#custm-tool-ele').dataTable();
            oTable.find('input').each(function (i) {
                if ($(this).attr('name') != 'checkAll') {
                    var isSelected = $(this).is(':checked');
                    if (isSelected) {
                        $('#all_settled').prop("checked", false);
                        $(this).prop("checked", false);
                    } else {
                        $('#all_settled').prop("checked", true);
                        $(this).prop("checked", true);
                    }
                }
            });
            return false;
        });

        $("#today").click(function () {
            var current_date = new Date();
            var year = current_date.getFullYear();
            var month = (current_date.getMonth() + 1);
            var date = (current_date.getDate());
            var get_from_dt = (date < 10 ? '0' : '') + date + '-' + (month < 10 ? '0' : '') + month + '-' + year;
            $("#from_date").val(get_from_dt);
            $("#to_date").val(get_from_dt);
        });

        $("#yesterday").click(function () {

            var current_date = new Date();
            current_date.setDate(current_date.getDate() - 1);
            var year = current_date.getFullYear();
            var month = (current_date.getMonth() + 1);
            var date = (current_date.getDate());
            var get_from_dt = (date < 10 ? '0' : '') + date + '-' + (month < 10 ? '0' : '') + month + '-' + year;
            $("#from_date").val(get_from_dt);
            $("#to_date").val(get_from_dt);
        });

        $("#this_week").click(function () {
            var curr = new Date; // get current date
            curr.setDate(curr.getDate() - 1);
            var first = curr.getDate() - curr.getDay();
            var last = first + 6; // last day is the first day + 6

            var firstday = new Date(curr.setDate(first));
            var firstyear = firstday.getFullYear();
            var firstmonth = (firstday.getMonth() + 1);
            var firstdate = (firstday.getDate());
            var get_from_dt = (firstdate < 10 ? '0' : '') + firstdate + '-' + (firstmonth < 10 ? '0' : '') + firstmonth + '-' + firstyear;

            var lastday = new Date(curr.setDate((curr.getDate() - curr.getDay()) + 6));
            var lastyear = lastday.getFullYear();
            var lastmonth = (lastday.getMonth() + 1);
            var lastdate = (lastday.getDate());
            var get_to_dt = (lastdate < 10 ? '0' : '') + lastdate + '-' + (lastmonth < 10 ? '0' : '') + lastmonth + '-' + lastyear;

            $("#from_date").val(get_from_dt);
            $("#to_date").val(get_to_dt);
        });

        $("#this_month").click(function () {
            var date = new Date();
            var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
            var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);

            var year = firstDay.getFullYear();
            var month = (firstDay.getMonth() + 1);
            var date = (firstDay.getDate());
            var get_from_dt = (date < 10 ? '0' : '') + date + '-' + (month < 10 ? '0' : '') + month + '-' + year;
            var year = lastDay.getFullYear();
            var month = (lastDay.getMonth() + 1);
            var date = (lastDay.getDate());
            var get_to_dt = (date < 10 ? '0' : '') + date + '-' + (month < 10 ? '0' : '') + month + '-' + year;

            $("#from_date").val(get_from_dt);
            $("#to_date").val(get_to_dt);
        });

        $("#last_month").click(function () {
            var now = new Date();
            var prevMonthLastDate = new Date(now.getFullYear(), now.getMonth(), 0);
            var prevMonthFirstDate = new Date(now.getFullYear() - (now.getMonth() > 0 ? 0 : 1), (now.getMonth() - 1 + 12) % 12, 1);

            var formatDateComponent = function (dateComponent) {
                return (dateComponent < 10 ? '0' : '') + dateComponent;
            };
            var formatDate = function (date) {
                return formatDateComponent(date.getDate()) + '-' + formatDateComponent(date.getMonth() + 1) + '-' + date.getFullYear();
            };
            $("#from_date").val(formatDate(prevMonthFirstDate));
            $("#to_date").val(formatDate(prevMonthLastDate));
        });

        $("#this_year").click(function () {
            var date = new Date();
            var get_from_dt = "01" + '-' + "01" + '-' + (new Date()).getFullYear();
            var get_to_dt = "31" + '-' + "12" + '-' + (new Date()).getFullYear();

            $("#from_date").val(get_from_dt);
            $("#to_date").val(get_to_dt);
        });

        $("#last_year").click(function () {
            var date = new Date();
            var get_from_dt = "01" + '-' + "01" + '-' + ((new Date()).getFullYear() - 1);
            var get_to_dt = "31" + '-' + "12" + '-' + ((new Date()).getFullYear() - 1);

            $("#from_date").val(get_from_dt);
            $("#to_date").val(get_to_dt);
        });
    </script>

    <script type="text/javascript" src="{{asset('assets/js/bootstrap-datetimepicker.js')}}" charset="UTF-8"></script>
    <script type="text/javascript">
        const datePickerFormate = {
            // format: "dd-mm-yyyy hh:ii",
            minView: 2,
            endDate: new Date(),
//            format: "yyyy-mm-dd",
            format: "dd-mm-yyyy",
            autoclose: true,
            clear: 'Clear selection',
            pickerPosition: "bottom-left",
        };
        var param1 = new Date();
        var param2 = param1.getFullYear() + '-' + (param1.getMonth() + 1) + '-' + (param1.getDate() + 4) + ' 23:59';
        $('.form_datetime').datetimepicker({
            minView: 2,
            format: "dd-mm-yyyy",
            autoclose: true,
            clear: 'Clear selection',
            pickerPosition: "bottom-left",
            endDate : new Date(),
        });
        var param1 = new Date();
        var param2 = param1.getFullYear() + '-' + (param1.getMonth() + 1) + '-' + (param1.getDate() + 4);
        $('.to_datetime').datetimepicker({
            minView: 2,
            format: "dd-mm-yyyy",
            autoclose: true,
            clear: 'Clear selection',
            pickerPosition: "bottom-left",
            endDate : new Date(),
        });

        $(".remove_from_date").click(function () {
            $('#from_date').val("").datetimepicker("remove").datetimepicker(datePickerFormate);

            $('#to_date').val("").datetimepicker("remove").datetimepicker(datePickerFormate);
        });
        $(".remove_to_date").click(function () {
            $('#from_date').val("").datetimepicker("remove").datetimepicker(datePickerFormate);

            $('#to_date').val("").datetimepicker("remove").datetimepicker(datePickerFormate);
        });

        $(document).on('click', '.provider_payment', function (e) {
            e.preventDefault();
            var order_id = [];
            var provider_pay_settle_type = 0;
            var provider_pay_amount = 0;
            var provider_collect_amount = 0;
            $('.order_check:checkbox:checked').each(function (i) {
                provider_pay_settle_type = Number($(this).attr("provider_pay_settle_type"));
                if (provider_pay_settle_type == 0) {
                    order_id[i] = $(this).attr("order_id");
                    provider_pay_amount += Number(($(this).attr("provider_pay_amount")));
                    provider_collect_amount += Number(($(this).attr("provider_collect_amount")));
                }
            });
            var provider_total_amount = provider_pay_amount - provider_collect_amount;
            if (provider_total_amount > 0) {
                var label = "Pay to Provider!";
            } else {
                var label = "Collect From Provider!";
            }
            var pay_amount = Math.abs(provider_total_amount);
            var payout_txt = 'Total amount : ' + pay_amount.toFixed(2);

            var url = '{{route('post:admin:other_service_order_payment_settle',$slug)}}';
            swal({
                    title: "Provider Payment",
                    text: "if press yes then payment continue!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes",
                    cancelButtonText: "No",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function (isConfirm) {
                    if (isConfirm) {
                        if (order_id != "") {
                            swal({
                                    title: "<h3 class='sw-title'>" + label + "</h3>",
                                    text: payout_txt,
                                    html: true,
                                    showCancelButton: true,
                                    closeOnConfirm: false,
                                    closeOnCancel: true,
                                    animation: "slide-from-top",
                                    confirmButtonText: "Payout!",
                                },
                                function (isConfirm) {
                                    if (isConfirm) {
                                        $.ajax({
                                            type: 'get',
                                            url: url,
                                            data: {order_id: order_id},
                                            success: function (result) {
                                                if (result.success == true) {
                                                    swal({
                                                        title: "Success!",
                                                        text: "Provider Payment successfully",
                                                        type: "success",
                                                    }, function () {
                                                        location.reload();
                                                    });
                                                } else {
                                                    swal("Warning", result.message, "warning");
                                                    console.log(result);
                                                }
                                            }
                                        })
                                    } else {
                                        swal("Cancelled", "Provider Payment Failed", "error");
                                    }
                                });
                        } else {
                            swal("Cancelled", "Please select any one unsettled order", "error");
                        }
                    } else {
                        swal("Cancelled", "Provider Payment Failed", "error");
                    }
                });
        });
    </script>

@endsection
