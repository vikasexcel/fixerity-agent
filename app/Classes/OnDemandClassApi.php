<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 26-02-2019
 * Time: 12:27 PM
 */

namespace App\Classes;


use App\Models\AppVersionSetting;
use App\Models\GeneralSettings;
use App\Models\OtherServiceCategory;
use App\Models\OtherServiceProviderDetails;
use App\Models\OtherServiceRatings;
use App\Models\OtherServiceProviderPackages;
use App\Models\Provider;
use App\Models\ProviderDocuments;
use App\Models\ProviderPortfolioImage;
use App\Models\ProviderServices;
use App\Models\RequiredDocuments;
use App\Models\ServiceCategory;
use App\Models\UserPackageBooking;
use App\Models\UserWalletTransaction;
use App\Models\WorldCurrency;
use Illuminate\Support\Facades\DB;

class OnDemandClassApi
{

    //        json response status [
    //            0 => false,
    //            1 => true,
    //            2 => registration pending,
    //            3 => app user blocked,
    //            4 => app user access token not match,
    //            5 => app user not found
    //          ]
    private $on_demand_service_id_array;
    private $dog_walking;
    private $baby_care;
    private $pet_care;
    private $workout_trainer;
    private $security_service;
    private $tutors;
    private $beauty_service;
    private $massage_service;
    private $home_cleaning;
    private $gardening;
    private $snow_blowers;
    private $laundry_service;
    private $maid_service;
    private $pest_control;
    private $ac_repair;
    private $electricians;
    private $car_wash;
    private $car_repair;
    private $tow_truck;
    private $plumbers;
    private $adminClass;

    private $transport_delivery_service_id_array;

    public function __construct(NotificationClass $notificationClass, AdminClass $adminClass)
    {
        $this->adminClass = $adminClass;
        //        $this->on_demand_service_id_array = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30];
        $service_cat = ServiceCategory::query()->whereIN('category_type', [3, 4])->where('status', 1)->get()->pluck('id')->toArray();
        $this->on_demand_service_id_array = $service_cat;
        $this->dog_walking = 11;
        $this->baby_care = 12;
        $this->pet_care = 13;
        $this->workout_trainer = 14;
        $this->security_service = 15;
        $this->tutors = 16;
        $this->beauty_service = 17;
        $this->massage_service = 18;
        $this->home_cleaning = 19;
        $this->gardening = 20;
        $this->snow_blowers = 21;
        $this->laundry_service = 22;
        $this->maid_service = 23;
        $this->pest_control = 24;
        $this->ac_repair = 25;
        $this->electricians = 26;
        $this->car_wash = 27;
        $this->car_repair = 28;
        $this->tow_truck = 29;
        $this->plumbers = 30;


        $this->transport_delivery_service_id_array = [1, 2, 4, 5, 6, 7, 8, 9, 10];
    }



