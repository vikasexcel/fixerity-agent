<?php

namespace App\Http\Controllers\api\otherservice;

use App\Classes\AdminClass;
use App\Classes\NotificationClass;
use App\Classes\OnDemandClassApi;
use App\Models\GeneralSettings;
use App\Models\OtherServiceCategory;
use App\Models\OtherServiceProviderDetails;
use App\Models\OtherServiceProviderPackages;
use App\Models\OtherServiceProviderTimings;
use App\Models\OtherServiceRatings;
use App\Models\PromocodeDetails;
use App\Models\Provider;
use App\Models\ProviderAcceptedPackageTime;
use App\Models\ProviderBankDetails;
use App\Models\ProviderDocuments;
use App\Models\ProviderPortfolioImage;
use App\Models\ProviderServices;
use App\Models\RequiredDocuments;
use App\Models\ServiceCategory;
use App\Models\ServiceSettings;
use App\Models\UsedPromocodeDetails;
use App\Models\User;
use App\Models\UserPackageBooking;
use App\Models\UserPackageBookingQuantity;
use App\Models\UserRatings;
use App\Models\UserReferHistory;
use App\Models\UserWalletTransaction;
use App\Models\WorldCurrency;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;

class ProviderController extends Controller
{
    //        json response status [
    //            0 => false,
    //            1 => true,
    //            2 => registration pending,
    //            3 => app user blocked,
    //            4 => app user access token not match,
    //            5 => app user not found,
    //          ]

    //ApiLogDetail logger type => 0:user,1:store,2:driver,3:provider
    private $onDemandClassApi;
    private $notificationClass;
    private $on_demand_service_id_array;
    private $dog_walking;
    private $baby_care;
    private $pet_care;
    private $workout_trainer;
    private $security_service;
    private $tutors;
    private $beauty_service;
    private $massage_service;
    private $home_cleaning;
    private $gardening;
    private $snow_blowers;
    private $laundry_service;
    private $maid_service;
    private $pest_control;
    private $ac_repair;
    private $electricians;
    private $car_wash;
    private $car_repair;
    private $tow_truck;
    private $plumbers;
    private $adminClass;

    public function __construct(OnDemandClassApi $onDemandClassApi, NotificationClass $notificationClass, AdminClass $adminClass)
    {
        $this->adminClass = $adminClass;
        $this->onDemandClassApi = $onDemandClassApi;
        $this->notificationClass = $notificationClass;
        //        $this->on_demand_service_id_array = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30];
        $service_cat = ServiceCategory::query()->whereIN('category_type', [3, 4])->where('status', 1)->get()->pluck('id')->toArray();
        $this->on_demand_service_id_array = $service_cat;

        $this->dog_walking = 11;
        $this->baby_care = 12;
        $this->pet_care = 13;
        $this->workout_trainer = 14;
        $this->security_service = 15;
        $this->tutors = 16;
        $this->beauty_service = 17;
        $this->massage_service = 18;
        $this->home_cleaning = 19;
        $this->gardening = 20;
        $this->snow_blowers = 21;
        $this->laundry_service = 22;
        $this->maid_service = 23;
        $this->pest_control = 24;
        $this->ac_repair = 25;
        $this->electricians = 26;
        $this->car_wash = 27;
        $this->car_repair = 28;
        $this->tow_truck = 29;
        $this->plumbers = 30;
    }

    public function postOnDemandServiceList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $lang_prefix = $this->adminClass->get_langugae_fields($provider_details->language);

