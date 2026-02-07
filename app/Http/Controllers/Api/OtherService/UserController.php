<?php

namespace App\Http\Controllers\Api\OtherService;

use App\Classes\AdminClass;
use App\Classes\NotificationClass;
use App\Classes\UserClassApi;
use App\Models\AdminAreaList;
use App\Models\GeneralSettings;
use App\Models\LanguageLists;
use App\Models\OtherServiceCategory;
use App\Models\OtherServiceProviderDetails;
use App\Models\OtherServiceProviderPackages;
use App\Models\OtherServiceProviderTimings;
use App\Models\OtherServiceRatings;
use App\Models\PromocodeDetails;
use App\Models\Provider;
use App\Models\ProviderAcceptedPackageTime;
use App\Models\ProviderPortfolioImage;
use App\Models\ProviderServices;
use App\Models\RestrictedArea;
use App\Models\ServiceCategory;
use App\Models\ServiceSettings;
use App\Models\ServiceSliderBanner;
use App\Models\TempUserBooking;
use App\Models\UsedPromocodeDetails;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserCardDetails;
use App\Models\UserPackageBooking;
use App\Models\UserPackageBookingQuantity;
use App\Models\UserRatings;
use App\Models\UserReferHistory;
use App\Models\UserWalletTransaction;
use App\Models\WorldCurrency;
use App\Services\FirebaseService;
use Braintree_Configuration;
use Braintree_Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Luigel\Paymongo\Facades\Paymongo;
use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use phpDocumentor\Reflection\Types\Null_;

