<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the table does not exist then create
        if (!Schema::hasTable('general_settings')) {
            Schema::create('general_settings', function (Blueprint $table) {
                $table->id();
                $table->string('website_name', '50')->nullable();
                $table->string('website_logo', '50')->nullable();
                $table->string('theme_color', '11')->nullable();
                $table->string('website_favicon', '50')->nullable();
                $table->text('address')->nullable();
                $table->string('contact_no', '15')->nullable();
                $table->string('email', '40')->nullable();
                $table->string('send_receive_email', '100')->nullable();
                $table->string('site_url', '191')->nullable();
                $table->string('copy_right', '191')->nullable();
                $table->string('facebook_link', '191')->nullable();
                $table->string('instagram_link', '191')->nullable();
                $table->string('twitter_link', '191')->nullable();
                $table->string('linkedin_link', '191')->nullable();
                $table->string('map_key', '191')->nullable();
                $table->tinyInteger('auto_settle_wallet')->default(0);
                $table->tinyInteger('wallet_payment')->default(0);
                $table->string('omise_sk', '191')->nullable();
                $table->string('omise_pk', '191')->nullable();
                $table->string('paypal_sandbox', '191')->nullable();
                $table->string('paypal_client_id', '191')->nullable();
                $table->string('paypal_client_secret_key', '191')->nullable();
                $table->string('paypal_merchant_id', '191')->nullable();
                $table->string('paypal_public_key', '191')->nullable();
                $table->string('paypal_private_key', '191')->nullable();
                $table->string('fcm_server_key', '200')->nullable();
                $table->string('fcm_user_topic_name', '191')->nullable();
                $table->string('fcm_driver_topic_name', '191')->nullable();
                $table->string('fcm_store_topic_name', '191')->nullable();
                $table->string('fcm_provider_topic_name', '191')->nullable();
                $table->text('fcm_bearer_token')->nullable();
                $table->dateTime('fcm_bearer_token_expiry_date')->nullable();
                $table->integer('fcm_bearer_token_expiry_mins')->default('55')->unsigned()->comment('fcm bearer token will expire after x mins of generation');
                $table->string('twilio_service_key', '191')->nullable();
                $table->string('twilio_auth_token', '191')->nullable();
                $table->string('twilio_verify_service_key', '191')->nullable();
                $table->string('twilio_contact_number', '191')->nullable();
                $table->string('user_playstore_link', '191')->nullable();
                $table->string('user_appstore_link', '191')->nullable();
                $table->string('driver_delivery_playstore_link', '191')->nullable();
                $table->string('driver_delivery_appstore_link', '191')->nullable();
                $table->string('provider_playstore_link', '191')->nullable();
                $table->string('provider_appstore_link', '191')->nullable();
                $table->string('store_playstore_link', '191')->nullable();
                $table->string('store_appstore_link', '191')->nullable();
                $table->string('shopper_playstore_link', '191')->nullable();
                $table->string('shopper_appstore_link', '191')->nullable();
                $table->double('used_user_discount')->default(0.00);
                $table->tinyInteger('used_user_discount_type')->default(0);
                $table->double('refer_user_discount')->default(0.00);
                $table->tinyInteger('refer_user_discount_type')->default(0);
                $table->string('vnp_TmnCode', '191')->nullable();
                $table->string('vnp_HashSecret', '191')->nullable();
                $table->string('vnp_Url', '191')->nullable();
                $table->string('vnp_Returnurl', '191')->nullable();
                $table->double('delivery_commission')->nullable();
                $table->string('about_us_youtube_link', '191')->nullable();
                $table->integer('user_timeout')->default(60);
                $table->tinyInteger('driver_algorithm')->default(0);
                $table->integer('max_driver_reassign')->default(0)->comment('0-not reassign,0<max limit assign');
                $table->integer('day_allow')->default(0)->comment('0-disable,0< no of day show record');
                $table->integer('send_mail')->default(0)->comment('1:mail send, 0: mail not send');
                $table->string('mail_site_name', '191')->nullable();
                $table->string('smtp_user_name', '191')->nullable();
                $table->string('smtp_password', '191')->nullable();
                $table->string('smtp_hostname', '191')->default('smtp.googlemail.com');
                $table->integer('smtp_port')->default(465);
                $table->string('smtp_encryption', '191')->default('ssl');
                $table->tinyInteger('is_google_login')->default(0);
                $table->tinyInteger('is_facebook_login')->default(0);
                $table->tinyInteger('is_apple_login')->default(0);
                $table->tinyInteger('is_private_driver_module')->default(0)->comment('0:off,1:on');
                $table->string('app_key', '191')->default('TW9zdCBXYW50ZWQgSW4gSW5kaWEgV2l0aCBDeWJlciBDcmltZQ==');
                $table->integer('on_demand_start_service_time')->default(60);
                $table->string('default_server_timezone', '51')->nullable();
                $table->time('provider_start_time')->nullable();
                $table->time('provider_end_time')->nullable();
                $table->integer('provider_slot_time')->default(60);
                $table->time('default_start_time')->nullable();
                $table->time('default_end_time')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
