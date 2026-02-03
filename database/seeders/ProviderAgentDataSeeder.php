<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Provider Agent Data Seeder – 100 providers (IDs 8001–8100)
 *
 * Seeds all data required for the buyer agent and provider-list API with full
 * coverage of all service categories and subcategories. All data is realistic
 * (no dummy, test, or seeder references). At the end, logs how many rows were
 * inserted in each table.
 *
 * Tables populated:
 * - providers (100)
 * - other_service_provider_details (100)
 * - provider_services (~300–400, 2–6 categories per provider)
 * - other_service_provider_packages (~1200+, all subcategories covered)
 * - other_service_provider_timings (7 days per provider)
 * - other_service_rating (sample, with realistic comments)
 * - user_service_package_booking (one per rating)
 * - provider_portfolio_image (1–2 per provider)
 * - provider_bank_details (1 per provider)
 * - users / user_address (test customer for E2E, user_id=2)
 *
 * How to run:
 *   php artisan db:seed --class=ProviderAgentDataSeeder
 *
 * Prerequisites: ServiceCategorySeeder and OtherServiceSubCategorySeeder
 * (or full DatabaseSeeder) so service_category and other_service_sub_category have rows.
 *
 * Test customer: user_id=2, access_token=652220102026020270
 */
class ProviderAgentDataSeeder extends Seeder
{
    private const PROVIDER_ID_START = 8001;
    private const PROVIDER_COUNT = 100;
    private const TEST_USER_ID = 2;
    private const TEST_ACCESS_TOKEN = '652220102026020270';
    private const CENTER_LAT = 22.3;
    private const CENTER_LONG = 70.8;

    /** @var array<string, int> */
    private array $insertCounts = [];

    /** @var array<int, array{first_name: string, last_name: string, email: string, contact_number: string, gender: int}> */
    private array $providerNames = [];

    /** @var array<int, array{address: string, lat: string, long: string, rating: float, total_completed_order: int}> */
    private array $providerDetailsData = [];

    /** @var list<string> */
    private array $ratingComments = [];

    /** @var list<string> */
    private array $packageDescriptions = [];

    /** @var list<string> */
    private array $banks = [];

    public function run(): void
    {
        $now = Carbon::now();
        $this->insertCounts = [
            'providers' => 0,
            'other_service_provider_details' => 0,
            'provider_services' => 0,
            'other_service_provider_packages' => 0,
            'other_service_provider_timings' => 0,
            'other_service_rating' => 0,
            'user_service_package_booking' => 0,
            'provider_portfolio_image' => 0,
            'provider_bank_details' => 0,
        ];

        $serviceCategories = DB::table('service_category')->where('status', 1)->pluck('id')->toArray();
        if (empty($serviceCategories)) {
            $this->command->warn('No active service_category found. Run ServiceCategorySeeder first.');
            return;
        }

        $subCatsRaw = DB::table('other_service_sub_category')->where('status', 1)->get();
        $subCatsByCategory = $subCatsRaw->groupBy('service_cat_id')->map(function ($items) {
            return $items->values()->all();
        })->toArray();
        $subCatsByCategory = array_intersect_key($subCatsByCategory, array_flip($serviceCategories));

        $providerIds = range(self::PROVIDER_ID_START, self::PROVIDER_ID_START + self::PROVIDER_COUNT - 1);

        $this->cleanup($providerIds);
        $this->buildRealisticProviderData($providerIds);
        $this->insertProviders($providerIds, $now);
        $this->insertOtherServiceProviderDetails($providerIds, $now);
        $providerServiceMap = $this->insertProviderServices($providerIds, $serviceCategories, $now);
        $this->insertPackages($providerServiceMap, $subCatsByCategory, $now);
        $this->insertTimings($providerIds, $now);
        $this->ensureTestUserAndAddress($now);
        $this->insertRatingsAndBookings($providerIds, $providerServiceMap, $now);
        $this->insertPortfolioImages($providerIds, $providerServiceMap, $now);
        $this->insertBankDetails($providerIds, $now);
        $this->logInsertCounts();
    }

