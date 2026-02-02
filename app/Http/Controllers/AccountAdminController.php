<?php

namespace App\Http\Controllers;

use App\Classes\AdminClass;
use App\Models\Admin;
use App\Models\ServiceCategory;
use App\Models\UsedPromocodeDetails;
use App\Models\User;
use App\Models\UserPackageBooking;
use App\Models\UserPackageBookingQuantity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AccountAdminController extends Controller
{
    private $adminClass;
    private $on_demand_service_id_array;
    private $package_status;
    private $is_restricted = 0;
    private $order_status;

    public function __construct(AdminClass $adminClass)
    {
        $this->middleware('auth');
        $this->adminClass = $adminClass;
        $service_cat = ServiceCategory::query()->whereIN('category_type', [3, 4])->where('status', 1)->get()->pluck('id')->toArray();
        $this->on_demand_service_id_array = $service_cat;
        $this->package_status = ['', 'pending', 'approved', 'approved', 'rejected', 'cancelled', 'ongoing', 'arrived', 'processing', 'completed', 'failed'];
        $this->order_status = [2, 3, 6, 7, 8];
        $this->middleware( function ($request, $next) {
            $is_restrict_admin = $request->get('is_restrict_admin');
            $this->is_restricted = $is_restrict_admin;
            return $next($request);
        });
    }

    //dashboard
    public function getAdminDashboard(Request $request)
    {
        if (Auth::guard("admin")->user()->roles == 3) {
            $date = date('Y-m-d');
            $total_user = User::query()->whereDate('created_at', '=', $date)->whereNull('users.deleted_at')->count();

            //total completed order
            $total_ondemand_sales = UserPackageBooking::query()->where('status', 9)->whereDate('service_date_time', '=', $date)->where('payment_status', '=',1)->count();
            $total_completed_order =  $total_ondemand_sales;

            //total cancelled order
            $total_cancelled_order = UserPackageBooking::query()->whereIn('status', [4,5])->whereDate('service_date_time', '=', $date)->count();

            //total order
            $total_order = UserPackageBooking::query()->whereNotIn('status', [10])->whereDate('service_date_time', '=', $date)->count();

            //total revenue

            $ondemand_rev = UserPackageBooking::query()
                ->whereDate('service_date_time', '=', $date)
                ->where('status', 9)
                ->where('payment_status', '=',1)
                ->sum('admin_commission');
            $n_ondemand = (0 + str_replace(",", "", number_format($ondemand_rev, 2)));

            $n = $n_ondemand;
            if (!is_numeric($n)) {
                false;
            }
            if ($n > 1000000000000) {
                $total_revenue = round(($n / 1000000000000), 2) . ' T';
            } elseif ($n > 1000000000) {

                $total_revenue = round(($n / 1000000000), 2) . ' B';
            } elseif ($n > 1000000) {
                $total_revenue = round(($n / 1000000), 2) . ' M';
            } elseif ($n > 1000) {
                $total_revenue = round(($n / 1000), 2) . ' K';
            } else {
                $total_revenue = $n;
            }

            $total_order_count = UserPackageBooking::query()->whereDate('service_date_time', '=', $date)->count();

            $service_category = ServiceCategory::query()->where('status', 1)->get();
            foreach ($service_category as $key => $category) {
                if (in_array($category->category_type, [3, 4])) {
                    $order_count[$category->id] = UserPackageBooking::query()->where('service_cat_id', $category->id)->whereDate('service_date_time', '=', $date)->count();
                } else {
                    $order_count[$category->id] = 0;
                }
            }
            $view = view('admin.pages.super_admin.dashboard', compact('total_user',
                'total_completed_order','total_ondemand_sales',
                'total_revenue','total_cancelled_order','total_order',
                'service_category','total_order_count', 'order_count'));
            if ($request->ajax()) {
                $view = $view->renderSections();
                return $this->adminClass->renderingResponce($view);
            }
            return $view;
        } elseif (Auth::guard("admin")->user()->roles == 1) {
            return redirect()->route('get:admin:dashboard');
        } elseif (Auth::guard("admin")->user()->roles == 2) {
            return redirect()->route('get:dispatcher:manual_ride_booking');
        } else {
            Auth::guard('admin')->logout();
            return redirect()->route('get:admin:login');
        }
    }

    //othre service order list
    public function getOtherServiceOrderList(Request $request)
    {
        if (Auth::guard("admin")->user()->roles != 3) {
//            Session::flash('error', 'Something want to wrong!');
            return redirect()->back();
        }
//        $service_category = ServiceCategory::where('slug', $slug)->first();
//        if ($service_category == Null) {
//            Session::flash('error', 'Service category not found!');
//            return redirect()->back();
//        }
//        if ($status == "all") {
//            $status = [];
//        } elseif ($status == "pending") {
//            $status = [1];
//        } elseif ($status == "approved") {
//            $status = [2, 3];
//        } elseif ($status == "rejected") {
//            $status = [4];
//        } elseif ($status == "ongoing") {
//            $status = [6, 7, 8];
//        } elseif ($status == "completed") {
//            $status = [9];
//        } elseif ($status == "cancelled") {
//            $status = [5, 10];
//        } else {
//            $status = [];
//        }
//        if (count($status) > 0) {
//            $order_list = UserPackageBooking::select('id', 'user_name', 'provider_name', 'total_pay', 'status')->where('service_cat_id', $service_category->id)->whereIN('status', $status)->get();
//        } else {
//            $order_list = UserPackageBooking::select('id', 'user_name', 'provider_name', 'total_pay', 'status')->where('service_cat_id', $service_category->id)->get();
//        }
        $order_list = UserPackageBooking::select('user_service_package_booking.id',
            'user_service_package_booking.user_name', 'user_service_package_booking.provider_name',
            'user_service_package_booking.total_pay', 'user_service_package_booking.status',
            'service_category.name as service_category_name')
//            ->where('service_cat_id', $service_category->id)
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->whereIN('user_service_package_booking.service_cat_id', $this->on_demand_service_id_array)
            ->orderBy('user_service_package_booking.service_date_time', 'desc')
            ->get();
        $order_status = $this->package_status;
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.billing_admin_other_service_order_list', compact( 'order_list', 'order_status'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.billing_admin_other_service_order_list', compact( 'order_list', 'order_status'));
    }

    public function getOtherServiceOrderDetails(Request $request, $order_id)
    {
//        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
//        if ($service_category == Null) {
//            return redirect()->back();
//        }
        if (Auth::guard("admin")->user()->roles != 3) {
            Session::flash('error', 'Something want to wrong!');
            return redirect()->back();
        }

        $orders_details = UserPackageBooking::query()->select('user_service_package_booking.id', 'user_service_package_booking.order_no',
            'user_service_package_booking.user_name', 'user_service_package_booking.provider_name','user_service_package_booking.booking_time_zone',
            'user_service_package_booking.service_date_time',
            'user_service_package_booking.service_date',
            'user_service_package_booking.service_time',
            'user_service_package_booking.total_item_cost',
            'user_service_package_booking.delivery_address',
            'user_service_package_booking.extra_amount',
            'user_service_package_booking.promo_code',
            'user_service_package_booking.tax',
            'user_service_package_booking.tip',
            'user_service_package_booking.created_at',
            'user_service_package_booking.remark',
            'user_service_package_booking.refer_discount',
            'user_service_package_booking.service_date',
            'user_service_package_booking.service_time',
            'user_service_package_booking.book_start_time',
            'user_service_package_booking.book_end_time',
            'used_promocode_details.discount_amount as promo_code_discount',
            'used_promocode_details.promocode_name as promo_code_name',
            'user_service_package_booking.total_pay', 'user_service_package_booking.status'
            , 'users.country_code'
            , 'users.contact_number'
            , 'user_service_package_booking.cancel_by'
            , 'user_service_package_booking.cancel_reason',
            'user_service_package_booking.payment_status',
            'user_service_package_booking.payment_type',
        )
            ->join('users', 'users.id', '=', 'user_service_package_booking.user_id')
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', 'user_service_package_booking.promo_code')
            ->where('user_service_package_booking.id', $order_id)
//            ->whereNull('users.deleted_at')
//            ->where('service_category.slug', $slug)
            ->first();
        $orders_status = "----";
        $orders_status_array = $this->order_status;
        if ($orders_details != Null) {
            if ($orders_details->promo_code != 0) {
                try {
                    $get_discount = UsedPromocodeDetails::query()->where('id', $orders_details->promo_code)->first();
                    $promo_code_discount = $get_discount->discount_amount;
                    $promocode_name = $get_discount->promocode_name;
                } catch (\Exception $e) {
                    $promo_code_discount = round($orders_details->total_item_cost - $orders_details->discount_cost + $orders_details->tax_cost + $orders_details->delivery_cost + $orders_details->packaging_cost - $orders_details->total_pay, 2);
                    $promocode_name = 'promo';
                }
            } else {
                $promo_code_discount = 0;
                $promocode_name = 'promo';
            }


            $orders_details->promo_code_discount=$promo_code_discount;
            $orders_details->promocode_name=$promocode_name;
            $package_list = UserPackageBookingQuantity::query()->select('num_of_items', 'package_name', 'sub_category_name', 'price_for_one')
                ->where('order_id', $orders_details->id)
                ->get();
            $total_package_cost = 0;
            foreach ($package_list as $key => $package) {
                $total_package_item_cost = round($package->price_for_one * $package->num_of_items, 2);
                $total_package_cost = $total_package_cost + $total_package_item_cost;
            }

            if ($orders_details->status == 1) {
                $orders_status = "Pending";
            } elseif ($orders_details->status == 2 || $orders_details->status == 3) {
                $orders_status = "Confirmed";
            } elseif ($orders_details->status == 4) {
                $orders_status = "Rejected";
            } elseif ($orders_details->status == 5) {
                $orders_status = "Cancelled";
            } elseif ($orders_details->status == 6) {
                $orders_status = "Ongoing";
            } elseif ($orders_details->status == 7) {
                $orders_status = "Arrived";
            } elseif ($orders_details->status == 8) {
                $orders_status = "Processing";
            } elseif ($orders_details->status == 9) {
                $orders_status = "Completed";
            } elseif ($orders_details->status == 10) {
                $orders_status = "Failed";
            }
            $view = view('admin.pages.other_services.billing_admin_other_service_order_details', compact('orders_status', 'orders_status_array', 'orders_details', 'package_list'));
        } else {
            $view = view('admin.pages.other_services.billing_admin_other_service_order_details', compact('orders_status', 'orders_status_array'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getAdminProfile(Request $request)
    {
        if (Auth::guard("admin")->user()->roles != 3) {
            Session::flash('error', 'Something want to wrong!');
            return redirect()->back();
        }
        $admin_details = Admin::where('id', Auth::guard('admin')->user()->id)->first();
        if ($admin_details != Null) {
            $view = view('admin.pages.super_admin.profile.form', compact('admin_details'));
        } else {
            $view = view('admin.pages.super_admin.profile.form');
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }

        return $view;
    }

    public function postAdminProfile(Request $request)
    {
        if($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        if (Auth::guard("admin")->user()->roles != 3) {
            Session::flash('error', 'Something want to wrong!');
            return redirect()->back();
        }
        $admin = Admin::where('id', Auth::guard('admin')->user()->id)->first();
        if ($admin != Null) {
            $admin->name = $request->get("name");
            $admin->save();
            Session::flash('success', 'Billing Admin profile update successfully!');
            return redirect()->route('get:account:profile');
        }
        Session::flash('error', 'Admin Details Not Found!');
        return redirect()->back();
    }

    public function getOtherServiceEarningReport(Request $request)
    {
        if (Auth::guard("admin")->user()->roles != 3) {
            Session::flash('error', 'Something want to wrong!');
            return redirect()->back();
        }

        $user_list = UserPackageBooking::query()->select('users.id',
            'users.first_name', 'users.last_name', 'users.contact_number')
            ->join('users', 'users.id', '=', 'user_service_package_booking.user_id')
            ->where('user_service_package_booking.status', '=', 9)
            ->whereIn('user_service_package_booking.service_cat_id', $this->on_demand_service_id_array)
            ->whereNull('users.deleted_at')
            ->groupBy('user_service_package_booking.user_id')
            ->get();
        $provider_list = UserPackageBooking::query()->select('providers.id',
            DB::raw("providers.first_name as name"), 'providers.contact_number', 'user_service_package_booking.provider_id')
            ->join('providers', 'providers.id', '=', 'user_service_package_booking.provider_id')
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->where('user_service_package_booking.status', '=', 9)
            ->whereNull('providers.deleted_at')
            ->whereIn('user_service_package_booking.service_cat_id', $this->on_demand_service_id_array)
            ->groupBy('user_service_package_booking.provider_id')
            ->get();
        $service_category_list = ServiceCategory::query()
            ->select('service_category.id as service_cat_id', 'service_category.name as service_cat_name')
            ->whereIn('service_category.id', $this->on_demand_service_id_array)
            ->where('service_category.status', 1)
            ->get();

        $package_order_list = UserPackageBooking::query()->select('user_service_package_booking.id',
            'user_service_package_booking.order_no',
            'user_service_package_booking.order_type',
            'user_service_package_booking.service_date_time',
            'user_service_package_booking.user_name',
            'user_service_package_booking.provider_name',
            'user_service_package_booking.total_item_cost',
            'user_service_package_booking.service_time',
            'user_service_package_booking.extra_amount',
            'user_service_package_booking.tax',
            'user_service_package_booking.tip',
            'user_service_package_booking.total_pay',
            'user_service_package_booking.provider_amount',
            'user_service_package_booking.refer_discount',
            'user_service_package_booking.created_at',
            'user_service_package_booking.user_id',
            'user_service_package_booking.admin_commission',
            'user_service_package_booking.provider_pay_settle_status',
            'user_service_package_booking.provider_id',
            'user_service_package_booking.payment_type',
            'service_category.name as service_category_name',
            'used_promocode_details.discount_amount as promocode',
            'provider_bank_details.bank_name',
            'provider_bank_details.holder_name',
            'provider_bank_details.bank_location',
            'provider_bank_details.payment_email',
            'provider_bank_details.bic_swift_code',
            'provider_bank_details.account_number'
        )
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', '=','user_service_package_booking.promo_code')
            ->leftJoin('provider_bank_details', 'provider_bank_details.provider_id', '=', 'user_service_package_booking.provider_id')
            ->where('user_service_package_booking.status', 9)
            ->whereIn('user_service_package_booking.service_cat_id', $this->on_demand_service_id_array);

        $from_date = Date('Y-m-d');
        $to_date = Date('Y-m-d');
        $from = Date('Y-m-d', strtotime($from_date)) . " 00:00:00";
        $to = Date('Y-m-d', strtotime($to_date)) . " 23:59:59";

        $package_order_list->whereDate('user_service_package_booking.service_date_time', '>=', $from);
        $package_order_list->whereDate('user_service_package_booking.service_date_time', '<=', $to);

        $package_order_list = $package_order_list->orderBy('user_service_package_booking.id', "desc")->get();
        $total_amount = $package_order_list->sum('total_pay');
        $site_commission = $package_order_list->sum('admin_commission');
        $provider_earning = $package_order_list->sum('provider_amount');
        $total_discount = $package_order_list->sum('promocode');
        $refer_discount = $package_order_list->sum('refer_discount');
        $collect_from_provider = $package_order_list->where('payment_type', 1)->sum('total_pay') - $package_order_list->where('payment_type', 1)->sum('provider_amount');
        $collect_from_provider_total = $package_order_list->where('payment_type', 1)->sum('total_pay');

        $total_outstanding = $provider_earning - $collect_from_provider_total;
        $total_provider_outstanding_amount = number_format($total_outstanding, 2);

        $view = view('admin.pages.other_services.earning_report.billing_admin_earning_report',
            compact('user_list', 'provider_list', 'from_date', 'to_date','service_category_list','package_order_list', 'total_amount', 'site_commission',
                'provider_earning', 'collect_from_provider', 'total_discount', 'total_provider_outstanding_amount','refer_discount'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postOtherServiceEarningReport(Request $request)
    {
        if (Auth::guard("admin")->user()->roles != 3) {
            Session::flash('error', 'Something want to wrong!');
            return redirect()->back();
        }

        $service_category_list = ServiceCategory::query()
            ->select('service_category.id as service_cat_id', 'service_category.name as service_cat_name')
            ->whereIn('service_category.id', $this->on_demand_service_id_array)
            ->where('service_category.status', 1)
            ->get();
//        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
//        if ($service_category == Null) {
//            Session::flash('error', 'Service Category Not Found!');
//            return redirect()->back();
//        }
        $user_list = UserPackageBooking::query()->select('users.id',
            'users.first_name', 'users.last_name', 'users.contact_number')
            ->join('users', 'users.id', '=', 'user_service_package_booking.user_id')
            ->where('user_service_package_booking.status', '=', 9)
            ->whereIn('user_service_package_booking.service_cat_id', $this->on_demand_service_id_array)
            ->whereNull('users.deleted_at')
            ->groupBy('user_service_package_booking.user_id')
            ->get();
        $provider_list = UserPackageBooking::query()->select('providers.id',
            DB::raw("CONCAT(COALESCE(providers.first_name,''),' ',COALESCE(providers.last_name,'')) as name"), 'providers.contact_number', 'user_service_package_booking.provider_id')
//            ->join('provider_services', 'provider_services.id', '=', 'user_service_package_booking.provider_id')
            ->join('providers', 'providers.id', '=', 'user_service_package_booking.provider_id')
            ->where('user_service_package_booking.status', '=', 9)
            ->whereIn('user_service_package_booking.service_cat_id', $this->on_demand_service_id_array)
            ->whereNull('providers.deleted_at')
            ->groupBy('user_service_package_booking.provider_id')
            ->get();

        $from_date = ($request['from_date'] != Null && $request['to_date'] != Null) ? $request['from_date'] : Null;
        $to_date = ($request['from_date'] != Null && $request['to_date'] != Null) ? $request['to_date'] : Null;
        $provider = $request['provider'] != Null ? $request['provider'] : Null;
        $user = $request['user'] != Null ? $request['user'] : Null;
        $payment_type = $request['payment_type'] != Null ? $request['payment_type'] : Null;
        $provider_pay_type = $request['provider_pay_type'] != Null ? $request['provider_pay_type'] : Null;
        $service_id = $request['service_id'] != Null ? $request['service_id'] : Null;

        $package_order_list = UserPackageBooking::query()->select('user_service_package_booking.id',
            'user_service_package_booking.order_no',
            'user_service_package_booking.order_type',
            'user_service_package_booking.service_date_time',
            'user_service_package_booking.service_time',
            'user_service_package_booking.user_name',
            'user_service_package_booking.promo_code',
            'user_service_package_booking.provider_name',
            'user_service_package_booking.total_item_cost',
            'user_service_package_booking.extra_amount',
            'user_service_package_booking.tax',
            'user_service_package_booking.tip',
            'user_service_package_booking.total_pay',
            'user_service_package_booking.provider_amount',
            'user_service_package_booking.refer_discount',
            'user_service_package_booking.created_at',
            'user_service_package_booking.user_id',
            'user_service_package_booking.admin_commission',
            'user_service_package_booking.provider_pay_settle_status',
            'used_promocode_details.discount_amount as promocode',
            'user_service_package_booking.provider_id',
            'user_service_package_booking.payment_type',
            'service_category.name as service_category_name')
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', '=','user_service_package_booking.promo_code')
            ->where('user_service_package_booking.status', 9)
            ->whereIn('user_service_package_booking.service_cat_id', $this->on_demand_service_id_array);
        if ($from_date != Null && $to_date != Null) {
            $from = Date('Y-m-d', strtotime($from_date)) . " 00:00:00";
            $to = Date('Y-m-d', strtotime($to_date)) . " 23:59:59";
            $package_order_list->whereDate('user_service_package_booking.service_date_time', '>=', $from);
            $package_order_list->whereDate('user_service_package_booking.service_date_time', '<=', $to);
        }
        if ($user != Null) {
            $package_order_list->where('user_service_package_booking.user_id', $user);
        }
        if ($provider != Null) {
            $package_order_list->where('user_service_package_booking.provider_id', $provider);
        }
        if ($payment_type != Null) {
            //cash & card
            $package_order_list->where('user_service_package_booking.payment_type', $payment_type);
        }
        if ($service_id != Null) {
            $package_order_list->where('user_service_package_booking.service_cat_id', $service_id);
        }
        if ($provider_pay_type != Null) {
            $package_order_list->where('user_service_package_booking.provider_pay_settle_status', $provider_pay_type);
        }
        $package_order_list = $package_order_list->orderBy('user_service_package_booking.id', "desc")->get();
        $promo_total=0;
        $used_promo_cods = [];
        $collect_payment = [];
        $pay_payment = [];
        foreach($package_order_list as $order){
            $promo = UsedPromocodeDetails::query()->where('id',$order->promo_code)->first();
            if($promo != Null){
                $used_promo_cods[$order->id] = $promo->discount_amount;
                $promo_total=$promo_total+$promo->discount_amount;
            }
            if($order->payment_type == 1)
            {
                $collect_payment[$order->id] = $order->total_pay - $order->provider_amount;
                $pay_payment[$order->id] = 0;
            }
            else{
                $collect_payment[$order->id] = 0;
                $pay_payment[$order->id] = $order->provider_amount;
            }
        }
        $total_amount = +$package_order_list->sum('total_pay');
        $site_commission = $package_order_list->sum('admin_commission');
        $provider_earning = $package_order_list->sum('provider_amount');
        $total_discount = $package_order_list->sum('promocode');
        $refer_discount = $package_order_list->sum('refer_discount');
        $collect_from_provider = ($package_order_list->where('payment_type', 1)->sum('total_pay')) - ($package_order_list->where('payment_type', 1)->sum('provider_amount'));

        $total_outstanding = $provider_earning - $collect_from_provider;
        $total_provider_outstanding_amount = number_format($total_outstanding, 2);

//        dd($package_order_list);
        $view = view('admin.pages.other_services.earning_report.billing_admin_earning_report', compact(
            'user_list', 'provider_list', 'from_date', 'to_date', 'provider', 'user', 'payment_type', 'provider_pay_type',
            'package_order_list', 'total_amount','used_promo_cods', 'site_commission', 'provider_earning', 'collect_from_provider', 'service_category_list', 'refer_discount','total_discount','promo_total', 'total_provider_outstanding_amount', 'service_id'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postOtherServiceOrderPaymentSettle(Request $request)
    {
        if($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        if (Auth::guard("admin")->user()->roles != 3) {
            Session::flash('error', 'Something want to wrong!');
            return redirect()->back();
        }

        if ($request->get('order_id') != Null) {
            $order_id_settle = $request->get('order_id');
            foreach ($order_id_settle as $key => $order_id) {
//                dd($order_id);
                $order_details = UserPackageBooking::where('id', $order_id)->first();
                if ($order_details != Null) {
                    $order_details->provider_pay_settle_status = 1;
                    $order_details->save();
                }
            }
        }
        return response()->json([
            "success" => true,
            "message" => "success",
        ]);
    }
}