        $get_provider_service_list = ProviderServices::select('service_cat_id')->where('provider_id', $provider_details->id)->whereIN('service_cat_id', $this->on_demand_service_id_array)->get()->pluck('service_cat_id')->toArray();
        //        $service_category_list = ServiceCategory::select('id as service_cat_id', $lang_prefix . 'name as service_cat_name')
        //            ->whereIN('category_type', [3, 4])
        //            ->where('status', 1)
        //            ->whereNotIn('id', $get_provider_service_list)
        //            ->get();
        $service_category_list = OtherServiceCategory::select('service_category.id as service_cat_id', 'service_category.' . $lang_prefix . 'name as service_cat_name')
            ->join('service_category', 'service_category.id', '=', 'other_service_sub_category.service_cat_id')
            ->whereIN('service_category.category_type', [3, 4])
            ->where('service_category.status', 1)
            ->whereNotIn('service_category.id', $get_provider_service_list)
            ->groupBy('service_category.id')
            ->get();
        return response()->json([
            "status" => 1,
            //            "message" => "success",
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            "service_category_list" => $service_category_list
        ]);
    }

    public function postOnDemandProviderServiceList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        return $this->onDemandClassApi->providerServiceList($provider_details->id);
    }

    public function postOnDemandAddServices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "service_cat_id" => "required"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $req_service_cat_id = array_map('trim', explode(",", $request->service_cat_id));
        foreach ($req_service_cat_id as $service_id) {
            //            check service catgeory
            $service_category = ServiceCategory::where('id', $service_id)->first();
            if ($service_category != Null) {
                //                check provider duplicate service
                $provider_service = ProviderServices::where('provider_id', $provider_details->id)->where('service_cat_id', $service_category->id)->first();
                if ($provider_service == Null) {
                    $add_provider_service = new ProviderServices();
                    $add_provider_service->provider_id = $provider_details->id;
                    $add_provider_service->service_cat_id = $service_category->id;
                    //                    $add_provider_service->current_status = 0;
                    //                    $add_provider_service->status = 0;
                    $add_provider_service->current_status = 1;
                    // $add_provider_service->status = 1;
                    $add_provider_service->status = 0;
                    $add_provider_service->save();

                    if ($provider_details->status == 3) {
                        $provider_status = Provider::where('id', $provider_details->id)->whereNull('providers.deleted_at')->first();
                        if ($provider_status != Null) {
                            $provider_status->status = 0;
                            //                            $provider_status->status = 1;
                            $provider_status->save();
                        }
                    }
                }
            }
        }
        return $this->onDemandClassApi->providerServiceList($provider_details->id);
    }

    public function postOnDemandChangeServiceCurrentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "provider_service_id" => "required|integer",
            "current_status" => "required|integer|in:0,1"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_service = ProviderServices::where('id', $request->get('provider_service_id'))->whereIN('service_cat_id', $this->on_demand_service_id_array)->first();
        if ($provider_service != Null) {
            $provider_service->current_status = $request->get('current_status');
            $provider_service->save();
            return response()->json([
                "status" => 1,
                //                "message" => "success!",
                "message" => __('provider_messages.1'),
                "message_code" => 1,
            ]);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "provider service not found",
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function postOnDemandRemoveService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "provider_service_id" => "required|integer"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_service = ProviderServices::where('id', $request->get('provider_service_id'))->whereIN('service_cat_id', $this->on_demand_service_id_array)->first();
        $running_service = UserPackageBooking::query()
            ->where('provider_id', $request->get('provider_id'))
            ->where('service_cat_id', $provider_service->service_cat_id)
            ->whereIn('status', [2, 3, 6, 7, 8])
            ->get();
        if ($provider_service != Null) {
            if (!$running_service->isEmpty()) {
                return response()->json([
                    "status" => 0,
                    "message" => __('provider_messages.310'),
                    "message_code" => 310,
                ]);
            }
            ProviderServices::where('id', $request->get('provider_service_id'))->delete();
            return $this->onDemandClassApi->providerServiceList($provider_details->id);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "provider service not found",
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function postOnDemandPackageList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "provider_service_id" => "required|integer"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency->ratio;

        $provider_service = ProviderServices::query()->where('id', $request->get('provider_service_id'))->whereIN('service_cat_id', $this->on_demand_service_id_array)->first();
        if ($provider_service != Null) {
            $package_list = [];

            $sub_categories = OtherServiceCategory::query()->where('service_cat_id', $provider_service->service_cat_id)->where('status', 1)->get();


            $lang_prefix = $this->adminClass->get_langugae_fields($provider_details->language);

            foreach ($sub_categories as $sub_category) {
                $packages = OtherServiceProviderPackages::query()->select(
                    'id as package_id',
                    'name as package_name',
                    //                    'description as package_description',
                    DB::raw("(CASE WHEN description IS NOT NULL THEN description ELSE '' END) AS package_description"),
                    'max_book_quantity as package_max_book_quantity',
                    //                    'price as package_price',
                    DB::raw('ROUND(price * ' . $currency . ',2) As package_price'),
                    'status as package_status'
                )->where('provider_service_id', $provider_service->id)->where('sub_cat_id', $sub_category->id)->get();
                if (!$packages->isEmpty()) {
                    $package_list[] = [
                        "category_id" => $sub_category->id,
                        "category_name" => ($sub_category[$lang_prefix . "name"] != "") ? $sub_category[$lang_prefix . "name"] : $sub_category["name"],
                        "packages" => $packages
                    ];
                }
            }
            return response()->json([
                "status" => 1,
                //                "message" => "success!",
                "message" => __('provider_messages.1'),
                "message_code" => 1,
                "package_list" => $package_list
            ]);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "provider service not found!",
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function postOnDemandGetCategoryList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "provider_service_id" => "required|integer"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_service = ProviderServices::where('id', $request->get('provider_service_id'))->whereIN('service_cat_id', $this->on_demand_service_id_array)->first();
        if ($provider_service != Null) {

            $lang_prefix = $this->adminClass->get_langugae_fields($provider_details->language);

            $sub_categories = OtherServiceCategory::select(
                'id as category_id',
                DB::raw("(CASE WHEN " . $lang_prefix . "name != '' THEN  " . $lang_prefix . "name ELSE name END) as category_name")
            )->where('service_cat_id', $provider_service->service_cat_id)->where('status', 1)->get();
            return response()->json([
                "status" => 1,
                //                "message" => "success!",
                "message" => __('provider_messages.1'),
                "message_code" => 1,
                "category_list" => $sub_categories
            ]);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "provider service not found!",
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function postOnDemandAddUpdatePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "provider_service_id" => "required|integer",
            "category_id" => "required|integer",
            "package_name" => "required",
            "package_description" => "required",
            "package_price" => "required|numeric",
            "max_book_quantity" => "required|integer",
            "package_id" => "nullable|integer"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency->ratio;
        $provider_service = ProviderServices::where('id', $request->get('provider_service_id'))->whereIN('service_cat_id', $this->on_demand_service_id_array)->first();
        if ($provider_service != Null) {
            if ($request->get('package_id') != Null) {
                $add_package = OtherServiceProviderPackages::where('id', $request->get('package_id'))->first();
                if ($add_package == Null) {
                    $add_package = new OtherServiceProviderPackages();
                    $add_package->status = 1;
                }
            } else {
                $add_package = new OtherServiceProviderPackages();
                $add_package->status = 1;
            }
            $add_package->provider_service_id = $provider_service->id;
            $add_package->sub_cat_id = $request->get('category_id');
            $add_package->service_cat_id = $provider_service->service_cat_id;
            $add_package->name = ucwords(strtolower(trim($request->get('package_name'))));
            $add_package->description = $request->get('package_description');

            //            $val = number_format($request->get('package_price') / $currency, 2);
            ////            $reminder = $val % 10;
            //            $reminder = fmod($val * 100,10);
            //            if ($reminder >= 5) {
            //                $val = number_format($val + 0.01, 2);
            //            }

            $add_package->price = round($request->get('package_price') / $currency, 2);
            $add_package->max_book_quantity = $request->get('max_book_quantity');
            $add_package->save();
            return response()->json([
                "status" => 1,
                //                "message" => "success!",
                "message" => __('provider_messages.1'),
                "message_code" => 1,
            ]);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "provider service not found!",
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function postOnDemandDocumentList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        return $this->onDemandClassApi->providerDocumentList($provider_details->id);
    }

    public function postGetOnDemandFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details_check = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details_check) == false) {
            return $provider_details_check;
        }

        $provider_details = Provider::query()->where('id', '=', $request->get("provider_id"))->whereNull('providers.deleted_at')->first();
        if ($provider_details != Null) {

            if ($provider_details_check->language != "en") {
                $lang_prefix = $provider_details_check->language . "_";
            } else {
                $lang_prefix = "";
            }

            $user_profile_url = url('/assets/images/profile-images/customer');
            $provider_ratings = OtherServiceRatings::query()->select(
                'other_service_rating.id as rating_id',
                'user_service_package_booking.service_cat_id',
                'other_service_rating.booking_id as booking_id',
                DB::raw("concat(users.first_name,'') as user_name"),
                DB::raw("(CASE WHEN users.avatar != '' THEN (CASE WHEN CHAR_LENGTH(users.avatar) >= 25 THEN users.avatar ELSE concat('$user_profile_url','/',users.avatar) END) ELSE '' END) as user_profile_image"),
                'other_service_rating.rating',
                DB::raw("(CASE WHEN other_service_rating.comment != '' THEN other_service_rating.comment ELSE '' END) as comment"),
                'service_category.' . $lang_prefix . 'name as service_category_name',
                //(DB::raw('DATE_FORMAT(transport_driver_rating.created_at, "%a %d %b, %Y") as datetime'))
                (DB::raw('other_service_rating.created_at as datetime'))
            )
                ->join('users', 'users.id', '=', 'other_service_rating.user_id')
                ->join('user_service_package_booking', 'user_service_package_booking.id', '=', 'other_service_rating.booking_id')
                ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
                ->where('other_service_rating.status', 1)
                ->where('other_service_rating.provider_id', $provider_details->id)
                ->whereNotNull('other_service_rating.booking_id')
                ->whereNull('users.deleted_at')
                ->get()
                ->toArray();

            if ($provider_ratings != Null) {
                usort($provider_ratings, function ($a, $b) {
                    if ($a['datetime'] == $b['datetime']) return 0;
                    return $a['datetime'] < $b['datetime'] ? 1 : -1;
                });
            }
            $feedback_data = [];
            foreach ($provider_ratings as $feedback) {
                $feedback_data[] = [
                    'rating_id' => $feedback['rating_id'],
                    'service_cat_id' => $feedback['service_cat_id'],
                    'booking_id' => $feedback['booking_id'],
                    'user_name' => $feedback['user_name'],
                    'user_profile_image' => $feedback['user_profile_image'],
                    'rating' => $feedback['rating'],
                    'comment' => $feedback['comment'],
                    'service_category_name' => $feedback['service_category_name'],
                    //                    'datetime' => $this->notificationClass->dateLangConvert(Date('D d M, Y', strtotime($feedback['datetime'])), $provider_details_check->language),
                    'datetime' => Date('D d M, Y', strtotime($feedback['datetime'])),
                ];
            }
            return response()->json([
                "status" => 1,
                //                "message" => "success!",
                "message" => __('driver_messages.1'),
                "message_code" => 1,
                "feedback_list" => $feedback_data
            ]);
        } else {
            return response()->json([
                "status" => 5,
                //                "message" => 'Provider not found!',
                "message" => __('provider_messages.5'),
                "message_code" => 5
            ]);
        }
    }

    public function postOnDemandUploadSingleDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "provider_service_id" => "required|integer",
            "document_id" => "required|integer",
            "document_file" => "required|file",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_service = ProviderServices::where('id', $request->get('provider_service_id'))->first();
        if ($provider_service != Null) {
            $require_document = RequiredDocuments::where('id', $request->get('document_id'))->first();
            if ($require_document != Null) {
                $upload_document = ProviderDocuments::where('provider_service_id', $provider_service->id)->where('req_document_id', $require_document->id)->first();
                if ($request->file('document_file') != Null) {
                    if ($upload_document != Null) {
                        if (\File::exists(public_path('/assets/images/provider-documents/' . $upload_document->document_file))) {
                            \File::delete(public_path('/assets/images/provider-documents/' . $upload_document->document_file));
                        }
                    }
                    //                    $file = $request->file('document_file');
                    //                    $file_new = rand(1, 9) . date('sihYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                    //                    $file->move(public_path() . '/assets/images/provider-documents/', $file_new);
                    $image = $request->file('document_file');
                    $destinationPath = public_path('/assets/images/provider-documents/');
                    $img = Image::read($image->getRealPath());
                    $img->orient();
                    $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $image->getClientOriginalExtension();
                    $img->resize(500, 500, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save($destinationPath . $file_new);
                } else {
                    return response()->json([
                        "status" => 0,
                        //                        "message" => 'something went to wrong!',
                        "message" => __('provider_messages.9'),
                        "message_code" => 9,
                    ]);
                }
                if ($upload_document == Null) {
                    $upload_document = new ProviderDocuments();
                }
                $upload_document->provider_service_id = $provider_service->id;
                $upload_document->req_document_id = $require_document->id;
                $upload_document->document_file = $file_new;
                $upload_document->status = 0;
                $upload_document->save();
                return $this->onDemandClassApi->providerDocumentList($provider_details->id);
            } else {
                return response()->json([
                    "status" => 0,
                    //                    "message" => "required document not found!",
                    "message" => __('provider_messages.9'),
                    "message_code" => 9,
                ]);
            }
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "provider service not found!",
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function postOnDemandHome(Request $request)
    {
        \Log::info("postOnDemandHome");
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required", // string or numeric (app/agent vs legacy)
            "app_version" => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_details = Provider::query()->where('id', '=', $request->get("provider_id"))->whereNull('providers.deleted_at')->first();
        if ($provider_details == Null) {
            return response()->json([
                "status" => 5,
                //                "message" => 'Provider not found!',
                "message" => __('provider_messages.5'),
                "message_code" => 5
            ]);
        }
        $provider_details->app_version = $request->get("app_version");
        $provider_details->ip_address = $request->header('select-ip-address') != Null ? $request->get('select_ip_address') : Null;
        $provider_details->time_zone = $request->header('select-time-zone') != Null ? $request->header('select-time-zone') : Null;
        $provider_details->save();

        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency->ratio;
        return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
    }

    public function postOnDemandUpdateOrderStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "order_id" => "required|integer",
            "update_status" => "required|integer|in:2,3,4,5,6,7,8,9"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }
        $update_status = $request->get('update_status');
        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency->ratio;
        $general_settings = request()->get("general_settings");
        $default_server_timezone = "";
        if ($general_settings != Null) {
            if ($general_settings->default_server_timezone != "") {
                $default_server_timezone = $general_settings->default_server_timezone;
            }
        }
        $provider_timezone = $provider_details->time_zone != Null ? $provider_details->time_zone : $default_server_timezone;
        $order_details = UserPackageBooking::query()->where('id', $request->get('order_id'))->first();
        if ($order_details != Null) {
            if ($order_details->status == 4) {
                return response()->json([
                    "status" => 0,
                    //                    "message" => "order rejected by provider!",
                    "message" => __('provider_messages.74'),
                    "message_code" => 74,
                ]);
            }
            if ($order_details->status == 5) {
                //return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                $message_code = 36;
                if (str(trim($order_details->cancel_by))->lower()->value() == "admin") {
                    $message_code = 111;
                }
                return response()->json([
                    "status" => 0,
                    //                    "message" => "order cancel by admin!",
                    "message" => __("provider_messages.$message_code"),
                    "message_code" => $message_code,
                    "order_status" => $order_details->status - 0,
                ]);
            }
            if ($order_details->status == 9) {
                //return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                return response()->json([
                    "status" => 0,
                    //                    "message" => "order completed by admin!",
                    "message" => __('provider_messages.112'),
                    "message_code" => 112,
                    "order_status" => $order_details->status - 0,
                ]);
            }
            if ($order_details->status == 10) {
                return response()->json([
                    "status" => 0,
                    //                    "message" => "order is failed!",
                    "message" => __('provider_messages.76'),
                    "message_code" => 76,
                ]);
            }
            $user_details = User::query()->where('id', '=', $order_details->user_id)->whereNull('users.deleted_at')->first();
            switch ($update_status) {
                case $update_status == 2 || $update_status == 3:
                    $service_date = $order_details->service_date;
                    $service_time = $order_details->service_time;
                    $book_start_time = $order_details->book_start_time;
                    $book_end_time = $order_details->book_end_time;
                    $select_date = date('Y-m-d', strtotime($service_date));
                    $check_provider_accepted_time = ProviderAcceptedPackageTime::query()->where('provider_id', $provider_details->id)->where('date', '=', $select_date)->where('book_start_time', '=', $book_start_time)->where('book_end_time', '=', $book_end_time)->first();
                    if ($check_provider_accepted_time != Null) {
                        return response()->json([
                            "status" => 0,
                            //                            "message" => "something went to wrong!",
                            "message" => __('provider_messages.303'),
                            "message_code" => 303,
                        ]);
                    }
                    if ($order_details->status == 1) {
                        if ($general_settings->auto_settle_wallet == 1 && $general_settings->wallet_payment == 1) {
                            //converting the general setting to selected currency amount
                            $min_amount = round($general_settings->provider_min_amount * $currency, 2);

                            //check weather the provider has minimum wallet balance and wallet balance equal to (= tax + admin_commission )
                            $total_amount = $order_details->tax + $order_details->admin_commission;
                            $provider_wallet_balance = UserWalletTransaction::query()->select('remaining_balance')->where('user_id', $provider_details->id)->orderBy('id', 'desc')->first();

                            $default_amount = $provider_wallet_balance != NULL ? round($provider_wallet_balance->remaining_balance, 2) : 0;
                            if ($default_amount >= $general_settings->provider_min_amount && $default_amount >= $total_amount) {
                                if ($order_details->order_type == 1) {
                                    $order_details->status = 3;
                                    $order_details->save();
                                } else {
                                    $order_details->status = 2;
                                    $order_details->save();
                                }
                            } else {
                                $x = $default_amount < $general_settings->provider_min_amount ? $min_amount : $total_amount;
                                return response()->json([
                                    "status" => 0,
                                    "message" => __('provider_messages.339', ['amount' => $provider_details->currency . " " . $x]),
                                    "message_code" => 339,
                                ]);
                            }
                        } else {
                            //if autosettle is off normal flow
                            if ($order_details->order_type == 1) {
                                $order_details->status = 3;
                                $order_details->save();
                            } else {
                                $order_details->status = 2;
                                $order_details->save();
                            }
                        }
                    } else {
                        return response()->json([
                            "status" => 0,
                            //                            "message" => "something went to wrong!",
                            "message" => __('provider_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                    if ($user_details != Null) {
                        $this->notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status, $user_details->language);
                    }
                    $general_settings = request()->get("general_settings");
                    if ($general_settings !=  Null) {
                        if ($general_settings->send_mail == 1) {
                            $service_category = ServiceCategory::query()->where('id', $order_details->service_cat_id)->first();
                            if (($service_category != Null)) {
                                if ($user_details != Null) {
                                    try {
                                        //send mail user
                                        $email = $user_details->email;
                                        $service_name = ucwords(strtolower($service_category->name));
                                        $provider_name = ucwords($provider_details->first_name);
                                        $user_name = ($order_details->user_name != "") ? $order_details->user_name : "";

                                        $mail_type = "provider_accept_job_request_–_handyman";
                                        $to_mail = $email;
                                        $subject = "Your " . $general_settings->mail_site_name . " " . $service_name . " service accepted by " . $provider_name;
                                        $disp_data = array("##user_name##" => $user_name, "##service_name##" => $service_name, "##provider_name##" => $provider_name);
                                        $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                                    } catch (\Exception $e) {
                                    }
                                }

                                try {
                                    //send mail to provider
                                    $provider_email = $provider_details->email;
                                    $service_name = ucwords(strtolower($service_category->name));
                                    $provider_name = ucwords($provider_details->first_name);
                                    $mail_type = "provider_new_service_request";
                                    $to_mail = $provider_email;
                                    $subject = $provider_name . " you have a new " . $service_name . " service request ";
                                    $disp_data = array("##provider_name##" => $provider_name, "##service_name##" => $service_name, "##user_name##" => $user_name);
                                    $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                                } catch (\Exception $e) {
                                }

                                try {
                                    //send mail to admin
                                    if ($general_settings->send_receive_email != Null) {
                                        $user_name = ($order_details->user_name != "") ? $order_details->user_name : "";
                                        $service_name = ucwords(strtolower($service_category->name));
                                        $provider_name = ucwords($provider_details->first_name);
                                        $mail_type = "admin_new_service_request_-_handyman";
                                        $to_mail = $general_settings->send_receive_email;

                                        $subject = $provider_name . " provider get a new " . $service_name . " service request ";
                                        $disp_data = array("##provider_name##" => $provider_name, "##service_name##" => $service_name, "##user_name##" => $user_name);
                                        $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                                    }
                                } catch (\Exception $e) {
                                }
                            }
                        }
                    }

                    $add_provider_accepted_time = new ProviderAcceptedPackageTime();
                    $add_provider_accepted_time->provider_id = $provider_details->id;
                    $add_provider_accepted_time->order_id = $order_details->id;
                    $add_provider_accepted_time->date = $order_details->service_date;
                    $add_provider_accepted_time->time = $order_details->service_time;
                    $add_provider_accepted_time->book_start_time = $book_start_time;
                    $add_provider_accepted_time->book_end_time = $book_end_time;
                    $add_provider_accepted_time->save();
                    return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                    break;
                case 4:
                    if ($order_details->status == 1) {
                        $validator = Validator::make($request->all(), [
                            "reason" => "required",
                        ]);
                        if ($validator->fails()) {
                            return response()->json([
                                "status" => 0,
                                "message" => $validator->errors()->first(),
                                "message_code" => 9,
                            ]);
                        }
                        $order_status = $order_details->status;
                        $order_details->status = $update_status;
                        $order_details->cancel_by = "provider";
                        $order_details->cancel_reason = $request->get('reason');
                        $order_details->save();

                        ProviderAcceptedPackageTime::query()->where('provider_id', '=', $provider_details->id)->where('order_id', '=', $order_details->id)->delete();

                        if ($order_status < 8) {
                            if ($order_details->promo_code > 0) {
                                $used_promocode_details = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                                if ($used_promocode_details != Null) {
                                    $get_promocode = PromocodeDetails::query()->where('id', $used_promocode_details->promocode_id)->first();
                                    if ($get_promocode != Null) {
                                        $count_promocode = $get_promocode->total_usage - 1;
                                        $get_promocode->total_usage = ($count_promocode > 0) ? $count_promocode : 0;
                                        $get_promocode->save();
                                    }
                                    $used_promocode_details->status = 2;
                                    $used_promocode_details->save();
                                }
                            }

                            if ($order_details->refer_discount > 0) {
                                if ($user_details != Null) {
                                    $user_refer_history = UserReferHistory::query()->where('user_id', $order_details->user_id)->where('user_status', 1)->latest()->first();
                                    if ($user_refer_history != Null) {
                                        $user_refer_history->user_status = 0;
                                        $user_refer_history->save();
                                        $user_details->pending_refer_discount = $user_details->pending_refer_discount + 1;
                                        $user_details->save();
                                    } else {
                                        $user_refer_history = UserReferHistory::query()->where('refer_id', $order_details->user_id)->where('refer_status', 1)->latest()->first();
                                        if ($user_refer_history != Null) {
                                            $user_refer_history->refer_status = 0;
                                            $user_refer_history->save();
                                            $user_details->pending_refer_discount = $user_details->pending_refer_discount + 1;
                                            $user_details->save();
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        return response()->json([
                            "status" => 0,
                            //                            "message" => "something went to wrong!",
                            "message" => __('provider_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                    if ($user_details != Null) {
                        $this->notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status, $user_details->language);

                        $general_settings = request()->get("general_settings");
                        if ($general_settings !=  Null) {
                            if ($general_settings->send_mail == 1) {
                                $service_category = ServiceCategory::query()->where('id', $order_details->service_cat_id)->first();
                                if ($service_category != Null) {
                                    $email = $user_details->email;
                                    $service_name = ucwords(strtolower($service_category->name));
                                    $provider_name = ucwords($provider_details->first_name);
                                    $user_name = ($order_details->user_name != "") ? $order_details->user_name : "";
                                    try {
                                        $mail_type = "booking_cancel_–_handyman_services";
                                        $to_mail = $email;
                                        $subject = "Your " . $general_settings->mail_site_name . " " . $service_name . " service rejected by a " . $provider_name;
                                        $disp_data = array("##user_name##" => $user_name, "##service_name##" => $service_name, "##provider_name##" => $provider_name);
                                        $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                                    } catch (\Exception $e) {
                                    }
                                }
                            }
                        }
                    }
                    //deleting chat from firebase
                    (new FirebaseService())->deleteOrderChat($order_details->order_no, $order_details->id);
                    return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                    break;
                case 5:
                    if ($order_details->status == 2 || $order_details->status == 3) {
                        $validator = Validator::make($request->all(), [
                            "reason" => "required",
                        ]);
                        if ($validator->fails()) {
                            return response()->json([
                                "status" => 0,
                                "message" => $validator->errors()->first(),
                                "message_code" => 9,
                            ]);
                        }
                        $order_status = $order_details->status;
                        $order_details->status = $update_status;
                        $order_details->cancel_by = "provider";
                        $order_details->cancel_reason = $request->get('reason');
                        $order_details->save();

                        ProviderAcceptedPackageTime::query()->where('provider_id', '=', $provider_details->id)->where('order_id', '=', $order_details->id)->delete();

                        if ($order_status < 8) {
                            if ($order_details->promo_code > 0) {
                                $used_promocode_details = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                                if ($used_promocode_details != Null) {
                                    $get_promocode = PromocodeDetails::query()->where('id', $used_promocode_details->promocode_id)->first();
                                    if ($get_promocode != Null) {
                                        $count_promocode = $get_promocode->total_usage - 1;
                                        $get_promocode->total_usage = ($count_promocode > 0) ? $count_promocode : 0;
                                        $get_promocode->save();
                                    }
                                    $used_promocode_details->status = 2;
                                    $used_promocode_details->save();
                                }
                            }
                        }
                    } else {
                        return response()->json([
                            "status" => 0,
                            //                            "message" => "something went to wrong!",
                            "message" => __('provider_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                    if ($user_details != Null) {
                        $this->notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status, $user_details->language);
                    }
                    //deleting chat from firebase
                    (new FirebaseService())->deleteOrderChat($order_details->order_no, $order_details->id);
                    return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                    break;
                case 6:
                    if (in_array($order_details->status, [2, 3])) {
                        $on_demand_start_service_time = GeneralSettings::query()->select('on_demand_start_service_time')->first();
                        $start_service_time = ($on_demand_start_service_time != Null && $on_demand_start_service_time->on_demand_start_service_time != Null) ? $on_demand_start_service_time->on_demand_start_service_time : 60;
                        //                        if ($order_details->order_type == 1 || $order_details->service_date_time != Null) {
                        //                        }

                        $current_date_time = date('Y-m-d H:i:s');

                        $current_date_time = $this->notificationClass->convertTimezone($current_date_time, $default_server_timezone, $provider_timezone);

                        //                        $select_time = substr($order_details->service_time, 0, 8);
                        $select_time = $order_details->book_start_time;
                        $select_date_time = $order_details->service_date . " " . $select_time; //                        $pickup_date_time = strtotime(date('Y-m-d H:i:s', strtotime('-' . $start_service_time . ' minutes', strtotime($select_date_time))));

                        $pickup_date_time = date('Y-m-d H:i:s', strtotime('-' . $start_service_time . ' minutes', strtotime($select_date_time)));
                        //                            $current_date_time = strtotime(date('Y-m-d H:i:s'));
                        if (strtotime($current_date_time) < strtotime($pickup_date_time)) {
                            return response()->json([
                                "status" => 0,
                                //                                    "message" => "You can start the service before 60 minutes of requested time.",
                                "message" => __('provider_messages.117', ['value' => $start_service_time]),
                                "message_code" => 117,
                            ]);
                        }
                        $order_details->status = $update_status;
                        $order_details->save();
                    } else {
                        return response()->json([
                            "status" => 0,
                            //                            "message" => "something went to wrong!",
                            "message" => __('provider_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                    if ($user_details != Null) {
                        $this->notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status, $user_details->language);
                    }
                    return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                    break;
                case 7:
                    if ($order_details->status == 6) {
                        $order_details->status = $update_status;
                        $order_details->save();
                    } else {
                        return response()->json([
                            "status" => 0,
                            //                            "message" => "something went to wrong!",
                            "message" => __('provider_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                    if ($user_details != Null) {
                        $this->notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status, $user_details->language);
                    }
                    return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                    break;
                case 8:
                    if ($order_details->status == 7) {
                        $order_details->status = $update_status;
                        $order_details->save();
                    } else {
                        return response()->json([
                            "status" => 0,
                            //                            "message" => "something went to wrong!",
                            "message" => __('provider_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                    if ($user_details != Null) {
                        $this->notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status,  $user_details->language);
                    }
                    return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                    break;
                case 9:
                    if ($order_details->status == 8) {
                        //add the extra amount
                        $validator = Validator::make($request->all(), [
                            "extra_amount" => "nullable|numeric",
                        ]);
                        if ($validator->fails()) {
                            return response()->json([
                                "status" => 0,
                                "message" => $validator->errors()->first(),
                                "message_code" => 9,
                            ]);
                        }
                        $extra_amount = 0;
                        if ($request->get('extra_amount') > 0) {
                            //update the extra amount
                            $extra_amount = $request->get('extra_amount') / $currency;
                            UserPackageBooking::query()->where('id', $request->get('order_id'))->update(['extra_amount' => $extra_amount]);

                            //check weather the tax and admin commission is applied
                            $find_tax = ServiceSettings::query()->where('service_cat_id', $order_details->service_cat_id)->first();
                            $get_discount = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                            if ($get_discount != null) {
                                $promo_code_discount = $get_discount->discount_amount;
                            } else {
                                $promo_code_discount = 0;
                            }

                            if ($find_tax != Null) {
                                $get_tax = $find_tax->tax;
                                $admin_commission = $find_tax->admin_commission;
                            } else {
                                $get_tax = 0;
                                $admin_commission = 0;
                            }
                            $order_details->BookingCost($order_details->total_item_cost, $get_tax, $admin_commission, $promo_code_discount, $order_details->refer_discount, $extra_amount);
                            $extra_amount = 1;
                        }
                        //if ($order_details->payment_status == 1) {
                        $order_details->status = $update_status;
                        $order_details->save();

                        if ($order_details->promo_code > 0) {
                            $used_promocode_details = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                            if ($used_promocode_details != Null) {
                                $used_promocode_details->status = 1;
                                $used_promocode_details->save();
                            }
                        }
                        $other_provider_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_details->id)->first();
                        if ($other_provider_details != Null) {
                            $other_provider_details->total_completed_order = $other_provider_details->total_completed_order + 1;
                            $other_provider_details->save();
                        }
                        //} else {
                        //    return response()->json([
                        //        "status" => 0,
                        //        "message" => "payment process pending!",
                        //        "message_code" => 83,
                        //    ]);
                        //}
                    } else {
                        return response()->json([
                            "status" => 0,
                            //                            "message" => "something went to wrong!",
                            "message" => __('provider_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                    $service_category = ServiceCategory::query()->where('id', $order_details->service_cat_id)->first();
                    if ($user_details != Null) {
                        $this->notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status, $user_details->language, $extra_amount);
                    }
                    $general_settings = request()->get("general_settings");
                    if ($general_settings !=  Null) {
                        if ($general_settings->send_mail == 1) {
                            $service_category = ServiceCategory::query()->where('id', $order_details->service_cat_id)->first();
                            if ($service_category != Null) {
                                $user_name = ($order_details->user_name != "") ? $order_details->user_name : "";
                                $service_name = ucwords(strtolower($service_category->name));
                                $provider_name = ucwords($provider_details->first_name);
                                $date_time = date("Y-m-d h:i:s", strtotime($order_details->updated_at));

                                if ($user_details != Null) {
                                    try {
                                        $mail_type = "request_completed_–_handyman_services";
                                        $to_mail = $user_details->email;
                                        $subject = "Your " . $general_settings->mail_site_name . " " . $service_name . " service Completed";
                                        $disp_data = array("##user_name##" => $user_name, "##service_name##" => $service_name, "##provider_name##" => $provider_name, "##date_time##" => $date_time);
                                        $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                                    } catch (\Exception $e) {
                                    }
                                }
                                try {
                                    $mail_type = "provider_service_completed";
                                    $to_mail = $provider_details->email;
                                    $subject = $service_name . " Services Completed";
                                    $disp_data = array("##user_name##" => $user_name, "##service_name##" => $service_name, "##provider_name##" => $provider_name, "##date_time##" => $date_time);
                                    $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                                } catch (\Exception $e) {
                                }
                            }
                        }
                    }
                    ProviderAcceptedPackageTime::query()->where('provider_id', '=', $provider_details->id)->where('order_id', '=', $order_details->id)->delete();
                    //deleting chat from firebase
                    (new FirebaseService())->deleteOrderChat($order_details->order_no, $order_details->id);
                    return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                    break;
                default:
                    return response()->json([
                        "status" => 0,
                        //                            "message" => "something went to wrong!",
                        "message" => __('provider_messages.9'),
                        "message_code" => 9,
                    ]);
            }
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "order details not found!",
                "message" => __('provider_messages.59'),
                "message_code" => 59,
            ]);
        }
    }

    public function postOnDemandOrderDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "order_id" => "required|integer"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency->ratio;

        $order_details = UserPackageBooking::query()->where('id', $request->get('order_id'))->first();
        if ($order_details != Null) {
            $service_category = ServiceCategory::query()->where('id', $order_details->service_cat_id)->first();

            $lang_prefix = $this->adminClass->get_langugae_fields($provider_details->language);
            $fld = $lang_prefix . "name";
            $sub_cat_fld = $lang_prefix . "sub_category_name";
            $lang_service_cat = $service_category->$fld;
            $sub_service_cat = $sub_cat_fld;

            if ($service_category == Null) {
                return response()->json([
                    "status" => 0,
                    //                    "message" => "service category not found!",
                    "message" => __('provider_messages.9'),
                    "message_code" => 9,
                ]);
            }
            $other_provider_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_details->id)->first();
            $user_details = User::query()->where('id', $order_details->user_id)->whereNull('users.deleted_at')->first();
            if ($user_details->avatar != Null && $user_details != Null) {
                if (filter_var($user_details->avatar, FILTER_VALIDATE_URL) == true) {
                    $customer_profile_image = $user_details->avatar;
                } else {
                    $customer_profile_image = url('/assets/images/profile-images/customer/' . $user_details->avatar);
                }
            } else {
                $customer_profile_image = "";
            }
            $package_list = [];
            $get_package_list = UserPackageBookingQuantity::query()->select(
                'package_name',
                'num_of_items',
                //                'price_for_one',
                DB::raw('CAST(price_for_one * ' . $currency . ' AS DECIMAL(18,2)) As price_for_one'),
                //                DB::raw('ROUND(price_for_one * ' . $currency . ',2) As price_for_one'),
                DB::raw('num_of_items * (CAST(price_for_one * ' . $currency . ' AS DECIMAL(18,2))) As total_single_package_price'),
                $sub_service_cat . ' as sub_category_name'
            )->where('order_id', $order_details->id)->get()->groupBy('sub_category_name');
            foreach ($get_package_list as $key => $item) {
                $package_list[] = [
                    "category_name" => $key,
                    "packages" => $item
                ];
            }

            $discount = 0;
            /*$promo_code_discount = UsedPromocodeDetails::where('id', $order_details->promo_code)
                ->where('service_cat_id', $order_details->service_cat_id)
                ->where('user_id', $order_details->user_id)
                ->first();

            if ($promo_code_discount != Null) {
                $discount = $promo_code_discount->discount_amount;

            }*/

            if ($order_details->promo_code != 0) {
                try {
                    $get_discount = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                    $promo_code_discount = $get_discount->discount_amount;
                    $promocode_name = $get_discount->promocode_name;
                } catch (\Exception $e) {
                    $promo_code_discount = round($order_details->total_item_cost - $order_details->discount_cost + $order_details->tax_cost + $order_details->delivery_cost + $order_details->packaging_cost - $order_details->total_pay, 2);
                    $promocode_name = '';
                }
            } else {
                $promo_code_discount = 0;
                $promocode_name = '';
            }
            // $schedule_order_time= date("h:i A",strtotime($order_details->book_start_time))." - ".date("h:i A",strtotime($order_details->book_end_time));
            $schedule_order_time = Carbon::parse($order_details->book_start_time)->format('H:i:s')
                . " - " . Carbon::parse($order_details->book_end_time)->format('H:i:s');

            return response()->json([
                "status" => 1,
                //                "message" => "success!",
                "message" => __('provider_messages.1'),
                "message_code" => 1,
                "order_id" => $order_details->id,
                "order_no" => $order_details->order_no,
                "additional_remark" => "" . $order_details->remark,
                "order_type" => $order_details->order_type,
                "order_status" => $order_details->status,
                "order_cancel_by" => $order_details->cancel_by . "",
                "order_cancel_reason" => $order_details->cancel_reason . "",
                "order_payment_type" => $order_details->payment_type - 0,
                "order_payment_status" => $order_details->payment_status - 0,
                "provider_rating_status" => $order_details->provider_rating_status - 0,
                "payment_type" => $order_details->payment_type,
                "service_cat_name" => $lang_service_cat,
                "service_category_id" => $order_details->service_cat_id,
                //                "service_date" => Date('D d M, Y', strtotime($order_details->service_date_time)),
                //                "service_time" => Date('h:i A', strtotime($order_details->service_date_time)),

                /*"service_date" => Carbon::parse($order_details->created_at)->format('D d M, Y'),
                "service_time" => Carbon::parse($order_details->created_at)->format('h:i A'),
                "service_date_time" => Carbon::parse($order_details->created_at)->format('Y-m-d h:i:s'),*/
                //                "service_date" => $this->notificationClass->dateLangConvert(Date('D d M, Y', strtotime($order_details->created_at)),$provider_details->language),
                //                "service_time" => Date('h:i A', strtotime($order_details->created_at)),
                //                "schedule_order_date" => $this->notificationClass->dateLangConvert(Date('D d M, Y', strtotime($order_details->service_date)),$provider_details->language),
                "schedule_order_date" => $order_details->service_date,
                "schedule_order_time" => $schedule_order_time,
                "service_date_time" => Carbon::parse($order_details->created_at)->format('Y-m-d H:i:s'),
                //
                //                "schedule_order_date_time" => $order_details->service_date_time != Null ? $order_details->service_date_time : '',
                //                "schedule_order_date" => $order_details->service_date_time != Null ? $this->notificationClass->dateLangConvert(Date('D d M, Y', strtotime($order_details->service_date_time)),$provider_details->language) : '',
                //                "schedule_order_time" => $order_details->service_date_time != Null ? Date('h:i A', strtotime($order_details->service_date_time)) : '',

                "pickup_address" => $other_provider_details != Null ? $other_provider_details->address : '',
                "pickup_lat_long" => $other_provider_details != Null ? $other_provider_details->lat . ',' . $other_provider_details->long : '',
                "destination_address" => $order_details->delivery_address,
                "destination_lat_long" => $order_details->lat_long,
                "flat_no" => $order_details->flat_no != Null ? $order_details->flat_no : '',
                "landmark" => $order_details->landmark != Null ? $order_details->landmark : '',
                "customer_id" => $order_details->user_id,
                "customer_fcm_token" => $user_details != Null ? $user_details->device_token : '',
                "customer_name" => $user_details != Null ? trim($user_details->first_name) : '',
                "customer_contact_no" => $user_details != Null ? ($user_details->country_code != "" ? $user_details->country_code : "") . $user_details->contact_number : '',
                "customer_profile_image" => $customer_profile_image,
                "item_total" => number_format($order_details->total_item_cost * $currency, 2, '.', ''),
                "sub_total" => number_format((($order_details->total_pay - $order_details->tip) - $order_details->tax) * $currency, 2, '.', ''),
                "tax" => number_format($order_details->tax * $currency, 2, '.', ''),
                "refer_discount" => number_format($order_details->refer_discount * $currency, 2, '.', ''),
                "discount" => number_format(0 * $currency, 2, '.', ''),
                "tip" => number_format($order_details->tip * $currency, 2, '.', ''),
                'promocode_name' => $promocode_name,
                'promo_code_discount' => number_format($promo_code_discount * $currency, 2, '.', ''),
                "provider_pay_settle_status" => $order_details->provider_pay_settle_status,
                "extra_amount" => number_format($order_details->extra_amount * $currency, 2, '.', ''),
                "total_pay" => number_format($order_details->total_pay * $currency, 2, '.', ''),
                "select_provider_location" => $order_details->select_provider_location,
                "package_list" => $package_list,
                "order_chat_number" => (new FirebaseService())->CreateOrderNumberForChat($order_details->order_no, $order_details->id), //for fire base chat
            ]);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "order details not found!",
                "message" => __('provider_messages.59'),
                "message_code" => 59,
            ]);
        }
    }

    public function postOnDemandOrderHistory(Request $request)
    {
        $this->notificationClass->ApiLogDetail($logger_type = 3, $request->get('provider_id'), "postOnDemandOrderHistory", $request->all());
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "filter_type" => "nullable|in:0,1,2,3,4,5",
            "timezone" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $default_server_timezone = "";
        $general_settings = request()->get("general_settings");
        if ($general_settings != Null) {
            if ($general_settings->default_server_timezone != "") {
                $default_server_timezone = $general_settings->default_server_timezone;
            }
        }
        $timezone = $provider_details->time_zone != Null ? $provider_details->time_zone : $default_server_timezone;
        $currency = $provider_currency->ratio;

        if ($provider_details !== null) {
            $provider_timezone = $this->notificationClass->getDefaultTimeZone($timezone);
            date_default_timezone_set($provider_timezone);
        }

        $lang_prefix = $this->adminClass->get_langugae_fields($provider_details->language);

        $filter_type = $request->get('filter_type');
        $date = date('Y-m-d');
        if ($filter_type == 1) {
            //today
            //            $start_date = $date . " 00:00:01";
            //            $end_date = $date . " 23:59:59";
            $start_date = $date;
            $end_date = $date;
        } elseif ($filter_type == 2) {
            //last 7 day
            $start_date = date('Y-m-d', strtotime('-7 days', strtotime($date)));
            // $start_date = date('Y-m-d', strtotime($date . ' - 7 days'));
            $end_date = $date;
            //            $start_date = $start_date . " 00:00:01";
            //            $end_date = $end_date . " 23:59:59";
        } elseif ($filter_type == 3) {
            //last 30 day
            $start_date = date('Y-m-d', strtotime('-30 days', strtotime($date)));
            $end_date = $date;
            //            $start_date = $start_date . " 00:00:01";
            //            $end_date = $end_date . " 23:59:59";
        } elseif ($filter_type == 4) {
            //this year
            $start_date = date("Y-01-01", strtotime($date));
            $end_date = date("Y-m-d", strtotime($date));
            //            $start_date = $start_date . " 00:00:01";
            //            $end_date = $end_date . " 23:59:59";
        } elseif ($filter_type == 5) {
            //upcoming order
            $start_date = date('Y-m-d', strtotime('+1 days', strtotime($date)));
            $end_date = date('Y-m-d', strtotime('+365 days', strtotime($date)));
            //            $start_date = $start_date . " 00:00:01";
            //            $end_date = $end_date . " 23:59:59";
        } else { //$filter_type == 0//all order
            //last 365 day
            $start_date = date('Y-m-d', strtotime('-365 days', strtotime($date)));
            $end_date = $date;
            //            $start_date = $start_date . " 00:00:01";
            //            $end_date = $end_date . " 23:59:59";
        }
        $per_page = 10;
        if ($request->get('per_page') != Null) {
            $per_page = $request->get('per_page');
        }

        $order_history = UserPackageBooking::query()->select(
            'user_service_package_booking.id as order_id',
            'user_service_package_booking.order_no',
            'user_service_package_booking.user_name as customer_name',
            'user_service_package_booking.order_package_list',
            'user_service_package_booking.provider_pay_settle_status',
            'user_service_package_booking.select_provider_location',
            'service_category.id as service_category_id',
            DB::raw("DATE_FORMAT('user_service_package_booking.created_at', '%Y-%m-%d %H:%i:%s') as service_date_time"),
            DB::raw("DATE_FORMAT('user_service_package_booking.service_date_time', '%Y-%m-%d %H:%i:%s') as schedule_order_date_time"),
            //'user_service_package_booking.provider_amount as total_amount',
            DB::raw('ROUND(user_service_package_booking.provider_amount * ' . $currency . ',2) As total_amount'),
            'service_category.' . $lang_prefix . 'name as service_cat_name',
            'user_service_package_booking.status as order_status'
        )
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->where('user_service_package_booking.provider_id', $provider_details->id)
            ->whereNotIn('user_service_package_booking.status', [10, 11])
            ->orderBy('user_service_package_booking.created_at', 'desc');
        if ($filter_type != 5 && $filter_type != 0) {
            //            $order_history = $order_history->where('user_service_package_booking.service_date_time', '>=', $start_date)
            //                ->where('user_service_package_booking.service_date_time', '<=', $end_date);
            $order_history = $order_history->where('user_service_package_booking.service_date', '>=', $start_date)
                ->where('user_service_package_booking.service_date', '<=', $end_date);
        }
        if ($filter_type == 5) {
            //            $order_history = $order_history->where('user_service_package_booking.service_date_time', '>=', $start_date)
            //                ->whereIn('user_service_package_booking.status', [0, 1, 2, 3, 6, 7, 8]);
            $order_history = $order_history->where('user_service_package_booking.service_date', '>=', $start_date)
                ->whereIn('user_service_package_booking.status', [0, 1, 2, 3, 6, 7, 8]);
        }

        $order_history_get = $order_history->get()->toArray();
        $order_history_pagination = $order_history->paginate($per_page);
        $current_page = $order_history_pagination->currentPage();
        $last_page = $order_history_pagination->lastPage();
        $total = $order_history_pagination->total();

        $history_data = [];
        foreach ($order_history_pagination as $history) {
            $history_data[] = [
                'order_id' => $history->order_id,
                'order_no' => $history->order_no,
                'customer_name' => $history->customer_name,
                'total_amount' => number_format($history->total_amount, 2),
                'service_cat_name' => $history->service_cat_name,
                'provider_pay_settle_status' => $history->provider_pay_settle_status,
                'order_package_list' => $history->order_package_list,
                'order_status' => $history->order_status,
                'service_category_id' => $history->service_category_id
            ];
        }

        //$total_revenues_count = array_filter($order_history, function ($var) {
        //    return ($var['order_status'] == 1 || $var['order_status'] == 2 ||
        //        $var['order_status'] == 3 || $var['order_status'] == 6 ||
        //        $var['order_status'] == 7 || $var['order_status'] == 8 ||
        //        $var['order_status'] == 9);
        //});
        $total_revenues_count = array_filter($order_history_get, function ($var) {
            return ($var['order_status'] == 9 && $var['provider_pay_settle_status'] == 0);
        });

        $complete_orders = count(
            array_filter($order_history_get, function ($var) {
                return ($var['order_status'] == 9);
            })
        );
        $cancelled_orders = count(
            array_filter($order_history_get, function ($var) {
                return ($var['order_status'] == 4 || $var['order_status'] == 5 || $var['order_status'] == 10);
            })
        );
        $total_revenues = round(array_sum(array_column($total_revenues_count, 'total_amount')), 2);
        //$total_revenues = number_format(array_sum(array_column($order_history, 'total_amount')), 2);
        $pending_orders = count($order_history_get) - ($complete_orders + $cancelled_orders);
        return response()->json([
            "status" => 1,
            //            "message" => "success",
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            'current_page' => $current_page - 0,
            'last_page' => $last_page - 0,
            'total' => $total - 0,
            "pending_payments" => round($total_revenues * $currency, 2),
            "pending_order" => $pending_orders,
            "completed_order" => $complete_orders,
            "canceled order" => $cancelled_orders,
            "order_history" => $history_data
        ]);
    }

    public function postOnDemandChangeCurrentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "update_status" => "required|integer|in:0,1"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $get_provider_services = ProviderServices::where('provider_id', $provider_details->id)->whereIN('service_cat_id', $this->on_demand_service_id_array)->get();
        foreach ($get_provider_services as $provider_service) {
            $provider_service = ProviderServices::where('id', $provider_service->id)->first();
            if ($provider_service != Null) {
                $provider_service->current_status = $request->get('update_status');
                $provider_service->save();
            }
        }
        return response()->json([
            "status" => 1,
            //            "message" => "success!",
            "message" => __('provider_messages.1'),
            "message_code" => 1,
        ]);
    }

    public function postOnDemandChangePackageStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "provider_service_id" => "required|integer",
            "package_id" => "required|integer",
            "update_status" => "required|integer|in:0,1"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_service = ProviderServices::query()->where('id', $request->get('provider_service_id'))->whereIN('service_cat_id', $this->on_demand_service_id_array)->first();
        if ($provider_service != Null) {
            $update_package_status = OtherServiceProviderPackages::query()->where('id', $request->get('package_id'))->first();
            if ($update_package_status == Null) {
                return response()->json([
                    "status" => 0,
                    //                    "message" => "package not found!",
                    "message" => __('provider_messages.9'),
                    "message_code" => 9,
                ]);
            }
            $update_package_status->status = $request->get('update_status');
            $update_package_status->save();
            return response()->json([
                "status" => 1,
                //                "message" => "success!",
                "message" => __('provider_messages.1'),
                "message_code" => 1,
            ]);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "provider service not found!",
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function postOnDemandDeletePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "provider_service_id" => "required|integer",
            "package_id" => "required|integer"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency->ratio;

        $provider_service = ProviderServices::where('id', $request->get('provider_service_id'))->whereIN('service_cat_id', $this->on_demand_service_id_array)->first();
        if ($provider_service != Null) {
            $running_service = UserPackageBooking::query()
                ->join('user_package_booking_quantity', 'user_package_booking_quantity.order_id', '=', 'user_service_package_booking.id')
                ->where('user_service_package_booking.provider_id', $request->get('provider_id'))
                ->where('user_package_booking_quantity.package_id', $request->get('package_id'))
                ->whereIn('user_service_package_booking.status', [2, 3, 6, 7, 8])
                ->get();
            if (!$running_service->isEmpty()) {
                return response()->json([
                    "status" => 0,
                    "message" => __('provider_messages.310'),
                    "message_code" => 310,
                ]);
            }
            OtherServiceProviderPackages::where('id', $request->get('package_id'))->delete();
            $package_list = [];
            $sub_categories = OtherServiceCategory::where('service_cat_id', $provider_service->service_cat_id)->where('status', 1)->get();

            $lang_prefix = $this->adminClass->get_langugae_fields($provider_details->language);
            foreach ($sub_categories as $sub_category) {
                $packages = OtherServiceProviderPackages::select('id as package_id', 'name as package_name', 'description as package_description', 'max_book_quantity as package_max_book_quantity', DB::raw('ROUND(price * ' . $currency . ',2) As package_price'), 'status as package_status')->where('provider_service_id', $provider_service->id)->where('sub_cat_id', $sub_category->id)->get();
                if (!$packages->isEmpty()) {
                    $package_list[] = [
                        "category_id" => $sub_category->id,
                        "category_name" => $sub_category[$lang_prefix . "name"],
                        "packages" => $packages
                    ];
                }
            }
            return response()->json([
                "status" => 1,
                //                "message" => "success!",
                "message" => __('provider_messages.1'),
                "message_code" => 1,
                "package_list" => $package_list
            ]);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "provider service not found!",
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    public function postOnDemandOrderCollectPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "order_id" => "required|integer"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }
        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency->ratio;
        $order_details = UserPackageBooking::query()->where('id', $request->get('order_id'))->first();
        if ($order_details != Null) {
            if ($order_details->payment_status == 1) {
                if ($order_details->status == 9) {
                    return $this->onDemandClassApi->getOrderDispatcher($provider_details->id, $currency, $provider_details->status);
                } else {
                    return response()->json([
                        "status" => 0,
                        //                        "message" => "something went to wrong!",
                        "message" => __('provider_messages.9'),
                        "message_code" => 9,
                    ]);
                }
            } else {
                return response()->json([
                    "status" => 0,
                    //                    "message" => "Payment process pending!",
                    "message" => __('provider_messages.83'),
                    "message_code" => 83,
                ]);
            }
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "order details not found!",
                "message" => __('provider_messages.59'),
                "message_code" => 59,
            ]);
        }
    }

    public function postOnDemandOrderRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "order_id" => "required|integer",
            "rating" => "required|numeric",
            "comment" => "nullable"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }
        $order_details = UserPackageBooking::query()->where('id', $request->get('order_id'))->first();
        if ($order_details != Null) {
            if ($order_details->provider_rating_status == 0) {
                $user_rating = UserRatings::query()->where('user_id', $order_details->user_id)->where('package_book_id', $order_details->id)->first();
                if ($user_rating == Null) {
                    $user_rating = new UserRatings();
                    $user_rating->user_id = $order_details->user_id;
                    $user_rating->provider_id = $order_details->provider_id;
                    $user_rating->package_book_id = $order_details->id;
                    $user_rating->rating = round($request->get('rating'), 2);
                    if ($request->get('comment') != Null) {
                        $user_rating->comment = $request->get('comment');
                    }
                    $user_rating->status = 1;
                    $user_rating->save();

                    $order_details->provider_rating_status = 1;
                    $order_details->save();

                    $user_details = User::query()->where('id', $order_details->user_id)->whereNull('users.deleted_at')->first();
                    if ($user_details != Null) {
                        $ratings = UserRatings::query()
                            //                            ->select(DB::raw('avg(rating) as ratings'))
                            ->groupBy('user_id')
                            ->where('user_id', $order_details->user_id)
                            ->avg('rating');
                        $user_details->rating = round($ratings, 1);
                        $user_details->save();
                    }
                }
            }
            return response()->json([
                'status' => 1,
                //                'message' => "success!",
                'message' => __('provider_messages.1'),
                'message_code' => 1,
            ]);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => "order details not found!",
                "message" => __('provider_messages.59'),
                "message_code" => 59,
            ]);
        }
    }

    public function postUpdateProviderBankDetails(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "provider_id" => "required|numeric",
                "access_token" => "required",
                "account_number" => "required|numeric",
                "holder_name" => "required",
                "bank_name" => "required",
                "bank_location" => "required",
                "payment_email" => "required|email",
                "bic_swift_code" => "required"
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_id = $request->get('provider_id');
        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $bank_details = ProviderBankDetails::query()->where('provider_id', $provider_id)->first();
        if ($bank_details == Null) {
            $bank_details = new ProviderBankDetails();
        }
        $bank_details->provider_id = $provider_id;
        $bank_details->account_number = $request->get('account_number');
        $bank_details->holder_name = $request->get('holder_name');
        $bank_details->bank_name = $request->get('bank_name');
        $bank_details->bank_location = $request->get('bank_location');
        $bank_details->payment_email = $request->get('payment_email');
        $bank_details->bic_swift_code = $request->get('bic_swift_code');
        $bank_details->save();

        return response()->json([
            "status" => 1,
            //            "message" => "success!",
            "message" => __('provider_messages.1'),
            "message_code" => 1,
        ]);
    }

    public function postGetProviderBankDetails(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "provider_id" => "required|numeric",
                "access_token" => "required",
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        $provider_id = $request->get('provider_id');
        $provider_details = $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $bank_details = ProviderBankDetails::query()->where('provider_id', $provider_id)->first();
        if ($bank_details == Null) {
            return response()->json([
                "status" => 0,
                //                "message" => "driver bank details not found!",
                "message" => __('provider_messages.79'),
                "message_code" => 79,
            ]);
        }
        return response()->json([
            "status" => 1,
            //            "message" => "success!",
            "message" => __('provider_messages.1'),
            "message_code" => 1,
            "account_number" => $bank_details->account_number,
            "holder_name" => $bank_details->holder_name,
            "bank_name" => $bank_details->bank_name,
            "bank_location" => $bank_details->bank_location,
            "payment_email" => $bank_details->payment_email,
            "bic_swift_code" => $bank_details->bic_swift_code,
        ]);
    }

    //code add postOnDemandProviderServiceRegisterStep provider service register step
    public function postOnDemandProviderServiceRegisterStep(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|numeric",
            "access_token" => "required",
            "step" => "required|numeric|in:0,1,2,3,4",
            "landmark" => "nullable",
            "address" => "required_if:step,==,1",
            "contact_number" => "required_if:step,==,1",
            "select_country_code" => "required_if:step,==,1",
            "lat" => "required_if:step,==,1|numeric",
            "long" => "required_if:step,==,1|numeric",
            "service_radius" => "required_if:step,==,1|numeric",
            "service_category_id" => "required_if:step,==,2|numeric",
            "service_sub_category_id" => "required_if:step,==,3|numeric",
            "package_name" => "required_if:step,==,3",
            "package_description" => "required_if:step,==,3",
            "package_price" => "required_if:step,==,3|numeric",
            "max_book_quantity" => "required_if:step,==,3|numeric",
            "document_id" => "required_if:step,==,4",
            "document_file" => "nullable|required_if:step,==,4|file",
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }

        info('------------------------------------------------------ provider price');
        info($request->all());
        $step = $request->get('step');
        $provider_id = $request->get('provider_id');
        $access_token = $request->get('access_token');
        $provider_details = $this->onDemandClassApi->providerRegisterAllow($provider_id, $access_token);
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $language = $provider_details->language;
        if ($language != "en" && $language != "" && $language != "Null") {
            $lang_prefix = $language . "_";
        } else {
            $lang_prefix = "";
        }

        $provider_currency = WorldCurrency::query()->where('symbol', $provider_details->currency)->first();
        if ($provider_currency == Null) {
            $provider_currency = WorldCurrency::query()->where('default_currency', 1)->first();
        }
        $currency = $provider_currency->ratio;

        // if (isset($provider_details->currency) && $provider_details->currency <> null) {
        //     $currency = $provider_details->currency;
        //     $currency = WorldCurrency::query()->where('symbol', $currency)->first();
        // } else {
        //     $currency = WorldCurrency::query()->where('default_currency', 1)->first();
        // }

        if ($step == 1) {
            //step 1 get  detail from and update other_service_provider_details
            $landmark = $request->get('landmark');
            $address = $request->get('address');
            $lat = $request->get('lat');
            $long = $request->get('long');
            $service_radius = $request->get('service_radius');
            $contact_number = $request->get('contact_number');
            $select_country_code = $request->get('select_country_code');

            $check_contact = Provider::query()->where('contact_number', $request->get('contact_number'))
                ->where('country_code', $request->get('select_country_code'))
                ->whereNotIn('id', [$request->get('provider_id')])
                ->whereNull('providers.deleted_at')
                ->count();
            if ($check_contact > 0) {
                return response()->json([
                    "status" => 0,
                    //                        "message" => "Contact Number already been taken!",
                    "message" =>  __('provider_messages.12'),
                    "message_code" => 12,
                ]);
            }

            $get_other_service_provider = OtherServiceProviderDetails::query()->where('provider_id', '=', $provider_id)->first();
            if ($get_other_service_provider == Null) {
                $get_other_service_provider = new OtherServiceProviderDetails();
            }
            $get_other_service_provider->provider_id = $provider_id;
            $get_other_service_provider->address = $address;
            $get_other_service_provider->landmark = $landmark;
            $get_other_service_provider->lat = $lat;
            $get_other_service_provider->long = $long;
            $get_other_service_provider->save();

            Provider::query()->where('id', $provider_id)->update(array('completed_step' => 1, 'service_radius' => $service_radius, 'contact_number' => $contact_number, 'country_code' => $select_country_code));
            $default_provider_open_close_time =  $this->notificationClass->defaultProviderOpenCloseTime();

            $all_day_open_time = isset($default_provider_open_close_time['default_provider_start_time']) ? $default_provider_open_close_time['default_provider_start_time'] : "";
            $all_day_close_time = isset($default_provider_open_close_time['default_provider_end_time']) ? $default_provider_open_close_time['default_provider_end_time'] : "";
            $new_time_array = isset($default_provider_open_close_time['default_provider_slot']) ? $default_provider_open_close_time['default_provider_slot'] : [];

            $get_other_service_provider->start_time = $all_day_open_time;
            $get_other_service_provider->end_time = $all_day_close_time;
            $get_other_service_provider->time_list = "";
            $get_other_service_provider->save();
            $days = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];
            foreach ($days as $day) {

                if (count($new_time_array) > 0) {
                    foreach ($new_time_array as $single_time_arr) {
                        $provider_open_time =  $single_time_arr['start_time'];
                        $provider_close_time =  $single_time_arr['end_time'];
                        $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider_id)
                            ->where('day', $day)
                            ->where('provider_open_time', $provider_open_time)
                            ->where('provider_close_time', $provider_close_time)
                            ->first();
                        if ($get_open_timing == Null) {
                            $get_open_timing = new OtherServiceProviderTimings();
                            $get_open_timing->provider_id = $provider_id;
                            $get_open_timing->day = strtoupper(trim($day));
                            $get_open_timing->provider_open_time = $provider_open_time;
                            $get_open_timing->provider_close_time = $provider_close_time;
                            $get_open_timing->open_time_list = "";
                            $get_open_timing->save();
                        }
                    }
                }
            }
        } elseif ($step == 2) {
            //step 2 add service cateogry
            $service_category_id = $request->get('service_category_id');
            $get_provider_service =  ProviderServices::query()->where('provider_id', '=', $provider_id)->first();
            if ($get_provider_service == Null) {
                $get_provider_service =  new ProviderServices();
            } elseif ($get_provider_service->service_cat_id != $service_category_id) {
                OtherServiceProviderPackages::query()->where('provider_service_id', '=', $get_provider_service->id)->delete();
                $get_provider_service->delete();
            }

            //before update new service delete olrder service uploaded document if exist
            if ($get_provider_service->service_cat_id != $service_category_id) {
                //code for delete provider documents
                $provider_uploaded_doc = ProviderDocuments::query()->where('provider_service_id', $get_provider_service->id)->get();
                if (count($provider_uploaded_doc)) {
                }
            }

            $get_provider_service->provider_id = $provider_id;
            $get_provider_service->service_cat_id = $service_category_id;
            $get_provider_service->status = 1;
            $get_provider_service->current_status = 1;
            // $get_provider_service->status = 1;
            $get_provider_service->status = 0;
            $get_provider_service->save();
            Provider::query()->where('id', $provider_id)->update(array('completed_step' => 2));
        } elseif ($step == 3) {
            info('------------------------------------------------------ provider price 111');
            info($request->get('package_price'));
            //step 3  add packagess
            $service_category_id = $request->get('service_category_id');
            $service_sub_category_id = $request->get('service_sub_category_id');
            $package_name = $request->get('package_name');
            $package_description = $request->get('package_description');
            $package_price = $request->get('package_price');
            $max_book_quantity = $request->get('max_book_quantity');

             info('------------------------------------------------------ provider price 111');
            info($package_price);


             info('------------------------------------------------------ provider price 11');
            info(round($package_price / $currency, 2));

            $get_provider_service = ProviderServices::query()->where('provider_id', '=', $provider_id)->first();

            if ($get_provider_service != Null) {
                $provider_service_id = $get_provider_service->id;
                $add_provider_package = OtherServiceProviderPackages::query()->where('provider_service_id', $provider_service_id)->first();
                if ($add_provider_package == Null) {
                    $add_provider_package = new OtherServiceProviderPackages();
                }
                $add_provider_package->provider_service_id = $provider_service_id;
                $add_provider_package->sub_cat_id = $service_sub_category_id;
                $add_provider_package->service_cat_id = $service_category_id;
                $add_provider_package->name = $package_name;
                $add_provider_package->description = $package_description;
                $add_provider_package->price = round($package_price / $currency, 2);
                $add_provider_package->max_book_quantity = $max_book_quantity;
                $add_provider_package->status = 1;
                //                $add_provider_package->status = 0;
                $add_provider_package->save();
                Provider::query()->where('id', $provider_id)->update(array('completed_step' => 3));
            } else {
                return response()->json([
                    "status" => 0,
                    //                    "message" => "provider service not found!",
                    "message" =>  __('provider_messages.9'),
                    "message_code" => 9,
                ]);
            }
        } elseif ($step == 4) {
            //step 4  uploaded document
            $get_provider_service =  ProviderServices::query()->where('provider_id', '=', $provider_id)->first();
            if ($get_provider_service != Null) {

                $require_document = RequiredDocuments::query()->where('id', $request->get('document_id'))->first();
                if ($require_document != Null) {

                    $new_doc_service_cat_id = $require_document->service_cat_id;
                    //get and remove orlder service documenst data

                    $get_provider_doc_data = ProviderDocuments::query()->select('provider_documents.id as doc_id', 'provider_documents.document_file as document_file')
                        ->join('required_documents', 'required_documents.id', 'provider_documents.req_document_id')
                        ->where('required_documents.service_cat_id', '!=', $new_doc_service_cat_id)
                        ->where('provider_documents.provider_service_id', '=', $get_provider_service->id)
                        ->get();
                    if (count($get_provider_doc_data) > 0) {
                        foreach ($get_provider_doc_data as $remove_single_doc) {
                            if (\File::exists(public_path('/assets/images/provider-documents/' . $remove_single_doc->document_file))) {
                                \File::delete(public_path('/assets/images/provider-documents/' . $remove_single_doc->document_file));
                            }
                            ProviderDocuments::query()->where('id', $remove_single_doc->doc_id)->delete();
                        }
                    }

                    $upload_document = ProviderDocuments::query()->where('provider_service_id', $get_provider_service->id)->where('req_document_id', $require_document->id)->first();
                    if ($request->file('document_file') != Null) {
                        if ($upload_document != Null) {
                            if (\File::exists(public_path('/assets/images/provider-documents/' . $upload_document->document_file))) {
                                \File::delete(public_path('/assets/images/provider-documents/' . $upload_document->document_file));
                            }
                        }
                        $file = $request->file('document_file');
                        $file_new = rand(1, 9) . date('sihYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                        $file->move(public_path() . '/assets/images/provider-documents/', $file_new);
                    } else {
                        return response()->json([
                            "status" => 0,
                            //                            "message" => 'something went to wrong!',
                            "message" => __('provider_messages.9'),
                            "message_code" => 9,
                        ]);
                    }
                    if ($upload_document == Null) {
                        $upload_document = new ProviderDocuments();
                    }
                    $upload_document->provider_service_id = $get_provider_service->id;
                    $upload_document->req_document_id = $require_document->id;
                    $upload_document->document_file = $file_new;
                    $upload_document->status = 0;
                    $upload_document->save();
                } else {
                    return response()->json([
                        "status" => 0,
                        //                        "message" => "required document not found!",
                        "message" => __('provider_messages.9'),
                        "message_code" => 9,
                    ]);
                }
                Provider::query()->where('id', $provider_id)->update(array('completed_step' => 4));

                $general_settings = request()->get("general_settings");
                if ($general_settings !=  Null) {
                    if ($general_settings->send_mail == 1) {
                        try {
                            if ($general_settings != Null && $general_settings->send_receive_email != Null) {
                                $get_provider_service_list = ProviderServices::query()->select('service_category.name')
                                    ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
                                    ->where('provider_services.provider_id', $provider_details->id)
                                    ->whereIN('provider_services.service_cat_id', $this->on_demand_service_id_array)->get()
                                    ->pluck('name')->toArray();
                                $get_provider_service_count = count($get_provider_service_list);
                                if ($get_provider_service_count > 0) {
                                    $mail_type = "admin_new_provider_signup";
                                    $to_mail = $general_settings->send_receive_email;
                                    $provider_service_list = implode(" , ", $get_provider_service_list);
                                    $provider_email = $provider_details->email;
                                    $provider_contact_number = $provider_details->contact_number;
                                    $provider_name = ucwords($provider_details->first_name);
                                    $subject = "New Provider Registered";
                                    $disp_data = array("##provider_name##" => $provider_name, "##services_name##" => $provider_service_list, "##email##" => $provider_email, "##contact_no##" => $provider_contact_number);
                                    $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                                }
                            }
                        } catch (\Exception $e) {
                        }
                    }
                }
            } else {
                return response()->json([
                    "status" => 0,
                    //                    "message" => "provider service not found!",
                    "message" => __('provider_messages.9'),
                    "message_code" => 9,
                ]);
            }
        }

        return $provider_service_register_data = $this->onDemandClassApi->providerServiceRegisterData($provider_id);
    }

    //provider portfolio images upload
    public function postOnDemandUploadPortfolioImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "service_category_id" => "required|integer",
            "portfolio_image" => "nullable|file",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $access_token = $request->get('access_token');
        $service_category_id = $request->get('service_category_id');
        $portfolio_image = $request->get('portfolio_image');
        $provider_details = $this->onDemandClassApi->providerRegisterAllow($provider_id, $access_token);
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $provider_provide_service = ProviderServices::query()
            ->where('provider_id', '=', $provider_id)
            ->where('service_cat_id', '=', $service_category_id)
            ->first();
        if ($provider_provide_service != Null) {
            if ($request->file('portfolio_image') != Null) {
                if ($request->file('portfolio_image') != Null) {
                    $file = $request->file('portfolio_image');
                    $file_new = rand(1, 9) . date('sihYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path() . '/assets/images/provider-portfolio-images/', $file_new);
                } else {
                    return response()->json([
                        "status" => 0,
                        //                        "message" => 'something went to wrong!',
                        "message" => __('provider_messages.9'),
                        "message_code" => 9,
                    ]);
                }
                $provider_portfolio_document = new ProviderPortfolioImage();
                $provider_portfolio_document->service_cat_id = $service_category_id;
                $provider_portfolio_document->provider_id = $provider_id;
                $provider_portfolio_document->image = $file_new;
                $provider_portfolio_document->status = 1;
                $provider_portfolio_document->save();
            }
            return $this->onDemandClassApi->providerPortfolioList($provider_id, $service_category_id);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => 'Sorry, you are not provided service in this service category',
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }

    //delete portfolio image
    public function postOnDemandDeletePortfolioImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required|integer",
            "access_token" => "required|numeric",
            "image_id" => "required|numeric",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first(),
                "message_code" => 9,
            ]);
        }
        $provider_id = $request->get('provider_id');
        $access_token = $request->get('access_token');
        $id = $request->get('image_id');
        $provider_details = $this->onDemandClassApi->providerRegisterAllow($provider_id, $access_token);
        if ($decoded['status'] = json_decode($provider_details) == false) {
            return $provider_details;
        }

        $single_portfolio_img = ProviderPortfolioImage::query()->where('id', '=', $id)->first();
        if ($single_portfolio_img != Null) {
            if ($single_portfolio_img != Null) {
                if (\File::exists(public_path('/assets/images/provider-portfolio-images/' . $single_portfolio_img->image))) {
                    \File::delete(public_path('/assets/images/provider-portfolio-images/' . $single_portfolio_img->image));
                }
            }
            $service_category_id = ($single_portfolio_img->service_cat_id > 0) ? $single_portfolio_img->service_cat_id : 0;
            ProviderPortfolioImage::query()->where('id', $id)->delete();
            return $this->onDemandClassApi->providerPortfolioList($provider_id, $service_category_id);
        } else {
            return response()->json([
                "status" => 0,
                //                "message" => 'something went to wrong!',
                "message" => __('provider_messages.9'),
                "message_code" => 9,
            ]);
        }
    }
}