class UserController extends Controller
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
    private $notificationClass;
    private $adminClass;
    private $adminClassApi;
    private $provider_ratting_limit = 15;
    private $user_type = 0;

    public function __construct(UserClassApi $userClassapi, NotificationClass $notificationClass, AdminClass $adminClass, AdminClass $adminClassApi)
    {
        $this->adminClass = $adminClass;
        $this->userClassapi = $userClassapi;
        $this->adminClassApi = $adminClassApi;
        $this->notificationClass = $notificationClass;
    }

    public function postOtherServiceCategoryList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
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
        return $this->userClassapi->findOtherServiceCategory($request->get('service_category_id'));
    }

    public function postOtherServiceProviderList(Request $request) {
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable|numeric",
            "service_category_id" => "required|numeric",
            "sub_category_id" => "required|numeric",
            //"address_id" => "nullable|required_without:lat_long",
            "lat" => "required",
            "long" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_details = null;
        if ($request->get('user_id') <> null && $request->get('access_token')){
            $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($user_details) == false) {
                return $user_details;
            }
        }
        $service_cat_id = $request->get('service_category_id');
        $sub_cat_id = $request->get('sub_category_id');
        $lat = $request->get('lat');
        $long = $request->get('long');
        $lat_long = $lat.",".$long;
        $user_currency = null;
        if ($user_details <> null){
            $user_currency = WorldCurrency::query()->where('symbol', $user_details->currency)->first();
            if ($user_currency == Null) {
                $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
            }
        } else {
            $currency = $request->header("select-currency");
            if ($currency != null) {
                $user_currency = WorldCurrency::query()->where('currency_code', $currency)->first();
            }
        }

        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }

        $currency = $user_currency->ratio;
        return $this->userClassapi->findServiceProvider($service_cat_id, $sub_cat_id, $lat_long, $currency);

    }

    public function postOtherServiceProviderPackageList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable|numeric",
            "provider_id" => "required|numeric",
            "service_category_id" => "required|numeric"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_details = $user_currency = null;
        if ($request->get('user_id') && $request->get('access_token')){
            $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
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
        if ($language == ""){
            $language = "en";
        }

        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }

        return $this->userClassapi->findServiceProviderPackageList($request->get('provider_id'), $request->get('service_category_id'), $user_currency->ratio, $language);
    }

    public function postOtherServiceOrderPreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "service_category_id" => "required|numeric",
            //"order_type" => "required|in:book-now,schedule",
            "provider_id" => "required|numeric",
            "address" => "required|numeric",
            "package_id_list" => "required",
            "package_quantity_list" => "required",
            "payment_type" => "nullable",
            "promo_code" => "nullable",
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
        $service_category = ServiceCategory::query()->where('id', $request->get('service_category_id'))->first();
        if ($service_category == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "provider not found!",
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }

        //address json decode
        /*$address = json_decode($request->get('address'), true);
        if ((!array_key_exists('address', $address)) || (!array_key_exists('lat', $address)) || (!array_key_exists('long', $address)) || (!array_key_exists('flat_no', $address)) || (!array_key_exists('landmark', $address))) {
            return response()->json([
                "status" => 0,
                "message" => "something went to wrong!",
                "message_code" => 9,
            ]);
        }
        if ($address['address'] == Null || $address['lat'] == Null || $address['long'] == Null) {
            // if ($address['address'] == Null || $address['lat'] == Null || $address['long'] == Null || $address['flat_no'] == Null || $address['landmark'] == Null) {
            return response()->json([
                "status" => 0,
                "message" => "something went to wrong!",
                "message_code" => 9,
            ]);
        }
        $address_lat = $address['lat'];
        $address_long = $address['long'];*/

        $address = UserAddress::query()->where('id','=', $request->get('address'))
            ->where('user_id','=', $user_details->id)
            ->where('status','=', 1)
            ->first();
        if ($address == Null) {
            /*return response()->json([
                "status" => 0,
                "message" => "something went to wrong!",
                "message_code" => 9,
            ]);*/
            $address_lat = 0;
            $address_long = 0;
            $delivery_address = "";
        } else {
            $address_lat_long =  explode(",",$address->lat_long);
            $address_lat = isset($address_lat_long[0])?$address_lat_long[0]:0;
            $address_long = isset($address_lat_long[1])?$address_lat_long[1]:0;
            $delivery_address = isset($address->address)?$address->address:"";
        }


        $package_id_list = array_map('trim', explode(',', $request->get('package_id_list')));
        $package_quantity_list = array_map('trim', explode(',', $request->get('package_quantity_list')));
        if (count($package_quantity_list) != count($package_id_list)) {
            return response()->json([
                "status" => 0,
//                "message" => "something went to wrong!",
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
        $provider = Provider::query()->where('id', $request->get('provider_id'))->whereNull('providers.deleted_at')->first();
        if ($provider == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "provider not found!",
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }

        $provider_other_details = OtherServiceProviderDetails::query()->select('other_service_provider_details.id', 'other_service_provider_details.provider_id', 'other_service_provider_details.rating', 'other_service_provider_details.total_completed_order', 'other_service_provider_details.address', 'other_service_provider_details.flat_no', 'other_service_provider_details.landmark',
            DB::raw(('other_service_provider_details.lat,other_service_provider_details.long, ( ROUND( 6367 * acos( cos( radians(' . $address_lat . ') ) * cos( radians( other_service_provider_details.lat ) ) * cos( radians( other_service_provider_details.long ) - radians(' . $address_long . ') ) + sin( radians(' . $address_lat . ') ) * sin( radians( other_service_provider_details.lat ) ) ),2 ) ) AS distance'))
        )
            ->where('provider_id', $request->get('provider_id'))->first();
        if ($provider_other_details == Null) {
            return response()->json([
                "status" => 0,
                //"message" => "provider not found!",
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
        //average of total ratings
        $ratings = OtherServiceRatings::query()
            ->groupBy('provider_id')
            ->where('provider_id', $request->get('provider_id'))
            ->where('status', 1)
            ->avg('rating');

        if ($address_lat == 0 || $address_long == 0) {
            $distance = 0;
        } else {
            \Log::info($provider_other_details->distance);
            $distance = round($provider_other_details->distance, 1) - 0;
        }


        $order_package_list = [];
        $order_packages = [];
        $order_package_ids = [];
        $booking_price = 0;
        if ($request->get('schedule_date_time') != Null) {
            $service_date_time = date('Y-m-d H:i:s', strtotime($request->get('schedule_date_time')));
        } else {
            $service_date_time = date('Y-m-d H:i:s');
        }
        $user_currency = WorldCurrency::query()->where('symbol', $user_details->currency)->first();
        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $user_currency->ratio;

        $unavailable_packages = "";
        $unavailable_packages_name = "";

        foreach ($package_id_list as $key => $package_id) {
            $get_package = OtherServiceProviderPackages::query()->where('id', $package_id)->where('status', 0)->first();
            if ($get_package != Null) {
                $unavailable_packages .= $package_id.",";
                $unavailable_packages_name .= $get_package->name.",";
            }
        }
        $unavailable_packages = trim($unavailable_packages,",");
        $unavailable_packages_name = trim($unavailable_packages_name,",");


        foreach ($package_id_list as $key => $package_id) {
            if (!array_key_exists($package_id, $order_package_ids)) {
                $find_package = OtherServiceProviderPackages::query()->where('id', $package_id)->first();
                if ($find_package != Null) {
                    $sub_category = OtherServiceCategory::query()->where('id', $find_package->sub_cat_id)->where('status', 1)->first();
                    $lang_prefix = $this->adminClass->get_langugae_fields($user_details->language);
                    $fld = $lang_prefix . "name";
                    $sub_category_name = $sub_category->$fld;
                    if ($sub_category != Null) {
                        $order_package_ids[$package_id] = [
                            "package_id" => $find_package->id
                        ];
                        $order_packages[] = [
                            "id" => $find_package->id,
                            "package_name" => $find_package->name,
                            "sub_category_name" => $sub_category_name,
                            "num_of_items" => $package_quantity_list[$key] - 0,
                            "price_for_one" => number_format($find_package->price * $currency, 2)
                        ];
                        array_push($order_package_list, $find_package->name . ' x ' . $package_quantity_list[$key]);
                        $booking_price = ($booking_price) + (($find_package->price) * ($package_quantity_list[$key]));
                    }
                }
            }
        }
        $package_items = collect($order_packages)->groupBy('sub_category_name');
        $package_list = [];
        foreach ($package_items as $key => $list) {
            $package_list[] = [
                'sub_category_name' => $key,
                'item_list' => $list
            ];
        }
        /*if(empty($package_list)){
            return response()->json([
                "status" => 0,
                "message" => "Selected packages are not available at this moment!",
                "message_code" => 204,
                'unavailable_packages' => !empty($unavailable_packages) ? $unavailable_packages : ''
            ]);
        }*/
        $find_tax = ServiceSettings::query()->where('service_cat_id', $service_category->id)->first();
        if ($find_tax != Null) {
            $get_tax = $find_tax->tax;
            $admin_commission = $find_tax->admin_commission;
        } else {
            $get_tax = 0;
            $admin_commission = 0;
        }

        /*$promo_code_discount = 0;
        $promocode_name = '';
        if ($request->get('promocode_id') != 0) {
            $discount_on_amount = $booking_price;
            $promo_code = $this->userClassapi->checkPromoCodeValid($request->get('promocode_id'), $discount_on_amount, $user_details->id, $request->get('service_category_id'));
            if ($promo_code != 0) {
                if ($promo_code != 1) {
                    $promo_code_discount = $promo_code;
                    $promocode_name = PromocodeDetails::query()->select('promo_code')->where('id',$request->get('promocode_id'))->first();
                    $promocode_name = $promocode_name->promo_code;
                }
            }
        }*/

        $promocode_name = '';
        $promo_code_apply = 0;
        $promo_code_status = 0;
        $promo_message_code = 0;
        $promo_code_message = "";
        $min_order_amount = 0;
        $promo_discount_cost = 0;
        $get_promo_code = $request->get('promo_code');
        $get_promo_code_id = 0;
        if ($get_promo_code != "" && $get_promo_code != Null) {
            $promo_code_apply = 1;
            if (!is_numeric($get_promo_code)) {
                $get_promocode = PromocodeDetails::query()->where('promo_code', $get_promo_code)->where('service_cat_id', $request->get('service_category_id'))->where('status','1')->first();
                $get_promo_code_id = ($get_promocode != Null) ? $get_promocode->id : 0;
            }
            if ($get_promo_code_id != 0){
                $discount_on_amount = $booking_price;
                list($promo_code_status,$promo_message_code,$promo_code_message,$min_order_amount,$promo_code_amount,$promo_code_name) = $this->userClassapi->checkPromoCodeValid($get_promo_code_id, $discount_on_amount,  $user_details->id, $request->get('service_category_id'));
                $promo_code_status = $promo_code_status;
                $promo_message_code = $promo_message_code;
                $promo_code_message = $promo_code_message;
                $min_order_amount = $min_order_amount;
                $promo_discount_cost = $promo_code_amount;
                $promocode_name = $promo_code_name != Null ? $promo_code_name : "";
            } else {
                $promo_code_status = 0;
                $promo_message_code = 232;
                $promo_code_message = __('user_messages.232');
                $min_order_amount = 0;
            }
        }

        $total_referral_amount = 0;
        $referral_discount_cost = 0;
        if ($user_details->pending_refer_discount > 0) {

            $user = User::query()->where('id', $user_details->id)->first();
            if ($user != Null) {

                $user_refer_history = UserReferHistory::query()->where('user_id', $user_details->id)->where('user_status', 0)->first();

                if ($user_refer_history != Null) {
                    $admin_referral_type = $user_refer_history->user_discount_type;
                    $admin_referral_amount = $user_refer_history->user_discount;
//                    $referral_discount_cost = $admin_referral_amount;
                    $referral_discount_cost = $this->userClassapi->ReferralApply($admin_referral_type,$admin_referral_amount,$booking_price);
                } else {
                    $user_refer_history = UserReferHistory::query()->where('refer_id', $user_details->id)->where('refer_status', 0)->first();
                    if ($user_refer_history != Null) {
                        $admin_referral_type = $user_refer_history->refer_discount_type;
                        $admin_referral_amount = $user_refer_history->refer_discount;
                        $referral_discount_cost = $this->userClassapi->ReferralApply($admin_referral_type,$admin_referral_amount,$booking_price);
                    }
                }

                $total_referral_amount =$total_referral_amount +  $referral_discount_cost;
            }
        }

        $promo_code_discount=  $promo_discount_cost;
        $total_item_cost = number_format($booking_price, 2, '.', '');
        $get_taxable_item_cost = number_format($booking_price - $promo_code_discount - $referral_discount_cost, 2, '.', '');

        $tax = number_format((($get_taxable_item_cost * $get_tax) / 100), 2,'.','');
        $total_pay = number_format(($total_item_cost + $tax) - $promo_code_discount - $referral_discount_cost, 2, '.', '');
//        $provider_amount = number_format(($total_pay) - (($total_pay * $admin_commission)) / 100);

        $sub_total = number_format($total_pay - $tax,2, '.', '');
        return response()->json([
            "status" => 1,
//            "message" => "success!",
            "message" => __('user_messages.1'),
            "message_code" => 1,
            "order_id" => 0,
            "order_no" => "0",
            "category_name" => ucwords(strtolower($service_category->name)),
            "category_icon" => url('/assets/images/service-category/' . $service_category->icon_name),
            "service_date_time" => $service_date_time,
            "provider_name" => $provider->first_name,
            "distance" => $distance,
            "provider_profile_image" => $provider->avatar != Null ? url('/assets/images/profile-images/provider/' . $provider->avatar) : '',
            "provider_rating" => round($ratings,2),
            "item_total" => number_format($total_item_cost * $currency, 2, '.', ''),
            "tax" => number_format($tax * $currency, 2, '.', ''),
            "sub_total" => number_format(($sub_total) * $currency, 2, '.', ''),
            "discount" => 0.00,
            "promocode_id" => $get_promo_code_id,
            'promo_code_apply' => $promo_code_apply,
            'promo_code_status' => $promo_code_status,
            'promo_message_code' => $promo_message_code,
            'promo_code_message' => $promo_code_message,
            "refer_discount" => number_format($referral_discount_cost * $currency, 2, '.', ''),
            'min_order_amount' =>number_format($min_order_amount * $currency, 2, '.', ''),
            "promocode_discount" => number_format($promo_discount_cost * $currency, 2, '.', ''),
            "promocode_name" => $promocode_name,
            "total_pay" => number_format($total_pay * $currency, 2, '.', ''),
            "delivery_address" => $delivery_address,
            "cancel_by" => "",
            "cancel_reason" => "",
            "order_status" => 0,
            "package_list" => $package_list,
            "unavailable_packages" => isset($unavailable_packages) && $unavailable_packages != Null ? $unavailable_packages : ''
        ]);
    }

    public function postOtherServicePlaceOrderBKP(Request $request){

        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            //"address_id" => "required|numeric",
            "service_category_id" => "required|numeric",
            //"order_type" => "required|in:book-now,schedule",
            "provider_id" => "required|numeric",
            "address" => "required|numeric",
            "package_id_list" => "required",
            "package_quantity_list" => "required",
            //"payment_type" => "required|in:cash,card,wallet",
            "payment_type" => "required|in:1,2,3",
            "promo_code" => "nullable",
            "remark" => "nullable",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        //if ($request->get('booking_type') == "schedule") {
        //    $validator = Validator::make($request->all(), [
        //        "schedule_date_time" => "nullable"
        //    ]);
        //    if ($validator->fails()) {
        //        return response()->json([
        //            "status" => 0,
        //            "message" => $validator->errors()->first(),
        //            "message_code" => 9,
        //        ]);
        //    }
        //}
        $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_details) == false) {
            return $user_details;
        }
        //address json decode
        /*$address = json_decode($request->get('address'), true);
        if ((!array_key_exists('address', $address)) || (!array_key_exists('lat', $address)) || (!array_key_exists('long', $address)) || (!array_key_exists('flat_no', $address)) || (!array_key_exists('landmark', $address))) {
            return response()->json([
                "status" => 0,
                "message" => "something went to wrong!",
                "message_code" => 9,
            ]);
        }
        if ($address['address'] == Null || $address['lat'] == Null || $address['long'] == Null) {
            //if ($address['address'] == Null || $address['lat'] == Null || $address['long'] == Null || $address['flat_no'] == Null || $address['landmark'] == Null) {
            return response()->json([
                "status" => 0,
                "message" => "something went to wrong!",
                "message_code" => 9,
            ]);
        }
        $address_lat = trim($address['lat']);
        $address_long = trim($address['long']);*/

        $address = UserAddress::query()->where('id','=', $request->get('address'))
            ->where('user_id','=', $user_details->id)
            ->where('status','=', 1)
            ->first();
        if ($address == Null) {
            return response()->json([
                "status" => 0,
                "message" => "something went to wrong!",
                "message_code" => 9,
            ]);

        }
        $address_lat_long =  explode(",",$address->lat_long);
        $address_lat = isset($address_lat_long[0])?$address_lat_long[0]:0;
        $address_long = isset($address_lat_long[1])?$address_lat_long[1]:0;
        $delivery_address = isset($address->address)?$address->address:"";

        $package_id_list = array_map('trim', explode(',', $request->get('package_id_list')));
        $package_quantity_list = array_map('trim', explode(',', $request->get('package_quantity_list')));
        if (count($package_quantity_list) != count($package_id_list)) {
            return response()->json([
                "status" => 0,
                "message" => "something went to wrong!",
                "message_code" => 9,
            ]);
        }
        $provider = Provider::query()->where('id', $request->get('provider_id'))->whereNull('providers.deleted_at')->first();
        if ($provider == Null) {
            return response()->json([
                "status" => 0,
                "message" => "provider not found!",
                "message_code" => 9,
            ]);
        }

        $area_details = RestrictedArea::query()->where('status',1)->get();
        $latitudes = $longitudes = '';
        foreach ($area_details as $area){
            $latitudes = $latitudes . $area->latitude . ',';
            $longitudes = $longitudes . $area->longitude . ',';
        }
        $restricted_lat = explode(',',substr($latitudes, 0, -1));
        $restricted_long = explode(',',substr($longitudes, 0, -1));
        $points_polygon = count($restricted_lat);
        if($this->adminClassApi->is_in_restricted_area($points_polygon,$restricted_lat,$restricted_long,$address_lat,$address_long)){
            return response()->json([
                "status" => 0,
                "message" => "Your service address exist in restricted area, please try with other location!",
                "message_code" => 196,
            ]);
        }
        $check_near_delivery_address = OtherServiceProviderDetails::query()->select('other_service_provider_details.id','other_service_provider_details.min_order as min_order', 'providers.service_radius'
            , DB::raw('(6367 * 2 * ASIN( SQRT( POWER( SIN(( ' . $address_lat . ' - other_service_provider_details.lat) *  pi()/180 / 2), 2)
                +COS( ' . $address_lat . ' * pi()/180)
                * COS(other_service_provider_details.lat * pi()/180)
                * POWER(SIN(( ' . $address_long . ' - other_service_provider_details.long) * pi()/180 / 2), 2) ))) as distance')
        )
            //->join('provider_services', 'provider_services.id', '=', 'other_service_provider_details.provider_service_id')
            ->join('providers', 'providers.id', '=', 'other_service_provider_details.provider_id')
            //->where('provider_services.service_cat_id', $request->get('service_category_id'))
            ->where('providers.id', $provider->id)
            ->havingRaw('providers.service_radius > distance')
            ->whereNull('providers.deleted_at')
            ->first();
        if ($check_near_delivery_address == Null) {
            return response()->json([
                "status" => 0,
                "message" => "The provider not offer his/her service in your delivery location! Please select your nearest provider.",
                "message_code" => 116,
            ]);
        }

        $unavailable_packages = "";
        $unavailable_packages_name = "";

        foreach ($package_id_list as $key => $package_id) {
            $get_package = OtherServiceProviderPackages::query()->where('id', $package_id)->where('status', 0)->first();
            if ($get_package != Null) {
                    $unavailable_packages .= $package_id.",";
                    $unavailable_packages_name .= $get_package->name.",";
            }
        }

        if($unavailable_packages != ""){
            return response()->json([
                "status" => 0,
                "message" => "Selected packages are not available at this moment!",
                "message_code" => 204,
                'unavailable_packages' => !empty($unavailable_packages) ? trim($unavailable_packages,",") : '',
                'unavailable_packages_name' => !empty($unavailable_packages_name) ? trim($unavailable_packages_name,",") : ''
            ]);
        }

        $package_book = new UserPackageBooking();
        $package_book->user_id = $request->get('user_id');
        $package_book->provider_id = $request->get('provider_id');
        $package_book->provider_name = $provider->first_name ;
        $package_book->user_name = $user_details['first_name'] ;
        //$package_book->address_id = $request->get('address_id');
        $package_book->service_cat_id = $request->get('service_category_id');
        $package_book->payment_type = $request->get('payment_type');
        $package_book->delivery_address = $delivery_address;
        $package_book->lat_long = $address->lat_long;
        $package_book->flat_no = ($address->flat_no != Null) ? $address->flat_no : "";
        $package_book->landmark = ($address->landmark != Null) ? $address->landmark : "";
        $package_book->remark = $request->get('remark') != Null ? $request->get('remark') : Null;
        if ($request->get('schedule_date_time') != Null) {
            $package_book->order_type = 1;
            $package_book->service_date_time = date('Y-m-d H:i:s', strtotime($request->get('schedule_date_time')));
        } else {
            $package_book->order_type = 0;
            $package_book->service_date_time = date('Y-m-d H:i:s');
        }
        if ($request->get('payment_type') != Null) {
            $package_book->payment_type = $request->get('payment_type');
        }
        $package_book->save();
        $package_book->generateBookingNo();
        $booking_price = 0;

        $order_package_list = [];
        foreach ($package_id_list as $key => $package_id) {
            $check_duplicate = UserPackageBookingQuantity::query()->where('order_id', $package_book->id)->where('package_id', $package_id)->first();
            if ($check_duplicate == Null) {
                $find_package = OtherServiceProviderPackages::query()->where('id', $package_id)->where('status',1)->first();
                if ($find_package != Null) {
                    $sub_category = OtherServiceCategory::query()->where('id', $find_package->sub_cat_id)->where('status', 1)->first();
                    if ($sub_category != Null) {
                        $package_quantity = new UserPackageBookingQuantity();
                        $package_quantity->order_id = $package_book->id;
                        $package_quantity->package_id = $package_id;
                        $package_quantity->package_name = $find_package->name;
                        $package_quantity->sub_category_name = $sub_category->name;


                        try{
                            $language_list = LanguageLists::query()->select('language_name as name',
                                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_sub_category_name') ELSE 'name' END) as sub_category_col_name"),
                                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as category_col_name")
                            )->where('status',1)->get();
                            foreach ($language_list as $keys => $language){
                                if(Schema::hasColumn('user_package_booking_quantity',$language->sub_category_col_name)  ) {
                                    $package_quantity->{$language->sub_category_col_name} = $sub_category->{$language->category_col_name};
                                }
                            }
                        } catch (\Exception $e) {}
                        $package_quantity->num_of_items = $package_quantity_list[$key];
                        $package_quantity->price_for_one = $find_package->price;
                        $package_quantity->save();
                        array_push($order_package_list, $package_quantity->package_name . ' x ' . $package_quantity->num_of_items);
                        $booking_price = ($booking_price) + (($package_quantity->price_for_one) * ($package_quantity->num_of_items));
                    } else {
                        UserPackageBookingQuantity::query()->where("order_id","=", $package_book->id)->first();
                        UserPackageBooking::query()->where('id', "=", $package_book->id)->delete();
                        return response()->json([
                            "status" => 0,
                            "message" => "something went to wrong!",
                            "message_code" => 9,
                        ]);
                    }
                }
            }
        }

        $package_book->order_package_list = implode(", ", $order_package_list);
        $package_book->save();
        $find_tax = ServiceSettings::query()->where('service_cat_id', $package_book->service_cat_id)->first();
        if ($find_tax != Null) {
            $get_tax = $find_tax->tax;
            $admin_commission = $find_tax->admin_commission;
        } else {
            $get_tax = 5;
            $admin_commission = 5;
        }

        /*$promo_code_discount = 0;
        if ($request->get('promocode_id') != 0) {
            $discount_on_amount = $booking_price;
            $promo_code = $this->userClassapi->checkPromoCodeValid($request->get('promocode_id'), $discount_on_amount, $user_details->id, $request->get('service_category_id'));
            if ($promo_code != 0) {
                if ($promo_code != 1) {
                    $get_prmocode = PromocodeDetails::query()->where('id', $request->get('promocode_id'))->where('status', 1)->first();
                    if ($get_prmocode != Null) {
                        $promo_code_discount = $promo_code;
                        $add_promocode_details = new UsedPromocodeDetails();
                        $add_promocode_details->service_cat_id = $request->get('service_category_id');
                        $add_promocode_details->user_id = $request->get('user_id');
                        $add_promocode_details->promocode_id = $request->get('promocode_id');
                        $add_promocode_details->promocode_name = $get_prmocode->promo_code;
                        $add_promocode_details->discount_amount = $promo_code;
                        $add_promocode_details->status = 0;
                        $add_promocode_details->save();

                        $package_book->promo_code = $add_promocode_details->id;
                        //$package_book->total_pay = number_format($package_book->total_pay - $promo_code, 2);
                        $package_book->save();
                    }
                }
            }
        }*/

        $promo_code_discount = 0;
        $get_promo_code = $request->get('promo_code');
        if ($get_promo_code != 0) {
            if (!is_numeric($get_promo_code)) {
                $get_promocode = PromocodeDetails::query()->where('promo_code', $get_promo_code)->where('service_cat_id', $request->get('service_category_id'))->where('status','1')->first();
                $get_promo_code_id = ($get_promocode != Null) ? $get_promocode->id : 0;
            }
            //$discount_on_amount = (($total_price - $discount_cost) + $place_order->packaging_cost);

            if($get_promo_code_id > 0)
            {
                $discount_on_amount = $booking_price;

                list($promo_code_status,$promo_message_code,$promo_code_message,$min_order_amount,$promo_code_amt,$promo_code_name) = $this->userClassapi->checkPromoCodeValid($get_promo_code_id, $discount_on_amount, $user_details->id, $request->get('service_category_id'));

                if ($promo_code_status == 1) {
                    $get_prmocode = PromocodeDetails::query()->where('id', $get_promo_code_id)->where('status', 1)->first();
                    if ($get_prmocode != Null) {
                        $promo_code_discount = $promo_code_amt;

                        $add_promocode_details = new UsedPromocodeDetails();
                        $add_promocode_details->service_cat_id = $request->get('service_category_id');
                        $add_promocode_details->user_id = $request->get('user_id');
                        $add_promocode_details->promocode_id = $get_promo_code_id;
                        $add_promocode_details->promocode_name = $get_prmocode->promo_code;
                        $add_promocode_details->discount_amount = $promo_code_discount;
//                                $add_promocode_details->status = 1;
                        $add_promocode_details->save();

                        $get_prmocode->total_usage = $get_prmocode->total_usage + 1;
                        $get_prmocode->save();

                        $package_book->promo_code = $add_promocode_details->id;
                        $package_book->save();
                    }
                }
            }

        }

        $package_book->BookingCost($booking_price, $get_tax, $admin_commission, $promo_code_discount);

        if ($user_details->pending_refer_discount > 0) {
            $user = User::query()->where('id', $user_details->id)->whereNull('users.deleted_at')->first();
            if ($user != Null) {
                $user_refer_history = UserReferHistory::query()->where('user_id', $user_details->id)->where('user_status', 0)->first();
                $total_price = $package_book->total_pay;
                if ($user_refer_history != Null) {
                    if ($user_refer_history->user_discount_type == 1) {
                        $refer_discount_price = number_format($total_price - $user_refer_history->user_discount, 2,'.','');
                    } else {
                        $refer_discount_price = number_format((($total_price * $user_refer_history->user_discount) / 100), 2,'.','');
                        $refer_discount_price = number_format($total_price - $refer_discount_price, 2,'.','');
                    }
                    if ($refer_discount_price < 0) {
                        $refer_discount_price = 0;
                    }
                    $package_book->total_pay = $refer_discount_price;
                    $package_book->refer_discount = number_format($user_refer_history->user_discount, 2,'.','');
                    $package_book->save();
                    $user_refer_history->user_status = 1;
                    $user_refer_history->save();
                    $user->pending_refer_discount = $user->pending_refer_discount - 1;
                    $user->save();
                } else {
                    $user_refer_history = UserReferHistory::query()->where('refer_id', $user_details->id)->where('refer_status', 0)->first();
                    if ($user_refer_history != Null) {
                        if ($user_refer_history->refer_discount_type == 1) {
                            $refer_discount_price = number_format($total_price - $user_refer_history->refer_discount, 2,'.','');
                        } else {
                            $refer_discount_price = number_format((($total_price * $user_refer_history->refer_discount) / 100), 2,'.','');
                            $refer_discount_price = number_format($total_price - $refer_discount_price, 2,'.','');
                        }
                        if ($refer_discount_price < 0) {
                            $refer_discount_price = 0;
                        }
                        $package_book->total_pay = $refer_discount_price;
                        $package_book->refer_discount = number_format($user_refer_history->user_discount, 2,'.','');
                        $package_book->save();
                        $user_refer_history->refer_status = 1;
                        $user_refer_history->save();
                        $user->pending_refer_discount = $user->pending_refer_discount - 1;
                        $user->save();
                    }
                }
            }
        }
        //$check_SODS = Other Service Order Details Status true or false
        //add order_status parameter in response
        $user_lang = isset($user_details->language)?$user_details->language:"";
        return $this->userClassapi->OtherServiceOrderDetails($package_book->id, true,$unavailable_packages,$user_lang);
    }

    public function postOtherServicePlaceOrderNewBKP(Request $request) {

        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "service_category_id" => "required|numeric",
            "provider_id" => "required|numeric",
            "address" => "required|numeric",
            "package_id_list" => "required",
            "package_quantity_list" => "required",
            "payment_type" => "required|in:1,2,3",
            "promo_code" => "nullable",
            "remark" => "nullable",
            "select_provider_location" => "nullable|in:0,1",
            "schedule_date_time" => "nullable",
            "select_time" => "required",
            "select_date" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_id = $request->get("user_id");
        $user_details = $this->userClassapi->checkUserAllow($user_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_details) == false) {
            return $user_details;
        }

        $service_category_id = $request->get("service_category_id");
        $provider_id = $request->get("provider_id");
        $address = $request->get("address");
        $package_id_list = $request->get("package_id_list");
        $package_quantity_list = $request->get("package_quantity_list");
        $promo_code = $request->get("promo_code");


        $provider_details = Provider::query()->select('providers.*', 'other_service_provider_details.is_allowed_provider_location', 'other_service_provider_details.address', 'other_service_provider_details.lat', 'other_service_provider_details.long', 'other_service_provider_details.flat_no', 'other_service_provider_details.landmark')
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
            ->where('provider_services.service_cat_id', $service_category_id)
            ->where('provider_services.current_status', 1)
            ->where('provider_services.status', 1)
            ->where('other_service_provider_details.time_slot_status', 1)
            ->where('providers.status', 1)
            ->where('providers.id', $provider_id)
            ->whereNull('providers.deleted_at')
            ->first();
        if ($provider_details == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "Provider details not found!",
                "message" => __('user_messages.302'),
                "message_code" => 302,
            ]);
        }

        $address = UserAddress::query()->where('id','=', $address)->where('user_id','=', $user_details->id)->where('status','=', 1)->first();
        if ($address == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "Address details not found!",
                "message" => __('user_messages.215'),
                "message_code" => 215,
            ]);
        }
        $address_lat_long = explode(",", $address->lat_long);
        $address_lat = isset($address_lat_long[0]) ? $address_lat_long[0] : 0;
        $address_long = isset($address_lat_long[1]) ? $address_lat_long[1] : 0;
        $delivery_address = isset($address->address) ? $address->address : "";
        $package_id_list = array_map('trim', explode(',', $package_id_list));
        $package_quantity_list = array_map('trim', explode(',', $package_quantity_list));
        if (count($package_quantity_list) != count($package_id_list)) {
            return response()->json([
                "status" => 0,
//                "message" => "something went to wrong!",
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }

        if ($request->get('select_date') != Null) {
            $select_date = $request->get('select_date');
        } else {
            $select_date = date('Y-m-d');
        }
        $select_day = strtoupper(substr(date('l', strtotime($select_date)), '0', '3'));
        $select_date = date('Y-m-d', strtotime($select_date));
        $today_date = date('Y-m-d');
        $select_time = $request->get('select_time');
        $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider_details->id)->where('day', '=', $select_day)->first();
        if ($get_open_timing == Null) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.302'),
                "message_code" => 302,
            ]);
        }

        $provider_open_time = explode(',', $get_open_timing->open_time_list);
        if (!in_array($select_time, $provider_open_time)) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.302'),
                "message_code" => 302,
            ]);
        }

        $check_provider_accepted_time = ProviderAcceptedPackageTime::query()->where('provider_id', $provider_details->id)->where('date', '=', $select_date)->where('time', '=', $select_time)->first();
        if ($check_provider_accepted_time != Null) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.302'),
                "message_code" => 302,
            ]);
        }


        $provider_service_details = ProviderServices::query()->where('provider_id', $provider_details->id)->where('service_cat_id', $service_category_id)->first();
        if ($provider_service_details == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "Provider details not found!",
                "message" => __('user_messages.73'),
                "message_code" => 73,
            ]);
        }

        $area_details = RestrictedArea::query()->where('status',1)->get();
        $latitudes = $longitudes = '';
        foreach ($area_details as $area){
            $latitudes = $latitudes . $area->latitude . ',';
            $longitudes = $longitudes . $area->longitude . ',';
        }
        $restricted_lat = explode(',',substr($latitudes, 0, -1));
        $restricted_long = explode(',',substr($longitudes, 0, -1));
        $points_polygon = count($restricted_lat);
        if($this->adminClassApi->is_in_restricted_area($points_polygon,$restricted_lat,$restricted_long,$address_lat,$address_long)){
            return response()->json([
                "status" => 0,
//                "message" => "Your service address exist in restricted area, please try with other location!",
                "message" => __('user_messages.196'),
                "message_code" => 196,
            ]);
        }

        $provider_other_details = OtherServiceProviderDetails::query()->select(
            'other_service_provider_details.id',
            'other_service_provider_details.min_order',
            'providers.service_radius',
            DB::raw('(6367 * 2 * ASIN( SQRT( POWER( SIN(( ' . $address_lat . ' - other_service_provider_details.lat) *  pi()/180 / 2), 2) + COS( ' . $address_lat . ' * pi()/180) * COS(other_service_provider_details.lat * pi()/180) * POWER(SIN(( ' . $address_long . ' - other_service_provider_details.long) * pi()/180 / 2), 2) ))) as distance')
        )
            ->join('providers', 'providers.id', '=', 'other_service_provider_details.provider_id')
            ->where('providers.id', $provider_details->id)
            ->havingRaw('providers.service_radius > distance')
            ->first();
        if ($provider_other_details == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "The provider not offer his/her service in your delivery location! Please select your nearest provider.",
                "message" => __('user_messages.116'),
                "message_code" => 116,
            ]);
        }

        $user_currency = WorldCurrency::query()->where('symbol', $user_details->currency)->first();
        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $user_currency != Null ? $user_currency->ratio : 1;
        $currency_symbol = $user_currency != Null ? $user_currency->symbol : '';

        $language = $user_details->language;

        $unavailable_packages = "";
        $unavailable_packages_name = "";

        $booking_price = 0;
        $order_package_list = [];
        $order_book_package_list = [];
        foreach ($package_id_list as $key => $package_id) {

            $find_package = OtherServiceProviderPackages::query()->where('other_service_provider_packages.id', $package_id)->where('other_service_provider_packages.provider_service_id', $provider_service_details->id)->first();

            if ($find_package != Null) {
                if ($find_package->status == 1 ){
                    $sub_category = OtherServiceCategory::query()->where('id', $find_package->sub_cat_id)->where('status', 1)->first();
                    if ($sub_category != Null) {
                        $add_order_book_package = [
                            "order_id" => "",
                            "package_id" => $package_id,
                            "package_name" => $find_package->name,
                            "sub_category_name" => $sub_category->name,
                            "num_of_items" => $package_quantity_list[$key],
                            "price_for_one" => $find_package->price,
                        ];
                        $add_sub_category_name = [];
                        try{
                            $language_list = LanguageLists::query()->select('language_name as name',
                                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_sub_category_name') ELSE 'name' END) as sub_category_col_name"),
                                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as category_col_name")
                            )->where('status',1)->get();
                            foreach ($language_list as $keys => $language){
                                if(Schema::hasColumn('user_package_booking_quantity',$language->sub_category_col_name)  ) {
                                    //$package_quantity->{$language->sub_category_col_name} = $sub_category->{$language->category_col_name};
                                    $add_sub_category_name = array_merge($add_sub_category_name, $add_order_book_package);
                                    $add_sub_category_name = array_merge($add_sub_category_name, array(
//                                        $language->sub_category_col_name => $language->category_col_name,
                                        $language->sub_category_col_name => $sub_category->{$language->category_col_name}
                                    ));
                                }
                            }
                        } catch (\Exception $e) {}
                        $order_book_package_list[] = $add_sub_category_name;

                        array_push($order_package_list, $find_package->name . ' x ' . $package_quantity_list[$key]);
                        $booking_price = ($booking_price) + (($find_package->price) * ($package_quantity_list[$key]));
                    }
                    else {
                        $unavailable_packages .= $package_id.",";
                        $unavailable_packages_name .= $find_package->name.",";
                    }
                }
                else {
                    $unavailable_packages .= $package_id.",";
                    $unavailable_packages_name .= $find_package->name.",";
                }
            }
            else {
                $unavailable_packages .= $package_id.",";
                $unavailable_packages_name .= "Unknown package,";
            }
        }

        if($unavailable_packages != ""){
            return response()->json([
                "status" => 0,
//                "message" => "Selected packages are not available at this moment!",
                "message" => __('user_messages.204'),
                "message_code" => 204,
                'unavailable_packages' => !empty($unavailable_packages) ? trim($unavailable_packages,",") : '',
                'unavailable_packages_name' => !empty($unavailable_packages_name) ? trim($unavailable_packages_name,",") : ''
            ]);
        }
        if(!(count($order_book_package_list) > 0)){
            return response()->json([
                "status" => 0,
//                "message" => "Packages not available at this moment!",
                "message" => __('user_messages.174'),
                "message_code" => 174,
            ]);
        }
        if ($provider_other_details->min_order > $booking_price) {
            return response()->json([
                "status" => 0,
//                "message" => "Order min amount" . $currency_symbol . " " . number_format(($provider_other_details->min_order * $currency), 2),
                "message" =>  __('user_messages.127', ['currencySymbol' => $currency_symbol, 'amount' => number_format(($provider_other_details->min_order * $currency), 2)]),
                "message_code" => 127,
                "order_min_amount" => number_format(($provider_other_details->min_order * $currency), 2) - 0
            ]);
        }

        $package_book = new UserPackageBooking();
        $package_book->user_id = $user_id;
        $package_book->provider_id = $provider_id;
        $package_book->provider_name = $provider_details->first_name.' '.$provider_details->last_name ;
        $package_book->user_name = $user_details['first_name'] ;
        $package_book->service_cat_id = $service_category_id;
        $package_book->delivery_address = $delivery_address;
        $package_book->lat_long = $address->lat_long;
        $package_book->flat_no = ($address->flat_no != Null) ? $address->flat_no : "";
        $package_book->landmark = ($address->landmark != Null) ? $address->landmark : "";
        $package_book->remark = $request->get("remark") != Null ? $request->get("remark") : Null;
