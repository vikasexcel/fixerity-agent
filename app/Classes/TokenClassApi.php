<?php
/**
 * Created by PhpStorm.
 * User: Froyo Khyati
 * Date: 27-May-19
 * Time: 10:43 AM
 */

namespace App\Classes;

use App\Models\{GeneralSettings,Provider,User,ProviderVerification,UserVerification};
use Twilio\Rest\Client;
use Exception;

class TokenClassApi
{
    /*-----------------------------SendUserSmsVerification Code------------------------------------------*/
    public function sendUserSmsVerification($user_id)
    {
        // Fetch active user details
        $user_details = User::find($user_id);
        // If user not found, return error response
        if (!$user_details) {
            return response()->json([
                "status"       => 5,
                "message"      => "User not found!",
                "message_code" => 5,
            ]);
        }

        // Call Twillo SmsVerification  Function
        // user_type = 1 For "User"
        return $this->sendTwilloSmsVerification($user_details, 1);
    }

    /*-----------------------------sendProviderSmsVerification Code------------------------------------------*/
    public function sendProviderSmsVerification($provider_id)
    {
        $provider_details = Provider::find($provider_id);
        if ($provider_details == Null) {
            return response()->json([
                "status" => 5,
                "message" => "Not Found!",
                "message_code" => 5,
            ]);
        }
        return $this->sendTwilloSmsVerification($provider_details, 2);
    }

    /*-----------------------------sendTwilloSmsVerification Code------------------------------------------*/
    private function sendTwilloSmsVerification($user_details, $user_type)
    {
        // Fetch general settings
        $settings = GeneralSettings::first();
        // Check general settings
        if ($settings == null) {
            return response()->json([
                "status" => 0,
                "message" => "Something went wrong!",
                "message_code" => 9,
            ]);
        }

        // Verify if OTP verification is enabled and user is allowed
        if ($settings->is_otp_verification != null && $settings->is_otp_verification == 1 && $user_details->is_default_user == 0 && $user_details->fix_user_show == 0) {
            // Check OTP method (Twilio)
            if (isset($settings->otp_method) && $settings->otp_method == 1) {
                try {
                    // Initialize Twilio client
                    $twilio = new Client(str($settings->twilio_service_key)->trim()->value(), str($settings->twilio_auth_token)->trim()->value());
                    // Send OTP via Twilio Verify
                    $verification = $twilio->verify->v2->services(str($settings->twilio_verify_service_key)->trim()->value())
                        ->verifications
                        ->create($user_details->country_code . $user_details->contact_number, "sms", ['locale' => "en"]);

                    $verificationSid = $verification->sid;

                    // Select model + key based on user_type
                    $model = $user_type == 1 ? UserVerification::class : ProviderVerification::class;
                    $key   = $user_type == 1 ? 'user_id' : 'provider_id';

                    // Delete old OTP
                    $model::where($key, $user_details->id)->delete();

                    // Save new OTP
                    $new_otp = new $model();
                    $new_otp->$key = $user_details->id;
                    $new_otp->token = $verificationSid;
                    $new_otp->save();

                    // Success response
                    return [
                        "status" => 1,
                        "message" => "OTP sent",
                        "token" => $verificationSid,
                    ];
                }
                catch (Exception $e) {
                    info($e->getMessage());
                    return $e;
                }
            }
        }
        return "success";
    }

}
