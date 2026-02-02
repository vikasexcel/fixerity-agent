<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $world_currency_record =[
            [
                'id' => 1,
                'currency_name' => 'Japanese yen',
                'ratio' => 151.27,
                'currency_code' => 'JPY',
                'symbol' => '¥',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 2,
                'currency_name' => 'Pound sterling',
                'ratio' => 0.79,
                'currency_code' => 'GBP',
                'symbol' => '£',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 3,
                'currency_name' => 'Australian dollar',
                'ratio' => 1.51,
                'currency_code' => 'AUD',
                'symbol' => 'A$',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 4,
                'currency_name' => 'Canadian dollar',
                'ratio' => 1.36,
                'currency_code' => 'CAD',
                'symbol' => 'C$',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 5,
                'currency_name' => 'Swiss franc',
                'ratio' => 0.9261,
                'currency_code' => 'CHF',
                'symbol' => '₣',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 6,
                'currency_name' => 'Chinese renminbi',
                'ratio' => 7.25,
                'currency_code' => 'CNH',
                'symbol' => 'CN¥',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 7,
                'currency_name' => 'Hong Kong dollar',
                'ratio' => 7.7928,
                'currency_code' => 'HKD',
                'symbol' => 'HK$',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 8,
                'currency_name' => 'Indian rupee',
                'ratio' => 83.33,
                'currency_code' => 'INR',
                'symbol' => '₹',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 9,
                'currency_name' => 'Israeli Shekel',
                'ratio' => 3.69,
                'currency_code' => 'ILS',
                'symbol' => '₪',
                'status' => 1,
                'default_currency' => 0
            ]
        ];
        /*
        | upsert
        |--------------------------------------------------------------------------
        | We are using upsert here as it functions to either insert or update records efficiently.
        | If a record already exists, it updates it; if not, it inserts a new record.
        | This operation compares records using a unique key and supports handling multiple records in a single operation.
        */
        DB::table('world_currency')->upsert(
            $world_currency_record,
            ['id'], // Unique column to determine if a row exists
            ['currency_name', 'ratio', 'currency_code', 'symbol', 'status', 'default_currency']
        );
    }
}
