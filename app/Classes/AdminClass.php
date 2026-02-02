<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 01-01-2019
 * Time: 04:32 PM
 */

namespace App\Classes;


use App\Models\LanguageLists;
use App\Models\EmailTemplates;
use App\Models\GeneralSettings;
use App\Models\PromocodeDetails;
use App\Models\Provider;
use App\Models\ServiceSettings;
use App\Models\UsedPromocodeDetails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminClass
{
    public function __construct()
    {
        //
    }

    public function renderingResponce($view)
    {
        return response()->json([
            'content' => $view['page-content'],
            'title' => $view['title'],
            'extra_css' => $view['page-css'],
            'extra_js' => $view['page-js'],
        ]);
    }

    public function checkProviderStatus()
    {
        $provider = Provider::query()->where('id', Auth::guard('on_demand')->user()->id)->whereNull('providers.deleted_at')->first();
        if ($provider->status == 2) {
            Auth::logout();
            return redirect()->route('post:provider-admin:login')->with("error", "Your account is currently blocked, so not authorised to allow any activity!");
        }
        if ($provider->verified_at == null){
            return redirect()->route('get:provider-admin:not_verified');
        }
        if ($provider->status == 3) {
//            dd("3");
            return redirect()->route('get:provider-admin:service-register');
        }
    }
    public function ServiceSettingsStore($request)
    {
        $id = $request->get('id');
        if ($id != Null) {
            $service_settings = ServiceSettings::where('id', $request->get('id'))->first();
        } else {
            $service_settings = new ServiceSettings();
        }
        $service_settings->service_cat_id = $request->get('service_cat_id');
        $service_settings->provider_accept_timeout = $request->get('provider_accept_timeout');
        $service_settings->provider_search_radius = $request->get('provider_search_radius');
        $service_settings->tax = $request->get('tax');
        $service_settings->admin_commission = $request->get('admin_commission');
        $service_settings->cancel_charge = $request->get('cancel_charge');
        //$service_settings->status = $request->get('status');
        $service_settings->save();
        return $service_settings;
    }

    // Add Service Provider
    public function AddServiceProvider($request)
    {
        $id = $request->get('id');
        if ($id != Null) {
            $provider = Provider::where('id', $id)->whereNull('providers.deleted_at')->first();
//            $provider->status = 1;
        } else {
            $provider = new Provider();
            $provider->verified_at =  date('Y-m-d H:i:s');
//            $provider->web_verified_at =  date('Y-m-d H:i:s');
            $provider->status = 1;
            $provider->password = Hash::make($request->get('pass'));
//            $provider->email = $request->get('email');
//            $provider->country_code = $request->get('country_code');
////        $provider->contact_number = $request->get('full_number');
//            $provider->contact_number = trim($request->get('contact_number'));
        }
        $provider->email = $request->get('email');
        $provider->country_code = $request->get('country_code');
//        $provider->contact_number = $request->get('full_number');
        $provider->contact_number = trim($request->get('contact_number'));
        $provider->first_name = ucwords(trim($request->get('first_name')));
//        $provider->last_name = ucwords(trim($request->get('last_name')));

        $provider->service_radius = $request->get('service_radius');
        $provider->gender = $request->get('gender');
        //$provider->status = 1;

        if ($request->file('avatar')) {
            if (\File::exists(public_path('/assets/images/profile-images/provider/' . $provider->avatar))) {
                \File::delete(public_path('/assets/images/profile-images/provider/' . $provider->avatar));
            }
            $file = $request->file('avatar');
            $file_new = random_int(1, 99) . date('sihYdm') . random_int(1, 99) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path() . '/assets/images/profile-images/provider/', $file_new);
            $provider->avatar = $file_new;
        }
        $provider->save();
        if($provider->wasChanged('contact_number') || $provider->wasChanged('country_code')){
            $provider->access_token = Null;
            $provider->device_token = Null;
            $provider->save();
        }
        return $provider;
    }

    public function get_langugae_fields($user_lang="en")
    {
        $get_lang = LanguageLists::query()->where('language_code','=',$user_lang)->first();
        if($get_lang != Null){
            if(isset($get_lang->language_code) &&  $get_lang->language_code != Null){
                $lang_prefix = $get_lang->language_code."_";
            }else {
                $lang_prefix = "";
            }
        }else {
            $lang_prefix = "";
        }
        return $lang_prefix;
    }

    public function is_in_restricted_area($points_polygon, $vertices_x, $vertices_y, $longitude_x, $latitude_y){
        $i = $j = $c = 0;
        for ($i = 0, $j = $points_polygon-1; $i < $points_polygon; $j = $i++) {
            if ((($vertices_y[$i] > $latitude_y != ($vertices_y[$j] > $latitude_y)) &&
                ($longitude_x < ($vertices_x[$j] - $vertices_x[$i]) * ($latitude_y - $vertices_y[$i]) / ($vertices_y[$j] - $vertices_y[$i]) + $vertices_x[$i])))
                $c = !$c;
        }
        return $c;
    }

    public function checkPromoCodeValid($promo_id, $amount, $user_id, $service_cat_id)
    {
        $get_user_used_count = UsedPromocodeDetails::where('user_id', $user_id)->where('id', $promo_id)->where('service_cat_id', $service_cat_id)->count();
        $get_user_used = UsedPromocodeDetails::where('user_id', $user_id)->where('id', $promo_id)->where('service_cat_id', $service_cat_id)->first();
        $get_promocode = PromocodeDetails::where('id', $get_user_used->id)->where('service_cat_id', $service_cat_id)->where('status', 1)->first();
        if ($get_promocode != Null) {
            if ($get_promocode->min_order_amount != Null) {
                if ($get_promocode->min_order_amount <= $amount) {
                    if ($get_promocode->discount_type == 1) {
                        $new_price = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);
                    } elseif ($get_promocode->discount_type == 2) {
                        $new_price = round((($amount) - (($amount) * ($get_promocode->discount_amount)) / (100)), 2);
                    } else {
                        return 0;
                    }
                    if ($get_promocode->max_discount_amount != Null) {
                        if ($get_promocode->max_discount_amount < $new_price) {
                            return round($get_promocode->max_discount_amount, 2);
                        } else {
                            return round($new_price, 2);
                        }
                    } else {
                        return round($new_price, 2);
                    }
                } else {
                    return 1;
                }
            } else {
                if ($get_promocode->discount_type == 1) {
                    $new_price = round($amount - (($amount) - ($get_promocode->discount_amount)), 2);
                } elseif ($get_promocode->discount_type == 2) {
                    $new_price = round((($amount) - (($amount) * ($get_promocode->discount_amount)) / (100)), 2);
                } else {
                    return 0;
                }
                if ($get_promocode->max_discount_amount != Null) {
                    if ($get_promocode->max_discount_amount < $new_price) {
                        return round($get_promocode->max_discount_amount, 2);
                    } else {
                        return round($new_price, 2);
                    }
                } else {
                    return round($new_price, 2);
                }
            }
        } else {
            return 0;
        }
    }

    public function emailTemplates($data,$id){
        $template = EmailTemplates::query()->where('id',$id)->where('status',1)->first();
        if($template != Null){
            if($template->id == 1){
                $html = str_replace(['#user_name#'],$data,$template->content);
            } else if($template->id == 2){
                $html = str_replace(['#user_name#','#service_name#'],$data,$template->content);
            } else if($template->id == 3){
                $html = str_replace(['#user_name#','#driver_name#'],$data,$template->content);
            } else if($template->id == 4){
                $html = str_replace(['#rider#','#service_name#','#ride_id#'],$data,$template->content);
            } else if($template->id == 5){
                $html = str_replace(['#user_name#','#store_name#'],$data,$template->content);
            } else if($template->id == 6){
                $html = str_replace(['#cancel_by#','#service_name#'],$data,$template->content);
            } else if($template->id == 7){
                $html = str_replace(['#service_name#','#order_id#','#address#','#driver_name#','#time#'],$data,$template->content);
            } else if($template->id == 8){
                $html = str_replace(['#user_name#','#provider_name#','#service_name#'],$data,$template->content);
            } else if($template->id == 9){
                $html = str_replace(['#user_name#','#provider_name#','#service_name#'],$data,$template->content);
            } else if($template->id == 10){
                $html = str_replace(['#user_name#','#provider_name#','#service_name#','#date_time#'],$data,$template->content);
            } else if($template->id == 11){
                $html = str_replace(['#user_name#'],$data,$template->content);
            } else if($template->id == 12){
                $html = str_replace(['#driver_name#'],$data,$template->content);
            } else if($template->id == 13){
                $html = str_replace(['#driver_name#'],$data,$template->content);
            } else if($template->id == 14){
                $html = str_replace(['#driver_name#','#service_name#','#user_name#','#pickup_location#','#destination_location#','#pickup_time#'],$data,$template->content);
            } else if($template->id == 15){
                $html = str_replace(['#driver_name#','#service_name#','#ride_id#','#date_time#'],$data,$template->content);
            } else if($template->id == 16){
                $html = str_replace(['#driver_name#','#service_name#','#order_id#'],$data,$template->content);
            } else if($template->id == 17){
                $html = str_replace(['#driver_name#'],$data,$template->content);
            } else if($template->id == 18){
                $html = str_replace(['#store_name#'],$data,$template->content);
            } else if($template->id == 19){
                $html = str_replace(['#store_name#'],$data,$template->content);
            } else if($template->id == 20){
                $html = str_replace(['#store_name#','#user_name#','#product_details#','#total#'],$data,$template->content);
            } else if($template->id == 21){
                $html = str_replace(['#store_name#'],$data,$template->content);
            } else if($template->id == 22){
                $html = str_replace(['#provider_name#'],$data,$template->content);
            } else if($template->id == 23){
                $html = str_replace(['#provider_name#'],$data,$template->content);
            } else if($template->id == 24){
                $html = str_replace(['#provider_name#'],$data,$template->content);
            } else if($template->id == 25){
                $html = str_replace(['#provider_name#','#service_name#','#user_name#'],$data,$template->content);
            } else if($template->id == 26){
                $html = str_replace(['#provider_name#','#service_name#','#time#'],$data,$template->content);
            } else if($template->id == 27){
                $html = str_replace(['#restaurant_name#'],$data,$template->content);
            } else if($template->id == 28){
                $html = str_replace(['#user_name#'],$data,$template->content);
            } else if($template->id == 29){
                $html = str_replace(['#driver_name#'],$data,$template->content);
            } else if($template->id == 30){
                $html = str_replace(['#provider_name#','#services_name#','#email#','#contact_no#'],$data,$template->content);
            } else if($template->id == 31){
                $html = str_replace(['#store_name#','#user_name#','#product_details#','#total#'],$data,$template->content);
            } else if($template->id == 32){
                $html = str_replace(['#provider_name#','#service_name#','#user_name#'],$data,$template->content);
            } else if($template->id == 32){
                $html = str_replace(['#driver_name#','#service_name#','#user_name#','#pickup_location#','#destination_location#','#pickup_time#'],$data,$template->content);
            } else{
                $html = Null;
            }

            $settings = GeneralSettings::query()->select('twitter_link','facebook_link')->first();
            $facebook = $settings->facebook_link != NUll ? $settings->facebook_link : 'https://www.facebook.com';
            $twitter = $settings->twitter_link != NUll ? $settings->twitter_link : 'https://www.twitter.com';

            $html = str_replace(['#facebook#','#twitter#'],[$facebook,$twitter],$html);
            return $html;
        } else{
            return Null;
        }
    }
}
