<?php

namespace App\Http\Controllers\Api;

use App\Classes\AdminClass;
use App\Classes\NotificationClass;
use App\Classes\OnDemandClassApi;
use App\Classes\UserClassApi;
use App\Models\AppVersionSetting;
use App\Models\EmailTemplates;
use App\Models\GeneralSettings;
use App\Models\HomePageBanner;
use App\Models\HomepageSpotLight;
use App\Models\LanguageLists;
use App\Models\OtherServiceCategory;
use App\Models\OtherServiceProviderDetails;
use App\Models\OtherServiceProviderPackages;
use App\Models\OtherServiceRatings;
use App\Models\PageSettings;
use App\Models\PromocodeDetails;
use App\Models\Provider;
use App\Models\PushNotification;
use App\Models\ServiceCategory;
use App\Models\UsedPromocodeDetails;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPackageBooking;
use App\Models\WorldCurrency;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mockery\Exception;
use function PHPUnit\Framework\isEmpty;

class CustomerApiController extends Controller
{
//        json response status [
//            0 => false,
//            1 => true,
//            2 => registration pending,
//            3 => app user blocked,
//            4 => app user access token not match,
//            5 => app user not found
//          ]

    private $userClassapi;
    private $on_demand_service_id_array;
    private $transport_service_id_array = [1, 2, 4];
    private $adminClass;
    private $notificationClass;
    private $onDemandClassApi;
    private $spot_display_limit  = 15;
    private $top_rated_display_limit  = 3;
    private $user_type = 0;

    private $delivery_category_type = [2];
    private $on_demand_category_type = [3,4];

    public function __construct(OnDemandClassApi $onDemandClassApi,UserClassApi $userClassapi, AdminClass $adminClass, NotificationClass $notificationClass)
    {
        $this->userClassapi = $userClassapi;
        $this->adminClass = $adminClass;
        $this->notificationClass = $notificationClass;
        $this->onDemandClassApi = $onDemandClassApi;

        //$this->on_demand_service_id_array = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30];
        $service_cat = ServiceCategory::query()->whereIN('category_type', [3, 4])->where('status', 1)->get()->pluck('id')->toArray();
        $this->on_demand_service_id_array = $service_cat;
    }

    public function postHomepage(Request $request) {
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable|numeric",
            "app_version" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_details = null;
        if ($request->get('user_id') != null && $request->get('access_token') != null){
            $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($user_details) == false) {
                return $user_details;
            }
            $user_details = User::query()->where("id", "=", $user_details->id)->first();
            if ($user_details == Null) {
                return response()->json([
                    'status' => 5,
                    //'message' => "User not found!",
                    'message' => __('user_messages.5'),
                    "message_code" => 5,
                ]);
            }
            $user_details->app_version = $request->get("app_version");
        }
        if ($user_details <> null) {
            $user_details->ip_address = $request->header('select-ip-address') != Null ? $request->header('select-ip-address') : Null;
            $user_details->time_zone = $request->header('select-time-zone') != Null ? $request->header('select-time-zone') : Null;
            $user_details->app_version = $request->get("app_version");
            $user_details->save();
        }

        $service_category_icon_url = url('/assets/images/service-category/');
        $homepage_slider_url = url('/assets/images/home-banner/');

        if ($user_details != null) {
            $language = $user_details->language;
        } else {
            $language = $request->header("select-language");
        }
        //$lang_prefix = $this->adminClass->get_langugae_fields($user_details->language);
        if($language !="en" && $language != "" && $language != "Null" ){
            $lang_prefix = $language."_";
        }else{
            $lang_prefix ="";
        }

//        $services = ServiceCategory::query()->select('service_category.id as service_category_id',
//            'service_category.'.$lang_prefix . 'name as service_category_name',
//            DB::raw("(CASE WHEN service_category.icon_name != '' THEN (concat('$service_category_icon_url','/',service_category.icon_name,'?v=0.3')) ELSE '' END) as service_category_icon"))
//            ->where('service_category.status', 1)->orderBy('service_category.display_order', 'asc')->get();

        $services=OtherServiceCategory::query()->select('service_category.id as service_category_id',
            'service_category.'.$lang_prefix . 'name as service_category_name',
            DB::raw("(CASE WHEN service_category.icon_name != '' THEN (concat('$service_category_icon_url','/',service_category.icon_name,'?v=0.3')) ELSE '' END) as service_category_icon"))
            ->join('service_category','service_category.id','=','other_service_sub_category.service_cat_id')
            ->where('service_category.status', 1)
            ->where('other_service_sub_category.status', 1)
            ->groupBy('other_service_sub_category.service_cat_id')
            ->orderBy('service_category.display_order', 'asc')->get();

