<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PromocodeDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promocode_details_record = [
            [
                'id' => 1,
                'service_cat_id' => 11,
                'promo_code' => 'DISCOUNT $20',
                'discount_amount' => 20.50,
                'discount_type' => 1,
                'min_order_amount' => 100.00,
                'max_discount_amount' => 30.50,
                'coupon_limit' => 30,
                'usage_limit' => 20,
                'total_usage' => 16,
                'expiry_date_time' => '2025-12-22 00:00:00',
                'description' => 'a',
                'status' => 1,
            ],
            [
                'id' => 2,
                'service_cat_id' => 11,
                'promo_code' => 'PROMOCODE 20',
                'discount_amount' => 20.00,
                'discount_type' => 1,
                'min_order_amount' => 120.00,
                'max_discount_amount' => 20.00,
                'coupon_limit' => 102,
                'usage_limit' => 103,
                'total_usage' => 59,
                'expiry_date_time' => '2025-12-25 10:30:00',
                'description' => 'Order on above 120 get 20$ discount on Every order',
                'status' => 1,
            ],
            [
                'id' => 3,
                'service_cat_id' => 16,
                'promo_code' => 'PROMO 1',
                'discount_amount' => 30.00,
                'discount_type' => 1,
                'min_order_amount' => 10.00,
                'max_discount_amount' => 30.00,
                'coupon_limit' => 100,
                'usage_limit' => 100,
                'total_usage' => 19,
                'expiry_date_time' => '2026-07-08 00:00:00',
                'description' => '1',
                'status' => 1,
            ],
            [
                'id' => 4,
                'service_cat_id' => 16,
                'promo_code' => 'PROMO 2',
                'discount_amount' => 20.00,
                'discount_type' => 2,
                'min_order_amount' => 100.00,
                'max_discount_amount' => 20.00,
                'coupon_limit' => 50,
                'usage_limit' => 50,
                'total_usage' => 3,
                'expiry_date_time' => '2025-12-10 00:00:00',
                'description' => '2',
                'status' => 1,
            ],
           
            [
                'id' => 5,
                'service_cat_id' => 57,
                'promo_code' => 'PROMOCODE10',
                'discount_amount' => 10.00,
                'discount_type' => 2,
                'min_order_amount' => 50.00,
                'max_discount_amount' => 20.00,
                'coupon_limit' => 5,
                'usage_limit' => 2,
                'total_usage' => 0,
                'expiry_date_time' => '2025-12-23 05:30:00',
                'description' => 'Promocode10',
                'status' => 1,
            ],
            [
                'id' => 6,
                'service_cat_id' => 13,
                'promo_code' => 'PROMO 1',
                'discount_amount' => 10.00,
                'discount_type' => 1,
                'min_order_amount' => 100.00,
                'max_discount_amount' => 10.00,
                'coupon_limit' => 2,
                'usage_limit' => 1,
                'total_usage' => 2,
                'expiry_date_time' => '2025-12-29 10:00:00',
                'description' => 'Promo 1 Promo 1 Promo 1',
                'status' => 1,
            ],
           
            [
                'id' => 7,
                'service_cat_id' => 11,
                'promo_code' => 'GET 10% OFF',
                'discount_amount' => 10.00,
                'discount_type' => 2,
                'min_order_amount' => 150.00,
                'max_discount_amount' => 10.00,
                'coupon_limit' => 5,
                'usage_limit' => 4,
                'total_usage' => 1,
                'expiry_date_time' => '2025-12-28 00:00:00',
                'description' => 'dsdsds',
                'status' => 1,
            ],
          
        

        ];
        /*
    | upsert
    |--------------------------------------------------------------------------
    | We are using upsert here as it functions to either insert or update records efficiently.
    | If a record already exists, it updates it; if not, it inserts a new record.
    | This operation compares records using a unique key and supports handling multiple records in a single operation.
    */
        DB::table('promocode_details')->upsert(
            $promocode_details_record,
            ['id'], // Unique column to determine if a row exists
            ['service_cat_id', 'promo_code', 'discount_amount', 'discount_type', 'min_order_amount', 'max_discount_amount', 'coupon_limit', 'usage_limit', 'total_usage', 'expiry_date_time', 'description', 'status']
        );
    }
}
