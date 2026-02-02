<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageListsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $language_lists_record = [
            [
                'id' => 1,
                'language_name' => 'Spanish',
                'language_code' => 'es',
                'language_flag' => Null,
                'status' => 1,
            ],
            [
                'id' => 2,
                'language_name' => 'German',
                'language_code' => 'de',
                'language_flag' => Null,
                'status' => 1,
            ],

            [
                'id' => 3,
                'language_name' => 'Portuguese',
                'language_code' => 'pt',
                'language_flag' => Null,
                'status' => 1,
            ],
            [
                'id' => 4,
                'language_name' => 'French',
                'language_code' => 'fr',
                'language_flag' => Null,
                'status' => 1,
            ]

        ];
        /*
              | upsert
              |--------------------------------------------------------------------------
              | We are using upsert here as it functions to either insert or update records efficiently.
              | If a record already exists, it updates it; if not, it inserts a new record.
              | This operation compares records using a unique key and supports handling multiple records in a single operation.
              */
        DB::table('language_lists')->upsert(
            $language_lists_record,
            ['id'], // Unique column to determine if a row exists
            ['language_name', 'language_code', 'language_flag', 'status']
        );
    }
}
