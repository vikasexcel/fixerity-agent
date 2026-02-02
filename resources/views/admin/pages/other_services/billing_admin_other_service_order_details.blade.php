@extends('admin.layout.account_billing_admin')
@section('title')
    Order Details
@endsection
@section('page-css')
@endsection
@section('page-content')

    <div class="pcoded-content">

        @if(Illuminate\Support\Facades\Auth::guard("admin")->check())
            @if(Illuminate\Support\Facades\Auth::guard("admin")->user()->roles == 1)
                <div class="external-horizontal-nav">
                    @include('admin.include.other-service-horizontal-navbar')
                </div>
            @endif
        @elseif(Illuminate\Support\Facades\Auth::guard("on_demand")->check() && isset($slug))
            <div class="external-horizontal-nav">
                @include('admin.include.other-service-provider-navbar')
            </div>
        @endif

        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="page-header-title">
                                <i class="feather icon-list bg-c-blue"></i>
                                <div class="d-inline">
                                    <h5>Order Details</h5>
                                    <span>Full Order Details</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="card">
                            <div class="card-header">
                                <h5>Order Details</h5>
                            </div>
                            <div class="card-block">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="table-responsive ride-detail-table">
                                            @if(isset($orders_details))
                                                <table class="table">
                                                    <tr>
                                                        <th>Order ID.</th>
                                                        <td>#{{ $orders_details->order_no }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Provider Name</th>
                                                        <td>{{ ($orders_details->provider_name == Null)? "----" : ucwords($orders_details->provider_name) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Customer Name</th>
                                                        <td>{{ ($orders_details->user_name == Null)? "----" : ucwords($orders_details->user_name) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Customer Address</th>
                                                        <td>{{ $orders_details->delivery_address }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Phone No</th>
                                                        <td>{{ $orders_details->country_code}} {{ $orders_details->contact_number }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Order Date</th>
                                                        {{--<td>{{ $orders_details->service_date_time }}</td>--}}
                                                        <td>{{ (Illuminate\Support\Facades\Auth::guard("on_demand")->check())?\App\Classes\NotificationClass::convertTimezone($orders_details->created_at,"",$orders_details->booking_time_zone,"d")  : $orders_details->created_at }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Booked Order Date Time</th>
                                                        <td>{{ $orders_details->service_date.' '.date("h:i A",strtotime($orders_details->book_start_time))." - ".date("h:i A",strtotime($orders_details->book_end_time)) }}
                                                            {{isset($orders_details->booking_time_zone)?App\Models\User::timezonedetails($orders_details->booking_time_zone):""}} </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Additional Remarks</th>
                                                        <td>{{ isset($orders_details->remark) ? $orders_details->remark : '----' }}</td>
                                                    </tr>
                                                    @if(isset($orders_details) && in_array($orders_details->status, [4,5,10]))
                                                        <tr>
                                                            <th>Cancel By</th>
                                                            <td>{{ isset($orders_details) ? ucwords($orders_details->cancel_by) : '-----' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Cancel Reason</th>
                                                            <td>{{ isset($orders_details) ? $orders_details->cancel_reason : '-----' }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <th>Total Amount</th>
                                                        <td class=""><span class="currency"></span> {{ ($orders_details->total_pay == 0)? "0" : $orders_details->total_pay }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Payment Type</th>
                                                        <td>{{ isset($orders_details) ? ($orders_details->payment_type == 1 ? "Cash" : ($orders_details->payment_type == 2 ? "Card" : ($orders_details->payment_type == 3 ? "Wallet" : ''))) : '' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Payment Status</th>
                                                        <td>{{ ($orders_details->payment_status  == 1)? "Completed" : "Pending"}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Order Status</th>
                                                        <td><span id="order-status">{{ $orders_status }}</span></td>
                                                    </tr>
                                                </table>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="table-responsive ride-detail-table">
                                            <div class="ride-detail-table-header">
                                                <h5>Package Details</h5>
                                            </div>
                                            <table class="table">
                                                <tr>
                                                    <th>Category Name</th>
                                                    <th>Package Name</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Total Cost</th>
                                                </tr>
                                                @if(isset($orders_details))
                                                    @if(isset($package_list))
                                                        @foreach($package_list as $key => $package)
                                                            <tr>
                                                                <td>
                                                                    {{ $package->sub_category_name }}
                                                                </td>
                                                                <td>
                                                                    {{ $package->package_name }}
                                                                </td>
                                                                <td class="">
                                                                    <span class="currency"></span> {{ $package->price_for_one }}
                                                                </td>
                                                                <td class="">
                                                                     {{ $package->num_of_items }}
                                                                </td>
                                                                <td class="">
                                                                    <span class="currency"></span> {{ round($package->price_for_one * $package->num_of_items,2) }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                    <tr>
                                                        <td colspan="3"></td>
                                                        <td>
                                                            Item Total
                                                        </td>
                                                        <td class="">
                                                            <span class="currency"></span> {{ $orders_details->total_item_cost }}
                                                        </td>
                                                    </tr>
                                                        @if($orders_details->promo_code_discount > 0)
                                                            <tr>
                                                                <td colspan="3"></td>
                                                                <td>
                                                                    {{ $orders_details->promo_code_name }}
                                                                </td>
                                                                <td>
                                                                    <span class="currency"> {{ $orders_details->promo_code_discount != null ?  $orders_details->promo_code_discount : "0.00" }}</span>
                                                                </td>
                                                            </tr>
                                                       @endif
                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td>
                                                                Refferal Discount
                                                            </td>
                                                            <td>
                                                                <span class="currency"> {{ $orders_details->refer_discount != null ?  $orders_details->refer_discount : "0.00" }}</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td>
                                                                Extra Amount
                                                            </td>
                                                            <td>
                                                                <span class="currency"> {{ $orders_details->extra_amount != null ?  round($orders_details->extra_amount,2) : "0" }}</span>
                                                            </td>
                                                        </tr>
                                                    <tr>
                                                        <td colspan="3"></td>
                                                        <td>
                                                            Tax
                                                        </td>
                                                        <td class="">
                                                            <span class="currency"></span> {{ $orders_details->tax }}
                                                        </td>
                                                    </tr>
                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td>
                                                                Tip
                                                            </td>
                                                            <td class="currency">
                                                                {{ ($orders_details->tip > 0)?$orders_details->tip:0 }}
                                                            </td>
                                                        </tr>
                                                    <tr>
                                                        <td colspan="3"></td>
                                                        <td>
                                                            <h3>Total Pay</h3>
                                                        </td>
                                                        <td>
                                                            <h3 class=""> <span class="currency"></span> {{ $orders_details->total_pay }}</h3>
                                                        </td>
                                                    </tr>
                                                @endif
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
@endsection

