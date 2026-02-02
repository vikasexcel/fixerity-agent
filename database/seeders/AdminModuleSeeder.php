<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $admin_module_record = [
            [
                'id' => 1,
                'parent_id' => 0,
                'name' => 'Provider Services',
                'module_name' => 'other-services/service-list',
                'match_url' => 'provider-services/service-list',
                'route_path' => 'get:admin:other_service_list',
                'route_path_arr' => '',
                'image' => 'user-plus',
                'module_action' => '1',
                'seq' => 4,
                'status' => '1',
                'module_category_type' => '3,4'
            ],
            [
                'id' => 2,
                'parent_id' => 0,
                'name' => 'Customers',
                'module_name' => 'customer-list',
                'match_url' => 'admin/customer-list',
                'route_path' => 'get:admin:user_list',
                'route_path_arr' => '',
                'image' => 'user-circle',
                'module_action' => '1',
                'seq' => 6,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 3,
                'parent_id' => 0,
                'name' => 'Providers',
                'module_name' => '',
                'match_url' => '',
                'route_path' => 'get:admin:user_list',
                'route_path_arr' => '',
                'image' => 'user',
                'module_action' => '1',
                'seq' => 7,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 4,
                'parent_id' => 0,
                'name' => 'Incomplete Provider Registration',
                'module_name' => 'un-register-provider-list',
                'match_url' => 'admin/un-register-provider-list',
                'route_path' => 'get:admin:un_register_provider_list',
                'route_path_arr' => '',
                'image' => 'user-times',
                'module_action' => '1',
                'seq' => 8,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 5,
                'parent_id' => 0,
                'name' => 'Push Notification',
                'module_name' => 'push-notification',
                'match_url' => 'admin/push-notification',
                'route_path' => 'get:admin:push_notification',
                'route_path_arr' => '',
                'image' => 'bell',
                'module_action' => '1',
                'seq' => 10,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 6,
                'parent_id' => 0,
                'name' => 'World Currency ',
                'module_name' => 'world-currency-list',
                'match_url' => 'admin/world-currency-list',
                'route_path' => 'get:admin:world_currency_list',
                'route_path_arr' => '',
                'image' => 'dollar',
                'module_action' => '1',
                'seq' => 11,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 7,
                'parent_id' => 0,
                'name' => 'Site Settings',
                'module_name' => 'site-setting',
                'match_url' => 'admin/site-setting',
                'route_path' => 'get:admin:general_setting',
                'route_path_arr' => '',
                'image' => 'cogs',
                'module_action' => '1',
                'seq' => 12,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 8,
                'parent_id' => 0,
                'name' => 'Page List',
                'module_name' => 'support-page-list',
                'match_url' => 'admin/support-page-list',
                'route_path' => 'get:admin:support_pages',
                'route_path_arr' => '',
                'image' => 'list',
                'module_action' => '1',
                'seq' => 13,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 9,
                'parent_id' => 3,
                'name' => 'Other Service Providers List',
                'module_name' => 'other-service-providers-list',
                'match_url' => 'provider-services/provider-list',
                'route_path' => 'get:admin:provider_list',
                'route_path_arr' => 'provider-services',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 17,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 10,
                'parent_id' => 0,
                'name' => 'Pending Provider List',
                'module_name' => 'pending-provider-list',
                'match_url' => 'admin/pending-provider-list',
                'route_path' => 'get:admin:pending_provider_list',
                'route_path_arr' => '',
                'image' => 'user-secret',
                'module_action' => '1',
                'seq' => 9,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 11,
                'parent_id' => 10,
                'name' => 'Other Service Providers List',
                'module_name' => 'other-service-providers-list',
                'match_url' => 'provider-services/provider-list',
                'route_path' => 'get:admin:pending_other_provider_list',
                'route_path_arr' => '',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 21,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 12,
                'parent_id' => 19,
                'name' => 'Home Page banner',
                'module_name' => 'home-page-banner-list',
                'match_url' => 'admin/home-page-banner-list',
                'route_path' => 'get:admin:home_page_banner_list',
                'route_path_arr' => '',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 22,
                'status' => '0',
                'module_category_type' => ''
            ],
            [
                'id' => 13,
                'parent_id' => 0,
                'name' => 'Geo Fencing',
                'module_name' => 'restricted-area-list',
                'match_url' => 'restricted-area-list',
                'route_path' => 'get:admin:restricted_area_list',
                'route_path_arr' => '',
                'image' => 'area-chart',
                'module_action' => '1',
                'seq' => 8,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 14,
                'parent_id' => 0,
                'name' => 'Email Templates',
                'module_name' => 'email-templates',
                'match_url' => 'admin/email-templates',
                'route_path' => 'get:admin:email_templates',
                'route_path_arr' => '',
                'image' => 'envelope',
                'module_action' => '1',
                'seq' => 23,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 15,
                'parent_id' => 0,
                'name' => 'Language Lists',
                'module_name' => 'language-lists',
                'match_url' => 'admin/language-lists',
                'route_path' => 'get:admin:language_lists',
                'route_path_arr' => '',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 24,
                'status' => '0',
                'module_category_type' => ''
            ],
            [
                'id' => 16,
                'parent_id' => 0,
                'name' => 'Language Constant',
                'module_name' => 'language-constant',
                'match_url' => 'admin/language-constant',
                'route_path' => 'get:admin:language_constant',
                'route_path_arr' => '',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 25,
                'status' => '0',
                'module_category_type' => ''
            ],
            [
                'id' => 17,
                'parent_id' => 19,
                'name' => 'Home Page Slider',
                'module_name' => 'home-page-slider-list',
                'match_url' => 'admin/home-page-slider-list',
                'route_path' => 'get:admin:home_page_slider_list',
                'route_path_arr' => '',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 22,
                'status' => '0',
                'module_category_type' => ''
            ],
            [
                'id' => 18,
                'parent_id' => 19,
                'name' => 'Ordering Service',
                'module_name' => 'ordering-service-category-list',
                'match_url' => 'admin/ordering-service-category-list',
                'route_path' => 'get:admin:ordering_service_category_list',
                'route_path_arr' => '',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 29,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 19,
                'parent_id' => 0,
                'name' => 'App Home Page Setting',
                'module_name' => '',
                'match_url' => '',
                'route_path' => 'get:admin:home_page_banner_list',
                'route_path_arr' => '',
                'image' => 'cog',
                'module_action' => '1',
                'seq' => 22,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 20,
                'parent_id' => 19,
                'name' => 'Spot Light Provider List',
                'module_name' => 'home-page-sopt-light-list',
                'match_url' => 'admin/home-page-spot-light-list',
                'route_path' => 'get:admin:home_page_spot_light_list',
                'route_path_arr' => '',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 31,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 21,
                'parent_id' => 0,
                'name' => 'App Version Setting',
                'module_name' => 'app-version-setting',
                'match_url' => 'admin/app-version-setting',
                'route_path' => 'get:admin:app_version_setting',
                'route_path_arr' => '',
                'image' => 'wrench',
                'module_action' => '1',
                'seq' => 12,
                'status' => '1',
                'module_category_type' => ''
            ],
            //cash-out module
            [
                'id' => 22,
                'parent_id' => 0,
                'name' => "Cash Outs",
                'module_name' => '/cash-out',
                'match_url' => '/cash-out',
                'route_path' => 'get:admin:provider_cash_out_list',
                'route_path_arr' => '',
                'image' => 'fas fa-money-bill-wave',
                'module_action' => 1,
                'seq' => 8,
                'status' => 1,
                'module_category_type' => ''
            ],
            //Report Issue
            [
                'id' => 23,
                'parent_id' => 0,
                'name' => 'Report Issue',
                'module_name' => '',
                'match_url' => '',
                'route_path' => '',
                'route_path_arr' => '',
                'image' => 'fa-solid fa-file',
                'module_action' => '1',
                'seq' => 32,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 24,
                'parent_id' => 23,
                'name' => 'Customer Report Issues',
                'module_name' => 'customer-report-issues',
                'match_url' => 'admin/report-issue/customer',
                'route_path' => 'get:admin:report_issue',
                'route_path_arr' => 'customer',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 32,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 25,
                'parent_id' => 23,
                'name' => 'Provider Report Issues',
                'module_name' => 'provider-report-issues',
                'match_url' => 'admin/report-issue/provider',
                'route_path' => 'get:admin:report_issue',
                'route_path_arr' => 'provider',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 32,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 26,
                'parent_id' => 23,
                'name' => 'Report issue settings',
                'module_name' => 'report-issue-setting',
                'match_url' => 'admin/report-issue/setting',
                'route_path' => 'get:admin:report_issue_setting',
                'route_path_arr' => '',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 32,
                'status' => '1',
                'module_category_type' => ''
            ],
            [
                'id' => 27,
                'parent_id' => 23,
                'name' => 'FAQs',
                'module_name' => 'FAQs',
                'match_url' => 'admin/report-issue/faqs/manage',
                'route_path' => 'get:admin:faqs',
                'route_path_arr' => '',
                'image' => 'icon-home',
                'module_action' => '1',
                'seq' => 32,
                'status' => '1',
                'module_category_type' => ''
            ]
        ];

        /*
        | upsert
        |--------------------------------------------------------------------------
        | We are using upsert here as it functions to either insert or update records efficiently.
        | If a record already exists, it updates it; if not, it inserts a new record.
        | This operation compares records using a unique key and supports handling multiple records in a single operation.
        */
        DB::table('admin_module')->upsert(
            $admin_module_record,
            ['id'], // Unique column to determine if a row exists
            ['parent_id','name', 'module_name', 'match_url', 'route_path', 'route_path_arr', 'image', 'module_action', 'seq', 'status', 'module_category_type']
        );
    }
}
