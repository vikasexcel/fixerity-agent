<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Complete Provider Seeder
 * 
 * Creates a fully configured provider with all data needed for job scanning:
 * - Provider account (verified, active)
 * - Provider details (location, rating, stats)
 * - Provider services (at least one service category)
 * - Provider packages (pricing)
 * - Provider timings (availability)
 * 
 * Usage: php artisan db:seed --class=CompleteProviderSeeder
 * 
 * After seeding, login to get access_token:
 * POST /api/on-demand/login
 * {
 *   "login_type": "email",
 *   "email": "testprovider@example.com",
 *   "password": "password123",
 *   ...
 * }
 */
class CompleteProviderSeeder extends Seeder
{
    private const PROVIDER_EMAIL = 'testprovider@example.com';
    private const PROVIDER_PASSWORD = 'password123';
    private const PROVIDER_FIRST_NAME = 'Test';
    private const PROVIDER_LAST_NAME = 'Provider';
    private const PROVIDER_CONTACT = '1234567890';
    
    // Location (adjust as needed)
    private const PROVIDER_LAT = 22.3;
    private const PROVIDER_LONG = 70.8;
    private const PROVIDER_ADDRESS = '124 Oak Street';
    private const PROVIDER_SERVICE_RADIUS = 25; // km
    
    // Test buyer for creating jobs
    private const TEST_BUYER_EMAIL = 'testbuyer@example.com';
    private const TEST_BUYER_PASSWORD = 'password123';
    private const TEST_BUYER_ID = 9000; // Use a specific ID to avoid conflicts
    
    // Service category (will auto-select first available if not specified)
    private const SERVICE_CATEGORY_ID = null; // Set to a specific ID, or null to auto-select
    