        // Fallback: if no "other" services (e.g. house cleaning), return main service_category list so agents/UI get categories (e.g. id 19)
        if ($services->isEmpty()) {
            $services = ServiceCategory::query()->select('service_category.id as service_category_id',
                'service_category.'.$lang_prefix . 'name as service_category_name',
                DB::raw("(CASE WHEN service_category.icon_name != '' THEN (concat('$service_category_icon_url','/',service_category.icon_name,'?v=0.3')) ELSE '' END) as service_category_icon"))
                ->where('service_category.status', 1)
                ->orderBy('service_category.display_order', 'asc')->get();
        }

//        return $services->count();
//        $homepage_slider = HomePageBanner::query()->select('home_page_banner.service_id as service_category_id',
//            'service_category.'.$lang_prefix . 'name as service_category_name',
//            DB::raw("(CASE WHEN home_page_banner.banner_image != '' THEN (concat('$homepage_slider_url','/',home_page_banner.banner_image)) ELSE '' END) as homepage_slider_image"),
//            DB::raw("(CASE WHEN service_category.icon_name != '' THEN (concat('$service_category_icon_url','/',service_category.icon_name,'?v=0.3')) ELSE '' END) as service_category_icon"))
//            ->leftJoin('service_category','service_category.id','=','home_page_banner.service_id')
//            ->where('home_page_banner.type','=',0)
//            ->where('home_page_banner.status', 1)->get();

//        $provider_image_url =  url('/assets/images/profile-images/provider/');
//        $get_top_rated_providers = OtherServiceProviderDetails::query()->select('other_service_provider_details.provider_id as provider_id',
//            'providers.first_name as provider_name','other_service_provider_details.rating as provider_rating',
//            DB::raw("(CASE WHEN providers.avatar != '' THEN (concat('$provider_image_url','/',providers.avatar)) ELSE '' END) as provoider_image"),
//            'service_category.'.$lang_prefix . 'name as service_category_name',)
//            ->join('provider_services','provider_services.provider_id','other_service_provider_details.provider_id')
//            ->join('providers','providers.id','=','other_service_provider_details.provider_id')
//            ->join('service_category','service_category.id','=','provider_services.service_cat_id');
//        $get_top_rated_providers_total =    $get_top_rated_providers->count();
//        $get_top_rated_providers_list  =    $get_top_rated_providers->limit($this->top_rated_display_limit)->get()->toArray();
//        $top_rated_array = array(
//            'view_all_bnt' => ($get_top_rated_providers_total > $this->top_rated_display_limit)?1:0,
//            'top_rated_provider_lists' => $get_top_rated_providers_list
//        );
        if(count($services) > 0){
            return response()->json([
                "status" => 1,
                'message' => __('user_messages.1'),
                "message_code" => 1,
                "total_ongoing_order" => 0,
                "total_unread_message" => 0,
                "services" => $services,
//            "home_slider" => $homepage_slider,
            ]);
        }
        else{
            return response()->json([
                "status" => 1,
                'message' => __('user_messages.332'),
                "message_code" => 332,
            ]);
        }

    }

    /**
     * Get user (customer) details by user_id.
     * Returns: first_name, last_name, email, contact_number, profile_image, gender, etc.
     */
    public function postUserDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|integer",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_id = (int) $request->get('user_id');
        $user = User::query()
            ->select(
                'id',
                'first_name',
                'last_name',
                'email',
                'contact_number',
                'country_code',
                'avatar',
                'gender',
                'currency',
                'language',
                'invite_code',
                'emergency_contact',
                'verified_at',
                'status'
            )
            ->where('id', $user_id)
            ->whereNull('deleted_at')
            ->first();

        if ($user === null) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.5') ?? "User not found",
                "message_code" => 5,
            ]);
        }

        $profile_image = null;
        if ($user->avatar != null) {
            $profile_image = filter_var($user->avatar, FILTER_VALIDATE_URL)
                ? $user->avatar
                : url('/assets/images/profile-images/customer/' . $user->avatar);
        }

        return response()->json([
            "status" => 1,
            "message" => __('user_messages.1') ?? "Success",
            "message_code" => 1,
            "data" => [
                "user_id" => $user->id,
                "first_name" => $user->first_name ?? "",
                "last_name" => $user->last_name ?? "",
                "email" => $user->email ?? "",
                "contact_number" => $user->contact_number ?? "",
                "country_code" => $user->country_code ?? "",
                "profile_image" => $profile_image,
                "gender" => $user->gender ?? 0,
                "currency" => $user->currency ?? "",
                "language" => $user->language ?? "",
                "referral_code" => $user->invite_code ?? "",
                "emergency_contact" => $user->emergency_contact ?? "",
                "verified" => $user->verified_at != null ? 1 : 0,
                "status" => $user->status ?? 1,
            ],
        ]);
    }

    public function postCustomerAddCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "holder_name" => "nullable",
            "card_number" => "required",
            "month" => "required|numeric",
            "year" => "required|numeric",
            "cvv" => "required|numeric",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }

        return $this->userClassapi->addCardManage($this->user_type, $request->get('user_id'), $request->all());
    }

    public function postCustomerRemoveCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "card_id" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_id = $request->get("user_id");
        $card_id = $request->get("card_id");
        $user_check = $this->userClassapi->checkUserAllow($user_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }
        return $this->userClassapi->deleteCardManage($this->user_type, $user_id, $card_id);
    }

    public function postCustomerCardList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }
        return $this->userClassapi->manageCardList($this->user_type, $request->get('user_id'));
    }

    public function postCustomerAddWalletBalance(Request $request) {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "amount" => "required|numeric",
            //"card_id" => "required",
            "payment_method_type" => "nullable|in:1,0",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_details) == false) {
            return $user_details;
        }
        return $this->userClassapi->addWalletBalance($this->user_type, $request->get('user_id'), $user_details->first_name, $user_details->currency,  $request->all());
    }

    public function postCustomerWalletTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }
        $user_language = $user_check->language == null ? 'en' : $user_check->language;
        return $this->userClassapi->getWalletTransactionList($this->user_type, $request->get('user_id'), $user_check->currency, $user_language);
    }

    public function postCustomerGetWalletBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable|numeric"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_check = null;
        if ($request->get('user_id') <> null && $request->get('access_token') <> null){
            $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($user_check) == false) {
                return $user_check;
            }
        }

        if ($user_check != null) {
            $language = $user_check->language;
            $currency = $user_check->currency;
        } else {
            $language = $request->header("select-language");
            $currency = $request->header("select-currency");
        }

        if($language !="en" && $language != "" && $language != "Null" ){
            $language = $language."_";
        }else{
            $language ="";
        }

        return $this->userClassapi->getWalletBalance($this->user_type, $request->get('user_id'), $currency);
    }

    public function postCustomerSearchWalletTransferUserList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "search" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }
        return $this->userClassapi->searchWalletTransferUserList($this->user_type, $request->get('user_id'), $request->get("search"));
    }

    public function postCustomerWalletToWalletTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "amount" => "required|numeric",
            "transfer_id" => "required|numeric",
            "wallet_provider_type" => "required|in:0,3"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }
        return $this->userClassapi->walletToWalletTransfer($this->user_type, $request->get('user_id'), $user_check->first_name, $user_check->currency, $request->all());
    }

    public function postCustomerAddAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
                "access_token" => "required|numeric"
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }

        return $this->userClassapi->userAddressManage($request, true, false, false);
    }

    public function postCustomerEditAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
                "access_token" => "required|numeric"
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }

        return $this->userClassapi->userAddressManage($request, false, true, false);
    }

    public function postCustomerDeleteAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
                "access_token" => "required|numeric"
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }

        return $this->userClassapi->userAddressManage($request, false, false, true);
    }

    public function postCustomerAddressList(Request $request)
    {
        $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
                "access_token" => "required|numeric"
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }
        $addresses = UserAddress::select('id', 'address_type', 'address', 'lat_long', 'flat_no', 'landmark')->where('user_id', $request->get('user_id'))->where('status', 1)->get();
        if (!$addresses->isEmpty()) {
            foreach ($addresses as $address) {
                $lat_long = explode(',', $address->lat_long);
                $lat = $lat_long[0];
                $long = $lat_long[1];
                $address_list[] = [
                    'address_id' => $address->id,
                    'type' => $address->address_type,
                    'address' => $address->address,
                    'lat' => $lat,
                    'long' => $long,
                    'flat_no' => $address->flat_no,
                    'landmark' => $address->landmark
                ];
            }
            return response()->json([
                "status" => 1,
//                "message" => "success!",
                'message' => __('user_messages.1'),
                "message_code" => 1,
                "address_list" => $address_list,
                "address_limit" => 5
            ]);
        } else {
            return response()->json([
                "status" => 1,
//                "message" => "success!",
                'message' => __('user_messages.1'),
                "message_code" => 1,
                "address_list" => [],
                "address_limit" => 5
            ]);
//            return response()->json([
//                "status" => 0,
////                "message" => "address not found!",
//                'message' => __('user_messages.9'),
//                "message_code" => 9,
//            ]);
        }
    }

    public function postStorePromocodeList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|integer",
            "access_token" => "required|numeric",
            "service_category_id" => "required|numeric"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_details) == false) {
            return $user_details;
        }

        $current_date = date('Y-m-d H:i:s');
        $service_cat_id = $request->get('service_category_id');
        $promocode_list = PromocodeDetails::query()->where('service_cat_id', $service_cat_id)
            ->where('status', 1)
            ->whereDate('expiry_date_time','>=',$current_date)
            ->get();
        if ($promocode_list == Null) {
            return response()->json([
                "status" => 1,
//                "message" => 'success!',
                'message' => __('user_messages.1'),
                "message_code" => 1,
                "promocode_list" => []
            ]);
        } else {
            $promo_list = [];
            foreach ($promocode_list as $promocode) {
                try {
                    $get_user_used = UsedPromocodeDetails::query()->where('user_id', $user_details->id)->where('promocode_id', $promocode->id)
                        ->whereIn("status",[0,1])
                        ->count();
                } catch (\Exception $e) {
                    $get_user_used = 0;
                }
                if ($promocode->coupon_limit != 0) {
                    if ($promocode->coupon_limit > $promocode->total_usage) {
                        if ($promocode->usage_limit > $get_user_used) {
                            $promo_list[] = [
                                'promocode_id' => $promocode->id,
                                'promocode_name' => $promocode->promo_code,
                                'discount_amount' => $promocode->discount_amount,
                                'discount_type' => $promocode->discount_type,
                                'min_order_amount' => $promocode->min_order_amount != Null ? $promocode->min_order_amount : 0,
                                'max_discount_amount' => $promocode->max_discount_amount != Null ? $promocode->max_discount_amount : 0,
                                'promocode_description' => $promocode->description != Null ? $promocode->description : '',
                            ];
                        }
                    }
                } else {
                    if ($promocode->usage_limit > $get_user_used) {
                        $promo_list[] = [
                            'promocode_id' => $promocode->id,
                            'promocode_name' => $promocode->promo_code,
                            'discount_amount' => $promocode->discount_amount,
                            'discount_type' => $promocode->discount_type,
                            'min_order_amount' => $promocode->min_order_amount != Null ? $promocode->min_order_amount : 0,
                            'promocode_description' => $promocode->description != Null ? $promocode->description : '',
                        ];
                    }
                }
            }
            return response()->json([
                "status" => 1,
//                "message" => 'success!',
                'message' => __('user_messages.1'),
                "message_code" => 1,
                "promocode_list" => $promo_list
            ]);
        }
    }

    public function postCountryAndCurrencyList(Request $request)
    {
//        $country_list = WorldCountry::query()->select('id as country_id', 'country_name', 'country_code')->where('status', 1)->get();
        $country_list = LanguageLists::query()->select('id as country_id', 'language_name as country_name', 'language_code as country_code')->where('status', 1)->orderBy('id','asc')->get();
        $currency_list = WorldCurrency::query()->select('id as currency_id', 'currency_code as currency_name', 'symbol as currency_symbol')->where('status', 1)->get();
        $general_settings = request()->get("general_settings");
        $app_key = $general_settings?->app_key ?? '';
        return response()->json([
            "status" => 1,
//            "message" => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
            "app_key" => $app_key,
            "country_list" => $country_list,
            "currency_list" => $currency_list
        ]);
    }

    //end code for testing payment

    public function postSupportPages()
    {
        $page_settings = PageSettings::get();
        if (!$page_settings->isEmpty()) {
            $support_pages = [];
            foreach ($page_settings as $page_setting) {
                $support_pages[] = [
                    'id' => $page_setting->id,
                    'page_name' => $page_setting->name,
                    'page_title' => ucwords(strtolower(str_replace('-', " ", $page_setting->name))),
                    'description' => $page_setting->description != Null ? $page_setting->description : ''
                ];
            }
            return response()->json([
                "status" => 1,
                "message" => "success!",
                "message_code" => 1,
                "pages" => $support_pages
            ]);
        } else {
            return response()->json([
                "status" => 0,
                "message" => "data not found!",
                "message_code" => 0
            ]);
        }
    }

    public function postMyCheckoutSupportPages(Request $request)
    {
        $validator = Validator::make($request->all(), [
                "user_id" => "nullable|numeric",
                "access_token" => "nullable|numeric",
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_check = null;
        if ($request->get('user_id') <> null && $request->get('access_token') <> null){
            $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($user_check) == false) {
                return $user_check;
            }
        }

        if ($user_check <> null){
            $lang_prefix = $user_check->language;
        } else {
            $lang_prefix = $request->header("select-language");
            $currency = $request->header("select-currency");
        }

        $name = $lang_prefix != 'en' ? $lang_prefix.'_name' : 'name';
        $description = $lang_prefix != 'en' ? $lang_prefix.'_description' : 'description';

        $page_settings = PageSettings::query()
            ->select('id',$name,$description)
            ->where('type', 1)->get();
        if (!$page_settings->isEmpty()) {
            $support_pages = [];
            foreach ($page_settings as $page_setting) {
                $support_pages[] = [
                    'id' => $page_setting->id,
                    'page_name' => $page_setting->$name,
                    'page_title' => $lang_prefix == 'en' ? (str_replace('-', " ", $page_setting->name)) : $page_setting->$name,
                    'description' => $page_setting->$description != Null ? $page_setting->$description : ''
                ];
            }
            return response()->json([
                "status" => 1,
//                "message" => "success!",
                "message" => __('user_messages.1'),
                "message_code" => 1,
                "pages" => $support_pages
            ]);
        } else {
            return response()->json([
                "status" => 0,
//                "message" => "data not found!",
                "message" => __('user_messages.0'),
                "message_code" => 0
            ]);
        }
    }

    public function postMyServiceSupportPages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "nullable|integer",
            "access_token" => "nullable|numeric",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        if($request->get('provider_id') != Null) {
            $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($provider_details) == false) {
                return $provider_details;
            }
            $lang_prefix = $this->adminClass->get_langugae_fields($provider_details->language);
        } else{
            $lang_prefix = 'en_';
        }
        $name = $lang_prefix != 'en_' ? $lang_prefix.'name' : 'name';
        $description = $lang_prefix != 'en_' ? $lang_prefix.'description' : 'description';
        $page_settings = PageSettings::query()
            ->select('id',$name,$description)
            ->where('type', 2)->get();
        if (!$page_settings->isEmpty()) {
            $support_pages = [];
            foreach ($page_settings as $page_setting) {
                $support_pages[] = [
                    'id' => $page_setting->id,
                    'page_name' => $page_setting->$name,
                    'page_title' => $lang_prefix == 'en_' ? (str_replace('-', " ", $page_setting->name)) : $page_setting->$name,
                    'description' => $page_setting->$description != Null ? $page_setting->$description : ''
                ];
            }
            return response()->json([
                "status" => 1,
//                "message" => "success!",
                "message" => __('provider_messages.1'),
                "message_code" => 1,
                "pages" => $support_pages
            ]);
        } else {
            return response()->json([
                "status" => 0,
//                "message" => "data not found!",
                "message" => __('provider_messages.0'),
                "message_code" => 0
            ]);
        }
    }

    public function postMyShopperSupportPages()
    {
        $page_settings = PageSettings::query()->where('type', 5)->get();
        if (!$page_settings->isEmpty()) {
            $support_pages = [];
            foreach ($page_settings as $page_setting) {
                $support_pages[] = [
                    'id' => $page_setting->id,
                    'page_name' => $page_setting->name,
                    'page_title' => (str_replace('-', " ", $page_setting->name)),
                    'description' => $page_setting->description != Null ? $page_setting->description : ''
                ];
            }
            return response()->json([
                "status" => 1,
                "message" => "success!",
                "message_code" => 1,
                "pages" => $support_pages
            ]);
        } else {
            return response()->json([
                "status" => 0,
                "message" => "data not found!",
                "message_code" => 0
            ]);
        }
    }


    public function postFirebaseSecurityRules(Request $request)
    {
        $general_settings = GeneralSettings::select('map_key')->first();
        if ($general_settings == Null || $general_settings->map_key == Null) {
            return "";
        }

        $server_key = "AIzaSyCuT2LWAMblulfM1e0ncSP8swuv_Jzes7g";
        $url = $request->get('url');
        if ($url == Null) {
            return "";
        }
        //$fcmUrl = $url . $server_key . "&components=country:ph";
        $fcmUrl = $url . $server_key;
        $final_url = str_replace(' ', '%20', $fcmUrl);

        //$url2 = parse_url($fcmUrl);
        //$scheme = $url2["scheme"];
        //$host = $url2["host"];
        //$path = $url2["path"];
        //$url3 = str_replace(' ', '%20', $url2["query"]);
        //$query1 = $url3;
        //$final_url= $scheme."://".$host.$path."?".$query1;

        $fcmData = [];
        $headers = [
//            'Authorization: key=' . $server_key,
            'Content-Type: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $final_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmData));
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);


        return $response;
    }

    public function postFacebookProviderDataDeletion(Request $request){
        $reference = Str::random(10);
        $domain_url = route('front');
        $status_url = $domain_url.'/deletion/'.$reference;
        $confirmation_code = $reference;
        try{
            $signed_request = $request->get('signed_request');
            $data = $this->userClassapi->parse_signed_request($signed_request,'provider');
            $user_id = $data['user_id'];

            $provider = Provider::query()->where('login_type','facebook')->where('login_id',$user_id)->first();

            if($provider != NUll){
                $provider->login_id = $reference;
                $provider->email = Null;
                $provider->contact_number = Null;
                $provider->access_token = Null;
                $provider->device_token = Null;
                $provider->save();
            }

            $data = [
                'url' => $status_url,
                'confirmation_code' => $confirmation_code
            ];
        } catch(\Exception $e){
            $data = [
                'url' => $status_url,
                'confirmation_code' => $confirmation_code
            ];
        }

        return response()->json($data);
    }

    public function postFacebookUserDataDeletion(Request $request){
        $reference = Str::random(10);
//        $status_url = 'https://fox-jek.startuptrinity.com/deletion/'.$reference;
        $domain_url = route('front');
        $status_url = $domain_url.'/deletion/'.$reference;
        $confirmation_code = $reference;
        try{
            $signed_request = $request->get('signed_request');
            $data = $this->userClassapi->parse_signed_request($signed_request,'user');
            $user_id = $data['user_id'];

            $user = User::query()->where('login_type','facebook')->where('login_id',$user_id)->first();

            if($user != Null){
                $user->login_id = $reference;
                $user->email = Null;
                $user->contact_number = Null;
                $user->access_token = Null;
                $user->device_token = Null;
                $user->save();
            }

            $data = [
                'url' => $status_url,
                'confirmation_code' => $confirmation_code
            ];
        } catch(\Exception $e){
            $data = [
                'url' => $status_url,
                'confirmation_code' => $confirmation_code
            ];
        }

        return response()->json($data);
    }

    //api for home page spot light list
    public function postHomePageSpotLightList(Request $request){
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable|numeric",
            "lat" => "required",
            "long" => "required",
            "view_all" => "required|in:0,1",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_id = $request->get('user_id');
        $access_token = $request->get('access_token');
        $address_lat = $request->get('lat');
        $address_long = $request->get('long');
        $view_all = $request->get('view_all') - 0;

        $language = "";
        $user_currency = Null; $user_details = Null;
        if ($user_id != Null && $access_token != Null) {
            $user_details = $this->userClassapi->checkUserAllow($user_id, $access_token);
            if ($decoded['status'] = json_decode($user_details) == false) {
                return $user_details;
            }
        }

        if ($user_details <> null){
            $language = $user_details->language;
            $user_currency = WorldCurrency::query()->where('symbol', $user_details->currency)->first();
            if ($user_currency == Null) {
                $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
            }
        } else {
            $language = $request->header("select-language");
            $currency = $request->header("select-currency");
            if ($currency != null) {
                $user_currency = WorldCurrency::query()->where('currency_code', $currency)->first();
            }
        }

        if ($language != "en" && $language != "" && $language != "Null") {
            $lang_prefix = $language . "_";
        } else {
            $lang_prefix = "";
        }
        $store_image_path = url('/assets/images/store-images/');
        $service_category_icon_url = url('/assets/images/service-category/');

        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $user_currency->ratio;
        $currency_symbol = $user_currency->symbol;

        $feature_const = config('global.lang_constant.IN_SPOT_LIGHT.'.$lang_prefix.'value') ."";

        $home_page_feature_all_store_lists['status'] = 1;
//        $home_page_feature_all_store_lists['message'] = "success!";
        $home_page_feature_all_store_lists['message'] = __('user_messages.1');
        $home_page_feature_all_store_lists['message_code'] =1;
        $home_page_feature_all_store_lists['display_title_name'] = $feature_const;

        $get_home_page_spot_light_list = HomepageSpotLight::query()
            ->select("home_page_spot_light.*",
                "service_category." . $lang_prefix . "name as service_category_name",
                "service_category.category_type",
                DB::raw("(CASE WHEN service_category.icon_name != '' THEN (concat('$service_category_icon_url','/',service_category.icon_name,'?v=0.3')) ELSE '' END) as service_category_icon")
            )
            ->join('service_category','service_category.id','=','home_page_spot_light.service_cat_id')
//             ->join('provider_services','provider_services.provider_id','=','home_page_spot_light.provider_id')
//             ->join('provider_services', function($join)
//             {
//                 $join->on('provider_services.service_cat_id', '=', 'home_page_spot_light.service_cat_id')
//                     ->on('provider_services.provider_id', '=', 'home_page_spot_light.provider_id');
//             })
//             ->where('provider_services.current_status','=',1)
            ->where('service_category.status','=',1)
            ->where('home_page_spot_light.status','=',1)
            ->orderBy('home_page_spot_light.id','desc')
            ->get();
//        \Log::info('get_home_page_spot_light_list');
//        \Log::info($get_home_page_spot_light_list);
        $home_page_spot_light_list = [];
        if ($get_home_page_spot_light_list != Null){
            foreach ($get_home_page_spot_light_list as $key => $get_home_page_spot_light_detail){

                if(in_array($get_home_page_spot_light_detail->category_type, $this->on_demand_category_type)){
                    $provider_details = OtherServiceProviderDetails::query()->select('providers.id as provider_id',
                        'other_service_provider_details.rating as average_ratings','other_service_provider_details.total_completed_order as total_completed_order',
                        DB::raw("CONCAT(providers.first_name,' ') AS provider_name"),
                        'providers.avatar',
                        'provider_services.id as provider_service_id',
                        'providers.service_radius',
                        'other_service_provider_details.time_slot_status',
                        DB::raw('(SELECT AVG(rating) FROM other_service_rating WHERE other_service_rating.provider_id = providers.id AND other_service_rating.status = 1) AS average_rating'),
                        DB::raw(('other_service_provider_details.lat,other_service_provider_details.long, ( round( 6367 * acos( cos( radians(' . $address_lat . ') ) * cos( radians( other_service_provider_details.lat ) ) * cos( radians( other_service_provider_details.long ) - radians(' . $address_long . ') ) + sin( radians(' . $address_lat . ') ) * sin( radians( other_service_provider_details.lat ) ) ),2 ) ) AS distance'))
                    )
                        ->join('providers','providers.id','=','other_service_provider_details.provider_id')
                        ->join('provider_services','provider_services.provider_id','=','providers.id')
                        ->join('other_service_provider_packages', 'other_service_provider_packages.provider_service_id', '=', 'provider_services.id')
                        ->where('other_service_provider_details.provider_id', '=', $get_home_page_spot_light_detail->provider_id)
                        ->where('providers.status','=',1)
                        ->where('provider_services.status','=',1)
                        ->where('provider_services.current_status','=',1)
                        ->where('other_service_provider_packages.status', '=', 1)
//                       ->where('other_service_provider_details.time_slot_status','=',1)
                        ->where('provider_services.service_cat_id','=',$get_home_page_spot_light_detail->service_cat_id)
                        ->whereNull('providers.deleted_at')->havingRaw('providers.service_radius > distance');
                    $this->userClassapi->checkDayShowRecord($provider_details,0);
                    //
                    $provider_details = $provider_details->first();


                    if ($provider_details != ''){
                        if ($provider_details->avatar != Null && $provider_details != Null) {
                            if (filter_var($provider_details->avatar, FILTER_VALIDATE_URL) == true) {
                                $provider_profile_image = $provider_details->avatar;
                            } else {
                                $provider_profile_image = url('/assets/images/profile-images/provider/' . $provider_details->avatar);
                            }
                        } else {
                            $provider_profile_image = "";
                        }
                        $total_provider_rating = OtherServiceRatings::query()->where("provider_id",$provider_details->provider_id)->count();
//                        $package_check=OtherServiceProviderPackages::where('provider_service_id','=',$provider_details->provider_service_id)
//                            ->where('service_cat_id','=',$get_home_page_spot_light_detail->service_cat_id)
//                            ->where('status','=',1)->count();

//                        \Log::info('package_check');
//                        \Log::info($package_check);
                        /* check time provide has any selected time slot or not */
                        $selected_timing = $this->userClassapi->getProviderSelectedTiming($provider_details->provider_id);
//                       if($get_home_page_spot_light_detail->list_type == 1){
//                        {
                            $home_page_spot_light_list[] = [
                                "id" => $get_home_page_spot_light_detail->id,
                                "service_category_id" => $get_home_page_spot_light_detail->service_cat_id,
                                "service_category_name" => $get_home_page_spot_light_detail->service_category_name,
                                "service_category_icon" => $get_home_page_spot_light_detail->service_category_icon,
                                "provider_id" => $provider_details->provider_id,
                                "provider_name" => $provider_details->provider_name,
                                "average_ratings" => ($provider_details->average_rating > 0) ? round($provider_details->average_rating,2) : 0,
                                "eta_delivery_time" => 0,
                                //"total_completed_order" => ($provider_details->total_completed_order > 0) ? $provider_details->total_completed_order -0 : 0,
                                "distance" => ($provider_details->service_radius > 0) ? $provider_details->service_radius  : 0,
                                "no_of_ratings"=>$total_provider_rating,
                                "image" => $provider_profile_image,
                                "time_slot_status" => (isset($selected_timing) && $selected_timing == true) ? $provider_details->time_slot_status : 0,
                            ];
                        }

                    //}
                }
                if(($view_all == 0) && count($home_page_spot_light_list) == $this->spot_display_limit){
                    break;
                }
            }
        }

        $home_page_feature_all_store_lists['spot_light_list'] = $home_page_spot_light_list;
        $home_page_feature_all_store_lists['view_all_btn'] = count($home_page_spot_light_list) < $this->spot_display_limit?0:1;
        return $home_page_feature_all_store_lists;
    }
    //code check api splash screen data
    public function postAppVersionCheck(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "app_type" => "required|in:0,1,2,3",
            "login_device" => "required|in:1,2,3,4",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $general_settings = request()->get("general_settings");
        $app_type = $request->get('app_type');;
        $login_device = $request->get('login_device');
        $server_time_zone = ($general_settings->default_server_timezone != NUll)?$general_settings->default_server_timezone:"";
        $app_key =  ($general_settings->app_key != NUll)?$general_settings->app_key:"";
        if($app_type != "" && $login_device > 0)
        {
            $app_version_details = AppVersionSetting::query()
                ->where("app_type","=",$app_type)
                ->where("app_device_type", "=", $login_device)
                //            ->where("forcefully_type", "=", 1)
                ->orderBy("id","desc")
                ->first();
            $is_forcefully_update = 0;
            $version_name = "";

            if ($app_version_details != Null){
                $is_forcefully_update = $app_version_details->forcefully_type - 0;
                $version_name = $app_version_details->version_name;
            }

            return response()->json([
                "status" => 1,
//                "message" => "success!",
                'message' => __('user_messages.1'),
                "message_code" => 1,
                "app_version" => $version_name. "",
                "is_forcefully_update" => $is_forcefully_update,
                "app_key" => $app_key,
                "server_time_zone" => $server_time_zone,
                "cash_payment" => $general_settings->cash_payment,
                "wallet_payment" => $general_settings->wallet_payment,
                "card_payment" => $general_settings->card_payment,
            ]);
        }else{
            return response()->json([
                "status" => 1,
//                "message" => "success!",
                'message' => __('user_messages.1'),
                "message_code" => 1,
                "app_version" => "",
                "is_forcefully_update" => 0,
                "app_key" => $app_key,
                "server_time_zone" => $server_time_zone,
                "cash_payment" => $general_settings->cash_payment,
                "wallet_payment" => $general_settings->wallet_payment,
                "card_payment" => $general_settings->card_payment,
            ]);
        }

    }

    //remove provider account(softdelete)
    public function postUserRemoveAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_id = $request->get('user_id');
        $user_check = $this->userClassapi->checkUserAllow($user_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }
        $user_running_packages = UserPackageBooking::query()->where('user_id', '=', $user_id)
            ->where(function ($query) {
                $query->whereNotIn('status', [4,5,9,10])
                    ->orWhere(function($query2){
                        $query2->where('status',9)->where('payment_status',0);
                    });
            })->count();

        if ($user_running_packages > 0) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.301'),
                "message_code" => 5,
            ]);
        }

        $get_provider_details = User::query()->where('id', $request->get("user_id"))->first();
        if ($get_provider_details == Null) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.5'),
                "message_code" => 5,
            ]);
        }
        if ($get_provider_details->fix_user_show == 1) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.313'),
                "message_code" => 15,
            ]);
        }

        $get_provider_details->delete();

        return response()->json([
            "status" => 1,
            "message" => __('user_messages.1'),
            "message_code" => 1,
        ]);
    }

    public function postProviderRemoveAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $access_token = $request->get('access_token');
        $notificationCls = new NotificationClass();
        $adminCls = new AdminClass();
        $onDemandClass = new OnDemandClassApi($notificationCls, $adminCls);
        $provider_details = $onDemandClass->providerRegisterAllow($provider_id, $access_token);
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

