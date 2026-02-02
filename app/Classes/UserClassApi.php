<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 13-12-2018
 * Time: 03:48 PM
 */

namespace App\Classes;

use App\Models\GeneralSettings;
use App\Models\OtherServiceCategory;
use App\Models\OtherServiceProviderDetails;
use App\Models\OtherServiceProviderPackages;
use App\Models\OtherServiceProviderTimings;
use App\Models\OtherServiceRatings;
use App\Models\PromocodeDetails;
use App\Models\Provider;
use App\Models\RestrictedArea;
use App\Models\ServiceCategory;
use App\Models\UsedPromocodeDetails;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserCardDetails;
use App\Models\UserPackageBooking;
use App\Models\UserPackageBookingQuantity;
use App\Models\UserWalletTransaction;
use App\Models\WorldCurrency;
use App\Services\FirebaseService;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;


class UserClassApi
{
//        json response status [
//            0 => false,
//            1 => true,
//            2 => registration pending,
//            3 => app user blocked,
//            4 => app user access token not match,
//            5 => app user not found
//          ]

    private $notificationClass;
    private $adminClassApi;
    private $user_type = 0;
    private $provider_type = 3;

    public function __construct(NotificationClass $notificationClass , AdminClass $adminClassApi)
    {
        $this->notificationClass = $notificationClass;

        $this->adminClassApi = $adminClassApi;

    }

    public function generateBookingNo()
    {
        return random_int(1, 9) . date('siHYdm') . random_int(1, 9);
    }

    public function checkUserAllow($user_id, $access_token){
        $user_details = User::query()->select('id', 'first_name', 'last_name', 'email', 'verified_at', 'country_code','contact_number', 'login_type', 'login_id', 'password', 'avatar', 'invite_code', 'access_token', 'device_token', 'status as user_status', 'currency', 'pending_refer_discount', 'language','ip_address','time_zone','is_default_user','fix_user_show','emergency_contact')
            ->where('id',"=", $user_id)->whereNull('users.deleted_at')->first();
        if ($user_details != Null) {
            if ($user_details->access_token != $access_token) {
                return response()->json([
                    'status' => 4,
//                    'message' => "Access Token Not Match!",
                    'message' => __('user_messages.4'),
                    "message_code" => 4,
                ]);
            }
            if ($user_details->verified_at == Null) {
                return response()->json([
                    'status' => 2,
//                    'message' => "User Not Verified!",
                    'message' => __('user_messages.2'),
                    "message_code" => 2,
                ]);
            }
            if ($user_details->user_status == 0) {
                return response()->json([
                    'status' => 3,
//                    'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                    'message' => __('user_messages.3'),
                    "message_code" => 3,
                ]);
            }
            return $user_details;
        } else {
            return response()->json([
                'status' => 5,
//                'message' => "User not found!",
                'message' => __('user_messages.5'),
                "message_code" => 5,
            ]);
        }
    }

