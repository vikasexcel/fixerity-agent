<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $general_settings_record = [
            'id' => 1,
            'website_name' => 'Fixerity',
            'website_logo' => '490202112022181162.png',
            'theme_color' => '#F5AA00',
            'website_favicon' => '210202112022181159.png',
            'address' => Null,
            'contact_no' => Null,
            'email' => Null,
            'send_receive_email' => Null,
            'site_url' => Null,
            'copy_right' => 'Copyright @ 2025 Fixerity. All Rights Reserved',
            'facebook_link' => Null,
            'instagram_link' => Null,
            'twitter_link' => Null,
            'linkedin_link' => Null,
            'map_key' => 'AIzaSyCTZ2LUQ5uBXK_J6G0k2VPwifq_bO6rRhM',
            'server_map_key' => 'AIzaSyD9hg-YR3n9PZXyvwlj9ELWpyDGItuVxl0',
            'map_lat' => '22.303900',
            'map_long' => '70.802200',
            'auto_settle_wallet' => 0,
            'min_cashout' => 0,
            'max_cashout' => 0,
            'wallet_payment' => 0,
            'provider_min_amount' => 0,
            'omise_sk' => Null,
            'omise_pk' => Null,
            'paypal_sandbox' => Null,
            'paypal_client_id' => Null,
            'paypal_client_secret_key' => Null,
            'paypal_merchant_id' => Null,
            'paypal_public_key' => Null,
            'paypal_private_key' => Null,
            'fcm_server_key' => 'AAAAvk67lZE:APA91bE0-P97x2G8jPWh8Sv3R7UpWZSwsN62pb1oJPgOkD_Jo9ckxih04JejV6Bg_m8Mb37HyL1HpY6LBix_PIcAcdbP43Mpe_ovAW0kSyZ1UFFgHrtGdX0nL0Yj59C7pH_gfrGt5LXR',
            'fcm_user_topic_name' => 'FixerityUser',
            'fcm_driver_topic_name' => Null,
            'fcm_store_topic_name' => Null,
            'fcm_provider_topic_name' => 'FixerityProvider',
            'fcm_bearer_token' => '',
            'fcm_bearer_token_expiry_date' => '2024-07-15 11:28:00',
            'fcm_bearer_token_expiry_mins' => 55,
            'twilio_service_key' => Null,
            'twilio_auth_token' => Null,
            'twilio_verify_service_key' => Null,
            'twilio_contact_number' => Null,
            'user_playstore_link' => Null,
            'user_appstore_link' => Null,
            'driver_delivery_playstore_link' => Null,
            'driver_delivery_appstore_link' => Null,
            'provider_playstore_link' => Null,
            'provider_appstore_link' => Null,
            'store_playstore_link' => Null,
            'store_appstore_link' => Null,
            'shopper_playstore_link' => Null,
            'shopper_appstore_link' => Null,
            'used_user_discount' => 20.00,
            'used_user_discount_type' => 1,
            'refer_user_discount' => 30.00,
            'refer_user_discount_type' => 1,
            'vnp_TmnCode' => Null,
            'vnp_HashSecret' => Null,
            'vnp_Url' => Null,
            'vnp_Returnurl' => Null,
            'delivery_commission' => Null,
            'about_us_youtube_link' => Null,
            'user_timeout' => 0,
            'driver_algorithm' => 0,
            'max_driver_reassign' => 0,
            'day_allow' => 7,
            'send_mail' => 1,
            'mail_site_name' => 'Fixerity',
            'smtp_user_name' => 'wlf.mail22@gmail.com',
            'smtp_password' => 'tzwotytbdfkicsbi',
            'smtp_hostname' => 'smtp.googlemail.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'is_google_login' => 1,
            'is_facebook_login' => 0,
            'is_apple_login' => 0,
            'is_private_driver_module' => 1,
            'app_key' => 'CzowAbuvKJsczFOmfme1i14ba9ebVwy7Z4EOMTDAHBjtdHZAq1',
            'on_demand_start_service_time' => 60,
            'default_server_timezone' => 'UTC',
            'provider_start_time' => '00:00:00',
            'provider_end_time' => '23:59:59',
            'provider_slot_time' => 60,
            'default_start_time' => '00:00:00',
            'default_end_time' => '23:59:59',

        ];
        /*
      | upsert
      |--------------------------------------------------------------------------
      |
      | We are using updateOrInsert here, which functions to either update an existing record or insert a new one. If
      | the record already exists, it updates it; if not, it inserts a new record. It identifies records by comparing a unique
      | key and is designed to handle a single record at a time.
      |
      */
        DB::table('general_settings')->updateOrInsert( ['id' => 1], $general_settings_record);
    }
}
