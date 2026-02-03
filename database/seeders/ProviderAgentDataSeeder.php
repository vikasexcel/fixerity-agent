<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Provider Agent Data Seeder (provider 8002 only)
 *
 * Ensures all data required for the buyer agent to work successfully exists
 * for provider id 8002 (Morlod, morload@mailinator.com) only.
 *
 * What the seeder does:
 * - Ensures provider 8002 exists (creates with Morlod / morload@mailinator.com if missing).
 * - Backfills only for provider 8002: other_service_provider_details, provider_services,
 *   other_service_provider_packages, other_service_provider_timings.
 * - Optional: test customer (users) + user_address for E2E; one other_service_rating for match scoring.
 *
 * Data inserted (all for provider 8002):
 * - providers: create if id 8002 missing; else ensure status=1, service_radius set.
 * - other_service_provider_details: lat/long, rating, total_completed_order, time_slot_status.
 * - provider_services: link to service_cat_id.
 * - other_service_provider_packages: at least one package for agent budget matching.
 * - other_service_provider_timings: so getProviderSelectedTiming returns true.
 * - users + user_address (optional): test customer for provider-list / match API.
 * - other_service_rating (optional): one rating so average_rating / num_of_rating are set.
 *
 * How to run:
 *   php artisan db:seed --class=ProviderAgentDataSeeder
 *
 * Prerequisites: Run ServiceCategorySeeder and OtherServiceSubCategorySeeder first
 * (or full DatabaseSeeder) so service_category and other_service_sub_category have rows.
 *
 * How to test the buyer agent:
 * 1) Laravel provider-list: POST {LARAVEL_URL}/api/customer/on-demand/provider-list
 *    with user_id, access_token (test customer), service_category_id, sub_category_id, lat, long.
 *    Seeder prefers service_category_id=22, sub_category_id=38. Example: service_category_id=22, sub_category_id=38, lat=22.3, long=70.8.
 * 2) Match API: POST http://localhost:3001/agent/buyer/match with user_id, access_token, job
 *    (job must include priorities array and service_category_id, sub_category_id, lat, long).
 * 3) Frontend: Log in as test customer, create job with same category/location, run agent.
 *
 * Test credentials (if optional test customer is seeded): user_id=2, access_token=652220102026020270.
 */
class ProviderAgentDataSeeder extends Seeder
{
    private const PROVIDER_ID = 8002;
    private const FIRST_NAME = 'Morlod';
    private const EMAIL = 'morload@mailinator.com';
    private const CONTACT_NUMBER = '9723567896';
    private const TEST_LAT = '22.3';
    private const TEST_LONG = '70.8';
    private const TEST_USER_ID = 2;
    private const TEST_ACCESS_TOKEN = '652220102026020270';

