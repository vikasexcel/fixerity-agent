<?php

namespace App\Providers;

use App\Models\GeneralSettings;
use App\Models\LanguageConstant;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    protected $general_settings;
    protected $chat_replace_domain;
    public function boot()
    {
        if (config('app.debug')) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }
        Schema::defaultStringLength(191);
       //URL::forceScheme('https');

//        Paginator::useBootstrap();
//        view()->composer('*',function ($view){
//            if (Cookie::get('current_address')){
//                $current_address =  Cookie::get('current_address');
//            } else {
//                $current_address = "";
//            }
//            $data = array(
//                'current_address'=>$current_address,
//            );
//            $view->with($data);
//        });
//        $lang_constant= LanguageConstant::query()->select()->groupBy('constant_name')->get()->keyBy('constant_name')->toArray();
//        config(['global.lang_constant' => $lang_constant]);
//
//        $get_host = request()->getHost();
//        //$this->domain = preg_replace("/[\s_\-\.]/", "-",$get_host);
//        $this->chat_replace_domain = preg_replace("/[\s_\-\.]/", "-",$get_host);
//
//        $this->general_settings = GeneralSettings::query()->first();
//        view()->composer('*',function($view) {
//            $view->with('general_settings', $this->general_settings);
//            $view->with('chat_replace_domain', $this->chat_replace_domain);
//        });
//        request()->attributes->add(
//            [
//                'general_settings' => $this->general_settings,
//                'chat_replace_domain' => $this->chat_replace_domain
//            ]
//        );
    }

    public function register()
    {
        //
    }
}


