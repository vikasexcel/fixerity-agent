<?php

namespace App\Http\Controllers;

use App\Classes\AdminClass;
use App\Classes\NotificationClass;
use App\Http\Requests\OtherServicePackagesRequest;
use App\Http\Requests\OtherServiceProviderStoreRequest;
use App\Http\Requests\OtherServiceSubCategoryRequest;
use App\Http\Requests\ServiceCategoryRequest;
use App\Models\AdminAreaList;
use App\Models\CashOut;
use App\Models\LanguageLists;
use App\Models\OtherServiceCategory;
use App\Models\OtherServiceProviderDetails;
use App\Models\OtherServiceProviderPackages;
use App\Models\OtherServiceProviderTimings;
use App\Models\OtherServiceRatings;
use App\Models\PromocodeDetails;
use App\Models\Provider;
use App\Models\ProviderAcceptedPackageTime;
use App\Models\ProviderBankDetails;
use App\Models\ProviderDocuments;
use App\Models\ProviderServices;
use App\Models\ServiceCategory;
use App\Models\ServiceSettings;
use App\Models\ServiceSliderBanner;
use App\Models\UsedPromocodeDetails;
use App\Models\User;
use App\Models\UserPackageBooking;
use App\Models\UserPackageBookingQuantity;
use App\Models\UserReferHistory;
use App\Models\UserWalletTransaction;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\RequiredDocumentsRequest;
use App\Models\RequiredDocuments;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;

class OtherServiceController extends Controller
{
    private $adminClass;
    private $notificationClass;
    private $package_status;
    private $order_status;
    private $is_restricted = 0;

    public function __construct(AdminClass $adminClass, NotificationClass $notificationClass)
    {
        $this->adminClass = $adminClass;
        $this->notificationClass = $notificationClass;
        $this->package_status = ['', 'pending', 'approved', 'approved', 'rejected', 'cancelled', 'ongoing', 'arrived', 'processing', 'completed', 'failed'];
        $this->order_status = [2, 3, 6, 7, 8];

        $this->middleware( function ($request, $next) {
            $is_restrict_admin = $request->get('is_restrict_admin');
            $this->is_restricted = $is_restrict_admin;
            return $next($request);
        });
    }

    public static function checkCategoryType($slug)
    {
        $service = ServiceCategory::where('slug', $slug)->first();
        if ($service != Null) {
            return $service->category_type;
        } else {
            return 0;
        }
    }

