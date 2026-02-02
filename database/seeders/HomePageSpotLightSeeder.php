<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomePageSpotLightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $home_page_spot_light_record = [
            [
                'id' => 1,
                'service_cat_id' => 11,
                'provider_id' => 389,
                'status' => 1
            ],
            [
                'id' => 2,
                'service_cat_id' => 16,
                'provider_id' => 390,
                'status' => 1
            ],
            [
                'id' => 3,
                'service_cat_id' => 16,
                'provider_id' => 302,
                'status' => 1
            ],
            [
                'id' => 4,
                'service_cat_id' => 15,
                'provider_id' => 6,
                'status' => 1
            ],
            [
                'id' => 5,
                'service_cat_id' => 11,
                'provider_id' => 403,
                'status' => 1
            ],
            [
                'id' => 6,
                'service_cat_id' => 16,
                'provider_id' => 418,
                'status' => 0
            ],
            [
                'id' => 7,
                'service_cat_id' => 16,
                'provider_id' => 426,
                'status' => 1
            ],
            [
                'id' => 8,
                'service_cat_id' => 11,
                'provider_id' => 433,
                'status' => 1
            ],
            [
                'id' => 9,
                'service_cat_id' => 52,
                'provider_id' => 435,
                'status' => 0
            ],
            [
                'id' => 10,
                'service_cat_id' => 11,
                'provider_id' => 441,
                'status' => 1
            ],
            [
                'id' => 11,
                'service_cat_id' => 11,
                'provider_id' => 4,
                'status' => 1
            ],
        ];

        /*
       | upsert
       |--------------------------------------------------------------------------
       | We are using upsert here as it functions to either insert or update records efficiently.
       | If a record already exists, it updates it; if not, it inserts a new record.
       | This operation compares records using a unique key and supports handling multiple records in a single operation.
       */
        DB::table('home_page_spot_light')->upsert(
            $home_page_spot_light_record,
            ['id'], // Unique column to determine if a row exists
            ['service_cat_id', 'provider_id', 'status']
        );
    }
}
