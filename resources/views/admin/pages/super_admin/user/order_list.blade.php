@extends('admin.layout.super_admin')
@section('title')
    Customer Order List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <style>
        .customer-details td, .customer-details th {
            padding: 5px;
        }

        .customer-order {
            padding: 0;
            width: 100%;
            float: left;
        }

        .transport-1 {
            color: #4dc271;
        }

        .transport-2 {
            color: #aa5de2;
        }

        .store {
            color: #4389e9;
        }

        .ondemand-1 {
            color: #f3a934;
        }

        .ondemand-2 {
            color: #ee4c4d;
        }

        .transport-1 img, .transport-2 img, .store img, .ondemand-1 img, .ondemand-2 img {
            width: 20px;
            height: 20px;
        }

        .transport-1 b, .transport-2 b, .store b, .ondemand-1 b, .ondemand-2 b {
            font-size: 14px;
        }

        /*.customer-order:last-child{*/
        /*clear: left;*/
        /*}*/
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title ">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5> Customer Order List</h5>
                                    <span>All Customer Order List</span>
                                </div>
                            </div>
                        </div>
                        {{--<div class="col-lg-4">--}}
                        {{--<a href="{{ route('get:admin:user_list') }}"--}}
                        {{--class="btn btn-primary m-b-0 btn-right render_link">Back</a>--}}
                        {{--</div>--}}
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="pcoded-inner-content">
            <div class="main-body col-md-12 customer-order">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>Customer Profile Details</h5>
                            </div>
                            <div class="card-block">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="customer-details">
                                            <tr>
                                                <th>Name</th>
                                                <td>{{ isset($user_details)? ucwords(strtolower($user_details->first_name." ".$user_details->last_name)) : "----" }}</td>
                                            </tr>
                                            <tr>
                                                <th>Last Active</th>
{{--                                                {{ isset($user_details)? date("d F, Y h:i A", strtotime($user_details->updated_at)) : "----" }}--}}
                                                <td>{{ isset($user_details)? date("d F, Y h:i A", strtotime($user_details->updated_at)) : "----" }}</td>
{{--                                                <td>1 Apr, 2019 10:15 AM</td>--}}
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="customer-details">
                                            <tr>
                                                <th>Email</th>
                                                <td>{{ isset($user_details)? $user_details->email : "----" }}</td>
                                            </tr>
                                            <tr>
                                                <th>Contact No</th>
                                                <td>{{ isset($user_details)? $user_details->contact_number : "----" }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Page body end -->
            </div>
        </div>
        <div class="pcoded-inner-content" style="clear: left">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="card">
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <ul class="nav nav-tabs md-tabs " role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active show" data-toggle="tab" href="#transport"
                                               role="tab"
                                               aria-selected="true"><i class="icofont icofont-home"></i>
                                                <h5>Transport Orders</h5>
                                            </a>
                                            <div class="slide"></div>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#store"
                                               role="tab" aria-selected="false"><i class="icofont icofont-ui-user "></i>
                                                <h5>Store Orders</h5>
                                            </a>
                                            <div class="slide"></div>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#on-demand" role="tab"
                                               aria-selected="false"><i class="icofont icofont-ui-message"></i>
                                                <h5>On-Demand Orders</h5>
                                            </a>
                                            <div class="slide"></div>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#coupon-deals" role="tab"
                                               aria-selected="false"><i class="icofont icofont-ui-message"></i>
                                                <h5>Coupon list</h5>
                                            </a>
                                            <div class="slide"></div>
                                        </li>
                                    </ul>
                                    <div class="tab-content card-block">
                                        <div class="tab-pane active show" id="transport" role="tabpanel">
                                            <table id="new-cons-1" class="table table-striped table-bordered nowrap"
                                                   style="width:100%">
                                                <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Order No.</th>
                                                    <th>Service Name</th>
                                                    <th>Total Amount</th>
                                                    <th>Payment Type</th>
                                                    <th>Order Status</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @if(isset($transport_order_list))
                                                    @foreach($transport_order_list as $key => $order)
                                                        <tr>
                                                            <td>{{ $key+1 }}</td>
                                                            <td>{{ $order->order_no }}</td>
                                                            <td class="{{$order->service_cat_type == 1 ? "transport-1" : ($order->service_cat_type == 5 ? "transport-2" : "")}}">
                                                                <img src="{{ asset('/assets/images/service-category/'.$order->service_cat_icon)  }}">
                                                                <b>{{ $order->service_cat_name }}</b>
                                                            </td>
                                                            <td class=""> <span class="currency"></span> {{ $order->total_pay }}</td>
                                                            <td>
                                                                {{ $order->payment_type }}
                                                                @if($order->payment_type == 1)
                                                                    {{ ucwords("cash") }}
                                                                @elseif($order->payment_type == 2)
                                                                    {{ ucwords("card") }}
                                                                @elseif($order->payment_type == 3)
                                                                    {{ ucwords("wallet") }}
                                                                @endif
                                                            </td>
                                                            @if(isset($transport_ride_status))
                                                                <?php
                                                                if ($order->status == 0) {
                                                                    $bg_status_class = "pending";
                                                                } elseif (in_array($order->status, [1, 2])) {
                                                                    $bg_status_class = "approved";
                                                                } elseif (in_array($order->status, [3, 4, 10])) {
                                                                    $bg_status_class = "cancelled";
                                                                } elseif (in_array($order->status, [5])) {
                                                                    $bg_status_class = "processing";
                                                                } elseif (in_array($order->status, [6, 7, 8])) {
                                                                    $bg_status_class = "ongoing";
                                                                } elseif ($order->status == 9) {
                                                                    $bg_status_class = "completed";
                                                                } else {
                                                                    $bg_status_class = "";
                                                                }
                                                                ?>
                                                            @else
                                                                <?php
                                                                $bg_status_class = "";
                                                                ?>
                                                            @endif
                                                            <td class="icon-url-link">
                                                                {{--<a href="{{ route('get:admin:country_city_list',"china") }}">--}}
                                                                <div class="data-table-main icon-list-demo order-status">
                                                                    <span class="{{ $bg_status_class }}">{{ isset($transport_ride_status) ? str_replace('-',' ',ucwords(trim($transport_ride_status[$order->status]))) : '' }}</span>
                                                                </div>
                                                                {{--</a>--}}
                                                            </td>
                                                        </tr>

                                                    @endforeach
                                                @endif
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="tab-pane" id="store" role="tabpanel">
                                            <table id="new-cons-2" class="table table-striped table-bordered nowrap"
                                                   style="width:100%">
                                                <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Order No.</th>
                                                    <th>Service Name</th>
                                                    <th>Total Amount</th>
                                                    <th>Payment Type</th>
                                                    <th>Order Status</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @if(isset($store_order_list))
                                                    @foreach($store_order_list as $key => $order)
                                                        <tr>
                                                            <td>{{ $key+1 }}</td>
                                                            <td>{{ $order->order_no }}</td>
                                                            <td class="store">
                                                                <img src="{{ asset('/assets/images/service-category/'.$order->service_cat_icon)  }}">
                                                                <b>{{ $order->service_cat_name }}</b>
                                                            </td>
                                                            <td class="">
                                                                <span class="currency"></span>{{ $order->total_pay }}
                                                            </td>
                                                            <td>
                                                                {{ ucwords(strtolower($order->payment_type == 1 ? "Cash" : ($order->payment_type == 2 ? "Card" : "wallet"))) }}
                                                            </td>
                                                            @if(isset($store_order_status))
                                                                <?php
                                                                if ($order->status == 1) {
                                                                    $bg_status_class = "pending";
                                                                } elseif ($order->status == 2) {
                                                                    $bg_status_class = "approved";
                                                                } elseif (in_array($order->status, [3, 4, 10])) {
                                                                    $bg_status_class = "cancelled";
                                                                } elseif (in_array($order->status, [5, 6, 7])) {
                                                                    $bg_status_class = "processing";
                                                                } elseif ($order->status == 8) {
                                                                    $bg_status_class = "ongoing";
                                                                } elseif ($order->status == 9) {
                                                                    $bg_status_class = "completed";
                                                                } else {
                                                                    $bg_status_class = "";
                                                                }
                                                                ?>
                                                            @else
                                                                <?php
                                                                $bg_status_class = "";
                                                                ?>
                                                            @endif
                                                            <td class="icon-url-link">
                                                                {{--<a href="{{ route('get:admin:country_city_list',"china") }}">--}}
                                                                <div class="data-table-main icon-list-demo order-status">
                                                                    <span class="{{ $bg_status_class }}">{{ isset($store_order_status) ? str_replace('-',' ',ucwords(trim($store_order_status[$order->status]))) : '' }}</span>
                                                                </div>
                                                                {{--</a>--}}
                                                            </td>
                                                        </tr>

                                                    @endforeach
                                                @endif
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="tab-pane" id="on-demand" role="tabpanel">
                                            <table id="new-cons-2" class="table table-striped table-bordered nowrap"
                                                   style="width:100%">
                                                <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Order No.</th>
                                                    <th>Service Name</th>
                                                    <th>Total Amount</th>
                                                    <th>Payment Type</th>
                                                    <th>Order Status</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @if(isset($on_demand_order_list))
                                                    @foreach($on_demand_order_list as $key => $order)
                                                        <tr>
                                                            <td>{{ $key+1 }}</td>
                                                            <td>{{ $order->order_no }}</td>
                                                            <td class="store">
                                                                <img src="{{ asset('/assets/images/service-category/'.$order->service_cat_icon)  }}">
                                                                <b>{{ $order->service_cat_name }}</b>
                                                            </td>
                                                            <td class="">
                                                                <span class="currency"></span> {{ $order->total_pay }}
                                                            </td>
                                                            <td>
                                                                {{ ucwords(strtolower($order->payment_type == 1 ? "Cash" : ($order->payment_type == 2 ? "Card" : "wallet"))) }}
                                                            </td>
                                                            @if(isset($store_order_status))
                                                                <?php
                                                                if ($order->status == 1) {
                                                                    $bg_status_class = "pending";
                                                                } elseif ($order->status == 2) {
                                                                    $bg_status_class = "approved";
                                                                } elseif (in_array($order->status, [3, 4, 10])) {
                                                                    $bg_status_class = "cancelled";
                                                                } elseif (in_array($order->status, [5, 6, 7])) {
                                                                    $bg_status_class = "processing";
                                                                } elseif ($order->status == 8) {
                                                                    $bg_status_class = "ongoing";
                                                                } elseif ($order->status == 9) {
                                                                    $bg_status_class = "completed";
                                                                } else {
                                                                    $bg_status_class = "";
                                                                }
                                                                ?>
                                                            @else
                                                                <?php
                                                                $bg_status_class = "";
                                                                ?>
                                                            @endif
                                                            <td class="icon-url-link">
                                                                {{--<a href="{{ route('get:admin:country_city_list',"china") }}">--}}
                                                                <div class="data-table-main icon-list-demo order-status">
                                                                    <span class="{{ $bg_status_class }}">{{ isset($store_order_status) ? str_replace('-',' ',ucwords(trim($store_order_status[$order->status]))) : '' }}</span>
                                                                </div>
                                                                {{--</a>--}}
                                                            </td>
                                                        </tr>

                                                    @endforeach
                                                @endif
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="tab-pane" id="coupon-deals" role="tabpanel">
                                            <table id="new-cons-2" class="table table-striped table-bordered nowrap"
                                                   style="width:100%">
                                                <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Customer Name</th>
                                                    <th>Coupon Name</th>
                                                    <th>Brand Name</th>
                                                    <th>Amount</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @if(isset($purchas_coupon_history))
                                                    @foreach($purchas_coupon_history as $key => $purchas_coupon)
                                                        <tr>
                                                            <td>{{ $key+1 }}</td>
                                                            <td>{{ $purchas_coupon->first_name." ".$purchas_coupon->last_name }}</td>
                                                            {{--<td class="store">--}}
                                                            {{--<img src="{{ asset('/assets/images/service-category/'.$purchas_coupon->service_cat_icon)  }}">--}}
                                                            {{--<b>{{ $purchas_coupon->service_cat_name }}</b>--}}
                                                            {{--</td>--}}
                                                            <td>
                                                                {{ $purchas_coupon->coupon_name }}
                                                            </td>
                                                            <td>
                                                                {{ $purchas_coupon->coupon_brand_name }}
                                                            </td>
                                                            <td class="">
                                                                <span class="currency"></span>  {{ $purchas_coupon->coupon_amount }}
                                                            </td>
                                                        </tr>

                                                    @endforeach
                                                @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Page body end -->
            </div>
        </div>
    </div>

@endsection
@section('page-js')
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>
    <script>
        $('#new-cons-1').DataTable({
            responsive: true
        });
        $('#new-cons-2').DataTable({
            responsive: true
        });
        $('#new-cons-3').DataTable({
            responsive: true
        });
    </script>
@endsection

