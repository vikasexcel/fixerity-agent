<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorldCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $world_currency_record = [
            [
                'id' => 1,
                'currency_name' => 'Dollar',
                'ratio' => 1.0000,
                'currency_code' => 'USD',
                'symbol' => '$',
                'status' => 1,
                'default_currency' => 1
            ],
            [
                'id' => 2,
                'currency_name' => 'Canadian Dollar',
                'ratio' => 1.3800, // approx value
                'currency_code' => 'CAD',
                'symbol' => 'C$',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 3,
                'currency_name' => 'Pesos',
                'ratio' => 18.6700, // assuming Mexican Peso (MXN) approx
                'currency_code' => 'MXN',
                'symbol' => '₱',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 4,
                'currency_name' => 'Euro',
                'ratio' => 0.8600, // approx value
                'currency_code' => 'EUR',
                'symbol' => '€',
                'status' => 1,
                'default_currency' => 0
            ],
            [
                'id' => 5,
                'currency_name' => 'Brazilian Real',
                'ratio' => 5.4400, // approx value
                'currency_code' => 'BRL',
                'symbol' => 'R$',
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
