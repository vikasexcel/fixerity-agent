<?php

namespace App\Http\Controllers\Api\Auth;

use App\Classes\OnDemandClassApi;
use App\Classes\TokenClassApi;
use App\Classes\UserClassApi;
use App\Models\GeneralSettings;
use App\Models\Provider;
use app\Models\ProviderVerification;
use App\Models\User;
use app\Models\UserVerification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client;

class ResetPasswordController extends Controller
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
    private $onDemandClassApi;
    private $tokenClassApi;

    public function __construct(TokenClassApi $tokenClassApi, UserClassApi $userClassapi, OnDemandClassApi $onDemandClassApi)
    {
        $this->userClassapi = $userClassapi;
        $this->tokenClassApi = $tokenClassApi;
        $this->onDemandClassApi = $onDemandClassApi;
    }

    public function postCustomerChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
                "access_token" => "required",
                "old_password" => "required",
                "new_password" => "required"
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 0,
            ]);
        }

        $user_details = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($user_details) == false) {
            return $user_details;
        }
        if ($user_details->login_type != "facebook" && $user_details->login_type != "google") {
            if (Hash::check($request->get('old_password'), $user_details->password)) {
                $user_details->password = Hash::make($request->get('new_password'));
                $user_details->save();
                return response()->json([
                    'status' => 1,
//                    'message' => "Your Password Changed Successfully!",
                    'message' => __('user_messages.1'),
                    "message_code" => 1,
                ]);
            } else {
                return response()->json([
                    'status' => 0,
//                    'message' => "You have entered an incorrect password",
                    'message' => __('user_messages.10'),
                    "message_code" => 10,
                ]);
            }
        } else {
            return response()->json([
                'status' => 0,
//                'message' => "login with social parameters",
                'message' => __('user_messages.13'),
                "message_code" => 13,
            ]);
        }
    }

    public function postProviderChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
                "provider_id" => "required|integer",
                "access_token" => "required",
                "old_password" => "required",
                "new_password" => "required"
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = Provider::query()->where('id', $request->get('provider_id'))->whereNull('providers.deleted_at')->first();
        if ($provider_details != Null) {
            if ($provider_details->login_type != "facebook" && $provider_details->login_type != "google") {
                if (Hash::check($request->get('old_password'), $provider_details->password)) {
                    $provider_details->password = Hash::make($request->get('new_password'));
                    $provider_details->save();
                    return response()->json([
                        'status' => 1,
//                        'message' => "Your Password Changed Successfully!",
                        'message' =>  __('provider_messages.1'),
                        "message_code" => 1,
                    ]);
                } else {
                    return response()->json([
                        'status' => 0,
//                        'message' => "You have entered an incorrect password",
                        'message' => __('provider_messages.10'),
                        "message_code" => 10,
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 0,
//                    'message' => "login with social parameters",
                    'message' => __('provider_messages.13'),
                    "message_code" => 13,
                ]);
            }
        } else {
            return response()->json([
                'status' => 5,
//                'message' => "store not found!",
                'message' => __('provider_messages.5'),
                "message_code" => 5,
            ]);
        }
    }


    //forgot password customer
    public function postCustomerForgotPasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
                "contact_number" => "required|numeric",
                'select_country_code' =>"required"
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $get_user = User::query()->where('contact_number', $request->get('contact_number'))->where('country_code', $request->get('select_country_code'))->whereNull('users.deleted_at')->first();
        if ($get_user == Null) {
            return response()->json([
                "status" => 0,
//                "message" => "Not Registered.",
                'message' => __('user_messages.5'),
                "message_code" => 5,
            ]);
        }
        if ($get_user->status == 0) {
            return response()->json([
                "status" => 0,
//                "message" => "User blocked.",
                'message' => __('user_messages.3'),
                "message_code" => 3,
            ]);
        } elseif ($get_user->status == 2) {
            return response()->json([
                "status" => 0,
//                "message" => "User registration pending.",
                'message' => __('user_messages.88'),
                "message_code" => 88,
            ]);
        } elseif ($get_user->status == 1) {
            $this->tokenClassApi->sendUserSmsVerification($get_user->id);
            return response()->json([
                "status" => 1,
//                "message" => "OTP has been sent to your registered Contact Number!",
                'message' => __('user_messages.1'),
                "user_id" => $get_user->id,
                "message_code" => 1,
            ]);
        } else {
            return response()->json([
                "status" => 0,
//                "message" => "something went to wrong.",
                'message' => __('user_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function postCustomerForgotChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required|integer",
            "otp" => "required|numeric|digits:4",
            "new_password" => "required",
            "login_device" => "required|in:1,2,3,4"
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

        $get_user = User::query()->where('id', $request->get('user_id'))->whereNull('users.deleted_at')->first();
        if ($get_user != Null) {
            if ($get_user->status == 0) {
                return response()->json([
                    "status" => 0,
//                    "message" => "User blocked.",
                    'message' => __('user_messages.3'),
                    "message_code" => 3,
                ]);
            } elseif ($get_user->status == 2) {
                return response()->json([
                    "status" => 0,
//                    "message" => "User registration pending.",
                    'message' => __('user_messages.88'),
                    "message_code" => 88,
                ]);
            } elseif ($get_user->status == 1) {
                $settings = GeneralSettings::first();
                if ($settings == Null || $settings->twilio_service_key == Null || $settings->twilio_auth_token == Null || $settings->twilio_verify_service_key == Null) {
                    return response()->json([
                        "status" => 0,
                        "message" => "something went to wrong!",
                        "message_code" => 9,
                    ]);
                }
                // Verify if OTP verification is enabled and user is allowed
                if (($settings->is_otp_verification != Null && $settings->is_otp_verification == 1) && $get_user->is_default_user == 0 && $get_user->fix_user_show == 0) {
                    if (isset($settings->otp_method)) {
                        // Check OTP method (Twilio)
                        if ($settings->otp_method == 1) {
                           $get_otp = UserVerification::where('user_id', $get_user->id)->first();
                           if ($get_otp == Null) {
                               return response()->json([
                                   "status" => 0,
                                   "message" => "something went to wrong.",
                                   "message_code" => 9,
                               ]);
                           }
                           try {
                               $twilio = new Client($settings->twilio_service_key, $settings->twilio_auth_token);
                               $option = [
                                   'To' => $get_user->country_code.$get_user->contact_number,
                                   'VerificationSid' => $get_otp->token,
                                   'Code' => $request->get('otp'),
                               ];
                               $verification_check = $twilio->verify->v2->services($settings->twilio_verify_service_key)->verificationChecks->create($option);
                               if ($verification_check->status == "approved") {
                                   $verification_sid = $verification_check->sid;
                                   if ($verification_sid != $get_otp->token) {
                                       return response()->json([
                                           "status" => 0,
                                           "message" => "something went to wrong!",
                                           "message_code" => 9,
                                       ]);
                                   }
                                   UserVerification::where('user_id', $get_user->id)->delete();
                                   $get_user->login_device = $request->get('login_device');
                                   $get_user->password = Hash::make($request->get('new_password'));
                                   $get_user->save();
                                   $get_user->generateAccessToken($get_user->id);
                                   return $this->userClassapi->userLoginRegisterUpdateDetails($get_user);
                               } else {
                                   return response()->json([
                                       "status" => 0,
                                       "message" => "Entered OTP is Incorrect!",
                                       "message_code" => 89,
                                   ]);
                               }
                           } catch (\Exception $e) {
                               return response()->json([
                                   "status" => 0,
                                   "message" => "Entered OTP is Incorrect!",
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
                       $get_user->login_device = $request->get('login_device');
                       $get_user->password = Hash::make($request->get('new_password'));
                       $get_user->save();

                       $get_user->generateAccessToken($get_user->id);

                       return $this->userClassapi->userLoginRegisterUpdateDetails($get_user);
                   }else{
                       return response()->json([
                           "status" => 0,
                           "message" => __('user_messages.89'),
                           "message_code" => 89,
                       ]);
                   }
               }
           } else {
               return response()->json([
                   "status" => 0,
//                    "message" => "something went to wrong.",
                   'message' => __('user_messages.9'),
                   "message_code" => 9,
               ]);
           }
       } else {
           return response()->json([
               "status" => 0,
//                "message" => 'user not found',
               'message' => __('user_messages.5'),
               "message_code" => 5,
           ]);
       }
   }

   //forgot password On-demand Provider
   public function postOnDemandForgotPasswordRequest(Request $request)
   {
       $validator = Validator::make($request->all(), [
               "contact_number" => "required|numeric",
               'select_country_code' =>"required"
           ]
       );
       if ($validator->fails()) {
           return response()->json([
               "status" => 0,
               "message" => $validator->errors()->first(),
               "message_code" => 9,
           ]);
       }
       $get_provider = Provider::query()->select('providers.*')
           ->join('provider_services','provider_services.provider_id','=','providers.id')
           ->where('provider_services.service_cat_id', '>', 10)
           ->where('providers.contact_number', $request->get('contact_number'))
           ->where('providers.country_code', $request->get('select_country_code'))
           ->where('providers.provider_type', '=',3)
           ->whereNull('providers.deleted_at')
           ->first();
       if ($get_provider == Null) {
//            return response()->json([
//                "status" => 0,
//                "message" => "Not Registered.",
//                "message_code" => 5,
//            ]);
           return response()->json([
               'status' => 0,
//                'message' => 'App User Not Found',
               'message' => __('provider_messages.5'),
               "message_code" => 5
           ]);
       }
       if ($get_provider->status == 2) {
           return response()->json([
               "status" => 3,
//                "message" => "Store blocked.",
               "message" => __('provider_messages.3'),
               "message_code" => 3,
           ]);
       } else {
           $this->tokenClassApi->sendProviderSmsVerification($get_provider->id);
           return response()->json([
               "status" => 1,
//                "message" => "OTP has been sent to your registered Contact Number!",
               "message" => __('provider_messages.1'),
               "provider_id" => $get_provider->id,
               "message_code" => 1,
           ]);
       }
   }

   public function postOnDemandForgotChangePassword(Request $request)
   {
       $validator = Validator::make($request->all(), [
               "provider_id" => "required|integer",
               "otp" => "required|numeric|digits:4",
               "new_password" => "required",
               "login_device" => "required|in:1,2,3,4"
           ]
       );
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
       $get_provider = Provider::query()->where('id', $request->get('provider_id'))->whereNull('providers.deleted_at')->first();
       if ($get_provider != Null) {
           if ($get_provider->status == 2) {
               return response()->json([
                   "status" => 0,
//                    "message" => "Provider blocked.",
                   "message" => __('provider_messages.3'),
                   "message_code" => 3,
               ]);
           }
           $settings = GeneralSettings::first();
           if ($settings == Null || $settings->twilio_service_key == Null || $settings->twilio_auth_token == Null || $settings->twilio_verify_service_key == Null) {
               return response()->json([
                   "status" => 0,
                   "message" => "something went to wrong!",
                   "message_code" => 9,
               ]);
           }
           // Verify if OTP verification is enabled and user is allowed
           if (($settings->is_otp_verification != Null && $settings->is_otp_verification == 1) && $get_provider->is_default_user == 0 && $get_provider->fix_user_show == 0) {
               if (isset($settings->otp_method)) {
                   // Check OTP method (Twilio)
                   if ($settings->otp_method == 1) {
                       $get_otp = ProviderVerification::where('provider_id', $get_provider->id)->first();
                       if ($get_otp == Null) {
                           return response()->json([
                               "status" => 0,
                               "message" => "something went to wrong.",
                               "message_code" => 9,
                           ]);
                       }
                       try {
                           $twilio = new Client($settings->twilio_service_key, $settings->twilio_auth_token);
                           $option = [
                               'To' => $get_provider->country_code.$get_provider->contact_number,
                               'VerificationSid' => $get_otp->token,
                               'Code' => $request->get('otp'),
                           ];
                           $verification_check = $twilio->verify->v2->services($settings->twilio_verify_service_key)->verificationChecks->create($option);
                           if ($verification_check->status == "approved") {
                               $verification_sid = $verification_check->sid;
                               if ($verification_sid != $get_otp->token) {
                                   return response()->json([
                                       "status" => 0,
                                       "message" => "something went to wrong!",
                                       "message_code" => 9,
                                   ]);
                               }
                               ProviderVerification::where('provider_id', $get_provider->id)->delete();
                               $get_provider->password = Hash::make($request->get('new_password'));
                               $get_provider->login_device = $request->get('login_device');
                               $get_provider->save();
                               $get_provider->generateAccessToken($get_provider->id);
                               return $this->onDemandClassApi->onDemandLoginRegisterResponse($get_provider);
                           } else {
                               return response()->json([
                                   "status" => 0,
                                   "message" => "Entered OTP is Incorrect!",
                                   "message_code" => 89,
                               ]);
                           }
                       } catch (\Exception $e) {
                           return response()->json([
                               "status" => 0,
                               "message" => "Entered OTP is Incorrect!",
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
                   $get_provider->login_device = $request->get('login_device');
                   $get_provider->password = Hash::make($request->get('new_password'));
                   $get_provider->save();
                   $get_provider->generateAccessToken($get_provider->id);
                   return $this->onDemandClassApi->onDemandLoginRegisterResponse($get_provider);
               }
               else {
                   return response()->json([
                       "status" => 0,
//                    "message" => "Invalid Otp!",
                       "message" => __('provider_messages.89'),
                       "message_code" => 89,
                   ]);
               }
           }
        } else {
            return response()->json([
                "status" => 0,
//                "message" => 'user not found',
                "message" => __('provider_messages.5'),
                "message_code" => 5,
            ]);
        }
    }


}