    public function run(): void
    {
        $now = Carbon::now();
        
        // Get available service categories
        $availableCategories = DB::table('service_category')
            ->whereIn('category_type', [3, 4])
            ->where('status', 1)
            ->select('id', 'name')
            ->orderBy('id')
            ->get();
            
        if ($availableCategories->isEmpty()) {
            $this->command->error('No active service categories found. Please seed service categories first.');
            return;
        }
        
        // Use specified category or auto-select first available
        $serviceCategoryId = self::SERVICE_CATEGORY_ID;
        if ($serviceCategoryId === null) {
            $serviceCategoryId = $availableCategories->first()->id;
            $this->command->info("Auto-selected service category: {$availableCategories->first()->name} (ID: {$serviceCategoryId})");
        }
        
        $serviceCategory = $availableCategories->firstWhere('id', $serviceCategoryId);
        
        if (!$serviceCategory) {
            $this->command->error('Service category ' . $serviceCategoryId . ' not found or inactive.');
            $this->command->info('Available service categories:');
            foreach ($availableCategories as $cat) {
                $this->command->info("  ID: {$cat->id}, Name: {$cat->name}");
            }
            return;
        }
        
        $serviceCategoryId = (int) $serviceCategoryId; // Ensure it's an integer
        
        // Check if provider already exists
        $existingProvider = DB::table('providers')
            ->where('email', self::PROVIDER_EMAIL)
            ->first();
            
        if ($existingProvider) {
            $providerId = $existingProvider->id;
            $this->command->warn("Provider with email " . self::PROVIDER_EMAIL . " already exists (ID: {$providerId}). Updating...");
            
            // Update provider
            DB::table('providers')
                ->where('id', $providerId)
                ->update([
                    'first_name' => self::PROVIDER_FIRST_NAME,
                    'last_name' => self::PROVIDER_LAST_NAME,
                    'password' => Hash::make(self::PROVIDER_PASSWORD),
                    'contact_number' => self::PROVIDER_CONTACT,
                    'status' => 1,
                    'verified_at' => $now,
                    'service_radius' => self::PROVIDER_SERVICE_RADIUS,
                    'updated_at' => $now,
                ]);
        } else {
            // Create provider
            $providerId = DB::table('providers')->insertGetId([
                'first_name' => self::PROVIDER_FIRST_NAME,
                'last_name' => self::PROVIDER_LAST_NAME,
                'email' => self::PROVIDER_EMAIL,
                'password' => Hash::make(self::PROVIDER_PASSWORD),
                'contact_number' => self::PROVIDER_CONTACT,
                'provider_type' => 3,
                'status' => 1,
                'verified_at' => $now,
                'service_radius' => self::PROVIDER_SERVICE_RADIUS,
                'gender' => 1,
                'country_code' => '+1',
                'currency' => 'USD',
                'language' => 'en',
                'login_type' => 'email',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->command->info("Created provider with ID: {$providerId}");
        }
        
        // Create or update provider details
        $detailsExists = DB::table('other_service_provider_details')
            ->where('provider_id', $providerId)
            ->exists();
            
        if ($detailsExists) {
            DB::table('other_service_provider_details')
                ->where('provider_id', $providerId)
                ->update([
                    'address' => self::PROVIDER_ADDRESS,
                    'lat' => self::PROVIDER_LAT,
                    'long' => self::PROVIDER_LONG,
                    'rating' => 4.5,
                    'total_completed_order' => 25,
                    'time_slot_status' => 1,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('other_service_provider_details')->insert([
                'provider_id' => $providerId,
                'address' => self::PROVIDER_ADDRESS,
                'lat' => self::PROVIDER_LAT,
                'long' => self::PROVIDER_LONG,
                'rating' => 4.5,
                'total_completed_order' => 25,
                'time_slot_status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        // Create or update provider service
        $serviceExists = DB::table('provider_services')
            ->where('provider_id', $providerId)
            ->where('service_cat_id', $serviceCategoryId)
            ->first();
            
        if ($serviceExists) {
            $providerServiceId = $serviceExists->id;
            DB::table('provider_services')
                ->where('id', $providerServiceId)
                ->update([
                    'current_status' => 1,
                    'status' => 1,
                    'updated_at' => $now,
                ]);
        } else {
            $providerServiceId = DB::table('provider_services')->insertGetId([
                'provider_id' => $providerId,
                'service_cat_id' => $serviceCategoryId,
                'current_status' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        // Get sub-categories for this service category
        $subCategories = DB::table('other_service_sub_category')
            ->where('service_cat_id', $serviceCategoryId)
            ->where('status', 1)
            ->limit(3) // Create packages for up to 3 sub-categories
            ->get();
            
        if ($subCategories->isEmpty()) {
            $this->command->warn('No sub-categories found for service category ' . self::SERVICE_CATEGORY_ID);
        } else {
            // Create packages for each sub-category
            foreach ($subCategories as $index => $subCat) {
                $packageExists = DB::table('other_service_provider_packages')
                    ->where('provider_service_id', $providerServiceId)
                    ->where('sub_cat_id', $subCat->id)
                    ->exists();
                    
                if (!$packageExists) {
                    $price = 50.00 + ($index * 10); // $50, $60, $70
                    DB::table('other_service_provider_packages')->insert([
                    'provider_service_id' => $providerServiceId,
                    'sub_cat_id' => $subCat->id,
                    'service_cat_id' => $serviceCategoryId,
                    'name' => 'Standard ' . ($subCat->name ?? 'Package'),
                        'description' => 'Includes initial assessment and one session.',
                        'price' => $price,
                        'max_book_quantity' => 1,
                        'status' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
        
        // Create provider timings (all days)
        $days = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
        foreach ($days as $day) {
            $timingExists = DB::table('other_service_provider_timings')
                ->where('provider_id', $providerId)
                ->where('day', $day)
                ->exists();
                
            if (!$timingExists) {
                DB::table('other_service_provider_timings')->insert([
                    'provider_id' => $providerId,
                    'day' => $day,
                    'open_time_list' => '09:00:00,17:00:00',
                    'provider_open_time' => '09:00:00',
                    'provider_close_time' => '17:00:00',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        
        // Generate access token
        $accessToken = random_int(1, 99) . date('siHYdm') . random_int(1, 99);
        DB::table('providers')
            ->where('id', $providerId)
            ->update(['access_token' => $accessToken]);
        
        // Create test buyer and jobs
        $this->createTestBuyerAndJobs($serviceCategoryId, $serviceCategory, $now);
        
        $this->command->info("\n✅ Provider setup complete!");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("Provider ID: {$providerId}");
        $this->command->info("Email: " . self::PROVIDER_EMAIL);
        $this->command->info("Password: " . self::PROVIDER_PASSWORD);
        $this->command->info("Access Token: {$accessToken}");
        $this->command->info("Service Category: {$serviceCategory->name} (ID: {$serviceCategoryId})");
        $this->command->info("Location: " . self::PROVIDER_ADDRESS . " (" . self::PROVIDER_LAT . ", " . self::PROVIDER_LONG . ")");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("\nTo login, use:");
        $this->command->info("curl -X POST http://localhost:8000/api/on-demand/login \\");
        $this->command->info("  -H \"Content-Type: application/json\" \\");
        $this->command->info("  -H \"select-time-zone: UTC\" \\");
        $this->command->info("  -d '{\"login_type\":\"email\",\"email\":\"" . self::PROVIDER_EMAIL . "\",\"password\":\"" . self::PROVIDER_PASSWORD . "\",\"device_token\":\"test\",\"select_language\":\"en\",\"select_country_code\":\"+1\",\"select_currency\":\"USD\"}'");
    }
    
    /**
     * Create test buyer account and jobs for the provider's service category.
     */
    private function createTestBuyerAndJobs(int $serviceCategoryId, object $serviceCategory, Carbon $now): void
    {
        // Check if buyer_jobs table exists
        if (!Schema::hasTable('buyer_jobs')) {
            $this->command->warn('buyer_jobs table does not exist. Skipping job creation.');
            return;
        }
        
        // Create or update test buyer
        $buyerExists = DB::table('users')
            ->where('id', self::TEST_BUYER_ID)
            ->exists();
            
        if ($buyerExists) {
            DB::table('users')
                ->where('id', self::TEST_BUYER_ID)
                ->update([
                    'first_name' => 'Test',
                    'last_name' => 'Buyer',
                    'email' => self::TEST_BUYER_EMAIL,
                    'password' => Hash::make(self::TEST_BUYER_PASSWORD),
                    'status' => 1,
                    'verified_at' => $now,
                    'currency' => 'USD',
                    'language' => 'en',
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('users')->insert([
                'id' => self::TEST_BUYER_ID,
                'first_name' => 'Test',
                'last_name' => 'Buyer',
                'email' => self::TEST_BUYER_EMAIL,
                'password' => Hash::make(self::TEST_BUYER_PASSWORD),
                'status' => 1,
                'verified_at' => $now,
                'currency' => 'USD',
                'language' => 'en',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        // Generate buyer access token
        $buyerAccessToken = random_int(1, 99) . date('siHYdm') . random_int(1, 99);
        DB::table('users')
            ->where('id', self::TEST_BUYER_ID)
            ->update(['access_token' => $buyerAccessToken]);
        
        // Get sub-categories for the service category
        $subCategories = DB::table('other_service_sub_category')
            ->where('service_cat_id', $serviceCategoryId)
            ->where('status', 1)
            ->limit(2)
            ->get();
            
        $subCategoryId = $subCategories->isNotEmpty() ? $subCategories->first()->id : null;
        
        // Create test jobs (3-5 jobs with different priorities and budgets)
        $jobTitles = [
            'Urgent ' . ($serviceCategory->name ?? 'Service') . ' Needed',
            'Regular ' . ($serviceCategory->name ?? 'Service') . ' Request',
            'Weekly ' . ($serviceCategory->name ?? 'Service') . ' Service',
            'One-time ' . ($serviceCategory->name ?? 'Service') . ' Project',
            'Long-term ' . ($serviceCategory->name ?? 'Service') . ' Contract',
        ];
        
        $jobDescriptions = [
            'Looking for an experienced professional to help with this project.',
            'Need reliable service provider for regular maintenance.',
            'Seeking qualified professional for immediate start.',
            'One-time project requiring attention to detail.',
            'Long-term partnership opportunity for the right provider.',
        ];
        
        $jobsCreated = 0;
        foreach ($jobTitles as $index => $title) {
            // Vary location slightly (within provider's service radius)
            $jobLat = self::PROVIDER_LAT + (($index % 3 - 1) * 0.01); // ±0.01 degrees (~1km)
            $jobLong = self::PROVIDER_LONG + (($index % 3 - 1) * 0.01);
            
            // Vary budgets
            $budgetMin = 100 + ($index * 50);
            $budgetMax = 500 + ($index * 100);
            
            // Create priorities based on index
            $priorities = [];
            if ($index === 0) {
                // Urgent job - high rating requirement
                $priorities[] = [
                    'type' => 'rating',
                    'level' => 'must_have',
                    'value' => 4.0,
                    'description' => 'Minimum 4.0 star rating required',
                ];
            }
            if ($index === 1) {
                // Regular job - jobs completed requirement
                $priorities[] = [
                    'type' => 'jobsCompleted',
                    'level' => 'nice_to_have',
                    'value' => 10,
                    'description' => 'At least 10 completed jobs preferred',
                ];
            }
            if ($index === 2) {
                // Licensed requirement
                $priorities[] = [
                    'type' => 'licensed',
                    'level' => 'must_have',
                    'value' => true,
                    'description' => 'Must be licensed',
                ];
            }
            // Add price priority for some jobs
            if ($index % 2 === 0) {
                $priorities[] = [
                    'type' => 'price',
                    'level' => 'nice_to_have',
                    'value' => $budgetMax,
                    'description' => 'Budget-friendly pricing preferred',
                ];
            }
            
            // Check if job already exists
            $jobExists = DB::table('buyer_jobs')
                ->where('user_id', self::TEST_BUYER_ID)
                ->where('title', $title)
                ->exists();
                
            if (!$jobExists) {
                DB::table('buyer_jobs')->insert([
                    'user_id' => self::TEST_BUYER_ID,
                    'title' => $title,
                    'description' => $jobDescriptions[$index] ?? $jobDescriptions[0],
                    'budget_min' => $budgetMin,
                    'budget_max' => $budgetMax,
                    'start_date' => $now->copy()->addDays($index + 1)->format('Y-m-d'),
                    'end_date' => $now->copy()->addDays($index + 7)->format('Y-m-d'),
                    'service_category_id' => $serviceCategoryId,
                    'sub_category_id' => $subCategoryId,
                    'lat' => $jobLat,
                    'long' => $jobLong,
                    'status' => 'open',
                    'priorities' => json_encode($priorities),
                    'created_at' => $now->copy()->subDays($index),
                    'updated_at' => $now,
                ]);
                $jobsCreated++;
            }
        }
        
        if ($jobsCreated > 0) {
            $this->command->info("\n✅ Created {$jobsCreated} test jobs for service category: {$serviceCategory->name}");
            $this->command->info("Test Buyer:");
            $this->command->info("  ID: " . self::TEST_BUYER_ID);
            $this->command->info("  Email: " . self::TEST_BUYER_EMAIL);
            $this->command->info("  Password: " . self::TEST_BUYER_PASSWORD);
            $this->command->info("  Access Token: {$buyerAccessToken}");
            $this->command->info("\n💡 These jobs will be visible to sellers when they scan for jobs in this service category.");
        } else {
            $this->command->info("\nℹ️  Jobs already exist for this buyer, skipping job creation.");
        }
    }
}