//        if ($request->get('schedule_date_time') != Null) {
//            $package_book->order_type = 1;
//            $package_book->service_date_time = date('Y-m-d H:i:s', strtotime($request->get('schedule_date_time')));
//        } else {
//            $package_book->order_type = 0;
//            $package_book->service_date_time = date('Y-m-d H:i:s');
//        }
        if ($today_date != $select_date) {
            $package_book->order_type = 1;
        } else {
            $package_book->order_type = 0;
        }
        $package_book->service_date = $select_date;
        $package_book->service_time = $select_time;
        if ($request->get("payment_type") != Null) {
            $package_book->payment_type = $request->get("payment_type");
        }
        $package_book->order_package_list = implode(", ", $order_package_list);
        $package_book->save();
        $package_book->save();
        $package_book->generateBookingNo();

        $find_tax = ServiceSettings::query()->where('service_cat_id', $package_book->service_cat_id)->first();
        if ($find_tax != Null) {
            $get_tax = $find_tax->tax;
            $admin_commission = $find_tax->admin_commission;
        } else {
            $get_tax = 5;
            $admin_commission = 5;
        }

        $promo_code_discount = 0;
        $get_promo_code = $promo_code;
        if ($get_promo_code != 0) {
            if (!is_numeric($get_promo_code)) {
                $get_promocode = PromocodeDetails::query()->where('promo_code', $get_promo_code)->where('service_cat_id', '=', $service_category_id)->where('status','1')->first();
                $get_promo_code_id = ($get_promocode != Null) ? $get_promocode->id : 0;
            }
            if($get_promo_code_id > 0) {
                $discount_on_amount = $booking_price;
                list($promo_code_status,$promo_message_code,$promo_code_message,$min_order_amount,$promo_code_amt,$promo_code_name) = $this->userClassapi->checkPromoCodeValid($get_promo_code_id, $discount_on_amount, $user_details->id, $service_category_id);
                if ($promo_code_status == 1) {
                    $get_prmocode = PromocodeDetails::query()->where('id', $get_promo_code_id)->where('status', 1)->first();
                    if ($get_prmocode != Null) {
                        $promo_code_discount = $promo_code_amt;

                        $add_promocode_details = new UsedPromocodeDetails();
                        $add_promocode_details->service_cat_id = $service_category_id;
                        $add_promocode_details->user_id = $user_id;
                        $add_promocode_details->promocode_id = $get_promo_code_id;
                        $add_promocode_details->promocode_name = $get_prmocode->promo_code;
                        $add_promocode_details->discount_amount = $promo_code_discount;
                        //$add_promocode_details->status = 1;
                        $add_promocode_details->save();

                        $get_prmocode->total_usage = $get_prmocode->total_usage + 1;
                        $get_prmocode->save();

                        $package_book->promo_code = $add_promocode_details->id;
                        $package_book->save();
                    }
                }
            }
        }
        $get_refer_discount_price  = 0;
        if ($user_details->pending_refer_discount > 0) {
            $user = User::query()->where('id', $user_details->id)->first();
            if ($user != Null) {
                $user_refer_history = UserReferHistory::query()->where('user_id', $user_details->id)->where('user_status', 0)->first();
                $total_price = $booking_price;
                if ($user_refer_history != Null) {
                    if ($user_refer_history->user_discount_type == 1) {
                        $refer_discount_price = number_format($total_price - $user_refer_history->user_discount, 2,'.','');
                        $get_refer_discount_price = $user_refer_history->user_discount;
                    } else {
                        $refer_discount_price = number_format((($total_price * $user_refer_history->user_discount) / 100), 2,'.','');
                        $get_refer_discount_price = $refer_discount_price;
                        $refer_discount_price = number_format($total_price - $refer_discount_price, 2,'.','');
                    }
                    if ($refer_discount_price < 0) {
                        $refer_discount_price = 0;
                    }
//                    $package_book->total_pay = $refer_discount_price;
                    $package_book->refer_discount = number_format($get_refer_discount_price, 2,'.','');
                    $package_book->save();
                    $user_refer_history->user_status = 1;
                    $user_refer_history->save();
                    $user->pending_refer_discount = $user->pending_refer_discount - 1;
                    $user->save();
                } else {
                    $user_refer_history = UserReferHistory::query()->where('refer_id', $user_details->id)->where('refer_status', 0)->first();
                    if ($user_refer_history != Null) {
                        if ($user_refer_history->refer_discount_type == 1) {
                            $refer_discount_price = number_format($total_price - $user_refer_history->refer_discount, 2,'.','');
                            $get_refer_discount_price = $user_refer_history->refer_discount;
                        } else {
                            $refer_discount_price = number_format((($total_price * $user_refer_history->refer_discount) / 100), 2,'.','');
                            $get_refer_discount_price = $refer_discount_price;
                            $refer_discount_price = number_format($total_price - $refer_discount_price, 2,'.','');
                        }
                        if ($refer_discount_price < 0) {
                            $refer_discount_price = 0;
                        }
//                        $package_book->total_pay = $refer_discount_price;
                        $package_book->refer_discount = number_format($get_refer_discount_price, 2,'.','');
                        $package_book->save();
                        $user_refer_history->refer_status = 1;
                        $user_refer_history->save();
                        $user->pending_refer_discount = $user->pending_refer_discount - 1;
                        $user->save();
                    }
                }
            }
        }
        $package_book->BookingCost($booking_price, $get_tax, $admin_commission, $promo_code_discount,$get_refer_discount_price);
