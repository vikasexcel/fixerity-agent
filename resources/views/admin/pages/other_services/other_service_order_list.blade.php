@extends('admin.layout.other_service')
@section('title')
    @php
        $check_admin = Illuminate\Support\Facades\Auth::guard('admin')->check();
        $check_provider_admin = Illuminate\Support\Facades\Auth::guard("on_demand")->check();

        $statuses = ['approved', 'pending', 'rejected', 'ongoing', 'completed', 'cancelled'];
        $currentUrl = url()->current();
        $matchedStatus = null;

        if (isset($status) && $currentUrl != route('get:provider-admin:other_service_all_order_list', [$status]) && isset($slug)){
            foreach ($statuses as $status) {
    //            $route = $check_admin
    //                ? ($check_provider_admin
    //                    ? route('get:provider-admin:other_service_order_list', [$slug, $status])
    //                    : route('get:admin:other_service_order_list', [$slug, $status]))
    //                : route('get:provider-admin:other_service_all_order_list', [$status]);
                $route = $check_admin
                    ? route('get:admin:other_service_order_list', [$slug, $status]) : route('get:provider-admin:other_service_order_list', [$slug, $status]);

                if ($currentUrl === $route) {
                    $matchedStatus = ucfirst($status);
                    break;
                }
            }

            $matchedStatus = $matchedStatus ?? 'All';
        }

    @endphp
    {{ $matchedStatus }} @if(isset($provider_details)) {{$provider_details->first_name}} @endif Order List
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <!-- Data Table Excel Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/responsive/buttons.dataTables.min.css?v=0.1')}}">
    <style>
        .top {
            display: flex;
        }
        .dataTables_filter {
            margin-left: auto;
        }
        .dt-buttons {
            margin-left: 1em;
        }
    </style>
