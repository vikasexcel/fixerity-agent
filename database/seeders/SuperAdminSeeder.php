<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $super_admin_record =[
            [
                'id' => 1,
                'name' => 'Super Admin',
                'email' => 'admin@fixerity.com',
                'password' =>Hash::make('MoJgWi@!731f#8d'),
                'roles' => 1,
                'is_restrict_admin' => 0,
                'admin_type' => 's',
                'access_token' => '92455607201919047',
                'device_token' => 'dcQTZ9T9jB-eV8nyNfa6dg:APA91bHXExn6RxuwCgpcFcLxiTTnD_g77lyjPiIs1ROJU7OMX6ZFSOxu97Gq9IgLGDgQb7eLNNOfqEHKYMlJHaOmCrk4lverQilN8ZCjNyrEY9eI4a1kauro6vmwGauL_5czK5UX-a89',
                'remember_token' => '65hj7mtsNL55ZehizMSJAteOpRapWTDLudn2wnKwTolFTEjOvvAsU0lFXgxp',
            ],
            [
                'id' => 2,
                'name' => 'Billing Account Admin',
                'email' => 'account@fixerity.com',
                'password' =>Hash::make('dk49!G4e!49MQ!T'),
                'roles' => 3,
                'is_restrict_admin' => 0,
                'admin_type' => 's',
                'access_token' => '92455607201919048',
                'device_token' => Null,
                'remember_token' => 'CIR5TrWvypN4UoC1htuwnrsqTni9XQUdQPcALpb4y6xoDZnt2pbuv9vibASv',
            ],           
        ];
        /*
    | upsert
    |--------------------------------------------------------------------------
    | We are using upsert here as it functions to either insert or update records efficiently.
    | If a record already exists, it updates it; if not, it inserts a new record.
    | This operation compares records using a unique key and supports handling multiple records in a single operation.
    */
        DB::table('super_admin')->upsert(
            $super_admin_record,
            ['id'], // Unique column to determine if a row exists
            ['name','email','password','roles','is_restrict_admin','admin_type','access_token','device_token','remember_token']
        );
    }
}