//        if ($user_details->pending_refer_discount > 0) {
//            $user = User::query()->where('id', $user_details->id)->whereNull('users.deleted_at')->first();
//            if ($user != Null) {
//                $user_refer_history = UserReferHistory::query()->where('user_id', $user_details->id)->where('user_status', 0)->first();
//                $total_price = $package_book->total_pay;
//                if ($user_refer_history != Null) {
//                    if ($user_refer_history->user_discount_type == 1) {
//                        $refer_discount_price = number_format($total_price - $user_refer_history->user_discount, 2);
//                    } else {
//                        $refer_discount_price = number_format((($total_price * $user_refer_history->user_discount) / 100), 2);
//                        $refer_discount_price = number_format($total_price - $refer_discount_price, 2);
//                    }
//                    if ($refer_discount_price < 0) {
//                        $refer_discount_price = 0;
//                    }
//                    $package_book->total_pay = $refer_discount_price;
//                    $package_book->refer_discount = number_format($user_refer_history->user_discount, 2);
//                    $package_book->save();
//                    $user_refer_history->user_status = 1;
//                    $user_refer_history->save();
//                    $user->pending_refer_discount = $user->pending_refer_discount - 1;
//                    $user->save();
//                } else {
//                    $user_refer_history = UserReferHistory::query()->where('refer_id', $user_details->id)->where('refer_status', 0)->first();
//                    if ($user_refer_history != Null) {
//                        if ($user_refer_history->refer_discount_type == 1) {
//                            $refer_discount_price = number_format($total_price - $user_refer_history->refer_discount, 2);
//                        } else {
//                            $refer_discount_price = number_format((($total_price * $user_refer_history->refer_discount) / 100), 2);
//                            $refer_discount_price = number_format($total_price - $refer_discount_price, 2);
//                        }
//                        if ($refer_discount_price < 0) {
//                            $refer_discount_price = 0;
//                        }
//                        $package_book->total_pay = $refer_discount_price;
//                        $package_book->refer_discount = number_format($user_refer_history->user_discount, 2);
//                        $package_book->save();
//                        $user_refer_history->refer_status = 1;
//                        $user_refer_history->save();
//                        $user->pending_refer_discount = $user->pending_refer_discount - 1;
//                        $user->save();
//                    }
//                }
//            }
//        }

        foreach ($order_book_package_list as $key => $book_package_details) {
            $add_package_booking_quantity = UserPackageBookingQuantity::query()->where('order_id', $package_book->id)->where('package_id', $book_package_details['package_id'])->first();
            if ($add_package_booking_quantity == Null) {

                $add_package_quantity = new UserPackageBookingQuantity();
                $add_package_quantity->order_id = $package_book->id;
                $add_package_quantity->package_id = $book_package_details['package_id'];
                $add_package_quantity->package_name = $book_package_details['package_name'];
                $add_package_quantity->sub_category_name = $book_package_details['sub_category_name'];

                try {
                    $language_list = LanguageLists::query()->select('language_name as name',
                        DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_sub_category_name') ELSE 'name' END) as sub_category_col_name"),
                        DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as category_col_name")
                    )->where('status', 1)->get();
                    foreach ($language_list as $keys => $language) {
                        if (Schema::hasColumn('user_package_booking_quantity', $language->sub_category_col_name)) {
                            $add_package_quantity->{$language->sub_category_col_name} = $book_package_details[$language->sub_category_col_name];
                        }
                    }
                } catch (\Exception $e) {}

                $add_package_quantity->num_of_items = $book_package_details['num_of_items'];
                $add_package_quantity->price_for_one = $book_package_details['price_for_one'];
                $add_package_quantity->save();
            }
        }
        //$check_SODS = Other Service Order Details Status true or false
        //add order_status parameter in response
        $user_lang = isset($user_details->language)?$user_details->language:"";
        return $this->userClassapi->OtherServiceOrderDetails($package_book->id, true,$unavailable_packages,$user_lang);
    }

    public function postOtherServicePlaceOrder(Request $request)
    {
        $this->notificationClass->ApiLogDetail($logger_type = 1, $request->get("user_id"),$request->fullUrl(), $request->all());
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "service_category_id" => "required|numeric",
            "provider_id" => "required|numeric",
            "address" => "required|numeric",
            "package_id_list" => "required",
            "package_quantity_list" => "required",
            "payment_type" => "required|in:1,2,3",
            "promo_code" => "nullable",
            "remark" => "nullable",
            "select_provider_location" => "nullable|in:0,1",
//            "schedule_date_time" => "nullable",
//            "select_day" => "required",
//            "select_date" => "required",
            "select_time" => "required",
            "select_date" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_id = $request->get("user_id");
        $user_details = $this->userClassapi->checkUserAllow($user_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_details) == false) {
            return $user_details;
        }

        $service_category_id = $request->get("service_category_id");
        $provider_id = $request->get("provider_id");
        $address = $request->get("address");
        $package_id_list = $request->get("package_id_list");
        $package_quantity_list = $request->get("package_quantity_list");
        $promo_code = $request->get("promo_code");

        $provider_details = Provider::query()->select('providers.*', 'other_service_provider_details.is_allowed_provider_location', 'other_service_provider_details.address', 'other_service_provider_details.lat', 'other_service_provider_details.long', 'other_service_provider_details.flat_no', 'other_service_provider_details.landmark')
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
            ->where('provider_services.service_cat_id', $service_category_id)
            ->where('provider_services.current_status', 1)
            ->where('provider_services.status', 1)
            ->where('other_service_provider_details.time_slot_status', 1)
            ->where('providers.status', 1)
            ->where('providers.id', $provider_id)
            ->whereNull('providers.deleted_at')->first();
        if ($provider_details == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "Provider details not found!",
                "message" => __('user_messages.73'),
                "message_code" => 302,
            ]);
        }
        $select_provider_location = $request->get('select_provider_location') != Null ? $request->get('select_provider_location') : 0;
        if ($select_provider_location == 1 && $provider_details->is_allowed_provider_location == 0) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.303'),
                "message_code" => 303,
            ]);
        }
        $address = UserAddress::query()->where('id', '=', $address)->where('user_id', '=', $user_details->id)->where('status', '=', 1)->first();
        if ($address == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "Address details not found!",
                "message" => __('user_messages.215'),
                "message_code" => 215,
            ]);
        }
        $address_lat_long = explode(",", $address->lat_long);
        $address_lat = isset($address_lat_long[0]) ? $address_lat_long[0] : 0;
        $address_long = isset($address_lat_long[1]) ? $address_lat_long[1] : 0;
        $delivery_address = isset($address->address) ? $address->address : "";

        $package_id_list = array_map('trim', explode(',', $package_id_list));
        $package_quantity_list = array_map('trim', explode(',', $package_quantity_list));
        if (count($package_quantity_list) != count($package_id_list)) {
            return response()->json([
                "status" => 0,
//                "message" => "something went to wrong!",
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
//        $provider_details = Provider::query()->where('id', $provider_id)->first();
//        if ($provider_details == Null) {
//            return response()->json([
//                "status" => 0,
////                "message" => "Provider details not found!",
//                "message" => __('user_messages.73'),
//                "message_code" => 73,
//            ]);
//        }

        $book_slot_time= 00;
        if ($request->get('select_date') != Null) {
            $select_date = $request->get('select_date');

        } else {
            $select_date = date('Y-m-d');
        }
//        $select_day = strtoupper(substr(date('l', strtotime($select_date)), '0', '3'));
        $select_day = strtoupper(date("D",strtotime($select_date)));
        $select_date = date('Y-m-d', strtotime($select_date));
        $today_date = date('Y-m-d');

        $select_time = $request->get('select_time');
        $select_time_arr = explode("-",$select_time);
        /*$book_start_slot =$this->notificationClass->convertTimezone($select_time_arr[0],$user_details['time_zone'],$default_server_timezone,"n"); ;
        $book_end_slot   =  $this->notificationClass->convertTimezone($select_time_arr[1],$user_details['time_zone'],$default_server_timezone,"n"); ;*/

        $book_start_slot =$select_time_arr[0];
        $book_end_slot   =$select_time_arr[1];


        $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider_details->id)
            ->where('day', '=', $select_day)
            ->where('provider_open_time', '=', $book_start_slot)
            ->where('provider_close_time', '=', $book_end_slot)
            ->first();
        if ($get_open_timing == Null) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.302'),
                "message_code" => 302,
            ]);
        }

//        $provider_open_time = explode(',', $get_open_timing->open_time_list);
//        if (!in_array($select_time, $provider_open_time)) {
//            return response()->json([
//                "status" => 0,
//                "message" => __('user_messages.302'),
//                "message_code" => 302,
//            ]);
//        }

        $check_provider_accepted_time = ProviderAcceptedPackageTime::query()->where('provider_id', $provider_details->id)
            ->where('date', '=', $select_date)
            ->where('book_start_time', '=', $book_start_slot)
            ->where('book_end_time', '=', $book_end_slot)
            ->first();
        if ($check_provider_accepted_time != Null) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.302'),
                "message_code" => 302,
            ]);
        }

        $provider_service_details = ProviderServices::query()->where('provider_id', $provider_details->id)->where('service_cat_id', $service_category_id)->first();
        if ($provider_service_details == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "Provider details not found!",
                "message" => __('user_messages.73'),
                "message_code" => 73,
            ]);
        }

        $area_details = RestrictedArea::query()->where('status', 1)->get();
        $latitudes = $longitudes = '';
        foreach ($area_details as $area) {
            $latitudes = $latitudes . $area->latitude . ',';
            $longitudes = $longitudes . $area->longitude . ',';
        }
        $restricted_lat = explode(',', substr($latitudes, 0, -1));
        $restricted_long = explode(',', substr($longitudes, 0, -1));
        $points_polygon = count($restricted_lat);

        if ($this->adminClassApi->is_in_restricted_area($points_polygon, $restricted_lat, $restricted_long, $address_lat, $address_long)) {
            return response()->json([
                "status" => 0,
//                "message" => "Your service address exist in restricted area, please try with other location!",
                "message" => __('user_messages.196'),
                "message_code" => 196,
            ]);
        }

        $provider_other_details = OtherServiceProviderDetails::query()->select(
            'other_service_provider_details.id',
            'other_service_provider_details.min_order',
            'providers.service_radius',
            DB::raw('(6367 * 2 * ASIN( SQRT( POWER( SIN(( ' . $address_lat . ' - other_service_provider_details.lat) *  pi()/180 / 2), 2) + COS( ' . $address_lat . ' * pi()/180) * COS(other_service_provider_details.lat * pi()/180) * POWER(SIN(( ' . $address_long . ' - other_service_provider_details.long) * pi()/180 / 2), 2) ))) as distance')
        )
            ->join('providers', 'providers.id', '=', 'other_service_provider_details.provider_id')
            ->where('providers.id', $provider_details->id)
            ->havingRaw('providers.service_radius > distance')
            ->first();
        if ($provider_other_details == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "The provider not offer his/her service in your delivery location! Please select your nearest provider.",
                "message" => __('user_messages.116'),
                "message_code" => 116,
            ]);
        }

        $user_currency = WorldCurrency::query()->where('symbol', $user_details->currency)->first();
        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $user_currency != Null ? $user_currency->ratio : 1;
        $currency_symbol = $user_currency != Null ? $user_currency->symbol : '';

        $language = $user_details->language;

        $unavailable_packages = "";
        $unavailable_packages_name = "";

        $booking_price = 0;
        $order_package_list = [];
        $order_book_package_list = [];
        foreach ($package_id_list as $key => $package_id) {

            $find_package = OtherServiceProviderPackages::query()->where('other_service_provider_packages.id', $package_id)->where('other_service_provider_packages.provider_service_id', $provider_service_details->id)->first();
            if ($find_package != Null) {
                if ($find_package->status == 1) {
                    $sub_category = OtherServiceCategory::query()->where('id', $find_package->sub_cat_id)->where('status', 1)->first();
                    if ($sub_category != Null) {
                        $add_order_book_package = [
                            "order_id" => "",
                            "package_id" => $package_id,
                            "package_name" => $find_package->name,
                            "sub_category_name" => $sub_category->name,
                            "num_of_items" => $package_quantity_list[$key],
                            "price_for_one" => $find_package->price,
                        ];

                        $add_sub_category_name = [];
                        $add_sub_category_name = array_merge($add_sub_category_name, $add_order_book_package);
                        try {
                            $language_list = LanguageLists::query()->select('language_name as name',
                                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_sub_category_name') ELSE 'name' END) as sub_category_col_name"),
                                DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as category_col_name")
                            )->where('status', 1)->get();
                            foreach ($language_list as $keys => $language) {




                                if (Schema::hasColumn('user_package_booking_quantity', $language->sub_category_col_name)) {
                                    //$package_quantity->{$language->sub_category_col_name} = $sub_category->{$language->category_col_name};
                                    $add_sub_category_name = array_merge($add_sub_category_name, $add_order_book_package);
                                    $add_sub_category_name = array_merge($add_sub_category_name, array(
                                        //$language->sub_category_col_name => $language->category_col_name,
                                        $language->sub_category_col_name => $sub_category->{$language->category_col_name}
                                    ));
                                }
                            }
                        } catch (\Exception $e) {
                        }
                        $order_book_package_list[] = $add_sub_category_name;

                        array_push($order_package_list, $find_package->name . ' x ' . $package_quantity_list[$key]);
                        $booking_price = ($booking_price) + (($find_package->price) * ($package_quantity_list[$key]));
                    } else {
                        $unavailable_packages .= $package_id . ",";
                        $unavailable_packages_name .= $find_package->name . ",";
                    }
                } else {
                    $unavailable_packages .= $package_id . ",";
                    $unavailable_packages_name .= $find_package->name . ",";
                }
            } else {
                $unavailable_packages .= $package_id . ",";
                $unavailable_packages_name .= "Unknown package,";
            }
        }

        if ($unavailable_packages != "") {
            return response()->json([
                "status" => 0,
//                "message" => "Selected packages are not available at this moment!",
                "message" => __('user_messages.204'),
                "message_code" => 204,
                'unavailable_packages' => !empty($unavailable_packages) ? trim($unavailable_packages, ",") : '',
                'unavailable_packages_name' => !empty($unavailable_packages_name) ? trim($unavailable_packages_name, ",") : ''
            ]);
        }
        if (!(count($order_book_package_list) > 0)) {
            return response()->json([
                "status" => 0,
//                "message" => "Packages not available at this moment!",
                "message" => __('user_messages.174'),
                "message_code" => 174,
            ]);
        }
        if ($provider_other_details->min_order > $booking_price) {
            return response()->json([
                "status" => 0,
//                "message" => "Order min amount" . $currency_symbol . " " . round(($provider_other_details->min_order * $currency), 2),
                "message" => __('user_messages.127', ['currencySymbol' => $currency_symbol, 'amount' => round(($provider_other_details->min_order * $currency), 2)]),
                "message_code" => 127,
                "order_min_amount" => round(($provider_other_details->min_order * $currency), 2) - 0
            ]);
        }

        $current_lat = isset($address_lat_long[0]) ? $address_lat_long[0] : 0;
        $current_long = isset($address_lat_long[1]) ? $address_lat_long[1] : 0;

        $package_ids = implode(',',$package_id_list);

        $package_book = new UserPackageBooking();
        $package_book->user_id = $user_id;
        $package_book->provider_id = $provider_id;
        $package_book->provider_name = $provider_details->first_name . ' ' . $provider_details->last_name;
        $package_book->user_name = $user_details['first_name'];
        $package_book->booking_time_zone = isset($user_details['time_zone'])?$user_details['time_zone']:"";
        $package_book->service_cat_id = $service_category_id;
        $package_book->package_id = $package_ids;
        $package_book->select_provider_location = $select_provider_location;
        if ($select_provider_location == 1) {
            $package_book->delivery_address = $provider_details->address;
            $package_book->lat_long = $provider_details->lat.','.$provider_details->long;
            $package_book->flat_no = $provider_details->flat_no;
            $package_book->landmark = $provider_details->landmark;
        } else {
            $package_book->delivery_address = $delivery_address;
            $package_book->lat_long = $address->lat_long;
            $package_book->flat_no = ($address->flat_no != Null) ? $address->flat_no : "";
            $package_book->landmark = ($address->landmark != Null) ? $address->landmark : "";
        }

        $package_book->remark = $request->get("remark") != Null ? $request->get("remark") : Null;
        if ($today_date != $select_date) {
            $package_book->order_type = 1;
        } else {
            $package_book->order_type = 0;
        }
