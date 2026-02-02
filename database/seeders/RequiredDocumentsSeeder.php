<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RequiredDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $required_documents_record =[
            [
                'id' => 1,
                'name' => 'Driver’s License',
                'service_cat_id' => 1,
                'status' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Driver’s License',
                'service_cat_id' => 2,
                'status' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Driver’s License',
                'service_cat_id' => 4,
                'status' => 1,
            ],
            [
                'id' => 4,
                'name' => 'Restaurant License',
                'service_cat_id' => 5,
                'status' => 1,
            ],
            [
                'id' => 5,
                'name' => 'Grocery License',
                'service_cat_id' => 6,
                'status' => 1,
            ],
            [
                'id' => 6,
                'name' => 'Pet Care Certificate',
                'service_cat_id' => 11,
                'status' => 1,
            ],
            [
                'id' => 7,
                'name' => 'Insurance',
                'service_cat_id' => 1,
                'status' => 1,
            ],
            [
                'id' => 8,
                'name' => 'ID Card',
                'service_cat_id' => 16,
                'status' => 1,
            ],
            [
                'id' => 9,
                'name' => 'Profession Card',
                'service_cat_id' => 16,
                'status' => 1,
            ],
            [
                'id' => 10,
                'name' => 'Profession Card',
                'service_cat_id' => 14,
                'status' => 1,
            ],
            [
                'id' => 11,
                'name' => 'ID Card',
                'service_cat_id' => 14,
                'status' => 1,
            ],
            [
                'id' => 12,
                'name' => 'Insurance',
                'service_cat_id' => 2,
                'status' => 1,
            ],
            [
                'id' => 15,
                'name' => 'new version pet certi',
                'service_cat_id' => 11,
                'status' => 0,
            ],
            [
                'id' => 17,
                'name' => 'Massage Service License',
                'service_cat_id' => 18,
                'status' => 1,
            ],
            [
                'id' => 18,
                'name' => 'Shop Document',
                'service_cat_id' => 18,
                'status' => 1,
            ],  [
                'id' => 19,
                'name' => 'Photo id',
                'service_cat_id' => 30,
                'status' => 1,
            ],  [
                'id' => 20,
                'name' => 'Driver Qualification',
                'service_cat_id' => 30,
                'status' => 1,
            ],  [
                'id' => 21,
                'name' => 'Driving License',
                'service_cat_id' => 30,
                'status' => 1,
            ],  [
                'id' => 22,
                'name' => 'Aadhar Card',
                'service_cat_id' => 30,
                'status' => 1,
            ],  [
                'id' => 23,
                'name' => 'PAN Card',
                'service_cat_id' => 30,
                'status' => 1,
            ],  [
                'id' => 24,
                'name' => 'Medical card',
                'service_cat_id' => 30,
                'status' => 1,
            ],  [
                'id' => 25,
                'name' => 'Voters ID Card',
                'service_cat_id' => 30,
                'status' => 1,
            ],  [
                'id' => 26,
                'name' => 'Training Certificate',
                'service_cat_id' => 30,
                'status' => 1,
            ],
            [
                'id' => 27,
                'name' => 'LIC bond',
                'service_cat_id' => 30,
                'status' => 1,
            ],
            [
                'id' => 38,
                'name' => 'certificate',
                'service_cat_id' => 57,
                'status' => 1
            ],
            [
                'id' => 39,
                'name' => 'licence',
                'service_cat_id' => 12,
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
        DB::table('required_documents')->upsert(
            $required_documents_record,
            ['id'], // Unique column to determine if a row exists
            ['name','service_cat_id','status']
        );
    }
}