    public function userLoginRegisterUpdateDetails($user_details)
    {
        if ($user_details['avatar'] != Null) {
            if (filter_var($user_details['avatar'], FILTER_VALIDATE_URL) == true) {
                $avatar = $user_details['avatar'];
            } else {
                $avatar = url('/assets/images/profile-images/customer/' . $user_details['avatar']);
            }
        } else {
            $avatar = Null;
        }

        if ($user_details['currency'] != Null) {
            $user_currency = WorldCurrency::query()->where('symbol', $user_details['currency'])->first();
            if ($user_currency == Null) {
                $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
            }
        } else {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency_code = $user_currency->currency_code;

        return response()->json([
            'status' => 1,
//            'message' => 'Success!',
            'message' => __('user_messages.1'),
            "message_code" => 1,
            'user_id' => $user_details['id'],
            'user_verified' => $user_details['verified_at'] != Null ? 1 : 0,
            'user_name' => $user_details['first_name'],
            'access_token' => $user_details['access_token'] . '',
            'email' => $user_details['email'] != Null ? $user_details['email'] : "",
            'login_type' => $user_details['login_type'],
            'profile_image' => $avatar,
            'gender' => $user_details['gender'],
            'contact_number' => $user_details['contact_number'] != Null ? $user_details['contact_number'] : "",
            'referral_code' => $user_details['invite_code'] != Null ? $user_details['invite_code'] : "",
            'select_country_code' => $user_details['country_code'] != Null ? $user_details['country_code'] : "",
            'select_currency' => $user_details['currency'] != Null ? $user_details['currency'] : "",
            'currency_code' => $currency_code,
            'select_language' => $user_details['language'] != Null ? $user_details['language'] : "",
            'emergency_contact' => $user_details['emergency_contact'] != Null ? $user_details['emergency_contact'] . '' : "",
            'server_time_zone' => config('app.timezone'),
        ]);
    }

    public function ReferralApply($admin_referal_type=0,$admin_referral_amount=0,$single_store_item_cost=0,$all_store_item_total=0){
        //admin_promo_type : 1: Amount, 2: Percentage
        $referral_discount_amount = 0;
        if($admin_referal_type > 0 && $admin_referral_amount > 0 ){
            if($admin_referal_type == 1){
                //code for amount wise discount
                $referral_discount_amount= $admin_referral_amount;

            }else{
                //code for percentage wise discount
                $referral_discount_amount = ($single_store_item_cost*$admin_referral_amount)/100;
            }
        }
        return ($referral_discount_amount >= 0 )?$referral_discount_amount:0;
    }

    public function checkPromoCodeValid($promo_id, $amount, $user_id, $service_cat_id)
    {
        $current_date = date('y-m-d h:i:s');
        $get_promocode = PromocodeDetails::query()->where('id', $promo_id)->where('service_cat_id', $service_cat_id)->where('expiry_date_time', '>=', $current_date)->where('status', 1)->first();
        if ($get_promocode != Null) {
            try {
                $get_user_used = UsedPromocodeDetails::query()->where('user_id', $user_id)->where('promocode_id', $promo_id)->whereIn("status",[0,1])->count();
                $get_user_detail = User::query()->select('currency')->where('id','=',$user_id)->whereNull('users.deleted_at')->first();
                $user_currency = "";
                $currency_ratio = 1;
                if($get_user_detail != Null){
                    $user_currency = ($get_user_detail->currency != Null) ? $get_user_detail->currency : "$";

                    $world_currency = WorldCurrency::query()->where('symbol', $user_currency)->first();
                    if ($world_currency == Null) {
                        $world_currency = WorldCurrency::query()->where('default_currency', 1)->first();
                    }
                    $currency_ratio = $world_currency->ratio;
                }
            } catch (\Exception $e) {
                $get_user_used = 0;
            }
            if ($get_promocode->coupon_limit != 0) {
                if ($get_promocode->coupon_limit > $get_promocode->total_usage) {
                    if ($get_promocode->usage_limit > $get_user_used) {
                        if ($get_promocode->min_order_amount != Null) {
                            if ($get_promocode->min_order_amount <= $amount) {
                                if ($get_promocode->discount_type == 1) {
                                    $new_price = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);
                                    $new_discount = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);

                                } elseif ($get_promocode->discount_type == 2) {
                                    $new_price = round((($amount) - (($amount) * ($get_promocode->discount_amount)) / (100)), 2);
                                    $new_discount = round((($amount) * ($get_promocode->discount_amount)) / (100), 2);
                                } else {
//                                    return "zero";
//                                    return array('promo_code_status'=>0,'promo_message_code'=>9,'promo_code_message'=>"something went to wrong!",'min_order_amount'=>0,'promo_code_amount'=>0,'promo_code_name'=>"",
//                                    return array(0,9,"something went to wrong!",0,0,"");
                                    return array(0,9,__('user_messages.9'),0,0,"");
                                }
                                if ($get_promocode->max_discount_amount != Null) {
                                    if ($get_promocode->max_discount_amount < $new_discount) {
//                                        return number_format($get_promocode->max_discount_amount, 2);
                                        return array(1,1,__('user_messages.1'),0,round($get_promocode->max_discount_amount, 2),$get_promocode->promo_code);
                                    } else {
//                                        return number_format($new_discount, 2);
                                        return array(1,1,__('user_messages.1'),0,round($new_discount, 2),$get_promocode->promo_code);
                                    }
                                } else {
//                                    return number_format($new_discount, 2);
                                    return array(1,1,__('user_messages.1'),0,round($new_discount, 2),$get_promocode->promo_code);
                                }
                            } else {
//                                return "seven";
                                $min_order_amount = 0;
                                if($get_promocode != Null)
                                {
//                                    $min_order_amount = $get_promocode->min_order_amount." ".$user_currency;
                                    $min_order_amount = round($get_promocode->min_order_amount * $currency_ratio, 2);

                                }
//                                return array(0,235,"The promocode only applies if the order amount is greater than amount ",$min_order_amount,0,"");
                                return array(0,235,__('user_messages.235', ['value' => $user_currency." ".$min_order_amount]),$min_order_amount,0,"");
                            }
                        } else {
                            if ($get_promocode->discount_type == 1) {
                                $new_price = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);
                                $new_discount = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);
                            } elseif ($get_promocode->discount_type == 2) {
                                $new_price = round((($amount) - (($amount) * ($get_promocode->discount_amount)) / (100)), 2);
                                $new_discount = round((($amount) * ($get_promocode->discount_amount)) / (100), 2);
                            } else {
                                return array(0,9,__('user_messages.9'),0,0,"");
                            }
                            if ($get_promocode->max_discount_amount != Null) {
                                if ($get_promocode->max_discount_amount < $new_discount) {
//                                    return number_format($get_promocode->max_discount_amount, 2);
                                    return array(1,1,__('user_messages.1'),0,number_format($get_promocode->max_discount_amount, 2, '.', ''),$get_promocode->promo_code);
                                } else {
//                                    return number_format($new_discount, 2);
                                    return array(1,1,__('user_messages.1'),0,number_format($new_discount, 2, '.', ''),$get_promocode->promo_code);
                                }
                            } else {
//                                return number_format($new_discount, 2);
                                return array(1,1,__('user_messages.1'),0,number_format($new_discount, 2, '.', ''),$get_promocode->promo_code);
                            }
                        }
                    } else {
//                        return "two";
                        return array(0,177,__('user_messages.177'),0,0,"");
                    }
                } else {
//                    return "eight";
                    return array(0,236,__('user_messages.236'),0,0,"");
                }
            } else {
                if ($get_promocode->usage_limit > $get_user_used) {
                    if ($get_promocode->min_order_amount != Null) {
                        if ($get_promocode->min_order_amount <= $amount) {
                            if ($get_promocode->discount_type == 1) {
                                $new_price = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);
                                $new_discount = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);
                            } elseif ($get_promocode->discount_type == 2) {
                                $new_price = round((($amount) - (($amount) * ($get_promocode->discount_amount)) / (100)), 2);
                                $new_discount = round((($amount) * ($get_promocode->discount_amount)) / (100), 2);
                            } else {
                                return array(0,9,__('user_messages.9'),0,0,"");
                            }
                            if ($get_promocode->max_discount_amount != Null) {
                                if ($get_promocode->max_discount_amount < $new_discount) {
//                                    return number_format($get_promocode->max_discount_amount, 2);
                                    return array(1,1,__('user_messages.1'),0,number_format($get_promocode->max_discount_amount, 2, '.', ''),$get_promocode->promo_code);
                                } else {
//                                    return number_format($new_discount, 2);
                                    return array(1,1,__('user_messages.1'),0,number_format($new_discount, 2, '.', ''),$get_promocode->promo_code);
                                }
                            } else {
//                                return number_format($new_discount, 2);
                                return array(1,1,__('user_messages.1'),0,number_format($new_discount, 2, '.', ''),$get_promocode->promo_code);

                            }
                        } else {
//                            return "one";
                            return array(0,93,__('user_messages.93'),0,0,"");
                        }
                    } else {
                        if ($get_promocode->discount_type == 1) {
                            $new_price = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);
                            $new_discount = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);
                        } elseif ($get_promocode->discount_type == 2) {
                            $new_price = round((($amount) - (($amount) * ($get_promocode->discount_amount)) / (100)), 2);
                            $new_discount = round((($amount) * ($get_promocode->discount_amount)) / (100), 2);
                        } else {
//                          return "zero";
                            return array(0,9,__('user_messages.9'),0,0,"",);
                        }
                        if ($get_promocode->max_discount_amount != Null) {
                            if ($get_promocode->max_discount_amount < $new_discount) {
//                                return number_format($get_promocode->max_discount_amount, 2);
                                return array(1,1,__('user_messages.1'),0,number_format($get_promocode->max_discount_amount, 2, '.', ''),$get_promocode->promo_code);
                            } else {
//                                return number_format($new_discount, 2);
                                return array(1,1,__('user_messages.1'),0,number_format($new_discount, 2, '.', ''),$get_promocode->promo_code);
                            }
                        } else {
//                            return number_format($new_discount, 2);
                            return array(1,1,__('user_messages.1'),0,number_format($new_discount, 2, '.', ''),$get_promocode->promo_code);
                        }
                    }
                } else {
//                    return "two";
//                    return array(0,177, "Promocode usage limit exceeded!",0,0,"");
                    return array(0,177,__('user_messages.177'),0,0,"");
                }
            }
        } else {
//            return "zero";
//            return array(0,9,"something went to wrong!",0,0,"");
//            return array(0,234,"Promocode is expired!",0,0,"");
            return array(0,234,__('user_messages.234'),0,0,"");
        }
    }
    //other service start
    public function findOtherServiceCategory($service_cat_id)
    {
        $service_categories = OtherServiceCategory::query()->select('other_service_sub_category.id', 'other_service_sub_category.name', 'other_service_sub_category.icon_name')
            ->where('other_service_sub_category.service_cat_id', $service_cat_id)
            ->where('other_service_sub_category.status', 1)
            ->get();
        if (!$service_categories->isEmpty()) {
            $other_service_category_banner_url = url('/assets/images/provider-banners/');
            $service_cat_banner = ServiceCategory::query()->select(DB::raw("(CASE WHEN banner_image != '' THEN (concat('$other_service_category_banner_url','/',banner_image,'?v=0.3')) ELSE '' END) as service_category_banner"))
                ->where('id',$service_cat_id)
                ->first();
            $category = [];
            foreach ($service_categories as $service_category) {
                if ($service_category->icon_name != Null) {
                    $icon = url('/assets/images/service-category/other-service-sub-category/' . $service_category->icon_name);
                } else {
                    $icon = Null;
                }
                $category[] = [
                    "category_id" => $service_category->id,
                    "category_name" => $service_category->name,
                    "category_icon" => $icon
                ];
            }
            return response()->json([
                'status' => 1,
//                'message' => 'success!',
                'message' =>__('user_messages.1'),
                "message_code" => 1,
                'category_list' => $category,
                "service_category_banner" => isset($service_cat_banner) && $service_cat_banner != NUll ? $service_cat_banner->service_category_banner : ''
            ]);
        } else {
            return response()->json([
                'status' => 0,
//                'message' => 'Service Category not found!',
                'message' =>__('user_messages.124'),
                "message_code" => 124,
            ]);
        }
    }
    //radius fetch pending from database
    public function findServiceProvider($service_cat_id, $sub_cat_id, $lat_long, $currency)
    {
        if ($lat_long == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "user address not found!",
                "message" => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
        $address_lat_long = array_map('trim', explode(",", $lat_long));
        $address_lat = $address_lat_long[0];
        $address_long = $address_lat_long[1];
        $radius = 25;    //max radius this add in settings
        $angle_radius = $radius / (111 * cos($address_lat)); //-0.23609747634791
        $min_lat = $address_lat + $angle_radius; //22.059707223652
        $max_lat = $address_lat - $angle_radius; //22.531902176348
        $min_lon = $address_long + $angle_radius; //70.570694823652
        $max_lon = $address_long - $angle_radius; //71.042889776348
        //not for demo
        $providers = Provider::query()->select('providers.id',
            DB::raw("CONCAT(COALESCE(providers.first_name,''),'') as name"),
            'providers.gender',
            'providers.avatar',
            'providers.service_radius',
            'provider_services.id as provider_service_id',
            'other_service_provider_details.rating',
            'other_service_provider_details.total_completed_order',
            'other_service_provider_details.time_slot_status',
            DB::raw(('other_service_provider_details.lat,other_service_provider_details.long, ( ROUND( 6367 * acos( cos( radians(' . $address_lat . ') ) * cos( radians( other_service_provider_details.lat ) ) * cos( radians( other_service_provider_details.long ) - radians(' . $address_long . ') ) + sin( radians(' . $address_lat . ') ) * sin( radians( other_service_provider_details.lat ) ) ),2 ) ) AS distance'))
        //, DB::raw('(6367 * 2 * ASIN( SQRT( POWER( SIN(( ' . $address_lat . ' - other_service_provider_details.lat) *  pi()/180 / 2), 2)
        //    +COS( ' . $address_lat . ' * pi()/180)
        //    * COS(other_service_provider_details.lat * pi()/180)
        //    * POWER(SIN(( ' . $address_long . ' - other_service_provider_details.long) * pi()/180 / 2), 2) ))) as distance_to')
        )
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->join('other_service_provider_packages', 'other_service_provider_packages.provider_service_id', '=', 'provider_services.id')
            ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
            ->where('provider_services.service_cat_id', $service_cat_id)
//            ->where('other_service_provider_packages.sub_cat_id', $sub_cat_id)
            ->where('provider_services.current_status', 1)
            ->where('provider_services.status', 1)
//            ->where('other_service_provider_details.time_slot_status', 1)
            ->where('providers.status', 1)
            ->whereNull('providers.deleted_at')
            ->where('other_service_provider_packages.status',1)->havingRaw('providers.service_radius >= distance');
//            ->orderBy('distance');
        $this->checkDayShowRecord($providers,0);

        //->whereBetween('other_service_provider_details.lat', [$min_lat, $max_lat])
        //->whereBetween('other_service_provider_details.long', [$min_lon, $max_lon])
        //
        //->orderByRaw("distance")
        $providers = $providers->distinct()
            ->get();
        if ($providers->isEmpty()) {
            return response()->json([
                "status" => 0,
//                "message" => "providers not found!",
                "message" => __('user_messages.0'),
                "message_code" => 0,
            ]);
        }
        $provider_list = Null;
        foreach ($providers as $provider) {
            //if ($provider->distance <= $provider->service_radius) {
            //user ratings from other service rating
            $total_provider_rating = OtherServiceRatings::query()->where('status', 1)->where("provider_id",$provider->id);
            $average_rating = (clone $total_provider_rating)->groupBy('provider_id')->avg('rating');


            $package_list = OtherServiceProviderPackages::select('other_service_provider_packages.name as package_name',
                //'other_service_provider_packages.price as package_price',
                DB::raw('CAST(other_service_provider_packages.price * ' . $currency . ' AS DECIMAL(18,2)) As package_price')
            )
                ->join('other_service_sub_category', 'other_service_sub_category.id', '=', 'other_service_provider_packages.sub_cat_id')
                ->where('other_service_provider_packages.provider_service_id', $provider->provider_service_id)
                ->where('other_service_sub_category.service_cat_id', $service_cat_id)
                ->where('other_service_sub_category.status', 1)
                ->where('other_service_provider_packages.status', 1)
                ->orderBy('other_service_provider_packages.price', 'asc')->take(4)->get();
            if ($provider->avatar != Null) {
                $avatar = url('/assets/images/profile-images/provider/' . $provider->avatar);
            } else {
                $avatar = "";
            }
            $distance = ($provider->distance != 0) ? number_format($provider->distance, 1, '.', '') : 0;
            /* check time provide has any selected time slot or not */
            $selected_timing = $this->getProviderSelectedTiming($provider->id);
            $provider_list[] = [
                'provider_id' => $provider->id,
                'provider_name' => trim($provider->name),
                'provider_profile_image' => $avatar,
                'provider_gender' => $provider->gender,
                'average_rating' => round($average_rating,2),
                'num_of_rating' => $total_provider_rating->count(),
                'distance' => $distance,
                'total_completed_order' => $provider->total_completed_order,
                'package_list' => $package_list,
                "time_slot_status" => (isset($selected_timing) && $selected_timing == true) ? $provider->time_slot_status : 0,
            ];
            //}
        }
        if ($provider_list == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "providers not found!",
                "message" => __('user_messages.0'),
                "message_code" => 0,
            ]);
        }
        return response()->json([
            'status' => 1,
//            'message' => 'success!',
            'message' => __('user_messages.1'),
            "message_code" => 1,
            'provider_list' => $provider_list
        ]);
    }

    public function findServiceProviderPackageList($provider_id, $service_cat_id, $currency, $language = 'en')
    {
        if ($language != "en") {
            $lang_prefix = $language."_";
        } else {
            $lang_prefix = "";
        }

        $packages = OtherServiceProviderPackages::query()->select('other_service_provider_packages.id',
            'other_service_provider_packages.name',
            'other_service_provider_packages.description',
            DB::raw('CAST(other_service_provider_packages.price * ' . $currency . ' AS DECIMAL(18,2)) As price'),
            'other_service_provider_packages.max_book_quantity',
            'other_service_sub_category.id as category_id',
            'other_service_sub_category.' . $lang_prefix . 'name as category_name')
            ->join('provider_services', 'provider_services.id', '=', 'other_service_provider_packages.provider_service_id')
            ->join('providers', 'providers.id', '=', 'provider_services.provider_id')
            ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
            ->join('other_service_sub_category', 'other_service_sub_category.id', '=', 'other_service_provider_packages.sub_cat_id')
            ->where('provider_services.provider_id', $provider_id)
            ->where('provider_services.service_cat_id', $service_cat_id)
            ->where('other_service_provider_packages.status', 1)
            ->where('provider_services.current_status', 1)
            ->where('provider_services.status', 1)
            ->where('providers.status', 1)
            ->where('other_service_provider_details.time_slot_status', 1)
            ->where('other_service_sub_category.status', 1)
            ->get();

        if (!$packages->isEmpty()) {
            $categories = array_unique(Arr::pluck($packages, 'category_name', 'category_id'));
            $packages = $packages->groupBy('category_id');
            foreach ($packages as $key => $list) {
                $package[] = [
                    'category_id' => $key,
                    'category_name' => $categories[$key],
                    'category_package_list' => $list
                ];
            }
            return response()->json([
                'status' => 1,
//                'message' => 'success!',
                'message' => __('user_messages.1'),
                "message_code" => 1,
                'package_list' => $package
            ]);
        } else {
            return response()->json([
                'status' => 0,
//                'message' => 'something went to wrong!',
                'message' => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function OtherServiceOrderDetails($order_id, $new_order, $unavailable_packages = Null,$user_lang= "")
    {
        //$check_SODS = Other Service Order Details Status true or false
        if($user_lang !="en"){
            $user_lang = $user_lang."_";
        }else{
            $user_lang = "";
        }
        //add order_status parameter in response
        $order = UserPackageBooking::query()->select('service_category.id as service_category_id',
            'service_category.'.$user_lang.'name as category_name',
            'service_category.icon_name as category_icon',
            'user_service_package_booking.id',
            'user_service_package_booking.user_id',
            'user_service_package_booking.order_no',
            'user_service_package_booking.service_date_time',
            'user_service_package_booking.lat_long',
            'user_service_package_booking.tax',
            'user_service_package_booking.tip',
            'user_service_package_booking.remark',
            'user_service_package_booking.refer_discount',
            'user_service_package_booking.extra_amount',
            'user_service_package_booking.total_pay',
            'user_service_package_booking.delivery_address',
            'user_service_package_booking.provider_name',
            'user_service_package_booking.cancel_by',
            'user_service_package_booking.cancel_reason',
            'user_service_package_booking.cancel_charge',
            'user_service_package_booking.status as order_status',
            'user_service_package_booking.total_item_cost',
            'user_service_package_booking.flat_no',
            'user_service_package_booking.landmark',
            'user_service_package_booking.provider_id',
            'user_service_package_booking.user_rating_status',
            'user_service_package_booking.payment_type',
            'user_service_package_booking.promo_code',
            'user_service_package_booking.service_date',
            'user_service_package_booking.service_time',
            'user_service_package_booking.book_start_time',
            'user_service_package_booking.book_end_time',
            'user_service_package_booking.select_provider_location',
            'user_service_package_booking.payment_status')
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->where('user_service_package_booking.id', $order_id)
            ->first();

        if ($order != Null) {
            $service_provider = Provider::query()->select('providers.avatar','providers.contact_number','providers.country_code', 'other_service_provider_details.rating', 'other_service_provider_details.lat', 'other_service_provider_details.long')
                ->join('other_service_provider_details', 'other_service_provider_details.provider_id', '=', 'providers.id')
                ->where('providers.id', $order->provider_id)->whereNull('providers.deleted_at')->first();
            //average of total ratings
            $ratings = OtherServiceRatings::query()
                ->groupBy('provider_id')
                ->where('provider_id', $order->provider_id)
                ->where('status', 1)
                ->avg('rating');

            if($ratings != Null){
                $provider_rating = round($ratings,2);
            } else {
                $provider_rating = 0.00;
            }

            if ($service_provider != Null) {
                $provider_profile_image = $service_provider->avatar != Null ? url('/assets/images/profile-images/provider/' . $service_provider->avatar) : '';
//                $provider_rating = $service_provider->rating;
                $provider_contact = ($service_provider->contact_number != Null ) ? $service_provider->country_code.$service_provider->contact_number : "";
            } else {
                $provider_profile_image = '';
//                $provider_rating = 0.00;
                $provider_contact = "";
            }

            $user_details = User::query()->where('id', $order->user_id)->whereNull('users.deleted_at')->first();
            if ($user_details != Null) {
                $user_currency = WorldCurrency::query()->where('symbol', $user_details->currency)->first();
                if ($user_currency == Null) {
                    $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
                }
            } else {
                $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
            }
            $currency = $user_currency->ratio;
            $lang_prefix = $user_lang;
            $lang_service_category_name  = $order->category_name;


            $package_items = UserPackageBookingQuantity::query()->select('num_of_items',
                'package_name', $lang_prefix . 'sub_category_name as sub_category_name', 'package_id',
                DB::raw('CAST(price_for_one * ' . $currency . ' AS DECIMAL(18,2)) As price_for_one'),
                DB::raw('num_of_items * (CAST(price_for_one * ' . $currency . ' AS DECIMAL(18,2))) As total_single_package_price')
            )->where('order_id', $order->id)->get();
            $package_items = $package_items->groupBy('sub_category_name');
            $package_list = [];
            foreach ($package_items as $key => $list) {
                $package_list[] = [
                    'sub_category_name' => $key,
                    'item_list' => $list
                ];
            }

            /*$user_lang = ($user_details->language !="")?$user_details->language."_":"";
            $all_service_list  = ProviderServices::query()
                                ->select($user_lang . 'name as service_category_name')
                                ->join('service_category','service_category.id','provider_services.service_cat_id')
                                ->where('provider_services.provider_id',$order->provider_id)
                                ->where('provider_services.status',1)
                                ->orderBy('provider_services.id','desc')
                                ->get()
                                ->toArray();
            $all_service_list  = array_column($all_service_list,'service_category_name');
            $all_service_category_list = implode(",",$all_service_list);*/

            //find distance
            $delivery_lat_long = array_map('trim', explode(",", $order->lat_long));
            $delivery_lat = $delivery_lat_long[0];
            $delivery_long = $delivery_lat_long[1];
            $provider_lat_long = OtherServiceProviderDetails::query()->select('lat', 'long')->where('provider_id', $order->provider_id)->first();
            if ($provider_lat_long != Null) {
                $pickup_lat = $provider_lat_long->lat;
                $pickup_long = $provider_lat_long->long;
                $theta = $pickup_lat - $delivery_lat;
                $distance = (sin(deg2rad($pickup_lat)) * sin(deg2rad($delivery_lat))) + (cos(deg2rad($pickup_lat)) * cos(deg2rad($pickup_lat)) * cos(deg2rad($theta)));
                $distance = acos($distance);
                $distance = rad2deg($distance);
                $distance = $distance * 60 * 1.1515 * 1.609344;
                $distance = number_format($distance) . ' km away';
            } else {
                $distance = '';
            }
            if ($new_order == true) {
                $provider = Provider::query()->select('device_token', 'login_device','language')->where('id', $order->provider_id)->first();
                if ($provider != Null) {
                    $this->notificationClass->providerOrderRequestNotification($order->id, $order->order_status, $provider->device_token, $provider->language);
                }
                $general_settings = GeneralSettings::query()->first();
                if ($general_settings !=  Null) {
                    if ($general_settings->send_mail == 1) {
                        try{
                            $category_name = $order->category_name;

                            if ($general_settings->send_receive_email != Null) {
                                $service_name = ucwords(strtolower($category_name));
//                                $provider_name = ucwords($order->provider_name);
                                $user_name = $user_details->first_name."".$user_details->last_name;
                                $mail_type = "admin_user_new_service_request_-_handyman";
                                $to_mail = $general_settings->send_receive_email;

                                $subject = "You get a new " . $service_name . " service request ";
                                $disp_data = array("##service_name##" => $service_name, "##user_name##" => ucwords($user_name));
                                $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                            }
                        }
                        catch (\Exception $e){}
                    }
                }
            }
            if ($order->promo_code != 0) {
                try {
                    $get_discount = UsedPromocodeDetails::query()->where('id', $order->promo_code)->first();
                    $promo_code_discount = $get_discount->discount_amount;
                    $promocode_name = $get_discount->promocode_name;
                } catch (\Exception $e) {
                    $promo_code_discount = round($order->total_item_cost - $order->discount_cost + $order->tax_cost + $order->delivery_cost + $order->packaging_cost - $order->total_pay, 2);
                    $promocode_name = '';
                }
            } else {
                $promo_code_discount = 0;
                $promocode_name = '';
            }
//            $schedule_order_time= date("h:i A",strtotime($order->book_start_time))." - ".date("h:i A",strtotime($order->book_end_time));
            $schedule_order_time= date("H:i:s",strtotime($order->book_start_time))." - ".date("H:i:s",strtotime($order->book_end_time));
            $subtotal=number_format((($order->total_pay + $order->tip) - $order->tax) * $currency, 2, '.', '');

            return response()->json([
                "status" => 1,
//                "message" => "success!",
                "message" => __('user_messages.1'),
                "message_code" => 1,
                "order_id" => $order->id,
                "order_no" => $order->order_no."",
                "service_category_id" => $order->service_category_id - 0,
                'additional_remark' => "" . $order->remark,
                "order_payment_type" => $order->payment_type - 0,
                "order_payment_status" => $order->payment_status - 0,
                "category_name" => $lang_service_category_name,
                "category_icon" => url('/assets/images/service-category/' . $order->category_icon),
                //"service_date_time" => date('Y-m-d H:i:s',strtotime($order->created_at)),
                //"schedule_order_date_time" => $order->service_date_time,
                "schedule_order_date" => $order->service_date,
                "schedule_order_time" => $schedule_order_time,
                "provider_id" => $order->provider_id,
                "provider_name" => $order->provider_name,
                'provider_contact' =>$provider_contact,
                "distance" => $distance,
                "flat_no" => $order->flat_no != Null ? $order->flat_no : '',
                "landmark" => $order->landmark != Null ? $order->landmark : '',
                "provider_profile_image" => $provider_profile_image,
                "provider_rating" => $provider_rating,
                "user_rating_status" => $order->user_rating_status - 0,
                "item_total" => number_format($order->total_item_cost * $currency, 2, '.', ''),
                "tax" => number_format($order->tax * $currency, 2, '.', ''),
                "tip" => number_format($order->tip * $currency, 2, '.', ''),
                "sub_total" => number_format((($order->total_pay - $order->tip) - $order->tax ) * $currency, 2, '.', ''),
                "discount" => 0.00,
                "refer_discount" => number_format($order->refer_discount * $currency, 2, '.', ''),
                "extra_amount" => number_format($order->extra_amount * $currency, 2, '.', ''),
                "total_pay" => number_format($order->total_pay * $currency, 2, '.', ''),
                "delivery_address" => $order->delivery_address,
                "cancel_by" => $order->cancel_by != Null ? $order->cancel_by : "",
                "cancel_reason" => $order->cancel_reason != Null ? $order->cancel_reason : "",
                'cancel_charge' => ($order->status == 4) ? ($order->payment_type == 2 || $order->payment_type == 3) ? ($order->cancel_charge != Null) ? $order->cancel_charge : 0 : 0 : 0,
                "order_status" => $order->order_status,
                'promocode_name' => $promocode_name,
                'promo_code_discount' => number_format($promo_code_discount * $currency,2, '.', ''),
                'promocode_discount' =>  number_format($promo_code_discount * $currency,2, '.', ''),
//              'all_service_category_list' => $all_service_category_list,
                "package_list" => $package_list,
                "unavailable_packages" => isset($unavailable_packages) && $unavailable_packages != Null ? $unavailable_packages : '',
                'select_provider_location' => $order->select_provider_location,
                "order_chat_number" => (new FirebaseService())->CreateOrderNumberForChat($order->order_no,$order->id) ,//for fire base chat
            ]);
        } else {
            return response()->json([
                "status" => 0,
//                "message" => "order not found!",
                "message" => __('user_messages.59'),
                "message_code" => 59,
            ]);
        }
    }
    //other service end

    //user address
    public function userAddressManage($request, $add, $update, $delete)
    {
        if ($add == true || $update == true) {
            $validator = Validator::make($request->all(), [
                "address" => "required",
                "type" => "nullable|in:home,work,other",
                "lat" => "required",
                "long" => "required",
                "flat_no" => "required",
                "landmark" => "required"
            ]);
            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }
        }
        if ($update == true || $delete == true) {
            $validator = Validator::make($request->all(), [
                "address_id" => "required",
            ]);
            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }
        }
        $count_active_address = UserAddress::where('user_id', $request['user_id'])->where('status', 1)->count();
        $count_inactive_address = UserAddress::where('user_id', $request['user_id'])->where('status', 0)->count();
        if ($add == true) {
            if (($count_active_address + $count_inactive_address) < 5) {
                $address = new UserAddress();
                $address->user_id = $request['user_id'];
                if ($request['type'] != Null) {
                    $address->address_type = $request['type'];
                }
                $address->address = $request['address'];
                $address->lat_long = $request['lat'] . ',' . $request['long'];
                $address->flat_no = $request['flat_no'];
                $address->landmark = $request['landmark'];
                $address->save();
                return response()->json([
                    "status" => 1,
//                    "message" => "success!",
                    'message' => __('user_messages.1'),
                    "message_code" => 1,
                    "address_id" => $address->id
                ]);
            } else {
                if ($count_active_address == 5) {
                    return response()->json([
                        "status" => 0,
//                        "message" => "user only add five address,if you add new address then delete old address!",
                        'message' => __('user_messages.126'),
                        "message_code" => 9,
                    ]);
                } elseif ($count_inactive_address > 0) {
                    $address = UserAddress::where('user_id', $request['user_id'])->where('status', 0)->first();
                    $address->user_id = $request['user_id'];
                    $address->address_type = $request['type'];
                    $address->address = $request['address'];
                    $address->lat_long = $request['lat'] . ',' . $request['long'];
                    $address->flat_no = $request['flat_no'];
                    $address->landmark = $request['landmark'];
                    $address->status = 1;
                    $address->save();
                    return response()->json([
                        "status" => 1,
//                        "message" => "success!",
                        'message' => __('user_messages.1'),
                        "message_code" => 1,
                        "address_id" => $address->id
                    ]);
                } else {
                    return response()->json([
                        "status" => 0,
//                        "message" => "something went to wrong!",
                        'message' => __('user_messages.9'),
                        "message_code" => 9,
                    ]);
                }
            }
        } elseif ($update == true) {
            $address = UserAddress::where('user_id', $request['user_id'])->where('id', $request['address_id'])->first();
            $address->user_id = $request['user_id'];
            $address->address_type = $request['type'];
            $address->address = $request['address'];
            $address->lat_long = $request['lat'] . ',' . $request['long'];
            $address->flat_no = $request['flat_no'];
            $address->landmark = $request['landmark'];
            $address->save();
            return response()->json([
                "status" => 1,
//                "message" => "success!",
                'message' => __('user_messages.1'),
                "message_code" => 1,
                "address_id" => $address->id
            ]);
        } elseif ($delete == true) {
            $address = UserAddress::where('user_id', $request['user_id'])->where('id', $request['address_id'])->first();
            $address->status = 0;
            $address->save();
            return response()->json([
                "status" => 1,
//                "message" => "success!",
                'message' => __('user_messages.1'),
                "message_code" => 1,
            ]);
        } else {
            return response()->json([
                "status" => 0,
//                "message" => "something went to wrong!",
                'message' => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function get_coordinates($origin, $destination)
    {
//        $general_settings = GeneralSettings::query()->select('map_key')->first();
//        $map_key = trim($general_settings->map_key);
        $map_key = "AIzaSyBMGPhIDJy8aOePdHmxsWztcZCorr9BhG4";
        $details = "https://maps.googleapis.com/maps/api/directions/json?origin=" . $origin . "&destination=" . $destination . "&mode=DRIVING&key=" . $map_key;
        //$details = "https://maps.googleapis.com/maps/api/directions/json?origin=Rajkot,India,360001&waypoints=optimize:true|via:22.222,70.222|via:22.222,70.222via:22.222,70.222&destination=22.222,70.222&mode=DRIVING&key=".$map_key;
        $json = file_get_contents($details);
        return $json;
    }

    function parse_signed_request($signed_request,$user_type) {
        list($encoded_sig, $payload) = explode('.', $signed_request, 2);

        if($user_type == 'user'){
            $secret = "9045fc6e079eb0e440d6ff780b9d12c3";
        } elseif($user_type == 'driver'){
            $secret = "da23140a8d1e1105419c26c43c44f08e";
        } else{
            $secret = "da23140a8d1e1105419c26c43c44f08e";
        }

        $sig = $this->base64_url_decode($encoded_sig);
        $data = json_decode($this->base64_url_decode($payload), true);

        $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
        if ($sig !== $expected_sig) {
            error_log('Bad Signed JSON signature!');
            return null;
        }

        return $data;
    }

    function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function checkDayShowRecord($query, $type = 0)
    {
        $general_settings = request()->get("general_settings");
        $is_day_allow = $general_settings->day_allow ?? 0;

        // If day_allow is active
        if ($is_day_allow > 0) {

            $limit_date_time = date('Y-m-d', strtotime("-{$is_day_allow} days"));

            $query->where(function ($q) use ($limit_date_time, $type) {
                $q->where('providers.fix_user_show', 1)
                    ->groupBy("providers.id");

                if ($type == 0) {
                    $q->orWhere('providers.created_at', '>=', $limit_date_time);
                }
            });
        }

        // IMPORTANT: ALWAYS RETURN QUERY BUILDER
        return $query;
    }


    public function checkServiceAvailableLocationNew($current_lat, $current_long)
    {
        $address_lat = trim($current_lat);
        $address_long = trim($current_long);
        $area_details = RestrictedArea::query()->where('status',1)->get();
        $latitudes = $longitudes = '';
        foreach ($area_details as $area){
            $latitudes = $latitudes . $area->latitude . ',';
            $longitudes = $longitudes . $area->longitude . ',';
        }
        $restricted_lat = explode(',',substr($latitudes, 0, -1));
        $restricted_long = explode(',',substr($longitudes, 0, -1));
        $points_polygon = count($restricted_lat);
        $in_Rest = 0;
        if($this->adminClassApi->is_in_restricted_area($points_polygon,$restricted_lat,$restricted_long,$address_lat,$address_long)){
            $in_Rest = 1;
        }
        return $in_Rest;
    }

    public static function dateConvertTimezonewise($datetime="")
    {

        if ($datetime != "") {
            $timezone = (Session::get('timezone') != "") ? Session::get('timezone') : "";
            if ($timezone != "") {
                $date = new DateTime();
                $date = new DateTime($datetime, new DateTimeZone("UTC"));
                $date->setTimezone(new DateTimeZone($timezone));
                $time = $date->format('Y-m-d H:i:s');
                $date->setTimezone(new DateTimeZone("UTC"));
                return $time;
            } else {
                return $datetime;
            }
        } else {
            return now();
        }
    }
    //0:user,1:store,2:driver,3:provider
    public function manageCardList($card_provider_type, $card_user_id){
        $card_details = UserCardDetails::query()->select('card_details.id as card_id', 'card_details.holder_name as card_holder_name', 'card_details.card_number as card_number')
            ->where('user_id', "=", $card_user_id)
            ->where('card_provider_type', "=", $card_provider_type)
            ->get();
        return response()->json([
            "status" => 1,
//            "message" => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
            "card_list" => $card_details,
        ]);
    }

    public function addCardManage($card_provider_type, $card_user_id, $request_details){
        $card_details = new UserCardDetails();
        $card_details->user_id = $card_user_id;
        $card_details->card_provider_type = $card_provider_type;
        $card_details->holder_name = $request_details['holder_name'];
        $card_details->card_number = $request_details['card_number'];
        $card_details->month = $request_details['month'];
        $card_details->year = $request_details['year'];
        $card_details->cvv = $request_details['cvv'];
        $card_details->save();

        $month = (($card_details->month == Null) ? Null: round($card_details->month));
        $year = (($card_details->year == Null) ? Null : round($card_details->year));
        $cvv = (($card_details->cvv == Null) ? Null : round($card_details->cvv));
        return response()->json([
            "status" => 1,
//            "message" => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
            "holder_name" => $card_details->holder_name,
            "card_number" => $card_details->card_number,
            "month" => $month,
            "year" => $year,
            "cvv" => $cvv,
        ]);
    }

    public function deleteCardManage($card_provider_type, $card_user_id, $card_id){
        UserCardDetails::query()
            ->where('card_provider_type', "=", $card_provider_type)
            ->where('user_id', "=", $card_user_id)
            ->where('id', "=" , $card_id)
            ->delete();
        return response()->json([
            "status" => 1,
//            "message" => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
        ]);
    }

    public function addWalletBalance($provider_type, $provider_id, $wallet_holder_name, $currency, $request_details){
        $provider_currency = WorldCurrency::query()->where('symbol', $currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency != Null ? $provider_currency->ratio : 1;
        $currency_code = $provider_currency != Null ? $provider_currency->currency_code : '';

        $amount_to_default = round($request_details['amount'] / $currency, 2);

        try {
            $get_last_transaction = UserWalletTransaction::query()
                ->where('wallet_provider_type', "=", $provider_type)
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

        if ($request_details['amount'] > 0){
            $validator = Validator::make($request_details, [
                "card_id" => "required",
            ]);
            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }
            $card_details = UserCardDetails::query()->where('id', $request_details['card_id'])->first();
            if ($card_details == Null) {
                return response()->json([
                    "status" => 0,
//                    "message" => "card details not found",
                    'message' => __('user_messages.120'),
                    "message_code" => 120,
                ]);
            }

            $add_balance = new UserWalletTransaction();
            $add_balance->user_id = $provider_id;
            $add_balance->wallet_provider_type = $provider_type;
            $add_balance->transaction_type = 1;
            $add_balance->amount = $amount_to_default;
            $add_balance->order_no = $wallet_holder_name;
            //$add_balance->request_amount = number_format($request->get('amount'), 2);
            $add_balance->subject = "credit by " . $wallet_holder_name;
            $add_balance->subject_code = 1;
            $add_balance->remaining_balance = floatval($last_amount + $amount_to_default);
            $add_balance->save();
            $last_amount = $add_balance->remaining_balance;
        }

        return response()->json([
            "status" => 1,
//            "message" => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
            "wallet_balance" => number_format($last_amount * $currency, 2, '.', ''),
            "redirect_url" => "",
            "success_url" => "",
            "failed_url" => "",
            "error_url" => ""
        ]);
    }

    public function getWalletTransactionList($provider_type, $provider_id, $currency, $user_language = 'en'){
        $user_currency = WorldCurrency::query()->where('symbol', $currency)->first();
        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $user_currency != Null ? $user_currency->ratio : 1;

        $walletTransaction = config('wallettransaction.'.$user_language);

        $transactions_list = UserWalletTransaction::query()->select(
            'user_wallet_transaction.id',
            DB::raw('CAST(user_wallet_transaction.amount * ' . $currency . ' AS DECIMAL(18,2)) As amount'),
            'user_wallet_transaction.transaction_type',
            'user_wallet_transaction.subject',
            'user_wallet_transaction.subject_code',
            'user_wallet_transaction.order_no',
            'user_wallet_transaction.remaining_balance' ,
            'user_wallet_transaction.created_at as date_time'
        )
            ->where('wallet_provider_type', "=", $provider_type)
            ->where('user_id', "=", $provider_id)
            ->orderBy('id', 'desc')
            ->get();

        $transactions = [];
        foreach ($transactions_list as $key => $transactions_details){
            $transactions[] = [
                "id" => $transactions_details->id,
                "amount" => $transactions_details->amount,
                "transaction_type" => $transactions_details->transaction_type,
                "subject" => $transactions_details->subject_code == null ? $transactions_details->subject : $walletTransaction[$transactions_details->subject_code]." ".$transactions_details->order_no,
                "remaining_balance" => $transactions_details->remaining_balance,
                "date_time" => $transactions_details->date_time,
            ];
        }

        return response()->json([
            "status" => 1,
//            "message" => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
            "transactions" => $transactions
        ]);
    }

    public function getWalletBalance($provider_type, $provider_id, $currency){
        $user_currency = WorldCurrency::query()->where('symbol', $currency)->first();
        if ($user_currency == Null) {
            $user_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $user_currency != Null ? $user_currency->ratio : 1;
        $wallet_balance = UserWalletTransaction::query()->where('wallet_provider_type', '=', $provider_type)->where('user_id', '=', $provider_id)->orderBy('id', 'desc')->first();
        if ($wallet_balance != Null) {
            $balance = number_format($wallet_balance->remaining_balance * $currency, 2, '.', '');
        } else {
            $balance = "0";
        }
        $general_setting = GeneralSettings::query()->select('auto_settle_wallet')->first();
        return response()->json([
            "status" => 1,
//            "message" => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
            "wallet_balance" => $balance,
            "is_cash_out" => $general_setting->auto_settle_wallet,
        ]);
    }

    public function searchWalletTransferUserList($provider_type, $provider_id, $search){

        $transfer_user_list = [];
        $user_profile_url = url('/assets/images/profile-images/customer');
        $user_list = User::query()
            ->select("users.id as transfer_id", "users.first_name as name", "users.email", DB::raw("(CASE WHEN users.avatar != '' THEN (CASE WHEN CHAR_LENGTH(users.avatar) >= 25 THEN users.avatar ELSE concat('$user_profile_url','/',users.avatar) END) ELSE '' END) as profile_image"), "users.country_code as country_code", "users.contact_number as contact_number", DB::raw($this->user_type.' AS wallet_provider_type'))
            ->where('users.status', "=", 1)
            ->whereNotNull('users.verified_at')
            ->whereNull('users.deleted_at');
        if ($provider_type == $this->user_type) {
            $user_list->where('users.id', "!=", $provider_id);
        }
        $user_list = $user_list->where(function ($query) use ($search) {
            $query->orWhere('users.email', 'LIKE', "%{$search}%");
            $query->orWhere(DB::raw('CONCAT_WS("", users.country_code, users.contact_number)'), 'LIKE', '%' . $search . '%');
        })
            ->groupBy('users.id')
            ->get()->toArray();

        $provider_profile_url = url('/assets/images/profile-images/provider');
        $provider_list = Provider::query()
            ->select("providers.id as transfer_id", "providers.first_name as name", "providers.email", DB::raw("(CASE WHEN providers.avatar != '' THEN (CASE WHEN CHAR_LENGTH(providers.avatar) >= 25 THEN providers.avatar ELSE concat('$provider_profile_url','/',providers.avatar) END) ELSE '' END) as profile_image"), "providers.country_code as country_code", "providers.contact_number as contact_number",
                DB::raw('(CASE WHEN service_category.category_type IN (3,4) THEN '.$this->provider_type.' ELSE '.$this->user_type.' END) AS wallet_provider_type')
            )
            ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            ->where('providers.status', '=', 1)
            ->where('provider_services.status', '=', 1);
        if ($provider_type == $this->provider_type) {
            $provider_list->where('providers.id', "!=", $provider_id);
        }
        $provider_list = $provider_list->where(function ($query) use ($search) {
            $query->orWhere('providers.email', 'LIKE', "%{$search}%");
            $query->orWhere(DB::raw('CONCAT_WS("", providers.country_code, providers.contact_number)'), 'LIKE', '%' . $search . '%');
        })
            ->groupBy('providers.id')
            ->get()->toArray();

        $transfer_user_list = array_merge($transfer_user_list,$user_list);
        $transfer_user_list = array_merge($transfer_user_list,$provider_list);
        return response()->json([
            "status" => 1,
//            "message" => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
            "transfer_user_list" => $transfer_user_list
        ]);
    }

    public function walletToWalletTransfer($provider_type, $provider_id, $wallet_holder_name, $currency, $request_details){

        $provider_currency = WorldCurrency::query()->where('symbol', $currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency != Null ? $provider_currency->ratio : 1;
        $currency_code = $provider_currency != Null ? $provider_currency->currency_code : '';

        $amount_to_default = number_format($request_details['amount'] / $currency, 2, '.', '');
        try {
            $get_last_transaction = UserWalletTransaction::query()
                ->where('wallet_provider_type', "=", $provider_type)
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
        if ($request_details['amount'] > 0) {
            if ($amount_to_default > $last_amount) {
                return response()->json([
                    "status" => 0,
//                    "message" => "You can't transfer amount through Wallet because your wallet balance is insufficient.",
                    'message' => __('user_messages.110'),
                    "message_code" => 110
                ]);
            }
            $transfer_id = $request_details['transfer_id'];
            $wallet_provider_type = $request_details['wallet_provider_type'];


            if ($wallet_provider_type == $this->provider_type) {
                $transfer_provider_user_details = Provider::query()
                    ->select("providers.id as id", "providers.first_name as name", "providers.device_token as device_token", "providers.login_device as login_device","providers.language")
                    ->join('provider_services', 'provider_services.provider_id', '=', 'providers.id')
                    ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
                    ->where('providers.status', '=', 1)
                    ->where('provider_services.status', '=', 1)
                    ->where('providers.id', "=", $transfer_id)
                    ->whereNull('providers.deleted_at')
                    ->first();
                $title = __('provider_messages.262',[],$transfer_provider_user_details->language);
                $message = __('provider_messages.263',[],$transfer_provider_user_details->language);
            } elseif ($wallet_provider_type == $this->user_type) {
                $transfer_provider_user_details = User::query()
                    ->select("users.id as id", "users.first_name as name", "users.device_token as device_token", "users.login_device as login_device","users.language")
                    ->where('users.status', "=", 1)
                    ->where('users.id', "=", $transfer_id)
                    ->whereNull('users.deleted_at')
                    ->first();
                $title = __('user_messages.262',[],$transfer_provider_user_details->language);
                $message = __('user_messages.263',[],$transfer_provider_user_details->language);
            } else {
                return response()->json([
                    "status" => 0,
//                    "message" => "Invalid wallet transfer type",
                    'message' => __('user_messages.9'),
                    "message_code" => 9
                ]);
            }
            if ($transfer_provider_user_details == Null) {
                return response()->json([
                    "status" => 0,
//                    "message" => "App Transfer User Not Found",
                    'message' => __('user_messages.5'),
                    "message_code" => 5
                ]);
            }

            $transfer_id = $transfer_provider_user_details->id;
            $transfer_wallet_holder_name = $transfer_provider_user_details->name . "";
            $transfer_wallet_holder_device_token = $transfer_provider_user_details->device_token;
            $transfer_wallet_holder_login_device = $transfer_provider_user_details->login_device;


            try {
                $get_last_transaction = UserWalletTransaction::query()
                    ->where('wallet_provider_type', "=", $wallet_provider_type)
                    ->where('user_id', "=", $transfer_id)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($get_last_transaction != Null) {
                    $transfer_last_amount = $get_last_transaction->remaining_balance;
                } else {
                    $transfer_last_amount = 0;
                }
            } catch (\Exception $e) {
                $transfer_last_amount = 0;
            }

            $debited_balance = new UserWalletTransaction();
            $debited_balance->user_id = $provider_id;
            $debited_balance->wallet_provider_type = $provider_type;
            $debited_balance->transaction_type = 2;
            $debited_balance->amount = $amount_to_default;
            $debited_balance->subject = "Wallet Amount Transfer to " . $transfer_wallet_holder_name;
            $debited_balance->remaining_balance = floatval($last_amount - $amount_to_default);
            $debited_balance->subject_code = 8;
            $debited_balance->order_no = $transfer_wallet_holder_name;
            $debited_balance->save();

            $add_balance = new UserWalletTransaction();
            $add_balance->user_id = $transfer_id;
            $add_balance->wallet_provider_type = $wallet_provider_type;
            $add_balance->transaction_type = 1;
            $add_balance->amount = $amount_to_default;
            $add_balance->subject = "credit by " . $wallet_holder_name;
            $add_balance->remaining_balance = floatval($transfer_last_amount + $amount_to_default);
            $add_balance->subject_code = 1;
            $add_balance->order_no = $wallet_holder_name;
            $add_balance->save();

            if ($transfer_wallet_holder_device_token != Null && $transfer_wallet_holder_login_device != Null){
                $user_wallet_notication = $this->notificationClass->userWalletTransferNotification($title,$message,$transfer_wallet_holder_device_token);
            }
        }
        else {
            return response()->json([
                "status" => 0,
//                "message" => "amount can`t be null.",
                'message' => __('user_messages.9'),
                "message_code" => 9
            ]);
        }
        return response()->json([
            "status" => 1,
//            "message" => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
        ]);

    }

    /* Get Provider's Selected Timing */
    public function getProviderSelectedTiming($provider_id){
        $selected_timing = OtherServiceProviderTimings::where('provider_id', $provider_id)->get()->toArray();
        if(isset($selected_timing) && $selected_timing != Null){
            return true;
        } else {
            return false;
        }
    }

}