//        if ($schedule_date != Null) {
////            $package_book->order_type = 1;
//            $package_book->service_date_time = date('Y-m-d H:i:s', strtotime($schedule_date));
//        } else {
////            $package_book->order_type = 0;
//            $package_book->service_date_time = date('Y-m-d H:i:s');
//        }
        $package_book->service_date = $select_date;
        $package_book->service_time = $book_start_slot."-".$book_end_slot;
        $package_book->book_start_time = $book_start_slot;
        $package_book->book_end_time =$book_end_slot;
        $package_book->book_slot_time =$book_slot_time;

        $package_book->service_date_time = date('Y-m-d H:i:s', strtotime($select_date." ".$book_start_slot));
        if ($request->get("payment_type") != Null) {
            $package_book->payment_type = $request->get("payment_type");
        }
        $package_book->order_package_list = implode(", ", $order_package_list);
        $package_book->save();
        $package_book->save();
        $package_book->generateBookingNo();

        $find_tax = ServiceSettings::query()->where('service_cat_id', $package_book->service_cat_id)->first();
        if ($find_tax != Null) {
            $get_tax = $find_tax->tax;
            $admin_commission = $find_tax->admin_commission;
        } else {
            $get_tax = 0;
            $admin_commission = 0;
        }

        $promo_code_discount = 0;
        $get_promo_code = $promo_code;

        if ($get_promo_code != 0) {

            if (!is_numeric($get_promo_code)) {

                $get_promocode = PromocodeDetails::query()->where('promo_code', $get_promo_code)->where('service_cat_id', '=', $service_category_id)->where('status', '1')->first();
                $get_promo_code_id = ($get_promocode != Null) ? $get_promocode->id : 0;

            }
            if ($get_promo_code_id > 0) {
                $discount_on_amount = $booking_price;
                list($promo_code_status, $promo_message_code, $promo_code_message, $min_order_amount, $promo_code_amt, $promo_code_name) = $this->userClassapi->checkPromoCodeValid($get_promo_code_id, $discount_on_amount, $user_details->id, $service_category_id);
                if ($promo_code_status == 1) {
                    $get_prmocode = PromocodeDetails::query()->where('id', $get_promo_code_id)->where('status', 1)->first();
                    if ($get_prmocode != Null) {
                        $promo_code_discount = $promo_code_amt;

                        $add_promocode_details = new UsedPromocodeDetails();
                        $add_promocode_details->service_cat_id = $service_category_id;
                        $add_promocode_details->user_id = $user_id;
                        $add_promocode_details->promocode_id = $get_promo_code_id;
                        $add_promocode_details->promocode_name = $get_prmocode->promo_code;
                        $add_promocode_details->discount_amount = $promo_code_discount;
                        //$add_promocode_details->status = 1;
                        $add_promocode_details->save();

                        $get_prmocode->total_usage = $get_prmocode->total_usage + 1;
                        $get_prmocode->save();

                        $package_book->promo_code = $add_promocode_details->id;
                        $package_book->save();
                    }
                }
            }
        }
        $get_refer_discount_price  = 0;
        if ($user_details->pending_refer_discount > 0) {
            $user = User::query()->where('id', $user_details->id)->first();
            if ($user != Null) {
                $user_refer_history = UserReferHistory::query()->where('user_id', $user_details->id)->where('user_status', 0)->first();
                $total_price = $booking_price;
                if ($user_refer_history != Null) {
                    if ($user_refer_history->user_discount_type == 1) {
                        $refer_discount_price = round($total_price - $user_refer_history->user_discount, 2);
                        $get_refer_discount_price = $user_refer_history->user_discount;
                    } else {
                        $refer_discount_price = round((($total_price * $user_refer_history->user_discount) / 100), 2);
                        $get_refer_discount_price = $refer_discount_price;
                        $refer_discount_price = round($total_price - $refer_discount_price, 2);
                    }
                    if ($refer_discount_price < 0) {
                        $refer_discount_price = 0;
                    }
//                    $package_book->total_pay = $refer_discount_price;
                    $package_book->refer_discount = round($get_refer_discount_price, 2);
                    $package_book->save();
                    $user_refer_history->user_status = 1;
                    $user_refer_history->save();
                    $user->pending_refer_discount = $user->pending_refer_discount - 1;
                    $user->save();
                } else {
                    $user_refer_history = UserReferHistory::query()->where('refer_id', $user_details->id)->where('refer_status', 0)->first();
                    if ($user_refer_history != Null) {
                        if ($user_refer_history->refer_discount_type == 1) {
                            $refer_discount_price = round($total_price - $user_refer_history->refer_discount, 2);
                            $get_refer_discount_price = $user_refer_history->refer_discount;
                        } else {
                            $refer_discount_price = round((($total_price * $user_refer_history->refer_discount) / 100), 2);
                            $get_refer_discount_price = $refer_discount_price;
                            $refer_discount_price = round($total_price - $refer_discount_price, 2);
                        }
                        if ($refer_discount_price < 0) {
                            $refer_discount_price = 0;
                        }
//                        $package_book->total_pay = $refer_discount_price;
                        $package_book->refer_discount = round($get_refer_discount_price, 2);
                        $package_book->save();
                        $user_refer_history->refer_status = 1;
                        $user_refer_history->save();
                        $user->pending_refer_discount = $user->pending_refer_discount - 1;
                        $user->save();
                    }
                }
            }
        }
        $package_book->BookingCost($booking_price, $get_tax, $admin_commission, $promo_code_discount,$get_refer_discount_price);
        /*if ($user_details->pending_refer_discount > 0) {
            $user = User::query()->where('id', $user_details->id)->first();
            if ($user != Null) {
                $user_refer_history = UserReferHistory::query()->where('user_id', $user_details->id)->where('user_status', 0)->first();
                $total_price = $package_book->total_pay;
                if ($user_refer_history != Null) {
                    if ($user_refer_history->user_discount_type == 1) {
                        $refer_discount_price = round($total_price - $user_refer_history->user_discount, 2);
                        $get_refer_discount_price = $user_refer_history->user_discount;
                    } else {
                        $refer_discount_price = round((($total_price * $user_refer_history->user_discount) / 100), 2);
                        $get_refer_discount_price = $refer_discount_price;
                        $refer_discount_price = round($total_price - $refer_discount_price, 2);
                    }
                    if ($refer_discount_price < 0) {
                        $refer_discount_price = 0;
                    }
                    $package_book->total_pay = $refer_discount_price;
                    $package_book->refer_discount = round($get_refer_discount_price, 2);
                    $package_book->save();
                    $user_refer_history->user_status = 1;
                    $user_refer_history->save();
                    $user->pending_refer_discount = $user->pending_refer_discount - 1;
                    $user->save();
                } else {
                    $user_refer_history = UserReferHistory::query()->where('refer_id', $user_details->id)->where('refer_status', 0)->first();
                    if ($user_refer_history != Null) {
                        if ($user_refer_history->refer_discount_type == 1) {
                            $refer_discount_price = round($total_price - $user_refer_history->refer_discount, 2);
                            $get_refer_discount_price = $user_refer_history->refer_discount;
                        } else {
                            $refer_discount_price = round((($total_price * $user_refer_history->refer_discount) / 100), 2);
                            $get_refer_discount_price = $refer_discount_price;
                            $refer_discount_price = round($total_price - $refer_discount_price, 2);
                        }
                        if ($refer_discount_price < 0) {
                            $refer_discount_price = 0;
                        }
                        $package_book->total_pay = $refer_discount_price;
                        $package_book->refer_discount = round($get_refer_discount_price, 2);
                        $package_book->save();
                        $user_refer_history->refer_status = 1;
                        $user_refer_history->save();
                        $user->pending_refer_discount = $user->pending_refer_discount - 1;
                        $user->save();
                    }
                }
            }
        }*/

        foreach ($order_book_package_list as $key => $book_package_details) {
            $add_package_booking_quantity = UserPackageBookingQuantity::query()->where('order_id', $package_book->id)->where('package_id', $book_package_details['package_id'])->first();
            if ($add_package_booking_quantity == Null) {

                $add_package_quantity = new UserPackageBookingQuantity();
                $add_package_quantity->order_id = $package_book->id;
                $add_package_quantity->package_id = $book_package_details['package_id'];
                $add_package_quantity->package_name = $book_package_details['package_name'];
                $add_package_quantity->sub_category_name = $book_package_details['sub_category_name'];

                try {
                    $language_list = LanguageLists::query()->select('language_name as name',
                        DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_sub_category_name') ELSE 'name' END) as sub_category_col_name"),
                        DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as category_col_name")
                    )->where('status', 1)->get();
                    foreach ($language_list as $keys => $language) {
                        if (Schema::hasColumn('user_package_booking_quantity', $language->sub_category_col_name)) {
                            $add_package_quantity->{$language->sub_category_col_name} = $book_package_details[$language->sub_category_col_name];
                        }
                    }
                } catch (\Exception $e) {
                }

                $add_package_quantity->num_of_items = $book_package_details['num_of_items'];
                $add_package_quantity->price_for_one = $book_package_details['price_for_one'];
                $add_package_quantity->save();
            }
        }
        //$check_SODS = Other Service Order Details Status true or false
        //add order_status parameter in response
        $user_lang = isset($user_details->language) ? $user_details->language : "";
        return $this->userClassapi->OtherServiceOrderDetails($package_book->id, true, $unavailable_packages, $user_lang);
    }

    public function postOtherServiceOrderHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable|numeric",
            "filter_type" => "nullable|in:0,1,2,3,4,5",
            "timezone" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_details = null;
        if ($request->get('user_id') <> null && $request->get('access_token')){
            $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($user_details) == false) {
                return $user_details;
            }
        }
        $default_server_timezone= "";
        $general_settings = request()->get("general_settings");
        if ($general_settings != Null) {
            if ($general_settings->default_server_timezone != "") {
                $default_server_timezone= $general_settings->default_server_timezone;
            }
        }

        $user_currency = null;
        if ($user_details <> null){
            $timezone = $user_details->time_zone != Null ? $user_details->time_zone : $default_server_timezone;
            $provider_timezone = $this->notificationClass->getDefaultTimeZone($timezone);
            date_default_timezone_set($provider_timezone);

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

        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }

        $lang_prefix = ($language == "en") ? "" : ($language."_");

        $filter_type = $request->get('filter_type');
        $date = date('Y-m-d');
        if ($filter_type == 1) {
            //today
            $start_date = $date . " 00:00:01";
            $end_date = $date . " 23:59:59";
            $start_date = $date;
            $end_date = $date;
        } elseif ($filter_type == 2) {
            //last 7 day
            $start_date = date('Y-m-d', strtotime('-7 days', strtotime($date)));
            // $start_date = date('Y-m-d', strtotime($date . ' - 7 days'));
            $end_date = $date;
//            $start_date = $start_date . " 00:00:01";
//            $end_date = $end_date . " 23:59:59";
        } elseif ($filter_type == 3) {
            //last 30 day
            $start_date = date('Y-m-d', strtotime('-30 days', strtotime($date)));
            $end_date = $date;
//            $start_date = $start_date . " 00:00:01";
//            $end_date = $end_date . " 23:59:59";
        } elseif ($filter_type == 4) {
            //this year
            $start_date = date("Y-01-01", strtotime($date));
            $end_date = date("Y-m-d", strtotime($date));
//            $start_date = $start_date . " 00:00:01";
//            $end_date = $end_date . " 23:59:59";
        } elseif ($filter_type == 5) {
            //upcoming order
            $start_date = date('Y-m-d', strtotime('+1 days', strtotime($date)));
            $end_date = date('Y-m-d', strtotime('+365 days', strtotime($date)));
//            $start_date = $start_date . " 00:00:01";
//            $end_date = $end_date . " 23:59:59";
        } else {//$filter_type == 0//all order
            //last 365 day
            $start_date = date('Y-m-d', strtotime('-365 days', strtotime($date)));
            $end_date = $date;
//            $start_date = $start_date . " 00:00:01";
//            $end_date = $end_date . " 23:59:59";
        }

        $per_page = 10;
        if ($request->get('per_page') != Null) {
            $per_page = $request->get('per_page');
        }

        /*$start = new \DateTime($start_date, new \DateTimeZone(config('app.timezone')));
        $start->setTimezone(new \DateTimeZone($request->get('timezone')));

        $end = new \DateTime($end_date, new \DateTimeZone(config('app.timezone')));
        $end->setTimezone(new \DateTimeZone($request->get('timezone')));*/

        if ($user_details <> null) {
            if ($filter_type == 1 || $filter_type == 2 || $filter_type == 3 || $filter_type == 4) {
                $orders = UserPackageBooking::query()->select('service_category.id as category_id',
                    'service_category.' . $lang_prefix . 'name as category_name',
                    'service_category.icon_name as category_icon',
                    'user_service_package_booking.id',
                    'user_service_package_booking.order_no',
                    //'user_service_package_booking.service_date_time',
                    'user_service_package_booking.total_pay',
                    'user_service_package_booking.provider_name',
                    'user_service_package_booking.service_date',
                    'user_service_package_booking.service_time',
                    'user_service_package_booking.book_start_time',
                    'user_service_package_booking.book_end_time',
                    DB::raw("DATE_FORMAT(user_service_package_booking.created_at, '%Y-%m-%d %H:%i:%s') as service_date_time"),
                    DB::raw("DATE_FORMAT(user_service_package_booking.service_date_time, '%Y-%m-%d %H:%i:%s') as schedule_order_date_time"),
                    'user_service_package_booking.status as order_status')
                    ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
                    ->where('user_service_package_booking.user_id', $user_details->id)
                    ->where('user_service_package_booking.service_date', '>=', $start_date)
                    ->where('user_service_package_booking.service_date', '<=', $end_date)
                    ->orderBy('user_service_package_booking.id', 'desc');
            } else {
                $orders = UserPackageBooking::query()->select('service_category.id as category_id',
                    'service_category.' . $lang_prefix . 'name as category_name',
                    'service_category.icon_name as category_icon',
                    'user_service_package_booking.id',
                    'user_service_package_booking.order_no',
                    //'user_service_package_booking.service_date_time',
                    'user_service_package_booking.total_pay',
                    'user_service_package_booking.provider_name',
                    'user_service_package_booking.service_date',
                    'user_service_package_booking.service_time',
                    'user_service_package_booking.book_start_time',
                    'user_service_package_booking.book_end_time',
                    DB::raw("DATE_FORMAT(user_service_package_booking.created_at, '%Y-%m-%d %H:%i:%s') as service_date_time"),
                    DB::raw("DATE_FORMAT(user_service_package_booking.service_date_time, '%Y-%m-%d %H:%i:%s') as schedule_order_date_time"),
                    'user_service_package_booking.status as order_status')
                    ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
                    ->where('user_service_package_booking.user_id', $user_details->id)
                    ->orderBy('user_service_package_booking.id', 'desc');
            }
            if ($filter_type != 5 && $filter_type != 0) {
                $orders = $orders->where('user_service_package_booking.service_date', '>=', $start_date)
                    ->where('user_service_package_booking.service_date', '<=', $end_date);
            }
            if ($filter_type == 5) {
                $orders->where('user_service_package_booking.service_date', '>=', $start_date)
                    ->whereIn('user_service_package_booking.status', [0, 1, 2, 3, 6, 7, 8]);
            }

            $orders = $orders->paginate($per_page);
            $current_page = $orders->currentPage();
            $last_page = $orders->lastPage();
            $total = $orders->total();
            $order_list = [];
            //change of add category_id parameter
            foreach ($orders as $order) {
                $cat_icon_path = url('/assets/images/service-category/' . $order->category_icon);
                $packages_list = UserPackageBookingQuantity::query()->where('order_id', '=', $order->id)->get();
                $packages = "";
                if (!$packages_list->isEmpty()) {
                    $packages = implode(',', array_slice(Arr::pluck($packages_list, 'package_name'), 0, 3));
                }
                $schedule_order_time = date("h:i A", strtotime($order->book_start_time)) . " - " . date("h:i A", strtotime($order->book_end_time));
//            $schedule_order_time= date("H:i:s",strtotime($order->book_start_time))." - ".date("H:i:s",strtotime($order->book_end_time));

                $order_list[] = [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'category_id' => $order->category_id,
                    'category_name' => $order->category_name,
                    'category_icon' => $order->category_icon != Null ? $cat_icon_path : '',
                    'service_date' => $this->notificationClass->dateLangConvert(date('D d M ,Y', strtotime($order->service_date_time)), $user_details->language),
                    'service_time' => date('h:i A', strtotime($order->service_date_time)),
                    'service_date_time' => $order->service_date_time,
                    'schedule_order_date' => $order->service_date,
                    //'schedule_order_time' => $order->service_time,
                    'schedule_order_time' => $schedule_order_time,
                    'schedule_order_date_time' => $order->schedule_order_date_time,
                    'total_pay' => round($order->total_pay * $user_currency->ratio,2),
                    'provider_name' => $order->provider_name != Null ? $order->provider_name : '',
                    'order_status' => $order->order_status,
                    'package_list' => $packages,
                ];

            }

            return response()->json([
                "status" => 1,
//            "message" => "success!",
                "message" => __('user_messages.1'),
                "message_code" => 1,
                'current_page' => $current_page - 0,
                'last_page' => $last_page - 0,
                'total' => $total - 0,
                "order_list" => $order_list,
            ]);
        }

        return response()->json([
            "status" => 1,
//            "message" => "success!",
            "message" => __('user_messages.1'),
            "message_code" => 1,
            'current_page' => 0,
            'last_page' => 0,
            'total' => 0,
            "order_list" => [],
        ]);
    }

    public function postOtherServiceOrderDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "order_id" => "required|numeric"
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
        //$check_SODS = Other Service Order Details Status true or false
        //add order_status parameter in response
        $user_lang = $user_details->language;

        return $this->userClassapi->OtherServiceOrderDetails($request->get('order_id'), false,Null,$user_lang);
    }

    public function postOtherServiceOrderPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "order_id" => "required|numeric",
            "payment_type" => "required|in:1,2,3",