    public function run(): void
    {
        $now = Carbon::now();

        // 1. Prerequisites: at least one service_category and one other_service_sub_category.
        // Prefer service_category_id=22 and sub_category_id=38 so buyer match agent (job with category 22/38) finds a provider.
        $serviceCat = DB::table('service_category')->where('id', 22)->where('status', 1)->first();
        if (!$serviceCat) {
            $serviceCat = DB::table('service_category')->where('status', 1)->first();
        }
        if (!$serviceCat) {
            $this->command->warn('No active service_category found. Run ServiceCategorySeeder first.');
            return;
        }
        $serviceCatId = (int) $serviceCat->id;
        $subCat = DB::table('other_service_sub_category')
            ->where('service_cat_id', $serviceCatId)
            ->where('id', 38)
            ->where('status', 1)
            ->first();
        if (!$subCat) {
            $subCat = DB::table('other_service_sub_category')
                ->where('service_cat_id', $serviceCatId)
                ->where('status', 1)
                ->first();
        }
        if (!$subCat) {
            $this->command->warn('No active other_service_sub_category for service_cat_id=' . $serviceCatId . '. Run OtherServiceSubCategorySeeder first.');
            return;
        }
        $subCatId = (int) $subCat->id;

        // 2. Provider 8002: create if missing; else ensure status=1 and service_radius
        $providerExists = DB::table('providers')->where('id', self::PROVIDER_ID)->exists();
        if (!$providerExists) {
            DB::table('providers')->insert([
                'id' => self::PROVIDER_ID,
                'first_name' => self::FIRST_NAME,
                'last_name' => null,
                'email' => self::EMAIL,
                'contact_number' => self::CONTACT_NUMBER,
                'provider_type' => 3,
                'status' => 1,
                'service_radius' => 25,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->command->info('Created provider ' . self::PROVIDER_ID . ' (Morlod).');
        } else {
            DB::table('providers')
                ->where('id', self::PROVIDER_ID)
                ->update([
                    'status' => 1,
                    'service_radius' => 25,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);
            $this->command->info('Updated provider ' . self::PROVIDER_ID . ' (status=1, service_radius=25).');
        }

        // 3. other_service_provider_details for provider 8002
        if (!DB::table('other_service_provider_details')->where('provider_id', self::PROVIDER_ID)->exists()) {
            DB::table('other_service_provider_details')->insert([
                'provider_id' => self::PROVIDER_ID,
                'rating' => 4.5,
                'total_completed_order' => 10,
                'address' => '456 Provider Ave',
                'lat' => self::TEST_LAT,
                'long' => self::TEST_LONG,
                'time_slot_status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 4. provider_services for provider 8002 and chosen service_cat_id
        $providerServiceId = DB::table('provider_services')
            ->where('provider_id', self::PROVIDER_ID)
            ->where('service_cat_id', $serviceCatId)
            ->value('id');
        if (!$providerServiceId) {
            $providerServiceId = DB::table('provider_services')->insertGetId([
                'provider_id' => self::PROVIDER_ID,
                'service_cat_id' => $serviceCatId,
                'current_status' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 5. other_service_provider_packages for this provider_service and sub_cat_id
        $packageId = DB::table('other_service_provider_packages')
            ->where('provider_service_id', $providerServiceId)
            ->where('sub_cat_id', $subCatId)
            ->value('id');
        if (!$packageId) {
            $packageId = DB::table('other_service_provider_packages')->insertGetId([
                'provider_service_id' => $providerServiceId,
                'sub_cat_id' => $subCatId,
                'service_cat_id' => $serviceCatId,
                'name' => 'Basic Package (Agent Seed)',
                'description' => '1 session',
                'price' => 25.00,
                'max_book_quantity' => 5,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 6. other_service_provider_timings for provider 8002 (at least one day)
        if (!DB::table('other_service_provider_timings')->where('provider_id', self::PROVIDER_ID)->where('day', 'MON')->exists()) {
            DB::table('other_service_provider_timings')->insert([
                'provider_id' => self::PROVIDER_ID,
                'day' => 'MON',
                'open_time_list' => '09:00:00,17:00:00',
                'provider_open_time' => '09:00:00',
                'provider_close_time' => '17:00:00',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 7. Optional: test customer (user 2) and user_address for E2E
        $userExists = DB::table('users')->where('id', self::TEST_USER_ID)->exists();
        if (!$userExists) {
            DB::table('users')->insert([
                'id' => self::TEST_USER_ID,
                'first_name' => 'Buyer',
                'last_name' => 'AgentTest',
                'email' => 'buyer-agent-test@example.com',
                'verified_at' => $now,
                'access_token' => (int) self::TEST_ACCESS_TOKEN,
                'currency' => '$',
                'language' => 'en',
                'status' => 1,
                'time_zone' => 'UTC',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('users')->where('id', self::TEST_USER_ID)->update([
                'access_token' => (int) self::TEST_ACCESS_TOKEN,
                'status' => 1,
                'updated_at' => $now,
            ]);
        }
        if (!DB::table('user_address')->where('user_id', self::TEST_USER_ID)->where('status', 1)->exists()) {
            DB::table('user_address')->insert([
                'user_id' => self::TEST_USER_ID,
                'address_type' => 'home',
                'address' => '123 Test St, Agent City',
                'lat_long' => self::TEST_LAT . ',' . self::TEST_LONG,
                'flat_no' => '1',
                'landmark' => 'Near park',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 8. Optional: one other_service_rating for provider 8002 (requires a booking)
        $hasRating = DB::table('other_service_rating')
            ->where('provider_id', self::PROVIDER_ID)
            ->where('status', 1)
            ->exists();
        if (!$hasRating) {
            $orderId = DB::table('user_service_package_booking')
                ->where('user_id', self::TEST_USER_ID)
                ->where('provider_id', self::PROVIDER_ID)
                ->value('id');
            if (!$orderId) {
                $orderNo = (string) (1000000 + self::TEST_USER_ID * 10000 + self::PROVIDER_ID);
                $orderId = DB::table('user_service_package_booking')->insertGetId([
                    'user_id' => self::TEST_USER_ID,
                    'provider_id' => self::PROVIDER_ID,
                    'package_id' => $packageId,
                    'service_cat_id' => $serviceCatId,
                    'order_no' => $orderNo,
                    'order_type' => 0,
                    'service_date_time' => $now->copy()->subDays(2),
                    'service_date' => $now->copy()->subDays(2)->format('Y-m-d'),
                    'service_time' => '10:00',
                    'book_start_time' => '09:00:00',
                    'book_end_time' => '10:00:00',
                    'total_item_cost' => 25.00,
                    'tax' => 0,
                    'total_pay' => 25.00,
                    'user_name' => 'Buyer AgentTest',
                    'provider_name' => self::FIRST_NAME,
                    'status' => 9,
                    'payment_status' => 1,
                    'delivery_address' => '123 Test St',
                    'lat_long' => self::TEST_LAT . ',' . self::TEST_LONG,
                    'created_at' => $now->copy()->subDays(3),
                    'updated_at' => $now,
                ]);
            }
            if ($orderId && !DB::table('other_service_rating')->where('booking_id', $orderId)->where('provider_id', self::PROVIDER_ID)->exists()) {
                DB::table('other_service_rating')->insert([
                    'user_id' => self::TEST_USER_ID,
                    'provider_id' => self::PROVIDER_ID,
                    'booking_id' => $orderId,
                    'rating' => 5,
                    'comment' => 'Great service (ProviderAgentDataSeeder)',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('ProviderAgentDataSeeder finished. Provider 8002 (Morlod) is ready for agent. service_category_id=' . $serviceCatId . ', sub_category_id=' . $subCatId . '. Test customer: user_id=' . self::TEST_USER_ID . ', access_token=' . self::TEST_ACCESS_TOKEN);
    }
}
