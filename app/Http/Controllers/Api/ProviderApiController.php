<?php

namespace App\Http\Controllers\Api;

use App\Classes\NotificationClass;
use App\Classes\OnDemandClassApi;
use App\Classes\UserClassApi;
use App\Http\Controllers\Controller;
use App\Models\CashOut;
use App\Models\GeneralSettings;
use App\Models\OtherServiceProviderDetails;
use App\Models\OtherServiceProviderTimings;
use App\Models\PushNotification;
use App\Models\UserWalletTransaction;
use App\Models\WorldCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProviderApiController extends Controller
{
    //0:user,1:store,2:driver,3:provider
    private $userClassapi;
    private $onDemandClassApi;
    private $NotificationClass;
    private $provider_type = 3;

    public function __construct(UserClassApi $userClassapi, OnDemandClassApi $onDemandClassApi, NotificationClass $NotificationClass)
    {
        $this->userClassapi = $userClassapi;
        $this->onDemandClassApi = $onDemandClassApi;
        $this->NotificationClass = $NotificationClass;
    }
    //provider manage card
    public function postOnDemandAddCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "holder_name" => "nullable",
            "card_number" => "required",
            "month" => "required|numeric",
            "year" => "required|numeric",
            "cvv" => "required|numeric"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        return $this->userClassapi->addCardManage($this->provider_type, $provider_id, $request->all());
    }

    public function postOnDemandRemoveCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "card_id" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        $card_id = $request->get("card_id");
        return $this->userClassapi->deleteCardManage($this->provider_type, $provider_id, $card_id);
    }

    public function postOnDemandCardList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        return $this->userClassapi->manageCardList($this->provider_type, $provider_id);
    }
    //provider add wallet balance
    public function postOnDemandAddWalletBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "amount" => "required|numeric",
            "card_id" => "nullable",
            "payment_method_type" => "nullable|in:1,0",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        return $this->userClassapi->addWalletBalance($this->provider_type, $provider_id, $provider_check->first_name, $provider_check->currency, $request->all());
    }

    public function postOnDemandWalletTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        $provider_language = $provider_check->language == null ? 'en' : $provider_check->language;
        return $this->userClassapi->getWalletTransactionList($this->provider_type, $provider_id, $provider_check->currency, $provider_language);
    }

    public function postOnDemandGetWalletBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        return $this->userClassapi->getWalletBalance($this->provider_type, $provider_id, $provider_check->currency);
    }

    //provider transfer wallet amount
    public function postOnDemandSearchWalletTransferUserList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "search" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        return $this->userClassapi->searchWalletTransferUserList($this->provider_type, $provider_id, $request->get("search"));
    }

    public function postOnDemandWalletToWalletTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "amount" => "required|numeric",
            "transfer_id" => "required|numeric",
            "wallet_provider_type" => "required|in:0,3"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        return $this->userClassapi->walletToWalletTransfer($this->provider_type, $provider_id, $provider_check->first_name, $provider_check->currency, $request->all());
    }

    //time slot module start
    public function postOnDemandOpenTimeListBKP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        $provider_details = OtherServiceProviderDetails::query()->where('provider_id', '=', $provider_id)->first();
        if ($provider_details == Null) {
            return response()->json([
                "status" => 0,
                'message' => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
        $open_time_list = [];

        $get_open_timings = OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->get();

        foreach ($get_open_timings as $get_open_timing) {
            $slot = [];
            $time_list = explode(',', $get_open_timing->open_time_list);
            foreach ($time_list as $time) {
                $slot[] = ['slot' => $time];
            }
            $open_time_list [] = [
                'day' => $get_open_timing->day,
                'select_open_time' => $slot
            ];
        }
        return response()->json([
            "status" => 1,
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            "open_time_list" => $open_time_list,
            "service_time_status" => $provider_details->time_slot_status,
            "all_day_open_time" => $provider_details->start_time,
            "all_day_close_time" => $provider_details->end_time,
        ]);

    }

    public function postOnDemandOpenTimeList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "day" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $day = strtoupper($request->get('day'));
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }

        $provider_lang = isset($provider_check->language) ? $provider_check->language : "";
        if ($provider_lang != "en" && $provider_lang != "" && $provider_lang != "Null") {
            $provider_lang = $provider_lang . "_";
        } else {
            $provider_lang = "";
        }

        $provider_details = OtherServiceProviderDetails::query()->where('provider_id', '=', $provider_id)->first();
        if ($provider_details == Null) {
            return response()->json([
                "status" => 0,
                'message' => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }

        $provider_end_time = (($provider_details->end_time == '23:59:00') || ($provider_details->end_time == '23:59:59')) ? '24:00:00' : $provider_details->end_time;
        $provider_details->end_time = $provider_end_time;
        $provider_details->save();
        $open_time_list = [];
        $general_settings = request()->get("general_settings");
        $default_start_time= ($general_settings->default_start_time != Null)?$general_settings->default_start_time:"00:00:00";
        $default_end_time= ($general_settings->default_end_time != Null)?$general_settings->default_end_time:"23:59:59";
        $notificationClass = New NotificationClass();
        $default_provider_open_close_time =  $notificationClass->defaultProviderOpenCloseTime($default_start_time,$default_end_time);
        $get_open_timings = OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', '=', $day)->get();
        $default_provider_slot= isset($default_provider_open_close_time['default_provider_slot'])?$default_provider_open_close_time['default_provider_slot']:[];

        foreach ($default_provider_slot as $key=>$single_provider_slot){
            $selected = 0;
            foreach ($get_open_timings as $get_single_open_timing) {
                if($single_provider_slot['start_time'] == $get_single_open_timing->provider_open_time && $single_provider_slot['end_time'] == $get_single_open_timing->provider_close_time )
                {
                    $selected = 1;
                }
            }
            $single_day_slot[] = array(
                'start_time'=>$single_provider_slot['start_time'],
                'end_time'=>$single_provider_slot['end_time'],
                'display_start_time' => date("h:i A", strtotime($single_provider_slot['start_time'])),
                'display_end_time' => date("h:i A", strtotime($single_provider_slot['end_time'])),
                'selected'=>$selected,
            );
        }

        return response()->json([
            "status" => 1,
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            'day' => $day,
            "display_day" => (config('global.lang_constant.' . $day . '.' . $provider_lang . 'value') != "") ? config('global.lang_constant.' . $day . '.' . $provider_lang . 'value') : $day,
            "open_close_time_list" => $single_day_slot,
            "service_time_status" => $provider_details->time_slot_status,
            "all_day_open_time" => $provider_details->start_time,
            "all_day_close_time" => $provider_details->end_time,
        ]);

    }

    public function postOnDemandUpdateOpenTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
//            "open_timing" => "required_without:all_day_open_time|required_without:all_day_close_time",
//            "all_day_open_time" => "required_without:open_timing|required_with:all_day_close_time",
//            "all_day_close_time" => "required_without:open_timing|required_with:all_day_open_time",
            "open_timing" => "required",
            "day" => "required",
//            "all_day_open_time" => "required_with:all_day_close_time",
//            "all_day_close_time" => "required_with:all_day_open_time",
//            "service_time_status" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $day = strtoupper($request->get('day'));
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
        $days = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];

        if ($request->get('open_timing') != Null) {
            $open_timings = json_decode($request->get('open_timing'), true);
            if (count($open_timings) < 1) {
                OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', $day)->delete();
                return response()->json([
                    "status" => 1,
                    "message" => __('provider_messages.1'),
                    "message_code" => 1
                ]);
            }
            $time_day = [];
            $not_del_id_array=[];
            foreach ($open_timings as $key=>$open_timing) {
                if (in_array($day, $days)) {
                    $open_close_time_arr = explode("-",$open_timing);
                    $provider_open_time = isset($open_close_time_arr[0])?$open_close_time_arr[0]:"00:00:00";
                    $provider_close_time = isset($open_close_time_arr[1])?$open_close_time_arr[1]:"00:00:00";
                    //check_duplicate_value
                    $get_open_timing = OtherServiceProviderTimings::query()
                        ->where('provider_id', $provider_id)
                        ->where('provider_open_time', $provider_open_time)
                        ->where('provider_close_time', $provider_close_time)
                        ->where('day', $day)
                        ->first();
                    if ($get_open_timing == Null) {
                        $get_open_timing = new OtherServiceProviderTimings();
                        $get_open_timing->provider_id = $provider_id;
                        $get_open_timing->day = $day;
                        $get_open_timing->provider_open_time = $provider_open_time;
                        $get_open_timing->provider_close_time = $provider_close_time;
                        $get_open_timing->save();
                        $not_del_id_array[$key] = $get_open_timing->id;
                    }else{
                        //get not delete id
                        $not_del_id_array[$key] = $get_open_timing->id;
                    }
                }
            }
            OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', $day)->whereNotIn('id', $not_del_id_array)->delete();
        }

        return response()->json([
            "status" => 1,
            "message" => __('provider_messages.1'),
            "message_code" => 1,
//            "day_times" => $day_times
        ]);
    }

    public function postOnDemandUpdateWorkScheduleBKP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "all_day_open_time" => "required_with:all_day_close_time",
            "all_day_close_time" => "required_with:all_day_open_time"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }

        $all_day_open_time = $request->get('all_day_open_time');
        $all_day_close_time = $request->get('all_day_close_time');

        if ($all_day_open_time != Null && $all_day_close_time != Null) {
            $new_array = $this->createTimeArray($all_day_open_time, $all_day_close_time);
            if ($new_array != Null) {
                $provider_details = OtherServiceProviderDetails::query()->where('provider_id', '=', $provider_id)->first();
                if ($provider_details != Null) {
                    $provider_details->start_time = $all_day_open_time;
                    $provider_details->end_time = $all_day_close_time;
                    $provider_details->time_list = implode(',', $new_array);
                    $provider_details->save();

                    $days = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];

                    foreach ($days as $day) {
                        $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', $day)->first();
                        if ($get_open_timing == Null) {
                            $get_open_timing = new OtherServiceProviderTimings();
                            $get_open_timing->provider_id = $provider_id;
                            $get_open_timing->day = strtoupper(trim($day));
                        }
                        $get_open_timing->open_time_list = $provider_details->time_list;
                        $get_open_timing->save();
                    }
                }
            }
        }
        return response()->json([
            "status" => 1,
            "message" => __('provider_messages.1'),
            "message_code" => 1
        ]);
    }

    public function postOnDemandUpdateWorkSchedule(Request $request) {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "all_day_open_time" => "required_with:all_day_close_time",
            "all_day_close_time" => "required_with:all_day_open_time"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }

        $all_day_open_time = date("H:i:s",strtotime($request->get('all_day_open_time')));
        $all_day_close_time_orignal = date("H:i:s",strtotime($request->get('all_day_close_time')));
        $all_day_close_time = $all_day_close_time_orignal;
        if ($all_day_close_time == '00:00:00'){
            $all_day_close_time = '23:59:59';
        }

        if ($all_day_open_time != Null && $all_day_close_time != Null) {
            $notificationClass= New NotificationClass();
            $default_provider_open_close_time =  $notificationClass->defaultProviderOpenCloseTime($all_day_open_time,$all_day_close_time);

            $all_day_open_time =isset($default_provider_open_close_time['default_provider_start_time'])?$default_provider_open_close_time['default_provider_start_time']:"";
            $all_day_close_time = isset($default_provider_open_close_time['default_provider_end_time'])?$default_provider_open_close_time['default_provider_end_time']:"";
            $new_time_array = isset($default_provider_open_close_time['default_provider_slot'])?$default_provider_open_close_time['default_provider_slot']:[];

            $provider_details = OtherServiceProviderDetails::query()->where('provider_id', '=', $provider_id)->first();
            $provider_details->start_time = $all_day_open_time;
            $provider_details->end_time = $all_day_close_time_orignal;
            $provider_details->time_list = "";
            $provider_details->save();
            $days = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];
            foreach ($days as $day) {
                if( count($new_time_array) > 0){
                    //delete older record for same days
                    OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', $day)->delete();
                    foreach($new_time_array as $single_time_arr) {
                        $provider_open_time =  $single_time_arr['start_time'];
                        $provider_close_time =  $single_time_arr['end_time'];
                        $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', $day)->where('provider_open_time', $provider_open_time)->where('provider_close_time', $provider_close_time)->first();
                        if ($get_open_timing == Null) {
                            $get_open_timing = new OtherServiceProviderTimings();
                            $get_open_timing->provider_id = $provider_id;
                            $get_open_timing->day = strtoupper(trim($day));
                            $get_open_timing->provider_open_time = $provider_open_time;
                            $get_open_timing->provider_close_time = $provider_close_time;
                            $get_open_timing->open_time_list = "";
                            $get_open_timing->save();
                        }
                    }
                }
            }
        }
        return response()->json([
            "status" => 1,
            "message" => __('provider_messages.1'),
            "message_code" => 1
        ]);
    }

    public function postOnDemandUpdateWorkStatusBKP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            'status' => "required|in:0,1"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $status = $request->get('status');
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
//        ProviderServices::query()->where('provider_id', '=',$provider_id)
//            ->update([
//                'current_status' => $status
//            ]);


        $provider_details = OtherServiceProviderDetails::query()->where('provider_id', '=', $provider_id)->first();

        if ($provider_details != Null) {

            $provider_details->time_slot_status = $status;
            $provider_details->save();

            if ($status == 1) {
                if ($provider_details->time_list == Null && $provider_details->start_time != Null && $provider_details->end_time != Null) {
                    $new_array = $this->createTimeArray($provider_details->start_time, $provider_details->end_time);
                    $time_list = implode(',', $new_array);
                } else {
                    $time_list = $provider_details->time_list;
                }
                if ($time_list != Null) {
                    $days = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];

                    foreach ($days as $day) {
                        $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', $day)->first();
                        if ($get_open_timing == Null) {
                            $get_open_timing = new OtherServiceProviderTimings();
                            $get_open_timing->provider_id = $provider_id;
                            $get_open_timing->day = strtoupper(trim($day));
                        }
                        $get_open_timing->open_time_list = $time_list;
                        $get_open_timing->save();
                    }
                }
            }

        }

        return response()->json([
            "status" => 1,
            "message" => __('provider_messages.1'),
            "message_code" => 1
        ]);
    }

    public function postOnDemandUpdateWorkStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            'status' => "required|in:0,1"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $status = $request->get('status');
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }
//        ProviderServices::query()->where('provider_id', '=',$provider_id)
//            ->update([
//                'current_status' => $status
//            ]);


        $provider_details = OtherServiceProviderDetails::query()->where('provider_id', '=', $provider_id)->first();

        if ($provider_details != Null) {

            $provider_details->time_slot_status = $status;
            $provider_details->save();

            //if ($status == 1) {
            //    if ($provider_details->time_list == Null && $provider_details->start_time != Null && $provider_details->end_time != Null) {
            //        $new_array = $this->createTimeArray($provider_details->start_time, $provider_details->end_time);
            //        $time_list = implode(',', $new_array);
            //    } else {
            //        $time_list = $provider_details->time_list;
            //    }
            //    if ($time_list != Null) {
            //        $days = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];
            //        foreach ($days as $day) {
            //            $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider_id)->where('day', $day)->first();
            //            if ($get_open_timing == Null) {
            //                $get_open_timing = new OtherServiceProviderTimings();
            //                $get_open_timing->provider_id = $provider_id;
            //                $get_open_timing->day = strtoupper(trim($day));
            //            }
            //            $get_open_timing->open_time_list = $time_list;
            //            $get_open_timing->save();
            //        }
            //    }
            //}

        }

        return response()->json([
            "status" => 1,
            "message" => __('provider_messages.1'),
            "message_code" => 1
        ]);
    }

    public function createTimeArray($all_day_open_time, $all_day_close_time)
    {
        $default_time = array(
            '12:00 AM', '01:00 AM',
            '02:00 AM', '03:00 AM',
            '04:00 AM', '05:00 AM',
            '06:00 AM', '07:00 AM',
            '08:00 AM', '09:00 AM',
            '10:00 AM', '11:00 AM',
            '12:00 PM', '01:00 PM',
            '02:00 PM', '03:00 PM',
            '04:00 PM', '05:00 PM',
            '06:00 PM', '07:00 PM',
            '08:00 PM', '09:00 PM',
            '10:00 PM', '11:00 PM'
        );
        $start_time = (array_search($all_day_open_time, $default_time, true));
        $end_time = (array_search($all_day_close_time, $default_time, true));
        if ($end_time == 0) {
            $end_time = 23;
        } else {
            $end_time = $end_time - 1;
        }
        if ($start_time > $end_time) {
            return response()->json([
                "status" => 0,
                "message" => __('provider_messages.68'),
                "message_code" => 9,
            ]);
        }
        $new_array = [];
        if (is_integer($start_time) && is_integer($end_time)) {
            for ($i = $start_time; $i <= $end_time; $i++) {
                if ($i != 23) {
                    $new_array[] = $default_time[$i] . ' - ' . $default_time[$i + 1];
                } else {
                    $new_array[] = $default_time[$i] . ' - ' . $default_time[0];
                }
            }
        }
        return $new_array;
    }
    //time slot module end

    public function postOnDemandMassNotificationList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required|numeric",
            "page" => "nullable|numeric",
            "per_page" => "nullable|numeric"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($provider_id, $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }

        $per_page = 10;
        if ($request->get('per_page') != Null) {
            $per_page = $request->get('per_page');
        }
        $mass_notification_list = PushNotification::query()
            //->select('id', 'title', 'message')
            ->select('id', 'title', DB::raw("(CASE WHEN title != '' THEN title ELSE '' END) as title"),
                'message', DB::raw("(CASE WHEN created_at IS NOT NULL THEN created_at ELSE '' END) as datetime"))
            ->whereIn('notification_type', [1, 5])
            ->orderBy('id', 'desc')
            ->paginate($per_page);

        $get_items_list = $mass_notification_list->items();
        $current_page = $mass_notification_list->currentPage();
        $last_page = $mass_notification_list->lastPage();
        $total = $mass_notification_list->total();

        return response()->json([
            "status" => 1,
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            "current_page" => $current_page - 0,
            "last_page" => $last_page - 0,
            "total" => $total - 0,
            "mass_notification_list" => $get_items_list
        ]);
    }

    // Provider Cash_out request API
    public function postOnDemandRequestCashout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required",
            "amount" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_check = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_check) == false) {
            return $provider_check;
        }

        $settings = GeneralSettings::query()->first();

        $provider_currency = WorldCurrency::query()->where('symbol', $provider_check->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency != Null ? $provider_currency->ratio : 1;
        $currency_symbol = $provider_currency->symbol;

        $amount_to_default = round($request->get('amount')/ $currency, 2);

        //min cash amount according to provider currency
        $min_cash_amount = $settings->min_cashout * $currency;

        //max cash amount according to provider currency
        $max_cash_amount = $settings->max_cashout * $currency;

        if($amount_to_default < $settings->min_cashout || $amount_to_default > $settings->max_cashout){
            $message = $amount_to_default < $settings->min_cashout
                ? __('provider_messages.336', ['amount'=>$currency_symbol.''.$min_cash_amount])
                : __('provider_messages.337', ['amount' => $currency_symbol.''. $max_cash_amount]);

            $messageCode = $amount_to_default < $settings->min_cashout ? 335 : 340;

            return response()->json([
                "status" => 0,
                'message' => $message,
                "message_code" => $messageCode,
            ]);
        }

        //get wallet balance
        $last_amount = $this->NotificationClass->getWalletBalance($request->get('provider_id'));
        if($last_amount < $request->get('amount')){
            return response()->json([
                "status" => 0,
                "message" => __('provider_messages.332'),
                "message_code" => 332
            ]);
        }

        if($request->get('amount') > 0){
            $cash_out = new CashOut();
            $cash_out->user_id = $request->get('provider_id');
            $cash_out->user_name = $provider_check->first_name;
            $cash_out->amount = $request->get('amount');
            $cash_out->save();

            $add_balance = new UserWalletTransaction();
            $add_balance->user_id = $request->get('provider_id');
            $add_balance->wallet_provider_type = 3;
            $add_balance->transaction_type = 2;
            $add_balance->amount = $amount_to_default;
            $add_balance->subject = "request for cashout";
            $add_balance->subject_code = 16;
            $add_balance->remaining_balance = $last_amount - $amount_to_default;
            $add_balance->save();

            $last_amount = $add_balance->remaining_balance;
        }

        return response()->json([
            "status" => 1,
            'message' => __('provider_messages.338'),
            "message_code" => 1,
            "wallet_balance" => round($last_amount * $currency, 2),
        ]);
    }
    // end of cash_out module
}