//            "card_id" => "required",
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

        $place_order = UserPackageBooking::where('user_service_package_booking.id', $request->get('order_id'))->first();
        if ($place_order == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "Order details not found",
                "message" => __('user_messages.59'),
                "message_code" => 59,
            ]);
        }
        if ($place_order->status == 5) {
            return response()->json([
                "status" => 0,
//                "message" => "order cancel by admin!",
                "message" => __('user_messages.75'),
                "message_code" => 75,
                "order_status" => 5,
            ]);
        }
        $redirect_url = "";
        $success_url = "";
        $failed_url = "";
        $service_category = ServiceCategory::query()->where('id', $place_order->service_cat_id)->first();
        if ($service_category == Null) {
            return response()->json([
                "status" => 0,
//                        "message" => "something want to wrong!",
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }

        if ($request->get('payment_type') == 1) {

            //code for auto settle payment module
            if(request()->get('general_settings')->auto_settle_wallet == 1) {

                $subject = "Debit for admin commission - " . $service_category->name . " Booking # " . $place_order->order_no;
                $final_admin_commission = $place_order->admin_commission;
                $amount = $final_admin_commission +$place_order->tax;

                $transaction_type = 2;
                $subject_code = 13;
                if($amount <= 0) {
                    $transaction_type = 1;
                    $subject_code = 18;
                    $subject = "Credited for admin commission - " . $service_category->name . " Booking # " . $place_order->order_no;
                    // $subject =  "Admin Credited Earning";
                    $amount = abs($amount);
                }

                $driver_wallet_update = $this->notificationClass->providerUpdateWalletBalance(
                    provider_id: $place_order->provider_id,
                    wallet_provider_type: 3,
                    // service_cat_id: $place_order->service_cat_id,
                    transaction_type: $transaction_type,
                    add_update_wallet_bal: $amount,
                    subject: $subject,
                    subject_code: $subject_code,
                    order_no: $place_order->order_no
                );

                // $provider_id = $place_order->provider_id;
                // $wallet_provider_type = 3;
                // $service_cat_id = $place_order->service_cat_id;
                // $transaction_type = 2;
                // $add_update_wallet_bal = $place_order->admin_commission + $place_order->tax;
                // $subject =  "Debit for admin commission - " . $service_category->name . " Booking # " . $place_order->order_no;
                // $subject_code = 13;
                // $order_no = $place_order->order_no;

                // $driver_wallet_update = $this->notificationClass->providerUpdateWalletBalance($provider_id,$wallet_provider_type,$transaction_type,$add_update_wallet_bal,$subject,$subject_code,$order_no);

                if($driver_wallet_update) {
                    $place_order->provider_pay_settle_status = 1;
                }

            }
            //end code for auto settle
            $place_order->payment_type = $request->get('payment_type');
            $place_order->payment_status = 1;
            $place_order->save();
        }
        elseif ($request->get('payment_type') == 2) {

            $validator = Validator::make($request->all(), [
                "card_id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }
            $card_details = UserCardDetails::query()->where('id', $request->get('card_id'))->first();
            if ($card_details == Null) {
                return response()->json([
                    "status" => 0,
//                    "message" => "card details not found",
                    "message" => __('user_messages.120'),
                    "message_code" => 120,
                ]);
            }
            //code for auto settle
            if(request()->get('general_settings')->auto_settle_wallet == 1) {
                $provider_id = $place_order->provider_id;
                $wallet_provider_type = 3;
                $service_cat_id = $place_order->service_cat_id;
                $transaction_type = 1;
                $add_update_wallet_bal = $place_order->provider_amount;
                $subject = "Credited by Admin for your earning - " . $service_category->name . " Booking # " . $place_order->order_no;
                $subject_code = 14;
                $order_no = $place_order->order_no;

                $driver_wallet_update = $this->notificationClass->providerUpdateWalletBalance($provider_id,$wallet_provider_type,$transaction_type,$add_update_wallet_bal,$subject,$subject_code,$order_no);

                if($driver_wallet_update) {
                    $place_order->provider_pay_settle_status = 1;
                }

            }
            //end code for auto settle
            $place_order->payment_type = $request->get('payment_type');
            $place_order->payment_status = 1;
            $place_order->save();

//            if ($place_order->payment_status == 0) {
//                $validator = Validator::make($request->all(), [
//                    "payment_method_type" => "nullable|in:1,0",
//                ]);
//                if ($validator->fails()) {
//                    return response()->json([
//                        "status" => 0,
//                        "message" => $validator->errors()->first(),
//                        "message_code" => 9,
//                    ]);
//                }
//                if ($request->get('payment_method_type') == 1) {
//                    $amount_to_php = number_format($place_order->total_pay, 2);
//                    if ($amount_to_php < 100 || $amount_to_php > 99999999) {
//                        return response()->json([
//                            "status" => 0,
//                            "message" => "With paymongo min amount must be ₱100 and max amount must be ₱99999999",
//                            "message_code" => 158,
//                        ]);
//                    }
//
//                    $success_url = \URL::route('paypmongo.success');
//                    $failed_url = \URL::route('paypmongo.failed');
//                    $error_url = \URL::route('paypmongo.failed');
//                    //$error_url = \URL::route('mongo_pay.error');
//
//                    try {
//                        $user_id = $request->get('user_id');
//                        $service_category_id = $place_order->service_cat_id;
//                        $order_id = $place_order->id;
//
//
//                        $mongo_success_url = \URL::route('mongo_pay.success', ["id" => $user_id, "order_id" => $order_id]);
//                        $mongo_failed_url = \URL::route('mongo_pay.failed', ["id" => $user_id, "order_id" => $order_id]);
//
//                        $gcashSource = Paymongo::source()->create([
//                            'type' => 'gcash',
//                            'amount' => $amount_to_php,
//                            'currency' => 'PHP',
//                            'user_id' => $user_id,
//                            'redirect' => [
//                                'success' => $mongo_success_url,
//                                'failed' => $mongo_failed_url
//                            ]
//
//                        ]);
//
//                        $get_gcashSource_data = $gcashSource->getData();
//                        $mongo_success_url = $get_gcashSource_data['redirect']['checkout_url'];
//                        $gcashSource_status = $get_gcashSource_data['status'];
//                        $source_type = $get_gcashSource_data['source_type'];
//                        $gcashSource_id = $get_gcashSource_data['id'];
//                        if ($mongo_success_url != "" && $gcashSource_status == "pending" && $source_type == "gcash") {
//
//                            $place_order->payment_type = $request->get('payment_type');
//                            $place_order->save();
//
//                            $tempUserBooking = New TempUserBooking();
//                            $tempUserBooking->user_id = $user_id;
//                            $tempUserBooking->service_cat_id = $service_category_id;
//                            $tempUserBooking->booking_id = $order_id;
//                            $tempUserBooking->transaction_id = $gcashSource_id;
//                            $tempUserBooking->payment_method_type = 1;
//                            $tempUserBooking->coupon_ids = 0;
//                            $tempUserBooking->amount = number_format($amount_to_php, 2);
//                            $tempUserBooking->payment_status = 0;
//                            $tempUserBooking->save();
//
//                            $redirect_url = $mongo_success_url;
//
//                        } else {
//                            return response()->json([
//                                "status" => 0,
//                                "message" => "Payment transaction failed",
//                                "message_code" => 108,
//                            ]);
//                        }
//                    } catch (\Exception $e) {
//                        return response()->json([
//                            "status" => 0,
//                            "message" => $e->getMessage(),
//                            "message_code" => 108,
//                        ]);
//                    }
//                }
//                elseif ($request->get('payment_method_type') == 0) {
//                    $success_url = \URL::route('paypal.success');
//                    $failed_url = \URL::route('paypal.failed');
//
//                    $get_usd_currency = WorldCurrency::query()->where('symbol', "$")->first();
//                    if ($get_usd_currency == Null) {
//                        return response()->json([
//                            'status' => 0,
//                            'message' => "Something went to wrong!",
//                            "message_code" => 9,
//                        ]);
//                    }
//                    $settings = GeneralSettings::query()->first();
//                    if ($settings == Null && $settings->paypal_sandbox == Null && $settings->paypal_client_id == Null && $settings->paypal_client_secret_key == Null) {
//                        return response()->json([
//                            'status' => 0,
//                            'message' => "Something went to wrong!",
//                            "message_code" => 9,
//                        ]);
//                    }
//                    $amount_to_usd = number_format($place_order->total_pay * $get_usd_currency->ratio, 2);
//
//                    try {
//                        $user_id = $request->get('user_id');
//                        $service_category_id = $place_order->service_cat_id;
//                        $order_id = $place_order->id;
//
//                        $paypal_sandbox = $settings->paypal_sandbox . "";
//                        $paypal_client_id = $settings->paypal_client_id . "";
//                        $paypal_client_secret_key = $settings->paypal_client_secret_key . "";
//
//                        $api_context = new ApiContext(new OAuthTokenCredential($paypal_client_id, $paypal_client_secret_key));
//
//                        $settings = array(
//                            'mode' => $paypal_sandbox,
//                            'http.ConnectionTimeOut' => 30,
//                            'log.LogEnabled' => true,
//                            'log.FileName' => storage_path() . '/logs/paypal.log',
//                            'log.LogLevel' => 'ERROR'
//                        );
//                        $api_context->setConfig($settings);
//
//
//                        $payer = new Payer();
//                        $payer->setPaymentMethod('paypal');
//
//                        $item_1 = new Item();
//                        $item_1->setName('Item 1')
//                            ->setCurrency('USD')
//                            ->setQuantity(1)
//                            ->setPrice($amount_to_usd);
//
//                        $item_list = new ItemList();
//                        $item_list->setItems(array($item_1));
//
//                        $amount = new Amount();
//                        $amount->setCurrency('USD')
//                            ->setTotal($amount_to_usd);
//
//                        $transaction = new Transaction();
//                        $transaction->setAmount($amount)
//                            ->setItemList($item_list)
//                            ->setCustom($user_id . ',' . $service_category_id . ',' . $order_id)
//                            ->setDescription('Your transaction description');
//
//                        $redirect_urls = new RedirectUrls();
//                        $redirect_urls->setReturnUrl(\URL::route('payment.status'))/** Specify return URL **/
//                        ->setCancelUrl(\URL::route('payment.status'));
//
//                        $payment = new Payment();
//                        $payment->setIntent('Sale')
//                            ->setPayer($payer)
//                            ->setRedirectUrls($redirect_urls)
//                            ->setTransactions(array($transaction));
//
//                        try {
//                            $payment->create($api_context);
//                        } catch (\PayPal\Exception\PPConnectionException $ex) {
//                            if (\Config::get('app.debug')) {
//                                return response()->json([
//                                    "status" => 0,
//                                    "message" => "Connection timeout",
//                                    "message_code" => 9,
//                                ]);
//                            } else {
//                                return response()->json([
//                                    "status" => 0,
//                                    "message" => "your payment transaction failed",
//                                    "message_code" => 9,
//                                ]);
//                            }
//                        }
//                        foreach ($payment->getLinks() as $link) {
//                            if ($link->getRel() == 'approval_url') {
//                                $redirect_url = $link->getHref();
//                                break;
//                            }
//                        }
//                        \Session::put('paypal_payment_id', $payment->getId());
//                        if (isset($redirect_url)) {
//                            $place_order->payment_type = $request->get('payment_type');
////                        $place_order->payment_status = 1;
//                            $place_order->transaction_id = $payment->getId();
//                            $place_order->save();
//                        } else {
//                            return response()->json([
//                                "status" => 0,
//                                "message" => "your payment transaction failed",
//                                "message_code" => 108,
//                            ]);
//                        }
//                    } catch (\Exception $e) {
//                        return response()->json([
//                            "status" => 0,
//                            "message" => $e->getMessage(),
//                            "message_code" => 108,
//                        ]);
//                    }
//                }
//                else {
//                    return response()->json([
//                        "status" => 0,
//                        "message" => "something went to wrong",
//                        "message_code" => 9,
//                    ]);
//                }
//            }
        }
        elseif ($request->get('payment_type') == 3)
        {
            if ($place_order->payment_status == 0) {

                $wallet_balance = UserWalletTransaction::query()->where('user_id', $request->get('user_id'))->where('wallet_provider_type',0)->orderBy('id', 'desc')->first();
                if ($wallet_balance != Null) {
                    $balance = $wallet_balance->remaining_balance;
                } else {
                    $balance = 0;
                }
                if ($place_order->total_pay > $balance) {
                    return response()->json([
                        "status" => 0,
//                        "message" => "You can't pay order amount through Wallet because your wallet balance is insufficient.",
                        "message" => __('user_messages.110'),
                        "message_code" => 110
                    ]);
                }
                $add_balance = new UserWalletTransaction();
                $add_balance->user_id = $request->get('user_id');
                $add_balance->wallet_provider_type = $this->user_type;
                $add_balance->transaction_type = 2;
                $add_balance->amount = $place_order->total_pay;
                $add_balance->subject = "Paid to " . $place_order->provider_name . " - " . $service_category->name . " Booking # " . $place_order->order_no;
                $add_balance->remaining_balance = round($balance - $place_order->total_pay, 2);
                $add_balance->subject_code = 2;
                $add_balance->order_no = $place_order->order_no;
                $add_balance->save();

                //code for auto settle
                if(request()->get('general_settings')->auto_settle_wallet == 1) {
                    $provider_id = $place_order->provider_id;
                    $wallet_provider_type = 3;
                    $service_cat_id = $place_order->service_cat_id;
                    $transaction_type = 1;
                    $add_update_wallet_bal = $place_order->provider_amount;
                    $subject =  "Credited by Admin - " . $service_category->name . " Booking # " . $place_order->order_no;
                    $subject_code = 14;
                    $order_no = $place_order->order_no;

                    $driver_wallet_update = $this->notificationClass->providerUpdateWalletBalance($provider_id,$wallet_provider_type,$transaction_type,$add_update_wallet_bal,$subject,$subject_code,$order_no);

                    if($driver_wallet_update) {
                        $place_order->provider_pay_settle_status = 1;
                    }
                }
                //end code for auto settle

                $place_order->payment_type = $request->get('payment_type');
                $place_order->payment_status = 1;
                $place_order->save();
            }
        }
        else {
            return response()->json([
                "status" => 0,
//                "message" => "something went to wrong",
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }


        return response()->json([
            "status" => 1,
//            "message" => "success!",
            "message" => __('user_messages.1'),
            "message_code" => 1,
            "order_id" => $place_order->id,
            "order_no" => $place_order->order_no,
            "redirect_url" => $redirect_url,
            "success_url" => $success_url,
            "failed_url" => $failed_url
        ]);
    }

    //order cancel
    public function postOtherServiceOrderCancelled(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "order_id" => "required|numeric",
            "cancel_reason" => "nullable",
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
        if($user_details->language !="en"){
            $user_lang = $user_details->language."_";
        }else{
            $user_lang = "";
        }
        $order_details = UserPackageBooking::query()
            ->select('user_service_package_booking.*','service_category.'.$user_lang.'name as category_name',)
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->where('user_service_package_booking.id', $request->get('order_id'))
            ->first();
        if ($order_details == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "Order details not found",
                "message" => __('user_messages.59'),
                "message_code" => 59,
            ]);
        }
        if ($order_details->status == 5) {
            return response()->json([
                "status" => 0,
//                "message" => "order cancel by admin!",
                "message" => __('user_messages.75'),
                "message_code" => 75,
                "order_status" => 5,
            ]);
        }
        if ($order_details->status < 5) {
            if ($order_details->status == 4) {
                return response()->json([
                    'status' => 0,
//                    'message' => "order rejected by provider!",
                    'message' => __('user_messages.52'),
                    "message_code" => 52,
                ]);
            }
            //if ($order_details->order_type == 1 && $order_details->service_date_time != Null) {
            //    //$pickup_date_time = strtotime(date('Y-m-d H:i:s', strtotime('-1 hour', strtotime($order_details->service_date_time))));
            //    $pickup_date_time = strtotime(date('Y-m-d H:i:s', strtotime('-1 hour', strtotime($order_details->service_date_time))));
            //    $current_date_time = strtotime(date('Y-m-d H:i:s'));
            //    if ($current_date_time >= $pickup_date_time) {
            //        return response()->json([
            //            "status" => 0,
            //            "message" => "You cant cancel the service after 60 minutes of order time.",
            //            "message_code" => 148,
            //        ]);
            //    }
            //}
            $order_status = $order_details->status;
            $order_details->status = 5;
            $order_details->cancel_by = "user";
            if ($request->get('cancel_reason') != Null) {
                $order_details->cancel_reason = $request->get('cancel_reason');
            }
            $order_details->save();

            $provider_package_time = ProviderAcceptedPackageTime::query()->where('order_id','=',$request->get('order_id'))->first();
            if($provider_package_time != Null){
                $provider_package_time->delete();
            }

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
            }

            if($order_status >= 1 && $order_status <= 9 ){
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

            if ($order_details->order_type != 1) {
                if ($order_details->payment_type == 2 || $order_details->payment_type == 3) {
                    $service_settings = ServiceSettings::query()->where('service_cat_id', $order_details->service_cat_id)->first();
                    if ($service_settings != Null) {
                        $cancel_charge = $service_settings->cancel_charge != Null ? $service_settings->cancel_charge : 0;
                        $count_cancel_charge = number_format((($order_details->total_item_cost * $cancel_charge) / 100), 2,'.','');
                        $charges = number_format($order_details->total_pay - $count_cancel_charge, 2,'.','');
                        $order_details->cancel_charge = $count_cancel_charge;
                        $order_details->refund_amount = $charges;
                        $order_details->save();
                    }
                }
            }

            $other_provider_details = Provider::query()->where('id', $order_details->provider_id)->whereNull('providers.deleted_at')->first();
            if ($other_provider_details != Null) {
                $this->notificationClass->providerOrderCancelRequestNotification($order_details->id, $order_details->status, $other_provider_details->device_token, $other_provider_details->language);
            }
            //deleting chat from firebase
            (new FirebaseService())->deleteOrderChat($order_details->order_no, $order_details->id);
            $general_settings = GeneralSettings::query()->first();
            if ($general_settings !=  Null) {
                if ($general_settings->send_mail == 1) {
                    try{
                        $category_name = $order_details->category_name;
                        $cancel_reason = $order_details->cancel_reason;
                        if ($general_settings->send_receive_email != Null) {
                            $service_name = ucwords(strtolower($category_name));
                            $provider_name = ucwords($order_details->provider_name);
                            $user_name = $user_details->first_name."".$user_details->last_name;
                            $mail_type = "provider_booking_cancel_–_handyman_services";
                            $to_mail = $other_provider_details->email;
                            $subject = $user_name." has cancelled your " . $service_name . " service request ";
                            $disp_data = array("##cancel_reason##" => $cancel_reason,"##provider_name##" => $provider_name,"##service_name##" => $service_name, "##user_name##" => ucwords($user_name));
                            $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                        }
                    }
                    catch (\Exception $e){}
                }
            }
            return response()->json([
                'status' => 1,
//                'message' => "success!",
                'message' => __('user_messages.1'),
                "message_code" => 1,
                'order_current_status' => $order_details->status,
                'order_cancel_reject_reason' => $order_details->status == 4 ? 'Order Rejected By Provider' : ($order_details->status == 5 ? 'Order Cancelled By ' . ucwords(strtolower(trim($order_details->cancel_by))) : ''),
                'cancel_charge' => $order_details->cancel_charge - 0,
            ]);

        } else {
            return response()->json([
                'status' => 0,
//                'message' => "customer not allow to cancel this order!",
                'message' => __('user_messages.70'),
                "message_code" => 70,
            ]);
        }
    }

    public function postOtherServiceOrderAddTip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "order_id" => "required|numeric",
            "tip" => "required|numeric",
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

        $order_details = UserPackageBooking::query()->where('id', $request->get('order_id'))->first();
        if ($order_details == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "order not found",
                "message" => __('user_messages.59'),
                "message_code" => 59,
            ]);
        }
        $user_currency = WorldCurrency::query()->where('symbol', $user_details->currency)->first();
        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $user_currency->ratio;
        $service_setting = ServiceSettings::query()->where('service_cat_id', $order_details->service_cat_id)->first();
        if ($service_setting != Null) {
            $get_tax = $service_setting->tax;
            $admin_commission = $service_setting->admin_commission;
        } else {
            $get_tax = 0;
            $admin_commission = 5;
        }
        if ($order_details->tip < 1) {
//            $val = number_format($request->get('tip') / $currency, 2);
////            $reminder = $val % 10;
//            $reminder = fmod($val * 100,10);
//            if ($reminder >= 5) {
//                $val = number_format($val + 0.01, 2);
//            }
            $tip = number_format($request->get('tip') / $currency, 2, '.', '');
            $order_details->tip = $tip;
            $order_details->total_pay = number_format(($order_details->total_pay + $order_details->tip), 2,'.','');
//            $order_details->admin_commission = round(($order_details->total_pay * $admin_commission) / 100, 2);
            $order_details->provider_amount = number_format(($order_details->total_pay - $order_details->tax - $order_details->admin_commission), 2,'.','');
            $order_details->save();
        }
        $user_lang = isset($user_details->language)?$user_details->language:"";
        return $this->userClassapi->OtherServiceOrderDetails($order_details->id, false,Null,$user_lang);
//        return response()->json([
//            "status" => 1,
//            "message" => "success",
//            "message_code" => 1,
//        ]);
    }

    public function postOtherServiceOrderRating(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "order_id" => "required|numeric",
            "rating" => "nullable|numeric",
            "comment" => "nullable",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'message_code' => 9
            ]);
        }

        $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_details) == false) {
            return $user_details;
        }

        $order_details = UserPackageBooking::query()->where('id', $request->get('order_id'))->first();
        if ($order_details != Null) {
            $check_duplicate = OtherServiceRatings::query()->where('provider_id', $order_details->provider_id)->where('booking_id', $order_details->id)->first();
            if ($check_duplicate == Null) {
                $provider_rating = new OtherServiceRatings();
                $provider_rating->user_id = $order_details->user_id;
                $provider_rating->provider_id = $order_details->provider_id;
                $provider_rating->booking_id = $order_details->id;
                $provider_rating->rating = number_format($request->get('rating'), 2);
                if ($request->get('comment')) {
                    $provider_rating->comment = $request->get('comment');
                }
                $provider_rating->status = 1;
                $provider_rating->save();

                $order_details->user_rating_status = 1;
                $order_details->save();

                $provider_details = OtherServiceProviderDetails::query()->where('provider_id', $order_details->provider_id)->first();
                if ($provider_details != Null) {
                    $ratings = OtherServiceRatings::query()
//                            ->select(DB::raw('avg(rating) as ratings'))
                        ->groupBy('provider_id')
                        ->where('provider_id', $order_details->provider_id)
//                            ->first();
                        ->avg('rating');
                    $provider_details->rating = number_format($ratings, 1);
//                    $provider_details->total_completed_order = $provider_details->total_completed_order + 1;
                    $provider_details->save();
                }
            }
            return response()->json([
                'status' => 1,
//                'message' => "success!",
                'message' => __('user_messages.1'),
                'message_code' => 1,
            ]);
        } else {
            return response()->json([
                'status' => 0,
//                'message' => "order details not found",
                'message' => __('user_messages.9'),
                'message_code' => 9
            ]);
        }
    }

    //code for new home api
    public function postOtherServiceHome(Request $request) {
        $this->notificationClass->ApiLogDetail($logger_type = 0, $request->get('user_id'), "postOtherServiceHome", $request->all());
        //Validation check
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable|numeric",
            "service_category_id" => "required|numeric",
            "lat" => "required|numeric",
            "long" => "required|numeric",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_details = null;
        if ($request->get('user_id') <> null && $request->get('access_token') <> null){
            $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($user_details) == false) {
                return $user_details;
            }
        }

        //Language Code
        $language = $user_details != null ? $user_details->language : $request->header("select-language");
        if($language !="en" && $language != "" && $language != "Null" ){
            $language = $language."_";
        }else{
            $language ="";
        }
        $service_cat_id = $request->get('service_category_id');
        $service = ServiceCategory::query()->select('category_type')->where('id',$service_cat_id)->first();
        //Check service
        if($service == NULL){
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
        $lat = $request->get('lat');
        $long = $request->get('long');
        $service_categories = OtherServiceCategory::query()->select('other_service_sub_category.id', 'other_service_sub_category.'.$language.'name as name',
            'other_service_sub_category.icon_name')
            ->where('other_service_sub_category.service_cat_id','=', $service_cat_id)
            ->where('other_service_sub_category.status', '=',1)
            ->get();
        //Check Other Service Category
        if($service_categories->isEmpty()){
            return response()->json([
                'status' => 0,
//                'message' => 'Service Category not found!',
                'message' =>__('user_messages.124'),
                "message_code" => 124,
            ]);
        }

        $category = [];
            foreach ($service_categories as $service_category) {
                $icon = $service_category->icon_name != Null ? $service_category->icon_name : Null;
                $category[] = [
                    "category_id" => $service_category->id,
                    "category_name" => $service_category->name,
                    "category_icon" => $icon
                ];
            }

        $default_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        $user_currency = null;
        if ($user_details != null) {
            $user_currency = WorldCurrency::query()->where('symbol', $user_details->currency)->first();
        }
        $user_currency = $user_currency ?? $default_currency;
        $sponsor_provider_lists =  [];
        $currency = $user_currency->ratio;
            $providers = Provider::query()->select('providers.id',
                DB::raw("CONCAT(COALESCE(providers.first_name,''),' ') as name"),
                'providers.gender', 'providers.avatar', 'providers.service_radius', 'provider_services.id as provider_service_id',
                'other_service_provider_details.rating', 'other_service_provider_details.total_completed_order',
                'other_service_provider_details.time_slot_status',
                'other_service_provider_details.lat as provider_lat',
                'other_service_provider_details.long as provider_long',
                DB::raw("
                    ROUND(
                        6367 * acos(
                            cos(radians($lat))
                            * cos(radians(other_service_provider_details.lat))
                            * cos(radians(other_service_provider_details.long) - radians($long))
                            + sin(radians($lat)) * sin(radians(other_service_provider_details.lat))
                        ),
                    2) AS distance
                "))
                ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
                ->join('other_service_provider_packages', 'other_service_provider_packages.provider_service_id', '=', 'provider_services.id')
                ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
                ->join('other_service_sub_category','other_service_sub_category.id','=','other_service_provider_packages.sub_cat_id')
                ->where('provider_services.service_cat_id', $service_cat_id)
                //->where('other_service_provider_packages.sub_cat_id', $sub_cat_id)
                ->where('provider_services.current_status', 1)
                ->where('other_service_sub_category.status',1)
                ->where('provider_services.status', 1)
                ->where('providers.status', 1)
                ->whereNull('providers.deleted_at')
                ->where('provider_services.is_sponsor', 1)
                //->where('other_service_provider_details.time_slot_status', 1)
                ->where('other_service_provider_packages.status',1);
            $providers = $this->userClassapi->checkDayShowRecord($providers, 0);
            $providers = $providers->havingRaw('providers.service_radius >= distance')->distinct()->get();

            foreach ($providers as $provider) {
                $total_provider_rating = OtherServiceRatings::query()->where('status', 1)->where("provider_id",$provider->id);
                $average_rating = (clone $total_provider_rating)->groupBy('provider_id')->avg('rating');

                $provider_rating = $provider->rating;
                $provider_sub_category_list = OtherServiceProviderPackages::query()->select('other_service_sub_category.'.$language.'name as sub_cat_name')
                    ->join('other_service_sub_category', 'other_service_sub_category.id', '=', 'other_service_provider_packages.sub_cat_id')
                    ->where('other_service_provider_packages.provider_service_id', '=', $provider->provider_service_id)
                    ->where('other_service_provider_packages.service_cat_id', '=', $service_cat_id)
                    ->groupBy('other_service_provider_packages.sub_cat_id')
                    ->get()->pluck("sub_cat_name");

                if ($provider->avatar != Null) {
                    $avatar = url('/assets/images/profile-images/provider/' . $provider->avatar);
                } else {
                    $avatar = '';
                }
                $distance = ($provider->distance != 0) ? number_format($provider->distance, 1) : 0;

                /* check time provide has any selected time slot or not */
                $selected_timing = $this->userClassapi->getProviderSelectedTiming($provider->id);

                $sponsor_provider_lists[] = [
                    'provider_id' => $provider->id,
                    'provider_name' => trim($provider->name),
                    'provider_profile_image' => $avatar,
                    'provider_gender' => $provider->gender,
                    'average_rating' => round($average_rating,2),
                    'distance' => $distance,
                    'total_completed_order' => $provider->total_completed_order,
                    'provider_sub_category_list' => !empty($provider_sub_category_list)?$provider_sub_category_list:[],
                    "time_slot_status" => (isset($selected_timing) && $selected_timing == true) ? $provider->time_slot_status : 0,
                ];
            }

            //code for slider dispaly on home page with category wise
            $service_slider_path = url('/assets/images/service-slider-banner/');
            $get_service_slider =  ServiceSliderBanner::query()
                ->select('service_slider_banner.service_cat_id as service_category_id', 'service_slider_banner.ondemand_cat_id as sub_category_id', DB::raw("(CASE WHEN service_slider_banner.banner_image != '' THEN (concat('$service_slider_path','/',service_slider_banner.banner_image)) ELSE '' END) as slider_banner_image"))
                ->join('other_service_sub_category', 'other_service_sub_category.id', '=', 'service_slider_banner.ondemand_cat_id')

                ->where('service_slider_banner.service_cat_id', '=', $service_cat_id)
                ->where('service_slider_banner.type', '=', 2)
                ->where('service_slider_banner.status', '=', 1)
                ->where('other_service_sub_category.status', '=', 1)
                ->get();

            return response()->json([
                'status' => 1,
//                'message' => 'success!',
                'message' =>__('user_messages.1'),
                "message_code" => 1,
                "service_slider" => $get_service_slider,
                'category_list' => $category,
                "sponsor_provider_lists" => $sponsor_provider_lists
            ]);
    }

    public function postOtherServiceProviderDetails(Request $request) {
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable", // string or numeric (customer vs provider/seller agents)
            "provider_id" => "required|numeric",
            "service_category_id" => "required|numeric",
            "lat" => "required|numeric",
            "long" => "required|numeric"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        // Only validate user when both user_id and access_token are present (customer). Do not validate user when only provider_id is sent (seller/agent).
        $user_details = $user_currency = null;
        if ($request->get('user_id') <> null && $request->get('access_token') <> null) {
            $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
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

        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }

        $currency = $user_currency->ratio;
        $service_cat_id = $request->get('service_category_id');
        $provider_id = $request->get('provider_id');
        $lat = $request->get('lat');
        $long = $request->get('long');
        if($language !="en" && $language != "" && $language != "Null" ){
            $language = $language."_";
        }else{
            $language ="";
        }
        $provider_profile_path = url('/assets/images/profile-images/provider/');
        $service_category_icon_url = url('/assets/images/service-category/');
        $providers = Provider::query()->select('providers.id',
            DB::raw("CONCAT(COALESCE(providers.first_name,''),' ') as name"),
            'providers.gender',
            'providers.avatar',
            'providers.service_radius',
            'provider_services.id as provider_service_id',
            'other_service_provider_details.rating',
            'other_service_provider_details.total_completed_order',
            'service_category.'.$language.'name as service_category_name',
            'service_category.icon_name as service_category_icon',
            DB::raw("(CASE WHEN providers.avatar != '' THEN (concat('$provider_profile_path','/',providers.avatar)) ELSE '' END) as avatar"),
            DB::raw("(CASE WHEN providers.avatar != '' THEN (concat('$provider_profile_path','/',providers.avatar)) ELSE '' END) as avatar"),
            DB::raw("(CASE WHEN service_category.icon_name != '' THEN (concat('$service_category_icon_url','/',service_category.icon_name)) ELSE '' END) as service_category_icon"),
            DB::raw(('other_service_provider_details.lat,other_service_provider_details.long, ( ROUND( 6367 * acos( cos( radians(' . $lat . ') ) * cos( radians( other_service_provider_details.lat ) ) * cos( radians( other_service_provider_details.long ) - radians(' . $long . ') ) + sin( radians(' . $lat . ') ) * sin( radians( other_service_provider_details.lat ) ) ),2 ) ) AS distance'))
        )
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->join('other_service_provider_packages', 'other_service_provider_packages.provider_service_id', '=', 'provider_services.id')
            ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            ->where('provider_services.service_cat_id', $service_cat_id)
            ->where('provider_services.current_status', 1)
            ->where('provider_services.status', 1)
            //->where('other_service_provider_details.time_slot_status', 1)
            ->where('providers.status', 1)
            ->whereNull('providers.deleted_at')
            //->where('provider_services.is_sponsor', 1)
            ->where('other_service_provider_packages.status',1)
            ->where('providers.id','=',$provider_id)
            ->distinct()
            ->first();

        //average of total ratings
        $ratings = OtherServiceRatings::query()
            ->groupBy('provider_id')
            ->where('provider_id', $provider_id)
            ->where('status', 1)
            ->avg('rating');

        $provider_details = [];
        if($providers == Null){
            return response()->json([
                "status" => 0,
//                "message" => "provider not found!",
                "message" => __('user_messages.73'),
                "message_code" => 9,
            ]);
        }
        $distance = ($providers->distance != 0) ? round($providers->distance, 1) : 0;
        return response()->json([
            'status' => 1,
//            'message' => 'success!',
            'message' => __('user_messages.1'),
            "message_code" => 1,
            'provider_id' => $providers->id,
            'provider_name' => trim($providers->name),
            'provider_profile_image' => $providers->avatar,
            'provider_gender' => $providers->gender,
            'average_rating' => round($ratings,2) - 0,
            'distance' => $distance ,
            'total_completed_order' => $providers->total_completed_order,
            'service_category_name' => $providers->service_category_name,
            'service_category_icon' => $providers->service_category_icon,
        ]);
    }

    public function postOtherServiceProviderReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable|numeric",
            "provider_id" => "required|numeric",
            "service_category_id" => "required|numeric",
            "page" => "required|numeric",
            "per_page" => "nullable|numeric",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user_details = null;
        if ($request->get('user_id') <> null && $request->get('access_token')){
            $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($user_details) == false) {
                return $user_details;
            }
        }

        if ($user_details != null) {
            $language = $user_details->language;
        } else {
            $language = $request->header("select-language");
        }

        $service_cat_id = $request->get('service_category_id');
        $provider_id = $request->get('provider_id');

        if($language !="en" && $language != "" && $language != "Null" ){
            $language = $language."_";
        }else{
            $language ="";
        }
        if($request->get('per_page') > 0 )
        {
            $per_page_rec = ($request->get('per_page') > 0)?$request->get('per_page')-0:30;
        }else{
            $per_page_rec = ($request->get('page') > 0)?$this->provider_ratting_limit:30;
        }


        $user_profile_path = url('/assets/images/profile-images/customer/');
//        $get_provider_review = UserRatings::query()
//                                ->select('users.id as user_id',
//                                    DB::raw("CONCAT(COALESCE(users.first_name,''),' ') as user_name"),
//                                    DB::raw("(CASE WHEN users.avatar != '' THEN (concat('$user_profile_path','/',users.avatar)) ELSE '' END) as user_profile_image"),
//                                    'user_rating.rating as rating','user_rating.comment as comment','user_rating.created_at as ratting_date'
//                                    )
//                                ->join('user_service_package_booking','user_service_package_booking.id','=','user_rating.package_book_id')
//                                ->join('service_category','service_category.id','=','user_service_package_booking.service_cat_id')
//                                ->join('users','users.id','=','user_service_package_booking.user_id')
//                                ->where('service_category.id','=',$service_cat_id)
//                                ->where('user_rating.provider_id','=',$provider_id)
////                                ->get();
//                                ->paginate($per_page_rec);
        $get_provider_review = OtherServiceRatings::query()
            ->select('users.id as user_id',
                DB::raw("CONCAT(COALESCE(users.first_name,''),' ') as user_name"),
                DB::raw("(CASE WHEN users.avatar != '' THEN (concat('$user_profile_path','/',users.avatar)) ELSE '' END) as user_profile_image"),
                'other_service_rating.rating as rating','other_service_rating.comment as comment','other_service_rating.created_at as ratting_date'
            )
            ->join('user_service_package_booking','user_service_package_booking.id','=','other_service_rating.booking_id')
            ->join('service_category','service_category.id','=','user_service_package_booking.service_cat_id')
            ->join('users','users.id','=','user_service_package_booking.user_id')
            ->where('other_service_rating.status','=',1)
            ->where('service_category.id','=',$service_cat_id)
            ->where('other_service_rating.provider_id','=',$provider_id)
            ->orderBy('ratting_date','desc')
//                                ->get();
            ->paginate($per_page_rec);

        $provider_review_lists = [];
        if($get_provider_review != Null){
            foreach ($get_provider_review as $key=>$single_provider_list){

                $provider_review_lists[]= [
                    'user_id' => $single_provider_list->user_id,
                    'user_name' => $single_provider_list->user_name,
                    'user_profile_image' => $single_provider_list->user_profile_image,
                    'rating' => $single_provider_list->rating-0,
                    'comment' => ($single_provider_list->comment !="")?$single_provider_list->comment:"",
                    'ratting_date' => ($single_provider_list->ratting_date !="")?$single_provider_list->ratting_date:date("Y-m-d H:i:s"),
                ];
            }
        }

        return response()->json([
            'status' => 1,
//            'message' => 'success!',
            'message' => __('user_messages.1'),
            "message_code" => 1,
            'provider_review_list' => $provider_review_lists,
            'total_page' =>($get_provider_review->total() > 0) ? ceil($get_provider_review->total() / $per_page_rec) : 0,
            'per_page' =>($get_provider_review->perPage() > 0) ? $get_provider_review->perPage() : 0,
            'current_page' =>($get_provider_review->currentPage() > 0) ? $get_provider_review->currentPage() : 0
        ]);

    }

    public function postOtherServiceProviderGallery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable|numeric",
            "provider_id" => "required|numeric",
            "service_category_id" => "required|numeric",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_details = null;
        if ($request->get('user_id') <> null && $request->get('access_token') <> null){
            $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($user_details) == false) {
                return $user_details;
            }
        }

        if ($user_details != null) {
            $language = $user_details->language;
        } else {
            $language = $request->header("select-language");
        }
        if($language !="en" && $language != "" && $language != "Null" ){
            $lang_prefix = $language."_";
        }else{
            $lang_prefix ="";
        }

        $service_cat_id = $request->get('service_category_id');
        $provider_id = $request->get('provider_id');

        $provider_portfolio_images = url('/assets/images/provider-portfolio-images');
        $get_provider_portfolio_images = ProviderPortfolioImage::query()
            ->select('id as id',
                DB::raw("(CASE WHEN image != '' THEN (concat('$provider_portfolio_images','/',image)) ELSE '' END) as provider_portfolio_image")
            )
            ->where('service_cat_id','=',$service_cat_id)
            ->where('provider_id','=',$provider_id)
            ->where('status','=',1)
            ->get()
            ->toArray();

        return response()->json([
            'status' => 1,
//            'message' => 'success!',
            'message' => __('user_messages.1'),
            "message_code" => 1,
            'portfolio_images' => $get_provider_portfolio_images,
        ]);
    }

    public function postOtherServiceProviderTimeListBKP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "provider_id" => "required|numeric",
            "select_date" => "required",
            "timezone" => "nullable"
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

        $timezone = $request->get('timezone') != Null ? $request->get('timezone') : "Asia/Kolkata";
        date_default_timezone_set($timezone);
        $provider_id = $request->get('provider_id');
        $select_date = $request->get('select_date');

        $select_day = strtoupper(substr(date('l', strtotime($select_date)), '0', '3'));
        $select_date = date('Y-m-d', strtotime($select_date));
        $current_date = date('Y-m-d');
        $current_time = date('h').":00 ".date('A');
        $other_provider_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_id)->first();
        if ($other_provider_details == Null) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
