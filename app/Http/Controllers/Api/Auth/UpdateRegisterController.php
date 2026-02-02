<?php

namespace App\Http\Controllers\Api\Auth;

use App\Classes\AdminClass;
use App\Classes\AuthAlertClass;
use App\Classes\NotificationClass;
use App\Classes\OnDemandClassApi;
use App\Classes\TokenClassApi;
use App\Classes\UserClassApi;
use App\Models\OtherServiceProviderDetails;
use App\Models\Provider;
use App\Models\ProviderServices;
use App\Models\ProviderVerification;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;
use Twilio\Rest\Client;

class UpdateRegisterController extends Controller
{
//        json response status [
//            0 => false,
//            1 => true,
//            2 => registration pending,
//            3 => app user blocked,
//            4 => app user access token not match,
//            5 => app user not found
//          ]

    private $userClassApi;
    private $onDemandClassApi;
    private $tokenClassApi;
    private $on_demand_service_id_array;
    private $notificationClass;
    private $adminClass;

    public function __construct(TokenClassApi $tokenClassApi, UserClassApi $userClassApi, OnDemandClassApi $onDemandClassApi, NotificationClass $notificationClass, AdminClass $adminClass)
    {
        $this->userClassApi = $userClassApi;
        $this->onDemandClassApi = $onDemandClassApi;
        $this->notificationClass = $notificationClass;
        $this->tokenClassApi = $tokenClassApi;
        $this->adminClass = $adminClass;
//        $this->on_demand_service_id_array = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30];
        $service_cat = ServiceCategory::query()->whereIN('category_type', [3, 4])->where('status', 1)->get()->pluck('id')->toArray();
        $this->on_demand_service_id_array = $service_cat;
    }

