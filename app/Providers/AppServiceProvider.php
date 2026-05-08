<?php

namespace App\Providers;

use App\Models\GeneralSettings;
use App\Models\LanguageConstant;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    protected $general_settings;
    protected $chat_replace_domain;

    protected function logEnvForDebug(): void

    {

        $conn = config('database.default');

        $dbConfig = config("database.connections.{$conn}", []);

        $env = [

            'APP_ENV' => config('app.env'),

            'APP_DEBUG' => config('app.debug'),

            'DB_CONNECTION' => $conn,

            'DB_HOST' => $dbConfig['host'] ?? '(none)',

            'DB_PORT' => $dbConfig['port'] ?? '(none)',

            'DB_DATABASE' => $dbConfig['database'] ?? '(none)',

            'DB_USERNAME' => $dbConfig['username'] ?? '(none)',

            'DB_PASSWORD' => (!empty($dbConfig['password'])) ? '(set)' : '(empty)',

        ];

        $msg = '[ENV] ' . json_encode($env, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        Log::info($msg);

        if (App::runningInConsole()) {

            echo "\n" . $msg . "\n";

            // DB connection test removed — was causing hang on boot

            // try {

            //     DB::connection()->getPdo();

            //     echo "[ENV] DB connection OK.\n\n";

            // } catch (\Throwable $e) {

            //     echo "[ENV] DB connection FAILED: " . $e->getMessage() . "\n\n";

            //     Log::warning('[ENV] DB connection failed: ' . $e->getMessage());

            // }

        }

    }

    public function boot()

    {

        $this->logEnvForDebug();

        if (config('app.debug')) {

            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        }

        Schema::defaultStringLength(191);

    }



    public function register()
    {
        //
    }
}


