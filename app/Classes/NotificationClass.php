<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 18-02-2019
 * Time: 12:44 PM
 */

namespace App\Classes;

use App\Jobs\AutoMail;
use App\Models\ApiLogDetail;
use App\Models\EmailTemplates;
use App\Models\GeneralSettings;
use App\Models\ServiceCategory;
use App\Models\UserPackageBooking;
use App\Models\UserWalletTransaction;
use Carbon\CarbonPeriod;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Carbon;

class NotificationClass
{
    private $email_time = 5;//in seconds

    public function __construct()
    {
        //
    }

    public function ApiLogDetail($logger_type=Null, $logger_id=Null, $log_api_name="", $request=[]){
        try {
            $remove_log_date = date('Y-m-d H:i:s', strtotime("-3 days"));
            ApiLogDetail::query()->where("created_at","<=",$remove_log_date)->delete();
            $api_log_detail = new ApiLogDetail();
            $api_log_detail->logger_type = $logger_type;
            $api_log_detail->logger_id = $logger_id;
            $api_log_detail->log_api_name = $log_api_name;
            $api_log_detail->log_json = json_encode($request);
            $api_log_detail->save();
        }catch (\Exception $e){}
    }

    //send push notification via cURL call to FCM V1 API
    public function sendPushNotification($topic,$title,$message,$notification_type)
    {
        //cURL url
        $fcm_url = "https://fcm.googleapis.com/v1/projects/" . config('firebase-cloud-messaging.configurations.project_id') . "/messages:send";
        //fetch fcm bearer token and renew if its expired
        $fcm_bearer_token = (new AuthAlertClass())->fetchFCMBearerToken();
        //cURL headers
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $fcm_bearer_token
        ];
        //cURL body (send the body in json format and keep everything in string)
        $body = [
            "validate_only" => false,
            "message" => [
                "topic" => $topic,
                "data" => [
                    "sound" => "true",
                    "notification_type" => $notification_type . "",
                    "title" => $title . "",
                    "body" => $message . "",
                    "message" => $message . ""
                ],
                "notification" => [
                    "title" => $title . "",
                    "body" => $message . ""
                ],
                "android" => [
                    "notification" => [
                        "click_action" => "FLUTTER_NOTIFICATION_CLICK"
                    ]
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "content-available" => 1
                        ]
                    ]
                ]
            ]
        ];
        //initialize cURL call
        $curl = curl_init();
        //set cURL preferences
        curl_setopt($curl, CURLOPT_URL, $fcm_url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        //execute cURL call
        $response = curl_exec($curl);
        //close cURL call
        curl_close($curl);

        return $response;
    }