//        $provider_open_time_list = [];
        $time_list = [];

        if ($other_provider_details->time_slot_status == 1) {
            $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', '=', $select_day)->first();
            if ($get_open_timing != Null) {

                $delete_array = [];
                if($current_date == $select_date && $current_time != "11:00 PM" && $current_time != "12:00 AM")
                {
                    $default_time = array(
                        '12:00 AM', '01:00 AM',
                        '02:00 AM', '03:00 AM',
                        '04:00 AM', '05:00 AM',
                        '06:00 AM', '07:00 AM',
                        '08:00 AM', '09:00 AM',
                        '10:00 AM', '11:00 AM',
                        '12:00 PM', '01:00 PM',
                        '02:00 PM', '03:00 PM',
                        '04:00 PM', '05:00 PM',
                        '06:00 PM', '07:00 PM',
                        '08:00 PM', '09:00 PM',
                        '10:00 PM', '11:00 PM'
                    );
                    $last_time = (array_search($current_time, $default_time, true));
                    if (is_integer($last_time)) {
                        for ($i = 0; $i <= $last_time; $i++) {
                            if ($i != 23) {
                                $delete_array[] = $default_time[$i] . ' - ' . $default_time[$i + 1];
                            } else {
                                $delete_array[] = $default_time[$i] . ' - ' . $default_time[0];
                            }
                        }
                    }
                }
                $timings = explode(',', $get_open_timing->open_time_list);
                foreach ($timings as $timing) {
                    $check_provider_accepted_time = ProviderAcceptedPackageTime::query()->where('provider_id', $provider_id)->where('date', '=', $select_date)->where('time', '=', $timing)->first();
                    if ($check_provider_accepted_time == Null) {
                        if(!in_array($timing,$delete_array))
                        {
                            $time_list[] = ['slot' => $timing];
                        }
                    }
                }
//            $provider_open_time_list = [
//                "day" => $select_day,
//                "date" => $select_date,
//                "provider_time_list" => $time_list
//            ];
            }

        }
        return response()->json([
            "status" => 1,
            "message" => __('user_messages.1'),
            "message_code" => 1,
            "provider_time_list" => $time_list
//            "provider_open_time_list" => $provider_open_time_list
        ]);
    }

    public function postOtherServiceProviderTimeList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "provider_id" => "required|numeric",
            "select_date" => "required",
            "timezone" => "nullable"
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
        $default_server_timezone= "";
        $general_settings = request()->get("general_settings");
        if ($general_settings != Null) {
            if ($general_settings->default_server_timezone != "") {
                $default_server_timezone= $general_settings->default_server_timezone;
            }
        }

        $timezone = $user_details->time_zone != Null ? $user_details->time_zone : $default_server_timezone;
        if ($user_details !== null) {
            $user_timezone = $this->notificationClass->getDefaultTimeZone($timezone);
            date_default_timezone_set($user_timezone);
        }

        $provider_id = $request->get('provider_id');
        $select_date = $request->get('select_date');