    //register login response
    public function onDemandLoginRegisterResponse($provider_details, $request_type = 0, $request = "")
    {
        $provider_details = Provider::query()->where('id', $provider_details['id'])->whereNull('providers.deleted_at')->first();
        if ($provider_details == Null) {
            return response()->json([
                'status' => 5,
                //                'message' => 'Provider Not Found!',
                'message' => __('provider_messages.5'),
                "message_code" => 5,
            ]);
        }
        $extra_provider_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_details['id'])->first();
        if ($provider_details['status'] == 0 || $provider_details['status'] == 1) {
            $get_provider_service = ProviderServices::query()->where('provider_id', $provider_details['id'])
                ->whereIn('service_cat_id', $this->on_demand_service_id_array)
                ->where('status', 1)
                ->first();
            if ($get_provider_service != Null) {
                $provider_current_status = $get_provider_service->current_status;
            } else {
                $check_provider_service_for_transport = ProviderServices::query()->where('provider_id', $provider_details['id'])
                    ->whereIn('service_cat_id', $this->transport_delivery_service_id_array)
                    ->where('status', 1)
                    ->first();
                if ($check_provider_service_for_transport != Null) {
                    return response()->json([
                        'status' => 0,
                        //                        'message' => 'App User Not Found',
                        'message' => __('provider_messages.5'),
                        "message_code" => 5
                    ]);
                }
                $provider_current_status = 0;
                //return response()->json([
                //    'status' => 0,
                //    'message' => 'App User Not Found',
                //    "message_code" => 5
                //]);
            }
            //if ($extra_provider_details == Null) {
            //    return response()->json([
            //        'status' => 6,
            //        'message' => 'Provider Details not found!',
            //        "message_code" => 73
            //    ]);
            //}
            if ($request_type == 1) {
                if ($request != Null) {
                    $login_type = $request['login_type'];
                    if ($login_type != "facebook" && $login_type != "google" && $login_type != "apple") {
                        $provider_details->country_code = $request['select_country_code'];
                    }
                    $provider_details->last_active = date('Y-m-d H:i:s');

                    $provider_details->currency = $request['select_currency'];
                    $provider_details->language = $request['select_language'];
                    $provider_details->device_token = $request['device_token'];
                    $provider_details->login_device = $request['login_device'];
                    $provider_details->ip_address = request()->header('select-ip-address') != Null ? request()->header('select-ip-address') : Null;
                    $provider_details->time_zone = request()->header('select-time-zone') != Null ? request()->header('select-time-zone') : Null;
                    $provider_details->save();
                }
                $provider_details->generateAccessToken($provider_details->id);
            }
            return response()->json([
                'status' => 1,
                //                'message' => 'success!',
                'message' => __('provider_messages.1'),
                "message_code" => 1,
                'provider_id' => $provider_details['id'],
                'provider_verified' => $provider_details['verified_at'] != Null ? 1 : 0,
                'provider_current_status' => $provider_current_status,
                'provider_name' => $provider_details['first_name'],
                'provider_last_name' => $provider_details['last_name'],
                'access_token' => $provider_details['access_token'],
                'completed_step' => $provider_details['completed_step'],
                'email' => $provider_details['email'] != Null ? $provider_details['email'] : '',
                'login_type' => $provider_details['login_type'],
                'service_radius' => $provider_details['service_radius'] != Null ? $provider_details['service_radius'] - 0 : 0,
                'profile_image' => $provider_details['avatar'] != Null ? url('/assets/images/profile-images/provider/' . $provider_details['avatar']) : '',
                'contact_number' => $provider_details['contact_number'] != Null ? $provider_details['contact_number'] : "",
                'address' => $extra_provider_details != Null ? $extra_provider_details->address : "",
                'landmark' => $extra_provider_details != Null ? $extra_provider_details->landmark != Null ? $extra_provider_details->landmark : "" : "",
                'lat_long' => $extra_provider_details != Null && $extra_provider_details != Null ? $extra_provider_details->lat . ',' . $extra_provider_details->long : "",
                'min_order' => $extra_provider_details != Null  ? (($extra_provider_details->min_order > 0) ? $extra_provider_details->min_order - 0 : 0) : 0,
                'gender' => $provider_details['gender'] != Null ? (($provider_details['gender'] == 1 ? $provider_details['gender'] - 0 : ($provider_details['gender'] == 2 ? $provider_details['gender'] - 0 : 0))) : 0,
                'select_country_code' => $provider_details['country_code'] != Null ? $provider_details['country_code'] : "",
                'select_currency' => $provider_details['currency'] != Null ? $provider_details['currency'] : "",
                'select_language' => $provider_details['language'] != Null ? $provider_details['language'] : "",
                'server_time_zone' => config('app.timezone'),
            ]);
        } elseif ($provider_details['status'] == 2) {
            return response()->json([
                'status' => 3,
                //                'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                'message' =>  __('provider_messages.3'),
                "message_code" => 3
            ]);
        } elseif ($provider_details['status'] == 3) {

            $check_provider_service_for_transport = ProviderServices::query()->where('provider_id', $provider_details['id'])
                ->whereIn('service_cat_id', $this->transport_delivery_service_id_array)
                //->orWhereIn('service_cat_id', $this->delivery_service_id_array)
                ->where('status', 1)
                ->first();

            if ($check_provider_service_for_transport != Null) {
                return response()->json([
                    'status' => 0,
                    //                    'message' => 'App User Not Found',
                    'message' =>  __('provider_messages.5'),
                    "message_code" => 5
                ]);
            }
            if ($request_type == 1) {
                if ($request != Null) {
                    $login_type = $request['login_type'];
                    $provider_details->last_active = date('Y-m-d H:i:s');
                    if ($login_type != "facebook" && $login_type != "google" && $login_type != "apple") {
                        $provider_details->country_code = $request['select_country_code'];
                    }
                    $provider_details->currency = $request['select_currency'];
                    $provider_details->language = $request['select_language'];
                    $provider_details->device_token = $request['device_token'];
                    $provider_details->login_device = $request['login_device'];
                    $provider_details->ip_address = request()->header('select-ip-address') != Null ? request()->header('select-ip-address') : Null;
                    $provider_details->time_zone = request()->header('select-time-zone') != Null ? request()->header('select-time-zone') : Null;
                    $provider_details->save();
                }
                $provider_details->generateAccessToken($provider_details->id);
            }
            return response()->json([
                'status' => 1,
                //                'message' => 'Your Are Not Register Any Service!',
                'message' =>  __('provider_messages.1'),
                "message_code" => 1,
                'provider_id' => $provider_details['id'],
                'provider_verified' => $provider_details['verified_at'] != Null ? 1 : 0,
                'provider_current_status' => 0,
                'provider_name' => $provider_details['first_name'],
                'provider_last_name' => $provider_details['last_name'],
                'access_token' => $provider_details['access_token'],
                'completed_step' => $provider_details['completed_step'],
                'email' => $provider_details['email'] != Null ? $provider_details['email'] : '',
                'login_type' => $provider_details['login_type'],
                'service_radius' => $provider_details['service_radius'] != Null ? $provider_details['service_radius'] - 0 : 0,
                'profile_image' => $provider_details['avatar'] != Null ? url('/assets/images/profile-images/provider/' . $provider_details['avatar']) : '',
                'contact_number' => $provider_details['contact_number'] != Null ? $provider_details['contact_number'] : "",
                'address' => $extra_provider_details != Null ? $extra_provider_details->address : "",
                'landmark' => $extra_provider_details != Null ? $extra_provider_details->landmark != Null ? $extra_provider_details->landmark : "" : "",
                'lat_long' => $extra_provider_details != Null && $extra_provider_details != Null ? $extra_provider_details->lat . ',' . $extra_provider_details->long : "",
                'gender' => $provider_details['gender'] != Null ? ($provider_details['gender'] == 1 ? $provider_details['gender'] - 0 : ($provider_details['gender'] == 2 ? $provider_details['gender'] - 0 : 0)) : 0,
                'select_country_code' => $provider_details['country_code'] != Null ? $provider_details['country_code'] : "",
                'select_currency' => $provider_details['currency'] != Null ? $provider_details['currency'] : "",
                'select_language' => $provider_details['language'] != Null ? $provider_details['language'] : "",
                'server_time_zone' => config('app.timezone'),
            ]);
        } else {
            return response()->json([
                'status' => 0,
                //                'message' => 'something went to wrong!',
                'message' => __('provider_messages.9'),
                'message_code' => 9,
            ]);
        }
    }

    public function providerRegisterAllow($provider_id, $access_token)
    {
        $provider_details = Provider::where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        if ($provider_details != Null) {
            if ($provider_details->status == 2) {
                return response()->json([
                    "status" => 3,
                    //                    "message" => 'Your account is currently blocked, so not authorised to allow any activity!',
                    "message" =>  __('provider_messages.3'),
                    "message_code" => 3
                ]);
            }
            if ($provider_details->access_token != $access_token) {
                return response()->json([
                    'status' => 4,
                    //                    'message' => "Access Token Not Match!",
                    'message' => __('provider_messages.4'),
                    "message_code" => 4
                ]);
            }
            // Bypass OTP verification when APP_ENV=local for testing (matches UserClassApi::checkUserAllow)
            if (!app()->environment('local') && $provider_details->verified_at == Null) {
                return response()->json([
                    'status' => 2,
                    //                    'message' => "Provider Not Verified!",
                    'message' =>  __('provider_messages.2'),
                    "message_code" => 2,
                ]);
            }
            return $provider_details;
        } else {
            return response()->json([
                "status" => 5,
                //                "message" => 'provider not found!',
                "message" => __('provider_messages.5'),
                "message_code" => 5
            ]);
        }
    }

    public function providerServiceList($provider_id)
    {

        $provider_lang = Provider::query()->select('language')->where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        //        return $provider_lang->language;
        if ($provider_lang != null) {

            $lang_prefix =  $this->adminClass->get_langugae_fields($provider_lang->language);
        } else {
            $lang_prefix = "";
        }
        $get_provider_service_list = ProviderServices::query()->select(
            'provider_services.id as provider_service_id',
            'service_category.' . $lang_prefix . 'name as provider_service_name',
            'provider_services.status as provider_service_status',
            'provider_services.current_status as provider_service_current_status',
            'service_category.id as service_category_id',
            'service_category.name as service_cat_name',
            'provider_services.min_price',
            'provider_services.max_price',
            'provider_services.deadline_in_days'
        )
            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            ->where('provider_services.provider_id', $provider_id)
            ->whereIN('provider_services.service_cat_id', $this->on_demand_service_id_array)
            ->get();
        
        // Get provider currency for package price conversion
        $provider_details = Provider::query()->where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency ?? '')->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency ? $provider_currency->ratio : 1;
        
        $get_provider_service_list_data = [];
        foreach ($get_provider_service_list as $key => $single_provider_service) {

            $get_provider_service_list_data[$key]['provider_service_id'] = $single_provider_service->provider_service_id;
            $get_provider_service_list_data[$key]['provider_service_name'] = $single_provider_service->provider_service_name;
            $get_provider_service_list_data[$key]['service_cat_name'] = $single_provider_service->provider_service_name;
            $get_provider_service_list_data[$key]['provider_service_status'] = $single_provider_service->provider_service_status;
            $get_provider_service_list_data[$key]['provider_service_current_status'] = $single_provider_service->provider_service_current_status;
            $get_provider_service_list_data[$key]['service_category_id'] = $single_provider_service->service_category_id;
            $get_provider_service_list_data[$key]['service_cat_id'] = $single_provider_service->service_category_id;
            $get_provider_service_list_data[$key]['status'] = $single_provider_service->provider_service_status;
            $get_provider_service_list_data[$key]['current_status'] = $single_provider_service->provider_service_current_status;
            $get_provider_service_list_data[$key]['min_price'] = $single_provider_service->min_price;
            $get_provider_service_list_data[$key]['max_price'] = $single_provider_service->max_price;
            $get_provider_service_list_data[$key]['deadline_in_days'] = $single_provider_service->deadline_in_days;

            // Fetch subcategories for this service category
            $sub_categories = OtherServiceCategory::query()->select(
                'id as category_id',
                DB::raw("(CASE WHEN " . $lang_prefix . "name != '' THEN  " . $lang_prefix . "name ELSE name END) as category_name")
            )->where('service_cat_id', $single_provider_service->service_category_id)->where('status', 1)->get();
            
            $subcategories_list = [];
            $packages_list = [];
            
            foreach ($sub_categories as $sub_category) {
                $subcategories_list[] = [
                    'category_id' => $sub_category->category_id,
                    'category_name' => $sub_category->category_name
                ];
                
                // Fetch packages for this subcategory
                $packages = OtherServiceProviderPackages::query()->select(
                    'id as package_id',
                    'name as package_name',
                    DB::raw("(CASE WHEN description IS NOT NULL THEN description ELSE '' END) AS package_description"),
                    'max_book_quantity as max_book_quantity',
                    DB::raw('ROUND(price * ' . $currency . ',2) As package_price'),
                    'status as package_status'
                )->where('provider_service_id', $single_provider_service->provider_service_id)
                  ->where('sub_cat_id', $sub_category->category_id)
                  ->where('status', 1)
                  ->get();
                
                foreach ($packages as $package) {
                    $packages_list[] = [
                        'package_id' => $package->package_id,
                        'package_name' => $package->package_name,
                        'package_description' => $package->package_description,
                        'package_price' => $package->package_price,
                        'max_book_quantity' => $package->max_book_quantity
                    ];
                }
            }
            
            $get_provider_service_list_data[$key]['subcategories'] = $subcategories_list;
            $get_provider_service_list_data[$key]['packages'] = $packages_list;

            $provider_gallery_image_data = $this->providerPortfolioList($provider_id, $single_provider_service->service_category_id);
            $provider_gal_image = $provider_gallery_image_data->getData();

            $provider_gallery_image_lists =  isset($provider_gal_image->provider_service_gallery) ? $provider_gal_image->provider_service_gallery : [];
            $get_provider_service_list_data[$key]['provider_service_gallery'] = $provider_gallery_image_lists;
        }
        if (count($get_provider_service_list_data) == 0) {
            return response()->json([
                "status" => 1,
                // "message" => "Your registered services are currently unavailable.",
                "message" => __('provider_messages.340'),
                "message_code" => 340,
            ]);
        }

        return response()->json([
            "status" => 1,
            //            "message" => "success!",
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            "provider_service_list" => $get_provider_service_list_data
        ]);
    }

    public function providerDocumentList($provider_id)
    {
        $provider_lang = Provider::query()->select('language')->where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        if ($provider_lang != null) {
            $lang_prefix =  $this->adminClass->get_langugae_fields($provider_lang->language);
        } else {
            $lang_prefix = "";
        }
        $document_list = [];
        $all_document_uploaded = 1;
        $provider_services = ProviderServices::select('provider_services.id', 'provider_services.service_cat_id', 'service_category.' . $lang_prefix . 'name as service_cat_name')->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')->where('provider_id', $provider_id)->whereIN('service_cat_id', $this->on_demand_service_id_array)->get();
        foreach ($provider_services as $provider_service) {
            $required_documents = RequiredDocuments::where('service_cat_id', $provider_service->service_cat_id)->where('status', 1)->get();
            if (!$required_documents->isEmpty()) {
                $provider_document = [];
                foreach ($required_documents as $document) {
                    $get_provider_document = ProviderDocuments::query()->where('provider_service_id', $provider_service->id)->where('req_document_id', $document->id)->first();
                    if ($get_provider_document == Null) {
                        $all_document_uploaded = 0;
                    }

                    $provider_document[] = [
                        "document_id" => $document->id,
                        "document_name" => $document->name,
                        "document_file" => $get_provider_document != Null ? url('/assets/images/provider-documents/' . $get_provider_document->document_file) : '',
                        "document_status" => $get_provider_document != Null ? $get_provider_document->status : 3,
                    ];
                }
                $document_list[] = [
                    "provider_service_id" => $provider_service->id,
                    "service_cat_id" => $provider_service->service_cat_id,
                    "service_cat_name" => $provider_service->service_cat_name,
                    "all_document_uploaded" => $all_document_uploaded,
                    "document_list" => $provider_document
                ];
            }
        }
        return response()->json([
            "status" => 1,
            //            "message" => "success",
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            "required_document_list" => $document_list
        ]);
    }

    public function getOrderDispatcher($provider_id, $currency, $provider_status)
    {
        $date = new \DateTime();
        $date->modify('-24 hours');
        $date = $date->format('Y-m-d h:i:s');
        $provider_current_status = 0;
        $provider_services_list = '';
        $provider_details = Provider::query()->where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        if ($provider_details == Null) {
            return response()->json([
                "status" => 0,
                //                "message" => "provider details not found",
                "message" => __('provider_messages.73'),
                "message_code" => 73,
            ]);
        }


        $lang_prefix =  $this->adminClass->get_langugae_fields($provider_details->language);

        $general_settings = GeneralSettings::query()->select('provider_min_amount', 'auto_settle_wallet', 'wallet_payment')->first();
        $provider_services = ProviderServices::query()->select(
            'provider_services.id',
            'provider_services.service_cat_id',
            'service_category.' . $lang_prefix . 'name as service_cat_name',
            'provider_services.status',
            'provider_services.current_status'
        )
            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            ->where('provider_services.provider_id', $provider_id)
            //->whereIN('provider_services.service_cat_id', $this->on_demand_service_id_array)
            ->whereIN('service_category.category_type', [3, 4])
            ->get();
        if ($provider_services != Null) {
            $provider_services_list = $provider_services->pluck('service_cat_name')->toArray();
            $provider_services_list = implode(', ', $provider_services_list);
            //$get_status = $provider_services->pluck('status')->toArray();
            $get_current_status = $provider_services->pluck('current_status')->toArray();
            //if (in_array(1, $get_status)) {
            if (in_array(1, $get_current_status)) {
                $provider_current_status = 1;
            }
            //}
        }

        if ($provider_details->avatar != Null) {
            if (filter_var($provider_details->avatar, FILTER_VALIDATE_URL) == true) {
                $provider_profile_image = $provider_details->avatar;
            } else {
                $provider_profile_image = url('/assets/images/profile-images/provider/' . $provider_details->avatar);
            }
        } else {
            $provider_profile_image = "";
        }
        $pending_order_list = [];
        $accepted_order_list = [];
        $processing_order_list = [];
        $completed_order_list = [];
        if ($provider_services != Null) {
            $service_category = $provider_services->pluck('service_cat_id')->toArray();
            $get_orders = UserPackageBooking::query()->select(
                'user_service_package_booking.id as order_id',
                'user_service_package_booking.user_name as customer_name',
                'user_service_package_booking.payment_type as order_payment_type',
                'user_service_package_booking.payment_status as order_payment_status',
                //'users.gender as customer_gender',
                'users.id as user_id',
                'user_service_package_booking.order_no',
                'service_category.' . $lang_prefix . 'name as service_cat_name',
                'user_service_package_booking.order_package_list',
                'user_service_package_booking.order_type',
                'user_service_package_booking.service_date_time',
                'user_service_package_booking.service_date as schedule_order_date',
                //                'user_service_package_booking.service_time as schedule_order_time',
                //                DB::raw("(CASE WHEN user_service_package_booking.service_date_time IS NOT NULL THEN user_service_package_booking.service_date_time ELSE '' END) as schedule_order_date_time"),
                //                DB::raw("(CASE WHEN user_service_package_booking.service_date_time IS NOT NULL THEN DATE_FORMAT(service_date_time, '%a %d %b, %Y') ELSE '' END) as schedule_order_date"),
                //                DB::raw("(CASE WHEN user_service_package_booking.service_date_time IS NOT NULL THEN DATE_FORMAT(service_date_time, '%h:%i %p') ELSE '' END) as schedule_order_time"),

                //                DB::raw("DATE_FORMAT(user_service_package_booking.book_start_time, '%h:%i %p')' - 'DATE_FORMAT(user_service_package_booking.book_end_time, '%h:%i %p') as schedule_order_time"),
                DB::raw(" (concat(DATE_FORMAT(user_service_package_booking.book_start_time, '%h:%i %p'),' - ',DATE_FORMAT(user_service_package_booking.book_end_time, '%h:%i %p'))) as schedule_order_time"),
                //                DB::raw("(CASE WHEN user_service_package_booking.book_start_time IS NOT NULL THEN DATE_FORMAT(user_service_package_booking.book_start_time, '%h:%i %p') ELSE '' END)' - '(CASE WHEN user_service_package_booking.book_end_time IS NOT NULL THEN DATE_FORMAT(user_service_package_booking.book_end_time, '%h:%i %p') ELSE '' END) as schedule_order_time"),
                DB::raw('ROUND(user_service_package_booking.total_pay * ' . $currency . ',2) As total_pay'),
                //'user_service_package_booking.total_pay',
                'user_service_package_booking.lat_long',
                'user_service_package_booking.status as order_status'
            )
                ->join('users', 'users.id', '=', 'user_service_package_booking.user_id')
                ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
                ->where('user_service_package_booking.provider_id', $provider_id)
                ->whereIn('user_service_package_booking.service_cat_id', $service_category)
                ->orderBy('user_service_package_booking.created_at', 'desc')
                //->where('created_at', '>', $date)
                ->whereNull('users.deleted_at')
                ->get()->toArray();
            if (count($get_orders) > 0) {
                $pending_orders = array_filter($get_orders, function ($var) {
                    return ($var['order_status'] == 1);
                });
                $accepted_orders = array_filter($get_orders, function ($var) {
                    return ($var['order_status'] == 2 || $var['order_status'] == 3);
                });
                $processing_orders = array_filter($get_orders, function ($var) {
                    return ($var['order_status'] == 6 || $var['order_status'] == 7 || $var['order_status'] == 8);
                });
                $completed_orders = array_filter($get_orders, function ($var) {
                    return ($var['order_status'] == 9);
                });
                foreach ($pending_orders as $order) {
                    $pending_order_list[] = $order;
                }
                foreach ($accepted_orders as $order) {
                    $accepted_order_list[] = $order;
                }
                foreach ($processing_orders as $order) {
                    $processing_order_list[] = $order;
                }
                foreach ($completed_orders as $order) {
                    $completed_order_list[] = $order;
                }
            }
        }
        //provider wallet balance
        $provider_wallet_balance = UserWalletTransaction::query()->select('remaining_balance')->where('user_id', $provider_id)->orderBy('id', 'desc')->first();

        // average_rating and total_completed_order for dashboard (no hardcoded values on frontend)
        $average_rating = 0;
        $total_completed_order = count($completed_order_list);
        $other_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_id)->first();
        if ($other_details != null) {
            $total_completed_order = (int) $other_details->total_completed_order;
        }
        $avg_from_ratings = OtherServiceRatings::query()->where('provider_id', $provider_id)->where('status', 1)->avg('rating');
        if ($avg_from_ratings !== null) {
            $average_rating = round((float) $avg_from_ratings, 2);
        }

        //check if the provider wallet with min amount
        //converting with default currency

        return response()->json([
            "status" => 1,
            //            "message" => "success",
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            "provider_name" => $provider_details != Null ? $provider_details->first_name : '',
            "provider_status" => $provider_status,
            "provider_profile_image" => $provider_profile_image,
            "provider_service_radius" => $provider_details != Null ? ($provider_details->service_radius != Null ? $provider_details->service_radius . " km" : '') : '',
            "provider_services_list" => $provider_services_list,
            "average_rating" => $average_rating,
            "total_completed_order" => $total_completed_order,
            "current_status" => $provider_current_status,
            "pending_orders" => $pending_order_list,
            "accepted_orders" => $accepted_order_list,
            "processing_order" => $processing_order_list,
            "completed_order" => $completed_order_list,
            "is_auto_settle" => $general_settings->auto_settle_wallet, //parameter for cashout module
        ]);
    }

    public function providerServiceRegisterData($provider_id)
    {

        $provider_data = Provider::query()->select('language', 'contact_number', 'country_code', 'completed_step', 'service_radius','currency')->where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        $provider_currency = WorldCurrency::query()->where('symbol', $provider_data->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency->ratio;

        $provider_lang =  $provider_data->language;
        //        return $provider_lang->language;
        if ($provider_lang != "en" && $provider_lang != "" && $provider_lang != "Null") {
            $lang_prefix = $provider_lang . "_";
        } else {
            $lang_prefix = "";
        }
        $address = "";
        $landmark = "";
        $lat = 0.0;
        $long = 0.0;
        $completed_step = $provider_data->completed_step;
        $service_radius = ($provider_data->service_radius > 0) ? $provider_data->service_radius : 0;
        $provider_other_service_detail  = OtherServiceProviderDetails::query()->where('provider_id', '=', $provider_id)->first();
        if ($provider_other_service_detail != Null) {
            $address = ($provider_other_service_detail->address != "") ? $provider_other_service_detail->address : "";
            $landmark = ($provider_other_service_detail->landmark != "") ? $provider_other_service_detail->landmark : "";
            $lat = ($provider_other_service_detail->lat != "") ? $provider_other_service_detail->lat - 0 : 0.0;
            $long = ($provider_other_service_detail->long != "") ? $provider_other_service_detail->long - 0 : 0.0;
        }

        //get serive cateogry of provider if added
        $get_provider_service =  ProviderServices::query()->where('provider_id', '=', $provider_id)->first();
        $selected_service_cateogry_id = 0;
        $provider_service_id = 0;
        if ($get_provider_service != Null) {
            $selected_service_cateogry_id = $get_provider_service->service_cat_id;
            $provider_service_id = $get_provider_service->id;
        }

        $service_category_list = ServiceCategory::query()->select(
            'id as service_cat_id',
            $lang_prefix . 'name as service_category_name',
            DB::raw("(CASE WHEN id = '" . $selected_service_cateogry_id . "' THEN 1 ELSE 0 END) as selected_service_category")
        )
            ->whereIN('category_type', [3, 4])
            ->where('status', 1)
            ->get();

        //get provider sevice package lists
        $provider_package_details = OtherServiceProviderPackages::query()->where('provider_service_id', '=', $provider_service_id)->first();
        $package_name = "";
        $description = "";
        $price = 0;
        $max_book_quantity = 0;
        $provider_selected_sub_category_id = isset($provider_package_details->sub_cat_id) ? $provider_package_details->sub_cat_id : 0;

        $service_sub_category_list = OtherServiceCategory::query()->select(
            'id as service_sub_cat_id',
            $lang_prefix . 'name as service_sub_category_name',
            DB::raw("(CASE WHEN id = '" . $provider_selected_sub_category_id . "' THEN 1 ELSE 0 END) as selected_service_sub_category")
        )
            ->where('service_cat_id', '=', $selected_service_cateogry_id)
            ->where('status', '=', 1)
            ->get();

        if ($provider_package_details != Null) {
            $package_name = ($provider_package_details->name != "") ? $provider_package_details->name : "";
            $description = ($provider_package_details->description != "") ? $provider_package_details->description : "";
            $price = ($provider_package_details->price > 0) ? $provider_package_details->price * $currency : 0.0;

        info('--------------------------------price 1111');
        info($price);

            // $price = ($provider_package_details->price > 0)?$provider_package_details->price:0.0;
            $max_book_quantity = ($provider_package_details->max_book_quantity > 0) ? $provider_package_details->max_book_quantity : 0;
        }

        if ($completed_step == 4 || $completed_step == 3) {
            $data = $this->providerDocumentList($provider_id);
            $required_document_list = $data->getData();
            $required_document_lists =  isset($required_document_list->required_document_list[0]) ? $required_document_list->required_document_list[0] : Null;
            if ($required_document_lists != Null) {
                $all_document_uploaded = isset($required_document_list->required_document_list[0]->all_document_uploaded) ? $required_document_list->required_document_list[0]->all_document_uploaded : 0;
                if ($all_document_uploaded == 1) {
                    Provider::query()->where('id', $provider_id)->update(array('completed_step' => 5, 'status' => 1));
                    $completed_step = 5;
                }
            } else {
                //code after 3 step redirect to 5th step if no document avaialbel in services
                Provider::query()->where('id', $provider_id)->update(array('completed_step' => 5, 'status' => 1));
                $completed_step = 5;
            }
        } else {
            $required_document_lists = Null;
        }

        info('--------------------------------price');
        info($price);

        return response()->json([
            "status" => 1,
            //            "message" => "success!",
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            "completed_step" => $completed_step,
            "landmark" => $landmark,
            "address" => $address,
            "lat" => $lat,
            "long" => $long,
            "service_radius" => $service_radius,
            'contact_number' => $provider_data->contact_number != Null ? $provider_data->contact_number : "",
            'select_country_code' => $provider_data->country_code != Null ? $provider_data->country_code : "",
            "service_category_list" => $service_category_list,
            "service_sub_category_list" => $service_sub_category_list,
            "package_name" => $package_name,
            "description" => $description,
            "price" => round($price,2),
            "max_book_quantity" => $max_book_quantity,
            'required_document_lists' => $required_document_lists
        ]);
    }
    //get provider portfolio list
    public function providerPortfolioList($provider_id = 0, $service_category_id = 0)
    {
        if ($provider_id > 0 && $service_category_id > 0) {
            $provider_porftfolio_image_path = url('/assets/images/provider-portfolio-images/');
            $provider_portfolio_images = ProviderPortfolioImage::query()
                ->select(
                    'id',
                    'service_cat_id as service_category_id',
                    'provider_id as provider_id',
                    DB::raw("(CASE WHEN image != '' THEN (concat('$provider_porftfolio_image_path','/',image)) ELSE '' END) as portfolio_image")
                )
                ->where('provider_id', '=', $provider_id)
                ->where('service_cat_id', '=', $service_category_id)
                ->where('status', '=', 1)
                ->get()->toArray();

            return response()->json([
                "status" => 1,
                //                "message" => "success",
                "message" => __('provider_messages.1'),
                "message_code" => 1,
                "provider_service_gallery" => $provider_portfolio_images
            ]);
        } else {
            return response()->json([
                "status" => 1,
                //                "message" => "Provider or Provider service not found",
                "message" =>  __('provider_messages.1'),
                "message_code" => 1,
                "provider_service_gallery" => []
            ]);
        }
    }
}