//   handyman userProviderPackageNotification
    public function userProviderPackageNotification($order_id, $device_token, $delivery_status, $language,$extra_amount = 0)
    {
        if ($order_id == Null || $device_token == Null || $delivery_status == Null) {
            return response()->json([
                'status' => 0,
                'message' => __('provider_messages.9'),
                'message_code' => 9,
            ]);
        }
        $delivery = UserPackageBooking::query()->where('id', $order_id)->first();
        if ($delivery == Null) {
            return response()->json([
                'status' => 0,
                'message' => __('provider_messages.9'),
                'message_code' => 9,
            ]);
        }
        $service_category = ServiceCategory::query()->where('id',$delivery->service_cat_id)->where('status',1)->first();
        if($service_category == NULL){
            return response()->json([
                "status" => 0,
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }

        $language = $language != Null ? $language : 'en';

//        $title = "Order Notification";
        $title = __('user_messages.91',[],$language);
        $title_code = 91;

        if ($delivery_status == 2) {
//            $message = "Service Accepted by Provider";
            $message = __('user_messages.95',[],$language);
            $message_code = 95;
        } elseif ($delivery_status == 3) {
//            $message = "Schedule Service Accepted by Provider";
            $message = __('user_messages.96',[],$language);
            $message_code = 96;
        } elseif ($delivery_status == 4) {
//            $message = "Service Rejected by Provider";
            $message = __('user_messages.97',[],$language);
            $message_code = 97;
        } elseif ($delivery_status == 5) {
//            $message = "Service Cancelled by " . trim(ucwords(strtolower($delivery->cancel_by)));
            $message = __('user_messages.98',[],$language);
            $message_code = 98;

            $title = __('user_messages.42',[],$language);
            $title_code = 42;

            if (trim(ucwords(strtolower($delivery->cancel_by))) == "Admin") {
                $message = __('user_messages.111',[],$language);
                $message_code = 111;

                $title = __('user_messages.42',[],$language);
                $title_code = 42;
            }

        } elseif ($delivery_status == 6) {
//            $message = "Provider On the Way";
            $message = __('user_messages.99',[],$language);
            $message_code = 99;
        } elseif ($delivery_status == 7) {
//            $message = "Provider Arrived at Destination";
            $message = __('user_messages.100',[],$language);
            $message_code = 100;
        } elseif ($delivery_status == 8) {
//            $message = "Provider Start Processing";
            $message = __('user_messages.101',[],$language);
            $message_code = 101;
        }  elseif ($delivery_status == 9) {
            if($extra_amount == 1 && $delivery->completed_by ==0){
                $message = __('user_messages.331', [], $language);
                $message_code = 331;
            }
            else{
                $message = __('user_messages.102', [], $language);
                $message_code = 102;
            }
            if($delivery->completed_by == 1){
                $message = __('user_messages.112', [], $language);
                $message_code = 112;
            }
        } else {
            return response()->json([
                "status" => 0,
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    //notification type 0= simple , 1= communication
        $notification_data_array = [
            "title" => $title,
            "title_code" => $title_code . "",
            "sound" => "true",
            "notification_type" => "1",
            "order_id" => $delivery->id . "",
            "order_status" => $delivery->status . "",
            "message" => $message,
            "body" => $message,
            "message_code" => $message_code . "",
            "service_category_id" => $delivery->service_cat_id . "",
            "category_type" => $service_category->category_type . "",
            "booking_type" => $delivery->order_type . "",
        ];
        $response = (new AuthAlertClass())->sendFlowNotification($device_token, $notification_data_array, 0);
        return $response;
    }

    //   handyman providerOrderPackageNotification
    public function providerOrderPackageNotification($order_id, $device_token, $order_status,$language,$completed_by = 0)
    {
        if ($order_id == Null || $device_token == Null || $order_status == Null) {
            return response()->json([
                'status' => 0,
                'message' => __('provider_messages.9'),
                'message_code' => 9,
            ]);
        }
        $language = $language != Null ? $language : 'en';
        //$title = "Order Notification";
        $title = __('provider_messages.91',[],$language);
        $title_code = 91;
        if ($order_status == 6) {
            //$message = "User On the Way";
            $message = __('provider_messages.311',[],$language);
            $message_code = 264;
        }
        elseif ($order_status == 9 && $completed_by == 0) {
            //$message = "Order completed by admin!";
            $message = __('provider_messages.112',[],$language);
            $message_code = 112;
        }
        elseif ($order_status == 9 && $completed_by == 1) {
            //$message = "Order completed by admin!";
            $message = __('provider_messages.329',[],$language);
            $message_code = 329;
        }
        elseif ($order_status == 12) {
            //$message = "User Arrived at Destination";
            $message = __('provider_messages.312',[],$language);
            $message_code = 265;
        }
        else {
            return response()->json([
                'status' => 0,
                'message' => __('provider_messages.9'),
                'message_code' => 9,
            ]);
        }
        //notification type 0= simple , 1= communication
        $notification_data_array = [
            "title" => $title,
            "title_code" => $title_code . "",
            "sound" => "true",
            "notification_type" => "1",
            "order_id" => $order_id . "",
            "order_status" => $order_status . "",
            "message" => $message,
            "body" => $message,
            "message_code" => $message_code . "",
        ];
        $response = (new AuthAlertClass())->sendFlowNotification($device_token, $notification_data_array, 0);
        return $response;
    }

    //   handyman providerOrderRequestNotification
    public function providerOrderRequestNotification($order_id, $order_status, $device_token,$language)
    {
        if ($order_id == Null || $device_token == Null ) {
            return response()->json([
                'status' => 0,
                'message' => __('user_messages.9'),
                'message_code' => 9,
            ]);
        }

        $language = $language != Null ? $language : 'en';
        $notification_data_array = [
            "title" => __('provider_messages.41', [], $language),
            "title_code" => "41",
            "sound" => "true",
            "notification_type" => "1",
            "order_id" => $order_id . "",
            "order_status" => $order_status . "",
            "message" => __('provider_messages.90', [], $language),
            "body" => __('provider_messages.90', [], $language),
            "message_code" => "90",
        ];
        $response =  (new AuthAlertClass())->sendFlowNotification($device_token, $notification_data_array, 0);
        return $response;
    }

    //   handyman providerOrderCancelRequestNotification
    public function providerOrderCancelRequestNotification($order_id, $order_status, $device_token,$language)
    {
        if ($order_id == Null || $device_token == Null) {
            return response()->json([
                'status' => 0,
                'message' => __('provider_messages.9'),
                'message_code' => 9,
            ]);
        }
        $order_details = UserPackageBooking::query()->where('id', $order_id)->first();
        if ($order_details == Null) {
            return response()->json([
                'status' => 0,
                'message' => __('provider_messages.9'),
                'message_code' => 9,
            ]);
        }
        $language = $language != Null ? $language : 'en';

        // To driver Assign

//        $title = "Cancel Order";
        $title = __('provider_messages.42', [], $language);
        $title_code = 42;

//        $message = "Order Cancel By " . trim(ucwords(strtolower($order_details->cancel_by)));

        if (trim(ucwords(strtolower($order_details->cancel_by))) == "Admin") {
            $message = __('provider_messages.111', [], $language);
            $message_code = 111;
        } else {
            $message = __('provider_messages.36', [], $language);
            $message_code = 36;
        }
        $notification_data_array = [
            "title" => $title,
            "title_code" => $title_code . "",
            "sound" => "true",
            "notification_type" => "2",
            "order_id" => $order_id . "",
            "order_status" => $order_status . "",
            "message" => $message,
            "body" => $message,
            "message_code" => $message_code . "",
        ];
        $response = (new AuthAlertClass())->sendFlowNotification($device_token, $notification_data_array, 0);
        return $response;
    }

    public function dateLangConvert($date,$lang="en"){
        if($lang == "en"){
            return $date;
        }else{
            $search = config('dateconstants.en');
            $replace = config('dateconstants.'.$lang);
            if($replace == Null){
                return $date;
            }
            $new_date = str_ireplace($search, $replace, $date);
            return $new_date;
        }
    }

    public function statusLangConvert($status,$lang = 'en'){
        if($lang == "en"){
            return $status;
        }else{
            $search = config('statusconstants.en');
            $replace = config('statusconstants.'.$lang);
            if($replace == Null){
                return $status;
            }
            $new_date = str_ireplace($search, $replace, $status);
            return $new_date;
        }
    }

    public function serviceLangConvert(){
        $service = [
            'Bike Riding' => [
                'en' => 'Bike Riding', 'ar' => 'ركوب الدراجة', 'de' => 'Radfahren', 'ko' => '자전거 타기', 'fr' => 'Faire du vélo',
                'ceb' => 'Pagsakay sa Bisikleta', 'es' => 'Montar bicicleta', 'fil' => 'Pagsakay sa Bike',
                'zh-rCN' => '骑自行车', 'zh-Hans' => '骑自行车', 'zh' => '騎自行車', 'zh-Hant' => '騎自行車', 'ja' => '自転車に乗る'
            ],
            'Courier Delivery' => [
                'en' => 'Courier Delivery', 'ar' => 'تسليم البريد السريع', 'de' => 'Kurierlieferung', 'ko' => '택배 배송', 'fr' => 'La livraison de courrier',
                'ceb' => 'Paghatud sa Courier', 'es' => 'Entrega por mensajería', 'fil' => 'Paghahatid ng Courier',
                'zh-rCN' => '快递运送', 'zh-Hans' => '快递运送', 'zh' => '快遞運送', 'zh-Hant' => '快遞運送', 'ja' => '宅配便'
            ],
            'Store Delivery' => [
                'en' => 'Store Delivery', 'ar' => 'تسليم المتجر', 'de' => 'Ladenlieferung', 'ko' => '매장 배송', 'fr' => 'Livraison en magasin',
                'ceb' => 'Paghatud sa Tindahan', 'es' => 'Entrega en tienda', 'fil' => 'Paghahatid sa Tindahan',
                'zh-rCN' => '店铺送货', 'zh-Hans' => '店铺送货', 'zh' => '店鋪送貨', 'zh-Hant' => '店鋪送貨', 'ja' => '店舗配送'
            ],
            'Taxi Riding' => [
                'en' => 'Taxi Riding', 'ar' => 'ركوب سيارات الأجرة', 'de' => 'Taxifahren', 'ko' => '택시 타기', 'fr' => 'Promenade en taxi',
                'ceb' => 'Pagsakay sa Taxi', 'es' => 'Paseo en taxi', 'fil' => 'Pagsakay sa Taxi',
                'zh-rCN' => '乘坐出租车', 'zh-Hans' => '乘坐出租车', 'zh' => '乘坐出租車', 'zh-Hant' => '乘坐出租車', 'ja' => 'タクシー乗り'
            ],
            'Ride Service' => [
                'en' => 'Ride Service', 'ar' =>'خدمة الركوب', 'de' => 'Fahrservice', 'ko' => '라이드 서비스', 'fr' => 'Service de conduite',
                'ceb' => 'Pagsakay sa Serbisyo', 'es' => 'Servicio de viaje', 'fil' => 'Pagsakay sa Serbisyo',
                'zh-rCN' => '乘车服务', 'zh-Hans' => '乘车服务', 'zh' => '乘車服務', 'zh-Hant' => '乘車服務', 'ja' => 'ライドサービス'
            ],
            'Rental Service' => [
                'en' => 'Rental Service', 'ar' =>'خدمات التأجير', 'de' => 'Vermietung', 'ko' => '대여 서비스', 'fr' => 'Services de location',
                'ceb' => 'Mga Serbisyo sa Pag-abang', 'es' => 'Servicios de alquiler', 'fil' => 'Mga Serbisyo sa Pagrenta',
                'zh-rCN' => '租赁服务', 'zh-Hans' => '租赁服务', 'zh' => '租賃服務', 'zh-Hant' => '租賃服務', 'ja' => 'レンタルサービス'
            ],
        ];

        return $service;
    }

    //handyman-user WalletTransferNotification
    public function userWalletTransferNotification($title, $message, $transfer_wallet_holder_device_token)
    {
        $notification_data_array = [
            "title" => $title . "",
            "title_code" => "262",
            "sound" => "true",
            "notification_type" => "6",
            "message" => $message . "",
            "body" => $message . "",
            "message_code" => "263",
        ];
        $response = (new AuthAlertClass())->sendFlowNotification($transfer_wallet_holder_device_token, $notification_data_array, 0);
        return $response;
    }

    public function sendMail($subject="",$to_mail,$mail_type="",$data=[]){
        try {
            $general_setting = request()->get("general_settings");

            $template = EmailTemplates::query()->where('type',$mail_type)->where('status',1)->first();

            if ($general_setting != Null && $template != Null){
                if($general_setting->send_mail == 1){
                    $smtp_user_name = ($general_setting->smtp_user_name != Null)?$general_setting->smtp_user_name:"";
                    $smtp_password = ($general_setting->smtp_password != Null)?$general_setting->smtp_password:"";
                    $smtp_hostname = ($general_setting->smtp_hostname != Null)?$general_setting->smtp_hostname:"";
                    $smtp_port = ($general_setting->smtp_port != Null)?$general_setting->smtp_port:"";
                    $smtp_encryption = ($general_setting->smtp_encryption != Null)?$general_setting->smtp_encryption:"";
                    if($smtp_user_name !="" &&  $smtp_password != "" && $smtp_hostname !="" && $smtp_port !="" && $smtp_encryption != ""  ) {
                        //$site_logo = asset($this->getWebCompanyWiseImage("logo", $general_setting->website_logo));
                        //$mail_logo = asset($this->getWebCompanyWiseImage("email_template", "e-temp-email.png"));
                        //$fb_logo = asset($this->getWebCompanyWiseImage("email_template", "e-temp-facebook.png"));
                        //$twitter_logo = asset($this->getWebCompanyWiseImage("email_template", "e-temp-twitter.png"));
                        //$web_logo = asset($this->getWebCompanyWiseImage("email_template", "e-temp-world-wide-web.png"));

                        $site_logo = asset("/assets/images/email-temp-images/" . $general_setting->website_logo);
                        $mail_logo = asset("/assets/images/email-temp-images/e-temp-email.png");
                        $fb_logo = asset("/assets/images/email-temp-images/e-temp-facebook.png");
                        $twitter_logo = asset("/assets/images/email-temp-images/e-temp-twitter.png");
                        $web_logo = asset("/assets/images/email-temp-images/e-temp-world-wide-web.png");

                        $site_email = ($general_setting->email != "") ? $general_setting->email : "#";
                        $twitter_link = ($general_setting->twitter_link != "") ? $general_setting->twitter_link : "#";
                        $facebook_link = ($general_setting->facebook_link != "") ? $general_setting->facebook_link : "#";
                        $mail_site_name = ($general_setting->mail_site_name != "") ? $general_setting->mail_site_name : "";
                        $site_url = ($general_setting->site_url != "") ? $general_setting->site_url : request()->getHost();

                        $disp_data = $data;

                        $general_data = array(
                            "##logo##" => $site_logo ,
                            "##mail_site_name##" => $mail_site_name,
                            "##site_url##" => $site_url,
                            "##site_email##"  =>  $site_email,
                            "##twitter_link##" => $twitter_link,
                            "##facebook_link##" => $facebook_link,
                            "##mail_logo##" => $mail_logo,
                            "##fb_logo##" => $fb_logo,
                            "##twitter_logo##" => $twitter_logo,
                            "##web_logo##" => $web_logo,
                        );
                        $final_data = array_merge($disp_data,$general_data);
                        $template_content = str_replace(array_keys($final_data),$final_data,$template->content);

                        $email=$to_mail;

                        $data = [
                            "type" => 3,//store
                            "path" => "mail_template.temp",
                            "email" => $email,
                            "subject" => $subject,
                            "mail_site_name" => $mail_site_name,
                            "template_content" => $template_content,
                            "smtp_user_name" => $smtp_user_name,
                            "smtp_password" => $smtp_password,
                            "smtp_hostname" => $smtp_hostname,
                            "smtp_port" => $smtp_port,
                            "smtp_encryption" => $smtp_encryption,
                        ];
                        $emailJob = (new AutoMail($data))->delay(now()->addSeconds($this->email_time));
                        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($emailJob);
                    }
                    return "";
                }
                return "";
            }
            return "";
        }
        catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public static function convertTimezone($date="", $from_timezone="", $to_timezone="", $is_date="y"){
        $default_server_timezone = "";
        $general_settings = request()->get("general_settings");
        if ($general_settings != Null) {
            if ($general_settings->default_server_timezone != "") {
                $default_server_timezone = $general_settings->default_server_timezone;
            }
        }
        $timezoneMapping = [
            'Asia/Calcutta' => 'Asia/Kolkata'
        ];

        // Use the mapped timezone identifier if it exists
        $default_server_timezone = $timezoneMapping[$default_server_timezone] ?? $default_server_timezone;

        $from_timezone = $timezoneMapping[$from_timezone] ?? $from_timezone;
        $to_timezone = $timezoneMapping[$to_timezone] ?? $to_timezone;

        $timezone = $default_server_timezone;
        date_default_timezone_set($timezone);
        if($from_timezone == ""){
            $from_timezone = $timezone;
        }
        if($to_timezone == ""){
            $to_timezone = $timezone;
        }

        if($from_timezone !="" && $to_timezone != "" && ($from_timezone != $to_timezone)){
            if($is_date == "n"){
                $format = "H:i:s";
            }elseif($is_date == "d"){
                $format = "Y-m-d h:i:s A";
            }else{
                $format = "Y-m-d H:i:s";
            }
            $date = new DateTime($date, new DateTimeZone($from_timezone));
            $date->format($format);
            $date->setTimezone(new DateTimeZone($to_timezone));
            $date = $date->format($format);
            return $date;
        }else{
            return $date;
        }
    }

    public function defaultProviderOpenCloseTime($default_start_time="",$default_end_time="",$default_provider_slot_time=""){
        $general_settings = request()->get("general_settings");
        if($default_start_time == "" || $default_end_time == "")
        {
            $default_start_time= ($general_settings->default_start_time != Null)?$general_settings->default_start_time:"00:00:00";
            $default_end_time= ($general_settings->default_end_time != Null)?$general_settings->default_end_time:"23:59:59";
            $default_provider_start_time= ($general_settings->provider_start_time != Null)?$general_settings->provider_start_time:"09:00:00";
            $default_provider_end_time= ($general_settings->provider_end_time != Null)?$general_settings->provider_end_time:"19:00:00";
        }else{
            $default_provider_start_time = $default_start_time;
            $default_provider_end_time = $default_end_time;
        }
        $default_provider_slot_time= ($general_settings->provider_slot_time != Null)?$general_settings->provider_slot_time:"60";
//        $start = new DateTime($default_provider_start_time);
//        $end = new DateTime($default_provider_end_time);
//        $startTime = $start->format('H:i:s');
//        $endTime = $end->format('H:i:s');
//        $i=0;
//        $time = [];
//        while(strtotime($startTime) <= strtotime($endTime))
//        {
//            $start = $startTime;
//            $end = date('H:i:s',strtotime('+'.$default_provider_slot_time.' minutes',strtotime($startTime)));
//            $startTime = date('H:i:s',strtotime('+'.$default_provider_slot_time.' minutes',strtotime($startTime)));
//            $i++;
//            if($i == 25){
//                break;
//            }
//            if(strtotime($startTime) <= strtotime($endTime))
//            {
//                $time[$i] = array(
//                    'start_time'=>$start,
//                    'end_time'=>$end,
//                );
//            }
//            if($end == '00:00:00'){
//                break;
//            }
//        }


        $get_time_periods = collect();
        $default_start_time = Carbon::parse($default_start_time)->format('H:i:s');
        $default_end_time = Carbon::parse($default_end_time)->format('H:i:s');

        if ($default_end_time == '23:59:59'){
            $default_end_time = '24:00:00';
        }
        $create_period = (new CarbonPeriod($default_start_time,"$default_provider_slot_time minutes",$default_end_time))->toArray();

        foreach ($create_period as $key => $periode_time){
            if (!isset($create_period[$key+1])) break;
            $get_time_periods->push([
                'start_time'=>$periode_time->format('H:i:s'),
                'end_time'=>$create_period[$key+1]->format('H:i:s'),
            ]);
        }
        if($default_end_time == '23:59:59'){
            $get_time_periods->push([
                'start_time'=>$get_time_periods->last()['end_time'],
                'end_time'=>'24:00:00'
            ]);
        }

        $provider_default_time_Array = array(
            'default_provider_start_time'=>$default_provider_start_time,
            'default_provider_end_time'=>$default_provider_end_time,
            'default_provider_slot' => $get_time_periods,
        );
        return $provider_default_time_Array;
    }

    //code for auto settle payment module
    public function providerUpdateWalletBalance($provider_id,$wallet_provider_type,$transaction_type,$add_update_wallet_bal,$subject,$subject_code,$order_no){

        if($transaction_type > 0){
            try{
                //fetching remaning balance
                $provider_wallet_balance = UserWalletTransaction::query()->select('remaining_balance')->where('user_id','=', $provider_id)->where('wallet_provider_type','=',$wallet_provider_type)->orderBy('id', 'desc')->first();
                if ($provider_wallet_balance != Null) {
                    $provider_balance = $provider_wallet_balance->remaining_balance;
                } else {
                    $provider_balance = 0;
                }
                //adding/subtracting from wallet
                $add_balance = new UserWalletTransaction();
                $add_balance->user_id = $provider_id;
                $add_balance->wallet_provider_type = $wallet_provider_type;
                $add_balance->transaction_type = $transaction_type;
                $add_balance->amount = $add_update_wallet_bal;
                $add_balance->subject = $subject;
                //transaction_type = 1 means to add in wallet otherwise deduct in wallet
                if($transaction_type == 1)
                {
                    $add_balance->remaining_balance = round($provider_balance + $add_update_wallet_bal, 2);
                }else{
                    $add_balance->remaining_balance = round($provider_balance - $add_update_wallet_bal, 2);
                }
                $add_balance->subject_code = $subject_code;
                $add_balance->order_no = $order_no;
                $add_balance->save();
                return true;
            }catch (\Exception $e){
                return false;
            }
        }else{
            return false;
        }
    }

    //user for timzone convert
    public function getDefaultTimeZone($user_time_zone) {
        $timezoneMapping = [
            'Asia/Calcutta' => 'Asia/Kolkata'
        ];

        $timezone = $timezoneMapping[$user_time_zone] ?? ($time_zone ?? $user_time_zone);
        date_default_timezone_set($timezone);

        return $timezone;
    }

    //get walletBalance
    public function getWalletBalance($user_id)
    {
        $get_last_transaction = UserWalletTransaction::query()
            ->where('wallet_provider_type', "=", 3)
            ->where('user_id', "=", $user_id)
            ->orderBy('id', 'desc')
            ->first();
        if ($get_last_transaction != Null) {
            $last_amount = $get_last_transaction->remaining_balance;
        } else {
            $last_amount = 0;
        }
        return $last_amount;
    }
    //cash out Notification
    public function ProviderCashOutNotification($device_token,$language,$request_for)
    {
        if ($device_token == Null) {
            return response()->json([
                'status' => 0,
                'message' => __('driver_messages.9'),
                'message_code' => 9,
            ]);
        }

        $language = $language != Null ? $language : 'en';
        //$title = "Order Notification";
        $title = __('provider_messages.91',[],$language);
        $title_code = 91;
        if($request_for==1){
            $message = __('provider_messages.334',[],$language);
            $message_code = 334;
        }else{
            $message = __('provider_messages.333',[],$language);
            $message_code = 333;
        }

        //notification type 0= simple , 1= communication
        $notification_data_array = [
            'title' => $title."",
            'title_code' => $title_code."",
            'sound' => "true",
            'notification_type' => "6",
            'message' => $message."",
            'body' => $message."",
            'message_code' => $message_code."",
            "click_action" => "FLUTTER_NOTIFICATION_CLICK"
        ];
        $result = (new AuthAlertClass())->sendFlowNotification($device_token, $notification_data_array, 0);
        return $result;
    }
}