//        $select_day = strtoupper(substr(date('l', strtotime($select_date)), '0', '3'));
        $select_day = strtoupper(date("D",strtotime($select_date)));
        $select_date = date('Y-m-d', strtotime($select_date));
        $current_date = date('Y-m-d');
        $current_time = date("H:i:s");
        $other_provider_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_id)->first();
        if ($other_provider_details == Null) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
        //$provider_open_time_list = [];
        $time_list = [];
        $default_start_time= ($general_settings->default_start_time != Null)?$general_settings->default_start_time:"00:00:00";
        $default_end_time= ($general_settings->default_end_time != Null)?$general_settings->default_end_time:"23:59:59";
        $notificationClass = New NotificationClass();
        $default_provider_open_close_time =  $notificationClass->defaultProviderOpenCloseTime($default_start_time,$default_end_time);
        $default_provider_slot= isset($default_provider_open_close_time['default_provider_slot'])?$default_provider_open_close_time['default_provider_slot']:[];

        if ($other_provider_details->time_slot_status == 1) {
            $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', '=', $select_day)->get();
            if (!empty($get_open_timing) && count($get_open_timing)> 0) {
                foreach ($default_provider_slot as $key => $single_provider_slot){
                    $disabled = 1;
                    foreach ($get_open_timing as $get_single_open_timing) {
                        if($single_provider_slot['start_time'] == $get_single_open_timing->provider_open_time && $single_provider_slot['end_time'] == $get_single_open_timing->provider_close_time ) {
                            $selected_date_time = $select_date." ".$get_single_open_timing->provider_open_time;
                            $current_date_time = date("Y-m-d H:i:s");
                            if(strtotime($selected_date_time) >= strtotime($current_date_time)) {
                                $check_provider_accepted_time = ProviderAcceptedPackageTime::query()->where('provider_id', $provider_id)
                                    ->where('date', '=', $select_date)
                                    ->where('book_start_time', '=', $single_provider_slot['start_time'])
                                    ->where('book_end_time', '=', $single_provider_slot['end_time'])
                                    ->first();
                                if($check_provider_accepted_time == Null){
                                    $disabled = 0;
                                } else {
                                    $disabled = 1;
                                }
                            } else {
                                $disabled = 1;
                            }
                        }
                    }
                    $time_list[] = array(
                        'start_time'=>$single_provider_slot['start_time'],
                        'end_time'=>$single_provider_slot['end_time'],
                        'display_start_time' => date("h:i A", strtotime($single_provider_slot['start_time'])),
                        'display_end_time' => date("h:i A", strtotime($single_provider_slot['end_time'])),
                        'disabled'=>$disabled,
                    );
                }
            } else {
                foreach ($default_provider_slot as $key=>$single_provider_slot){
                    $disabled = 1;
                    $time_list[] = array(
                        'start_time'=>$single_provider_slot['start_time'],
                        'end_time'=>$single_provider_slot['end_time'],
                        'display_start_time' => date("h:i A", strtotime($single_provider_slot['start_time'])),
                        'display_end_time' => date("h:i A", strtotime($single_provider_slot['end_time'])),
                        'disabled'=>$disabled,
                    );
                }
            }
        } else {
            foreach ($default_provider_slot as $key=>$single_provider_slot){
                $disabled = 1;
                $time_list[] = array(
                    'start_time'=>$single_provider_slot['start_time'],
                    'end_time'=>$single_provider_slot['end_time'],
                    'display_start_time' => date("h:i A", strtotime($single_provider_slot['start_time'])),
                    'display_end_time' => date("h:i A", strtotime($single_provider_slot['end_time'])),
                    'disabled'=>$disabled,
                );
            }
        }
        return response()->json([
            "status" => 1,
            "message" => __('user_messages.1'),
            "message_code" => 1,
            "provider_time_list" => $time_list
        ]);
    }
}
