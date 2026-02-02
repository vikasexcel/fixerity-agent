<?php

namespace App\Http\Controllers\Api;

use App\Classes\AdminClass;
use App\Classes\DriverClassApi;
use App\Classes\NotificationClass;
use App\Classes\OnDemandClassApi;
use App\Classes\StoreClassApi;
use App\Classes\UserClassApi;
use App\Models\Faqs;
use App\Models\ReportIssues;
use App\Models\ReportIssueImage;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
//use App\Services\FirebaseService;
use Intervention\Image\Laravel\Facades\Image;

class ReportIssueController extends Controller
{
    //0:user,1:store,2:driver,3:provider
    private $userClassapi;
    private $onDemandClassApi;
    private $adminClass;
    private $notificationClass;
    private $user_type = 0;
    private $provider_type = 3;
    public function __construct(UserClassApi $userClassapi, OnDemandClassApi $onDemandClassApi, AdminClass $adminClass, NotificationClass $notificationClass)
    {
        $this->userClassapi = $userClassapi;
        $this->onDemandClassApi = $onDemandClassApi;
        $this->adminClass = $adminClass;
        $this->notificationClass = $notificationClass;
    }

    /**
     * Fetch report issue FAQs list for a user or provider.
     */
    public function postReportIssueFaqsList(Request $request)
    {
        try {
            // Validate request input
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric",
                "access_token" => "required|numeric",
                "provider_type" => "required|numeric|in:0,3", // 0 = user, 3 = provider
            ]);

            // Return validation error if any
            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            // Check provider access based on provider_type
            $provider_check = $request->provider_type == 0
                ? $this->userClassapi->checkUserAllow($request->provider_id, $request->access_token)
                : $this->onDemandClassApi->providerRegisterAllow($request->provider_id, $request->access_token);

            // Decode and verify the response
            //checking provider status
            if ($decoded['status'] = json_decode($provider_check) == false) {
                return $provider_check;
            }

            // Determine language-specific column names
            $lang = $provider_check->language ?? 'en';
            $nameCol = ($lang !== "en" && $lang !== "" && $lang !== "Null") ? "{$lang}_name" : "name";
            $descCol = ($lang !== "en" && $lang !== "" && $lang !== "Null") ? "{$lang}_description" : "description";

            // Fetch active FAQs with language-specific fields
            $faqs = Faqs::select("id", "{$nameCol} as name", "{$descCol} as description")
                ->where('status', 1)
                ->get();

            // Count existing report issues for the provider
            $report_count = ReportIssues::where([
                ['provider_id', '=', $request->provider_id],
                ['provider_type', '=', $request->provider_type],
            ])
                ->where('status', '!=', 0)
                ->count();