    /** @param list<int> $providerIds */
    private function cleanup(array $providerIds): void
    {
        $idList = implode(',', array_map('intval', $providerIds));
        DB::statement('DELETE FROM other_service_rating WHERE provider_id IN (' . $idList . ')');
        DB::statement('DELETE FROM user_service_package_booking WHERE provider_id IN (' . $idList . ')');
        DB::statement('DELETE FROM provider_accepted_package_time WHERE provider_id IN (' . $idList . ')');
        DB::statement('DELETE FROM other_service_provider_timings WHERE provider_id IN (' . $idList . ')');
        $psIds = DB::table('provider_services')->whereIn('provider_id', $providerIds)->pluck('id')->toArray();
        if (!empty($psIds)) {
            DB::table('other_service_provider_packages')->whereIn('provider_service_id', $psIds)->delete();
        }
        DB::table('provider_services')->whereIn('provider_id', $providerIds)->delete();
        DB::table('other_service_provider_details')->whereIn('provider_id', $providerIds)->delete();
        DB::table('provider_portfolio_image')->whereIn('provider_id', $providerIds)->delete();
        DB::table('provider_bank_details')->whereIn('provider_id', $providerIds)->delete();
        DB::table('providers')->whereIn('id', $providerIds)->delete();
    }

    /** @param list<int> $providerIds */
    private function buildRealisticProviderData(array $providerIds): void
    {
        $firstNames = [
            'James', 'Maria', 'David', 'Sarah', 'Michael', 'Emily', 'Robert', 'Jessica', 'William', 'Ashley',
            'John', 'Amanda', 'Daniel', 'Jennifer', 'Matthew', 'Stephanie', 'Anthony', 'Nicole', 'Mark', 'Elizabeth',
            'Donald', 'Lauren', 'Steven', 'Rachel', 'Paul', 'Samantha', 'Andrew', 'Megan', 'Joshua', 'Heather',
            'Kenneth', 'Christina', 'Kevin', 'Kelly', 'Brian', 'Laura', 'George', 'Lisa', 'Timothy', 'Angela',
            'Ronald', 'Kimberly', 'Edward', 'Melissa', 'Jason', 'Amy', 'Jeffrey', 'Rebecca', 'Ryan', 'Michelle',
            'Jacob', 'Tiffany', 'Gary', 'Kim', 'Nicholas', 'Sandra', 'Eric', 'Donna', 'Jonathan', 'Carol',
            'Stephen', 'Patricia', 'Larry', 'Deborah', 'Justin', 'Sharon', 'Scott', 'Karen', 'Brandon', 'Nancy',
            'Benjamin', 'Betty', 'Samuel', 'Margaret', 'Raymond', 'Dorothy', 'Gregory', 'Sandra', 'Frank', 'Helen',
            'Alexander', 'Diane', 'Patrick', 'Ruth', 'Jack', 'Virginia', 'Dennis', 'Catherine', 'Jerry', 'Carolyn',
            'Tyler', 'Janet', 'Aaron', 'Frances', 'Jose', 'Ann', 'Adam', 'Alice', 'Nathan', 'Julia',
        ];
        $lastNames = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
            'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
            'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson',
            'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores',
            'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts',
            'Turner', 'Phillips', 'Evans', 'Parker', 'Edwards', 'Collins', 'Stewart', 'Morris', 'Murphy', 'Cook',
            'Rogers', 'Morgan', 'Peterson', 'Cooper', 'Reed', 'Bailey', 'Bell', 'Gomez', 'Kelly', 'Howard',
            'Ward', 'Cox', 'Diaz', 'Richardson', 'Wood', 'Watson', 'Brooks', 'Bennett', 'Gray', 'James',
            'Reyes', 'Cruz', 'Hughes', 'Price', 'Myers', 'Long', 'Foster', 'Sanders', 'Ross', 'Morales',
            'Powell', 'Sullivan', 'Russell', 'Ortiz', 'Jenkins', 'Gutierrez', 'Perry', 'Butler', 'Barnes', 'Fisher',
        ];
        $addresses = [
            '124 Oak Street', '45 Maple Drive', '78 Park Avenue, Apt 3B', '201 Cedar Lane', '56 Birch Road',
            '312 Pine Street', '89 Elm Court', '167 Walnut Way', '23 Spruce Avenue', '401 Willow Lane',
            '90 Chestnut Drive', '155 Magnolia Street', '278 Hickory Road', '34 Sycamore Lane', '422 Ash Boulevard',
            '67 Beech Street', '189 Cherry Lane', '12 Dogwood Drive', '301 Fir Avenue', '445 Grove Road',
            '88 Holly Lane', '223 Ivy Street', '56 Juniper Drive', '178 Laurel Road', '390 Locust Street',
            '101 Mulberry Lane', '234 Oakwood Drive', '367 Orchard Street', '49 Palm Lane', '512 Quince Road',
            '145 Redwood Avenue', '258 Sequoia Drive', '71 Tulip Lane', '384 Vine Street', '497 Walnut Drive',
            '118 Yew Lane', '231 Zinnia Road', '354 Acacia Street', '67 Bay Lane', '180 Cypress Drive',
            '293 Dahlia Road', '406 Elder Street', '519 Fern Lane', '62 Garden Road', '175 Hazel Drive',
            '288 Iris Lane', '391 Jasmine Road', '504 Kelp Street', '617 Lavender Lane', '720 Moss Drive',
            '833 Nectar Road', '946 Olive Street', '59 Peony Lane', '162 Quartz Drive', '265 Rose Road',
            '368 Sage Street', '471 Thyme Lane', '574 Umber Drive', '677 Violet Road', '780 Wisteria Street',
            '883 Yarrow Lane', '986 Azalea Drive', '99 Blossom Road', '102 Camellia Street', '205 Daffodil Lane',
            '308 Eucalyptus Drive', '411 Fuchsia Road', '514 Geranium Street', '617 Hibiscus Lane', '720 Iris Drive',
            '823 Jasmine Road', '926 Lilac Street', '29 Marigold Lane', '132 Narcissus Drive', '235 Orchid Road',
            '338 Peony Street', '441 Primrose Lane', '544 Ranunculus Drive', '647 Sunflower Road', '750 Tulip Street',
            '853 Verbena Lane', '956 Wisteria Drive', '159 Zinnia Road', '262 Aster Street', '365 Bluebell Lane',
            '468 Clover Drive', '571 Daisy Road', '674 Foxglove Street', '777 Goldenrod Lane', '880 Heather Drive',
            '983 Indigo Road', '86 Jade Street', '189 Lavender Lane', '292 Lilac Drive', '395 Marigold Road',
            '498 Nasturtium Street', '501 Oleander Lane', '604 Pansy Drive', '707 Queen Anne Road', '810 Rose Street',
        ];