    public function postUpdateCustomerDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|integer",
            "access_token" => "required|numeric",
            "is_update" => "nullable|numeric|in:0,1",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_details = $this->userClassApi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_details) == false) {
            return $user_details;
        }

        if ($request->get('is_update') == 1 || $request->get('is_update') == NULL) {
            $validator = Validator::make($request->all(), [
            "full_name" => "required",
            "last_name" => "nullable",
            "emergency_contact" => "nullable",
            "email" => [
                "required",
                "email",
                Rule::unique('users','email')->where(function ($query) use ($request) {
                    $query->where('email', '=', $request->get('email'));
                    $query->where('id', '!=', $request->get('user_id'));
                    $query->where('deleted_at', '=', null);
                }),
            ],
            "select_country_code" => "required",
            'contact_number' => [
                'required','numeric',
                Rule::unique('users')->where(function($query) use($request) {
                    $query->where('contact_number', '=', $request->get('contact_number'));
                    $query->where('country_code', '=', $request->get('select_country_code'));
                    $query->where('id', '!=', $request->get('user_id'));
                    $query->where('deleted_at', '=', null);
                })
            ],
            ]);
//                [ 'contact_number.unique' => __('user_messages.305')]);
            if ($validator->fails()) {
                $failedRules = $validator->failed();
                if (isset($failedRules['email']['Unique'])) {
                    return response()->json([
                        "status" => 0,
                        "message" => __('user_messages.11'),
                        "message_code" => 11,
                    ]);
                }
                if (isset($failedRules['contact_number']['Unique'])) {
                    return response()->json([
                        "status" => 0,
                        "message" => __('user_messages.12'),
                        "message_code" => 12,
                    ]);
                }
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            $check_contact = User::query()->where('contact_number',$request->get('contact_number'))
                ->where('country_code',$request->get('select_country_code'))
                ->whereNotIn('id',[$request->get('user_id')])
                ->whereNull('users.deleted_at')
                ->count();
            if($check_contact > 0){
                return response()->json([
                    "status" => 0,
    //                "message" => "Contact Number already been taken!",
                    'message' => __('user_messages.12'),
                    "message_code" => 12,
                ]);
            }

            $user_details->first_name = $request->get('full_name');
            if ($request->get('last_name') != Null) {
                $user_details->last_name = $request->get('last_name');
            }
            $user_details->gender = $request->get('gender');
            $user_details->contact_number = $request->get('contact_number');
            $user_details->country_code = $request->get('select_country_code');
            $user_details->email = $request->get('email');
            //$user_details->contact_number = $request->get('contact_number');
            if ($request->file('profile_image') != Null) {
                if (\File::exists(public_path('/assets/images/profile-images/customer/' . $user_details->avatar))) {
                    \File::delete(public_path('/assets/images/profile-images/customer/' . $user_details->avatar));
                }
                $destinationPath = public_path('/assets/images/profile-images/customer/');
                $file = $request->file('profile_image');
                $img = Image::read($file->getRealPath());
                $img->orient();
                $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                $img->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($destinationPath . $file_new);
                $user_details->avatar = $file_new;
            }
            $user_details->emergency_contact = $request->get('emergency_contact') != Null ? $request->get('emergency_contact') : Null;
            $user_details->save();

            if ($user_details->avatar != Null) {
                if (filter_var($user_details->avatar, FILTER_VALIDATE_URL) == true) {
                    $avatar = $user_details->avatar;
                } else {
                    $avatar = url('/assets/images/profile-images/customer/' . $user_details->avatar);
                }
            } else {
                $avatar = Null;
            }
        }

        return $this->userClassApi->userLoginRegisterUpdateDetails($user_details);
    }

    public function postCustomerUpdateCountryAndCurrency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "nullable|numeric",
            "access_token" => "nullable",
            'select_language' => "required",
            'select_country_code' => "required",
            'select_currency' => "required"
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
            $user_details = $this->userClassApi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
            if ($decoded['status'] = json_decode($user_details) == false) {
                return $user_details;
            }
        }

        if ($user_details <> null) {
            $user_details->country_code = $request->get('select_country_code');
            $user_details->currency = $request->get('select_currency');
            $user_details->language = $request->get('select_language');
            $user_details->save();
        }
        return response()->json([
            'status' => 1,
//            'message' => "success!",
            'message' => __('user_messages.1'),
            "message_code" => 1,
        ]);

    }

    //on-demand
    public function postOnDemandSocialRequiredField(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required",
//            "email" => "required|email|unique:providers,email," . $request->get('provider_id'),
            "email" => [
                "required",
                "email",
                Rule::unique('providers','email')->where(function ($query) use ($request) {
                    $query->where('email', '=', $request->get('email'));
                    $query->where('id', '!=', $request->get('provider_id'));
                    $query->where('deleted_at', '=', null);
                }),
            ],
            "select_country_code" => "required",
            //"contact_number" => "required|numeric|unique:providers,contact_number," . $request->get('provider_id')
//            "contact_number" => "required"
            'contact_number' => [
                'required','numeric',
                Rule::unique('providers')->where(function($query) use($request) {
                    $query->where('contact_number', '=', $request->get('contact_number'));
                    $query->where('country_code', '=', $request->get('select_country_code'));
                    $query->where('id', '!=', $request->get('provider_id'));
                    $query->where('deleted_at', '=', null);
                })
            ],
        ]);
        if ($validator->fails()) {
            if (isset($failedRules['email']['Unique'])) {
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.11'),
                    "message_code" => 11,
                ]);
            }
            if (isset($failedRules['contact_number']['Unique'])) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 12,
                ]);
            }
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $check_contact = Provider::query()->where('contact_number',$request->get('contact_number'))
            ->where('country_code',$request->get('select_country_code'))
            ->where('provider_type', '=',3)
            ->whereNull('providers.deleted_at')
            ->count();
        if($check_contact > 0){
            return response()->json([
                "status" => 0,
//                "message" => "Contact Number already been taken!",
                "message" =>  __('provider_messages.12'),
                "message_code" => 12,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_details = Provider::query()->where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        if ($provider_details != Null) {
            if ($provider_details->status == 2) {
                return response()->json([
                    "status" => 3,
//                    "message" => 'Your account is currently blocked, so not authorised to allow any activity!',
                    "message" =>  __('provider_messages.3'),
                    "message_code" => 3
                ]);
            }
            if ($provider_details->access_token != $request->get('access_token')) {
                return response()->json([
                    'status' => 4,
//                    'message' => "Access Token Not Match!",
                    'message' =>  __('provider_messages.4'),
                    "message_code" => 4
                ]);
            }
            $provider_details = Provider::query()->where('id', $provider_id)->where('provider_type', '=',3)->whereNull('providers.deleted_at')->first();
            if ($provider_details == Null) {
                return response()->json([
                    "status" => 5,
//                    "message" => 'driver not found!',
                    "message" =>  __('provider_messages.5'),
                    "message_code" => 5,
                ]);
            }
            $provider_details->email = $request->get('email');
            $provider_details->contact_number = $request->get('contact_number');
            $provider_details->country_code = $request->get('select_country_code');
            $provider_details->save();
            $this->tokenClassApi->sendProviderSmsVerification($provider_details->id);
            return $this->onDemandClassApi->onDemandLoginRegisterResponse($provider_details);
        } else {
            return response()->json([
                "status" => 5,
//                "message" => 'provider not found!',
                "message" =>  __('provider_messages.5'),
                "message_code" => 5
            ]);
        }
    }

    public function postUpdateOnDemandProviderDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "is_update" => "nullable|numeric|in:0,1",
        ]);
        if ($validator->fails()) {
            /*if (isset($failedRules['contact_number']['Unique'])) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 12,
                ]);
            }*/
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_id = $request->get('provider_id');
        $check_provider_details = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($check_provider_details) == false) {
            return $check_provider_details;
        }

        $provider_details = Provider::query()->where('id', $request->get('provider_id'))->where('provider_type', '=',3)->whereNull('providers.deleted_at')->first();
        if ($provider_details != Null) {
            if($request->get('is_update') == 1 || $request->get('is_update') == NULL) {
                $validator = Validator::make($request->all(), [
                    "full_name" => "required",
                    "last_name" => "nullable",
                    "profile_image" => "nullable",
                    "gender" => "required|numeric|in:1,2",
                    "address" => "required",
                    "lat" => "required|numeric",
                    "long" => "required|numeric",
                    //"landmark" => "required",
                    "landmark" => "nullable",
                    "service_radius" => "required|integer",
                    "select_country_code" => "required",
//                    "email" => "required|email|unique:providers,email," . $request->get('provider_id'),
                    "email" => [
                        "required",
                        "email",
                        Rule::unique('providers')->where(function ($query) use ($request) {
                            $query->where('email', '=', $request->get('email'));
                            $query->where('id', '!=', $request->get('provider_id'));
                            $query->where('deleted_at', '=', null);
                        }),
                    ],
                    //"contact_number" => "required|numeric|unique:providers,contact_number," . $request->get('provider_id'),
//                    "contact_number" => "required",
                    "contact_number" => [
                        "required",
                        "numeric",
                        Rule::unique('providers')->where(function ($query) use ($request) {
                            $query->where('contact_number', '=', $request->get('contact_number'));
                            $query->where('id', '!=', $request->get('provider_id'));
                            $query->where('deleted_at', '=', null);
                        }),
                    ],
                    "min_order" => "required|numeric",

                ]);
                if ($validator->fails()) {
                    $failedRules = $validator->failed();
                    if (isset($failedRules['email']['Unique'])) {
                        return response()->json([
                            "status" => 0,
                            "message" => __('user_messages.11'),
                            "message_code" => 11,
                        ]);
                    }
                    if (isset($failedRules['contact_number']['Unique'])) {
                        return response()->json([
                            "status" => 0,
                            "message" => __('user_messages.12'),
                            "message_code" => 12,
                        ]);
                    }
                    return response()->json([
                        "status" => 0,
                        "message" => $validator->errors()->first(),
                        "message_code" => 9,
                    ]);
                }
                $check_contact = Provider::query()->where('contact_number',$request->get('contact_number'))
                    ->where('country_code',$request->get('select_country_code'))
                    ->whereNotIn('id',[$request->get('provider_id')])
                    ->whereNull('providers.deleted_at')
                    ->count();
                if($check_contact > 0){
                    return response()->json([
                        "status" => 0,
//                        "message" => "Contact Number already been taken!",
                        "message" =>  __('provider_messages.12'),
                        "message_code" => 12,
                    ]);
                }

                $provider_details->first_name =ucwords(strtolower(trim($request->get('full_name'))));
                $provider_details->last_name = ucwords(strtolower(trim($request->get('last_name'))));
                $provider_details->email = $request->get('email');
                $provider_details->gender = $request->get('gender');
                $provider_details->service_radius = $request->get('service_radius');
                $provider_details->contact_number = $request->get('contact_number');
                $provider_details->country_code = $request->get('select_country_code');
                //$provider_details->contact_number = $request->get('contact_number');
                if ($request->file('profile_image') != Null) {
                    if (\File::exists(public_path('/assets/images/profile-images/provider/' . $provider_details->avatar))) {
                        \File::delete(public_path('/assets/images/profile-images/provider/' . $provider_details->avatar));
                    }
                    $file = $request->file('profile_image');
                    $file_new = rand(1, 9) . date('sihYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path() . '/assets/images/profile-images/provider/', $file_new);
                    $provider_details->avatar = $file_new;
                }
                $provider_details->save();

                $provider_other_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_details->id)->first();
                if ($provider_other_details == Null) {
                    $provider_other_details = new OtherServiceProviderDetails();
                    $provider_other_details->provider_id = $provider_details->id;
                }
                $provider_other_details->address = $request->get('address');
                $provider_other_details->landmark = $request->get('landmark');
                $provider_other_details->lat = $request->get('lat');
                $provider_other_details->long = $request->get('long');
                $provider_other_details->min_order = $request->get('min_order');
                $provider_other_details->save();

//                $general_settings = request()->get("general_settings");
//                if ($general_settings !=  Null) {
//                    if ($general_settings->send_mail == 1) {
//                        try {
//                            if ($general_settings != Null && $general_settings->send_receive_email != Null) {
//                                $get_provider_service_list = ProviderServices::query()->select('service_category.name')
//                                    ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
//                                    ->where('provider_services.provider_id', $provider_details->id)
//                                    ->whereIN('provider_services.service_cat_id', $this->on_demand_service_id_array)->get()
//                                    ->pluck('name')->toArray();
//                                $get_provider_service_count = count($get_provider_service_list);
//                                if ($get_provider_service_count > 0) {
//                                    $mail_type = "admin_new_provider_signup";
//                                    $to_mail = $general_settings->send_receive_email;
//                                    $provider_service_list = implode(" , ", $get_provider_service_list);
//                                    $provider_email = $provider_details->email;
//                                    $provider_contact_number = $provider_details->contact_number;
//                                    $provider_name = ucwords($provider_details->first_name);
//                                    $subject = "New Provider Registered";
//                                    $disp_data = array("##provider_name##" => $provider_name, "##services_name##" => $provider_service_list, "##email##" => $provider_email, "##contact_no##" => $provider_contact_number);
//                                    $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
//                                }
//                            }
//                        } catch (\Exception $e) {}
//                    }
//                }
            }

            return $this->onDemandClassApi->onDemandLoginRegisterResponse($provider_details);
        } else {
            return response()->json([
                "status" => 0,
//                "message" => "provider details not found!",
                "message" =>  __('provider_messages.73'),
                "message_code" => 73,
            ]);
        }
    }

    public function postProviderUpdateCountryAndCurrency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required",
            'select_language' => "required",
            'select_country_code' => "required",
            'select_currency' => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }
        $provider_details = Provider::query()->where('id', $request->get('provider_id'))->where('provider_type', '=',3)->whereNull('providers.deleted_at')->first();
        if ($provider_details == Null) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        //$provider_details->country_code = $request->get('select_country_code');
        $provider_details->currency = $request->get('select_currency');
        $provider_details->language = $request->get('select_language');
        $provider_details->save();

        $provider_services_list= '';
        $lang_prefix = $this->adminClass->get_langugae_fields($provider_details->language);
        $provider_services = ProviderServices::query()->select('provider_services.id',
            'provider_services.service_cat_id', 'service_category.'.$lang_prefix.'name as service_cat_name', 'provider_services.status', 'provider_services.current_status')
            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            ->where('provider_services.provider_id', $provider_details->id)
            ->whereIN('service_category.category_type', [3, 4])
            ->get();
        if ($provider_services != Null) {
            $provider_services_list = $provider_services->pluck('service_cat_name')->toArray();
            $provider_services_list = implode(', ', $provider_services_list);
        }

        return response()->json([
            'status' => 1,
//            'message' => "success!",
            'message' => __('provider_messages.1'),
            'provider_services_list' => $provider_services_list,
            "message_code" => 1,
        ]);
    }

    //user
    public function postCustomerResendOtpVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $user = User::query()->where('id', $request->get('user_id'))->whereNull('users.deleted_at')->first();
        if ($user != Null) {
            if ($user->verified_at == Null) {
                $this->tokenClassApi->sendUserSmsVerification($user->id);
            }
        }
        return response()->json([
            "status" => 1,
//            "message" => "success",
            'message' => __('user_messages.1'),
            "message_code" => 1,
        ]);
    }

    public function postCustomerContactVerification(Request $request)
    {
        $check_authentication = (new AuthAlertClass())->checkAuthorizationApp($request);
        if ($check_authentication->getData()->status != 1){
            return $check_authentication;
        }
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required|numeric",
            "otp" => "required|numeric|digits:4"
        ]);
        if ($validator->fails()) {
            $failedRules = $validator->failed();
            if (isset($failedRules['otp']['Digits'])) {
                return response()->json([
                    "status" => 0,
//                    "message" => "Invalid Otp!",
                    'message' => __('user_messages.89'),
                    "message_code" => 89,
                ]);
            }
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $user_details = User::query()->where('id', $request->get('user_id'))->whereNull('users.deleted_at')->first();
        if ($user_details != Null) {
            if ($user_details->access_token != $request->get('access_token')) {
                return response()->json([
                    'status' => 4,
//                    'message' => "Access Token Not Match!",
                    'message' => __('user_messages.4'),
                    "message_code" => 4,
                ]);
            }
            $settings = request()->get('general_settings');
            if ($settings == Null) {
                return response()->json([
                    "status" => 0,
                    //"message" => "something went to wrong!",
                    "message" => __('user_messages.9'),
                    "message_code" => 9,
                ]);
            }

            // Verify if OTP verification is enabled and user is allowed
            if (($settings->is_otp_verification != Null && $settings->is_otp_verification == 1) && $user_details->is_default_user == 0 && $user_details->fix_user_show == 0) {
                if (isset($settings->otp_method)) {
                    // Check OTP method (Twilio)
                    if ($settings->otp_method == 1){
                        $get_otp = UserVerification::query()->where('user_id', "=", $user_details->id)->first();
                        if ($get_otp == Null){
                            return response()->json([
                                "status" => 0,
                                "message" => __('user_messages.89'),
                                "message_code" => 89,
                            ]);
                        }
                        try {
                            if ($settings->twilio_service_key == Null || $settings->twilio_auth_token == Null || $settings->twilio_verify_service_key == Null) {
                                return response()->json([
                                    "status" => 0,
                                    "message" => __('user_messages.9'),
                                    "message_code" => 9,
                                ]);
                            }
                            // Initialize Twilio client
                            $twilio = new Client($settings->twilio_service_key, $settings->twilio_auth_token);
                            $option = [
                                'To' => $user_details->country_code.$user_details->contact_number,
                                'VerificationSid' => $get_otp->token,
                                'Code' => $request->get('otp'),
                            ];
                            $verification_check = $twilio->verify->v2->services($settings->twilio_verify_service_key)->verificationChecks->create($option);
                            if ($verification_check->status == "approved") {
                                $verification_sid = $verification_check->sid;
                                if ($verification_sid != $get_otp->token) {
                                    return response()->json([
                                        "status" => 0,
                                        "message" => __('user_messages.9'),
                                        "message_code" => 9,
                                    ]);
                                }
                                UserVerification::query()->where('user_id', "=", $user_details->id)->delete();
                                $user_details->verified_at = date('Y-m-d H:i:s');
                                $user_details->save();
                            } else {
                                return response()->json([
                                    "status" => 0,
                                    "message" => __('user_messages.89'),
                                    "message_code" => 89,
                                ]);
                            }
                        } catch (\Exception $e) {
                            return response()->json([
                                "status" => 0,
                                "message" => __('user_messages.89'),
                                "message_code" => 89,
                            ]);
                        }
                    }
                    else{
                        return response()->json([
                            "status" => 0,
                            //"message" => "Verify method does not exists!",
                            "message" => __('user_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                } else {
                    return response()->json([
                        "status" => 0,
                        "message" => "Verify method does not exists",
                        "message_code" => 9,
                    ]);
                }
            } else {
                if ($request->get('otp') == "1234") {
                    $user_details->verified_at = date('Y-m-d H:i:s');
                    $user_details->save();
                } else {
                    return response()->json([
                        "status" => 0,
                        //"message" => "Invalid Otp!",
                        "message" => __('user_messages.89'),
                        "message_code" => 89,
                    ]);
                }
            }
            return $this->userClassApi->userLoginRegisterUpdateDetails($user_details);
        } else {
            return response()->json([
                "status" => 5,
//                "message" => "user not found!",
                'message' => __('user_messages.5'),
                "message_code" => 5,
            ]);
        }
    }

    //on-demand provider
    public function postOnDemandResendOtpVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider = Provider::query()->where('id', $request->get('provider_id'))->where('provider_type', '=',3)->whereNull('providers.deleted_at')->first();
        if ($provider != Null) {
            if ($provider->verified_at == Null) {
                $this->tokenClassApi->sendProviderSmsVerification($provider->id);
            }
        }
        return response()->json([
            "status" => 1,
//            "message" => "Fresh OTP has been sent to your register phone number!",
            "message" => __('provider_messages.324'),
            "message_code" => 324,
        ]);
    }

    public function postOnDemandContactVerification(Request $request)
    {
        $check_authentication = (new AuthAlertClass())->checkAuthorizationApp($request);
        if ($check_authentication->getData()->status != 1){
            return $check_authentication;
        }
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "otp" => "required|numeric|digits:4"
        ]);
        if ($validator->fails()) {
            $failedRules = $validator->failed();
            if (isset($failedRules['otp']['Digits'])) {
                return response()->json([
                    "status" => 0,
//                    "message" => "Invalid Otp!",
                    "message" => __('provider_messages.89'),
                    "message_code" => 89,
                ]);
            }
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_details = Provider::query()->where('id', $request->get('provider_id'))->where('provider_type', '=',3)->whereNull('providers.deleted_at')->first();
        if ($provider_details != Null) {
            $settings = request()->get('general_settings');
            if ($settings == Null) {
                return response()->json([
                    "status" => 0,
                    "message" => "something went to wrong!",
                    "message_code" => 9,
                ]);
            }
            //changes
            if ($settings->is_otp_verification != Null && $settings->is_otp_verification == 1 && $provider_details->is_default_user == 0 && $provider_details->fix_user_show == 0) {
                if (isset($settings->otp_method)) {
                    if ($settings->otp_method == 1) {
                        $get_otp = ProviderVerification::query()->where('provider_id', $provider_details->id)->first();
                        if ($get_otp == Null) {
                            return response()->json([
                                "status" => 0,
                                //"message" => "OTP Details not found",
                                "message" => __('provider_messages.9'),
                                "message_code" => 9,
                            ]);
                        }
                        if ($settings->twilio_service_key == Null || $settings->twilio_auth_token == Null || $settings->twilio_verify_service_key == Null) {
                            return response()->json([
                                "status" => 0,
                                //"message" => "something went to wrong!",
                                "message" => __('provider_messages.9'),
                                "message_code" => 9,
                            ]);
                        }
                        try {
                            $twilio = new Client($settings->twilio_service_key, $settings->twilio_auth_token);
                            $option = [
                                'To' => $provider_details->country_code . $provider_details->contact_number,
                                'VerificationSid' => $get_otp->token,
                                'Code' => $request->get('otp'),
                            ];
                            $verification_check = $twilio->verify->v2->services($settings->twilio_verify_service_key)->verificationChecks->create($option);
                            if ($verification_check->status == "approved") {
                                $verification_sid = $verification_check->sid;
                                if ($verification_sid != $get_otp->token) {
                                    return response()->json([
                                        "status" => 0,
                                        //"message" => "something went to wrong!",
                                        "message" => __('provider_messages.9'),
                                        "message_code" => 9,
                                    ]);
                                }
                                ProviderVerification::query()->where('provider_id', $provider_details->id)->delete();
                                $provider_details->verified_at = date('Y-m-d H:i:s');
                                $provider_details->save();
                            } else {
                                return response()->json([
                                    "status" => 0,
                                    //"message" => "Entered OTP is Incorrect!",
                                    "message" => __('provider_messages.89'),
                                    "message_code" => 89,
                                ]);
                            }
                        } catch (\Exception $e) {
                            return response()->json([
                                "status" => 0,
                                //"message" => "Entered OTP is Incorrect!",
                                "message" => __('provider_messages.89'),
                                "message_code" => 89,
                            ]);
                        }
                    } else {
                        return response()->json([
                            "status" => 0,
                            //"message" => "Verify method does not exists!",
                            "message" => __('provider_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                } else {
                    return response()->json([
                        "status" => 0,
                        "message" => "Verify method does not exists",
                        "message_code" => 9,
                    ]);
                }
            } else {
                if ($request->get('otp') == "1234") {
                    $provider_details->verified_at = date('Y-m-d H:i:s');
                    $provider_details->save();
                } else {
                    return response()->json([
                        "status" => 0,
                        //"message" => "Invalid Otp!",
                        "message" => __('user_messages.89'),
                        "message_code" => 89,
                    ]);
                }
            }
            return $this->onDemandClassApi->onDemandLoginRegisterResponse($provider_details);
        }
        else {
            return response()->json([
                "status" => 5,
//                "message" => "Provider not found!",
                "message" => __('provider_messages.5'),
                "message_code" => 5,
            ]);
        }
    }

    public function postCustomerChangeContactNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|numeric",
            "access_token" => "required",
            "contact_number" => "required|numeric",
            "select_country_code" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $check_contact = User::query()->where('contact_number',$request->get('contact_number'))
            ->where('country_code',$request->get('select_country_code'))
            ->where('id', '!=',$request->get('user_id'))
            ->whereNull('users.deleted_at')
            ->count();
        if($check_contact > 0){
            return response()->json([
                "status" => 0,
//                "message" => "Contact Number already been taken!",
                'message' => __('user_messages.12'),
                "message_code" => 12,
            ]);
        }

        $user_details = User::query()->where('id', $request->get('user_id'))->whereNull('users.deleted_at')->first();
        if ($user_details != Null) {
            if ($user_details->access_token != $request->get('access_token')) {
                return response()->json([
                    'status' => 4,
//                    'message' => "Access Token Not Match!",
                    'message' => __('user_messages.4'),
                    "message_code" => 4,
                ]);
            }
            if ($user_details->status == 0) {
                return response()->json([
                    'status' => 3,
//                    'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                    'message' => __('user_messages.3'),
                    "message_code" => 3,
                ]);
            }
            $user_details->contact_number = $request->get('contact_number');
            $user_details->country_code = $request->get('select_country_code');
            $user_details->save();
            $this->tokenClassApi->sendUserSmsVerification($user_details->id);
            return response()->json([
                'status' => 1,
//                'message' => 'Success!',
                'message' => __('user_messages.1'),
                "message_code" => 1,
                'contact_number' => $user_details->contact_number,
                "select_country_code" => $user_details->country_code
            ]);
        } else {
            return response()->json([
                'status' => 5,
//                'message' => "User not found!",
                'message' => __('user_messages.5'),
                "message_code" => 5,
            ]);
        }
    }

    public function postProviderChangeContactNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required",
            "contact_number" => "required|numeric",
            "select_country_code" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $check_contact = Provider::query()
            ->where('contact_number',$request->get('contact_number'))
            ->where('country_code',$request->get('select_country_code'))
            ->where('id', '!=',$request->get('provider_id'))
            ->whereNull('providers.deleted_at')
            ->count();

        if($check_contact > 0){
            return response()->json([
                "status" => 0,
//                "message" => "Contact Number already been taken!",
                "message" => __('provider_messages.12'),
                "message_code" => 12,
            ]);
        }

        $provider_details = Provider::query()->where('id', $request->get('provider_id'))->whereNull('providers.deleted_at')->first();
        if ($provider_details != Null) {
            if ($provider_details->access_token != $request->get('access_token')) {
                return response()->json([
                    'status' => 4,
//                    'message' => "Access Token Not Match!",
                    'message' => __('provider_messages.4'),
                    "message_code" => 4,
                ]);
            }
            if ($provider_details->status == 2) {
                return response()->json([
                    'status' => 3,
//                    'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                    'message' => __('provider_messages.3'),
                    "message_code" => 3,
                ]);
            }
            $provider_details->contact_number = $request->get('contact_number');
            $provider_details->country_code = $request->get('select_country_code');
            $provider_details->save();
            $this->tokenClassApi->sendProviderSmsVerification($provider_details->id);
            return response()->json([
                'status' => 1,
//                'message' => 'Success!',
                'message' => __('provider_messages.1'),
                "message_code" => 1,
                'contact_number' => $provider_details->contact_number,
                "select_country_code" => $provider_details->country_code
            ]);
        } else {
            return response()->json([
                'status' => 5,
//                'message' => "User not found!",
                'message' => __('provider_messages.5'),
                "message_code" => 5,
            ]);
        }
    }
}