            // Return successful response
            return response()->json([
                'status' => 1,
                'message' => __('user_messages.1'), // Success
                'message_code' => 1,
                'faqs' => $faqs, // faqs collection
                // maximum and minimum report issue image upload limit for app side validations
                'min_report_issue_image_upload' => request()->get('general_settings')->min_report_issue_image_upload,
                'max_report_issue_image_upload' => request()->get('general_settings')->max_report_issue_image_upload,
                'report_issue_count' => $report_count,
            ]);
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'status' => 0,
                'message' => __('user_messages.333'), // Oops! Something went wrong. Please try again soon
                'message_code' => 333,
            ]);
        }
    }


    /**
     * Function used for save Report issue as draft
     */
    public function postReportIssueDraft(Request $request)
    {
        try {
            //Validation rules
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric", // Validate provider id
                "access_token" => "required|numeric", // Validate access token
                "provider_type" => "required|numeric|in:0,3", // 0 = user, 3 = provider
                "order_id" => "required|numeric", // Validate order id
                "service_category_id" => "required|numeric" // Validate service category id
            ]);
            //Validation error handling
            if ($validator->fails()) {
                //return response on validation fails
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            // Check access based on provider_type
            $provider_check = $request->get('provider_type') == 0
                ? $this->userClassapi->checkUserAllow($request->get('provider_id'), $request->get('access_token'))
                : $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));

            //checking provider status
            if ($decoded['status'] = json_decode($provider_check) == false) {
                return $provider_check;
            }

            // Delete any existing draft report issue for this provider
            if ($existingDraft = ReportIssues::where([
                ['provider_id', $request->get('provider_id')],
                ['provider_type', $request->get('provider_type')],
                ['status', 0]
            ])->first()) {

                // Delete associated images from filesystem and database
                $imagePaths = ReportIssueImage::where('report_issue_id', $existingDraft->id)
                    ->pluck('image')
                    ->map(function ($img) {
                        return str_replace('\\', '/', public_path("assets/images/report-issue/$img"));
                    })->toArray();

                if (!empty($imagePaths)) {
                    File::delete($imagePaths); // Delete physical files
                    ReportIssueImage::where('report_issue_id', $existingDraft->id)->delete(); // Delete DB records
                }

                $existingDraft->delete(); // Delete the draft issue itself
            }

            // Create new draft report issue
            $report_issue = ReportIssues::create([
                'reference_no' => ReportIssues::generateReferenceNo(),
                'order_id' => $request->get('order_id'),
                'provider_id' => $request->get('provider_id'),
                'service_cat_id' => $request->get('service_category_id'),
                'provider_type' => $request->get('provider_type'),
                'status' => 0, // 0 = Draft
            ]);

            // Return success response with new report issue ID and image upload limits
            return response()->json([
                'status' => 1,
                'message' => __('user_messages.1'), // Success message
                'message_code' => 1,
                'report_id' => $report_issue->id,
                'min_report_issue_image_upload' => request()->get("general_settings")->min_report_issue_image_upload,
                'max_report_issue_image_upload' => request()->get("general_settings")->max_report_issue_image_upload,
            ]);
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'status' => 0,
                'message' => __('user_messages.333'), // Oops! Something went wrong. Please try again soon
                'message_code' => 333,
            ]);
        }
    }


    /**
     * Function used for update report issue
     */
    public function postUpdateReportIssue(Request $request)
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric", // Validate provider id
                "access_token" => "required|numeric", // Validate access token
                "provider_type" => "required|numeric|in:0,3", // 0 = user, 3 = provider
                "order_id" => "required|numeric", // Validate order id
                "description" => "required|max:200", // Validate description
                "report_id" => "required|numeric|min:1", // Validate report id
            ]);
            //Validation error handling
            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            // provider_type: 0 = user, 3 = provider
            // fetching provider details
            $provider_check = $request->provider_type == 0
                ? $this->userClassapi->checkUserAllow($request->provider_id, $request->access_token)
                : $this->onDemandClassApi->providerRegisterAllow($request->provider_id, $request->access_token);

            //checking provider status
            if ($decoded['status'] = json_decode($provider_check) == false) {
                return $provider_check;
            }

            // Get general settings for get values of min_images and max_images
            $general_settings = request()->get("general_settings");
            $min_images = $general_settings->min_report_issue_image_upload ?? 1; // Minimum number of images allowed
            $max_images = $general_settings->max_report_issue_image_upload ?? 1; // Maximum number of images allowed

            // Count existing images for the report_id
            $image_count = ReportIssueImage::where('report_issue_id', $request->get('report_id'))->count();

            // Check if adding a new image exceeds the maximum limit
            if (($image_count < $min_images) || ($image_count > $max_images)) {
                // return response with minimum and maximum image limit violation
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.334', ['min' => $min_images,'max' => $max_images]),
                    "message_code" => 334
                ]);
            }

            // Add description & update status 0 to 1 of report issue for that provider
            $report_issue = ReportIssues::query()
                ->select('report_issues.*', 'service_category.name as service_name', 'user_service_package_booking.order_no as order_no')
                ->leftJoin('service_category', 'report_issues.service_cat_id', '=', 'service_category.id') // Join with service category table details
                ->leftJoin('user_service_package_booking', 'report_issues.order_id', '=', 'user_service_package_booking.id') // Join with user_service_package_booking table details
                ->where('report_issues.id', '=', $request->get('report_id')) // Filter by report issue id
                ->where('report_issues.provider_id', '=', $request->get('provider_id')) // Filter by provider id
                ->first(); // Fetch the updated record details

            // reported data found then update information related to that issue
            if ($report_issue != null) {
                $report_issue->description = $request->get('description');
                $report_issue->status = 1;
                $report_issue->save();

                // Sending email to report issue creator & admin
                if ($general_settings !=  Null){
                    // check if send mail is enabled
                    if ($general_settings->send_mail == 1) {
                        // Sending email to report issue creator
                        // Get issue category
                        $issue_category = $report_issue->service_name != Null ? ucwords(strtolower($report_issue->service_name)) . " - #" . $report_issue->order_no : "General Issue";
                        try {
                            $mail_type = "new_issue_reported_-_to_issue_creator"; // Send mail type
                            $to_mail = $provider_check->email; // Send mail to
                            $subject = "Your report issue #" . $report_issue->reference_no . " has been submitted successfully"; // Send mail subject
                            $disp_data = array("##created_by##" => $provider_check->first_name, "##mail_site_name##" => $general_settings->mail_site_name,
                                "##ticket_id##" => "#" . $report_issue->reference_no, "##created_on##" => \Carbon\Carbon::parse($report_issue->created_at)->format('Y-m-d H:i'),
                                "##issue_category##" => $issue_category, "##issue_description##" => $report_issue->description); // Send mail data
                            // Send mail according to above details
                            $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                        } catch (\Exception $e) {
                            \Log::error("Failed to send email to creator: " . $e->getMessage());
                        }
                        // Sending email to admin
                        if ($general_settings->send_receive_email != Null ){
                            // Get issue creator type
                            $providerTypes = [0 => "user", 3 => "provider"];
                            $creator_type = $providerTypes[$request->get('provider_type')] ?? "provider";
                            // Get URL for link
                            $link = url("/admin/report-issue/$report_issue->id/$report_issue->provider_id");
                            try {
                                $mail_type = "new_issue_reported_-_to_admin"; // Send mail type
                                $to_mail = $general_settings->send_receive_email; // Send mail to
                                $subject = "New issue reported - Ticket ID #" . $report_issue->reference_no; // Send mail subject
                                $disp_data = array("##creator_type##" => $creator_type, "##created_by##" => $provider_check->first_name,
                                    "##ticket_id##" => "#" . $report_issue->reference_no, "##created_on##" => \Carbon\Carbon::parse($report_issue->created_at)->format('Y-m-d H:i'),
                                    "##issue_category##" => $issue_category, "##issue_description##" => $report_issue->description, "##link##" => $link); // Send mail data
                                // Send mail according to above details
                                $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                            } catch (\Exception $e) {
                                \Log::error("Failed to send email to admin: " . $e->getMessage());
                            }
                        }
                    }
                }
            } else {
                // return response
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.335'), // Sorry, we couldn’t find the details for the issue you reported
                    "message_code" => 335
                ]);
            }

            // return success response
            return response()->json([
                'status' => 1,
                'message' => __('user_messages.1'), // success
                'message_code' => 1
            ]);
        } catch(\Exception $e){
            // Handle unexpected errors
            return response()->json([
                'status' => 0,
                'message' => __('user_messages.333'), // Oops! Something went wrong. Please try again soon
                'message_code' => 333,
            ]);
        }
    }

    /**
     * Function used for upload report issue image
     */
    public function postReportIssueUploadImage(Request $request)
    {
        try{
            // Validate the request
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric", // Validate provider id
                "access_token" => "required|numeric", // Validate access token
                "provider_type" => "required|numeric|in:0,3", // 0 = user, 3 = provider
                "report_id" => "required|numeric", // Validate report id
                "image" => "required|file|mimes:jpg,jpeg,png,webp|dimensions:ratio=1/1", // Validate image file types
            ]);

            // Return success response with images
            $image_path = url('/assets/images/report-issue/');
            $report_images = ReportIssueImage::select('id',
                DB::raw("IF(image != '', CONCAT('$image_path/', image), '') AS image"))
                ->where('report_issue_id', $request->report_id)
                ->get()
                ->toArray();

            // Return error & report issue images if validation fails
            if ($validator->fails()) {
                return response()->json([
                    "status" => 1,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                    "image" => $report_images, // return all report issue images
                    "is_image_valid" => 0
                ]);
            }

            // Check access based on provider_type
            $provider_check = $request->get('provider_type') == 0
                ? $this->userClassapi->checkUserAllow($request->get('provider_id'), $request->get('access_token'))
                : $this->onDemandClassApi->providerRegisterAllow($request->get('provider_id'), $request->get('access_token'));

            //checking provider status
            if ($decoded['status'] = json_decode($provider_check) == false) {
                return $provider_check;
            }

            // Fetch report id for image upload of that provider
            $report_id = ReportIssues::query()->where('id', '=', $request->get('report_id'))->first();

            // Check if report_id exists
            if ($report_id != null) {
                // try catch block
                try {
                    // Get the uploaded image from the request
                    $file = $request->file('image');
                    // Define the upload path
                    $uploadPath = public_path('/assets/images/report-issue/');
                    // Create the directory if it doesn't exist
                    if (!File::isDirectory($uploadPath)) {
                        File::makeDirectory($uploadPath, 0775, true, true);
                    }
                    // Give a unique name to the image
                    $file_new = rand(1, 9) . date('sihYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                    // Move the uploaded file to the specified folder
                    $file->move($uploadPath, $file_new);

                    // Save the image in the ReportIssueImage model
                    $report_issue_image = new ReportIssueImage();
                    $report_issue_image->report_issue_id = $request->get('report_id'); // Associating with report_id
                    $report_issue_image->image = $file_new;
                    $report_issue_image->save();

                    // Fetch and format all images for the given report_id
                    $image_path = url('/assets/images/report-issue/');
                    // fetch report issue images
                    $report_images = ReportIssueImage::select('id',
                        DB::raw("IF(image != '', CONCAT('$image_path/', image), '') AS image"))
                        ->where('report_issue_id', $request->report_id)
                        ->get()
                        ->toArray();

                    // Return success response with images
                    return response()->json([
                        "status" => 1,
                        "message" => __('user_messages.1'),
                        "message_code" => 1,
                        "image" => $report_images, // return all report issue images
                        "is_image_valid" => 1
                    ]);
                }
                    // Catch any exceptions
                catch (\Exception $e) {
                    return response()->json([
                        'status' => 0,
                        'message' => __('user_messages.333'), // Oops! Something went wrong. Please try again soon
                        'message_code' => 333,
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 0,
                    'message' => __('user_messages.335'), // Sorry, we couldn’t find the details for the issue you reported
                    'message_code' => 335,
                ]);
            }
        } catch(\Exception $e){
            return response()->json([
                'status' => 0,
                'message' => __('user_messages.333'), // Oops! Something went wrong. Please try again soon
                'message_code' => 333,
            ]);
        }
    }

    /**
     * Function used for Remove report issue image
     */
    public function postReportIssueRemoveImage(Request $request)
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric",
                "access_token" => "required|numeric",
                "provider_type" => "required|numeric|in:0,3", // 0 = user, 3 = provider
                "image_id" => "required|numeric",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            // Validate provider access based on type
            $provider_check = $request->provider_type == 0
                ? $this->userClassapi->checkUserAllow($request->provider_id, $request->access_token)
                : $this->onDemandClassApi->providerRegisterAllow($request->provider_id, $request->access_token);

            //checking provider status
            if ($decoded['status'] = json_decode($provider_check) == false) {
                return $provider_check;
            }

            // Find image record
            $image = ReportIssueImage::find($request->image_id);

            if (!$image) {
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.0'), // Data not found
                    "message_code" => 0
                ]);
            }

            // Try to delete image file and database record
            try {
                $file_path = public_path("/assets/images/report-issue/{$image->image}");

                if (\File::exists($file_path)) {
                    \File::delete($file_path);
                }

                $report_id = $image->report_issue_id;
                $image->delete(); // Delete from database

                // Fetch updated image list for the report
                $report_issue_images = ReportIssueImage::where('report_issue_id', $report_id)
                    ->select('id', DB::raw("CASE WHEN image != '' THEN CONCAT('" . url('/assets/images/report-issue') . "/', image) ELSE '' END AS image"))
                    ->get()
                    ->toArray();

                return response()->json([
                    "status" => 1,
                    "message" => __('user_messages.1'), // Success
                    "message_code" => 1,
                    "image" => $report_issue_images // return all report issue images
                ]);
            } catch (\Exception $e) {
                // On deletion failure
                return response()->json([
                    "status" => 0,
                    "message" => $e->getMessage(),
                    "message_code" => 9,
                ]);
            }
        } catch (\Exception $e) {
            // General failure
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.333'), // Oops! Something went wrong. Please try again soon
                'message_code' => 333,
            ]);
        }
    }

    /**
     * Function for Fetch report issue details for particular provider
     */
    public function postReportIssueDetails(Request $request)
    {
        try {
            // Validate the request with validation rules
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric", // Validate provider id
                "access_token" => "required|numeric", // Validate access token
                "provider_type" => "required|numeric|in:0,3", // 0 = user, 3 = provider
                "report_id" => "required|numeric" // Validate report id
            ]);

            // Return error if validation fails
            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            // Validate provider access based on type
            $provider_check = $request->provider_type == 0
                ? $this->userClassapi->checkUserAllow($request->provider_id, $request->access_token)
                : $this->onDemandClassApi->providerRegisterAllow($request->provider_id, $request->access_token);

            //checking provider status
            if ($decoded['status'] = json_decode($provider_check) == false) {
                return $provider_check;
            }

            // Get report issue details based on report_id for that particular provider
            $report_issue = ReportIssues::query()
                ->select('report_issues.*', 'user_service_package_booking.order_no as order_no')
                ->leftJoin('service_category', 'report_issues.service_cat_id', '=', 'service_category.id') // Join for get service category details
                ->leftJoin('user_service_package_booking', 'report_issues.order_id', '=', 'user_service_package_booking.id') // Join for get user_service_package_booking table details
                ->where('report_issues.id', '=', $request->get('report_id')) // Filter for report_id
                ->where('report_issues.provider_id', '=', $request->get('provider_id')) // Filter for provider_id
                ->where('report_issues.provider_type', '=', $request->get('provider_type')) // Filter for provider_type
                ->first();

            // Return error if report_issue is null
            if (!$report_issue) {
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.335'), // Sorry, we couldn’t find the details for the issue you reported
                    "message_code" => 335,
                ]);
            }

            // Store report issue image url in variable
            $report_issue_images_url = url('/assets/images/report-issue/');
            // Fetch related images from the report_issue_image table and construct URLs in the $report_issue_images array
            $report_issue_images = ReportIssueImage::query()->select(DB::raw("CONCAT('$report_issue_images_url', '/', image) AS image"))
                ->where('report_issue_id', '=', $request->get('report_id')) // Filter for report_issue_id
                ->get()->toArray(); // Convert the result to a plain array

            // Return report issue details
            return response()->json([
                'status' => 1,
                'message' => __('user_messages.1'),
                'message_code' => 1,
                'reference_no' => $report_issue->reference_no,
                'order_no' => (string) $report_issue->order_no,
                'description' => $report_issue->description,
                'images' => $report_issue_images, // List of image URLs
                'report_chat_number' => (new FirebaseService())->CreateOrderNumberForChat($report_issue->reference_no,$report_issue->id) ,//for fire base chat
                'min_report_issue_image_upload' => request()->get("general_settings")->min_report_issue_image_upload,
                'max_report_issue_image_upload' => request()->get("general_settings")->max_report_issue_image_upload
            ]);
        } catch (\Exception $e) {
            // General failure
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.333'), // Oops! Something went wrong. Please try again soon
                'message_code' => 333,
            ]);
        }
    }

    /**
     * Function for show report issue history of handyman service orders
     */
    public function postOnDemandReportIssueHistory(Request $request)
    {
        try {
            // 1. Validate the request
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric|min:1", // Validation for provider id
                "access_token" => "required", // Validation for access token
                "provider_type" => "required|numeric|in:0,3",// 0 = user, 3 = provider
                "filter_type" => "nullable|in:0,1,2,3,4,5",// 0 = all, 1 = today, 2 = last 7 days, 3 = last 30 days, 4 = this year, 5 = upcoming order, 6 = last 365 days
                "timezone" => "required", // Validation for timezone
                "service_category" => "nullable", // Validation for service category
                "status" => "nullable", // 1 = un-resolved, 2 = resolved
                "general_issue_filter" => "required|in:0,1", // 0 = all, 1 = general issue
                "per_page" => "required|numeric", // Validation for per page
                "page" => "required|numeric" // Validation for page
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            // 2. Authenticate Provider/User
            $provider_check = $request->provider_type == 0
                ? $this->userClassapi->checkUserAllow($request->provider_id, $request->access_token)
                : $this->onDemandClassApi->providerRegisterAllow($request->provider_id, $request->access_token);

            //checking provider status
            if ($decoded['status'] = json_decode($provider_check) == false) {
                return $provider_check;
            }

            // 3. Language Prefix and Timezone
            $lang_prefix = $this->adminClass->get_langugae_fields($provider_check->language);
            date_default_timezone_set($this->notificationClass->getDefaultTimeZone($request->timezone));

            // 4. Date Filtering
            $today = date('Y-m-d');
            $start_date = $end_date = null;

            switch ($request->filter_type) {
                case 1: // Today
                    $start_date = "$today 00:00:00";
                    $end_date = "$today 23:59:59";
                    break;
                case 2: // Last 7 Days
                    $start_date = date('Y-m-d', strtotime('-7 days')) . " 00:00:00";
                    $end_date = "$today 23:59:59";
                    break;
                case 3: // Last 30 Days
                    $start_date = date('Y-m-d', strtotime('-30 days')) . " 00:00:00";
                    $end_date = "$today 23:59:59";
                    break;
                case 4: // This Year
                    $start_date = date("Y-01-01") . " 00:00:00";
                    $end_date = "$today 23:59:59";
                    break;
                case 5: // Upcoming
                    $start_date = date('Y-m-d', strtotime('+1 day')) . " 00:00:00";
                    $end_date = date('Y-m-d', strtotime('+365 days')) . " 23:59:59";
                    break;
                default: // Last 365 Days
                    $start_date = date('Y-m-d', strtotime('-365 days')) . " 00:00:00";
                    $end_date = "$today 23:59:59";
            }

            // 5. Build Report Issue Query
            $query = ReportIssues::select(
                'report_issues.id', 'report_issues.reference_no', 'report_issues.status',
                DB::raw("CASE WHEN service_category.category_type IN (3, 4, 6) THEN IFNULL(user_service_package_booking.order_no, 0) ELSE 0 END as order_no"),
                DB::raw("COALESCE(service_category.{$lang_prefix}name, service_category.name) as category_name"),
                DB::raw("DATE_FORMAT(report_issues.created_at, '%Y-%m-%d %H:%i:%s') as report_issue_date_time"),
                'service_category.id as category_id', 'service_category.icon_name as category_icon'
            )
                ->leftJoin('service_category', 'report_issues.service_cat_id', '=', 'service_category.id')
                ->leftJoin('user_service_package_booking', 'report_issues.order_id', '=', 'user_service_package_booking.id')
                ->where('report_issues.provider_id', $request->provider_id)
                ->where('report_issues.provider_type', $request->provider_type)
                ->where('report_issues.status', '!=', 0)
                ->orderByDesc('report_issues.id');

            // 6. Provider vs User Specific Filters
            if ($request->provider_type != 0) {
                if ($request->general_issue_filter == 0) {
                    $query->whereRaw("CASE WHEN report_issues.order_id != 0 THEN service_category.category_type IN (3, 4, 6) ELSE true END");
                } elseif ($request->general_issue_filter == 1) {
                    $query->where('report_issues.order_id', 0);
                }
            } else {
                $query->whereIn('service_category.category_type', [3, 4, 6]);
            }

            // 7. Date Filter Application
            if ($request->filter_type != 0) {
                $query->whereBetween('report_issues.created_at', [$start_date, $end_date]);
            }

            // 8. Status Filter (Resolved/Unresolved)
            if ($request->filled('status') && trim($request->status) !== '') {
                $statuses = explode(',', $request->status);
                $filteredStatuses = array_intersect([1, 2], array_map('intval', $statuses));

                if (!empty($filteredStatuses)) {
                    $query->whereIn('report_issues.status', $filteredStatuses);
                }
            }

            // 9. Paginate and Format
            $report_issues = $query->paginate($request->per_page);
            $last_page = $report_issues->lastPage();
            $general_settings = $request->get('general_settings');
            $default_icon = url('/assets/images/report-issue/logo/' . $general_settings->general_report_issue_icon);
            $default_name = __('user_messages.336');

            $data = $report_issues->map(function ($issue) use ($default_icon, $default_name) {
                return [
                    "report_id" => $issue->id,
                    "reference_no" => $issue->reference_no,
                    "order_no" => (string) $issue->order_no,
                    "category_icon" => $issue->category_icon
                        ? url('/assets/images/service-category/' . $issue->category_icon)
                        : $default_icon,
                    "category_name" => $issue->category_icon
                        ? ucwords(strtolower($issue->category_name))
                        : $default_name,
                    "service_category_id" => $issue->category_id,
                    "status" => $issue->status,
                    "report_issue_date_time" => $issue->report_issue_date_time,
                ];
            });

            // 10. Response
            return response()->json([
                "status" => 1,
                "message" => __('user_messages.1'),
                "message_code" => 1,
                "current_page" => (int) $request->page,
                "last_page" => $last_page,
                "total" => $report_issues->total(),
                "report_issue_history" => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.333'),
                'message_code' => 333,
            ]);
        }
    }

    /**
     * Show general report issue history.
     */
    public function postGeneralReportIssueHistory(Request $request)
    {
        try {
            // 1. Validate the request input
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|numeric|min:1", // Validate provider id
                "access_token" => "required", // Validate access token
                "provider_type" => "required|numeric|in:0",// 0 = user
                "filter_type" => "nullable|in:0,1,2,3,4,5",// 0 = all, 1 = today, 2 = last 7 days, 3 = last 30 days, 4 = this year, 5 = upcoming order, 6 = last 365 days
                "timezone" => "required", // Validate timezone
                "status" => "nullable", // 1 = un-resolved, 2 = resolved
                "per_page" => "required|numeric", // Validate per page
                "page" => "required|numeric" // Validate page
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "status"       => 0,
                    "message"      => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            // 2. Check provider validity
            $providerCheckResponse = $request->provider_type == 0
                ? $this->userClassapi->checkUserAllow($request->provider_id, $request->access_token)
                : $this->onDemandClassApi->providerRegisterAllow($request->provider_id, $request->access_token);

            if (!json_decode($providerCheckResponse)) {
                return $providerCheckResponse;
            }

            // 3. Set timezone
            $timezone = $this->notificationClass->getDefaultTimeZone($request->timezone);
            date_default_timezone_set($timezone);
            $today = date('Y-m-d');

            // 4. Determine date range based on filter type
            $start_date = $end_date = null;
            switch ($request->filter_type) {
                case 1: // Today
                    $start_date = $today . " 00:00:01";
                    $end_date = $today . " 23:59:59";
                    break;
                case 2: // Last 7 days
                    $start_date = date('Y-m-d', strtotime('-7 days')) . " 00:00:01";
                    $end_date = $today . " 23:59:59";
                    break;
                case 3: // Last 30 days
                    $start_date = date('Y-m-d', strtotime('-30 days')) . " 00:00:01";
                    $end_date = $today . " 23:59:59";
                    break;
                case 4: // This year
                    $start_date = date("Y-01-01") . " 00:00:01";
                    $end_date = $today . " 23:59:59";
                    break;
                case 5: // Upcoming
                    $start_date = date('Y-m-d', strtotime('+1 day')) . " 00:00:01";
                    $end_date = date('Y-m-d', strtotime('+365 days')) . " 23:59:59";
                    break;
                default: // All (last 365 days)
                    $start_date = date('Y-m-d', strtotime('-365 days')) . " 00:00:01";
                    $end_date = $today . " 23:59:59";
            }

            // 5. Parse status (optional)
            $statuses = $request->status ? explode(",", $request->status) : [];

            // 6. Query report issues
            $query = ReportIssues::select(
                'report_issues.id',
                'report_issues.reference_no',
                'report_issues.status',
                DB::raw("'0' as order_no"),
                DB::raw("DATE_FORMAT(report_issues.created_at, '%Y-%m-%d %H:%i:%s') as report_issue_date_time")
            )
                ->where('provider_id', $request->provider_id)
                ->where('provider_type', $request->provider_type)
                ->where('status', '!=', 0)
                ->where('order_id', '=', 0)
                ->orderByDesc('id');

            // 7. Apply date filters if not "all"
            if ($request->filter_type != 0) {
                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            // 8. Apply status filter if given
            if (!empty($statuses)) {
                $validStatuses = array_intersect([1, 2], $statuses);
                if (!empty($validStatuses)) {
                    $query->whereIn('status', $validStatuses);
                }
            }

            // 9. Paginate results
            $perPage = $request->per_page;
            $currentPage = $request->page;
            $reportIssues = $query->paginate($perPage, ['*'], 'page', $currentPage);

            // 10. Calculate last page manually
            $total = $reportIssues->total();
            $lastPage = ceil($total / $perPage);

            // 11. Format response
            $general_settings = $request->get('general_settings');
            $report_issue_history = $reportIssues->map(function ($issue) use ($general_settings) {
                return [
                    "report_id"              => $issue->id,
                    "reference_no"           => $issue->reference_no,
                    "order_no"               => (string) $issue->order_no,
                    "category_icon"          => url('/assets/images/report-issue/logo/' . $general_settings->general_report_issue_icon),
                    "category_name"          => __('user_messages.336'),
                    "status"                 => $issue->status,
                    "report_issue_date_time" => $issue->report_issue_date_time,
                ];
            });

            // 12. Return success response
            return response()->json([
                "status"               => 1,
                "message"              => __('user_messages.1'),
                "message_code"         => 1,
                "current_page"         => $currentPage,
                "last_page"            => $lastPage,
                "total"                => $total,
                "report_issue_history" => $report_issue_history,
            ]);

        } catch (\Exception $e) {
            // Log error if needed: Log::error($e);
            return response()->json([
                "status"       => 0,
                "message"      => __('user_messages.333'),
                'message_code' => 333,
            ]);
        }
    }

    /**
     * Upload a chat image into a specific folder based on report_chat_number
     */
    public function uploadChatPhoto(Request $request)
    {
        try {
            // Validate input: report_chat_number is required and chat_image must be a valid image file
            $validator = Validator::make($request->all(), [
                "report_chat_number" => "required",
                "chat_image" => "required|file|mimes:jpeg,png,jpg,webp", // Allow only specific image formats
            ]);

            // If validation fails, return the first error message
            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(),
                    "message_code" => 9,
                ]);
            }

            // Retrieve the chat identifier
            $reportChatNumber = $request->report_chat_number;

            // Build the path for storing the chat image
            $imagePath = public_path('/assets/images/report-issue-images/' . $reportChatNumber . '/');

            // Check if a file is uploaded
            if ($request->hasFile('chat_image')) {
                // Create directory if it doesn't exist
                if (!File::isDirectory($imagePath)) {
                    File::makeDirectory($imagePath, 0755, true, true); // Create nested directory with proper permissions
                }

                // Get the uploaded file instance
                $file = $request->file('chat_image');

                // Load and auto-orient the image using Intervention Image
                $image = Image::read($file->getRealPath())->orient();

                // Generate a unique file name
                $fileName = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();

                // Resize image to 300x300 while maintaining aspect ratio and save it to the target path
                $image->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($imagePath . $fileName);

                // Return success with image URL
                return response()->json([
                    "status" => 1,
                    "message" => __('user_messages.1'), // Success
                    "message_code" => 1,
                    'chat_image_path' => url('/assets/images/report-issue-images/' . $reportChatNumber . '/' . $fileName),
                ]);
            }

            // If no file was actually uploaded
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.333'), // Fallback message
                'message_code' => 333,
            ]);
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.333'), // Fallback message
                'message_code' => 333,
            ]);
        }
    }

    /**
     * Delete chat images for a specific chat_id by removing its directory
     */
    public function deleteChatPhoto(Request $request)
    {
        try {
            // Validate that chat_id is provided
            $validator = Validator::make($request->all(), [
                "chat_id" => "required", // Must be present
            ]);

            // Return error if validation fails
            if ($validator->fails()) {
                return response()->json([
                    "status" => 0,
                    "message" => $validator->errors()->first(), // Return first error
                    "message_code" => 9,
                ]);
            }

            // Build the directory path once for reuse
            $chatDir = public_path('/img/assets/chat-images/' . $request->chat_id);

            try {
                // Check if the directory exists, then delete it
                if (File::isDirectory($chatDir)) {
                    File::deleteDirectory($chatDir); // Recursively delete all files inside
                }

                // Success response
                return response()->json([
                    "status" => 1,
                    "message" => __('user_messages.1'), // Success message
                    "message_code" => 1,
                ]);

            } catch (\Exception $e) {
                // Error during directory deletion
                return response()->json([
                    "status" => 0,
                    "message" => __('user_messages.333'), // General failure message
                    'message_code' => 333,
                ]);
            }

        } catch (\Exception $e) {
            // General outer try-catch for unexpected failures
            return response()->json([
                "status" => 0,
                "message" => __('user_messages.333'), // General fallback error
                'message_code' => 333,
            ]);
        }
    }

}