    public function getAddServiceCategory(Request $request)
    {
        $language_lists = LanguageLists::query()->where('status', '=', '1')->get();

        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.service_category.form',compact( 'language_lists'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.service_category.form',compact( 'language_lists'));
    }

    //edit service category form
    public function getEditServiceCategory($slug, Request $request)
    {

        $service_category = ServiceCategory::where('slug', $slug)->first();
        $language_lists = LanguageLists::query()->where('status','=','1')->get();
        if ($service_category != Null) {
            if ($request->ajax()) {
                $view = view('admin.pages.super_admin.service_category.form', compact('service_category','language_lists'))->renderSections();
                return $this->adminClass->renderingResponce($view);
            }
            return view('admin.pages.super_admin.service_category.form', compact('service_category','language_lists'));
        } else {
            Session::flash('error', 'Service Category Not Found!');
            return redirect()->back();
        }
    }

    //update or store service category
    public function postUpdateServiceCategory(ServiceCategoryRequest $request)
    {

        if($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $id = $request->get('id');
        if ($id != Null) {
            $service_category = ServiceCategory::where('id', $request->get('id'))->first();
            //code check service have active sub category or not
            $check_sub_category = OtherServiceCategory::query()->where('service_cat_id','=',$service_category->id)->where('status','=',1)->first();
            if($check_sub_category != Null)
            {
                $service_category->status = $request->get('status');
            }else{
                $service_category->status = 0;
            }
        } else {
            $last_order = ServiceCategory::query()->orderBy('display_order', 'desc')->first();
//            dd($last_order->display_order);
            $service_category = new ServiceCategory();
            $service_category->status = 0;
            $service_category->slug = $service_category->slug($request->get('name'));
            if ($last_order != null) {
                $service_category->display_order = ($last_order->display_order + 1);
            } else {
                $service_category->display_order = 1;
            }
        }
        $service_category->name = $request->get('name');
        $service_category->ar_name = $request->get('ar_name');
        /*$service_category->en_name = $request->get('name');
        $service_category->fl_name = $request->get('fl_name');
        $service_category->cb_name = $request->get('cb_name');
        $service_category->cs_name = $request->get('cs_name');
        $service_category->ct_name = $request->get('ct_name');
        $service_category->jp_name = $request->get('jp_name');
        $service_category->ko_name = $request->get('ko_name');
        $service_category->fr_name = $request->get('fr_name');
        $service_category->sp_name = $request->get('sp_name');
        $service_category->gr_name = $request->get('gr_name');
        $service_category->ar_name = $request->get('ar_name');*/
        try{
            $language_list = LanguageLists::query()->select('language_name as name',
                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as category_col_name")
            )->where('status',1)->get();
            foreach ($language_list as $key => $language){
                $service_category->{$language->category_col_name} = $request->get($language->category_col_name);
            }

        } catch (\Exception $e) {}


        if ($request->file('icon')) {
            if (\File::exists(public_path('/assets/images/service-category/' . $service_category->icon_name))) {
                \File::delete(public_path('/assets/images/service-category/' . $service_category->icon_name));
            }
            $filename = date('HisYdm') . '.' . $request->file('icon')->getClientOriginalExtension();
            $request->file('icon')->move(public_path('/assets/images/service-category'), $filename);
            $service_category->icon_name = $filename;
        }
//        if($request->hasFile('banner_image') != Null){
//            if (\File::exists(public_path('/assets/images/provider-banners/' . $service_category->banner_image))) {
//                \File::delete(public_path('/assets/images/provider-banners/' . $service_category->banner_image));
//            }
//            $destinationPath = public_path('/assets/images/provider-banners/');
//            $file = $request->file('banner_image');
//            $img = Image::read($file->getRealPath());
//            $img->orient();
//            $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
//            $img->resize(980, 400, function ($constraint) {
//                //$constraint->aspectRatio();
//            })->save($destinationPath . $file_new);
//            $service_category->banner_image = $file_new;
//        }
//        $service_category->category_type = $request->get('icon_type');
//        $service_category->status = 0;
        $service_category->save();
        if ($id != Null) {
            Session::flash('success', 'Service Category Updated successfully!');
        } else {
            Session::flash('success', 'Service Category Added successfully!');
        }
        return redirect()->route('get:admin:other_service_list');
    }

    public function getOtherServiceDashboard($slug, Request $request)
    {
        $service_category = ServiceCategory::query()->select('id', 'name', 'category_type')->where('slug', $slug)->first();
        if ($service_category != Null) {
            $admin_role = request()->get("admin_role");
            $admin_city_id = request()->get("admin_city_id");

            $last_seven_date = date('Y-m-d', strtotime("-6 days"));
            $approved_provider = ProviderServices::query()->join('providers', 'providers.id', '=', 'provider_services.provider_id')->join('other_service_provider_details', 'other_service_provider_details.provider_id', 'provider_services.provider_id')->where('provider_services.service_cat_id', $service_category->id);
            $live_provider = ProviderServices::query()->join('providers', 'providers.id', '=', 'provider_services.provider_id')->join('other_service_provider_details', 'other_service_provider_details.provider_id', 'provider_services.provider_id')->where('provider_services.service_cat_id', $service_category->id);
            $pending_provider = ProviderServices::query()->join('providers', 'providers.id', '=', 'provider_services.provider_id')->join('other_service_provider_details', 'other_service_provider_details.provider_id', 'provider_services.provider_id')->where('provider_services.service_cat_id', $service_category->id);

            $total_amount = UserPackageBooking::query()->where('service_cat_id', $service_category->id)->where('status', 9);
            $total_orders = UserPackageBooking::query()->where('service_cat_id', $service_category->id)->whereDate('created_at', '>=', $last_seven_date);
            $completed_orders = UserPackageBooking::query()->where('service_cat_id', $service_category->id)->whereDate('created_at', '>=', $last_seven_date)->where('status', 9);
            $running_orders = UserPackageBooking::query()->where('service_cat_id', $service_category->id)->whereDate('created_at', '>=', $last_seven_date)->whereIn('status', [2, 3, 6, 7, 8]);
            $cancelled_orders = UserPackageBooking::query()->where('service_cat_id', $service_category->id)->whereDate('created_at', '>=', $last_seven_date)->whereIn('status', [4, 5,10]);

            if ($admin_role == 4) {
                $approved_provider = $approved_provider->where('providers.area_id', $admin_city_id);
                $live_provider = $live_provider->where('providers.area_id', $admin_city_id);
                $pending_provider = $pending_provider->where('providers.area_id', $admin_city_id);

                $total_amount = $total_amount->where('area_id', $admin_city_id);
                $total_orders = $total_orders->where('area_id', $admin_city_id);
                $completed_orders = $completed_orders->where('area_id', $admin_city_id);
                $running_orders = $running_orders->where('area_id', $admin_city_id);
                $cancelled_orders = $cancelled_orders->where('area_id', $admin_city_id);
            }
            $approved_provider = $approved_provider->where('provider_services.status', 1)->whereNull('providers.deleted_at')->count();
            $live_provider = $live_provider->where('provider_services.current_status', 1)->where('provider_services.status', 1)->count();
            $pending_provider = $pending_provider->where('provider_services.status', 0)->whereNull('providers.deleted_at')->count();

            $total_amount = $total_amount->sum('admin_commission');
            $total_orders = $total_orders->count();
            $completed_orders = $completed_orders->count();
            $running_orders = $running_orders->count();
            $cancelled_orders = $cancelled_orders->count();

            $view = view('admin.pages.super_admin.service_dashboard.other_service_dashboard', compact('service_category', 'slug', 'approved_provider', 'live_provider', 'pending_provider', 'total_amount', 'total_orders', 'completed_orders', 'running_orders', 'cancelled_orders'));
        } else {
            $view = view('admin.pages.super_admin.service_dashboard.other_service_dashboard', compact('slug'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getOtherServiceList(Request $request)
    {
        $other_service_categories = ServiceCategory::query()->select('id', 'name', 'slug', 'icon_name', 'category_type', 'status')
            ->whereIn('category_type', [3, 4]);
        if (request()->get("is_all_service") == 1) {
            $other_service_categories->whereIn("id", request()->get("admin_category_id_list"));
        }
        $other_service_categories = $other_service_categories->get();
        if ($other_service_categories->isEmpty()) {
            $view = view('admin.pages.other_services.other_service_list');
        } else {
            $view = view('admin.pages.other_services.other_service_list', compact('other_service_categories'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getOtherServicesProviderList(Request $request, $slug, $status)
    {
//        $sub_category = OtherServiceCategory::join('service_category','service_category.id','=','other_service_sub_category.service_cat_id')->where('service_category.slug',$slug)->pluck('other_service_sub_category.id');
//        dd($sub_category);
        if ($status == "approved") {
            $status = 1;
        } elseif ($status == "unapproved") {
            $status = 0;
        } elseif ($status == "blocked") {
            $status = 2;
        } elseif ($status == "rejected") {
            $status = 3;
        } else {
            return redirect()->back();
        }
        $service_category = ServiceCategory::where('slug', $slug)->first();
        $providers = Provider::select('providers.id', 'provider_services.id as provider_id', DB::raw("CONCAT(COALESCE(providers.first_name,''),' ',COALESCE(providers.last_name,'')) as name"),
            'providers.created_at',
            'providers.contact_number',
            'providers.country_code',
            'other_service_provider_details.rating',
            'provider_services.status',
            'provider_services.is_sponsor',
            'user_wallet_transaction.remaining_balance',
            'other_service_rating.rating',
            DB::raw('(SELECT AVG(rating) FROM other_service_rating WHERE other_service_rating.provider_id = providers.id AND other_service_rating.status = 1) AS average_rating')
        )
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
            ->leftJoin('other_service_rating','other_service_rating.provider_id','=','providers.id')
//            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
//            ->where('service_category.slug', $slug)
            ->leftJoin('user_wallet_transaction', function($query) {
                $query->on('user_wallet_transaction.user_id','=','providers.id');
                $query->on('user_wallet_transaction.id','=',DB::raw("(SELECT max(id) from user_wallet_transaction WHERE user_wallet_transaction.user_id = providers.id)"));
            })
            ->where('provider_services.status', $status)
            ->where('provider_services.service_cat_id', $service_category->id)
            ->whereNull('providers.deleted_at')
            ->orderBY('id','desc')
            ->groupBy('providers.id')
            ->get();
//dd($providers);

        $provider_id = $providers->pluck('provider_id')->toArray();
        $package_count = OtherServiceProviderPackages::select(array(DB::raw('COUNT(other_service_provider_packages.id) as packages,provider_service_id')))
            ->whereIN('provider_service_id', $provider_id)->groupBy('provider_service_id')->pluck('packages', 'provider_service_id')->toArray();
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.other_service_provider_list', compact('providers', 'service_category', 'slug', 'status', 'package_count'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.other_service_provider_list', compact('providers', 'service_category', 'slug', 'status', 'package_count'));
    }

    public function getOtherServicesAddProviderBKP($slug, Request $request)
    {
        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'category not found!');
            return redirect()->back();
        }
        $service_category_multiple = ServiceCategory::select('id', 'name')->whereIn('category_type', [3, 4])->get();
        $all_day_open_time = ["09:00 AM - 10:00 AM", "10:00 AM - 11:00 AM", "11:00 AM - 12:00 PM", "12:00 PM - 01:00 PM", "01:00 PM - 02:00 PM", "02:00 PM - 03:00 PM", "03:00 PM - 04:00 PM", "04:00 PM - 05:00 PM", "05:00 PM - 06:00 PM", "06:00 PM - 07:00 PM"];
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.provider.form', compact('slug', 'service_category', 'service_category_multiple','all_day_open_time'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.provider.form', compact('slug', 'service_category', 'service_category_multiple','all_day_open_time'));
    }

    public function getOtherServicesAddProvider($slug, Request $request)
    {
        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'category not found!');
            return redirect()->back();
        }

        $service_category_multiple = ServiceCategory::query()->select('id', 'name')->whereIn('category_type', [3, 4])->get();
        $general_settings = request()->get("general_settings");
        $default_start_time= ($general_settings->default_start_time != Null)?$general_settings->default_start_time:"00:00:00";
        $default_end_time= ($general_settings->default_end_time != Null)?$general_settings->default_end_time:"23:00:00";
        $default_open_close_time =  $this->notificationClass->defaultProviderOpenCloseTime($default_start_time,$default_end_time);
        $default_slot= isset($default_open_close_time['default_provider_slot'])?$default_open_close_time['default_provider_slot']:[];


        $provider_start_time= ($general_settings->provider_start_time != Null)?$general_settings->provider_start_time:"08:00:00";
        $provider_end_time= ($general_settings->provider_end_time != Null)?$general_settings->provider_end_time:"20:00:00";
        $default_provider_open_close_time =  $this->notificationClass->defaultProviderOpenCloseTime($provider_start_time,$provider_end_time);
        $default_provider_slot= isset($default_provider_open_close_time['default_provider_slot'])?$default_provider_open_close_time['default_provider_slot']:[];
        $days_arr = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];
        $time_slot_list =[];
        $all_day =  0;
        foreach ($days_arr as $single_day){
            foreach ($default_slot as $key=>$single_default_slot){
                $selected = 0;
                foreach ($default_provider_slot as $single_default_provider_slot) {
                    if($single_default_provider_slot['start_time'] == $single_default_slot['start_time'] &&$single_default_provider_slot['end_time'] == $single_default_slot['end_time'] ) {
                        $selected = 1;
                    }
                }
                $time_slot_list[strtolower($single_day)][] = array(
                    'start_time'=>$single_default_slot['start_time'],
                    'end_time'=>$single_default_slot['end_time'],
                    'display_start_time' => date("h:i A", strtotime($single_default_slot['start_time'])),
                    'display_end_time' => date("h:i A", strtotime($single_default_slot['end_time'])),
                    'selected'=>$selected,
                );
            }
        }
        //$all_day_open_time = ["09:00 AM - 10:00 AM", "10:00 AM - 11:00 AM", "11:00 AM - 12:00 PM", "12:00 PM - 01:00 PM", "01:00 PM - 02:00 PM", "02:00 PM - 03:00 PM", "03:00 PM - 04:00 PM", "04:00 PM - 05:00 PM", "05:00 PM - 06:00 PM", "06:00 PM - 07:00 PM"];
        $view = view('admin.pages.other_services.provider.form', compact('slug', 'service_category', 'service_category_multiple','time_slot_list','days_arr','all_day'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getOtherServicesEditProviderBKP(Request $request, $slug, $id)
    {
        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'category not found!');
            return redirect()->back();
        }
        $service_category_multiple = ServiceCategory::select('id', 'name')->whereIn('category_type', [3, 4])->get();
        $provider = Provider::query()->where('id', $id)->whereNull('providers.deleted_at')->first();
        if ($provider != Null) {
            $provider_other_details = OtherServiceProviderDetails::query()->where('provider_id', $provider->id)->first();
            $bank_details = ProviderBankDetails::query()->where('provider_id', $provider->id)->first();

            $sun_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'SUN')->first();
            $mon_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'MON')->first();
            $tue_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'TUE')->first();
            $wed_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'WED')->first();
            $thu_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'THU')->first();
            $fri_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'FRI')->first();
            $sat_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'SAT')->first();
            $all_day_open_time = [];

            $count = 0;
            $check_get_data = 0;
            $time_status = $provider_other_details->time_slot_status;
            if ($sun_day_open_time != Null) {
                $check_get_data = 1;
                $sun_day_open_time = explode(',', $sun_day_open_time->open_time_list);
            }
            else{
                $sun_day_open_time = [];
            }
            if ($mon_day_open_time != Null) {
                $check_get_data = 1;
                $mon_day_open_time = explode(',', $mon_day_open_time->open_time_list);
            }
            else{
                $mon_day_open_time = [];
            }
            if ($tue_day_open_time != Null) {
                $check_get_data = 1;
                $tue_day_open_time = explode(',', $tue_day_open_time->open_time_list);
            }
            else{
                $tue_day_open_time = [];
            }
            if ($wed_day_open_time != Null) {
                $check_get_data = 1;
                $wed_day_open_time = explode(',', $wed_day_open_time->open_time_list);
            }
            else{
                $wed_day_open_time = [];
            }
            if ($thu_day_open_time != Null) {
                $check_get_data = 1;
                $thu_day_open_time = explode(',', $thu_day_open_time->open_time_list);
            }
            else{
                $thu_day_open_time = [];
            }
            if ($fri_day_open_time != Null) {
                $check_get_data = 1;
                $fri_day_open_time = explode(',', $fri_day_open_time->open_time_list);
            }
            else{
                $fri_day_open_time = [];
            }
            if ($sat_day_open_time != Null) {
                $check_get_data = 1;
                $sat_day_open_time = explode(',', $sat_day_open_time->open_time_list);
            }
            else{
                $sat_day_open_time = [];
            }

            if ($sun_day_open_time == $mon_day_open_time) {
                $count = $count + 1;
            }
            if ($sun_day_open_time == $tue_day_open_time) {
                $count = $count + 1;
            }
            if ($sun_day_open_time == $wed_day_open_time) {
                $count = $count + 1;
            }
            if ($sun_day_open_time == $thu_day_open_time) {
                $count = $count + 1;
            }
            if ($sun_day_open_time == $fri_day_open_time) {
                $count = $count + 1;
            }
            if ($sun_day_open_time == $sat_day_open_time) {
                $count = $count + 1;
            }

            $all_day = 0;
            if ($count === 6) {
                $all_day = 1;
                $all_day_open_time = $sun_day_open_time;
                $sun_day_open_time = [];
                $mon_day_open_time = [];
                $tue_day_open_time = [];
                $wed_day_open_time = [];
                $thu_day_open_time = [];
                $fri_day_open_time = [];
                $sat_day_open_time = [];
            }
            if ($check_get_data == 0) {
                $all_day = 1;
            }

            $view = view('admin.pages.other_services.provider.form', compact('slug', 'service_category', 'service_category_multiple', 'provider', 'provider_other_details', 'bank_details',
                'all_day', 'all_day_open_time', 'sun_day_open_time', 'mon_day_open_time', 'tue_day_open_time', 'wed_day_open_time', 'thu_day_open_time', 'fri_day_open_time', 'sat_day_open_time','time_status'));

            if ($request->ajax()) {
                $view = $view->renderSections();
                return $this->adminClass->renderingResponce($view);
            } else {
                return $view;
            }
        } else {
            $view = view('admin.pages.other_services.provider.form', compact('slug', 'service_category', 'service_category_multiple'));
            if ($request->ajax()) {
                $view = $view->renderSections();
                return $this->adminClass->renderingResponce($view);
            } else {
                return $view;
            }
        }
    }

    public function getOtherServicesEditProvider(Request $request, $slug, $id) {
        $service_category = ServiceCategory::query()->select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'category not found!');
            return redirect()->back();
        }
        $service_category_multiple = ServiceCategory::query()->select('id', 'name')->whereIn('category_type', [3, 4])->get();
        $provider = Provider::query()->where('id', $id)->whereNull('providers.deleted_at')->first();
        $all_day  = 0;
        if ($provider != Null) {
            $provider_other_details = OtherServiceProviderDetails::query()->where('provider_id', $provider->id)->first();
            $all_day = $provider_other_details->all_day;
            $bank_details = ProviderBankDetails::query()->where('provider_id', $provider->id)->first();

            $general_settings = request()->get("general_settings");
            $default_start_time= ($general_settings->default_start_time != Null)?$general_settings->default_start_time:"00:00:00";
            $default_end_time= ($general_settings->default_end_time != Null)?$general_settings->default_end_time:"23:00:00";
            $default_open_close_time =  $this->notificationClass->defaultProviderOpenCloseTime($default_start_time,$default_end_time);
            $default_slot= isset($default_open_close_time['default_provider_slot'])?$default_open_close_time['default_provider_slot']:[];

            $time_status = $provider_other_details->time_slot_status;
            $days_arr = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];
            $time_slot_list =[];
            foreach ($days_arr as $single_day) {
                foreach ($default_slot as $key => $single_default_slot) {
                    $get_open_timings = OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtolower($single_day))->get();
                    $selected = 0;
                    foreach ($get_open_timings as $get_single_open_timing) {
                        if ($single_default_slot['start_time'] == $get_single_open_timing->provider_open_time && $single_default_slot['end_time'] == $get_single_open_timing->provider_close_time) {
                            $selected = 1;
                        }
                    }
                    $time_slot_list[strtolower($single_day)][] = array(
                        'start_time' => $single_default_slot['start_time'],
                        'end_time' => $single_default_slot['end_time'],
                        'display_start_time' => date("h:i A", strtotime($single_default_slot['start_time'])),
                        'display_end_time' => date("h:i A", strtotime($single_default_slot['end_time'])),
                        'selected' => $selected,
                    );
                }
            }
            $view = view('admin.pages.other_services.provider.form', compact('slug', 'service_category', 'service_category_multiple','all_day', 'provider', 'provider_other_details', 'bank_details','time_slot_list','days_arr','time_status'));
        } else {
            $view = view('admin.pages.other_services.provider.form', compact('slug', 'service_category', 'service_category_multiple','all_day'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        } else {
            return $view;
        }
    }

    public function getOtherServicesChangeProviderStatus(Request $request)
    {
        $id = $request->get('id');
        $slug = $request->get('slug');
        if ($id == Null || $slug == Null) {
            Session::flash('error', 'something went to wrong!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $service_category = ServiceCategory::where('slug', $slug)->first();
        if ($service_category == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider_service = ProviderServices::where('provider_id', $id)->where('service_cat_id', $service_category->id)->first();
        if ($provider_service == Null) {
            Session::flash('error', 'provider Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider_service->status = $request->get('status');
        $provider_service->save();
        return response()->json([
            'success' => true,
            'status' => $provider_service->status
        ]);
    }

    public function getOtherServicesChangeDeleteProvider(Request $request)
    {
        $id = $request->get('id');
        $slug = $request->get('slug');
        if ($id == Null || $slug == Null) {
            Session::flash('error', 'something went to wrong!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $service_category = ServiceCategory::where('slug', $slug)->first();
        if ($service_category == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider_service = ProviderServices::where('provider_id', $id)->where('service_cat_id', $service_category->id)->first();
        if ($provider_service == Null) {
            Session::flash('error', 'provider Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
//        $provider_service->status = 0;
//        $provider_service->save();
        $provider_service->delete();
        return response()->json([
            'success' => true
        ]);
    }

    public function getOtherServicePackageList(Request $request)
    {
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.packages.manage')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.packages.manage');
    }

    public function getEditOtherServicePackage(Request $request)
    {
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.packages.form')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.packages.form');
    }

//    public function getDeleteOtherServicePackage()
//    {
//
//
//    }
//
//    public function postUpdateOtherServicePackage()
//    {
//        return redirect()->back();
//    }

    public function getOtherServiceOrderList($slug, $status, Request $request)
    {
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        if ($status == "all") {
            $newStatus = [];
        } elseif ($status == "pending") {
            $newStatus = [1];
        } elseif ($status == "approved") {
            $newStatus = [2, 3];
        } elseif ($status == "rejected") {
            $newStatus = [4];
        } elseif ($status == "ongoing") {
            $newStatus = [6, 7, 8];
        } elseif ($status == "completed") {
            $newStatus = [9];
        } elseif ($status == "cancelled") {
            $newStatus = [5, 10];
        } else {
            $newStatus = [];
        }
        if (count($newStatus) > 0) {
            $order_list = UserPackageBooking::query()->select('id', 'user_name', 'provider_name', 'total_pay', 'status', 'payment_type', 'payment_status', 'user_refund_status')->where('service_cat_id', $service_category->id)->whereIN('status', $newStatus)->orderBy('service_date_time','desc')->get();
        } else {
            $order_list = UserPackageBooking::query()->select('id', 'user_name', 'provider_name', 'total_pay', 'status', 'payment_type', 'payment_status', 'user_refund_status')->where('service_cat_id', $service_category->id)->orderBy('service_date_time','desc')->get();
        }
        $order_status = $this->package_status;

        if ($request->ajax()) {
            $view = view('admin.pages.other_services.other_service_order_list', compact('service_category','slug','status','order_list', 'order_status'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.other_service_order_list', compact('service_category','status','slug', 'order_list', 'order_status'));
    }

    public function getOtherServiceProviderOrderList($slug, $provider_id, Request $request)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        $provider_details = Provider::query()->select('id', 'first_name')->where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        if ($provider_details == Null) {
            return redirect()->back();
        }
        $order_list = UserPackageBooking::query()->select('id', 'user_name', 'order_package_list', 'total_pay', 'status', 'payment_type', 'payment_status', 'user_refund_status')->where('service_cat_id', $service_category->id)->where('provider_id', $provider_id)->latest('user_service_package_booking.created_at')->get();
        $order_status = $this->package_status;
        $view = view('admin.pages.other_services.other_service_order_list', compact('slug', 'order_list', 'order_status', 'provider_details'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getOtherServiceReviewList(Request $request)
    {

        if ($request->ajax()) {
            $view = view('admin.pages.other_services.other_service_review_list')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.other_service_review_list');
    }

    public function getOtherServiceProviderReviewList($slug, $id, Request $request)
    {
        $service_category = ServiceCategory::query()->select('id', 'name','category_type')->where('slug', $slug)->first();
        if ($service_category == Null) {
            return redirect()->back();
        }
        $ratings = OtherServiceRatings::select('users.first_name', 'users.last_name', 'other_service_rating.rating', 'other_service_rating.id', 'other_service_rating.comment', 'other_service_rating.status')
            ->join('users', 'users.id', '=', 'other_service_rating.user_id')
            ->join('user_service_package_booking', 'user_service_package_booking.id', 'other_service_rating.booking_id')
            ->where('other_service_rating.provider_id', $id)
            ->whereNull('users.deleted_at')
            ->get();
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.other_service_review_list', compact('ratings', 'slug','service_category'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.other_service_review_list', compact('ratings', 'slug','service_category'));
    }

    public function getOtherServiceSubCategoryList($slug, Request $request)
    {
        $service_category = ServiceCategory::query()->select('id','name','category_type')->where('slug', $slug)->first();
        $sub_categories = OtherServiceCategory::select('other_service_sub_category.id', 'other_service_sub_category.service_cat_id', 'other_service_sub_category.name', 'other_service_sub_category.icon_name', 'other_service_sub_category.status', 'service_category.name as service_category_name')
            ->join('service_category', 'service_category.id', '=', 'other_service_sub_category.service_cat_id')
            ->where('service_category.slug', $slug)->get();
        if ($sub_categories != Null) {
            $view = view('admin.pages.other_services.sub_categories.manage', compact('sub_categories', 'slug','service_category'));
        } else {
            $view = view('admin.pages.other_services.sub_categories.manage', compact('slug','service_category'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getOtherServiceSubCategoryChangeStatus(Request $request)
    {
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $service_sub_category = OtherServiceCategory::where('id', $id)->first();
        if ($service_sub_category == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($service_sub_category->status == 1) {
            $check_sub_category = OtherServiceProviderPackages::query()->where('sub_cat_id','=',$id)->count();
            if($check_sub_category == 0){
                $service_sub_category->status = 0;
                $service_sub_category->save();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Category status cannot be changed because the provider has already added packages to it"
                ]);
            }
        } else {
            $service_sub_category->status = 1;
            $service_sub_category->save();
        }
        return response()->json([
            'success' => true,
            'status' => $service_sub_category->status
        ]);
    }

    public function getAddOtherServiceSubCategory($slug, Request $request)
    {
//        $service_categories = ServiceCategory::select('id', 'name')->where('category_type', 3)->orWhere('category_type', 4)->orderBy('id', 'asc')->get();
        $language_lists = LanguageLists::query()->where('status','=','1')->get();
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.sub_categories.form', compact('slug','language_lists'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.sub_categories.form', compact('slug','language_lists'));
    }

    public function getEditOtherServiceSubCategory($slug, $id, Request $request)
    {
        $service_sub_category = OtherServiceCategory::where('id', $id)->first();
        $language_lists = LanguageLists::query()->where('status','=','1')->get();
        if ($service_sub_category != Null) {
            $view = view('admin.pages.other_services.sub_categories.form', compact('service_sub_category', 'slug','language_lists'));
        } else {
            Session::flash('error', 'category not found!');
            $view = view('admin.pages.other_services.sub_categories.form', compact('slug','language_lists'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getDeleteOtherServiceSubCategory(Request $request)
    {
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Other Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $other_service_category = OtherServiceCategory::where('id', $id)->first();
        if ($other_service_category == Null) {
            Session::flash('error', 'Other Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $check_sub_category = OtherServiceProviderPackages::query()->where('sub_cat_id','=',$id)->count();
        if($check_sub_category == 0){
            if (\File::exists(public_path('/assets/images/service_category/other-service-sub-category/' . $other_service_category->icon_name))) {
                \File::delete(public_path('/assets/images/service_category/other-service-sub-category/' . $other_service_category->icon_name));
            }
            $other_service_category->delete();
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "You can not Delete Category because the provider has already added packages to it"
            ]);
        }


//        Session::flash('error', 'Other Service Category remove successfully!');

    }

    public function getUpdateOtherServiceSubCategory($slug, OtherServiceSubCategoryRequest $request)
    {
        if($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $id = $request->get('id');
        if ($id != Null) {
            $sub_category = OtherServiceCategory::where('id', $id)->first();
            $msg = 'category update successfully!';
        } else {
            $sub_category = new OtherServiceCategory();
            $msg = 'category add successfully!';
        }
        $service_category = ServiceCategory::where('slug', $slug)->first();
        $sub_category->service_cat_id = $service_category->id;
//        $sub_category->name = ucwords(trim($request->get('name')));
        $sub_category->name = $request->get('name');


        try{
            $language_list = LanguageLists::query()->select('language_name as name',
                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as category_col_name")
            )->where('status',1)->get();
            foreach ($language_list as $key => $language){
                if(Schema::hasColumn('other_service_sub_category',$language->category_col_name)  ) {
                    $sub_category->{$language->category_col_name} = $request->get($language->category_col_name);
                }
            }

        } catch (\Exception $e) {}

        /*$sub_category->vn_name = $request->get('vn_name');
        $sub_category->ch_name = $request->get('ch_name');*/

        if ($request->file('category_icon')) {
            if (\File::exists(public_path('/assets/images/service-category/other-service-sub-category/' . $sub_category->icon_name))) {
                \File::delete(public_path('/assets/images/service-category/other-service-sub-category/' . $sub_category->icon_name));
            }
            $file = $request->file('category_icon');
            $file_new = $request->get('service') . date('siHYdm') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path() . '/assets/images/service-category/other-service-sub-category/', $file_new);
            $sub_category->icon_name = $file_new;
        }
        $sub_category->status = $request->get('status');
        $sub_category->save();
        Session::flash('success', $msg);
        return redirect()->route('get:admin:other_service_sub_category_list', $slug);
    }

    public function getOtherServiceProviderPackageList($slug, Request $request, $provider_id)
    {
        $service_category = ServiceCategory::query()->select('id', 'name','category_type')->where('slug', $slug)->first();
        $package_list = OtherServiceProviderPackages::select('other_service_provider_packages.id',
            'other_service_sub_category.name as sub_cat_name',
            'other_service_provider_packages.name as package_name',
            'other_service_provider_packages.max_book_quantity',
            'other_service_provider_packages.price',
            'other_service_provider_packages.status',
            'other_service_provider_packages.provider_service_id')
            ->join('other_service_sub_category', 'other_service_sub_category.id', '=', 'other_service_provider_packages.sub_cat_id')
            ->join('provider_services', 'provider_services.id', '=', 'other_service_provider_packages.provider_service_id')
//            ->join('provider_services', 'provider_services.id', '=', 'other_service_provider_packages.provider_service_id')
            ->join('service_category', 'service_category.id', '=', 'other_service_provider_packages.service_cat_id')
            ->where('provider_services.provider_id', $provider_id)
            ->where('service_category.slug', $slug)
            ->get();
        if (!$package_list->isEmpty()) {
            $view = view('admin.pages.other_services.packages.manage', compact('package_list', 'slug', 'provider_id', 'service_category'));
        } else {
            $view = view('admin.pages.other_services.packages.manage', compact('slug', 'provider_id','service_category'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getAddOtherServiceProviderPackage($slug, Request $request, $provider_id)
    {
        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'category not found!');
            return redirect()->back();
        }
        $service_category_list = OtherServiceCategory::select('id', 'name', 'status')->where('service_cat_id', $service_category->id)->where('status', 1)->get();
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.packages.form', compact('slug', 'service_category', 'service_category_list', 'provider_id'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.packages.form', compact('slug', 'service_category', 'service_category_list', 'provider_id'));
    }

    public function getEditOtherServiceProviderPackage($slug, Request $request, $id)
    {

        $package = OtherServiceProviderPackages::where('id', $id)->first();
        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if($package != Null)
        {
            $service_category_list = OtherServiceCategory::select('id', 'name', 'status')->where("service_cat_id",$package->service_cat_id)->where('status','=',1)->get();
        }else{
            Session::flash('error', 'sub category not found!');
            return redirect()->back();
        }

        if ($package != Null) {
            $view = view('admin.pages.other_services.packages.form', compact('package', 'service_category', 'service_category_list', 'slug'));
        } else {
            Session::flash('error', 'category not found!');
            $view = view('admin.pages.other_services.packages.form', compact('slug', 'service_category', 'service_category_list'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postUpdateOtherServiceProviderPackage($slug, OtherServicePackagesRequest $request)
    {
        if($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        if ($request->get('id') == Null) {
            if ($request->get('provider_id') != Null) {
                $service_category = ServiceCategory::where('slug', $slug)->first();
                if ($service_category != Null) {
                    $provider_service = ProviderServices::where('provider_id', $request->get('provider_id'))->where('service_cat_id', $service_category->id)->first();
                    if ($provider_service != Null) {
                        $add_package = new OtherServiceProviderPackages();
                        $add_package->provider_service_id = $provider_service->id;
                        $add_package->sub_cat_id = $request->get('category');
                        $add_package->service_cat_id = $service_category->id;
                        $add_package->name = $request->get('package_name');
                        $add_package->description = $request->get('description');
                        $add_package->price = $request->get('package_price');
                        $add_package->max_book_quantity = $request->get('max_book_quantity');
                        $add_package->status = 1;
                        $add_package->save();
                        Session::flash('success', 'package add successfully!');
                        return redirect()->route('get:admin:provider_package_list', [$service_category->slug, $provider_service->provider_id]);
                    } else {
                        Session::flash('error', 'something went to wrong!');
                        return redirect()->back();
                    }
                } else {
                    Session::flash('error', 'something went to wrong!');
                    return redirect()->back();
                }
            } else {
                Session::flash('error', 'something went to wrong!');
                return redirect()->back();
            }
        } else {
            $edit_package = OtherServiceProviderPackages::where('id', $request->get('id'))->first();
            if ($edit_package == Null) {
                Session::flash('error', 'something went to wrong!');
                return redirect()->back();
            }
            $service_category = ServiceCategory::where('slug', $slug)->first();
            if ($service_category == Null) {
                Session::flash('error', 'something went to wrong!');
                return redirect()->back();
            }
            $edit_package->service_cat_id = $service_category->id;
            $edit_package->sub_cat_id = $request->get('category');
            $edit_package->name = $request->get('package_name');
            $edit_package->description = $request->get('description');
            $edit_package->price = $request->get('package_price');
            $edit_package->max_book_quantity = $request->get('max_book_quantity');
            $edit_package->status = $request->get('status');
            $edit_package->save();
            $get_provider_id = ProviderServices::where('id', $edit_package->provider_service_id)->first();
            Session::flash('success', 'package updated successfully!');
            if ($get_provider_id != Null) {
                return redirect()->route('get:admin:provider_package_list', [$service_category->slug, $get_provider_id->provider_id]);
            } else {
                return redirect()->route('get:admin:other_service_dashboard', $service_category->slug);
            }
        }
    }

    public function getDeleteOtherServiceProviderPackage(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Other Service Package Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $package = OtherServiceProviderPackages::where('id', $id)->first();
        if ($package == Null) {
            Session::flash('error', 'Other Service Package Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }

        $running_service = UserPackageBooking::query()
            ->whereRaw("find_in_set($id,package_id)")
            ->whereIn('status',[2,3,6,7,8])
            ->count();

        if($running_service > 0){
            return response()->json([
                'success' => false,
                'message' => 'Try again later ! Currently this package is in use'
            ]);
        }

        $package->delete();
//        Session::flash('error', 'Other Service Package remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }

    public function getChangeStatusOtherServiceProviderPackage(Request $request)
    {
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $service_package = OtherServiceProviderPackages::where('id', $id)->first();
        if ($service_package == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($service_package->status == 1) {
            $service_package->status = 0;
            $service_package->save();
//            Session::flash('success', $service_sub_category->name . ' category disabled successfully!');
        } else {
            $service_package->status = 1;
            $service_package->save();
//            Session::flash('success', $service_sub_category->name . ' category enable successfully!');
        }
        return response()->json([
            'success' => true,
            'status' => $service_package->status
        ]);
    }


    public function getOtherServiceOrderDetails(Request $request, $slug, $order_id)
    {
        $service_category = ServiceCategory::query()->select('id', 'name')->where('slug','=', $slug)->first();
        if ($service_category == Null) {
            return redirect()->back();
        }
        $orders_details = UserPackageBooking::query()->select(
            'user_service_package_booking.id',
            'user_service_package_booking.order_no',
            'user_service_package_booking.booking_time_zone',
            'user_service_package_booking.user_name', 'user_service_package_booking.provider_name',
            'user_service_package_booking.service_date_time',
            'user_service_package_booking.service_time',
            'user_service_package_booking.service_date',
            'user_service_package_booking.total_item_cost',
            'user_service_package_booking.delivery_address',
            'user_service_package_booking.extra_amount',
            'user_service_package_booking.tax',
            'user_service_package_booking.tip',
            'user_service_package_booking.created_at',
            'user_service_package_booking.order_type',
            'user_service_package_booking.service_date',
            'user_service_package_booking.service_time',
            'user_service_package_booking.book_start_time',
            'user_service_package_booking.book_end_time',
            'user_service_package_booking.remark','user_service_package_booking.refer_discount',
            'user_service_package_booking.total_pay', 'user_service_package_booking.status'
            , 'users.country_code'
            , 'users.contact_number',
            'used_promocode_details.discount_amount as promo_code_discount',
            'used_promocode_details.promocode_name as promo_code_name'
            , 'user_service_package_booking.cancel_by'
            , 'user_service_package_booking.cancel_reason'
            , 'user_service_package_booking.payment_type'
            , 'user_service_package_booking.payment_status'
            , 'user_service_package_booking.refund_amount', 'user_service_package_booking.cancel_charge', 'user_service_package_booking.user_refund_status'
        )
            ->join('users', 'users.id', '=', 'user_service_package_booking.user_id')
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', 'user_service_package_booking.promo_code')
            ->where('user_service_package_booking.id','=', $order_id)
            ->where('service_category.slug','=', $slug)
//            ->whereNull('users.deleted_at')
            ->first();
        $orders_status = "----";
        $orders_status_array = $this->order_status;
        if ($orders_details != Null) {
            $package_list = UserPackageBookingQuantity::query()->select('num_of_items', 'package_name', 'sub_category_name', 'price_for_one')
                ->where('order_id','=', $orders_details->id)
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
            $view = view('admin.pages.other_services.other_service_order_details', compact('slug', 'orders_status', 'orders_status_array', 'orders_details', 'package_list'));
        } else {
            $view = view('admin.pages.other_services.other_service_order_details', compact('slug', 'orders_status', 'orders_status_array'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //refund order
    public function getUserRefundAmountSettle(Request $request)
    {
        $id = $request->get('id');
        if ($id == Null) {
//            Session::flash('error', 'Store Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $order_details = UserPackageBooking::query()->where('id', $id)->first();
        if ($order_details == Null) {
//            Session::flash('error', 'Store Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($order_details->user_refund_status == 0) {
            if ($order_details->payment_type == 2 || $order_details->payment_type == 3) {

                $service_category = ServiceCategory::query()->where('id', $order_details->service_cat_id)->first();
                if ($service_category == Null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Store details not found'
                    ]);
                }
                try {
                    $get_last_transaction = UserWalletTransaction::query()->where('user_id', $order_details->user_id)->orderBy('id', 'desc')->first();
                    if ($get_last_transaction != Null) {
                        $last_amount = $get_last_transaction->remaining_balance;
                    } else {
                        $last_amount = 0;
                    }
                } catch (\Exception $e) {
                    $last_amount = 0;
                }

                $add_balance = new UserWalletTransaction();
                $add_balance->user_id = $order_details->user_id;
                $add_balance->transaction_type = 1;
                $add_balance->amount = $order_details->refund_amount;
                //$add_balance->subject = "Order Refund Amount";
                $add_balance->subject = "Refund from " . $order_details->provider_name . " - " . $service_category->name . " #" . $order_details->order_no;
                $add_balance->remaining_balance = floatval($last_amount + $order_details->refund_amount);
                $add_balance->subject_code = 10;
                $add_balance->order_no = '';
                $add_balance->save();

                $order_details->user_refund_status = 1;
                $order_details->save();
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function postUpdateOtherServiceProvider(OtherServiceProviderStoreRequest $request, $slug)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        $provider = $this->adminClass->AddServiceProvider($request);
        if ($provider == Null) {
            Session::flash('error', 'Something want to wrong!');
            return redirect()->back();
        }

//        $current_lat = Null;
//        $current_long = Null;

        $id = $request->get('id');
        $day_open_time = $request->get('day_open_time');

        if ($id == Null) {
            //update provider type status
            $provider_data = Provider::query()->where('id', '=', $provider->id)->whereNull('providers.deleted_at')->first();
            if ($provider_data == Null) {
                Session::flash('error', 'Provider not found!');
                return redirect()->back();
            }
            $provider_data->provider_type = 3;
            $provider_data->completed_step = 5;
            $provider_data->save();

            $provider_services = new ProviderServices();
            $provider_services->provider_id = $provider->id;
            $provider_services->service_cat_id = $service_category->id;
            $provider_services->current_status = 1;
            $provider_services->status = 1;
            $provider_services->save();

            $provider_details = new OtherServiceProviderDetails();
            $provider_details->provider_id = $provider->id;
            $provider_details->address = $request->get('address');
            $provider_details->landmark = $request->get('landmark');
            $provider_details->min_order = ($request->get('min_order') == null) ? 0 : $request->get('min_order');
            $provider_details->lat = $request->get('lat');
            $provider_details->long = $request->get('long');

            $general_settings = request()->get("general_settings");

            $default_provider_start_time= ($general_settings->provider_start_time != Null)?$general_settings->provider_start_time:"09:00:00";
            $default_provider_end_time= ($general_settings->provider_end_time != Null)?$general_settings->provider_end_time:"19:00:00";

            $provider_details->start_time = $default_provider_start_time;
            $provider_details->end_time = $default_provider_end_time;
            $provider_details->time_list = "";
            $provider_details->save();

            //$current_lat = $provider_details->lat;
            //$current_long = $provider_details->long;

            $status = "approved";
            Session::flash('success', 'Provider add successfully!');
        } else {
            //update provider type status
            $provider_data = Provider::query()->where('id', '=', $provider->id)->whereNull('providers.deleted_at')->first();
            if ($provider_data != Null) {
                $provider_data->provider_type = 3;
                $provider_data->save();
            }

            $provider_details = OtherServiceProviderDetails::query()->where('provider_id', $provider->id)->first();
            if ($provider_details == Null) {
                Session::flash('error', 'Something want to wrong!');
                return redirect()->back();
            }
            $provider_details->landmark = $request->get('landmark');
            $provider_details->min_order = $request->get('min_order');
            $provider_details->address = $request->get('address');
            $provider_details->lat = $request->get('lat');
            $provider_details->long = $request->get('long');
            $provider_details->save();

            //$current_lat = $provider_details->lat;
            //$current_long = $provider_details->long;

            $status = "approved";
            Session::flash('success', 'Provider updated successfully!');
        }
        $all_day =  $request->get('all_day') != Null ? $request->get('all_day') : 0;
        $open_time_status = $request->get('open_time_status') != Null ? 1 : 0;
        $provider_details->time_slot_status = $open_time_status;
        $provider_details->all_day = $all_day;
        $provider_details->save();

        //slot inseert and update
        $not_del_id_array=[];
        $i=0;
        if($all_day == 1){
            $activeday = strtolower($request->get('activeday'));
            $day_array = array("SUN","MON","TUE","WED","THU","FRI","SAT");
            foreach ($day_array as $key => $day) {
                if (count($day_array) > 0) {
                    if (isset($day_open_time[$activeday])) {
                        foreach ($day_open_time[$activeday] as $singel_day) {
                            $single_time_arr = explode("-", $singel_day);
                            $provider_open_time = $single_time_arr[0];
                            $provider_close_time = $single_time_arr[1];

                            $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtoupper(trim(($day))))->where('provider_open_time', $provider_open_time)->where('provider_close_time', $provider_close_time)->first();
                            if ($get_open_timing == Null) {
                                $get_open_timing_ins = new OtherServiceProviderTimings();
                                $get_open_timing_ins->provider_id = $provider->id;
                                $get_open_timing_ins->day = strtoupper(trim(($day)));
                                $get_open_timing_ins->provider_open_time = $provider_open_time;
                                $get_open_timing_ins->provider_close_time = $provider_close_time;
                                $get_open_timing_ins->open_time_list = "";
                                $get_open_timing_ins->save();
                                $not_del_id_array[$i] = $get_open_timing_ins->id;
                            } else {
                                //get not delete id
                                $not_del_id_array[$i] = $get_open_timing->id;
                            }
                            $i++;
                        }
                        if(count($not_del_id_array)>0){
                            OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtoupper(trim(($day))))->whereNotIn('id', $not_del_id_array)->delete();
                        }
                    } else {
                        OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->delete();
                    }
                }
            }
        } else {
            $day_array = array("SUN","MON","TUE","WED","THU","FRI","SAT");
            if ($day_open_time != Null) {
                foreach ($day_open_time as $key => $day) {
                    $day_name = strtoupper($key);
                    if (($key_data = array_search($day_name, $day_array)) !== false) {
                        unset($day_array[$key_data]);
                    }
                    if (count($day) > 0) {
                        foreach ($day as $singel_day) {

                            $single_time_arr = explode("-",$singel_day);
                            $provider_open_time =  $single_time_arr[0];
                            $provider_close_time =  $single_time_arr[1];
                            $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtoupper(trim(($key))))->where('provider_open_time', $provider_open_time)->where('provider_close_time', $provider_close_time)->first();
                            if ($get_open_timing == Null) {
                                $get_open_timing_ins = new OtherServiceProviderTimings();
                                $get_open_timing_ins->provider_id = $provider->id;
                                $get_open_timing_ins->day = strtoupper(trim(($key)));
                                $get_open_timing_ins->provider_open_time = $provider_open_time;
                                $get_open_timing_ins->provider_close_time = $provider_close_time;
                                $get_open_timing_ins->open_time_list = "";
                                $get_open_timing_ins->save();
                                $not_del_id_array[$i] = $get_open_timing_ins->id;
                            } else {
                                //get not delete id
                                $not_del_id_array[$i] = $get_open_timing->id;
                            }
                            $i++;
                        }
                        if(count($not_del_id_array)>0){
                            OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtoupper(trim(($key))))->whereNotIn('id', $not_del_id_array)->delete();
                        }
                    }
                }
                if(count($day_array) > 0){
                    OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->whereIn('day',$day_array)->whereNotIn('id', $not_del_id_array)->delete();
                }
            } else {
                OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->delete();
            }
        }

        if ($request->get('account_number') != Null && $request->get('holder_name') != Null && $request->get('bank_name') != Null && $request->get('bank_location') != Null && $request->get('payment_email') != Null && $request->get('bic_swift_code') != Null) {
            $provider_bank_details = ProviderBankDetails::query()->where('provider_id', $provider->id)->first();
            if ($provider_bank_details == Null) {
                $provider_bank_details = new ProviderBankDetails();
            }
            $provider_bank_details->provider_id = $provider->id;
            $provider_bank_details->account_number = $request->get('account_number');
            $provider_bank_details->holder_name = $request->get('holder_name');
            $provider_bank_details->bank_name = $request->get('bank_name');
            $provider_bank_details->bank_location = $request->get('bank_location');
            $provider_bank_details->payment_email = $request->get('payment_email');
            $provider_bank_details->bic_swift_code = $request->get('bic_swift_code');
            $provider_bank_details->save();
        }

        return redirect()->route('get:admin:other_service_provider_list', [$slug, $status]);
    }


    public function getOtherServicesRequiredDocumentList($slug, Request $request)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
//
        $required_documents_list = RequiredDocuments::select('required_documents.id', 'required_documents.name as document_name',
            'required_documents.status', 'service_category.name')
            ->join('service_category', 'service_category.id', '=', 'required_documents.service_cat_id')
            ->where('service_category.slug', '=', $slug)
            ->get();
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.required-documents.manage', compact('slug', 'service_category', 'required_documents_list'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.required-documents.manage', compact('slug', 'service_category', 'required_documents_list'));
    }

    //get add services category wise required documents form
    public function getOtherServicesAddRequiredDocument($slug, Request $request)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Required document`s service category not found!');
            return redirect()->back();
        }
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.required-documents.form', compact('slug', 'service_category'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.required-documents.form', compact('slug', 'service_category'));
    }

    //edit required documents
    public function getOtherServicesEditRequiredDocument($slug, $id, Request $request)
    {
        $required_document = RequiredDocuments::where('id', $id)->first();
        $service_category = ServiceCategory::where('slug', $slug)->first();
        if ($required_document != Null) {
            if ($request->ajax()) {
                $view = view('admin.pages.super_admin.required-documents.form', compact('slug', 'required_document', 'service_category'))->renderSections();
                return $this->adminClass->renderingResponce($view);
            }
            return view('admin.pages.super_admin.required-documents.form', compact('slug', 'required_document', 'service_category'));
        } else {
            Session::flash('error', 'Required Document Not Found!');
            return redirect()->back();
        }
    }

    //save or update required documents
    public function postOtherServicesUpdateRequiredDocument(RequiredDocumentsRequest $request)
    {
        $id = $request->get('id');
        if ($id != Null) {
            $required_document = RequiredDocuments::where('id', $request->get('id'))->first();
        } else {
            $required_document = new RequiredDocuments();
        }
        $required_document->name = $request->get('name');
        $required_document->service_cat_id = $request->get('service_cat_id');
        $required_document->status = $request->get('status');
        $required_document->save();

        $sevice_category = ServiceCategory::where('id', $required_document->service_cat_id)->first();
        if ($id != Null) {
            Session::flash('success', $sevice_category->slug . ' Required Document Updated successfully!');
        } else {
            Session::flash('success', $sevice_category->slug . ' Required Document Added successfully!');
        }
        return redirect()->route('get:admin:other_service_required_document_list', [$sevice_category->slug]);
    }

    public function getUpdateOtherServiceProviderStatus(Request $request)
    {

        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }

        $validator = Validator::make($request->all(), [
                "id" => "required",
                "request_for" => "required",
                "service_cat_id" => "required",
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => $validator->errors()->first()
            ]);
        }
        $id = $request->get('id');
        $request_for = $request->get('request_for');
        $service_cat_id = $request->get('service_cat_id');
        //1 = approved
        //2 = blocked
        //3 = rejected
        if ($id == Null || $request_for == Null || $service_cat_id == Null || $service_cat_id == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider_service = ProviderServices::query()->where('id', $id)->first();
        if ($provider_service == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider = Provider::query()->where('id', $provider_service->provider_id)->whereNull('providers.deleted_at')->first();
        if ($provider == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $running_status=['2','6','7','8'];
        $package_booking=UserPackageBooking::query()->where('provider_id',$provider_service->provider_id)->whereIn('status',$running_status)->get();
        if(count($package_booking)>0)
        {
            return response()->json([
                'success' => false,
                'message' => 'Provider has running order.'
            ]);
        }
        else{
            if ($request_for == 1) {
                $provider_service->status = 1;
                $provider_service->save();
                $provider->status = 1;
                $provider->save();

                $general_settings = request()->get("general_settings");
                if ($general_settings !=  Null) {
                    if ($general_settings->send_mail == 1) {
                        $provider_name =  ($provider->first_name != "")? $provider->first_name : "";
                        try{
                            $mail_type = "provider_account_approved";
                            $to_mail = $provider->email;
                            $subject = "Your pending account approved by admin";
                            $disp_data = array("##provider_name##"=>$provider_name );
                            $mail_return_data = $this->notificationClass->sendMail($subject,$to_mail,$mail_type,$disp_data);
                        }
                        catch (\Exception $e){}
                    }
                }
            } elseif ($request_for == 2) {
                if($provider->fix_user_show == 1){
                    return response()->json([
                        "success" => false,
                        "message" => "Sorry,You Cannot Block Fixed Provider"
                    ]);
                }
                $user_running_packages = UserPackageBooking::query()
                    ->where('provider_id', '=', $provider->id)
                    ->where('service_cat_id','=',$service_cat_id)
                    ->where(function ($query) {
                        $query->whereNotIn('status', [4, 5, 9, 10])
                            ->orWhere(function ($query2) {
                                $query2->where('status', 9)->where('payment_status', 0);
                            });
                    })->count();
                if ($user_running_packages > 0) {
                    return response()->json([
                        "success" => false,
                        "message" => "Sorry, Currently the service of this provider is running so you can't block the account at the time. Try Later!"
                    ]);
                }

                $provider_service->status = 2;
                $provider_service->save();
//                $provider->status = 2;
//                $provider->save();
                $general_settings = request()->get("general_settings");
                if ($general_settings !=  Null) {
                    if ($general_settings->send_mail == 1) {
                        $provider_name =  ($provider->first_name != "")?$provider->first_name:"";
                        try{
                            $mail_type = "provider_account_block";
                            $to_mail = $provider->email;
                            $subject = "Account has been blocked by Admin";
                            $disp_data = array("##provider_name##"=>$provider_name );
                            $mail_return_data = $this->notificationClass->sendMail($subject,$to_mail,$mail_type,$disp_data);
                        }
                        catch (\Exception $e){}
                    }
                }
            } elseif ($request_for == 3) {
                $validator = Validator::make($request->all(), [
                        "reject_reason" => "required"
                    ]
                );
                if ($validator->fails()) {
                    return response()->json([
                        "status" => false,
                        "message" => $validator->errors()->first()
                    ]);
                }
                $provider_service->status = 3;
                $provider_service->rejected_reason = $request->get('reject_reason');
                $provider_service->save();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ]);
            }
            return response()->json([
                'success' => true
            ]);
        }

    }

    public function getOtherServiceProviderDocument(Request $request, $slug, $provider_id)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
        $id = $provider_id;
        if ($service_category == null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }

        $provider_services = ProviderServices::where('id', $provider_id)->first();
        if ($provider_services == null) {
            return redirect()->back();
        }

        $provider_status = ProviderServices::query()
            ->select('provider_services.status')
            ->join('providers', 'providers.id', '=', 'provider_services.provider_id')
            ->where('provider_services.id', '=', $provider_id)
            ->whereNull('providers.deleted_at')
            ->first();

        $status = "deleted";
        if (isset($provider_status)) {
            $status = match ($provider_status->status) {
                0 => "unapproved",
                1 => "approved",
                2 => "blocked",
                3 => "rejected",
                default => $status,
            };
        }

        $required_documents = RequiredDocuments::where('service_cat_id', $service_category->id)
            ->where('status', 1)
            ->get();

        $provider_documents = [];
        foreach ($required_documents as $key => $required_document) {
            $document_details = ProviderDocuments::where('provider_service_id', '=', $provider_services->id)
                ->where('req_document_id', $required_document->id)
                ->first();

            if ($document_details) {
                $document_details->file_path = asset('assets/images/provider-documents/' . $document_details->document_file);
            }

            $provider_documents[$key] = $document_details;
        }

        if ($request->ajax()) {
            $view = view('admin.pages.other_services.document.manage', compact('slug', 'status', 'required_documents', 'provider_documents'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }

        return view('admin.pages.other_services.document.manage', compact('slug', 'status', 'required_documents', 'provider_documents', 'id', 'service_category'));
    }

    public function postOtherServiceProviderDocument(Request $request)
    {
        //validations
        $validator = Validator::make($request->all(), [
            "document_file" => "mimes:jpeg,png,jpg,webp",
        ]);
        if ($validator->fails()) {
            Session::flash('error', "Please upload a file in JPEG, PNG, JPG, or WEBP format.");
            return redirect()->back();
        }

        $image = $request->file('document_file');

        if ($image) {
            //path to store the file
            $destinationPath = public_path('/assets/images/provider-documents/');
            //finding image extensions
            $image_extension = $image->extension();
            //making file name
            $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $image->getClientOriginalExtension();
            //fetching store id
            $provider_id = $request->get('provider_id');

            //Fetching store details
            $provider_details = ProviderServices::where('id', $provider_id)->first();
            if ($provider_details == Null) {
                return redirect()->back();
            }
            //fetching required documents
            $get_document = RequiredDocuments::where('id', $request->get('slug'))->where('service_cat_id', '=' , $request->get('service_cat_id'))->first();
            if ($get_document != Null) {
                //fetching uploaded documents
                $find_document = ProviderDocuments::where('provider_service_id', $provider_details->id)->where('req_document_id', $get_document->id)->first();
                if ($find_document != Null) {
                    if (File::exists(public_path('/assets/images/provider-documents/' . $find_document->file_name))) {
                        File::delete(public_path('/assets/images/provider-documents/' . $find_document->file_name));
                    }
                    //image resize
                    if ($image_extension != "pdf" && $image_extension != "doc" && $image_extension != "docx") {
                        $img = Image::read($image->getRealPath());
                        $img->orient();
                        $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $image->getClientOriginalExtension();
                        $img->resize(500, 500, function ($constraint) {
                            $constraint->aspectRatio();
                        })->save($destinationPath . $file_new);
                    } else {
                        $image->move($destinationPath, $file_new);
                    }

                    $find_document->req_document_id = $get_document->id;
                    $find_document->document_file = $file_new;
                    $find_document->status = 0;
                    $find_document->save();
                    Session::flash('success', $get_document->name . ' Updated Successfully!');
                } else {
                    //image resize
                    if ($image_extension != "pdf" && $image_extension != "doc" && $image_extension != "docx") {
                        $img = Image::read($image->getRealPath());
                        $img->orient();
                        $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $image->getClientOriginalExtension();
                        $img->resize(500, 500, function ($constraint) {
                            $constraint->aspectRatio();
                        })->save($destinationPath . $file_new);
                    } else {
                        $image->move($destinationPath, $file_new);
                    }
                    $documents = new ProviderDocuments();
                    $documents->provider_service_id = $provider_details->id;
                    $documents->req_document_id = $get_document->id;
                    $documents->document_file = $file_new;
                    $documents->status = 0;
                    $documents->save();
                    Session::flash('success', $get_document->name . ' Upload Successfully!');
                }
            } else {
                Session::flash('error', 'Document Name Not Found!');
            }
        }
        return redirect()->route('get:admin:provider_document', ['slug' => $request->get('service'), 'id' => $request->get('provider_id')]);
    }


    public function getOtherServiceProviderReviewChangeStatus(Request $request)
    {
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Review Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $review = OtherServiceRatings::where('id', $id)->first();
        if ($review == Null) {
            Session::flash('error', 'Review Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($review->status == 1) {
            $review->status = 0;
            $review->save();
//            Session::flash('success', $service_sub_category->name . ' category disabled successfully!');
        } else {
            $review->status = 1;
            $review->save();
//            Session::flash('success', $service_sub_category->name . ' category enable successfully!');
        }
        return response()->json([
            'success' => true,
            'status' => $review->status
        ]);
    }

    public function getDeleteOtherServiceProviderReview(Request $request)
    {
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
//            Session::flash('error', 'Store Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $review = OtherServiceRatings::where('id', $id)->first();
        if ($review == Null) {
//            Session::flash('error', 'Store Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        OtherServiceRatings::where('id', $id)->delete();
//        Session::flash('error', 'Other Service Package remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }

    public function getUpdateOtherServiceOrderStatus(Request $request)
    {
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $validator = Validator::make($request->all(), [
            "id" => "required|numeric",
            "update_status" => "required|numeric|in:5,9"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => $validator->errors()->first()
            ]);
        }
        $id = $request->get('id');
        $update_status = $request->get('update_status');

        if ($update_status == 5) {
            $validator = Validator::make($request->all(), [
                "reason" => "required"
            ]);
            if ($validator->fails()) {
                return response()->json([
                    "status" => false,
                    "message" => $validator->errors()->first()
                ]);
            }
        }

        $order_details = UserPackageBooking::query()->where('id', $id)->first();
        if ($order_details == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user_details = User::query()->where('id', $order_details->user_id)->whereNull('users.deleted_at')->first();

        //$user_details = User::select('device_token', 'login_device')->where('id', $order_details->user_id)->first();
        if ($order_details->status == 4 || $order_details->status == 5) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        switch ($update_status) {
            case 5:
                if ($order_details->status != 4 && $order_details->status != 5 && $order_details->status != 9 && $order_details->status != 10) {
                    $order_status = $order_details->status;
                    $order_details->status = $update_status;
                    $order_details->cancel_by = "admin";
                    $order_details->cancel_reason = $request->get('reason');
                    if ($order_details->order_type != 1) {
                        if ($order_details->payment_type == 2 || $order_details->payment_type == 3) {
                            $service_settings = ServiceSettings::query()->where('service_cat_id', $order_details->service_cat_id)->first();
                            if ($service_settings != Null) {
                                $cancel_charge = $service_settings->cancel_charge != Null ? $service_settings->cancel_charge : 0;
                                $count_cancel_charge = round((($order_details->total_item_cost * $cancel_charge) / 100), 2);
                                $charges = round($order_details->total_pay - $count_cancel_charge, 2);
                                $order_details->cancel_charge = $count_cancel_charge;
                                $order_details->refund_amount = $charges;
                                $order_details->save();
                            }
                        }
                    }
                    $order_details->save();
                    if ($user_details != Null) {
                        $this->notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status,  $user_details->language);
                    }

                    ProviderAcceptedPackageTime::query()->where('provider_id', '=', $order_details->provider_id)->where('order_id', '=', $order_details->id)->delete();

                    $other_provider_details = Provider::query()->where('id', $order_details->provider_id)->whereNull('providers.deleted_at')->first();
                    if ($other_provider_details != Null) {
                        $this->notificationClass->providerOrderCancelRequestNotification($order_details->id, $order_details->status, $other_provider_details->device_token, $other_provider_details->language);
                    }
                    //deleting chat from firebase
                    (new FirebaseService())->deleteOrderChat($order_details->order_no, $order_details->id);
                    if ($order_status < 8){
                        if ($order_details->promo_code > 0){
                            $used_promocode_details = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                            if ($used_promocode_details != Null) {
                                $get_promocode = PromocodeDetails::query()->where('id', $used_promocode_details->promocode_id)->first();
                                if ($get_promocode != Null) {
                                    $count_promocode = $get_promocode->total_usage - 1;
                                    $get_promocode->total_usage = ($count_promocode > 0)? $count_promocode : 0;
                                    $get_promocode->save();
                                }
                                $used_promocode_details->status = 2;
                                $used_promocode_details->save();
                            }
                        }

                        if ($order_details->refer_discount > 0) {
//                    $user = User::query()->where('id', $order_details->user_id)->whereNull('deleted_at')->first();
                            if ($user_details != Null) {
                                $user_refer_history = UserReferHistory::query()->where('user_id', $order_details->user_id)->where('user_status', 1)->latest()->first();
                                if ($user_refer_history != Null) {
                                    $user_refer_history->user_status = 0;
                                    $user_refer_history->save();
                                    $user_details->pending_refer_discount = $user_details->pending_refer_discount + 1;
                                    $user_details->save();
                                } else {
                                    $user_refer_history = UserReferHistory::query()->where('refer_id', $order_details->user_id)->where('refer_status', 1)->latest()->first();
                                    if ($user_refer_history != Null) {
                                        $user_refer_history->refer_status = 0;
                                        $user_refer_history->save();
                                        $user_details->pending_refer_discount = $user_details->pending_refer_discount + 1;
                                        $user_details->save();
                                    }
                                }
                            }
                        }
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data not found'
                    ]);
                }
                break;
            case 9:
                if ($order_details->status > 1 && $order_details->status < 9) {
                    if ($order_details->status != 4 && $order_details->status != 5 && $order_details->status != 9 && $order_details->status != 10) {
                        $order_details->status = $update_status;
                        $order_details->extra_amount = $request->get('extra_charge') ?? 0;
                        UserPackageBooking::query()->where('id', $order_details->id)->update(['extra_amount' => $order_details->extra_amount]);

                        //check weather the tax and admin commission is applied
                        $find_tax = ServiceSettings::query()->where('service_cat_id', $order_details->service_cat_id)->first();
                        $get_discount = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                        if($get_discount != null){
                            $promo_code_discount = $get_discount->discount_amount;
                        }else{
                            $promo_code_discount = 0;
                        }

                        if ($find_tax != Null) {
                            $provider_details = Provider::where('id', $order_details->provider_id)->whereNull('providers.deleted_at')->first();
                            $get_tax = $find_tax->tax;
                            $admin_commission = $find_tax->admin_commission;
                        } else {
                            $get_tax = 0;
                            $admin_commission = 0;
                        }
                        $order_details->BookingCost($order_details->total_item_cost, $get_tax, $admin_commission, $promo_code_discount, $order_details->refer_discount,$order_details->extra_amount);

                        $service_category = ServiceCategory::query()->where('id', $order_details->service_cat_id)->first();
                        if ($service_category == Null) {
                            Session::flash('error', __('user_messages.9'));
                            return redirect()->back();
                        }
                        $notificationClss = new NotificationClass();
                        $general_settings = request()->get("general_settings");
                        //auto settle code for admin complete
                        if ($update_status == 9)
                        {
                            if ($general_settings->auto_settle_wallet == 1 && $order_details->provider_pay_settle_status != 1) {
                                    $provider_id = $order_details->provider_id;
                                    $service_cat_id = $order_details->service_cat_id;
                                    $order_no = $order_details->order_no;
                                    $transaction_type = 0;
                                    $subject = '';
                                    $subject_code = 0;
                                    $wallet_provider_type = 3;

                                    if ($order_details->payment_type == 1) {
                                        //if payment type is cash
                                        $transaction_type = 2;
                                        $add_update_wallet_bal = $order_details->admin_commission + $order_details->tax;
                                        $subject = "Debited By admin - " . $service_category->name . " Booking # " . $order_details->order_no;
                                        $subject_code = 13;
                                    } elseif ($order_details->payment_type == 2 || $order_details->payment_type == 3) {
                                        //if payment type is not cash then
                                        $transaction_type = 1;
                                        $add_update_wallet_bal = ($order_details->provider_amount + $order_details->tip);
                                        $subject = "Credited by Admin for your earning - " . $service_category->name . " Booking # " . $order_details->order_no;
                                        $subject_code = 14;
                                    }

                                    $provider_wallet_update = $this->notificationClass->providerUpdateWalletBalance($provider_id, $wallet_provider_type, $transaction_type, $add_update_wallet_bal, $subject, $subject_code, $order_no);
                                    if ($provider_wallet_update) {
                                        $order_details->provider_pay_settle_status = 1;
                                        $order_details->save();
                                    }
                            }
                        }
                        //auto settle code for admin end

                        $order_details->status = $update_status;
                        $order_details->payment_status = 1;
                        $order_details->completed_by = 1;
                        $order_details->save();

                        $provider_details = Provider::query()->where('id',$order_details->provider_id)->first();

                        if ($order_details->promo_code > 0){
                            $used_promocode_details = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                            if ($used_promocode_details != Null) {
                                $used_promocode_details->status = 1;
                                $used_promocode_details->save();
                            }
                        }

                        ProviderAcceptedPackageTime::query()->where('provider_id', '=', $order_details->provider_id)->where('order_id', '=', $order_details->id)->delete();

                        $other_provider_details = OtherServiceProviderDetails::query()->where('provider_id', $order_details->provider_id)->first();
                        if ($other_provider_details != Null) {
                            $other_provider_details->total_completed_order = $other_provider_details->total_completed_order + 1;
                            $other_provider_details->save();
                        }
                        $service_category = ServiceCategory::query()->where('id', $order_details->service_cat_id)->first();
                        //deleting chat from firebase
                        (new FirebaseService())->deleteOrderChat($order_details->order_no, $order_details->id);
                        if ($user_details != Null) {
                            $this->notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status, $user_details->language, 1);
                        }
                        if ($provider_details != null){
                            $this->notificationClass->providerOrderPackageNotification($order_details->id,$provider_details->device_token,$order_details->status,$provider_details->language,0);
                        }
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Data not found'
                        ]);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data not found'
                    ]);
                }
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ]);
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function getOtherServiceEarningReport(Request $request, $slug)
    {
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return redirect()->back();
        }
        $user_list = UserPackageBooking::query()->select('users.id',
            'users.first_name', 'users.last_name', 'users.contact_number')
            ->join('users', 'users.id', '=', 'user_service_package_booking.user_id')
            ->where('user_service_package_booking.status', '=', 9)
            ->whereNull('users.deleted_at')
            ->where('user_service_package_booking.service_cat_id', '=', $service_category->id);
        $admin_role = request()->get("admin_role");
        if ($admin_role == 4) {
            $admin_city_id = request()->get("admin_city_id");
            $user_list->where('users.area_id', $admin_city_id);
        }
        $user_list = $user_list->groupBy('user_service_package_booking.user_id')
            ->get();
        $provider_list = UserPackageBooking::query()->select('providers.id',
            DB::raw("providers.first_name as name"), 'providers.contact_number', 'user_service_package_booking.provider_id')
            ->join('providers', 'providers.id', '=', 'user_service_package_booking.provider_id')
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->where('user_service_package_booking.status', '=', 9)
            ->whereNull('providers.deleted_at')
            ->where('user_service_package_booking.service_cat_id', '=', $service_category->id);
        $admin_role = request()->get("admin_role");
        if ($admin_role == 4) {
            $admin_city_id = request()->get("admin_city_id");
            $provider_list->where('user_service_package_booking.area_id', $admin_city_id);
        }
        $provider_list = $provider_list->groupBy('user_service_package_booking.provider_id')->get();


        $package_order_list = UserPackageBooking::query()->select('user_service_package_booking.id', 'user_service_package_booking.order_no', 'user_service_package_booking.order_type', 'user_service_package_booking.service_date_time', 'user_service_package_booking.service_time',
            'user_service_package_booking.user_name', 'user_service_package_booking.provider_name', 'user_service_package_booking.total_item_cost', 'user_service_package_booking.tax', 'user_service_package_booking.tip', 'user_service_package_booking.total_pay', 'user_service_package_booking.provider_amount', 'user_service_package_booking.refer_discount', 'user_service_package_booking.created_at',
            'user_service_package_booking.user_id', 'user_service_package_booking.promo_code','user_service_package_booking.extra_amount', 'user_service_package_booking.admin_commission','user_service_package_booking.refer_discount',
            'user_service_package_booking.provider_pay_settle_status', 'user_service_package_booking.provider_id', 'user_service_package_booking.payment_type','user_service_package_booking.service_date_time',
            'used_promocode_details.discount_amount')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', 'user_service_package_booking.promo_code')
            ->where('user_service_package_booking.status', 9)
            ->where('user_service_package_booking.service_cat_id', $service_category->id);
        $admin_role = request()->get("admin_role");
        if ($admin_role == 4) {
            $admin_city_id = request()->get("admin_city_id");
            $package_order_list->where('user_service_package_booking.area_id', $admin_city_id);
        }
        $from_date = Date('Y-m-d');
        $to_date = Date('Y-m-d');
        $from = Date('Y-m-d', strtotime($from_date)) . " 00:00:00";
        $to = Date('Y-m-d', strtotime($to_date)) . " 23:59:59";

        $package_order_list->whereDate('user_service_package_booking.service_date_time', '>=', $from);
        $package_order_list->whereDate('user_service_package_booking.service_date_time', '<=', $to);

        $package_order_list = $package_order_list->orderBy('user_service_package_booking.id', "desc")->get();
        $used_promo_cods = [];
        $collect_payment = [];
        $pay_payment = [];

        $total_amount = $package_order_list->sum('total_pay');
        $site_commission = $package_order_list->sum('admin_commission');
        $provider_earning = $package_order_list->sum('provider_amount');
        $total_discount = $package_order_list->sum('discount_amount');
        $refer_discount = $package_order_list->sum('refer_discount');

//        $collect_from_provider = ($package_order_list->where('payment_type', 1)->sum('total_pay')) - ($package_order_list->where('payment_type', 1)->sum('provider_amount'));
        $collect_from_provider = $package_order_list->where('payment_type', 1)->sum('total_pay') - $package_order_list->where('payment_type', 1)->sum('provider_amount');
        $collect_from_provider_total = $package_order_list->where('payment_type', 1)->sum('total_pay');

        $total_outstanding = $provider_earning - $collect_from_provider_total;
        $total_outstanding = $provider_earning - $collect_from_provider_total;
        $total_provider_outstanding_amount = number_format($total_outstanding, 2);

        $view = view('admin.pages.other_services.earning_report.admin_earning_report', compact('slug', 'service_category', 'user_list', 'provider_list', 'from_date', 'to_date','package_order_list', 'total_amount', 'site_commission', 'provider_earning', 'collect_from_provider', 'total_discount', 'total_provider_outstanding_amount', 'used_promo_cods', 'collect_payment', 'pay_payment','refer_discount'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postOtherServiceEarningReport(Request $request, $slug)
    {
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return redirect()->back();
        }
        $user_list = UserPackageBooking::query()->select('users.id',
            'users.first_name', 'users.last_name', 'users.contact_number')
            ->join('users', 'users.id', '=', 'user_service_package_booking.user_id')
            ->where('user_service_package_booking.status', '=', 9)
            ->where('user_service_package_booking.service_cat_id', '=', $service_category->id)
            ->whereNull('users.deleted_at')
            ->groupBy('user_service_package_booking.user_id')
            ->get();
        $provider_list = UserPackageBooking::query()->select('providers.id',
            DB::raw("CONCAT(COALESCE(providers.first_name,''),' ',COALESCE(providers.last_name,'')) as name"), 'providers.contact_number', 'user_service_package_booking.provider_id')
//            ->join('provider_services', 'provider_services.id', '=', 'user_service_package_booking.provider_id')
            ->join('providers', 'providers.id', '=', 'user_service_package_booking.provider_id')
            ->where('user_service_package_booking.status', '=', 9)
            ->where('user_service_package_booking.service_cat_id', '=', $service_category->id)
            ->whereNull('providers.deleted_at')
            ->groupBy('user_service_package_booking.provider_id')
            ->get();
        $from_date = ($request['from_date'] != Null && $request['to_date'] != Null) ? $request['from_date'] : Null;
        $to_date = ($request['from_date'] != Null && $request['to_date'] != Null) ? $request['to_date'] : Null;
        $provider = $request['provider'] != Null ? $request['provider'] : Null;
        $user = $request['user'] != Null ? $request['user'] : Null;
        \Log::info('===============user==========');
        \Log::info($user);
        $payment_type = $request['payment_type'] != Null ? $request['payment_type'] : Null;
        $provider_pay_type = $request['provider_pay_type'] != Null ? $request['provider_pay_type'] : Null;

        $package_order_list = UserPackageBooking::query()->select('user_service_package_booking.id', 'user_service_package_booking.order_no', 'user_service_package_booking.order_type', 'user_service_package_booking.service_date_time', 'user_service_package_booking.service_time',
            'user_service_package_booking.user_name', 'user_service_package_booking.provider_name', 'user_service_package_booking.total_item_cost', 'user_service_package_booking.tax', 'user_service_package_booking.tip', 'user_service_package_booking.total_pay', 'user_service_package_booking.provider_amount', 'user_service_package_booking.refer_discount', 'user_service_package_booking.created_at',
            'user_service_package_booking.user_id', 'user_service_package_booking.promo_code','user_service_package_booking.extra_amount', 'user_service_package_booking.admin_commission','user_service_package_booking.refer_discount',
            'user_service_package_booking.provider_pay_settle_status', 'user_service_package_booking.provider_id', 'user_service_package_booking.payment_type','user_service_package_booking.service_date_time',
            'used_promocode_details.discount_amount')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', 'user_service_package_booking.promo_code')
            ->where('user_service_package_booking.status', 9)
            ->where('user_service_package_booking.service_cat_id', $service_category->id);
        if ($from_date != Null && $to_date != Null) {
            $from = Date('Y-m-d', strtotime($from_date)) . " 00:00:00";
            $to = Date('Y-m-d', strtotime($to_date)) . " 23:59:59";
            $package_order_list->whereDate('service_date_time', '>=', $from);
            $package_order_list->whereDate('service_date_time', '<=', $to);
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
        if ($provider_pay_type != Null) {
            $package_order_list->where('user_service_package_booking.provider_pay_settle_status', $provider_pay_type);
        }
        $package_order_list = $package_order_list->orderBy('user_service_package_booking.id', "desc")->get();
        $used_promo_cods = [];
        $promo_total=0;
        foreach($package_order_list as $order){
            $promo = UsedPromocodeDetails::query()->where('id',$order->promo_code)->first();
            if($promo != Null){
                $used_promo_cods[$order->id] = $promo->discount_amount;
                $promo_total=$promo_total+$promo->discount_amount;

            }
        }
        $total_amount = $package_order_list->sum('total_pay');
        $site_commission = $package_order_list->sum('admin_commission');
        $provider_earning = $package_order_list->sum('provider_amount');
        $total_discount = $package_order_list->sum('discount_amount');
        $refer_discount = $package_order_list->sum('refer_discount');
        $collect_from_provider = ($package_order_list->where('payment_type', 1)->sum('total_pay')) - ($package_order_list->where('payment_type', 1)->sum('provider_amount'));

        $total_outstanding = $provider_earning - $collect_from_provider;
        $total_provider_outstanding_amount = number_format($total_outstanding, 2);

        $used_promo_cods = [];
        $collect_payment = [];
        $pay_payment = [];
        foreach($package_order_list as $order){
            $promo = UsedPromocodeDetails::query()->where('id',$order->promo_code)->first();
            if($promo != Null){
                $used_promo_cods[$order->id] = $promo->discount_amount;
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

//        dd($package_order_list);
        $view = view('admin.pages.other_services.earning_report.admin_earning_report', compact('slug', 'service_category',
            'user_list', 'provider_list', 'from_date', 'to_date', 'provider', 'user', 'payment_type', 'provider_pay_type',
            'package_order_list', 'total_amount', 'site_commission', 'provider_earning', 'collect_from_provider', 'refer_discount','total_discount','promo_total',
            'total_provider_outstanding_amount','used_promo_cods','collect_payment','pay_payment'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postOtherServiceOrderPaymentSettle(Request $request, $slug)
    {
        if($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Service Category Not Found!'
            ]);
        }
        if ($request->get('order_id') != Null) {
            $order_id_settle = $request->get('order_id');
            foreach ($order_id_settle as $key => $order_id) {
//                dd($order_id);
                $order_details = UserPackageBooking::query()->where('id', $order_id)->first();
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
//        return redirect()->back();
    }

    public function getOtherServiceSetting(Request $request, $slug)
    {
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return redirect()->back();
        }
        $service_settings = ServiceSettings::where('service_cat_id', $service_category->id)->first();
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.other_service_setting', compact('slug', 'service_category', 'service_settings'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.other_service_setting', compact('slug', 'service_category', 'service_settings'));
    }

    public function postUpdateOtherServiceSetting(Request $request)
    {
        if($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $service_category = $request->get('service_cat_id');
        $service_category = ServiceCategory::where('id', $service_category)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        $id = $request->get('id');
        if ($id != Null) {
            Session::flash('success', 'Service Settings Updated Successfully!');
            $service_settings = ServiceSettings::where('id', $id)->first();
        } else {
            Session::flash('success', 'Service Settings Added Successfully!');
            $service_settings = new ServiceSettings();
            $service_settings->service_cat_id = $service_category->id;
        }
//        $service_settings->provider_search_radius = $request->get('provider_search_radius');
        $service_settings->tax = $request->get('tax');
        $service_settings->admin_commission = $request->get('admin_commission');
        $service_settings->cancel_charge = $request->get('cancel_charge');
//        $service_settings->delivery_charge = $request->get('delivery_charge');
        $service_settings->save();
        $slug = $service_category->slug;
        return redirect()->route('get:admin:other_service_setting', compact('slug'));
    }

    //code for dispaly service slider display 28-01-2022
    public function getOnDemandServiceSliderList(Request $request ,$slug){

        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }

        $service_slider_list = ServiceSliderBanner::query()->select(
            'other_service_sub_category.name as on_demand_category_name',
            'service_slider_banner.banner_image',
            'service_slider_banner.id',
            'service_slider_banner.status'
        )
            ->join('other_service_sub_category','other_service_sub_category.id','service_slider_banner.ondemand_cat_id')
            ->where('service_slider_banner.service_cat_id','=',$service_category->id)
            ->where('service_slider_banner.type','=',2)
            ->get();

        $view = view('admin.pages.other_services.service_slider.service_slider_list', compact('service_slider_list','slug'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponse($view);
        }
        return $view;
    }

    public function getAddOnDemandServiceSlider(Request $request ,$slug){


        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        $service_slider_store_id = ServiceSliderBanner::query()
            ->where('service_cat_id','=',$service_category->id)
            ->where('type','=',2)
            ->pluck('ondemand_cat_id');

        $ondemand_category_list = OtherServiceCategory::query()->select(
            'other_service_sub_category.id as id',
            'other_service_sub_category.service_cat_id as service_cat_id',
            'other_service_sub_category.name as ondemand_category_name'
        )
            ->where('other_service_sub_category.service_cat_id','=',$service_category->id)
            ->where('other_service_sub_category.status','=',1)
            ->whereNotIn('other_service_sub_category.id',$service_slider_store_id)
            ->get();

        $view = view('admin.pages.other_services.service_slider.service_slider', compact('ondemand_category_list','slug'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponse($view);
        }
        return $view;
    }

    public function postUpdateOnDemandServiceSlider(Request $request ,$slug){

        if($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }

        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }

        $banner_id = $request->get('banner_id');

        if($banner_id != Null){
            $banner = ServiceSliderBanner::query()->where('id',$banner_id)
                        ->where('service_cat_id','=',$service_category->id)
                        ->where('type','=',2)
                        ->first();
        } else{
            $banner = new ServiceSliderBanner();
            $banner->status = 1;
        }

        $banner->service_cat_id = $service_category->id;
        $banner->type = 2;
        $banner->ondemand_cat_id = $request->get('ondemand_category_id');
        $banner->save();

        if($request->hasFile('image') != Null){
            if (\File::exists(public_path('/assets/images/service-slider-banner/' . $banner->banner_image))) {
                \File::delete(public_path('/assets/images/service-slider-banner/' . $banner->banner_image));
            }
            $destinationPath = public_path('/assets/images/service-slider-banner/');
            $file = $request->file('image');
            $img = Image::read($file->getRealPath());
            $img->orient();
            $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
            $img->resize(600, 240
                , function ($constraint) {
                $constraint->aspectRatio();
            }
            )->save($destinationPath . $file_new);
            $banner->banner_image = $file_new;
         }
        $banner->save();

        Session::flash('success', 'Slider added successfully!');
        return redirect()->route('get:admin:on_demand_service_slider_list',$slug);
    }

    public function getEditOnDemandServiceSlider(Request $request ,$slug ,$id){
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }

        $service_slider_banner = ServiceSliderBanner::query()
            ->where('id','=',$id)
            ->where('service_cat_id','=',$service_category->id)
            ->where('type','=',2)
            ->first();
        if ($service_slider_banner == Null) {
            Session::flash('error', 'Service Slider not found!');
            return redirect()->back();
        }

        $service_slider_store_id = ServiceSliderBanner::query()
            ->where('service_cat_id','=',$service_category->id)
            ->where('type','=',2)
            ->whereNotIn('id',array($id))
            ->pluck('ondemand_cat_id');

        $ondemand_category_list = OtherServiceCategory::query()->select(
            'other_service_sub_category.id as id',
            'other_service_sub_category.service_cat_id as service_cat_id',
            'other_service_sub_category.name as ondemand_category_name'
        )
            ->where('other_service_sub_category.service_cat_id','=',$service_category->id)
            ->where('other_service_sub_category.status','=',1)
            ->whereNotIn('other_service_sub_category.id',$service_slider_store_id)
            ->get();


        $view = view('admin.pages.other_services.service_slider.service_slider', compact('ondemand_category_list','service_slider_banner','slug'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponse($view);
        }
        return $view;
    }

    public function postUpdateOnDemandServiceSliderStatus(Request $request){
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Service Slider not found'
            ]);
        }
        $service_slider = ServiceSliderBanner::query()
                            ->where('id','=', $id)
                            ->where('type', '=',2)
                            ->first();
        if ($service_slider == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Home Slider not found'
            ]);
        }
        if ($service_slider->status == 1) {
            $service_slider->status = 0;
            $service_slider->save();
        } else {
            $service_slider->status = 1;
            $service_slider->save();
        }
        return response()->json([
            'success' => true,
            'status' => $service_slider->status
        ]);
    }

    public function postDeleteOnDemandServiceSlider(Request $request){
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Service Slider not found'
            ]);
        }
        $service_slider = ServiceSliderBanner::query()
                            ->where('id','=', $id)
                            ->where('type', '=',2)
                            ->first();
        if ($service_slider == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Service Slider not found'
            ]);
        }
        if (\File::exists(public_path('/assets/images/service-slider-banner/' . $service_slider->banner_image))) {
            \File::delete(public_path('/assets/images/service-slider-banner/' . $service_slider->banner_image));
        }
        $service_slider->delete();
        return response()->json([
            'success' => true
        ]);
    }

    public function getOtherServicesChangeProviderSponsor(Request $request)
    {
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        $providerid = $request->get('providerid');
        $status = $request->get('status');
        $slug = $request->get('slug');
        if ($id == Null || $slug == Null) {
            Session::flash('error', 'something went to wrong!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $service_category = ServiceCategory::where('slug','=', $slug)->first();
        if ($service_category == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider_service = ProviderServices::query()->where('id','=', $id)->where('service_cat_id', $service_category->id)->first();
//        $provider_service = ProviderServices::query()->where('provider_id', $providerid)->where('service_cat_id', $service_category->id)->get();
        if ($provider_service == Null) {
            Session::flash('error', 'provider Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider_service->is_sponsor =$status;
        $provider_service->save();
        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    //for Admin Provider Wallet Transaction
    public function postAdminProviderWalletTransaction(Request $request, $id)
    {
        if (!is_numeric($id) || $id == Null) {
            Session::flash('error', 'Provider wallet transaction Not found!');
            return redirect()->back();
        }
        $user_details = Provider::select('id', 'first_name', 'email', 'contact_number', 'status','provider_type')
            ->where('id', $id)
            ->first();
        if ($user_details == Null) {
            Session::flash('error', 'Provider wallet transaction Not found!');
            return redirect()->back();
        }

        $wallet_transaction_list = UserWalletTransaction::select(
            'id', 'amount', 'subject', 'remaining_balance', 'created_at',
            DB::raw("(CASE WHEN transaction_type = 1 THEN 'Credit' ELSE (CASE WHEN transaction_type = 2 THEN 'Debit' ELSE '----' END) END) as transaction_type")
        )->where('user_id', $id)->orderBy('id', 'desc')->where('wallet_provider_type','=',$user_details->provider_type)->get();

        $view = view('admin.pages.super_admin.provider.wallet_transaction', compact('user_details', 'wallet_transaction_list'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }


    public function postAdminUpdateProviderWalletTransaction(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric",
                "wallet_amount" => "required",
                "choose_option" => "required",
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'something went wrong'
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider = Provider::query()->select('id','currency','provider_type')
            ->where('id', $provider_id)->first();
        if ($provider == Null) {
            return response()->json([
                'success' => false,
                'message' => 'something went wrong'
            ]);
        }

        $amount_to_default = round($request->get('wallet_amount'), 2);
        try {
            $get_last_transaction = UserWalletTransaction::query()
                ->where('user_id', "=", $provider_id)
                ->orderBy('id', 'desc')
                ->first();
            if ($get_last_transaction != Null) {
                $last_amount = $get_last_transaction->remaining_balance;
            } else {
                $last_amount = 0;
            }
        } catch (\Exception $e) {
            $last_amount = 0;
        }

        if ($request->get('choose_option') == 1) {
            $add_balance = new UserWalletTransaction();
            $add_balance->user_id = $provider_id;
            $add_balance->wallet_provider_type = $provider->provider_type;
            $add_balance->transaction_type = 1;
            $add_balance->amount = $amount_to_default;
            $add_balance->subject = "credit by Admin";
            $add_balance->subject_code = 6;
            $add_balance->remaining_balance = floatval($last_amount + $amount_to_default);
            $add_balance->save();
        } else {
            $add_balance = new UserWalletTransaction();
            $add_balance->user_id = $provider_id;
            $add_balance->wallet_provider_type = $provider->provider_type;
            $add_balance->transaction_type = 2;
            $add_balance->amount = $amount_to_default;
            $add_balance->subject = "debit by Admin";
            $add_balance->subject_code = 13;
            $add_balance->remaining_balance = floatval($last_amount - $amount_to_default);
            $add_balance->save();
        }

        $last_amount = $add_balance->remaining_balance;

        return response()->json([
            'success' => true,
            'message' => 'success',
            'user_id' => $provider->id,
            'last_amount' => $last_amount
        ]);
    }

    //get Other Service Cash Out List
    public function getOtherServiceCashOutList(Request $request) {
        $view = view('admin.pages.other_services.cash_outs.other_service_cash_out_list');
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //fetching the other Service Cash Out List
    public function getOtherServiceCashOutListNew(Request $request){
        $status_check = $request->get('status_check');

        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page
        $rowperpage = (isset($rowperpage) && $rowperpage > 0) ? $rowperpage : 25;
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value

        $cashout_lists_check = CashOut::query()
            ->select('cash_out.id', 'cash_out.user_name','cash_out.amount','cash_out.status','cash_out.user_id','provider_bank_details.bank_name','provider_bank_details.account_number','provider_bank_details.payment_email')
            ->leftJoin('provider_bank_details','provider_bank_details.provider_id','=','cash_out.user_id');
        $cashout_list_data = $cashout_lists_check;
        $totalRecords = $cashout_lists_check->count();
        if($searchValue != ""){
            $totalRecordswithFilter = $cashout_lists_check->where(function($query) use ($searchValue){
                $query->orWhere('user_name', 'like', '%' .$searchValue . '%')
                    ->orWhere('amount', 'like', '%' .$searchValue . '%');
            })->count();
        } else{
            $totalRecordswithFilter = $cashout_lists_check->count();
        }

        $record = $cashout_list_data;
        if($searchValue != ""){
            $record = $record->where(function($query) use ($searchValue){
                $query->orWhere('user_name', 'like', '%' .$searchValue . '%')
                    ->orWhere('amount', 'like', '%' .$searchValue . '%');
            });
        }

        if ($columnName != 'no'){
            $record = $record->orderBy($columnName, $columnSortOrder);
        }else{
            $record = $record->orderBy('id', 'desc');
        }

        $records = $record->skip($start)
            ->take($rowperpage)
            ->get();

        $i = 1;
        $data_arr = [];
        foreach ($records as $record){
            if($record->status == 0){
                $cashout_status = "pending";
            } else if($record->status == 1){
                $cashout_status = "approved";
            } else if($record->status == 2){
                $cashout_status = "rejected";
            }
            $cashout_status_html = '<span class="'.$cashout_status.'" id="status_' . $record->id . '">'.$cashout_status.'</span>';
            $status_html = '';

            // Check if the record's status is not 2
            if ($record->status != 2) {
                // Add approve button if the status is not 1
                if ($record->status != 1) {
                    $status_html .= '
                <a class="render_link" id="approve_remove_' . $record->id . '">
                    <img src="' . asset('/assets/images/template-images/thumbs-up.png') . '"
                         style="width:20px; height: 20px;"
                         data-toggle="tooltip" class="approve"
                         id="' . $record->id . '"
                         data-placement="top" title="Approve">
                </a>';
                }

                // Always add reject button if the status is not 2
                $status_html .= '
                <a class="render_link" id="reject_remove_' . $record->id . '">
                    <img src="' . asset('/assets/images/template-images/thumb-down.png') . '"
                         style="width:20px; height: 20px;"
                         data-toggle="tooltip" class="reject"
                         id="' . $record->id . '"
                         data-placement="top" title="Reject">
                </a>';
                }

            $data_arr[] = array(
                "no" => $i,
                "user_name" => $record->user_name,
                "amount" => '<span class="currency"></span>'.$record->amount,
                "bank_name" => $record->bank_name != null ? $record->bank_name: "N/A",
                "account_number" => $record->account_number ? $record->account_number : "N/A",
                "payment_email" => $record->payment_email ? $record->payment_email : "N/A",
                'status' => '<span class="order-status">'.$cashout_status_html.'</span>',
                'actions'=>$status_html
            );
            $i++;
        }
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
        );
        return json_encode($response);
    }


    //get Ajax Other cash_out Status Change
    public function getUpdateOtherServiceCashOutStatus(Request $request)
    {
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        $request_for = $request->get('request_for');
        if ($id == Null || $request_for == Null) {
            return response()->json([
                'success' => false,
                'message' => __('provider_messages.0')
            ]);
        }

        $cashout_details = CashOut::query()->where('id', $id)->first();
        if ($cashout_details == Null) {
            return response()->json([
                'success' => false,
                'message' => __('provider_messages.0')
            ]);
        }
        $user_details = Provider::query()->where('id', $cashout_details->user_id)->first();
        if ($user_details == Null) {
            return response()->json([
                'success' => false,
                'message' => __('provider_messages.0')
            ]);
        }
        $user_id=$cashout_details->user_id;
        $amount_to_default=$cashout_details->amount;
        if ($request_for == 1) {
            $cashout_details->status = 1;
            $cashout_details->save();
        } elseif ($request_for == 2) {

            //for get wallet Balance
            $last_amount = $this->notificationClass->getWalletBalance($user_id);

            $add_balance = new UserWalletTransaction();
            $add_balance->user_id = $user_id;
            $add_balance->transaction_type = 1;
            $add_balance->wallet_provider_type = 3;
            $add_balance->amount = $amount_to_default;
            $add_balance->subject = "credit by admin for cashout";
            $add_balance->subject_code = 17;
            $add_balance->remaining_balance = floatval($last_amount + $amount_to_default);
            $add_balance->save();

            $cashout_details->status = 2;
            $cashout_details->save();
        } else {
            return response()->json([
                'success' => false,
                'message' => __('provider_messages.0')
            ]);
        }
        $notification_log = $this->notificationClass->ProviderCashOutNotification($user_details->device_token,$user_details->language,$request_for);

        return response()->json([
            'success' => true
        ]);
    }
}