//        $user_running_delivery = 0;
//        $provider_details_id = OtherServiceProviderDetails::query()->where('provider_id','=',$provider_id)->first();
//        if($provider_details_id != Null)
//        {
//            $provider_detail_id = $provider_details_id->id;
//            $user_running_packges = UserPackageBooking::query()->where('provider_id','=',$provider_detail_id)->whereNotIn('status',[4,5,9,10])->count();
//        }
        $user_running_packges = UserPackageBooking::query()
            ->where('provider_id', '=', $provider_id)
            ->where(function ($query) {
                $query->whereNotIn('status', [4, 5, 9, 10])
                    ->orWhere(function ($query2) {
                        $query2->where('status', 9)->where('payment_status', 0);
                    });
            })
            ->count();
        if ($user_running_packges > 0) {
            return response()->json([
                "status" => 0,
                "message" => __('provider_messages.264'),
                "message_code" => 5,
            ]);
        }

        $get_provider_details = Provider::query()->where('id', $provider_id)->first();
        if ($get_provider_details == Null) {
            return response()->json([
                "status" => 0,
                "message" => __('provider_messages.73'),
                "message_code" => 73,
            ]);
        }
        if ($get_provider_details->fix_user_show == 1) {
            return response()->json([
                "status" => 0,
                "message" => __('provider_messages.313'),
                "message_code" => 15,
            ]);
        }

        OtherServiceRatings::where('provider_id',$provider_id)->delete();

        $get_provider_details->delete();
        return response()->json([
            "status" => 1,
            "message" => __('provider_messages.1'),
            "message_code" => 1,
        ]);
    }

    // Mass Notification
    public function postCustomerMassNotificationList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "page" => "nullable|numeric",
            "per_page" => "nullable|numeric"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_check = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_check) == false) {
            return $user_check;
        }

        $per_page = 10;
        if ($request->get('per_page') != Null) {
            $per_page = $request->get('per_page');
        }
        $mass_notification_list = PushNotification::query()->select('id', 'title',
            DB::raw("(CASE WHEN title != '' THEN title ELSE '' END) as title"),
            'message', DB::raw("(CASE WHEN created_at IS NOT NULL THEN created_at ELSE '' END) as datetime"))
            ->whereIn('notification_type', [1, 2])
            ->orderBy('id', 'desc')
            ->paginate($per_page);

        $get_items_list = $mass_notification_list->items();
        $current_page = $mass_notification_list->currentPage();
        $last_page = $mass_notification_list->lastPage();
        $total = $mass_notification_list->total();
        return response()->json([
            "status" => 1,
            "message" => __('user_messages.1'),
            "message_code" => 1,
            "current_page" => $current_page - 0,
            "last_page" => $last_page - 0,
            "total" => $total - 0,
            "mass_notification_list" => $get_items_list
        ]);

    }

     /* This function sends a request to the Google Places Autocomplete API to fetch place suggestions based on a user's input */
    public function postAutocompleteGooglePlaces(Request $request)
    {
        try{
            // fetch general settings
            $general_settings = GeneralSettings::query()->select('server_map_key')->first();

            // general settings null or mapKey null then return something went wrong
            if($general_settings == Null || $general_settings->server_map_key == Null){
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.9'), // "Sorry, something went wrong"
                    "message_code" => 9,
                ]);
            }

            // Google Places Autocomplete API URL for cURL call
            $url = "https://places.googleapis.com/v1/places:autocomplete";

            /*   PAYLOAD STARTS   */
            // Prepare request payload
            $payload = [
                'input' => $request->input('input'), // type: string, The user's search query(partial address or place name or landmark)
                'locationBias' => [ // type: array, Used to influences ranking
                    'circle' => [ // type: array, Specifies a circular area around a point to bias search results
                        'center' => [ // type: array, Defines central point of the location bias
                            'latitude' => $request->input('latitude'), // type: float, Defines latitude coordinate of the central point
                            'longitude' => $request->input('longitude') // type: float, Defines longitude coordinate of the central point
                        ],
                        'radius' => 50000, // type: integer, The radius around the central point(in meters)  task: Places within this area will appear higher in the search results
                        // min-radius: 0 km -> Disables location biasing. Results are ranked normally
                        // max-radius: 50 km -> Strongly biases results toward the specified location
                    ]
                ]
            ];
            /*   PAYLOAD ENDS   */

            // request headers
            $headers = [
                'Content-Type: application/json',
                'X-Goog-Api-Key:' .  $general_settings->server_map_key,
            ];

            // cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true); // post request
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // headers
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload)); // payload

            $result = curl_exec($ch);

            $curl_error = curl_error($ch);
            if (!empty($curl_error)) {
                curl_close($ch);
                return response()->json([
                    "status" => 0,
                    "message" => $curl_error,
                ]);
            }

            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Decode response
            $decodedResult = json_decode($result, true);

            // Return the response from Google Places API
            return response()->json($decodedResult, $httpStatus);
        } catch(Exception $exception){
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    /* This function retrieves place details from the Google Places API based on a provided place_id */
    public function postGooglePlaceDetails(Request $request)
    {
        try{
            // place_id:  if search query is "kkv" and click on that search result there is unique place_id for that result (e.g: ChIJCdxbob_LWTkR8bLPvJgnjOM)
            // fetch general_settings
            $general_settings = GeneralSettings::query()->select('server_map_key')->first();
            // if general_settings Null or mapKey null
            if($general_settings == Null || $general_settings->server_map_key == Null){
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.9'), // Sorry, something went wrong
                    "message_code" => 9,
                ]);
            }

            // Google Places API URL for cURL call
            $url = "https://places.googleapis.com/v1/places/" . $request->input('place_id');

            // request headers
            // X-Goog-FieldMask param used to specify which fields should be included in the API response
            $headers = [
                'Content-Type: application/json',
                'X-Goog-Api-Key:' .  $general_settings->server_map_key,
                'X-Goog-FieldMask: displayName,formattedAddress,location,addressComponents' // four fields that are required in API response
            ];

            // cURL request, Don't require any payload
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPGET, true); // This API supports GET request
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($ch);

            $curl_error = curl_error($ch);
            if (!empty($curl_error)) {
                curl_close($ch);
                return response()->json([
                    "status" => 0,
                    "message" => $curl_error,
                ]);
            }

            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Decode response
            $decodedResult = json_decode($result, true);

            // Return the response from Google Places API
            return response()->json($decodedResult, $httpStatus);
        } catch(Exception $exception){
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    /* This function requests route details from the Google Routes API, including origin, destination, waypoints, and traffic information. */
    public function postGoogleRouteDetails(Request $request)
    {
        try{
            // fetch general_settings
            $general_settings = GeneralSettings::query()->select('server_map_key', 'matrix_api_route_preference')->first();
            // if general_settings Null or mapKey Null
            if($general_settings == Null || $general_settings->server_map_key == Null){
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.9'), // Sorry, something went wrong
                    "message_code" => 9,
                ]);
            }

            /* ---------------------- Define Route Preference Based on Settings ---------------------- */
            $route_preference = (isset($general_settings->matrix_api_route_preference) && $general_settings->matrix_api_route_preference == 1)
                ? "TRAFFIC_AWARE" // Consider real-time traffic data
                : "TRAFFIC_UNAWARE"; // Ignore traffic data

            // Google Places API URL
            $url = "https://routes.googleapis.com/directions/v2:computeRoutes";

            // Parse waypoints from JSON string
            $waypoints = [];
            // filled method:
            // It returns true if the waypoint field is present and has a non-empty value.
            //It returns false if the waypoint is missing or empty (including null, "", or an empty array).
            if ($request->filled('waypoint')) {
                // decode request json waypoint parameter to associative array
                $decodedWaypoints = json_decode($request->input('waypoint'), true);
                if (is_array($decodedWaypoints)) {
                    foreach ($decodedWaypoints as $point) {
                        $waypoints[] = [
                            "via" => true,
                            "location" => [
                                "latLng" => [
                                    "latitude" => $point['latitude'],
                                    "longitude" => $point['longitude']
                                ]
                            ]
                        ];
                    }
                }
            }

            /*   PAYLOAD STARTS   */
            // Prepare request payload
            $payload = [
                "origin" => [ // Defines starting location of the route
                    "location" => [
                        "latLng" => [
                            "latitude" => $request->input('pickup_latitude'),
                            "longitude" => $request->input('pickup_longitude')
                        ]
                    ]
                ],
                "destination" => [ // Defines ending location of the route
                    "location" => [
                        "latLng" => [
                            "latitude" => $request->input('drop_latitude'),
                            "longitude" => $request->input('drop_longitude')
                        ]
                    ]
                ],
                // Max Limit: 25 waypoints (for standard API), 200 (for premium users).
                "intermediates" => $waypoints, // A list of waypoints (stopping points) between the origin and destination.
                "travelMode" => "DRIVE", // For cars and vehicles or Travel by passenger car.
                // "TRAFFIC_ON_POLYLINE" – Includes real-time traffic impact. "TOLLS" – Calculates toll costs on the route.
                "extraComputations" => ["TRAFFIC_ON_POLYLINE", "TOLLS"],
                // Optimized based on real-time traffic.
                "routingPreference" => $route_preference,
                // Controls the precision of the route polyline (map drawing) and  More precise, larger response.
                "polylineQuality" => "HIGH_QUALITY",
                // Response text in "en"
                "languageCode" => "en",
            ];
            /*  PAYLOAD ENDS  */

            // request headers
            // X-Goog-FieldMask param used to specify which fields should be included in the API response
            $headers = [
                'Content-Type: application/json',
                'X-Goog-Api-Key:' .  $general_settings->server_map_key,
                'X-Goog-FieldMask: routes'
            ];

            // cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $result = curl_exec($ch);

            $curl_error = curl_error($ch);
            if (!empty($curl_error)) {
                curl_close($ch);
                return response()->json([
                    "status" => 0,
                    "message" => $curl_error,
                ]);
            }

            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Decode response
            $decodedResult = json_decode($result, true);

            // Return the response from Google Places API
            return response()->json($decodedResult, $httpStatus);
        } catch(Exception $exception){
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
    }
}