@endsection
@section('page-content')

    <div class="pcoded-content">
        @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
            <div class="external-horizontal-nav">
                @include('admin.include.other-service-horizontal-navbar')
            </div>
        @elseif(Illuminate\Support\Facades\Auth::guard("on_demand")->check() && isset($slug))
            <div class="external-horizontal-nav">
                @include('admin.include.other-service-provider-navbar')
            </div>
        @endif
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-list bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>
                                {{ $matchedStatus }} @if(isset($provider_details)) {{$provider_details->first_name}} @endif Order List
                            </h5>
                            <span>Order List </span>
                        </div>
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
                                <h5>{{ $matchedStatus }} @if(isset($provider_details)) {{$provider_details->first_name}} @endif Order List</h5>
                                {{--<a href="{{ route('get:admin:store_provider_list') }}"--}}
                                {{--class="btn btn-primary m-b-0 btn-right render_link">Back</a>--}}
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="new-cons" class="table table-striped table-bordered nowrap"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            @if(isset($provider_details))
                                                <th>No</th>
                                                <th>Customer Name</th>
                                                <th>Package List</th>
                                                <th>Total Cost</th>
                                                <th>Order Status</th>
                                                @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                                                    <th>Payment Status</th>
{{--                                                    <th>Refund Status</th>--}}
                                                    <th>Chat</th>
                                                @endif
                                                <th>Details</th>
                                            @else
                                                <th>No</th>
                                                <th>Customer Name</th>
                                                <th>Provider Name</th>
                                                <th>Total Cost</th>
                                                <th>Order Status</th>
                                                @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                                                    <th>Payment Status</th>
{{--                                                    <th>Refund Status</th>--}}
                                                <th>Chat</th>
                                                @endif
                                                <th>Details</th>
                                            @endif
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($provider_details))
                                            @if(isset($order_list))
                                                @foreach($order_list as $key => $order)
                                                    <tr>
                                                        <td>{{ $key+1 }}</td>
                                                        <td>{{ $order->user_name }}</td>
                                                        <td style="font-size: 12px">{{  \Illuminate\Support\Str::limit($order->order_package_list, $limit = 60, $end = '...') }}</td>
                                                        <td class="">
                                                            {{--<i class="fa fa-usd"></i>--}}
                                                            <span class="currency"></span> {{ $order->total_pay }}</td>
                                                        <td class="order-status">
                                                            <?php
                                                            if ($order->status == 1) {
                                                                $bg_status_class = "pending";
                                                            } elseif (in_array($order->status, [2, 3])) {
                                                                $bg_status_class = "approved";
                                                            } elseif (in_array($order->status, [3, 4, 5, 10])) {
                                                                $bg_status_class = "cancelled";
                                                            } elseif (in_array($order->status, [6, 7])) {
                                                                $bg_status_class = "processing";
                                                            } elseif ($order->status == 8) {
                                                                $bg_status_class = "ongoing";
                                                            } elseif ($order->status == 9) {
                                                                $bg_status_class = "completed";
                                                            } else {
                                                                $bg_status_class = "";
                                                            }
                                                            ?>
                                                            <span class="{{$bg_status_class}}">
                                                        {{ str_replace('-',' ',ucwords(trim($order_status[$order->status]))) }}
                                                    </span>
                                                        </td>
                                                        @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                                                            <td class="order-status">
                                                                @if($order->payment_status == 1)
                                                                    <span class="completed">Paid</span>
                                                                @else
                                                                    <span class="pending">Pending</span>
                                                                @endif
                                                            </td>
{{--                                                            <td class="order-status">--}}
{{--                                                                @if($order->status == 4 || $order->status == 5)--}}
{{--                                                                    @if($order->payment_type == 1 )--}}
{{--                                                                        <span class="approved">N/A</span>--}}
{{--                                                                    @else--}}
{{--                                                                        @if($order->user_refund_status == 1)--}}
{{--                                                                            <span class="completed">Completed</span>--}}
{{--                                                                        @else--}}
{{--                                                                            <span class="pending">Pending</span>--}}
{{--                                                                        @endif--}}
{{--                                                                    @endif--}}
{{--                                                                @else--}}
{{--                                                                    <span class="approved">N/A</span>--}}
{{--                                                                @endif--}}
{{--                                                            </td>--}}

                                                        <td>
                                                            @if(!in_array($order['status'], [4,5,9,10]))
                                                                <a href="{{ route('get:admin:get_order_wise_chat', [$slug, $order['id']]) }}"
                                                                   class="btn btn-primary btn-sm"
                                                                   style="width:15px;padding:8px 21px 8px 10px">
                                                                    <i class="fas fa-comment-dots"></i>
                                                                </a>
                                                            @else
                                                                <span class='order-status'>
                                                                <span class='cancelled'>N/A</span>
                                                            </span>
                                                            @endif
                                                        </td>
                                                        @endif
                                                        <td class="order-status">
                                                            <a {{--href="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('get:admin:other_service_order_details',[$slug,$order->id]) : route('get:admin:other_service_order_details',[$order->id]) }}"--}}
                                                               href="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('get:admin:other_service_order_details',[$slug,$order->id]) : ( isset($slug) ? (route('get:provider-admin:other_service_order_details',[$order->id])) : (route('get:provider-admin:other_service_order_details',[$order->id]))) }}"
                                                               class="render_link">view details</a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @else
                                            @if(isset($order_list))
                                                @foreach($order_list as $key => $order)
                                                    <tr>
                                                        <td>{{ $key+1 }}</td>
                                                        <td>{{ $order->user_name }}</td>
                                                        <td>{{ $order->provider_name }}</td>
                                                        <td class=""><span class="currency"></span> {{ $order->total_pay }}</td>
                                                        <td class="order-status">
                                                            <?php
                                                            if ($order->status == 1) {
                                                                $bg_status_class = "pending";
                                                            } elseif (in_array($order->status, [2, 3])) {
                                                                $bg_status_class = "approved";
                                                            } elseif (in_array($order->status, [4, 5, 10])) {
                                                                $bg_status_class = "cancelled";
                                                            } elseif (in_array($order->status, [6, 7])) {
                                                                $bg_status_class = "processing";
                                                            } elseif ($order->status == 8) {
                                                                $bg_status_class = "ongoing";
                                                            } elseif ($order->status == 9) {
                                                                $bg_status_class = "completed";
                                                            } else {
                                                                $bg_status_class = "";
                                                            }
                                                            ?>
                                                            <span class="{{$bg_status_class}}">
                                                        {{ str_replace('-',' ',ucwords(trim($order_status[$order->status]))) }}
                                                    </span>
                                                        </td>
                                                        @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
                                                            <td class="order-status">
                                                                @if($order->payment_status == 1)
                                                                    <span class="completed">Paid</span>
                                                                @else
                                                                    <span class="pending">Pending</span>
                                                                @endif
                                                            </td>
{{--                                                            <td class="order-status">--}}
{{--                                                                @if($order->status == 4 || $order->status == 5)--}}
{{--                                                                    @if($order->payment_type == 1 )--}}
{{--                                                                        <span class="approved">N/A</span>--}}
{{--                                                                    @else--}}
{{--                                                                        @if($order->user_refund_status == 1)--}}
{{--                                                                            <span class="completed">Completed</span>--}}
{{--                                                                        @else--}}
{{--                                                                            <span class="pending">Pending</span>--}}
{{--                                                                        @endif--}}
{{--                                                                    @endif--}}
{{--                                                                @else--}}
{{--                                                                    <span class="approved">N/A</span>--}}
{{--                                                                @endif--}}
{{--                                                            </td>--}}
                                                        <td>
                                                            @if(!in_array($order['status'], [4,5,9,10]))
                                                                <a href="{{ route('get:admin:get_order_wise_chat', [$slug, $order['id']]) }}"
                                                                   class="btn btn-primary btn-sm"
                                                                   style="width:15px;padding:8px 21px 8px 10px">
                                                                    <i class="fas fa-comment-dots"></i>
                                                                </a>
                                                            @else
                                                                <span class='order-status'>
                                                                <span class='cancelled'>N/A</span>
                                                            </span>
                                                            @endif
                                                        </td>
                                                        @endif
                                                        <td class="order-status">
                                                            <a {{--href="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('get:admin:other_service_order_details',[$slug,$order->id]) : route('get:admin:other_service_order_details',[$order->id]) }}"--}}
                                                               href="{{ (Illuminate\Support\Facades\Auth::guard("admin")->check()) ? route('get:admin:other_service_order_details',[$slug,$order->id]) : ( isset($slug) ? (route('get:provider-admin:other_service_order_details',[$order->id])) : (route('get:provider-admin:other_service_order_details',[$order->id]))) }}"
                                                               class="render_link">view details</a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endif
                                        </tbody>
                                    </table>
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
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}" type="text/javascript"></script>
    <!-- CDN for the Excel file -->
    <script src="{{asset('assets/js/responsive/dataTables.buttons.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/jszip.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/buttons.html5.min.js')}}"></script>
    <script src="{{asset('assets/js/responsive/buttons.print.min.js')}}"></script>
    <script type="text/javascript">
        var newcs = $('#new-cons').DataTable({
            dom: '<"top"lBf>rt<"bottom"pi><"clear">',
            buttons: [{
                extend: 'excel',
                text: 'Download Excel'
            }],
            "columnDefs": [
                { "orderable": false, "targets": [6] } // Disable sorting on Icon (index 1) and Actions (index 5)
            ]
        });
    </script>
@endsection
