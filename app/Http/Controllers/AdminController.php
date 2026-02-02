<?php

namespace App\Http\Controllers;

use App\Classes\AdminClass;
use App\Classes\NotificationClass;
use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\EmailTemplatesRequest;
use App\Http\Requests\GeneralSettingsRequest;
use App\Http\Requests\PromocodeDetailsRequest;
use App\Http\Requests\PushNotificationRequest;
use App\Http\Requests\RequiredDocumentsRequest;
use App\Http\Requests\ServiceCategoryRequest;
use App\Http\Requests\SubAdminRequest;
use App\Models\Admin;
use App\Models\AdminCategoryPermission;
use App\Models\AdminModule;
use App\Models\AdminPageAction;
use App\Models\AdminPermission;
use App\Models\AppVersionSetting;
use App\Models\EmailTemplates;
use App\Models\GeneralSettings;
use App\Models\HomePageBanner;
use App\Models\HomepageSpotLight;
use App\Models\LanguageConstant;
use App\Models\LanguageLists;
use App\Models\OtherServiceCategory;
use App\Models\OtherServiceProviderDetails;
use App\Models\PageSettings;
use App\Models\PromocodeDetails;
use App\Models\PushNotification;
use App\Models\RestrictedArea;
use App\Models\User;
use App\Models\UserPackageBooking;
use App\Models\UserPackageBookingQuantity;
use App\Models\UserRatings;
use App\Models\UserWalletTransaction;
use App\Models\Provider;
use App\Models\ProviderDocuments;
use App\Models\ProviderServices;
use App\Models\RequiredDocuments;
use App\Models\ServiceCategory;
use App\Models\WorldCountry;
use App\Models\WorldCurrency;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;

class AdminController extends Controller
{
    private $adminClass;
    private $notificationClass;
    private $on_demand_service_id_array;
    private $food_delivery;
    private $is_restricted = 0;
    private $spot_light_array = [2, 3, 4];
    private $on_demand_category_type = [3, 4];

    public function __construct(AdminClass $adminClass, NotificationClass $notificationClass)
    {

        $this->middleware('auth');
        $this->adminClass = $adminClass;
        $this->notificationClass = $notificationClass;
        //        $this->on_demand_service_id_array = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30];
        $service_cat = ServiceCategory::query()->whereIN('category_type', [3, 4])->where('status', 1)->get()->pluck('id')->toArray();
        $this->on_demand_service_id_array = $service_cat;
        $this->on_demand_order_status = ['', 'pending', 'accepted', 'schedule-accepted', 'rejected', 'cancelled', 'ongoing', 'arrived', 'processing', 'completed', 'failed'];
        $this->food_delivery = 5;

        /*if(request()->getHost() == 'fox-jek.startuptrinity.com'){
            $this->is_restricted = 1;
        }*/
        $this->middleware(function ($request, $next) {
            $is_restrict_admin = $request->get('is_restrict_admin');
            $this->is_restricted = $is_restrict_admin;
            return $next($request);
        });
    }

