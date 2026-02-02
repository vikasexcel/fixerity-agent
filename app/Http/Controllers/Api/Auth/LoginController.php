<?php

namespace App\Http\Controllers\Api\Auth;

use App\Classes\AuthAlertClass;
use App\Classes\NotificationClass;
use App\Classes\OnDemandClassApi;
use App\Classes\TokenClassApi;
use App\Classes\UserClassApi;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    //json response status [
    //    0 => false,
    //    1 => true,
    //    2 => registration pending,
    //    3 => app user blocked,
    //    4 => app user access token not match,
    //    5 => app user not found
    //  ]

    //ApiLogDetail logger type => 0:user,1:store,2:driver,3:provider
    private $userClassApi;
    Private $onDemandClassApi;
    Private $tokenClassApi;
    private $notificationClass;

    public function __construct(TokenClassApi $tokenClassApi, UserClassApi $userClassApi, OnDemandClassApi $onDemandClassApi, NotificationClass $notificationClass)
    {
        $this->userClassApi = $userClassApi;
        $this->onDemandClassApi = $onDemandClassApi;
        $this->tokenClassApi = $tokenClassApi;
        $this->notificationClass = $notificationClass;
    }

    //customer login api
    public function postCustomerLogin(Request $request)
    {
        $check_authentication = (new AuthAlertClass())->checkAuthorizationApp($request);
        if ($check_authentication->getData()->status != 1){
            return $check_authentication;
        }
        $validator = Validator::make($request->header(), [
            'select-time-zone' => 'required',
            'select-ip-address' => 'nullable',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'message_code' => 9,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'login_type' => 'required|in:facebook,google,email,apple',
            'device_token' => 'required',
            'select_language' => "required",
            'select_country_code' => "nullable",
            'select_currency' => "required",
            'login_device' => "nullable|in:1,2,3,4"
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $login_type = $request->get('login_type');
        if ($login_type != "facebook" && $login_type != "google" && $login_type != "apple") {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
                'select_country_code' => "required"
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }
            if (is_numeric($request->get('email'))) {
                $user_details = User::query()->where('contact_number', $request->get('email'))->where('country_code', $request->get('select_country_code'))->whereNull('users.deleted_at')->first();
            } else {
                $user_details = User::query()->where('email', $request->get('email'))->whereNull('users.deleted_at')->first();
            }
            if ($user_details != Null) {
                if ($user_details->status == 0) {
                    return response()->json([
                        'status' => 3,
//                        'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                        'message' => __('user_messages.3'),
                        "message_code" => 3,
                    ]);
                }
                if( $user_details->password != null ){
                    if (Hash::check($request->get('password'), $user_details->password)) {
                        $user_details->country_code = $request->get('select_country_code');
                        $user_details->currency = $request->get('select_currency');
                        $user_details->language = $request->get('select_language');
                        $user_details->device_token = $request->get('device_token');
                        $user_details->login_device = $request->get('login_device') != Null ? $request->get('login_device') : 0;
                        $user_details->time_zone = $request->header('select-time-zone')!= Null ? $request->header('select-time-zone') : Null;
                        $user_details->login_device = $request->get('login_device') != Null ? $request->get('login_device') : 0;
                        $user_details->save();
                        $user_details->generateAccessToken($user_details->id);
                        if ($user_details->avatar != Null) {
                            if (filter_var($user_details->avatar, FILTER_VALIDATE_URL) == true) {
                                $avatar = $user_details->avatar;
                            } else {
                                $avatar = url('/assets/images/profile-images/customer/' . $user_details->avatar);
                            }
                        } else {
                            $avatar = "";
                        }
                        return $this->userClassApi->userLoginRegisterUpdateDetails($user_details);
                    } else {
                        return response()->json([
                            'status' => 0,
    //                        'message' => 'You have entered an incorrect password',
                            'message' => __('user_messages.10'),
                            "message_code" => 10,
                        ]);
                    }
                } else {
                    $user_details->password = Hash::make($request->get('password'));
                    $user_details->save();
                }
            } else {
                return response()->json([
                    'status' => 5,
//                    'message' => 'User Not Found!',
                    'message' => __('user_messages.5'),
                    "message_code" => 5,
                ]);
            }
        }
        //social login
        else {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'email' => 'nullable|email',
                'login_id' => 'required',
                'profile_image' => 'nullable',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }
            $user_details = User::query()->where('login_type', $request->get('login_type'))->where('login_id', $request->get('login_id'))->whereNull('deleted_at')->first();
            if ($user_details == Null) {
                $contact_number = $request->get("email");
                $is_update = 0;
                $user_id = 0;
                if ($contact_number != Null){
                    if (!is_numeric($contact_number)) {
                        $check_email = User::query()->where('email', $contact_number)->whereNull('deleted_at')->first();
                        if ($check_email != Null){
                            if ($check_email->login_type != "email"){
                                return response()->json([
                                    'status' => 0,
                                    //'message' => 'Email Already Exist!',
                                    'message' => __('user_messages.11'),
                                    'message_code' => 11,
                                ]);
                            } else {
                                $is_update = 1;
                                $user_id = $check_email->id;
                            }
                        }
                    }
                }
                $is_new_or_not = 0;
                if ($is_update == 1){
                    $user_details = User::query()->where('id','=', $user_id)->whereNull('deleted_at')->first();

                    if ($user_details == Null){
                        $user_details = new User();
                        $is_new_or_not = 1;
                    }
                    if ($user_details->status == 0) {
                        return response()->json([
                            'status' => 3,
                            //'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                            'message' => __('user_messages.3'),
                            'message_code' => 3,
                        ]);
                    }
                } else{
                    $user_details = new User();
                    $is_new_or_not = 1;
                }

                if ($user_id == 0){
                    $user_details->login_type = $request->get('login_type');
                }
                $user_details->login_id = $request->get('login_id');
                $user_details->verified_at = date('Y-m-d H:i:s');

//                $check_email = User::query()->select('id')->where('email', $request->get('email'))->whereNull('users.deleted_at')->first();
//                if ($check_email == Null) {
//                    $user_details = new User();

                if ($is_new_or_not == 1) {
                    $user_details->status = 1;

                    if (filter_var($request->get('profile_image'), FILTER_VALIDATE_URL) == true) {
                        $user_details->avatar = $request->get('profile_image');
                    }
                    if(trim($request->get("full_name")) != "N/A")
                    {
                        $user_details->first_name = ucwords(strtolower(trim($request->get('full_name'))));
                    }
                    if ($request->get('email') != Null) {
                        $user_details->email = $request->get('email');
                    }
                    if ($request->get("contact_number") != Null) {
                        if (is_numeric($request->get("contact_number"))) {
                            $user_details->contact_number = $request->get("contact_number");
                        } else {
                            $user_details->email = $contact_number;
                        }
                    }
                }

                $user_details->save();
                //sending mail
                if (request()->get("general_settings") != Null){
                    if (request()->get("general_settings")->send_mail == 1) {
                        $notificationClass = new NotificationClass();
                        if($user_details->email != null){
                            try {
                                $mail_type = "customer_signup";
                                $to_mail = $user_details->email;
                                $subject = "Welcome to " . request()->get("general_settings")->mail_site_name;
                                $disp_data = array("##user_name##" => $user_details->first_name );
                                $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                            } catch (\Exception $e) {}
                        }
                        if (request()->get("general_settings")->send_receive_email != Null ){
                            try {
                                $mail_type = "admin_new_user_signup";
                                $to_mail = request()->get("general_settings")->send_receive_email;
                                $subject = "New User has Register";
                                $disp_data = array("##user_name##" => $user_details->first_name , "##mail_site_name##" => request()->get("general_settings")->mail_site_name);
                                $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                            } catch (\Exception $e) {}
                        }
                    }
                }

                if ($is_new_or_not == 1) {
                    if ($user_details->first_name != Null) {
                        $user_details->InviteCode($user_details->id, $user_details->first_name);
                    }
                }

//                    if ($request->get('login_device') != Null && $request->get('login_device') == 2) {
//                        if ($login_type == "apple") {
//                            $user_details->contact_number = "+" . random_int(1, 99) . date('siHd') . random_int(1, 9);
////                            $user_details->verified_at = date('Y-m-d H:i:s');
////                            $user_details->email = "demo" . random_int(1, 9999) . "@gmail.com";
//                        }
//                    }
//                }
//                else {
//                    return response()->json([
//                        'status' => 0,
////                        'message' => 'Email Already Exist!',
//                        'message' => __('user_messages.11'),
//                        "message_code" => 11,
//                    ]);
//                }
            }
            else {
                if ($user_details->status == 0) {
                    return response()->json([
                        'status' => 3,
//                        'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                        'message' => __('user_messages.3'),
                        "message_code" => 3,
                    ]);
                }
            }
            if ($request->get('select_country_code') != Null){
                $user_details->country_code = $request->get('select_country_code');
            }
            $user_details->currency = $request->get('select_currency');
            $user_details->language = $request->get('select_language');
            $user_details->device_token = $request->get('device_token');
            $user_details->login_device = $request->get('login_device') != Null ? $request->get('login_device') : 0;
            $user_details->time_zone = $request->header('select-time-zone')!= Null ? $request->header('select-time-zone') : Null;
            $user_details->save();

            $user_details->generateAccessToken($user_details->id);
            if ($user_details->avatar != Null) {
                if (filter_var($user_details->avatar, FILTER_VALIDATE_URL) == true) {
                    $avatar = $user_details->avatar;
                } else {
                    $avatar = url('/assets/images/profile-images/customer/' . $user_details->avatar);
                }
            } else {
                $avatar = "";
            }
            return $this->userClassApi->userLoginRegisterUpdateDetails($user_details);
        }
    }

    public function postOnDemandLogin(Request $request)
    {
        $check_authentication = (new AuthAlertClass())->checkAuthorizationApp($request);
        if ($check_authentication->getData()->status != 1){
            return $check_authentication;
        }
        $this->notificationClass->ApiLogDetail($logger_type = 3, 0, "postOnDemandLogin", $request->all());

        $validator = Validator::make($request->header(), [
            'select-time-zone' => 'required',
            'select-ip-address' => 'nullable',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'message_code' => 9,
            ]);
        }
        $validator = Validator::make($request->all(), [
            'login_type' => 'required|in:facebook,google,email,apple',
//            'device_token' => 'required',
            'select_language' => "required",
            'select_country_code' => "nullable",
            'select_currency' => "required",
            'login_device' => "nullable|in:1,2,3,4"
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $login_type = $request->get('login_type');
        if ($login_type != "facebook" && $login_type != "google" && $login_type != "apple") {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
                'select_country_code' => "required"
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            if (is_numeric($request->get('email'))) {
                $provider_details = Provider::query()->where('provider_type','=',3)->where('contact_number', $request->get('email'))->where('country_code', $request->get('select_country_code'))->whereNull('providers.deleted_at')->first();
            } else {
                $provider_details = Provider::query()->where('provider_type','=',3)->where('email', $request->get('email'))->whereNull('providers.deleted_at')->first();
            }
            if ($provider_details != Null) {
                if ($provider_details->status == 2) {
                    return response()->json([
                        'status' => 3,
//                        'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                        'message' =>  __('provider_messages.3'),
                        "message_code" => 3,
                    ]);
                }
                if($provider_details->password != NULL){
                    if (Hash::check($request->get('password'), $provider_details->password)) {
                        //$provider_details->last_active = date('Y-m-d h:i:s');
                        //$provider_details->device_token = $request->get('device_token');
                        //$provider_details->country_code = $request->get('select_country_code');
                        //$provider_details->currency = $request->get('select_currency');
                        //$provider_details->language = $request->get('select_language');
                        //$provider_details->login_device = $request->get('login_device') != Null ? $request->get('login_device') : 1;
                        //$provider_details->save();
                        //$provider_details->generateAccessToken($provider_details->id);
                        return $this->onDemandClassApi->onDemandLoginRegisterResponse($provider_details, $request_type =1 ,$request);
                    } else {
                        return response()->json([
                            'status' => 0,
    //                        'message' => 'You have entered an incorrect password',
                            'message' => __('provider_messages.10'),
                            "message_code" => 10,
                        ]);
                    }
                } else {
                    $provider_details->password = Hash::make($request->get('password'));
                    $provider_details->save();
                }
            } else {
                return response()->json([
                    'status' => 5,
//                    'message' => 'User Not Found!',
                    'message' => __('provider_messages.5'),
                    "message_code" => 5,
                ]);
            }
        } else {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'email' => 'nullable|email',
                'login_id' => 'required',
                'profile_image' => 'nullable',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }
            $provider_details = Provider::query()->where('provider_type','=',3)->where('login_type', $request->get('login_type'))->where('login_id', $request->get('login_id'))->whereNull('deleted_at')->first();
            if ($provider_details == Null) {
                $contact_number = $request->get("email");
                $is_update = 0;
                $provider_id = 0;
                if($contact_number != Null){
                    if(!is_numeric($contact_number)) {
                        $check_email = Provider::query()->where('email', $contact_number)->where('provider_type','=',3)->whereNull('deleted_at')->first();
                        if ($check_email != Null){
                            if ($check_email->login_type != "email"){
                                return response()->json([
                                    'status' => 0,
                                    //'message' => 'Email Already Exist!',
                                    'message' => __('provider_messages.11'),
                                    'message_code' => 11,
                                ]);
                            } else {
                                $is_update = 1;
                                $provider_id = $check_email->id;
                            }
                        }
                    }
                }
                $is_new_or_not = 0;
                if ($is_update == 1){
                    $provider_details = Provider::query()->where('id','=', $provider_id)->where('provider_type','=',3)->whereNull('deleted_at')->first();
                    if ($provider_details == Null){
                        $provider_details = new Provider();
                        $is_new_or_not = 1;
                    }
                    if ($provider_details->status == 2) {
                        return response()->json([
                            'status' => 3,
                            //'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                            'message' =>  __('provider_messages.3'),
                            'message_code' => 3,
                        ]);
                    }
                } else{
                    $provider_details = new Provider();
                    $provider_details->login_id = $request->get('login_id');
                    $is_new_or_not = 1;
                }
                if ($provider_id == 0){
                    $provider_details->login_type = $request->get('login_type');
                }
                $provider_details->provider_type = 3;
                $provider_details->verified_at = date('Y-m-d h:i:s');

                if ($is_new_or_not == 1) {
                    if (trim($request->get("full_name")) != "N/A") {
                        $provider_details->first_name = ucwords(strtolower(trim($request->get('full_name'))));
                    }
                    if (filter_var($request->get('profile_image'), FILTER_VALIDATE_URL) == true) {
                        $provider_details->avatar = $request->get('profile_image');
                    }
                    if ($request->get('email') != Null) {
                        $provider_details->email = $request->get('email');
                    }
//                    $provider_details->web_verified_at = date('Y-m-d h:i:s');
                    $provider_details->provider_type = 3;
                    $provider_details->status = 3;
                    $provider_details->save();
                }
                $provider_details->currency = $request->get('select_currency');
                $provider_details->language = $request->get('select_language');
                $provider_details->login_device = $request->get('login_device') != Null ? $request->get('login_device') : 0;
                $provider_details->ip_address = $request->header('select-ip-address') != Null ? $request->header('select-ip-address') : Null;
                $provider_details->time_zone = $request->header('select-time-zone') != Null ? $request->header('select-time-zone') : Null;
                $provider_details->device_token = $request->get('device_token');
                $provider_details->last_active = date('Y-m-d h:i:s');
                $provider_details->save();

                $general_settings = request()->get("general_settings");
                if ($general_settings != Null) {
                    if ($general_settings->send_mail == 1) {
                        // admin mail
                        $notificationClass = New NotificationClass();
                        try {
                            if ($general_settings->send_receive_email != Null) {
                                $mail_type = "admin_new_provider_signup";
                                $to_mail = $general_settings->send_receive_email;
                                $provider_email = $provider_details->email;
                                $provider_contact_number = $provider_details->country_code.$provider_details->contact_number;
                                $provider_name = ucwords($provider_details->first_name);
                                $subject = "New Provider Registered";
                                $disp_data = array("##provider_name##" => $provider_name, "##email##" => $provider_email, "##contact_no##" => $provider_contact_number);
                                $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                            }
                        } catch (\Exception $e) {
                        }

                        // provider mail
                        if($provider_details->email != null){
                            try {
                                $mail_type = "provider_signup";
                                $to_mail = $provider_details->email;
                                $subject = "Welcome to " . $general_settings->mail_site_name;
                                $disp_data = array("##provider_name##" => $provider_details->first_name);
                                $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                            } catch (\Exception $e) {}
                        }
                    }
                }
            } else {
                if ($provider_details->status == 2) {
                    return response()->json([
                        'status' => 3,
//                        'message' => 'Your account is currently blocked, so not authorised to allow any activity!',
                        'message' =>  __('provider_messages.3'),
                        "message_code" => 3,
                    ]);
                }
            }
            //$provider_details->generateAccessToken($provider_details->id);
            return $this->onDemandClassApi->onDemandLoginRegisterResponse($provider_details, $request_type =1 ,$request);
            //return $this->onDemandClassApi->onDemandLoginRegisterResponse($provider_details);
        }
    }

}