        $this->ratingComments = [
            'On time and did a great job.',
            'Professional and efficient.',
            'Very satisfied, would book again.',
            'Friendly and skilled. Highly recommend.',
            'Quick response and quality work.',
            'Exactly what I needed. Thanks!',
            'Will definitely use again.',
            'Courteous and thorough.',
        ];

        $this->packageDescriptions = [
            'Includes initial assessment and one session.',
            'Up to 2 hours.',
            'Single session. Materials included.',
            'One visit. Best for small jobs.',
            'Standard scope. Ideal for most needs.',
        ];

        $this->banks = ['Chase', 'Bank of America', 'Wells Fargo', 'Citibank', 'US Bank', 'PNC', 'TD Bank', 'Capital One'];

        mt_srand(42);
        $nFirst = count($firstNames);
        $nLast = count($lastNames);
        $nAddr = count($addresses);
        foreach ($providerIds as $i => $providerId) {
            $first = $firstNames[$i % $nFirst];
            $last = $lastNames[($i * 7 + 11) % $nLast];
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', $first . $last)) . ($i % 3 === 0 ? (string) ($i + 1) : '');
            $this->providerNames[$providerId] = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $slug . '@mailinator.com',
                'contact_number' => (string) (5550000000 + ($providerId * 12345) % 10000000),
                'gender' => $i % 3,
            ];
            $lat = (string) round(self::CENTER_LAT + (($i % 19 - 9) * 0.008), 4);
            $long = (string) round(self::CENTER_LONG + (($i % 17 - 8) * 0.009), 4);
            $this->providerDetailsData[$providerId] = [
                'address' => $addresses[$i % $nAddr],
                'lat' => $lat,
                'long' => $long,
                'rating' => round(3.5 + (($i % 31) / 31) * 1.5, 1),
                'total_completed_order' => 5 + ($i % 146),
            ];
        }
    }

    /** @param list<int> $providerIds */
    private function insertProviders(array $providerIds, Carbon $now): void
    {
        $rows = [];
        foreach ($providerIds as $providerId) {
            $d = $this->providerNames[$providerId];
            $rows[] = [
                'id' => $providerId,
                'first_name' => $d['first_name'],
                'last_name' => $d['last_name'],
                'email' => $d['email'],
                'contact_number' => $d['contact_number'],
                'provider_type' => 3,
                'status' => 1,
                'service_radius' => 10 + ($providerId % 41),
                'gender' => $d['gender'],
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('providers')->insert($chunk);
        }
        $this->insertCounts['providers'] = count($rows);
    }

    /** @param list<int> $providerIds */
    private function insertOtherServiceProviderDetails(array $providerIds, Carbon $now): void
    {
        $rows = [];
        foreach ($providerIds as $providerId) {
            $d = $this->providerDetailsData[$providerId];
            $rows[] = [
                'provider_id' => $providerId,
                'rating' => $d['rating'],
                'total_completed_order' => $d['total_completed_order'],
                'address' => $d['address'],
                'lat' => $d['lat'],
                'long' => $d['long'],
                'time_slot_status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('other_service_provider_details')->insert($chunk);
        }
        $this->insertCounts['other_service_provider_details'] = count($rows);
    }

    /**
     * @param list<int> $providerIds
     * @param list<int> $serviceCategories
     * @return array<int, array{provider_id: int, service_cat_id: int}> provider_service_id => [provider_id, service_cat_id]
     */
    private function insertProviderServices(array $providerIds, array $serviceCategories, Carbon $now): array
    {
        $minProvidersPerCategory = 2;
        $categoryCount = count($serviceCategories);
        $assignments = [];
        foreach ($serviceCategories as $catId) {
            $assignments[$catId] = 0;
        }
        $pool = [];
        foreach ($providerIds as $pid) {
            $n = 2 + (($pid + $categoryCount) % 5);
            $n = min($n, $categoryCount);
            $cats = (array) array_rand(array_flip($serviceCategories), $n);
            if (!is_array($cats)) {
                $cats = [$cats];
            }
            foreach ($cats as $c) {
                $pool[] = ['provider_id' => $pid, 'service_cat_id' => (int) $c];
                $assignments[(int) $c]++;
            }
        }
        foreach ($serviceCategories as $catId) {
            while ($assignments[$catId] < $minProvidersPerCategory) {
                $pid = $providerIds[array_rand($providerIds)];
                $pool[] = ['provider_id' => $pid, 'service_cat_id' => $catId];
                $assignments[$catId]++;
            }
        }
        $map = [];
        foreach ($pool as $row) {
            $id = DB::table('provider_services')->insertGetId([
                'provider_id' => $row['provider_id'],
                'service_cat_id' => $row['service_cat_id'],
                'current_status' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $map[$id] = $row;
        }
        $this->insertCounts['provider_services'] = count($map);
        return $map;
    }

    /**
     * @param array<int, array{provider_id: int, service_cat_id: int}> $providerServiceMap
     * @param array<int, array<object>> $subCatsByCategory
     */
    private function insertPackages(array $providerServiceMap, array $subCatsByCategory, Carbon $now): void
    {
        $packageNames = ['Standard', '1-Hour', 'Single session', 'Basic', 'Starter'];
        $rows = [];
        foreach ($providerServiceMap as $psId => $row) {
            $serviceCatId = $row['service_cat_id'];
            $subCats = $subCatsByCategory[$serviceCatId] ?? [];
            foreach ($subCats as $subCat) {
                $name = $subCat->name ?? 'Service';
                $pkgName = $packageNames[$psId % count($packageNames)] . ' ' . $name;
                if (strlen($pkgName) > 180) {
                    $pkgName = substr($pkgName, 0, 177) . '…';
                }
                $price = round(25 + (($psId + $subCat->id) % 96) + (($subCat->id % 10) / 10), 2);
                $desc = $this->packageDescriptions[$subCat->id % count($this->packageDescriptions)];
                $rows[] = [
                    'provider_service_id' => $psId,
                    'sub_cat_id' => $subCat->id,
                    'service_cat_id' => $serviceCatId,
                    'name' => $pkgName,
                    'description' => $desc,
                    'price' => $price,
                    'max_book_quantity' => 1 + (($psId + $subCat->id) % 10),
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('other_service_provider_packages')->insert($chunk);
        }
        $this->insertCounts['other_service_provider_packages'] = count($rows);
    }

    /** @param list<int> $providerIds */
    private function insertTimings(array $providerIds, Carbon $now): void
    {
        $days = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
        $rows = [];
        foreach ($providerIds as $providerId) {
            foreach ($days as $day) {
                $rows[] = [
                    'provider_id' => $providerId,
                    'day' => $day,
                    'open_time_list' => '09:00:00,17:00:00',
                    'provider_open_time' => '09:00:00',
                    'provider_close_time' => '17:00:00',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('other_service_provider_timings')->insert($chunk);
        }
        $this->insertCounts['other_service_provider_timings'] = count($rows);
    }

    private function ensureTestUserAndAddress(Carbon $now): void
    {
        $userExists = DB::table('users')->where('id', self::TEST_USER_ID)->exists();
        if (!$userExists) {
            DB::table('users')->insert([
                'id' => self::TEST_USER_ID,
                'first_name' => 'Alex',
                'last_name' => 'Morgan',
                'email' => 'alex.morgan@example.com',
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
                'access_token' => (string) self::TEST_ACCESS_TOKEN,
                'status' => 1,
                'updated_at' => $now,
            ]);
        }
        if (!DB::table('user_address')->where('user_id', self::TEST_USER_ID)->where('status', 1)->exists()) {
            DB::table('user_address')->insert([
                'user_id' => self::TEST_USER_ID,
                'address_type' => 'home',
                'address' => '124 Oak Street',
                'lat_long' => self::CENTER_LAT . ',' . self::CENTER_LONG,
                'flat_no' => '1',
                'landmark' => 'Near park',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @param list<int> $providerIds
     * @param array<int, array{provider_id: int, service_cat_id: int}> $providerServiceMap
     */
    private function insertRatingsAndBookings(array $providerIds, array $providerServiceMap, Carbon $now): void
    {
        $comments = $this->ratingComments;
        $nComments = count($comments);
        $sampleSize = min(45, (int) (count($providerIds) * 0.45));
        $chosen = array_slice($providerIds, 0, $sampleSize);
        $latLong = self::CENTER_LAT . ',' . self::CENTER_LONG;
        $user = DB::table('users')->where('id', self::TEST_USER_ID)->first();
        $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'Alex Morgan';
        if ($userName === '') {
            $userName = 'Alex Morgan';
        }

        $bookingCount = 0;
        $ratingCount = 0;
        foreach ($chosen as $idx => $providerId) {
            $psIds = array_keys(array_filter($providerServiceMap, fn ($r) => $r['provider_id'] === $providerId));
            if (empty($psIds)) {
                continue;
            }
            $psId = $psIds[0];
            $serviceCatId = $providerServiceMap[$psId]['service_cat_id'];
            $pkg = DB::table('other_service_provider_packages')
                ->where('provider_service_id', $psId)
                ->where('status', 1)
                ->first();
            if (!$pkg) {
                continue;
            }
            $providerName = $this->providerNames[$providerId]['first_name'] . ' ' . $this->providerNames[$providerId]['last_name'];
            $orderNo = (string) (1000000 + self::TEST_USER_ID * 10000 + $providerId + $idx);
            $serviceDate = $now->copy()->subDays(2 + ($idx % 30));
            $bookingId = DB::table('user_service_package_booking')->insertGetId([
                'user_id' => self::TEST_USER_ID,
                'provider_id' => $providerId,
                'package_id' => $pkg->id,
                'service_cat_id' => $serviceCatId,
                'order_no' => $orderNo,
                'order_type' => 0,
                'service_date_time' => $serviceDate,
                'service_date' => $serviceDate->format('Y-m-d'),
                'service_time' => '10:00',
                'book_start_time' => '09:00:00',
                'book_end_time' => '10:00:00',
                'total_item_cost' => $pkg->price,
                'tax' => 0,
                'total_pay' => $pkg->price,
                'user_name' => trim($userName),
                'provider_name' => $providerName,
                'status' => 9,
                'payment_status' => 1,
                'delivery_address' => $this->providerDetailsData[$providerId]['address'],
                'lat_long' => $latLong,
                'created_at' => $now->copy()->subDays(3 + ($idx % 30)),
                'updated_at' => $now,
            ]);
            $bookingCount++;
            DB::table('other_service_rating')->insert([
                'user_id' => self::TEST_USER_ID,
                'provider_id' => $providerId,
                'booking_id' => $bookingId,
                'rating' => 4 + ($idx % 2),
                'comment' => $comments[$idx % $nComments],
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ratingCount++;
        }
        $this->insertCounts['user_service_package_booking'] = $bookingCount;
        $this->insertCounts['other_service_rating'] = $ratingCount;
    }

    /**
     * @param list<int> $providerIds
     * @param array<int, array{provider_id: int, service_cat_id: int}> $providerServiceMap
     */
    private function insertPortfolioImages(array $providerIds, array $providerServiceMap, Carbon $now): void
    {
        $filenames = ['portfolio.jpg', 'work-sample.png', 'project.jpg', 'sample.png'];
        $rows = [];
        foreach ($providerIds as $providerId) {
            $psIds = array_keys(array_filter($providerServiceMap, fn ($r) => $r['provider_id'] === $providerId));
            $serviceCatId = !empty($psIds) ? $providerServiceMap[$psIds[0]]['service_cat_id'] : 0;
            foreach (array_slice($filenames, 0, 1 + ($providerId % 2)) as $fi => $fn) {
                $rows[] = [
                    'provider_id' => $providerId,
                    'service_cat_id' => $serviceCatId ?: 11,
                    'image' => $fn,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('provider_portfolio_image')->insert($chunk);
        }
        $this->insertCounts['provider_portfolio_image'] = count($rows);
    }

    /** @param list<int> $providerIds */
    private function insertBankDetails(array $providerIds, Carbon $now): void
    {
        $banks = $this->banks;
        $locations = ['New York, NY', 'Los Angeles, CA', 'Chicago, IL', 'Houston, TX', 'Phoenix, AZ', 'Philadelphia, PA', 'San Antonio, TX', 'San Diego, CA'];
        $rows = [];
        foreach ($providerIds as $providerId) {
            $d = $this->providerNames[$providerId];
            $fullName = $d['first_name'] . ' ' . $d['last_name'];
            $rows[] = [
                'provider_id' => $providerId,
                'account_number' => '****' . (4521 + $providerId % 10000),
                'holder_name' => $fullName,
                'bank_name' => $banks[$providerId % count($banks)],
                'bank_location' => $locations[$providerId % count($locations)],
                'payment_email' => $d['email'],
                'bic_swift_code' => 'CHASUS' . (33 + $providerId % 100),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('provider_bank_details')->insert($chunk);
        }
        $this->insertCounts['provider_bank_details'] = count($rows);
    }

    private function logInsertCounts(): void
    {
        $this->command->info('ProviderAgentDataSeeder finished. Inserted:');
        foreach ($this->insertCounts as $table => $count) {
            $this->command->info('  ' . $table . ': ' . $count);
        }
    }
}
