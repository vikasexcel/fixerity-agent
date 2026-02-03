<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Buyer Agent Test Data Seeder
 *
 * Seeds all tables required to test the buyer agent end-to-end:
 * users (customer), user_address, user_wallet_transaction, providers,
 * provider_services, other_service_provider_details, other_service_provider_packages,
 * other_service_provider_timings, user_service_package_booking, user_package_booking_quantity,
 * other_service_rating.
 *
 * Run: php artisan db:seed --class=BuyerAgentTestDataSeeder
 *
 * Test credentials (use with buyer agent / Laravel customer API):
 *   user_id: 2
 *   access_token: 652220102026020270
 * Sample search: service_category_id=12, sub_category_id=2, lat=22.3, long=70.8
 */
class BuyerAgentTestDataSeeder extends Seeder
{
    private const TEST_USER_ID = 2;
    private const TEST_ACCESS_TOKEN = 652220102026020270;
    private const TEST_PROVIDER_ID = 8001;
    private const SERVICE_CAT_ID = 12;
    private const SUB_CAT_ID = 2;

    public function run(): void
    {
        $now = Carbon::now();

        // 1. Ensure test customer (user id 2) exists with access_token and verified_at
        $user = User::find(self::TEST_USER_ID);
        if ($user) {
            $user->access_token = (string) self::TEST_ACCESS_TOKEN;
            $user->verified_at = $user->verified_at ?? $now;
            $user->status = 1;
            $user->currency = '$';
            $user->language = 'en';
            $user->time_zone = 'UTC';
            $user->deleted_at = null;
            $user->save();
        } else {
            DB::table('users')->insert([
                'id' => self::TEST_USER_ID,
                'first_name' => 'Buyer',
                'last_name' => 'AgentTest',
                'email' => 'buyer-agent-test@example.com',
                'verified_at' => $now,
                'access_token' => (string) self::TEST_ACCESS_TOKEN,
                'currency' => '$',
                'language' => 'en',
                'status' => 1,
                'time_zone' => 'UTC',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. user_address for order/address-list (ensure at least one for user 2)
        if (!DB::table('user_address')->where('user_id', self::TEST_USER_ID)->where('status', 1)->exists()) {
            DB::table('user_address')->insert([
                'user_id' => self::TEST_USER_ID,
                'address_type' => 'home',
                'address' => '123 Test St, Agent City',
                'lat_long' => '22.3,70.8',
                'flat_no' => '1',
                'landmark' => 'Near park',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. user_wallet_transaction so getWalletBalance returns a balance (wallet_provider_type 0 = customer)
        if (!DB::table('user_wallet_transaction')->where('user_id', self::TEST_USER_ID)->where('wallet_provider_type', 0)->exists()) {
            DB::table('user_wallet_transaction')->insert([
                'user_id' => self::TEST_USER_ID,
                'wallet_provider_type' => 0,
                'transaction_type' => 1,
                'amount' => 100.00,
                'order_no' => 'WALLET-SEED-1',
                'subject' => 'Initial balance (seed)',
                'subject_code' => 0,
                'remaining_balance' => 100.00,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 4. Test provider (id 8001)
        if (!DB::table('providers')->where('id', self::TEST_PROVIDER_ID)->exists()) {
            DB::table('providers')->insert([
                'id' => self::TEST_PROVIDER_ID,
                'first_name' => 'Test',
                'last_name' => 'Provider',
                'email' => 'provider-agent-test@example.com',
                'status' => 1,
                'service_radius' => 25,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 5. provider_services (link provider to service category 12)
        $providerServiceId = DB::table('provider_services')->where('provider_id', self::TEST_PROVIDER_ID)->where('service_cat_id', self::SERVICE_CAT_ID)->value('id');
        if (!$providerServiceId) {
            $providerServiceId = DB::table('provider_services')->insertGetId([
                'provider_id' => self::TEST_PROVIDER_ID,
                'service_cat_id' => self::SERVICE_CAT_ID,
                'current_status' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 6. other_service_provider_details (location, rating, time_slot_status)
        if (!DB::table('other_service_provider_details')->where('provider_id', self::TEST_PROVIDER_ID)->exists()) {
            DB::table('other_service_provider_details')->insert([
                'provider_id' => self::TEST_PROVIDER_ID,
                'rating' => 4.5,
                'total_completed_order' => 10,
                'address' => '456 Provider Ave',
                'lat' => '22.31',
                'long' => '70.81',
                'time_slot_status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 7. other_service_provider_packages
        $packageId = DB::table('other_service_provider_packages')
            ->where('provider_service_id', $providerServiceId)
            ->where('sub_cat_id', self::SUB_CAT_ID)
            ->value('id');
        if (!$packageId) {
            $packageId = DB::table('other_service_provider_packages')->insertGetId([
                'provider_service_id' => $providerServiceId,
                'sub_cat_id' => self::SUB_CAT_ID,
                'service_cat_id' => self::SERVICE_CAT_ID,
                'name' => 'Basic Baby Maids Package',
                'description' => '1 session',
                'price' => 25.00,
                'max_book_quantity' => 5,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 8. other_service_provider_timings (MON–FRI 09:00–17:00)
        $days = ['MON', 'TUE', 'WED', 'THU', 'FRI'];
        foreach ($days as $day) {
            if (!DB::table('other_service_provider_timings')->where('provider_id', self::TEST_PROVIDER_ID)->where('day', $day)->exists()) {
                DB::table('other_service_provider_timings')->insert([
                    'provider_id' => self::TEST_PROVIDER_ID,
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

        // 9. user_service_package_booking (one past completed order)
        $orderId = DB::table('user_service_package_booking')
            ->where('user_id', self::TEST_USER_ID)
            ->where('provider_id', self::TEST_PROVIDER_ID)
            ->value('id');
        if (!$orderId) {
            $orderNo = (string) (1000000 + self::TEST_USER_ID * 10000 + self::TEST_PROVIDER_ID);
            $orderId = DB::table('user_service_package_booking')->insertGetId([
                'user_id' => self::TEST_USER_ID,
                'provider_id' => self::TEST_PROVIDER_ID,
                'package_id' => $packageId,
                'service_cat_id' => self::SERVICE_CAT_ID,
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
                'provider_name' => 'Test Provider',
                'status' => 9,
                'payment_status' => 1,
                'delivery_address' => '123 Test St',
                'lat_long' => '22.3,70.8',
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now,
            ]);
        }

        // 10. user_package_booking_quantity (order line item)
        if (!DB::table('user_package_booking_quantity')->where('order_id', $orderId)->exists()) {
            $packageName = DB::table('other_service_provider_packages')->where('id', $packageId)->value('name');
            DB::table('user_package_booking_quantity')->insert([
                'package_id' => $packageId,
                'order_id' => $orderId,
                'num_of_items' => 1,
                'package_name' => $packageName ?? 'Basic Baby Maids Package',
                'sub_category_name' => 'Baby Maids',
                'price_for_one' => 25.00,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 11. other_service_rating (one review)
        if (!DB::table('other_service_rating')->where('booking_id', $orderId)->where('provider_id', self::TEST_PROVIDER_ID)->exists()) {
            DB::table('other_service_rating')->insert([
                'user_id' => self::TEST_USER_ID,
                'provider_id' => self::TEST_PROVIDER_ID,
                'booking_id' => $orderId,
                'rating' => 5,
                'comment' => 'Great service (seed data)',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('BuyerAgentTestDataSeeder finished. Test credentials: user_id=' . self::TEST_USER_ID . ', access_token=' . self::TEST_ACCESS_TOKEN);
    }
}
