<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminPageactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin_pageaction_record = [
            [
                'id' => 1,
                'constant' => 'module',
                'name' => 'Module'
            ],
            [
                'id' => 2,
                'constant' => 'add',
                'name' => 'Add'
            ],
        ];
        /*
        | upsert
        |--------------------------------------------------------------------------
        | We are using upsert here as it functions to either insert or update records efficiently.
        | If a record already exists, it updates it; if not, it inserts a new record.
        | This operation compares records using a unique key and supports handling multiple records in a single operation.
        */
        DB::table('admin_pageaction')->upsert(
            $admin_pageaction_record,
            ['id'], // Unique column to determine if a row exists
            ['constant', 'name']
        );
    }
}
