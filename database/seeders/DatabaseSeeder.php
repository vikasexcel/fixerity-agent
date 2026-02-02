<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            AdminModuleSeeder::class,
            AdminPageactionSeeder::class,
            EmailTemplatesSeeder::class,
            GeneralSettingsSeeder::class,
            HomePageBannerSeeder::class,
            HomePageSpotLightSeeder::class,
            LanguageConstatntSeeder::class,
            LanguageListsSeeder::class,
            PageSettingsSeeder::class,
            PromocodeDetailsSeeder::class,
            RequiredDocumentsSeeder::class,
            ServiceCategorySeeder::class,
            ServiceSettingsSeeder::class,
            SuperAdminSeeder::class,
            WorldCurrencySeeder::class,
            OtherServiceSubCategorySeeder::class,
            ServiceSliderBannerSeeder::class,
        ]);
    }
}