    public function getAdminTest_Mail(Request $request)
    {

        $email = "ftavichalp@gmail.com";

        $data = [
            "mail_type" => 1,
            "user_name" => "Av",
            "content_1" => "",
        ];
        Mail::send('mail_template.wel_come', $data, function ($message) use ($email) {
            $message->to($email);
            $message->subject('Welcome to Mycheckout App');
        });
        if (Mail::failures()) {
            return dd('Sorry! Please try again latter');
        } else {
            return dd('Great! Successfully send in your mail');
        }
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.dashboard')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.dashboard');
        //        return view('home');
    }

    //dashboard
    public function getAdminDashboard(Request $request)
    {
        if (Auth::guard("admin")->user()->roles == 1 || Auth::guard("admin")->user()->roles == 4) {
            $admin_role = request()->get("admin_role");
            $admin_city_id = request()->get("admin_city_id");
            $date = date('Y-m-d');

            //total completed order
            $ondemand_completed = UserPackageBooking::query()->where('status', 9)->whereDate('service_date_time', '=', $date)->where('payment_status', '=', 1);
            if ($admin_role == 4) {
                $ondemand_completed->where('area_id', $admin_city_id);
            }
            $ondemand_completed = $ondemand_completed->count();
            $total_completed_order = $ondemand_completed;


            //total cancelled order
            $ondemand_cancelled = UserPackageBooking::query()->whereIn('status', [4, 5])->whereDate('service_date_time', '=', $date);
            if ($admin_role == 4) {
                $ondemand_cancelled->where('area_id', $admin_city_id);
            }
            $ondemand_cancelled = $ondemand_cancelled->count();
            $total_cancelled_order = $ondemand_cancelled;

            //total order
            $ondemand_total_order = UserPackageBooking::query()->whereNotIn('status', [10])->whereDate('service_date_time', '=', $date);
            if ($admin_role == 4) {
                $ondemand_total_order->where('area_id', $admin_city_id);
            }
            $ondemand_total_order = $ondemand_total_order->count();
            $total_order = $ondemand_total_order;

            //total revenue order
            $ondemand_revenue = UserPackageBooking::query()->where('status', 9)->whereDate('service_date_time', '=', $date)->where('payment_status', '=', 1);
            if ($admin_role == 4) {
                $ondemand_revenue->where('area_id', $admin_city_id);
            }
            $ondemand_rev = $ondemand_revenue->sum('admin_commission');


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

            $service_category = ServiceCategory::query()->where('status', 1);
            if (request()->get("is_all_service") == 1) {
                $service_category->whereIn("id", request()->get("admin_category_id_list"));
            }
            $service_category = $service_category->get();
            $order_count = [];

            foreach ($service_category as $key => $category) {
                $get_other_count = UserPackageBooking::query()->whereDate('service_date_time', '>=', $date);
                if ($admin_role == 4) {
                    $get_other_count->where('area_id', $admin_city_id);
                }
                if (in_array($category->category_type, [3, 4])) {
                    $order_count[$category->id] = $get_other_count->where('service_cat_id', $category->id)->count();
                } else {
                    $order_count[$category->id] = 0;
                }
            }
            $view = view('admin.pages.super_admin.dashboard', compact('service_category', 'order_count', 'total_revenue', 'total_completed_order', 'total_cancelled_order', 'total_order'));
            if ($request->ajax()) {
                $view = $view->renderSections();
                return $this->adminClass->renderingResponce($view);
            }
            return $view;
        } elseif (Auth::guard("admin")->user()->roles == 3) {
            return redirect()->route('get:account:dashboard');
        } elseif (Auth::guard("admin")->user()->roles == 2) {
            return redirect()->route('get:dispatcher:manual_ride_booking');
        } else {
            Auth::guard('admin')->logout();
            return redirect()->route('get:admin:login');
        }
    }

    //service category list
    public function getServiceCategoryList(Request $request)
    {
        $service_categories = ServiceCategory::select('id', 'name', 'slug', 'icon_name', 'display_order', 'category_type', 'status')->get();
        if ($service_categories != Null) {
            $view = view('admin.pages.super_admin.service_category.manage', compact('service_categories'));
        } else {
            $view = view('admin.pages.super_admin.service_category.manage');
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //delete service category
    public function getServiceCategoryChangeStatus(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $service_category = ServiceCategory::where('id', $id)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }

        if (in_array($service_category->category_type, [3, 4, 6])) {
            $service_cat_array = ServiceCategory::query()->whereIN('category_type', [3, 4])->get()->pluck('id')->toArray();
            $provider_service = array_merge($service_cat_array);
            \Log::info('provider_service');
            \Log::info($provider_service);
            if (in_array($service_category->id, $provider_service)) {
                \Log::info('in_matching_array_condition');
                //now check sub category available or not and active
                $active_sub_cateogry = OtherServiceCategory::query()->where('service_cat_id', $service_category->id)->where('status', '=', 1)->first();
                \Log::info('active_sub_category');
                \Log::info($active_sub_cateogry);
                if ($active_sub_cateogry == Null) {
                    \Log::info('active_sub_category_null');
                    //                    Session::flash('error', 'Sorry you cannot Enabled Service.Service category have not any sub category or all sub category are disabled!');
                    //not required but check for existing services who have no category
                    $service_category->status = 0;
                    $service_category->save();
                    //End not required but check for existing services who have no category

                    return response()->json([
                        'success' => false,
                        'status' => $service_category->status,
                        'message' => 'Sorry you cannot Enabled Service.Service category have not any sub category or all sub category are disabled!'
                    ]);
                }
            }
        }

        if ($service_category->status == 1) {
            $service_category->status = 0;
            $service_category->save();
            $message = $service_category->name . ' Service disabled successfully!';
        } else {
            $service_category->status = 1;
            $service_category->save();
            $message = $service_category->name . ' Service Enable successfully!';
        }
        return response()->json([
            'success' => true,
            'status' => $service_category->status,
            'message' => $message
        ]);
    }

    //add service category form
    public function getAddServiceCategory(Request $request)
    {
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.all_service_category.form')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.all_service_category.form');
    }

    //edit service category form
    public function getEditServiceCategory($slug, Request $request)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
        $language_lists = LanguageLists::query()->where('status', '=', '1')->get();
        if ($service_category != Null) {
            if ($request->ajax()) {
                $view = view('admin.pages.super_admin.all_service_category.form', compact('service_category', 'language_lists'))->renderSections();
                return $this->adminClass->renderingResponce($view);
            }
            return view('admin.pages.super_admin.all_service_category.form', compact('service_category', 'language_lists'));
        } else {
            Session::flash('error', 'Service Category Not Found!');
            return redirect()->back();
        }
    }

    //update or store service category
    public function postUpdateServiceCategory(ServiceCategoryRequest $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        //        dd("test");
        $id = $request->get('id');
        if ($id != Null) {
            $service_category = ServiceCategory::query()->where('id', '=', $request->get('id'))->first();
        } else {
            $service_category = new ServiceCategory();
        }
        $service_category->name = $request->get('name');

        try {
            $language_list = LanguageLists::query()->select(
                'language_name as name',
                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as category_col_name")
            )->where('status', 1)->get();
            if ($language_list->isNotEmpty()) {
                foreach ($language_list as $key => $language) {
                    if (Schema::hasColumn('service_category', $language->category_col_name)) {
                        $service_category->{$language->category_col_name} = $request->get($language->category_col_name);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::info($e);
        }


        //$service_category->slug = $service_category->slug($request->get('name'));
        if ($request->file('icon')) {
            if (\File::exists(public_path('/assets/images/service-category/' . $service_category->icon_name))) {
                \File::delete(public_path('/assets/images/service-category/' . $service_category->icon_name));
            }
            $filename = date('HisYdm') . '.' . $request->file('icon')->getClientOriginalExtension();
            $request->file('icon')->move(public_path('/assets/images/service-category'), $filename);
            $service_category->icon_name = $filename;
        }
        $service_category->save();
        if ($id != Null) {
            Session::flash('success', 'Service Category Updated successfully!');
        } else {
            Session::flash('success', 'Service Category Added successfully!');
        }

        //        dd($service_category->slug);
        if ($service_category->category_type == 1 || $service_category->category_type == 5) {
            if ($service_category->slug == "coupon-deals") {
                return redirect()->route('get:admin:coupon_deals_service_list');
            } else {
                return redirect()->route('get:admin:transport_service_list');
            }
        } else {
            return redirect()->route('get:admin:store_service_list');
        }
    }

    //delete service category
    public function getDeleteServiceCategory(Request $request)
    {
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $service_category = ServiceCategory::where('id', $id)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if (\File::exists(public_path('/assets/images/service-category/' . $service_category->icon_name))) {
            \File::delete(public_path('/assets/images/service-category/' . $service_category->icon_name));
        }
        $service_category->delete();
        Session::flash('error', 'Service Category remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }


    //world country list
    public function getAdminWorldCountryList(Request $request)
    {
        $country_list = WorldCountry::query()->get();
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.world_country_city.manage', compact('country_list'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.world_country_city.manage', compact('country_list'));
    }

    //form world country
    public function getAdminAddCountry(Request $request)
    {
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.world_country_city.form')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.world_country_city.form');
    }

    //save or update country city name
    public function postUpdateCountryCity()
    {
        return redirect()->back();
    }

    //user list
    public function getAdminUserList(Request $request)
    {
        $user_list = User::query()->select('id', 'first_name', 'last_name', 'email', 'country_code',  'contact_number', 'status', 'rating')
            ->whereNull('users.deleted_at')
            ->orderBy('id', 'desc')
            ->get();

        $user_wallet_balance = [];
        foreach ($user_list as $list) {
            $user_wallet = UserWalletTransaction::query()->select('remaining_balance')->where('user_id', $list->id)->orderBy('id', 'desc')->first();
            if ($user_wallet != Null) {
                $user_wallet_balance[$list->id] = $user_wallet->remaining_balance;
            } else {
                $user_wallet_balance[$list->id] = 0;
            }
        }
        $view = view('admin.pages.super_admin.user.manage', compact('user_list', 'user_wallet_balance'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getAdminUserListNew(Request $request)
    {
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

        $totalRecords = User::select('count(*) as allcount')->whereNull('users.deleted_at')->count();
        if ($searchValue != "") {
            $totalRecordswithFilter = User::select('count(*) as allcount')
                ->whereNull('users.deleted_at')
                ->where('first_name', 'like', '%' . $searchValue . '%')
                ->orWhere('email', 'like', '%' . $searchValue . '%')
                ->orWhere('contact_number', 'like', '%' . $searchValue . '%')
                ->count();
        } else {
            $totalRecordswithFilter = User::select('count(*) as allcount')->whereNull('users.deleted_at')->count();
        }

        $records = User::query()->select(
            'users.*',
            DB::raw("(CASE WHEN user_wallet_transaction.remaining_balance IS NOT NULL THEN user_wallet_transaction.remaining_balance ELSE 0 END) AS wallet_balance")
        )
            ->leftJoin('user_wallet_transaction', function ($query) {
                $query->on('user_wallet_transaction.user_id', '=', 'users.id');
                $query->where('user_wallet_transaction.wallet_provider_type', '=', 0);
                $query->whereRaw('user_wallet_transaction.id IN (select MAX(a2.id) from user_wallet_transaction as a2 join users as u2 on u2.id = a2.user_id group by u2.id)');
            })
            ->whereNull('users.deleted_at')->orderBy($columnName, $columnSortOrder);
        if ($searchValue != "") {
            $records = $records->where('users.first_name', 'like', '%' . $searchValue . '%');
            $records = $records->orWhere('users.email', 'like', '%' . $searchValue . '%');
            $records = $records->orWhere('users.contact_number', 'like', '%' . $searchValue . '%');
        }
        $records = $records
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach ($records as $key => $record) {
            //            $id = $record->id;
            $id = $key + 1 + $start;
            $username = $record->first_name . " " . $record->last_name;
            $email = $record->email;
            $country_code = $record->country_code;
            $contact_number = $record->contact_number;
            $user_app_version = ($record->app_version != Null) ? $record->app_version : 0;

            $user_wallet_balance = $record->wallet_balance;
            $user_wallet_balance_html = '<span id="change_wallet_' . $record->id . '">' . $user_wallet_balance . '</span><a href="' . route('post:admin:customer_wallet_transaction', ['id' => $record->id]) . '" userid="' . $record->id . '" style="margin: 0 7px;">
                            <img src="' . asset('/assets/images/template-images/wallet-history3.png') . '" style="width:25px; height: 25px;" title="Wallet Transaction">
                        </a>
                        <a style="border: 1px solid Green; border-radius: 5px; font-size: 16px; font-weight: bolder; color: green; padding: 0 5px;cursor: pointer" class="md-trigger-1 text-c-orenge"
                              data-modal="modal-3" data-toggle="tooltip" userid="' . $record->id . '"> <i class="fa fa-plus" aria-hidden="true"></i> / <i class="fa fa-minus" aria-hidden="true"></i> </a>';

            //            $user_wallet = UserWalletTransaction::query()->select('remaining_balance')->where('user_id', $record->id)->orderBy('id', 'desc')->first();
            //            if ($user_wallet != Null) {
            //                $user_wallet_balance = $user_wallet->remaining_balance;
            //            } else {
            //                $user_wallet_balance = 0;
            //            }

            $action = '<a  href="' . route('get:admin:edit_user', $record->id) . '" style="margin: 0 7px;">
                            <img src="' . asset('/assets/images/template-images/writing-1.png') . '" style="width:20px; height: 20px;" title="Edit">
                        </a>
                        <a class="delete" userid="' . $record->id . '" style="margin: 0 7px;">
                            <img src="' . asset('/assets/images/template-images/remove-1.png') . '" style="width:20px; height: 20px;" title="Delete">
                        </a>

                        <span title="Change Password"
                              class="md-trigger text-c-orenge"
                              data-modal="modal-2" data-toggle="tooltip"
                              user_name="' . $username . '"
                              userid="' . $record->id . '"
                        >
                        <i class="fa fa-key"></i>
               ';
            $checked = ($record->status == "1") ? "checked" : "";
            $user_Status = ($record->status == "1") ? "Active" : "InActive";
            $status = '<span class="toggle">
                            <label>
                                <input name="status"
                                       class="form-control user"
                                       id="user_id_' . $record->id . '"
                                       user_id="' . $record->id . '"
                                       user_status="' . $record->status . '"
                                       type="checkbox"   ' . $checked . ' >
                                <span class="button-indecator" data-toggle="tooltip"s
                                      data-placement="top"
                                      id="title_status_' . $record->id . '"
                                      title="' . $user_Status . '"></span>
                            </label>
                        </span>';

            $data_arr[] = array(
                "id" => $id,
                "first_name" => $username,
                "email" => User::Email2Stars($email),
                "contact_number" => User::ContactNumber2Stars($country_code . $contact_number),
                "wallet_balance" => $user_wallet_balance_html,
                'status' => $status,
                'user_app_version' => $user_app_version,
                'action' => $action
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
        );

        return json_encode($response);
    }

    //add user form
    public function getAdminAddUser(Request $request)
    {
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.user.form')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.user.form');
    }

    //edit user
    public function getAdminEditUser(Request $request, $id)
    {
        if (!is_numeric($id) || $id == Null) {
            Session::flash('error', 'Customer Not found!');
            return redirect()->back();
        }
        $user_details = User::query()->where('id', '=', $id)->whereNull('users.deleted_at')->first();
        if ($user_details == Null) {
            $view = view('admin.pages.super_admin.user.form');
        } else {
            $view = view('admin.pages.super_admin.user.form', compact('user_details'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //save or update user
    public function postAdminUpdateUser(CustomerStoreRequest $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $user = User::where('id', $request->get('id'))->whereNull('users.deleted_at')->first();
        $msg = "Customer Updated successfully!";
        if ($user == Null) {
            $user = new User();
            $user->verified_at = date('Y-m-d H:i:s');
            $user->password = Hash::make($request->get('password'));
            $msg = "Customer added successfully!";
        } else {
            $user_old_mobile_no = $user->contact_number;
        }
        $user->first_name = ucwords(strtolower(trim($request->get('first_name'))));
        //        $user->last_name = ucwords(strtolower(trim($request->get('last_name'))));
        $user->email = $request->get('email');
        $user->country_code = $request->get('country_code');
        $user->contact_number = trim($request->get('contact_number'));
        $user->login_type = "email";

        if ($request->file('avatar') != Null) {
            if (\File::exists(public_path('/assets/images/profile-images/customer/' . $user->avatar))) {
                \File::delete(public_path('/assets/images/profile-images/customer/' . $user->avatar));
            }
            $file = $request->file('avatar');
            $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path() . '/assets/images/profile-images/customer/', $file_new);
            $user->avatar = $file_new;
        }
        //        $user->gender = $request->get('gender');
        //        $user->status = 1;
        $user->save();

        if (isset($user_old_mobile_no)) {
            if ($user_old_mobile_no != trim($request->get('contact_number'))) {
                $user->generateAccessToken($user->id);
            }
        }
        $user->InviteCode($user->id, $user->first_name);

        Session::flash('success', $msg);
        return redirect()->route('get:admin:user_list');
    }

    //delete user
    public function getAdminDeleteUser(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Customer Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user = User::where('id', $id)->first();
        if ($user == Null) {
            Session::flash('error', 'Customer Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user_running_packages = UserPackageBooking::query()->where('user_id', '=', $id)
            ->where(function ($query) {
                $query->whereNotIn('status', [4, 5, 9, 10])
                    ->orWhere(function ($query2) {
                        $query2->where('status', 9)->where('payment_status', 0);
                    });
            })->count();

        if ($user_running_packages > 0) {
            return response()->json([
                "success" => false,
                "message" => "Sorry, Currently the Orders of this user is running or has a pending payment so you can't delete the account at the time. Try Later!"
            ]);
        }
        //code for default user
        if ($user->fix_user_show == 1) {
            return response()->json([
                "success" => false,
                "message" => "Sorry,you cannot delete Fixed User"
            ]);
        }
        //end code of default user
        if (\File::exists(public_path('/assets/images/profile-images/customer/' . $user->avatar))) {
            \File::delete(public_path('/assets/images/profile-images/customer/' . $user->avatar));
        }
        $user->delete();
        Session::flash('success', 'Customer remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }
    public function getAdminUpdateUserStatus(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user_details = User::query()->where('id', '=', $id)->whereNull('users.deleted_at')->first();

        $user_running_packages = UserPackageBooking::query()
            ->where('user_id', $user_details->id)
            ->whereNotIn('status', [4, 5, 9, 10])
            // ->where('payment_status', 0)
            ->count();

        if ($user_running_packages > 0) {
            return response()->json([
                'success' => false,
                'message' => "Sorry, Currently the Orders of this user is running or has a pending payment so you can not Block the account at the time. Try Later!"
            ]);
        } else {
            if ($user_details == Null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ]);
            }

            if ($user_details->status == 1) {
                $user_details->device_token = null;
                $status = $user_details->status = 0;
                $general_settings = request()->get("general_settings");
                if ($general_settings != Null) {
                    if ($general_settings !=  Null) {
                        if ($general_settings->send_mail == 1) {
                            $user_name = $user_details->first_name . " " . $user_details->last_name;
                            try {
                                $mail_type = "account_blocked_-_customer";
                                $to_mail = $user_details->email;
                                $subject = "Your Account has been Block by Admin";
                                $disp_data = array("##user_name##" => $user_name);
                                $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                            } catch (\Exception $e) {
                            }
                        }
                    }
                }
            } else {
                $status = $user_details->status = 1;
            }
            $user_details->save();
            return response()->json([
                'success' => true,
                'status' => $status,
            ]);
        }
        //        $user_details = User::select('id', 'status')->where('id', '=', $id)->first();

    }

    //user review lists
    public function getAdminUserReviewList(Request $request, $user_id = "0")
    {
        if ($user_id != "") {
            $user_review_lists = UserRatings::query()->select(
                'user_rating.id',
                'user_rating.user_id as  user_id',
                'user_rating.rating',
                'user_rating.comment',
                'user_rating.status as status',
                DB::raw("CONCAT(COALESCE(providers.first_name,''),' ',COALESCE(providers.last_name,'')) as provider_name"),
                'service_category.name as service_name'
            )
                ->Join('providers', 'providers.id', 'provider_id')
                ->Join('users', 'users.id', 'user_id')
                ->leftJoin('provider_services', 'provider_services.provider_id', 'providers.id')
                ->leftJoin('service_category', 'provider_services.service_cat_id', 'service_category.id')
                ->where('user_rating.user_id', $user_id)
                ->whereNull('users.deleted_at')
                ->whereNull('providers.deleted_at')
                //                ->where('user_rating.status',1)
                ->groupBy('user_rating.id')
                ->get();
            $user_details = User::query()->select('first_name', 'last_name')->where('id', $user_id)->first();

            if ($user_review_lists != null) {
                if (count($user_review_lists) > 0) {
                    return view('admin.pages.super_admin.user.user_review', compact('user_review_lists', 'user_details'));
                } else {
                    return redirect()->route('get:admin:user_list')->with('error', 'Sorry, user review not found!!!');
                }
            } else {
                return redirect()->route('get:admin:user_list')->with('error', 'Sorry, user review not found!!!');
            }
        } else {
            return redirect()->route('get:admin:user_list')->with('error', 'Sorry, user review not found!!!');
        }
    }
    public function getAdminUpdateUserReviewStatus(Request $request)
    {
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user_review_details = UserRatings::query()->where('id', '=', $id)->first();
        //        $user_review_details = User::select('id', 'status')->where('id', '=', $id)->first();
        if ($user_review_details == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($user_review_details->status == 1) {
            $status = $user_review_details->status = 0;
        } else {
            $status = $user_review_details->status = 1;
        }
        $user_review_details->save();
        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    public function getAdminDeleteUserReview(Request $request)
    {
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Customer Review Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user_ratting = UserRatings::where('id', $id)->first();
        $user_id = $user_ratting->user_id;
        if ($user_ratting == Null) {
            Session::flash('error', 'Customer Review Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user_ratting->delete();
        $user = User::query()->where('id', $user_id)->whereNull('users.deleted_at')->first();
        if ($user != null) {
            $avg_ratting = UserRatings::query()->where('user_id', $user_id)->average('rating');
            $user->rating = $avg_ratting;
            $user->save();
        }
        Session::flash('success', 'Customer Review remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }


    //required documents list
    public function getRequiredDocumentsList($slug, Request $request)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
        $required_documents_list = RequiredDocuments::select(
            'required_documents.id',
            'required_documents.name as document_name',
            'required_documents.status',
            'service_category.name'
        )
            ->join('service_category', 'service_category.id', '=', 'required_documents.service_cat_id')
            ->get();
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.required-documents.manage', compact('slug', 'required_documents_list'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.required-documents.manage', compact('slug', 'required_documents_list'));
    }

    //get ajax admin required document status change
    public function getAjaxUpdateAdminRequiredDocumentStatus(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $required_document_details = RequiredDocuments::select('id', 'status')->where('id', '=', $id)->first();
        if ($required_document_details == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($required_document_details->status == 1) {
            $status = $required_document_details->status = 0;
        } else {
            $status = $required_document_details->status = 1;
        }
        $required_document_details->save();
        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    //get ajax admin required document status change
    public function getAjaxUpdateAdminApprovedRejectProviderDocument(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        $status = $request->get('status');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider_document_details = ProviderDocuments::query()->where('id', '=', $id)->first();
        if ($provider_document_details == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        //        if ($provider_document_details->status == 1) {
        //            $status = $provider_document_details->status = 0;
        //        } else {
        $provider_document_details->status = $status;
        //        }
        $provider_document_details->save();
        return response()->json([
            'success' => true,
            'status' => $provider_document_details,
        ]);
    }

    //get Admin General Setting
    public function getAdminGeneralSetting(Request $request)
    {
        //        $service_category = ServiceCategory::where('slug', $slug)->first();
        //        $required_documents_list = RequiredDocuments::select('required_documents.id', 'required_documents.name as document_name',
        //            'required_documents.status', 'service_category.name')
        //            ->join('service_category', 'service_category.id', '=', 'required_documents.service_cat_id')
        //            ->get();
        $general_settings = GeneralSettings::query()->first();

        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.general_settings.form', compact('general_settings'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.general_settings.form', compact('general_settings'));
    }

    //save or update general settings
    public function postAdminUpdateGeneralSetting(GeneralSettingsRequest $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $id = $request->get('id');
        if ($id != Null) {
            $general_settings = GeneralSettings::query()->first();
        } else {
            $general_settings = new GeneralSettings();
        }
        $general_settings->website_name = $request->get('website_name');

        if ($request->get('cash_payment') == 0 && $request->get('card_payment') == 0 && $request->get('walletModule') == 0) {

            Session::flash('error', 'Atleast one payment option need to be ON.');
            return redirect()->back();
        }

        if ($request->file('website_logo')) {
            if (\File::exists(public_path('/assets/images/website-logo-icon/' . $general_settings->website_logo))) {
                \File::delete(public_path('/assets/images/website-logo-icon/' . $general_settings->website_logo));
            }
            $file = $request->file('website_logo');
            $file_new = random_int(1, 99) . date('siHYdm') . random_int(1, 99) . '.' . $file->getClientOriginalExtension();
            //            $file_new = 'logo.' . $file->getClientOriginalExtension();
            $file->move(public_path() . '/assets/images/website-logo-icon/', $file_new);
            $general_settings->website_logo = $file_new;
        }

        if ($request->file('website_favicon')) {
            if (\File::exists(public_path('/assets/images/website-logo-icon/' . $general_settings->website_favicon))) {
                \File::delete(public_path('/assets/images/website-logo-icon/' . $general_settings->website_favicon));
            }
            $file = $request->file('website_favicon');
            $file_new = random_int(1, 99) . date('siHYdm') . random_int(1, 99) . '.' . $file->getClientOriginalExtension();
            //            $file_new = 'favicon.' . $file->getClientOriginalExtension();
            $file->move(public_path() . '/assets/images/website-logo-icon/', $file_new);
            $general_settings->website_favicon = $file_new;
        }

        if ($request->get('used_user_discount') > 0 && in_array($request->get('used_user_discount_type'), [1, 2])) {
            $general_settings->used_user_discount = round($request->get('used_user_discount'), 2);
            $general_settings->used_user_discount_type = $request->get('used_user_discount_type');
        } else {
            $general_settings->used_user_discount = 0;
            $general_settings->used_user_discount_type = 0;
        }
        if ($request->get('refer_user_discount') > 0 && in_array($request->get('refer_user_discount_type'), [1, 2])) {
            $general_settings->refer_user_discount = round($request->get('refer_user_discount'), 2);
            $general_settings->refer_user_discount_type = $request->get('refer_user_discount_type');
        } else {
            $general_settings->refer_user_discount = 0;
            $general_settings->refer_user_discount_type = 0;
        }

        $general_settings->address = $request->get('address');
        $general_settings->contact_no = $request->get('contact_no');
        $general_settings->email = $request->get('email');
        $general_settings->send_receive_email = $request->get('send_receive_email');
        $general_settings->copy_right = $request->get('copy_right');

        $general_settings->facebook_link = $request->get('facebook_link');
        $general_settings->instagram_link = $request->get('instagram_link');
        $general_settings->linkedin_link = $request->get('linkedin_link');
        $general_settings->twitter_link = $request->get('twitter_link');

        $general_settings->user_playstore_link = $request->get('user_playstore_link');
        $general_settings->user_appstore_link = $request->get('user_appstore_link');
        $general_settings->driver_delivery_playstore_link = $request->get('driver_delivery_playstore_link');
        $general_settings->driver_delivery_appstore_link = $request->get('driver_delivery_appstore_link');
        $general_settings->provider_playstore_link = $request->get('provider_playstore_link');
        $general_settings->provider_appstore_link = $request->get('provider_appstore_link');
        $general_settings->store_playstore_link = $request->get('store_playstore_link');
        $general_settings->store_appstore_link = $request->get('store_appstore_link');

        //payment methods
        $general_settings->cash_payment = $request->get('cash_payment');
        $general_settings->card_payment = $request->get('card_payment');

        //wallet and auto settle
        $general_settings->wallet_payment = $request->get('walletModule');
        $general_settings->auto_settle_wallet = $request->get('auto_settle_wallet');

        //min & max cash-out
        $general_settings->min_cashout = $request->get('min_cashout');
        $general_settings->max_cashout = $request->get('max_cashout');

        //driver min wallet balance
        $general_settings->provider_min_amount = $request->get('provider_min_amount');

        $general_settings->delivery_commission = $request->get('delivery_commission');
        $general_settings->on_demand_start_service_time = $request->get('on_demand_start_service_time');
        $general_settings->driver_algorithm = $request->get('driver_algorithm');
        $general_settings->user_timeout = $request->get('user_timeout');
        $general_settings->save();
        if ($id != Null) {
            Session::flash('success', 'General Setting Updated successfully!');
        } else {
            Session::flash('success', 'General Setting Added successfully!');
        }
        return redirect()->route('get:admin:general_setting');
        //$sevice_category = ServiceCategory::where('id', $required_document->service_cat_id)->first();
        //return redirect()->route('get:admin:service_cat_required_documents_list', ['service_cat_slug' => $sevice_category->slug]);
    }

    //App Version Setting
    public function getAdminAppVersionSetting(Request $request)
    {

        //User App Setting
        //Start android flutter user app version
        $is_android_flutter_user_app_version_check = 0;
        $android_flutter_user_app_version = AppVersionSetting::query()->where("app_type", "=", 0)->where("app_device_type", "=", 3)->where("forcefully_type", "=", 1)->orderBy("id", "desc")->first();
        if ($android_flutter_user_app_version == Null) {
            $android_flutter_user_app_version = AppVersionSetting::query()->where("app_type", "=", 0)->where("app_device_type", "=", 3)->orderBy("id", "desc")->first();
        } else {
            $is_android_flutter_user_app_version_check = 1;
        }
        $android_flutter_user_app_version_id = 0;
        if ($android_flutter_user_app_version != Null) {
            $android_flutter_user_app_version_id = $android_flutter_user_app_version->id;
        }
        $android_flutter_user_app_version_list = AppVersionSetting::query()->select('id', 'version_code', 'version_name', DB::raw("(CASE WHEN id != 0 THEN (CASE WHEN id = $android_flutter_user_app_version_id THEN 1 ELSE 0 END) ELSE 0 END) as is_selected"))
            ->where("app_type", "=", 0)
            ->where("app_device_type", "=", 3)
            ->get();
        //End android flutter user app version

        //Start ios flutter user app version
        $is_ios_flutter_user_app_version_check = 0;
        $ios_flutter_user_app_version = AppVersionSetting::query()->where("app_type", "=", 0)->where("app_device_type", "=", 4)->where("forcefully_type", "=", 1)->orderBy("id", "desc")->first();
        if ($ios_flutter_user_app_version == Null) {
            $ios_flutter_user_app_version = AppVersionSetting::query()->where("app_type", "=", 0)->where("app_device_type", "=", 4)->orderBy("id", "desc")->first();
        } else {
            $is_ios_flutter_user_app_version_check = 1;
        }
        $ios_flutter_user_app_version_id = 0;
        if ($ios_flutter_user_app_version != Null) {
            $ios_flutter_user_app_version_id = $ios_flutter_user_app_version->id;
        }
        $ios_flutter_user_app_version_list = AppVersionSetting::query()->select('id', 'version_code', 'version_name', DB::raw("(CASE WHEN id != 0 THEN (CASE WHEN id = $ios_flutter_user_app_version_id THEN 1 ELSE 0 END) ELSE 0 END) as is_selected"))
            ->where("app_type", "=", 0)
            ->where("app_device_type", "=", 4)
            ->get();
        //End ios flutter user app version

        //Store App Setting
        //Start android flutter store app version
        $is_android_flutter_store_app_version_check = 0;
        $android_flutter_store_app_version = AppVersionSetting::query()->where("app_type", "=", 1)->where("app_device_type", "=", 3)->where("forcefully_type", "=", 1)->orderBy("id", "desc")->first();
        if ($android_flutter_store_app_version == Null) {
            $android_flutter_store_app_version = AppVersionSetting::query()->where("app_type", "=", 1)->where("app_device_type", "=", 3)->orderBy("id", "desc")->first();
        } else {
            $is_android_flutter_store_app_version_check = 1;
        }
        $android_flutter_store_app_version_id = 0;
        if ($android_flutter_store_app_version != Null) {
            $android_flutter_store_app_version_id = $android_flutter_store_app_version->id;
        }
        $android_flutter_store_app_version_list = AppVersionSetting::query()->select('id', 'version_code', 'version_name', DB::raw("(CASE WHEN id != 0 THEN (CASE WHEN id = $android_flutter_store_app_version_id THEN 1 ELSE 0 END) ELSE 0 END) as is_selected"))
            ->where("app_type", "=", 1)
            ->where("app_device_type", "=", 3)
            ->get();
        //End android flutter store app version
        //Start ios flutter store app version
        $is_ios_flutter_store_app_version_check = 0;
        $ios_flutter_store_app_version = AppVersionSetting::query()->where("app_type", "=", 1)->where("app_device_type", "=", 4)->where("forcefully_type", "=", 1)->orderBy("id", "desc")->first();
        if ($ios_flutter_store_app_version == Null) {
            $ios_flutter_store_app_version = AppVersionSetting::query()->where("app_type", "=", 1)->where("app_device_type", "=", 4)->orderBy("id", "desc")->first();
        } else {
            $is_ios_flutter_store_app_version_check = 1;
        }
        $ios_flutter_store_app_version_id = 0;
        if ($ios_flutter_store_app_version != Null) {
            $ios_flutter_store_app_version_id = $ios_flutter_store_app_version->id;
        }
        $ios_flutter_store_app_version_list = AppVersionSetting::query()->select('id', 'version_code', 'version_name', DB::raw("(CASE WHEN id != 0 THEN (CASE WHEN id = $ios_flutter_store_app_version_id THEN 1 ELSE 0 END) ELSE 0 END) as is_selected"))
            ->where("app_type", "=", 1)
            ->where("app_device_type", "=", 4)
            ->get();
        //End ios flutter store app version

        //Driver App Setting
        //Start android flutter driver app version
        $is_android_flutter_driver_app_version_check = 0;
        $android_flutter_driver_app_version = AppVersionSetting::query()->where("app_type", "=", 2)->where("app_device_type", "=", 3)->where("forcefully_type", "=", 1)->orderBy("id", "desc")->first();
        if ($android_flutter_driver_app_version == Null) {
            $android_flutter_driver_app_version = AppVersionSetting::query()->where("app_type", "=", 2)->where("app_device_type", "=", 3)->orderBy("id", "desc")->first();
        } else {
            $is_android_flutter_driver_app_version_check = 1;
        }
        $android_flutter_driver_app_version_id = 0;
        if ($android_flutter_driver_app_version != Null) {
            $android_flutter_driver_app_version_id = $android_flutter_driver_app_version->id;
        }
        $android_flutter_driver_app_version_list = AppVersionSetting::query()->select('id', 'version_code', 'version_name', DB::raw("(CASE WHEN id != 0 THEN (CASE WHEN id = $android_flutter_driver_app_version_id THEN 1 ELSE 0 END) ELSE 0 END) as is_selected"))
            ->where("app_type", "=", 2)->where("app_device_type", "=", 3)->get();
        //End android flutter driver app version
        //Start ios flutter driver app version
        $is_ios_flutter_driver_app_version_check = 0;
        $ios_flutter_driver_app_version = AppVersionSetting::query()->where("app_type", "=", 2)->where("app_device_type", "=", 4)->where("forcefully_type", "=", 1)->orderBy("id", "desc")->first();
        if ($ios_flutter_driver_app_version == Null) {
            $ios_flutter_driver_app_version = AppVersionSetting::query()->where("app_type", "=", 2)->where("app_device_type", "=", 4)->orderBy("id", "desc")->first();
        } else {
            $is_ios_flutter_driver_app_version_check = 1;
        }
        $ios_flutter_driver_app_version_id = 0;
        if ($ios_flutter_driver_app_version != Null) {
            $ios_flutter_driver_app_version_id = $ios_flutter_driver_app_version->id;
        }
        $ios_flutter_driver_app_version_list = AppVersionSetting::query()->select('id', 'version_code', 'version_name', DB::raw("(CASE WHEN id != 0 THEN (CASE WHEN id = $ios_flutter_driver_app_version_id THEN 1 ELSE 0 END) ELSE 0 END) as is_selected"))
            ->where("app_type", "=", 2)->where("app_device_type", "=", 4)->get();
        //End ios flutter driver app version


        //Provider App Setting
        //Start android flutter provider app version
        $is_android_flutter_provider_app_version_check = 0;
        $android_flutter_provider_app_version = AppVersionSetting::query()->where("app_type", "=", 3)->where("app_device_type", "=", 3)->where("forcefully_type", "=", 1)->orderBy("id", "desc")->first();
        if ($android_flutter_provider_app_version == Null) {
            $android_flutter_provider_app_version = AppVersionSetting::query()->where("app_type", "=", 3)->where("app_device_type", "=", 3)->orderBy("id", "desc")->first();
        } else {
            $is_android_flutter_provider_app_version_check = 1;
        }
        $android_flutter_provider_app_version_id = 0;
        if ($android_flutter_provider_app_version != Null) {
            $android_flutter_provider_app_version_id = $android_flutter_provider_app_version->id;
        }
        $android_flutter_provider_app_version_list = AppVersionSetting::query()->select('id', 'version_code', 'version_name', DB::raw("(CASE WHEN id != 0 THEN (CASE WHEN id = $android_flutter_provider_app_version_id THEN 1 ELSE 0 END) ELSE 0 END) as is_selected"))
            ->where("app_type", "=", 3)->where("app_device_type", "=", 3)->get();
        //End android flutter provider app version
        //Start ios flutter provider app version
        $is_ios_flutter_provider_app_version_check = 0;
        $ios_flutter_provider_app_version = AppVersionSetting::query()->where("app_type", "=", 3)->where("app_device_type", "=", 4)->where("forcefully_type", "=", 1)->orderBy("id", "desc")->first();
        if ($ios_flutter_provider_app_version == Null) {
            $ios_flutter_provider_app_version = AppVersionSetting::query()->where("app_type", "=", 3)->where("app_device_type", "=", 4)->orderBy("id", "desc")->first();
        } else {
            $is_ios_flutter_provider_app_version_check = 1;
        }
        $ios_flutter_provider_app_version_id = 0;
        if ($ios_flutter_provider_app_version != Null) {
            $ios_flutter_provider_app_version_id = $ios_flutter_provider_app_version->id;
        }
        $ios_flutter_provider_app_version_list = AppVersionSetting::query()->select('id', 'version_code', 'version_name', DB::raw("(CASE WHEN id != 0 THEN (CASE WHEN id = $ios_flutter_provider_app_version_id THEN 1 ELSE 0 END) ELSE 0 END) as is_selected"))
            ->where("app_type", "=", 3)->where("app_device_type", "=", 4)->get();
        //End ios flutter provider app version

        $view = view('admin.pages.super_admin.app_version_setting.form', compact(
            'android_flutter_user_app_version_list',
            'is_android_flutter_user_app_version_check',
            'ios_flutter_user_app_version_list',
            'is_ios_flutter_user_app_version_check',
            'android_flutter_store_app_version_list',
            'is_android_flutter_store_app_version_check',
            'ios_flutter_store_app_version_list',
            'is_ios_flutter_store_app_version_check',
            'android_flutter_driver_app_version_list',
            'is_android_flutter_driver_app_version_check',
            'ios_flutter_driver_app_version_list',
            'is_ios_flutter_driver_app_version_check',
            'android_flutter_provider_app_version_list',
            'is_android_flutter_provider_app_version_check',
            'ios_flutter_provider_app_version_list',
            'is_ios_flutter_provider_app_version_check'

        ));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }
    public function postAdminUpdateAppVersionSetting(Request $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }

        //update android flutter user app version
        $android_flutter_user_app_version_id =  $request->get("android_flutter_user_app_version");
        $android_flutter_user_app_version = AppVersionSetting::query()->where("app_type", "=", 0)->where("app_device_type", "=", 3)->where("id", "=", $android_flutter_user_app_version_id)->first();
        if ($android_flutter_user_app_version != Null) {
            $android_flutter_user_app_version->forcefully_type = 0;
            if ($request->get('update_forcefully_android_flutter_user_app') != Null) {
                $android_flutter_user_app_version->forcefully_type = 1;
            }
            $android_flutter_user_app_version->save();
        }

        //update ios flutter user app version
        $ios_flutter_user_app_version_id =  $request->get("ios_flutter_user_app_version");
        $ios_flutter_user_app_version = AppVersionSetting::query()->where("app_type", "=", 0)->where("app_device_type", "=", 4)->where("id", "=", $ios_flutter_user_app_version_id)->first();
        if ($ios_flutter_user_app_version != Null) {
            $ios_flutter_user_app_version->forcefully_type = 0;
            if ($request->get('update_forcefully_ios_flutter_user_app') != Null) {
                $ios_flutter_user_app_version->forcefully_type = 1;
            }
            $ios_flutter_user_app_version->save();
        }

        //update android flutter store app version
        $android_flutter_store_app_version_id =  $request->get("android_flutter_store_app_version");
        $android_flutter_store_app_version = AppVersionSetting::query()->where("app_type", "=", 1)->where("app_device_type", "=", 3)->where("id", "=", $android_flutter_store_app_version_id)->first();
        if ($android_flutter_store_app_version != Null) {
            $android_flutter_store_app_version->forcefully_type = 0;
            if ($request->get('update_forcefully_android_flutter_store_app') != Null) {
                $android_flutter_store_app_version->forcefully_type = 1;
            }
            $android_flutter_store_app_version->save();
        }

        //update ios flutter store app version
        $ios_flutter_store_app_version_id =  $request->get("ios_flutter_store_app_version");
        $ios_flutter_store_app_version = AppVersionSetting::query()->where("app_type", "=", 1)->where("app_device_type", "=", 4)->where("id", "=", $ios_flutter_store_app_version_id)->first();
        if ($ios_flutter_store_app_version != Null) {
            $ios_flutter_store_app_version->forcefully_type = 0;
            if ($request->get('update_forcefully_ios_flutter_store_app') != Null) {
                $ios_flutter_store_app_version->forcefully_type = 1;
            }
            $ios_flutter_store_app_version->save();
        }

        //update android flutter driver app version
        $android_flutter_driver_app_version_id =  $request->get("android_flutter_driver_app_version");
        $android_flutter_driver_app_version = AppVersionSetting::query()->where("app_type", "=", 2)->where("app_device_type", "=", 3)->where("id", "=", $android_flutter_driver_app_version_id)->first();
        if ($android_flutter_driver_app_version != Null) {
            $android_flutter_driver_app_version->forcefully_type = 0;
            if ($request->get('update_forcefully_android_flutter_driver_app') != Null) {
                $android_flutter_driver_app_version->forcefully_type = 1;
            }
            $android_flutter_driver_app_version->save();
        }

        //update ios flutter driver app version
        $ios_flutter_driver_app_version_id =  $request->get("ios_flutter_driver_app_version");
        $ios_flutter_driver_app_version = AppVersionSetting::query()->where("app_type", "=", 2)->where("app_device_type", "=", 4)->where("id", "=", $ios_flutter_driver_app_version_id)->first();
        if ($ios_flutter_driver_app_version != Null) {
            $ios_flutter_driver_app_version->forcefully_type = 0;
            if ($request->get('update_forcefully_ios_flutter_driver_app') != Null) {
                $ios_flutter_driver_app_version->forcefully_type = 1;
            }
            $ios_flutter_driver_app_version->save();
        }

        //update android flutter provider app version
        $android_flutter_provider_app_version_id =  $request->get("android_flutter_provider_app_version");
        $android_flutter_provider_app_version = AppVersionSetting::query()->where("app_type", "=", 3)->where("app_device_type", "=", 3)->where("id", "=", $android_flutter_provider_app_version_id)->first();
        if ($android_flutter_provider_app_version != Null) {
            $android_flutter_provider_app_version->forcefully_type = 0;
            if ($request->get('update_forcefully_android_flutter_provider_app') != Null) {
                $android_flutter_provider_app_version->forcefully_type = 1;
            }
            $android_flutter_provider_app_version->save();
        }

        //update ios flutter provider app version
        $ios_flutter_provider_app_version_id =  $request->get("ios_flutter_provider_app_version");
        $ios_flutter_provider_app_version = AppVersionSetting::query()->where("app_type", "=", 3)->where("app_device_type", "=", 4)->where("id", "=", $ios_flutter_provider_app_version_id)->first();
        if ($ios_flutter_provider_app_version != Null) {
            $ios_flutter_provider_app_version->forcefully_type = 0;
            if ($request->get('update_forcefully_ios_flutter_provider_app') != Null) {
                $ios_flutter_provider_app_version->forcefully_type = 1;
            }
            $ios_flutter_provider_app_version->save();
        }

        Session::flash('success', 'App Version Setting Updated successfully!');
        return redirect()->route('get:admin:app_version_setting');
    }

    //provider list
    public function getAdminProviderList($type_of_cat, Request $request)
    {
        $service_category_list = $this->on_demand_service_id_array;

        $providers = Provider::select(
            'providers.id',
            DB::raw("CONCAT(COALESCE(providers.first_name,''),' ',COALESCE(providers.last_name,'')) as name"),
            'providers.email',
            'providers.country_code',
            'providers.contact_number',
            'service_category.name AS service_name'
        )
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            ->whereIn('provider_services.service_cat_id', $service_category_list)
            ->whereNull('providers.deleted_at')
            ->orderBy('providers.id', 'desc')
            ->get();


        if ($providers->isEmpty()) {
            $view = view('admin.pages.super_admin.provider.manage', compact('type_of_cat'));
        } else {
            $view = view('admin.pages.super_admin.provider.manage', compact('type_of_cat', 'providers'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //add provider services
    public function getAdminAddProviderServices(Request $request, $category_type, $provider_id)
    {

        $providers = Provider::select(
            'providers.id',
            DB::raw("CONCAT(COALESCE(providers.first_name,''),' ',COALESCE(providers.last_name,'')) as name"),
            'providers.email',
            'providers.contact_number'
            //            'other_service_provider_details.rating', 'provider_services.status'
        )
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            //            ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
            //            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            //            ->whereIn('provider_services.service_cat_id', $service_category_list)
            ->where('providers.id', $provider_id)
            ->whereNull('providers.deleted_at')
            ->orderBy('providers.id', 'desc')
            ->first();

        $providers_services = ProviderServices::where('provider_id', $providers->id)->get();

        $service_category_multiple = ServiceCategory::select('id', 'name')->whereIn('category_type', [3, 4])->get();


        //        $providers = Provider::select('providers.id', 'providers.name',  'provider_services.status')
        //            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
        //            ->whereIn('provider_services.service_cat_id', [$service_category])
        //            ->get();
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.provider.form_provider_service', compact('category_type', 'service_category_multiple'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.provider.form_provider_service', compact('category_type', 'service_category_multiple'));
    }

    public function postAdminCustomerOrderList(Request $request, $id)
    {
        $user_details = User::select('id', 'first_name', 'last_name', 'email', 'contact_number', 'updated_at', 'status')
            ->where('id', $id)
            ->whereNull('users.deleted_at')
            ->first();
        if ($user_details == Null) {
            Session::flash('error', 'user details not found!');
            return redirect()->back();
        }
        $on_demand_order_list = UserPackageBooking::select('user_service_package_booking.id', 'service_category.name as service_cat_name', 'service_category.category_type as service_cat_type', 'service_category.icon_name as service_cat_icon', 'user_service_package_booking.order_no', 'user_service_package_booking.total_pay', 'user_service_package_booking.payment_type', 'user_service_package_booking.status')
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->where('user_service_package_booking.user_id', $user_details->id)->get();
        $on_demand_order_status = $this->on_demand_order_status;
        $view = view('admin.pages.super_admin.user.order_list', compact('user_details', 'on_demand_order_list', 'on_demand_order_status',));

        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postAdminCustomerWalletTransaction(Request $request, $id)
    {
        if (!is_numeric($id) || $id == Null) {
            Session::flash('error', 'Customer wallet transaction Not found!');
            return redirect()->back();
        }
        $user_details = User::select('id', 'first_name', 'last_name', 'email', 'contact_number', 'status')
            ->where('id', $id)
            ->whereNull('users.deleted_at')
            ->first();
        if ($user_details == Null) {
            Session::flash('error', 'Customer wallet transaction Not found!');
            return redirect()->back();
        }

        $wallet_transaction_list = UserWalletTransaction::select(
            'id',
            'amount',
            'subject',
            'remaining_balance',
            'created_at',
            DB::raw("(CASE WHEN transaction_type = 1 THEN 'Credit' ELSE (CASE WHEN transaction_type = 2 THEN 'Debit' ELSE '----' END) END) as transaction_type")
        )->where('user_id', $id)->orderBy('id', 'desc')->get();

        $view = view('admin.pages.super_admin.user.wallet_transaction', compact('user_details', 'wallet_transaction_list'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postAdminUpdateCustomerWalletTransaction(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $validator = Validator::make(
            $request->all(),
            [
                "user_id" => "required|numeric",
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
        $user_id = $request->get('user_id');
        $user = User::query()->select('id')
            ->where('id', $user_id)->first();
        if ($user == Null) {
            return response()->json([
                'success' => false,
                'message' => 'something went wrong'
            ]);
        }
        //        $get_currency = WorldCurrency::query()->where('symbol', $user->currency)->first();
        //        if ($get_currency == Null) {
        //            $get_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        //        }
        //        $currency = $get_currency != Null ? $get_currency->ratio : 1;
        //        $amount_to_default = round($request->get('wallet_amount') / $currency, 2);
        $amount_to_default = round($request->get('wallet_amount'), 2);
        try {
            $get_last_transaction = UserWalletTransaction::query()
                ->where('wallet_provider_type', "=", 0)
                ->where('user_id', "=", $user_id)
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
            $add_balance->user_id = $user_id;
            $add_balance->wallet_provider_type = 0;
            $add_balance->transaction_type = 1;
            $add_balance->amount = $amount_to_default;
            //$add_balance->request_amount = round($request->get('amount'), 2);
            $add_balance->subject = "credit by Admin";
            $add_balance->subject_code = 6;
            $add_balance->remaining_balance = floatval($last_amount + $amount_to_default);
            $add_balance->save();
        } else {
            $add_balance = new UserWalletTransaction();
            $add_balance->user_id = $user_id;
            $add_balance->wallet_provider_type = 0;
            $add_balance->transaction_type = 2;
            $add_balance->amount = $amount_to_default;
            //$add_balance->request_amount = round($request->get('amount'), 2);
            $add_balance->subject = "debit by Admin";
            $add_balance->subject_code = 13;
            $add_balance->remaining_balance = floatval($last_amount - $amount_to_default);
            $add_balance->save();
        }

        $last_amount = $add_balance->remaining_balance;

        return response()->json([
            'success' => true,
            'message' => 'success',
            'user_id' => $user->id,
            'last_amount' => $last_amount
        ]);
    }

    public function getRequiredDocumentList($slug, Request $request)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Required document`s service category not found!');
            return redirect()->back();
        }
        $service_cat_id = $service_category->id;
        $required_documents_list = RequiredDocuments::select(
            'required_documents.id',
            'required_documents.name as document_name',
            'required_documents.status',
            'service_category.name'
        )
            ->join('service_category', 'service_category.id', '=', 'required_documents.service_cat_id')
            ->where('service_category.id', '=', $service_cat_id)
            ->get();
        $segment = "provider-services";
        $view = view('admin.pages.super_admin.required-documents.manage', compact('segment', 'slug', 'service_category', 'required_documents_list'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //get add services category wise required documents form
    public function getAddRequiredDocument($slug, Request $request)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Required document`s service category not found!');
            return redirect()->back();
        }
        $segment = "provider-services";
        $view = view('admin.pages.super_admin.required-documents.form', compact('segment', 'slug', 'service_category'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //edit required documents
    public function getEditRequiredDocument($slug, $id, Request $request)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Required document`s service category not found!');
            return redirect()->back();
        }
        $required_document = RequiredDocuments::where('id', $id)->first();
        if ($required_document != Null) {
            $segment = "provider-services";
            $view = view('admin.pages.super_admin.required-documents.form', compact('segment', 'slug', 'service_category', 'required_document'));
            if ($request->ajax()) {
                $view = $view->renderSections();
                return $this->adminClass->renderingResponce($view);
            }
            return $view;
        } else {
            Session::flash('error', 'Required Document Not Found!');
            return redirect()->back();
        }
    }

    //save or update required documents
    public function postUpdateRequiredDocument(RequiredDocumentsRequest $request, $slug)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Required document`s service category not found!');
            return redirect()->back();
        }
        $id = $request->get('id');
        if ($id != Null) {
            $required_document = RequiredDocuments::query()->where('id', $request->get('id'))->first();
        } else {
            $required_document = new RequiredDocuments();
        }
        $service_cat_id = $service_category->id;
        $required_document->name = $request->get('name');
        $required_document->service_cat_id = $service_cat_id;
        $required_document->status = $request->get('status');
        $required_document->save();

        if ($id != Null) {
            Session::flash('success', ucwords($service_category->name) . ' Required Document Updated successfully!');
        } else {
            Session::flash('success', ucwords($service_category->name) . ' Required Document Added successfully!');
        }
        return redirect()->route('get:admin:other_service_required_document_list', [$service_category->slug]);
    }

    //start PromoCode Module
    //Admin PromoCode List
    public function getAdminPromocodeList(Request $request, $slug)
    {
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        $promocode_list = PromocodeDetails::query()->select('id', 'coupon_limit', 'total_usage', 'promo_code', 'status', 'usage_limit', DB::raw('DATE(expiry_date_time) as expiry_date'))->where('service_cat_id', $service_category->id)->get();
        $view = view('admin.pages.super_admin.promocode.manage', compact('slug', 'promocode_list', 'service_category'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }
    //Admin Change PromoCode Status
    public function getAdminPromocodeChangeStatus(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            //Session::flash('error', 'product Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $promocode_details = PromocodeDetails::query()->where('id', $id)->first();
        if ($promocode_details == Null) {
            //Session::flash('error', 'product Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Promocode detail not found'
            ]);
        }
        if ($promocode_details->status == 1) {
            $promocode_details->status = 0;
            $promocode_details->save();
            //Session::flash('success', $service_sub_category->name . ' category disabled successfully!');
        } else {
            $promocode_details->status = 1;
            $promocode_details->save();
            //Session::flash('success', $service_sub_category->name . ' category enable successfully!');
        }
        return response()->json([
            'success' => true,
            'status' => $promocode_details->status
        ]);
    }

    //Admin Add PromoCode
    public function getAdminAddPromocode(Request $request, $slug)
    {
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }

        $view = view('admin.pages.super_admin.promocode.form', compact('slug', 'service_category'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //Admin Store PromoCode
    public function postAdminUpdatePromocode(PromocodeDetailsRequest $request, $slug)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        $id = $request->get('id');
        if ($id != Null) {
            Session::flash('success', 'Promo Code Update Successfully!');
            $promocode_details = PromocodeDetails::query()->where('id', $id)->first();
        } else {
            Session::flash('success', 'Promo Code Add Successfully!');
            $promocode_details = new PromocodeDetails();
            $promocode_details->status = 1;
        }
        $promocode_details->service_cat_id = $request->get('service_cat_id');
        $promocode_details->promo_code = trim(strtoupper($request->get('code_name')));
        $promocode_details->discount_amount = $request->get('discount_amount');
        $promocode_details->discount_type = $request->get('discount_type');
        $promocode_details->min_order_amount = $request->get('min_order_amount') != Null ? $request->get('min_order_amount') : 0;
        $promocode_details->max_discount_amount = $request->get('max_discount_amount') != Null ? $request->get('max_discount_amount') : 0;
        $promocode_details->coupon_limit = $request->get('coupon_limit') != Null ? $request->get('coupon_limit') : 0;
        $promocode_details->usage_limit = $request->get('usage_limit');
        $promocode_details->expiry_date_time = Date('Y-m-d', strtotime($request->get('expiry_date_time')));
        $promocode_details->description = $request->get('description');
        $promocode_details->save();

        if (isset($service_category) && in_array($service_category->category_type, [1, 5])) {
            return redirect()->route('get:admin:transport:promocode_list', $slug);
        } elseif (isset($service_category) && in_array($service_category->category_type, [2])) {
            return redirect()->route('get:admin:store:promocode_list', $slug);
        } else {
            return redirect()->route('get:admin:other:promocode_list', $slug);
        }
    }

    //Admin Edit PromoCode
    public function getAdminEditPromocode(Request $request, $slug, $id)
    {
        $service_category = ServiceCategory::where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }

        $promocode_details = PromocodeDetails::where('id', $id)->first();
        $view = view('admin.pages.super_admin.promocode.form', compact('slug', 'promocode_details', 'service_category'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }
    //end PromoCode Module

    public function getAdminWorldCurrencyList(Request $request)
    {
        $currencies = WorldCurrency::query()->get();
        $view = view('admin.pages.super_admin.world_currency.manage', compact('currencies'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postAdminWorldCurrencyList(Request $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $ratios = $request->get('ratio');
        if ($ratios != Null) {
            foreach ($ratios as $key => $ratio) {
                $get_currency = WorldCurrency::query()->where('id', $key)->first();
                if ($get_currency != Null) {
                    $get_currency->ratio = $ratio;
                    $get_currency->save();
                }
            }
        }
        Session::flash('success', 'Currencies Update Successfully!');
        return redirect()->route('get:admin:world_currency_list');
    }

    public function getAdminProfile(Request $request)
    {
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
        $admin = Admin::where('id', Auth::guard('admin')->user()->id)->first();
        if ($admin != Null) {
            $admin->name = $request->get("name");
            $admin->save();
            Session::flash('success', 'Admin profile update successfully!');
            return redirect()->route('get:admin:profile');
        }
        Session::flash('error', 'Admin Details Not Found!');
        return redirect()->back();
    }

    //get support pages list
    public function getAdminSupportPages(Request $request)
    {
        $my_checkuout_pages_list = PageSettings::query()->where('type', 1)->get();
        $my_service_pages_list = PageSettings::query()->where('type', 2)->get();
        $my_driver_pages_list = PageSettings::query()->where('type', 3)->get();
        $my_store_pages_list = PageSettings::query()->where('type', 4)->get();
        $view = view('admin.pages.super_admin.support_pages.manage', compact('my_checkuout_pages_list', 'my_service_pages_list', 'my_driver_pages_list', 'my_store_pages_list'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //get add support pages
    public function getAdminAddPages(Request $request)
    {
        $service_category = ServiceCategory::where('id', $this->food_delivery)->first();
        $language_lists = LanguageLists::query()->where('status', '=', '1')->get();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        $slug = $service_category->slug;
        $view = view('admin.pages.super_admin.support_pages.add_new', compact('slug', 'language_lists'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //get edit support pages
    public function getAdminEditPages(Request $request, $page_id)
    {
        $service_category = ServiceCategory::where('id', $this->on_demand_service_id_array)->first();
        $language_lists = LanguageLists::query()->where('status', '=', '1')->get();

        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        $slug = $service_category->slug;
        $pages = PageSettings::query()->where('id', '=', $page_id)->first();
        $view = view('admin.pages.super_admin.support_pages.add_new', compact('slug', 'pages', 'language_lists'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //post update support pages
    public function postAdminUpdateSupportPages(Request $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $service_category = ServiceCategory::where('id', $this->on_demand_service_id_array)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        $slug = $service_category->slug;

        $pages = PageSettings::where('id', $request->get('id'))->first();
        if ($pages == Null) {
            $pages = new PageSettings();
        }
        $pages->name = $request->get('name');
        /*$pages->fl_name = $request->get('fl_name');
        $pages->cb_name = $request->get('cb_name');
        $pages->cs_name = $request->get('cs_name');
        $pages->ct_name = $request->get('ct_name');
        $pages->jp_name = $request->get('jp_name');
        $pages->ko_name = $request->get('ko_name');
        $pages->fr_name = $request->get('fr_name');
        $pages->sp_name = $request->get('sp_name');
        $pages->gr_name = $request->get('gr_name');
        $pages->ar_name = $request->get('ar_name');*/
        $pages->description = $request->get('description');
        /*$pages->fl_description = $request->get('fl_description');
        $pages->cb_description = $request->get('cb_description');
        $pages->cs_description = $request->get('cs_description');
        $pages->ct_description = $request->get('ct_description');
        $pages->jp_description = $request->get('jp_description');
        $pages->ko_description = $request->get('ko_description');
        $pages->fr_description = $request->get('fr_description');
        $pages->sp_description = $request->get('sp_description');
        $pages->gr_description = $request->get('gr_description');
        $pages->ar_description = $request->get('ar_description');*/

        try {
            $language_list = LanguageLists::query()->select(
                'language_name as name',
                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as page_setting_name"),
                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_description') ELSE 'name' END) as page_desc_name")
            )->where('status', 1)->get();
            foreach ($language_list as $key => $language) {
                if (Schema::hasColumn('page_settings', $language->page_setting_name) && Schema::hasColumn('page_settings', $language->page_desc_name)) {
                    $pages->{$language->page_setting_name} = $request->get($language->page_setting_name);
                    $pages->{$language->page_desc_name} = $request->get($language->page_desc_name);
                }
            }
        } catch (\Exception $e) {
        }
        $pages->save();
        if ($request->get('id') == Null) {
            //            $pages->slug = $pages->getSlugForCustom($request->get('name'));
            $pages->save();
        }
        return redirect()->route('get:admin:support_pages');
    }

    //delete support pages
    public function getAdminDeleteSupportPages(Request $request)
    {
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Page Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $page = PageSettings::where('id', $id)->first();
        if ($page == Null) {
            Session::flash('error', 'Page Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'ddd not found'
            ]);
        }
        $page->delete();
        Session::flash('success', 'Page remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }

    //post support pages
    //    public function postAdminUpdateSupportPages(Request $request)
    //    {
    //        $pages = PageSettings::where('name', $request->get('name'))->first();
    //        if ($pages == Null) {
    //            Session::flash('error', 'Support Page not Updated!');
    //            return redirect()->back();
    //        }
    //        $pages->description = $request->get('description');
    //        $pages->save();
    //        if ($request->ajax()) {
    //            $view = view('admin.pages.super_admin.support_pages.form', compact('pages'))->renderSections();
    //            return $this->adminClass->renderingResponce($view);
    //        }
    //        return view('admin.pages.super_admin.support_pages.form', compact('pages'));
    //    }

    //get AdminAbout pages
    public function getAdminAboutPages(Request $request)
    {
        $pages = PageSettings::where('name', 'about-us')->first();
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.support_pages.form', compact('pages'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.support_pages.form', compact('pages'));
    }

    //get support pages
    public function getAdminContactUsPages(Request $request)
    {
        $pages = PageSettings::where('name', 'contact-us')->first();
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.support_pages.form', compact('pages'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.support_pages.form', compact('pages'));
    }

    public function getAdminFaqPages(Request $request)
    {
        $pages = PageSettings::where('name', 'faq')->first();
        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.support_pages.form', compact('pages'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.support_pages.form', compact('pages'));
    }

    //unregister provider list
    public function getAdminUnRegisterProviderList(Request $request)
    {
        $provider_service = ProviderServices::select('provider_id')->get()->toArray();
        $provider_service_array = [];
        if ($provider_service != Null) {
            $provider_service_array = array_unique(Arr::pluck($provider_service, 'provider_id'));
        }
        $un_register_providers_list = Provider::whereIn('providers.status', [3])
            ->whereNull('providers.deleted_at')
            ->whereNotIn('id', $provider_service_array)
            ->orderBy('providers.id', 'desc')
            ->get();
        if ($un_register_providers_list->isEmpty()) {
            $view = view('admin.pages.super_admin.un_register_porvider_list');
        } else {
            $view = view('admin.pages.super_admin.un_register_porvider_list', compact('un_register_providers_list'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //delete un register provider
    public function getDeleteUnRegisterProvider(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }

        $provider_id = $request->get('provider_id');
        if ($provider_id == Null) {
            //            Session::flash('error', 'Provider Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider = Provider::where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        if ($provider == Null) {
            //            Session::flash('error', 'Provider Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($provider->status == 3) {
            if (\File::exists(public_path('/assets/images/profile-images/provider/' . $provider->avatar))) {
                \File::delete(public_path('/assets/images/profile-images/provider/' . $provider->avatar));
            }
            $provider->delete();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        //        Session::flash('error', 'Provider remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }

    //pending other service provider list
    public function getAdminPendingProviderList(Request $request)
    {
        $un_register_providers_list = Provider::select(
            'providers.id',
            'providers.email',
            'providers.country_code',
            'providers.contact_number',
            'provider_services.id as provider_id',
            DB::raw("CONCAT(COALESCE(providers.first_name,''),' ',COALESCE(providers.last_name,'')) as name"),
            'provider_services.created_at',
            'provider_services.status',
            'service_category.name as service_category_name'
        )
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            ->where('provider_services.status', 0)
            ->whereNull('providers.deleted_at')
            ->whereIn('provider_services.service_cat_id', $this->on_demand_service_id_array)
            ->get();
        if ($un_register_providers_list->isEmpty()) {
            $view = view('admin.pages.super_admin.pending_porvider_list');
        } else {
            $view = view('admin.pages.super_admin.pending_porvider_list', compact('un_register_providers_list'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //get push notification
    public function getAdminPushNotification(Request $request)
    {
        $push_notification = PushNotification::select(
            "id",
            "notification_type",
            "title",
            "message",
            "created_at",
            DB::raw("(CASE WHEN notification_type ='1' THEN 'All Users And Providers' ELSE CASE WHEN notification_type ='2' THEN 'All Users' ELSE CASE WHEN notification_type ='3' THEN 'All Drivers' ELSE  CASE WHEN notification_type ='4' THEN 'All Stores' ELSE 'All Providers' END END END END) as notification_type")
        )->orderBy('id', 'desc')->get();
        $view = view('admin.pages.super_admin.push_notification.form_manage', compact('push_notification'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //save or update general settings
    public function postAdminUpdatePushNotification(PushNotificationRequest $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $general_settings = request()->get('general_settings');
        if ($general_settings == Null) {
            return redirect()->back()->with("error", "Something went wrong!");
        }

        if ($request->get('notification_type') == null) {
            return redirect()->back()->with('error', 'Notification type is required');
        }

        $push_notification = new PushNotification();
        $push_notification->notification_type = $request->get('notification_type');
        $push_notification->title = $request->get('title');
        $push_notification->message = $request->get('message');
        $push_notification->save();

        if ($request->get('notification_type') == 1) {
            $send = "All Users And Providers";
            $this->notificationClass->sendPushNotification($general_settings->fcm_user_topic_name, $push_notification->title, $push_notification->message, 0);
            $this->notificationClass->sendPushNotification($general_settings->fcm_provider_topic_name, $push_notification->title, $push_notification->message, 3);
        }
        if ($request->get('notification_type') == 2) {
            $send = "All Users";
            $this->notificationClass->sendPushNotification($general_settings->fcm_user_topic_name, $push_notification->title, $push_notification->message, 0);
        }
        if ($request->get('notification_type') == 5) {
            $send = "All Providers";
            $this->notificationClass->sendPushNotification($general_settings->fcm_provider_topic_name, $push_notification->title, $push_notification->message, 3);
        }
        //Session::flash('success', 'Send notification successfully!');
        return redirect()->route('get:admin:push_notification')->with("success", ucwords($send) . " Sent Notifications Successfully.");
    }

    public function getAdminRemovePushNotification(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Customer Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $notification = PushNotification::query()->where('id', $id)->first();
        if ($notification == Null) {
            //            Session::flash('error', 'Customer Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $notification->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    //get sub admin list
    public function getAdminSubAdminList(Request $request)
    {
        $sub_admin_list = Admin::query()->where('roles', '4')->get();
        $view = view('admin.pages.super_admin.sub_admin.manage', compact('sub_admin_list'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //get sub admin add
    public function getAdminAddSubAdmin(Request $request)
    {
        $admin_all_module = AdminModule::query()->select('id', 'name', 'module_name', 'module_action')
            ->where('parent_id', '=', 0)
            ->where('status', '=', 1)
            ->whereNotIn('id', [7, 19, 21]) // Use whereNotIn if you want to exclude these IDs
            ->orderBy("seq")->get();
        $module_with_action = [];
        if ($admin_all_module->isNotEmpty()) {
            foreach ($admin_all_module as $menu_detail) {
                $sub_menu_list = AdminModule::query()->select('id', 'name', 'module_name', 'module_action')
                    ->where('parent_id', '=', $menu_detail->id)
                    ->where('status', '=', 1)
                    ->orderBy("seq")
                    ->get();
                $is_checkbox = 1;
                $sub_module_with_action = [];
                if ($sub_menu_list->isNotEmpty()) {
                    $is_checkbox = 0;
                    foreach ($sub_menu_list as $sub_menu_detail) {
                        $sub_module_action_checkbox = [];
                        $getAllPageAction = AdminPageAction::query()->select('id', 'constant', 'name')->get();
                        foreach ($getAllPageAction as $singleAction) {
                            if (!in_array($singleAction->id, explode(',', $menu_detail->module_action))) {
                                continue;
                            }
                            $sub_module_action_checkbox[] = [
                                'id' => $singleAction->id,
                                'name' => $singleAction->name,
                                'constant' => $singleAction->constant,
                                'checked' => ""
                            ];
                        }
                        $sub_module_with_action[] = [
                            'module_id' => $sub_menu_detail->id,
                            'name' => $sub_menu_detail->name,
                            'module_name' => $sub_menu_detail->module_name,
                            'module_action' => $sub_menu_detail->module_action,
                            'checkbox' => $sub_module_action_checkbox,
                            'is_checkbox_show' => 1,
                            'sub_module_with_action' => [],
                            'menu_category_wise_list' => [],
                        ];
                    }
                }

                $module_action_checkbox = [];
                $menu_category_wise_list = [];
                if ($is_checkbox == 1) {
                    if ($menu_detail->id == 1) {
                        $get_menu_category_wise_list = ServiceCategory::query()
                            ->select("id", "name")
                            ->whereIn("category_type", [3, 4])
                            ->get();
                        if ($get_menu_category_wise_list->isNotEmpty()) {
                            foreach ($get_menu_category_wise_list as $get_menu_category_wise) {
                                $sub_module_action_checkbox = [];
                                $getAllPageAction = AdminPageAction::query()->select('id', 'constant', 'name')->get();
                                foreach ($getAllPageAction as $singleAction) {
                                    if (!in_array($singleAction->id, explode(',', $menu_detail->module_action))) {
                                        continue;
                                    }
                                    $sub_module_action_checkbox[] = [
                                        'id' => $singleAction->id,
                                        'name' => $singleAction->name,
                                        'constant' => $singleAction->constant,
                                        'checked' => ""
                                    ];
                                }
                                $menu_category_wise_list[] = [
                                    'module_id' => $menu_detail->id,
                                    'category_id' => $get_menu_category_wise->id,
                                    'name' => $get_menu_category_wise->name,
                                    'module_name' => $get_menu_category_wise->name,
                                    'module_action' => 1,
                                    'checkbox' => $sub_module_action_checkbox,
                                    'is_checkbox_show' => 1,
                                    'sub_module_with_action' => [],
                                    'menu_category_wise_list' => [],
                                ];
                            }
                        }
                    }

                    $getAllPageAction = AdminPageAction::query()->select('id', 'constant', 'name')->get();
                    foreach ($getAllPageAction as $singleAction) {
                        if (!in_array($singleAction->id, explode(',', $menu_detail->module_action))) {
                            continue;
                        }
                        $module_action_checkbox[] = [
                            'id' => $singleAction->id,
                            'name' => $singleAction->name,
                            'constant' => $singleAction->constant,
                            'checked' => ""
                        ];
                    }
                }
                $module_with_action[] = [
                    'module_id' => $menu_detail->id,
                    'name' => $menu_detail->name,
                    'module_name' => $menu_detail->module_name,
                    'module_action' => $menu_detail->module_action,
                    'checkbox' => $module_action_checkbox,
                    'is_checkbox_show' => $is_checkbox,
                    'sub_module_with_action' => $sub_module_with_action,
                    'menu_category_wise_list' => $menu_category_wise_list,
                ];
            }
        }
        $on_click_res_module = array(1, 2, 4);
        $on_click_res_module =  json_encode($on_click_res_module);
        $res_module = 33;

        $view = view('admin.pages.super_admin.sub_admin.add_new', compact('module_with_action', 'on_click_res_module', 'res_module'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //get sub admin edit
    public function getAdminEditSubAdmin(Request $request, $admin_id)
    {
        $admin_user = Admin::where('id', $admin_id)->first();
        if ($admin_user != Null) {
            $admin_all_module = AdminModule::query()->select('id', 'name', 'module_name', 'module_action')
                ->where('parent_id', '=', 0)
                ->where('status', '=', 1)
                //                ->where('is_access', '=', 1)
                ->whereNotIn('id', [7, 19, 21]) // Use whereNotIn if you want to exclude these IDs
                ->orderBy("seq")
                ->get();
            $module_with_action = [];
            if ($admin_all_module->isNotEmpty()) {
                foreach ($admin_all_module as $menu_detail) {
                    $sub_menu_list = $admin_all_module = AdminModule::query()->select('id', 'name', 'module_name', 'module_action')
                        ->where('parent_id', '=', $menu_detail->id)
                        ->where('status', '=', 1)
                        //                        ->where('is_access', '=', 1)
                        ->orderBy("seq")
                        ->get();
                    $is_checkbox = 1;
                    $sub_module_with_action = [];
                    if ($sub_menu_list->isNotEmpty()) {
                        $is_checkbox = 0;
                        foreach ($sub_menu_list as $sub_menu_detail) {
                            $sub_module_action_checkbox = [];
                            $getAllPageAction = AdminPageAction::query()->select('id', 'constant', 'name')->get();
                            foreach ($getAllPageAction as $singleAction) {
                                if (!in_array($singleAction->id, explode(',', $sub_menu_detail->module_action))) {
                                    continue;
                                }
                                $checkadminPermission = AdminPermission::query()
                                    ->where('admin_id', '=', $admin_id)
                                    ->where('module_id', '=', $sub_menu_detail->id)
                                    ->whereRaw("find_in_set('$singleAction->id',permission)")
                                    ->first();
                                $checked = ($checkadminPermission != NULL) ? "checked" : "";
                                $sub_module_action_checkbox[] = [
                                    'id' => $singleAction->id,
                                    'name' => $singleAction->name,
                                    'constant' => $singleAction->constant,
                                    'checked' => $checked
                                ];
                            }
                            $sub_module_with_action[] = [
                                'module_id' => $sub_menu_detail->id,
                                'name' => $sub_menu_detail->name,
                                'module_name' => $sub_menu_detail->module_name,
                                'module_action' => $sub_menu_detail->module_action,
                                'checkbox' => $sub_module_action_checkbox,
                                'is_checkbox_show' => 1,
                                'sub_module_with_action' => [],
                                //'checkbox' => $module_action_checkbox,
                            ];
                        }
                    }
                    $module_action_checkbox = [];
                    $menu_category_wise_list = [];
                    if ($is_checkbox == 1) {
                        if ($menu_detail->id == 1) {
                            $get_menu_category_wise_list = ServiceCategory::query()->select("id", "name")
                                ->whereIn("category_type",  [3, 4])
                                ->get();
                            if ($get_menu_category_wise_list->isNotEmpty()) {
                                foreach ($get_menu_category_wise_list as $get_menu_category_wise) {
                                    $sub_module_action_checkbox = [];
                                    $getAllPageAction = AdminPageAction::query()->select('id', 'constant', 'name')->get();
                                    foreach ($getAllPageAction as $singleAction) {
                                        if (!in_array($singleAction->id, explode(',', $menu_detail->module_action))) {
                                            continue;
                                        }
                                        $checkadminPermission = AdminCategoryPermission::query()
                                            ->where('service_cat_id', '=', $get_menu_category_wise->id)
                                            ->where('admin_id', '=', $admin_id)
                                            ->where('module_id', '=', $menu_detail->id)
                                            ->whereRaw("find_in_set('$singleAction->id',permission)")
                                            ->first();
                                        $checked = ($checkadminPermission != NULL) ? "checked" : "";
                                        $sub_module_action_checkbox[] = [
                                            'id' => $singleAction->id,
                                            'name' => $singleAction->name,
                                            'constant' => $singleAction->constant,
                                            'checked' => $checked
                                        ];
                                    }
                                    $menu_category_wise_list[] = [
                                        'module_id' => $menu_detail->id,
                                        'category_id' => $get_menu_category_wise->id,
                                        'name' => $get_menu_category_wise->name,
                                        'module_name' => $get_menu_category_wise->name,
                                        'module_action' => 1,
                                        'checkbox' => $sub_module_action_checkbox,
                                        'is_checkbox_show' => 1,
                                        'sub_module_with_action' => [],
                                        'menu_category_wise_list' => [],
                                        //'checkbox' => $module_action_checkbox,
                                    ];
                                }
                            }
                        }

                        $getAllPageAction = AdminPageAction::query()->select('id', 'constant', 'name')->get();
                        foreach ($getAllPageAction as $singleAction) {
                            if (!in_array($singleAction->id, explode(',', $menu_detail->module_action))) {
                                continue;
                            }
                            $checkadminPermission = AdminPermission::query()
                                ->where('admin_id', '=', $admin_id)
                                ->where('module_id', '=', $menu_detail->id)
                                ->whereRaw("find_in_set('$singleAction->id',permission)")
                                ->first();
                            $checked = ($checkadminPermission != NULL) ? "checked" : "";
                            $module_action_checkbox[] = [
                                'id' => $singleAction->id,
                                'name' => $singleAction->name,
                                'constant' => $singleAction->constant,
                                'checked' => $checked
                            ];
                        }
                    }
                    $module_with_action[] = [
                        'module_id' => $menu_detail->id,
                        'name' => $menu_detail->name,
                        'module_name' => $menu_detail->module_name,
                        'module_action' => $menu_detail->module_action,
                        'checkbox' => $module_action_checkbox,
                        'is_checkbox_show' => $is_checkbox,
                        'sub_module_with_action' => $sub_module_with_action,
                        'menu_category_wise_list' => $menu_category_wise_list,
                        //'checkbox' => $module_action_checkbox,
                    ];
                }
            }
            $on_click_res_module = array(1, 2, 4);
            $on_click_res_module =  json_encode($on_click_res_module);
            $res_module = 33;
            $view = view('admin.pages.super_admin.sub_admin.add_new', compact('admin_user', 'module_with_action', 'on_click_res_module', 'res_module'));
            if ($request->ajax()) {
                $view = $view->renderSections();
                return $this->adminClass->renderingResponce($view);
            }
            return $view;
        } else {
            Session::flash('error', 'Something want to wrong!');
            return redirect()->back();
        }
    }

    //post usub admin update
    public function postAdminUpdateSubAdmin(SubAdminRequest $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        if ($request->get('admin_permission') == Null) {
            Session::flash('error', 'select at-list one provider service module.');
            return redirect()->back();
        }
        if ($request->get('admin_permission') == Null) {
            Session::flash('error', 'select at-list one category module.');
            return redirect()->back();
        }

        $admin = Admin::where('id', $request->get('id'))->first();
        if ($admin == Null) {
            $admin = new Admin();
        }
        $admin->name = $request->get('name');
        $admin->email = $request->get('email');
        if ($request->get('password') != "") {
            $admin->password = Hash::make($request->get('password'));
        }
        $admin->roles = "4";
        $admin->admin_type = 'g';
        $admin->save();

        $admin_cat_permission = $request->get('admin_cat_permission');
        if ((isset($admin_cat_permission)) && count($admin_cat_permission) > 0) {
            $is_exist = AdminCategoryPermission::query()->where('admin_id', $admin->id)->first();
            if ($is_exist != NULL) {
                AdminCategoryPermission::query()->where('admin_id', $admin->id)->delete();
            }
            foreach ($admin_cat_permission as $mIdkey => $get_module_value) {
                if ($get_module_value != Null) {
                    foreach ($get_module_value as $sIdkey => $value) {
                        $get_parent_id = ServiceCategory::query()->select('id')->where('id', '=', $sIdkey)->first();
                        $add_admin_permission_new = new AdminCategoryPermission();
                        $add_admin_permission_new->service_cat_id = $sIdkey;
                        $add_admin_permission_new->admin_id = $admin->id;
                        $add_admin_permission_new->module_id = $mIdkey;
                        $add_admin_permission_new->permission = implode(',', $value);
                        $add_admin_permission_new->save();
                    }
                }
            }
        } else {
            AdminCategoryPermission::query()->where('admin_id', $admin->id)->delete();
        }
        $admin_permission = $request->get('admin_permission');
        if ((isset($admin_permission)) && count($admin_permission) > 0) {
            $is_exist = AdminPermission::where('admin_id', $admin->id)->first();
            if ($is_exist != NULL) {
                AdminPermission::where('admin_id', $admin->id)->delete();
            }

            foreach ($admin_permission as $key => $value) {
                //code for store admin permisson to admin_permission table
                //code for check parent module for permission if direct give child mod permission then auto asign parent permission

                $get_parent_id = AdminModule::query()->select('parent_id')->where('id', '=', $key)->first();

                if ($get_parent_id->parent_id != 0) {
                    $check_parent_add = AdminPermission::query()->select('id')->where('admin_id', '=', $admin->id)
                        ->where('module_id', '=', $get_parent_id->parent_id)->first();

                    if ($check_parent_add == null) {
                        $add_admin_permission = new AdminPermission();
                        $add_admin_permission->admin_id = $admin->id;
                        $add_admin_permission->module_id = $get_parent_id->parent_id;
                        $add_admin_permission->permission = "1";
                        $add_admin_permission->save();
                    }
                }

                $add_admin_permission_new = new AdminPermission();
                $add_admin_permission_new->admin_id = $admin->id;
                $add_admin_permission_new->module_id = $key;
                $add_admin_permission_new->permission = implode(',', $value);
                $add_admin_permission_new->save();
            }
        }

        if ($request->get('id') == NULL) {
            Session::flash('success', 'Sub admin added sucessfully!');
        } else {
            Session::flash('success', 'Sub admin edited sucessfully!');
        }
        return redirect()->route('get:admin:sub_admin_list');
    }

    //delete sub admin delete
    public function getAdminDeleteSubAdmin(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Sub Admin Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $admin = Admin::where('id', $id)->first();
        if ($admin == Null) {
            Session::flash('error', 'Sub Admin Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'data not found'
            ]);
        }
        $admin->delete();
        //        Session::flash('success', 'Sub Admin remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }

    public function getHomePageBannerList(Request $request)
    {
        $banner_image = HomePageBanner::query()->select('id', 'service_name', 'name', 'banner_image', 'service_id', 'status')->where('type', '=', 1)->get();

        $view = view('admin.pages.super_admin.home_page_banner_list', compact('banner_image'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponse($view);
        }
        return $view;
    }

    public function getHomePageBanner($id = 0)
    {
        $slug = '';
        $service_category = ServiceCategory::query()->where('status', 1)->get();
        $language_lists = LanguageLists::query()->orderBy('id', 'asc')->get();
        if (isset($id) && $id > 0) {
            $home_banner = HomePageBanner::query()->where('id', $id)->first();
            $view = view('admin.pages.super_admin.home_page_banner', compact('slug', 'service_category', 'home_banner', 'language_lists'));
        } else {
            $view = view('admin.pages.super_admin.home_page_banner', compact('service_category', 'language_lists'));
        }

        return $view;
    }

    public function AddEditHomePageBanner(Request $request)
    {
        //        dd($request->all());
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $banner_id = $request->get('banner_id');
        $language_lists = LanguageLists::query()->orderBy('id', 'asc')->get();

        if ($request->get('service') != Null) {
            $service_name = ServiceCategory::query()->select('name')->where('id', $request->get('service'))->first();

            //echo $service_name;exit;
            if ($banner_id != Null) {
                $banner = HomePageBanner::query()->where('id', $banner_id)->where('type', '=', 1)->first();
            } else {
                $banner = new HomePageBanner();
            }
            if ($request->hasFile('image') != Null) {
                if (\File::exists(public_path('/assets/images/home-banner/' . $banner->banner_image))) {
                    \File::delete(public_path('/assets/images/home-banner/' . $banner->banner_image));
                }
                $destinationPath = public_path('/assets/images/home-banner/');
                $file = $request->file('image');
                $img = Image::read($file->getRealPath());
                $img->orient();
                $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                $img->resize(600, 240, function ($constraint) {
                    //$constraint->aspectRatio();
                })->save($destinationPath . $file_new);
                $banner->banner_image = $file_new;

                /*$file = $request->file('image');
                $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $file_new);
                $banner->banner_image = $file_new;*/
            }
            $banner->service_name = $service_name->name;
            $banner->service_id = $request->get('service');
            $banner->name = $request->get('name');
            $banner->description = $request->get('description');

            try {
                $language_list = LanguageLists::query()->select(
                    'language_name as name',
                    DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as constant_val"),
                    DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_description') ELSE 'name' END) as constant_val_description")
                )->where('status', 1)->get();
                foreach ($language_list as $key => $language) {

                    if (Schema::hasColumn('home_page_banner', $language->constant_val)) {
                        $banner->{$language->constant_val} = $request->get($language->constant_val);
                        $banner->{$language->constant_val_description} = $request->get($language->constant_val_description);
                    }
                }
            } catch (\Exception $e) {
                return redirect()->route('get:admin:home_page_banner_list')->with("error",  " Language Constant value is not properly added.");
            }

            $banner->save();
            Session::flash('success', 'Banner added successfully!');
            return redirect()->route('get:admin:home_page_banner_list', compact('language_lists'));
        } else {
            Session::flash('error', 'Service not found!');
            return redirect()->route('get:admin:home_page_banner_list', compact('language_lists'));
        }
    }

    public function getAdminHomeBannerChangeStatus(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Service Banner not found'
            ]);
        }
        $home_banner = HomePageBanner::query()->where('id', $id)->where('type', '=', 1)->first();
        if ($home_banner == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Home Banner not found'
            ]);
        }
        if ($home_banner->status == 1) {
            $home_banner->status = 0;
            $home_banner->save();
        } else {
            $home_banner->status = 1;
            $home_banner->save();
        }
        return response()->json([
            'success' => true,
            'status' => $home_banner->status
        ]);
    }

    public function getAdminDeleteHomeBanner(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Service Banner not found'
            ]);
        }
        $app_banner = HomePageBanner::query()->where('id', $id)->where('type', '=', 1)->first();
        if ($app_banner == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Service Banner not found'
            ]);
        }
        if (\File::exists(public_path('/assets/images/home-banner/' . $app_banner->banner_image))) {
            \File::delete(public_path('/assets/images/home-banner/' . $app_banner->banner_image));
        }
        $app_banner->delete();
        return response()->json([
            'success' => true
        ]);
    }


    public function getAdminRestrictedAreaList(Request $request)
    {
        $area_list = RestrictedArea::query()->get();
        $view = view('admin.pages.super_admin.geo_fencing.manage', compact('area_list'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getAdminAddRestrictedArea(Request $request)
    {
        $view = view('admin.pages.super_admin.geo_fencing.form');
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postAdminUpdateRestrictedArea(Request $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        if ($request->get('latitude') == Null || $request->get('longitude') == Null || $request->get('area_name') == Null) {
            Session::flash('error', 'Please select restricted ares!');
            return redirect()->back();
        }
        $id = $request->get('id') != Null ? $request->get('id') : 0;
        $area = RestrictedArea::query()->where('id', $id)->first();
        if ($area == Null) {
            $area = new RestrictedArea();
        }
        $area->name = $request->get('area_name');
        $area->latitude = $request->get('latitude');
        $area->longitude = $request->get('longitude');
        $area->save();

        if ($id != Null) {
            return redirect()->route('get:admin:restricted_area_list')->with('success', 'Restricted area updated successfully!');
        }
        return redirect()->route('get:admin:restricted_area_list')->with('success', 'Restricted area added successfully!');
    }

    public function getAdminEditRestrictedArea(Request $request, $id)
    {
        $area_details = RestrictedArea::query()->where('id', $id)->first();
        $view = view('admin.pages.super_admin.geo_fencing.form', compact('area_details'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postAdminUpdateRestrictedAreaStatus(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        /*$area_details = RestrictedArea::query()->get();
        $latitudes = $longitudes = '';
        foreach ($area_details as $area){
            $latitudes = $latitudes . $area->latitude . ',';
            $longitudes = $longitudes . $area->longitude . ',';
        }
        $restricted_lat = explode(',',substr($latitudes, 0, -1));
        $restricted_long = explode(',',substr($longitudes, 0, -1));
        $points_polygon = count($restricted_lat);
        if($this->is_in_restricted_area($points_polygon,$restricted_lat,$restricted_long,21.44611300681402,105.24368486328122)){
            exit('in');
        } else{
            exit('out');
        }*/
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $area_details = RestrictedArea::query()->where('id', '=', $id)->first();
        if ($area_details == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($area_details->status == 1) {
            $status = $area_details->status = 0;
        } else {
            $status = $area_details->status = 1;
        }
        $area_details->save();
        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    public function getAdminDeleteRestrictedArea(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Area Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user = RestrictedArea::where('id', $id)->first();
        if ($user == Null) {
            Session::flash('error', 'Area Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user->delete();
        //        Session::flash('success', 'Area remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }

    public function getEmailTemplatesList(Request $request)
    {
        $email_templates = EmailTemplates::query()->get();
        $view = view('admin.pages.super_admin.email_templates.manage', compact('email_templates'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getAdminAddEmailTemplates(Request $request)
    {
        $view = view('admin.pages.super_admin.email_templates.form');
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getAdminEditEmailTemplates(Request $request, $id)
    {
        $email_templates = EmailTemplates::query()->where('id', $id)->first();
        if ($email_templates == Null) {
            return redirect()->back();
        }
        $view = view('admin.pages.super_admin.email_templates.form', compact('email_templates'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postAdminUpdateEmailTemplates(EmailTemplatesRequest $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $id = $request->get('id');
        $email_templates = EmailTemplates::query()->where('id', $id)->first();
        Session::flash('success', 'Template updated successfully!');
        if ($email_templates == Null) {
            Session::flash('success', 'Template added successfully!');
            $email_templates = new EmailTemplates();
        }
        $email_templates->title = $request->get('title');
        $email_templates->content = $request->get('content');
        $email_templates->save();

        return redirect()->route('get:admin:email_templates');
    }

    public function postAdminUpdateEmailTemplatesStatus(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $template_details = EmailTemplates::query()->where('id', '=', $id)->first();
        if ($template_details == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($template_details->status == 1) {
            $status = $template_details->status = 0;
        } else {
            $status = $template_details->status = 1;
        }
        $template_details->save();
        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    public function getAdminDeleteEmailTemplates(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Area Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $template = EmailTemplates::where('id', $id)->first();
        if ($template == Null) {
            Session::flash('error', 'Area Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $template->delete();
        Session::flash('success', 'Area remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }

    //get Language Lists
    public function getAdminLanguageLists(Request $request)
    {
        $language_lists = LanguageLists::query()->orderBy('id', 'asc')->get();
        $view = view('admin.pages.super_admin.language_lists.form_manage', compact('language_lists'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    //save or update Language Lists
    public function postAdminUpdateLanguageLists(Request $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }

        $language_name = $request->get('language_name');
        //        $language_code = trim(strtolower(str_replace(" ","",$request->get('language_code'))));
        $language_code = str_replace(" ", "", $request->get('language_code'));

        $get_Exist_code = LanguageLists::query()->where('language_code', $language_code)->first();
        if ($get_Exist_code == Null) {
            //add new column in service category(en_name) ,other_service_sub_category,page_settings,user_package_booking_quantity
            $col_name = $language_code . "_name";
            $col_sub_category_name = $language_code . "_sub_category_name";
            $page_setting_desc_col = $language_code . "_description";
            $page_setting_inst_col = $language_code . "_instruction";
            $constant_name = $language_code . "_value";
            try {

                //add column at service cteogry
                if (!Schema::hasColumn('service_category', $col_name)) {
                    Schema::table('service_category', function (Blueprint $table) use ($col_name) {
                        $table->string($col_name)->after('slug')->collation('utf8mb4_unicode_ci')->nullable();
                    });
                    //add value in new column
                    ServiceCategory::query()->where('name', '!=', "")
                        ->update([
                            $col_name => DB::raw("CONCAT('" . $language_code . "-', name)")
                        ]);
                }

                //add column at other_service_sub_category
                if (!Schema::hasColumn('other_service_sub_category', $col_name)) {
                    Schema::table('other_service_sub_category', function (Blueprint $table) use ($col_name) {
                        $table->string($col_name)->after('status')->collation('utf8mb4_unicode_ci')->nullable();
                    });
                    //add value in new column
                    OtherServiceCategory::query()->where('name', '!=', "")
                        ->update([
                            $col_name => DB::raw("name"),
                        ]);
                }

                //add column at user_package_booking_quantity
                if (!Schema::hasColumn('user_package_booking_quantity', $col_sub_category_name)) {
                    Schema::table('user_package_booking_quantity', function (Blueprint $table) use ($col_sub_category_name) {
                        $table->string($col_sub_category_name)->after('price_for_one')->collation('utf8mb4_unicode_ci')->nullable();
                    });
                    //add value in new column
                    UserPackageBookingQuantity::query()->where('sub_category_name', '!=', "")
                        ->update([
                            $col_sub_category_name => DB::raw("sub_category_name"),
                        ]);
                }

                //add column at page_settings
                if (!Schema::hasColumn('page_settings', $col_name)) {
                    Schema::table('page_settings', function (Blueprint $table) use ($col_name, $page_setting_desc_col) {
                        $table->string($col_name)->after('name')->nullable();
                        $table->longText($page_setting_desc_col)->after('description')->collation('utf8mb4_unicode_ci')->nullable();
                    });
                }

                // add column in language_constant table
                if (!Schema::hasColumn('language_constant', $constant_name)) {
                    Schema::table('language_constant', function (Blueprint $table) use ($constant_name) {
                        $table->string($constant_name)->after('value')->collation('utf8mb4_unicode_ci')->nullable();
                    });
                    //add value in new column
                    LanguageConstant::query()->where('value', '!=', "")
                        ->update([
                            $constant_name => DB::raw("CONCAT('" . $language_code . "-', value)")
                        ]);
                }

                //add column in home_page_banner table
                if (!Schema::hasColumn('home_page_banner', $col_name)) {
                    Schema::table('home_page_banner', function (Blueprint $table) use ($col_name, $page_setting_desc_col) {
                        $table->string($col_name)->after('name')->collation('utf8mb4_unicode_ci')->nullable();
                        $table->string($page_setting_desc_col)->after('description')->collation('utf8mb4_unicode_ci')->nullable();
                    });
                    //add value in new column
                    HomePageBanner::query()->where('name', '!=', "")
                        ->update([
                            $col_name => DB::raw("CONCAT('" . $language_code . "-', name)"),
                            $page_setting_desc_col => DB::raw("CONCAT('" . $language_code . "-', description)")
                        ]);
                }

                $language_lists = new LanguageLists();
                $language_lists->language_name = $language_name;
                $language_lists->language_code = $language_code;
                $language_lists->save();
            } catch (\Exception $e) {
                //                dd($e->getMessage());
                return redirect()->route('get:admin:language_lists')->with("error",  " Language Field is not properly added.");
            }

            return redirect()->route('get:admin:language_lists')->with("success",  " Language Added Successfully.");
        } else {


            //add new column in service category(en_name) ,other_service_sub_category,page_settings,user_package_booking_quantity
            $col_name = $language_code . "_name";
            $col_sub_category_name = $language_code . "_sub_category_name";
            $page_setting_desc_col = $language_code . "_description";
            $page_setting_inst_col = $language_code . "_instruction";
            $constant_name = $language_code . "_value";
            try {

                //add column at service cteogry
                if (!Schema::hasColumn('service_category', $col_name)) {
                    Schema::table('service_category', function (Blueprint $table) use ($col_name) {
                        $table->string($col_name)->after('slug')->collation('utf8mb4_unicode_ci')->nullable();
                    });

                    //add value in new column
                    ServiceCategory::query()->where('name', '!=', "")
                        ->update([
                            $col_name => DB::raw("name"),
                        ]);
                }

                //add column at other_service_sub_category
                if (!Schema::hasColumn('other_service_sub_category', $col_name)) {
                    Schema::table('other_service_sub_category', function (Blueprint $table) use ($col_name) {
                        $table->string($col_name)->after('status')->collation('utf8mb4_unicode_ci')->nullable();
                    });

                    //add value in new column
                    OtherServiceCategory::query()->where('name', '!=', "")
                        ->update([
                            $col_name => DB::raw("name"),
                        ]);
                }

                //add column at user_package_booking_quantity
                if (!Schema::hasColumn('user_package_booking_quantity', $col_sub_category_name)) {
                    Schema::table('user_package_booking_quantity', function (Blueprint $table) use ($col_sub_category_name) {
                        $table->string($col_sub_category_name)->after('price_for_one')->collation('utf8mb4_unicode_ci')->nullable();
                    });

                    //add value in new column
                    UserPackageBookingQuantity::query()->where('sub_category_name', '!=', "")
                        ->update([
                            $col_sub_category_name => DB::raw("sub_category_name"),
                        ]);
                }

                //add column at page_settings
                if (!Schema::hasColumn('page_settings', $col_name)) {
                    Schema::table('page_settings', function (Blueprint $table) use ($col_name, $page_setting_desc_col) {
                        $table->string($col_name)->after('name')->nullable();
                        $table->longText($page_setting_desc_col)->after('description')->collation('utf8mb4_unicode_ci')->nullable();
                    });
                }

                // add column in language_constant table
                if (!Schema::hasColumn('language_constant', $constant_name)) {
                    Schema::table('language_constant', function (Blueprint $table) use ($constant_name) {
                        $table->string($constant_name)->after('value')->collation('utf8mb4_unicode_ci')->nullable();
                    });
                    //add value in new column
                    LanguageConstant::query()->where('value', '!=', "")
                        ->update([
                            $constant_name => $language_code . "-" . DB::raw("value"),
                        ]);
                }

                //add column in home_page_banner table
                if (!Schema::hasColumn('home_page_banner', $col_name)) {
                    Schema::table('home_page_banner', function (Blueprint $table) use ($col_name, $page_setting_desc_col) {
                        $table->string($col_name)->after('name')->nullable();
                        $table->string($page_setting_desc_col)->after('description')->collation('utf8mb4_unicode_ci')->nullable();
                    });

                    //add value in new column
                    HomePageBanner::query()->where('name', '!=', "")
                        ->update([
                            $col_name => $language_code . "-" . DB::raw("name"),
                            $page_setting_desc_col => $language_code . "-" . DB::raw("description"),
                        ]);
                }
            } catch (\Exception $e) {
                return redirect()->route('get:admin:language_lists')->with("error",  " Language Field is not properly added.");
            }

            return redirect()->route('get:admin:language_lists')->with("error",  " Language Code Already Added.");
        }
    }
    public function getAdminUpdateLanguageLists(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $lang_details = LanguageLists::query()->where('id', '=', $id)->first();

        if ($lang_details == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($lang_details->status == 1) {
            $status = $lang_details->status = 0;
        } else {
            $status = $lang_details->status = 1;
        }
        $lang_details->save();
        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }


    //get constant lists
    public function getAdminLanguageConstant(Request $request)
    {
        $language_constant = LanguageConstant::query()->orderBy('id', 'asc')->get();
        $language_lists = LanguageLists::query()->orderBy('id', 'asc')->get();
        $view = view('admin.pages.super_admin.language_constant.form_manage', compact('language_constant', 'language_lists'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }
    public function getAdminEditLanguageConstant(Request $request, $id = 0)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }

        $language_single_constant = LanguageConstant::query()->where('id', '=', $id)->first();
        $language_constant = LanguageConstant::query()->orderBy('id', 'asc')->get();

        $language_lists = LanguageLists::query()->orderBy('id', 'asc')->get();
        if ($language_single_constant != Null) {
            $view = view('admin.pages.super_admin.language_constant.form_manage', compact('language_single_constant', 'language_constant', 'language_lists'));
            if ($request->ajax()) {
                $view = $view->renderSections();
                return $this->adminClass->renderingResponce($view);
            }
            return $view;
        } else {
            return redirect()->route('get:admin:language_constant')->with("error",  " Language Constant value is not properly edited.");
        }
    }
    public function postAdminUpdateLanguageConstant(Request $request)
    {

        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }

        $constant_name = $request->get('constant_name');
        $constant_id = $request->get('id');
        $value = $request->get('value');
        if ($constant_id > 0) {
            $get_language_constant = LanguageConstant::query()->where('id', $constant_id)->first();
        } else {
            $get_language_constant = new LanguageConstant();
        }

        try {
            $language_list = LanguageLists::query()->select(
                'language_name as name',
                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_value') ELSE 'name' END) as constant_val")
            )->where('status', 1)->get();
            foreach ($language_list as $key => $language) {
                if (Schema::hasColumn('language_constant', $language->constant_val)) {
                    $get_language_constant->{$language->constant_val} = $request->get($language->constant_val);
                }
            }
        } catch (\Exception $e) {
            return redirect()->route('get:admin:language_constant')->with("error",  " Language Constant value is not properly added.");
        }

        $get_language_constant->constant_name = strtoupper(str_replace(" ", "_", $constant_name));
        $get_language_constant->value = $value;
        $get_language_constant->save();

        return redirect()->route('get:admin:language_constant')->with("success",  " Language Constant Added Successfully.");
    }

    public function getHomePageSliderList(Request $request)
    {

        $banner_image = HomePageBanner::query()->select('id', 'service_name', 'banner_image', 'service_id', 'status')->where('type', '=', 0)->get();

        $view = view('admin.pages.super_admin.home_page_slider.home_page_slider_list', compact('banner_image'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponse($view);
        }
        return $view;
    }

    public function getHomePageSlider($id = 0)
    {
        $slug = '';
        $service_category = ServiceCategory::query()->where('status', 1)->get();

        if (isset($id) && $id > 0) {
            $home_banner = HomePageBanner::query()->where('id', $id)->first();
            $view = view('admin.pages.super_admin.home_page_slider.home_page_slider', compact('slug', 'service_category', 'home_banner'));
        } else {
            $view = view('admin.pages.super_admin.home_page_slider.home_page_slider', compact('service_category'));
        }

        return $view;
    }

    public function AddEditHomePageSlider(Request $request)
    {
        //        dd($request->all());
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $banner_id = $request->get('banner_id');


        if ($request->get('service') != Null) {
            $service_name = ServiceCategory::query()->select('name')->where('id', $request->get('service'))->first();

            //echo $service_name;exit;
            if ($banner_id != Null) {
                $banner = HomePageBanner::query()->where('id', $banner_id)->where('type', '=', 0)->first();
            } else {
                $banner = new HomePageBanner();
            }
            if ($request->hasFile('image') != Null) {
                if (\File::exists(public_path('/assets/images/home-banner/' . $banner->banner_image))) {
                    \File::delete(public_path('/assets/images/home-banner/' . $banner->banner_image));
                }
                $destinationPath = public_path('/assets/images/home-banner/');
                $file = $request->file('image');
                $img = Image::read($file->getRealPath());
                $img->orient();
                $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                $img->resize(600, 240, function ($constraint) {
                    //$constraint->aspectRatio();
                })->save($destinationPath . $file_new);
                $banner->banner_image = $file_new;

                /*$file = $request->file('image');
                $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $file_new);
                $banner->banner_image = $file_new;*/
            }
            $banner->service_name = $service_name->name;
            $banner->service_id = $request->get('service');
            $banner->type = 0;
            $banner->save();

            Session::flash('success', 'Slider added successfully!');
            return redirect()->route('get:admin:home_page_slider_list');
        } else {
            Session::flash('error', 'Service not found!');
            return redirect()->route('get:admin:home_page_slider_list');
        }
    }

    public function getAdminHomeSliderChangeStatus(Request $request)
    {
        if ($this->is_restricted == 1) {
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
        $home_banner = HomePageBanner::query()->where('id', $id)->where('type', '=', 0)->first();
        if ($home_banner == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Home Slider not found'
            ]);
        }
        if ($home_banner->status == 1) {
            $home_banner->status = 0;
            $home_banner->save();
        } else {
            $home_banner->status = 1;
            $home_banner->save();
        }
        return response()->json([
            'success' => true,
            'status' => $home_banner->status
        ]);
    }

    public function getAdminDeleteHomeSlider(Request $request)
    {
        if ($this->is_restricted == 1) {
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
        $app_banner = HomePageBanner::query()->where('id', $id)->where('type', '=', 0)->first();
        if ($app_banner == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Service Slider not found'
            ]);
        }
        if (\File::exists(public_path('/assets/images/home-banner/' . $app_banner->banner_image))) {
            \File::delete(public_path('/assets/images/home-banner/' . $app_banner->banner_image));
        }
        $app_banner->delete();
        return response()->json([
            'success' => true
        ]);
    }

    //code set service category seq change
    public function getOrderServiceCategoryList()
    {

        $service_category_lists =  ServiceCategory::query()->whereIn('category_type', $this->on_demand_category_type)->orderBy('display_order', 'asc')->get();

        $view = view('admin.pages.super_admin.ordering_service_category.manage', compact('service_category_lists'));
        return $view;
    }
    public function postOrderingServiceCategorySorting(Request $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }

        $service_category_count = ServiceCategory::query()->count();
        if (count($request->get('category_sorting_type')) != $service_category_count) {
            Session::flash('error', 'Can not sorting service category!');
            return redirect()->back();
        }

        for ($i = 0; $i < count($request->get('category_sorting_type')); $i++) {
            $ordering_id = $i + 1;
            $service_categorie = ServiceCategory::where('id', $request->get('category_sorting_type')[$i])->first();
            if ($service_categorie != Null) {
                $service_categorie->display_order = $ordering_id;
                $service_categorie->save();
            }
        }
        return redirect()->route('get:admin:ordering_service_category_list');
    }
    public function getStoreCategoryChangeStatus(Request $request)
    {

        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Service Category not found'
            ]);
        }
        $service_category = ServiceCategory::query()->where('id', $id)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Service Category not found'
            ]);
        }
        if ($service_category->status == 1) {
            $service_category->status = 0;
            $service_category->save();
        } else {
            $service_category->status = 1;
            $service_category->save();
        }
        return response()->json([
            'success' => true,
            'status' => $service_category->status
        ]);
    }

    //code home page spot light section
    //code for feature store list
    public function getHomePageSpotLightList(Request $request)
    {
        $get_home_page_spot_light_list = HomepageSpotLight::query()
            ->select(
                "home_page_spot_light.*",
                "service_category.name as service_cat_name",
                "service_category.category_type"
            )
            ->join('service_category', 'service_category.id', '=', 'home_page_spot_light.service_cat_id')
            ->get();
        $home_page_spot_light_list = [];
        if ($get_home_page_spot_light_list != Null) {
            foreach ($get_home_page_spot_light_list as $key => $get_home_page_spot_light_detail) {
                if (in_array($get_home_page_spot_light_detail->category_type, $this->on_demand_category_type)) {
                    $provider_details = OtherServiceProviderDetails::query()->select(
                        'providers.id as provider_id',
                        DB::raw("CONCAT(providers.first_name,( IFNULL(providers.last_name,''))) AS provider_name")
                    )
                        ->join('providers', 'providers.id', '=', 'other_service_provider_details.provider_id')
                        ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
                        ->where('other_service_provider_details.provider_id', '=', $get_home_page_spot_light_detail->provider_id)
                        ->where('providers.status', '=', 1)
                        ->where('provider_services.status', '=', 1)
                        ->whereNull('providers.deleted_at')
                        ->where('provider_services.service_cat_id', '=', $get_home_page_spot_light_detail->service_cat_id)
                        ->first();
                } else {
                    $provider_details = Null;
                }
                if ($provider_details != Null) {
                    $home_page_spot_light_list[] = [
                        "id" => $get_home_page_spot_light_detail->id,
                        "service_cat_id" => $get_home_page_spot_light_detail->service_cat_id,
                        "provider_id" => $get_home_page_spot_light_detail->provider_id,
                        "status" => $get_home_page_spot_light_detail->status,
                        "created_at" => $get_home_page_spot_light_detail->created_at,
                        "updated_at" => $get_home_page_spot_light_detail->updated_at,
                        "provider_name" => $provider_details->provider_name,
                        "service_cat_name" => $get_home_page_spot_light_detail->service_cat_name,
                    ];
                }
            }
        }
        $view = view('admin.pages.super_admin.home_page_spot_light.manage', compact('home_page_spot_light_list'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponse($view);
        }
        return $view;
    }

    public function getAddEditHomePageSpotLight($id = 0)
    {
        $service_category = ServiceCategory::query()->where('status', 1)->whereIn('category_type', $this->spot_light_array)->get();
        if (isset($id) && $id > 0) {
            $home_page_spot_light = HomepageSpotLight::query()->where('id', $id)->first();

            $view = view('admin.pages.super_admin.home_page_spot_light.form', compact('service_category', 'home_page_spot_light'));
        } else {
            $view = view('admin.pages.super_admin.home_page_spot_light.form', compact('service_category'));
        }
        return $view;
    }

    public function postAdminUpdateHomePageSpotLight(Request $request)
    {
        if ($this->is_restricted == 1) {
            Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
            return redirect()->back();
        }
        $id = $request->get('id');
        if ($request->get('service') == Null) {
            Session::flash('error', 'Service not found!');
            return redirect()->route('get:admin:home_page_spot_light_list');
        }
        if ($id != Null) {
            $home_page_spot_light = HomepageSpotLight::query()->where('id', $id)->first();
            if ($home_page_spot_light == Null) {
                Session::flash('error', 'Spot light detail not found!');
                return redirect()->route('get:admin:home_page_spot_light_list');
            }
        } else {
            $home_page_spot_light = new HomepageSpotLight();
        }
        $home_page_spot_light->service_cat_id = $request->get('service');
        $home_page_spot_light->provider_id = $request->get('provider');
        $home_page_spot_light->save();

        isset($id) ? Session::flash('success', 'Home page Spot Light Updated successfully!') : Session::flash('success', 'Home page Spot Light added successfully!');

        return redirect()->route('get:admin:home_page_spot_light_list');
    }

    public function getAdminHomeSpotLightChangeStatus(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Homepage Spot Light not found'
            ]);
        }
        $home_page_spot_light = HomepageSpotLight::query()->where('id', '=', $id)->first();
        if ($home_page_spot_light == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Homepage Spot Light not found'
            ]);
        }
        if ($home_page_spot_light->status == 1) {
            $home_page_spot_light->status = 0;
            $home_page_spot_light->save();
        } else {
            $home_page_spot_light->status = 1;
            $home_page_spot_light->save();
        }
        return response()->json([
            'success' => true,
            'status' => $home_page_spot_light->status
        ]);
    }

    public function getAdminDeleteHomeSpotLight(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $id = $request->get('id');
        if ($id == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Homepage Spot Light not found'
            ]);
        }
        $home_page_spot_light = HomepageSpotLight::query()->where('id', '=', $id)->first();
        if ($home_page_spot_light == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Homepage Spot Light not found'
            ]);
        }
        $home_page_spot_light->delete();
        return response()->json([
            'success' => true
        ]);
    }

    public function postAjaxLoadStoreProvider(Request $request)
    {
        $service_category = $request->get('service_Category');
        $selected_provider = ($request->get('selected_provider') > 0) ? $request->get('selected_provider') : 0;
        if ($service_category == Null) {
            return response()->json([
                'success' => false
            ]);
        }
        $id = $request->get('id');
        $service_category_data = ServiceCategory::query()->select('id', 'category_type')->where('status', '=', 1)->where('id', '=', $service_category)->first();
        if ($service_category_data == Null) {
            return response()->json([
                'success' => false
            ]);
        }
        $service_category_type =  $service_category_data->category_type;

        $home_page_spot_light_Array = HomepageSpotLight::query()->select("provider_id")->where('provider_id', '!=', $selected_provider)->where('service_cat_id', '=', $service_category)->get()->toArray();
        if (in_array($service_category_type, $this->on_demand_category_type)) {
            $provider_list = OtherServiceProviderDetails::query()->select(
                'providers.id as provider_id',
                DB::raw("CONCAT(providers.first_name,( IFNULL(providers.last_name,'')),' - ',providers.country_code,providers.contact_number)  AS provider_name")
            )
                ->join('providers', 'providers.id', '=', 'other_service_provider_details.provider_id')
                ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
                ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
                ->where('providers.status', '=', 1)
                ->where('provider_services.status', '=', 1)
                ->where('service_category.id', '=', $service_category)
                ->whereNotIn('providers.id', $home_page_spot_light_Array)
                ->whereNull('providers.deleted_at')
                ->groupBy('providers.id')
                ->get();
        } else {
            $provider_list = [];
        }

        if ($provider_list != Null) {
            return response()->json([
                'success' => true,
                'data' => $provider_list
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    //change in admin can change user password
    public function getUpdateUserChangePassword(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $validator = Validator::make(
            $request->all(),
            [
                "user_id" => "required|numeric",
                "password" => "required",
                "confirm_password" => "required",
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'something went wrong'
            ]);
        }
        $user_id = $request->get('user_id');
        $users = User::query()->select('id')
            ->where('id', $user_id)->first();
        if ($users == Null) {
            return response()->json([
                'success' => false,
                'message' => 'something went wrong'
            ]);
        }
        $user = User::query()->where('id', '=', $users->id)->whereNull('users.deleted_at')->first();
        if ($user == Null) {
            return response()->json([
                'success' => false,
                'message' => 'something went wrong'
            ]);
        }
        $user->password = Hash::make($request->get('password'));
        $user->save();
        return response()->json([
            'success' => true,
            'message' => 'Password Successfully Changed'
        ]);
    }


    //change in admin can change driver and provider password
    public function getUpdateProviderChangePassword(Request $request)
    {
        if ($this->is_restricted == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $validator = Validator::make(
            $request->all(),
            [
                "provider_id" => "required|numeric",
                "password" => "required|min:6",
                "confirm_password" => "required|same:password",
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                "message" => $validator->errors()->first(),
            ]);
        }
        $provider_data = Provider::query()->select('providers.id')
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->where('provider_services.id', $request->get('provider_id'))->whereNull('providers.deleted_at')->first();
        if ($provider_data == Null) {
            return response()->json([
                'success' => false,
                'message' => 'something went wrong'
            ]);
        }
        $provider = Provider::query()->where('id', $provider_data->id)->whereNull('providers.deleted_at')->first();
        if ($provider == Null) {
            return response()->json([
                'success' => false,
                'message' => 'something went wrong'
            ]);
        }
        $provider->password = Hash::make($request->get('password'));
        $provider->save();
        return response()->json([
            'success' => true,
            'message' => 'Password Successfully Changed'
        ]);
    }

    // Chat Module
    public function postUpdateWebToken(Request $request)
    {
        $device_token = $request->get('web_token');

        if ($device_token == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $admin = Admin::query()->where('id', Auth::guard('admin')->user()->id)->first();
        if ($admin == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $admin->device_token = $device_token;
        $admin->save();
        return response()->json([
            'success' => true,
            'message' => 'success'
        ]);
    }

    public function getUserList(Request $request, $type_string, $type)
    {
        $view = view('admin.pages.super_admin.chat.manage', compact('type', 'type_string'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getUserChatMessageList(Request $request, $type_string, $type, $id)
    {
        $sender_id = "a_1";
        $view = view('admin.pages.super_admin.chat.chat', compact('sender_id', 'id', 'type', 'type_string'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getSendUserChatMessages(Request $request)
    {
        $user_fcm_token = $request->get('user_fcm');
        $message = $request->get('user_message');
        try {
            $data =  array(
                "user_id" => "a_1",
                "title" => "Admin",
                "message" => $message . "",
                "desc" => $message . "",
                "sound" => "default",
                "is_chat_notification" => "true"
            );

            $this->notificationClass->sendFlowNotification($user_fcm_token, $data, 0);

            return response()->json([
                'success' => true,
                'message' => 'success!',
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Some server issue!'
            ]);
        }
    }
}
