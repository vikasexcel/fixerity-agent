<?php

namespace App\Http\Controllers\Api\Auth;

use App\Classes\AdminClass;
use App\Classes\AuthAlertClass;
use App\Classes\NotificationClass;
use App\Classes\OnDemandClassApi;
use App\Classes\TokenClassApi;
use App\Classes\UserClassApi;
use App\Models\Provider;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\UserReferHistory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
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
    Private $onDemandClassApi;
    private $tokenClassApi;
    private $adminClass;
    private $on_demand_service_id_array;
    private $notificationClass;

    public function __construct(TokenClassApi $tokenClassApi, UserClassApi $userClassApi, OnDemandClassApi $onDemandClassApi, AdminClass $adminClass,NotificationClass $notificationClass)
    {
        $this->userClassApi = $userClassApi;
        $this->onDemandClassApi = $onDemandClassApi;
        $this->tokenClassApi = $tokenClassApi;
        $this->adminClass = $adminClass;
        $this->notificationClass = $notificationClass;

        $service_cat = ServiceCategory::query()->whereIN('category_type', [3, 4])->where('status', 1)->get()->pluck('id')->toArray();
        $this->on_demand_service_id_array = $service_cat;
    }
    public function postCustomerRegister(Request $request)
    {
        $check_authentication = (new AuthAlertClass())->checkAuthorizationApp($request);
        if ($check_authentication->getData()->status != 1){
            return $check_authentication;
        }
        $validator = Validator::make($request->all(), [
//            "email" => "required|email|unique:users,email",
            "email" => [
                "required",
                "email",
                Rule::unique('users','email')->where(function ($query) use ($request) {
                    $query->where('email', '=', $request->get('email'));
                    $query->where('deleted_at', '=', null);
                }),
            ],
            "password" => "required",
            "full_name" => "required",
            "last_name" => "nullable",
            "contact_number" => "required|numeric",
            "device_token" => "required",
            "select_language" => "required",
            "select_country_code" => "required",
            "select_currency" => "required",
            "login_device" => "nullable|in:1,2,3,4",
            "refer_code" => 'nullable',
            //"gender" => "required|in:1,2"
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
            //if (isset($failedRules['contact_number']['Unique'])) {
            //    return response()->json([
            //        "status" => 0,
            //        "message" => $validator->errors()->first(),
            //        "message_code" => 12,
            //    ]);
            //}
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $check_exist_number = User::query()->where('contact_number',$request->get('contact_number'))->where('country_code',$request->get('select_country_code'))->whereNull('users.deleted_at')->first();
        if($check_exist_number != Null){
            return response()->json([
                "status" => 0,
                //"message" => "Contact Number Already Exist",
                'message' => __('user_messages.12'),
                "message_code" => 12,
            ]);
        }
        $user = new User();
        $user->first_name = ucwords(strtolower(trim($request->get('full_name'))));
        //$user->last_name = ucwords(strtolower(trim($request->get('last_name'))));
        $user->email = $request->get('email');
        $user->contact_number = $request->get('contact_number');
        //$user->gender = $request->get('gender');
        $user->login_type = "email";
        $user->password = Hash::make($request->get('password'));
        $user->country_code = $request->get('select_country_code');
        $user->currency = $request->get('select_currency');
        $user->language = $request->get('select_language');
        $user->device_token = $request->get('device_token');
        $user->login_device = $request->get('login_device') != Null ? $request->get('login_device') : 1;
        $user->status = 1;
        $user->time_zone = $request->header('select-time-zone')!= Null ? $request->header('select-time-zone') : Null;
        $user->login_device = $request->get('login_device') != Null ? $request->get('login_device') : 0;
        $user->save();

        $general_settings = request()->get("general_settings");
        if ($general_settings != Null) {
            if (trim($request->get('refer_code')) != Null) {
                $refer_code = strtoupper(trim($request->get('refer_code')));
                $find_refer = User::query()->where('invite_code', $refer_code)->first();
                if ($find_refer != Null) {
                    $used_user_discount = $general_settings->used_user_discount;
                    $used_user_discount_type = $general_settings->used_user_discount_type;
                    $refer_user_discount = $general_settings->refer_user_discount;
                    $refer_user_discount_type = $general_settings->refer_user_discount_type;
                    if ($used_user_discount != 0 || $refer_user_discount != 0) {
                        $refer_history = new UserReferHistory();
                        $refer_history->user_id = $user->id;
                        //$refer_history->user_id = 13;
                        $refer_history->refer_id = $find_refer->id;
                        $refer_history->user_discount = $used_user_discount;
                        $refer_history->user_discount_type = $used_user_discount_type;
                        $refer_history->refer_discount = $refer_user_discount;
                        $refer_history->refer_discount_type = $refer_user_discount_type;
                        $refer_history->user_status = 0;
                        $refer_history->refer_status = 0;
                        $refer_history->save();
                        $find_refer->pending_refer_discount = $find_refer->pending_refer_discount + 1;
                        $find_refer->save();
                        $user->pending_refer_discount = $user->pending_refer_discount + 1;
                        $user->save();
                    }
                }
            }
        }
        $user->generateAccessToken($user->id);
        $user->InviteCode($user->id, $user->first_name);
        $this->tokenClassApi->sendUserSmsVerification($user->id);

        if ($general_settings !=  Null){
            if ($general_settings->send_mail == 1) {
                $notificationClass = new NotificationClass();
                try {
                    $mail_type = "customer_signup";
                    $to_mail = $user->email;
                    $subject = "Welcome to " . $general_settings->mail_site_name;
                    $disp_data = array("##user_name##" => $user->first_name );
                    $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                } catch (\Exception $e) {}
                if ($general_settings->send_receive_email != Null ){
                    try {
                        $mail_type = "admin_new_user_signup";
                        $to_mail = $general_settings->send_receive_email;
                        $subject = "New User has Register";
                        $disp_data = array("##user_name##" => $user->first_name , "##mail_site_name##" => $general_settings->mail_site_name);
                        $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                    } catch (\Exception $e) {}
                }
            }
        }
        return $this->userClassApi->userLoginRegisterUpdateDetails($user);
    }

    public function postOnDemandRegister(Request $request)
    {
        $check_authentication = (new AuthAlertClass())->checkAuthorizationApp($request);
        if ($check_authentication->getData()->status != 1){
            return $check_authentication;
        }
        $validator = Validator::make($request->all(), [
//                "email" => "required|email|unique:providers,email",
                "email" => [
                    "required",
                    "email",
                    Rule::unique('providers')->where(function ($query) use ($request) {
                        $query->where('email', '=', $request->get('email'));
                        $query->where('deleted_at', '=', null);
                    }),
                ],
                "password" => "required",
                "full_name" => "required",
//                "last_name" => "required",
                "gender" => "required|in:1,2",
                "contact_number" => [
                    "required",
                    "numeric",
                    Rule::unique('providers')->where(function ($query) use ($request) {
                        $query->where('contact_number', '=', $request->get('contact_number'));
                        $query->where('country_code', '=', $request->get('contact_number'));
                        $query->where('id', '!=', $request->get('provider_id'));
                        $query->where('deleted_at', '=', null);
                    }),
                ],
                "device_token" => "required",
                'select_language' => "required",
                'select_country_code' => "required",
                'select_currency' => "required",
                'login_device' => "nullable|in:1,2,3,4"
            ]
        );
        if ($validator->fails()) {
            $failedRules = $validator->failed();
            if (isset($failedRules['email']['Unique'])) {
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.11'),
                    "message_code" => 11,
                ]);
            }
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

        $check_exist_number = Provider::query()->where('contact_number',$request->get('contact_number'))->where('country_code',$request->get('select_country_code'))->whereNull('providers.deleted_at')->first();

        if($check_exist_number != Null){
            return response()->json([
                "status" => 0,
//                "message" => "Contact Number Already Exist",
                "message" =>  __('provider_messages.12'),
                "message_code" => 12,
            ]);
        }
        $provider_details = new Provider();
        $provider_details->first_name = ucwords(strtolower(trim($request->get('full_name'))));
        $provider_details->last_name = "";
        $provider_details->email = $request->get('email');
        $provider_details->contact_number = $request->get('contact_number');
        $provider_details->provider_type = 3;
        $provider_details->login_type = "email";
        $provider_details->gender = $request->get('gender');
        $provider_details->password = Hash::make($request->get('password'));
        $provider_details->last_active = date('Y-m-d h:i:s');
        $provider_details->device_token = $request->get('device_token');
        $provider_details->status = 3;
        $provider_details->country_code = $request->get('select_country_code');
        $provider_details->currency = $request->get('select_currency');
        $provider_details->language = $request->get('select_language');
        $provider_details->login_device = $request->get('login_device') != Null ? $request->get('login_device') : 1;
        $provider_details->ip_address = $request->header('select-ip-address')!= Null ? $request->header('select-ip-address') : Null;
        $provider_details->time_zone = $request->header('select-time-zone')!= Null ? $request->header('select-time-zone') : Null;
        $provider_details->save();

        $provider_details->generateAccessToken($provider_details->id);
        $this->tokenClassApi->sendProviderSmsVerification($provider_details->id);

        $general_setting = request()->get("general_settings");
        if ($general_setting !=  Null) {
            if ($general_setting->send_mail == 1) {
                $notificationClass = new NotificationClass();
                try {
                    $mail_type = "provider_signup";
                    $to_mail = $provider_details->email;
                    $subject = "Welcome to " . $general_setting->mail_site_name;
                    $disp_data = array("##provider_name##" => $provider_details->first_name);
                    $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                } catch (\Exception $e) {}
            }
        }
        return $this->onDemandClassApi->onDemandLoginRegisterResponse($provider_details);
    }

}
