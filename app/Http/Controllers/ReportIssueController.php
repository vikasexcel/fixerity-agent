<?php

namespace App\Http\Controllers;

use App\Classes\AdminClass;
use App\Classes\AuthAlertClass;
use App\Classes\NotificationClass;
use App\Http\Requests\FaqsRequest;
use App\Http\Requests\ReportIssueSettingRequest;
use App\Models\Admin;
use App\Models\Faqs;
use App\Models\Provider;
use App\Models\ReportIssueImage;
use App\Models\LanguageLists;
use App\Models\ReportIssues;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Exception;
use Intervention\Image\Laravel\Facades\Image;

class ReportIssueController extends Controller
{
    private $adminClass;
    private $notificationClass;
    protected $is_restricted = 0;


//    public static function middleware(): array
//    {
//        return [
//            'auth',
//            new Middleware(function (Request $request, Closure $next) {
//               /* From 8.2 version of php there was issue that demo admin was not restricted and was able to make changes
//                    due to changes in OOPS concepts as laravel 11 & php 8.2 is more secure,
//                    so we need to first make object of the controller to use it's variable to append values in middleware()
//                */
//                $is_restrict_admin = $request->get('is_restrict_admin');
//                $controller = $request->route()->getController();
//                $controller->is_restricted = $is_restrict_admin;
//                return $next($request);
//            }),
//        ];
//    }
    public function __construct(AdminClass $adminClass, NotificationClass $notificationClass)
    {
        $this->middleware('auth');
        $this->adminClass = $adminClass;
        $this->notificationClass = $notificationClass;
    }

    //customer report issues
    public function showReportIssues($type_of_list, Request $request)
    {
        // Initialize the provider type and user list query
        $provider_type = 0;
        $user_list = User::query();

        // Determine the type of list to be fetched
        if ($type_of_list == "customer") {
            // For customers: provider_type remains 0, use User model
            $provider_type = 0;
            $user_list = User::query();
        } elseif ($type_of_list == "provider") {
            // For providers: provider_type is 3, use Provider model
            $provider_type = 3;
            $user_list = Provider::query();
        } else {
            // Invalid type, show 404 page
            return view('website.404_page');
        }

        // Build the base query: select necessary fields
        $user_list = $user_list->select('id', 'first_name', 'contact_number', 'country_code')
            ->whereNull('deleted_at') // Exclude deleted users
            ->whereNotNull('first_name'); // Exclude users without a first name

        // If dealing with providers, add provider-specific filters
        if ($provider_type > 0) {
            $user_list = $user_list->where('provider_type', $provider_type);

            // If the admin has role 4, filter by their city (area_id)
            if (request()->get("admin_role") == 4) {
                $user_list->where('providers.area_id', request()->get("admin_city_id"));
            }
        }

        // Only include active users (status = 1)
        $user_list = $user_list->where('status', 1)->get();

        // Prepare the view with the fetched data
        $view = view('admin.pages.super_admin.report_issues.manage', compact('provider_type', 'user_list'));

        // If the request is AJAX, return only the view sections
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }

        // For normal requests, return the full view
        return $view;
    }


    //update report issues status
    public function updateReportIssuesStatus(Request $request)
    {
        // Check if admin actions are restricted (for demo purposes)
        // if ($this->is_restricted == 1) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
        //     ]);
        // }

        // Validate that the report issue ID is provided
        if ($request->get('id') == null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }

        // Fetch the report issue with necessary joins to get detailed info
        $report_issue = ReportIssues::query()
            ->select(
                'report_issues.*',
                // Determine the creator's name based on provider type
                DB::raw("CASE
                WHEN report_issues.provider_type = 0 THEN users.first_name
                ELSE providers.first_name
            END as created_by"),
                // Get the language of the creator
                DB::raw("CASE
                WHEN report_issues.provider_type = 0 THEN users.language
                ELSE providers.language
            END as language"),
                // Get the email of the creator
                DB::raw("CASE
                WHEN report_issues.provider_type = 0 THEN users.email
                ELSE providers.email
            END as email"),
                // Get the device token of the creator
                DB::raw("CASE
                WHEN report_issues.provider_type = 0 THEN users.device_token
                ELSE providers.device_token
            END as device_token"),
                // Get the service name
                'service_category.name as service_name',
                // Get the order number for specific categories
                DB::raw("CASE
                WHEN report_issues.service_cat_id = 0 THEN '0'
                WHEN service_category.category_type IN (3, 4, 6) THEN IFNULL(user_service_package_booking.order_no, 0)
                ELSE '0'
            END as order_no")
            )
            ->leftJoin('service_category', 'report_issues.service_cat_id', '=', 'service_category.id')
            ->leftJoin('user_service_package_booking', 'report_issues.order_id', '=', 'user_service_package_booking.id')
            ->leftJoin('users', 'report_issues.provider_id', '=', 'users.id')
            ->leftJoin('providers', 'report_issues.provider_id', '=', 'providers.id')
            ->where('report_issues.id', '=', $request->get('id'))
            ->first();

        // If no report issue found, return error
        if ($report_issue == null) {
            return response()->json(['success' => false, 'message' => 'Report Issue details not found']);
        }

        // If the report issue is unresolved (status == 1), proceed to resolve it
        if ($report_issue->status == 1) {
            $report_issue->status = 2; // Mark as resolved
            $report_issue->resolved_on = now(); // Set current timestamp as resolved_on
            $report_issue->save();

            // Check if device token exists to send push notification
            if ($report_issue->device_token == null) {
                return response()->json([
                    'status' => 0,
                    'message' => __('user_messages.9'),
                    'message_code' => 9,
                ]);
            }

            // Determine language for the notification
            $language = $report_issue->language != null ? $report_issue->language : 'en';
            $title = __('user_messages.337', [], $language); // Notification title
            $title_code = 337;
            $message = __('user_messages.338', ['value' => $report_issue->reference_no], $language); // Notification message
            $message_code = 338;

            // Notification data payload
            $notification_data_array = [
                'title' => $title . "",
                'title_code' => $title_code . "",
                'sound' => "true",
                'notification_type' => "12", // Type of notification for resolved issue
                'report_id' => "{$report_issue->id}",
                'message' => $message . "",
                'body' => $message . "",
                'message_code' => $message_code . "",
                "click_action" => "FLUTTER_NOTIFICATION_CLICK"
            ];

            // Send push notification
            (new AuthAlertClass())->sendFlowNotification($report_issue->device_token, $notification_data_array, 0, null, 0);

            // Send email to issue creator if mail is enabled in general settings
            $general_settings = request()->get("general_settings");
            if ($general_settings != null && $general_settings->send_mail == 1) {
                $issue_category = $report_issue->service_name != null ? ucwords(strtolower($report_issue->service_name)) . " - #" . $report_issue->order_no : "General Issue";
                try {
                    $mail_type = "reported_issue_resolved_-_to_issue_creator";
                    $to_mail = $report_issue->email;
                    $subject = "Your report issue #" . $report_issue->reference_no . " has been resolved";
                    $disp_data = [
                        "##created_by##" => $report_issue->created_by,
                        "##ticket_id##" => "#" . $report_issue->reference_no,
                        "##resolved_on##" => \Carbon\Carbon::parse($report_issue->resolved_on)->format('Y-m-d H:i'),
                        "##issue_category##" => $issue_category,
                        "##issue_description##" => $report_issue->description
                    ];
                    $mail_return_data = $this->notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                } catch (\Exception $e) {
                    // Catch and ignore any email exceptions
                }
            }

            // Return success response along with resolved timestamp
            return response()->json([
                'success' => true,
                'message' => 'Issue has been marked as resolved',
                'resolved_on' => \Carbon\Carbon::parse($report_issue->resolved_on)->format('Y-m-d H:i')
            ]);
        }

        // If the report issue is already resolved
        return response()->json(['success' => false, 'message' => 'Report Issue is already resolved']);
    }

    //Fetch report issues
    public function getReportIssue(Request $request, $providerType)
    {
        try {
            // DataTable-specific parameters
            $draw = $request->get('draw'); // DataTable draw counter
            $start = $request->get("start"); // Offset
            $rowperpage = $request->get("length"); // Rows per page

            // Columns that can be sorted, mapped to database columns
            $sortableColumns = [
                'description' => 'description',
                'created_by' => 'provider_type',
                'created_at' => 'created_at',
                'order_no' => 'order_id',
                'service_name' => 'service_name',
                'resolved_on' => 'resolved_on',
            ];

            // Get sorting information
            $columnIndex = $request->input('order.0.column'); // index of the column being sorted
            $columnName = $request->input("columns.$columnIndex.data"); // column name
            $columnSortOrder = $request->input('order.0.dir', 'asc'); // sorting direction
            $searchValue = $request->input('search.value', ''); // global search keyword
            $status = $request->input('status'); // filter by status
            $orderByColumn = $sortableColumns[$columnName] ?? 'created_at'; // default sorting column

            // Base query
            $query = ReportIssues::query()
                ->select('report_issues.*', 'service_category.name as service_name')
                ->leftJoin('service_category', 'report_issues.service_cat_id', '=', 'service_category.id')
                ->leftJoin('user_service_package_booking', 'report_issues.order_id', '=', 'user_service_package_booking.id')
                ->leftJoin('users', 'report_issues.provider_id', '=', 'users.id')
                ->leftJoin('providers', 'report_issues.provider_id', '=', 'providers.id')
                ->where('report_issues.provider_type', $providerType)
                ->whereIn('report_issues.status', [1, 2]); // show both resolved and unresolved initially

            // Search and filter logic
            if (empty($status)) {
                // If no specific status filter, apply global search
                $query->where(function ($q) use ($searchValue) {
                    if ($searchValue) {
                        $q->where('report_issues.description', 'like', "%$searchValue%")
                            ->orWhere('service_category.name', 'like', "%$searchValue%")
                            ->orWhere('report_issues.reference_no', 'like', "%$searchValue%")
                            ->orWhereRaw("(CASE
                            WHEN report_issues.provider_type = 0 THEN users.first_name
                            ELSE providers.first_name
                        END) LIKE ?", ["%$searchValue%"])
                            ->orWhereRaw("(CASE
                            WHEN service_category.category_type IN (3, 4, 6) THEN IFNULL(user_service_package_booking.order_no, 0)
                            ELSE 0
                        END) LIKE ?", "%$searchValue%");
                    }
                });
            } else {
                // If status filter provided (resolved or unresolved)
                $statusFilter = $status == "resolved" ? 2 : 1;
                $query->where('report_issues.status', $statusFilter);

                // Apply search within that status
                if ($searchValue) {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('report_issues.description', 'like', "%$searchValue%")
                            ->orWhere('service_category.name', 'like', "%$searchValue%")
                            ->orWhere('report_issues.reference_no', 'like', "%$searchValue%")
                            ->orWhereRaw("(CASE
                            WHEN report_issues.provider_type = 0 THEN users.first_name
                            ELSE providers.first_name
                        END) LIKE ?", ["%$searchValue%"])
                            ->orWhereRaw("(CASE
                            WHEN service_category.category_type IN (3, 4, 6) THEN IFNULL(user_service_package_booking.order_no, 0)
                            ELSE 0
                        END) LIKE ?", "%$searchValue%");
                    });
                }
            }

            /* -------------------- Access control for City Admin -------------------- */
            $admin_role = request()->get("admin_role");
            if ($admin_role == 4) {
                $admin_city_id = request()->get("admin_city_id");
                $query->where(function ($q) use ($admin_city_id) {
                    $q->where('providers.area_id', $admin_city_id)
                        ->orWhere('providers.area_id', $admin_city_id);
                });
            }

            /* -------------------- Date filters -------------------- */
            if ($request->from_date && $request->to_date) {
                $from = date('Y-m-d 00:00:00', strtotime($request->from_date));
                $to = date('Y-m-d 23:59:59', strtotime($request->to_date));
                $query->whereBetween('report_issues.created_at', [$from, $to]);
            } elseif ($request->from_date && !$request->to_date) {
                $from = date('Y-m-d 00:00:00', strtotime($request->from_date));
                $query->where('report_issues.created_at', '>=', $from);
            } elseif (!$request->from_date && $request->to_date) {
                $to = date('Y-m-d 23:59:59', strtotime($request->to_date));
                $query->where('report_issues.created_at', '<=', $to);
            }

            /* -------------------- Filter by driver/provider -------------------- */
            if ($request->created_by != null) {
                $query->where(function ($q) use ($request) {
                    $q->where('providers.id', '=', $request->created_by);
                });
            }

            /* -------------------- Count total matching records -------------------- */
            $totalRecords = $query->count();

            /* -------------------- Fetch paginated and sorted records -------------------- */
            $records = $query->selectRaw("
            report_issues.*,
            CASE
                WHEN report_issues.provider_type = 0 THEN users.first_name
                ELSE providers.first_name
            END as created_by,
            CASE
                WHEN service_category.category_type IN (3, 4, 6) THEN IFNULL(user_service_package_booking.order_no, 0)
                ELSE 0
            END as order_no
        ")
                ->orderBy($orderByColumn, $columnSortOrder)
                ->skip($start)
                ->take($rowperpage)
                ->get();

            /* -------------------- Format data for DataTable -------------------- */
            $data_arr = $records->map(function ($record, $index) use ($start) {
                $statusText = $record->status == 2 ? "Resolved" : "Unresolved";
                $displayStatusStyle = $record->status == 2 ? "display:none" : "";

                return [
                    "id" => $start + $index + 1,
                    "ticket_id" => '#' . $record->reference_no,
                    "description" => $record->description,
                    "created_by" => $record->created_by ?? "N/A",
                    "created_at" => \Carbon\Carbon::parse($record->created_at)->format('Y-m-d H:i'),
                    "order_no" => $record->order_no ? '#' . $record->order_no : 'N/A',
                    "service_name" => $record->service_name ?? 'N/A',
                    "resolved_on" => '<span id="resolved_on_' . $record->id . '">' .
                        ($record->resolved_on ? \Carbon\Carbon::parse($record->resolved_on)->format('Y-m-d H:i') : 'N/A') .
                        '</span>',
                    "view" => '<a href="' . route('get:admin:detailed_report', [$record->id, $record->provider_id]) . '" class="btn btn-primary btn-sm" style="width:15px;padding:8px 21px 8px 10px">
                    <i class="fas fa-eye"></i>
                </a>',
                    "chat" => '<a href="' . route('get:admin:report_issue_chat', [$record->id]) . '" class="btn btn-primary btn-sm" style="width:15px;padding:8px 21px 8px 10px">
                    <i class="fas fa-comment-dots"></i>
                </a>',
                    "status" => '<span id="status_' . $record->id . '" class="' . ($record->status == 2 ? "resolved" : "unresolved") . '">' . $statusText . '</span>
                    <i id="changeStatus_' . $record->id . '" style="padding: 10px; ' . $displayStatusStyle . '"
                    class="changeStatus fa fa-thumbs-up" data-id="' . $record->id . '" aria-hidden="true" title="' . $statusText . '"></i>',
                ];
            });

            /* -------------------- Return final JSON response for DataTable -------------------- */
            return response()->json([
                "draw" => intval($draw),
                "iTotalRecords" => $totalRecords,
                "iTotalDisplayRecords" => $totalRecords,
                "aaData" => $data_arr,
            ]);

        } catch (Exception $exception) {
            // Log the error for debugging
            \Log::error($exception);
            // Return error response
            return response()->json(['error' => 'An error occurred while fetching the report issues.'], 500);
        }
    }

    //manage details of the report
    public function showReportDetails($id, $provider_id)
    {
        // Fetching the report issue details, including the creator's name and service information
        $report_issue = ReportIssues::query()
            ->select(
                'report_issues.*',
                // Determine whether the created_by is from users or providers table
                DB::raw("CASE
                WHEN report_issues.provider_type = 0 THEN users.first_name
                ELSE providers.first_name
            END as created_by"),
                // Service category name and icon
                'service_category.name as service_name',
                'service_category.icon_name as category_icon',
                // Determine the order_no based on category_type or set to '0'
                DB::raw("CASE
                WHEN report_issues.service_cat_id = 0 THEN '0'
                WHEN service_category.category_type IN (3, 4, 6) THEN IFNULL(user_service_package_booking.order_no, 0)
                ELSE '0'
            END as order_no")
            )
            // Join with service_category for category details
            ->leftJoin('service_category', 'report_issues.service_cat_id', '=', 'service_category.id')
            // Join with booking details for order_no if relevant
            ->leftJoin('user_service_package_booking', 'report_issues.order_id', '=', 'user_service_package_booking.id')
            // Join with users and providers for creator's name
            ->leftJoin('users', 'report_issues.provider_id', '=', 'users.id')
            ->leftJoin('providers', 'report_issues.provider_id', '=', 'providers.id')
            // Filter by report issue id
            ->where('report_issues.id', '=', $id)
            // Filter by provider id
            ->where('report_issues.provider_id', '=', $provider_id)
            // Fetch the first matching record
            ->first();

        // Check if the report issue is found
        if ($report_issue == null) {
            Session::flash('danger', 'Report issue not found');
            return redirect()->back();
        }

        // Path to the images folder
        $report_issue_image_path = url('/assets/images/report-issue/');

        // Fetch report issue images and build full URL for each
        $report_issue_images = ReportIssueImage::query()
            ->select(
                'id',
                DB::raw("(CASE
                WHEN image != '' THEN (concat('$report_issue_image_path','/',image))
                ELSE ''
            END) as image")
            )
            ->where('report_issue_id', '=', $id) // Filter images by report issue id
            ->get()
            ->toArray();

        // Return the view with the fetched data
        return view('admin.pages.super_admin.report_issues.details', compact('id', 'report_issue', 'report_issue_images'));
    }

    //Form of report issue setting
    public function getAdminReportIssueSetting(Request $request)
    {
        return view("admin.pages.super_admin.report_issues.report_issue_setting");
    }

    //updating the report issue settings
    public function postAdminUpdateReportIssueSetting(ReportIssueSettingRequest $request)
    {
        // Check if modifications are restricted (e.g., for a demo environment)
        // if ($this->is_restricted == 1) {
        //     Session::flash('error', 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.');
        //     return redirect()->back();
        // }

        // Handle icon file upload
        if ($request->file('general_report_issue_icon')) {
            // Delete the existing icon if it exists
            $currentIcon = request()->get("general_settings")->general_report_issue_icon;
            $currentIconPath = public_path('/assets/images/report-issue/logo/' . $currentIcon);
            if (\File::exists($currentIconPath)) {
                \File::delete($currentIconPath);
            }

            // Save the new icon file
            $file = $request->file('general_report_issue_icon');
            $file_new = random_int(1, 99) . date('siHYdm') . random_int(1, 99) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path() . '/assets/images/report-issue/logo/', $file_new);
            request()->get("general_settings")->general_report_issue_icon = $file_new;
        }

        // Update report issue settings
        $general_settings = request()->get("general_settings");
        $general_settings->report_chat_history_delete = $request->get('report_chat_history_delete');
        $general_settings->chat_deletion_days_after_issue_resolution = $request->get('chat_deletion_days_after_issue_resolution');
        $general_settings->min_report_issue_image_upload = $request->get('min_report_issue_image_upload');
        $general_settings->max_report_issue_image_upload = $request->get('max_report_issue_image_upload');

        // Save the updated settings
        $general_settings->save();

        // Flash success message based on whether the settings were updated or added
        if ($general_settings != null) {
            Session::flash('success', 'Report Issue Setting Updated successfully!');
        } else {
            Session::flash('success', 'Report Issue Setting Added successfully!');
        }

        // Redirect back to the settings page
        return redirect()->route('get:admin:report_issue_setting');
    }

    //Manage Faqs
    public function showFaq()
    {
        // returning manage faq page
        return view("admin.pages.super_admin.faqs.manage");
    }

    //Fetch Faqs
    public function getFaq(Request $request)
    {
        try {
            // Get datatable details from request
            $draw = $request->get('draw'); // Count how many records will be returned
            $start = $request->get("start"); // Start from
            $rowperpage = $request->get("length"); // Rows display per page

            $columnIndex_arr = $request->get('order'); // Column index
            $columnName_arr = $request->get('columns'); // Column name
            $order_arr = $request->get('order'); // Order
            $search_arr = $request->get('search'); // Search

            $columnIndex = $columnIndex_arr[0]['column']; // Column index
            $columnName = $columnName_arr[$columnIndex]['data']; // Column name
            $columnSortOrder = $order_arr[0]['dir']; // asc or desc
            $searchValue = $search_arr['value']; // Search value

            // Fetch records
            $records = Faqs::query()->select('id', 'name', 'status')
                ->orderBy($columnName, $columnSortOrder); // Apply sorting here

            $totalRecords = $records->count(); // Total records

            // Check if search value exist
            if ($searchValue != null) {
                // Search query
                $records->where(function ($query) use ($searchValue) {
                    $query->where('name', 'like', '%' . $searchValue . '%'); // Search by name
                });
            }

            $iTotalDisplayRecords = $records->count(); // Total records after search

            $records = $records
                ->skip($start) // Start from
                ->take($rowperpage) // Show records
                ->get(); // Get records

            // Initialize data array
            $data_arr = array();
            // Loop through each record
            foreach ($records as $record) {
                $temp = ++$start;
                //Faq status
                $checked = ($record->status == "1") ? "checked" : "";
                $faq_status = ($record->status == "1") ? "Active" : "InActive";
                $status = '<span class="toggle">
                        <label>
                            <input name="status"
                                   class="form-control faq_status"
                                   id="faqid_' . $record->id . '"
                                   faq_id="' . $record->id . '"
                                   faq_status="' . $record->status . '"
                                   type="checkbox"   ' . $checked . ' >
                            <span class="button-indecator" data-toggle="tooltip"
                                  data-placement="top"
                                  id="title_status_' . $record->id . '"
                                  title="' . $faq_status . '"></span>
                        </label>
                    </span>';
                $action = '<a href="' . route('get:admin:edit_faq', $record->id) . '"><img src="' . asset('/assets/images/template-images/writing-1.png') . '" style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Edit"></a>
                   <a class="delete" faqid="' . $record->id . '"><img src="' . asset('/assets/images/template-images/remove-1.png') . '" style="width:20px; height: 20px;" data-toggle="tooltip" data-placement="top" title="Delete"></a>';

                // Push to data array
                $data_arr[] = array(
                    "id" => $temp,
                    "name" => $record->name,
                    "status" => $status,
                    "action" => $action,
                );
            }

            // Response array
            $response = array(
                "draw" => intval($draw),
                "iTotalRecords" => $totalRecords,
                "iTotalDisplayRecords" => $iTotalDisplayRecords,
                "aaData" => $data_arr
            );

            // Return response
            return json_encode($response);
        } catch (Exception $exception) { // Catch exception
            Session::flash('alert-info', 'FAQ records not found.'); // Display error message
            return redirect()->back(); // Redirect back to previous page
        }
    }

    //Add new Faqs
    public function addFaq()
    {
        $language_lists = LanguageLists::query()->select('language_name', 'language_code')->where('status', '=', '1')->get();
        // return view for add Faqs
        return view("admin.pages.super_admin.faqs.form", compact('language_lists'));
    }

    //Edit Faq Form
    public function editFaq($id)
    {
        // Fetch the FAQ that needs to be updated
        $faq_details = Faqs::query()->where('id', $id)->first();

        // Fetch active language lists
        $language_lists = LanguageLists::query()
            ->select('language_name', 'language_code')
            ->where('status', '=', '1')
            ->get();

        // Check if the FAQ was found
        if ($faq_details == null) {
            Session::flash('error', 'Sorry, Faq Details Not Found!');
            return redirect()->back();
        }

        // Return the edit form view with the fetched FAQ and language list data
        return view('admin.pages.super_admin.faqs.form', compact('faq_details', 'language_lists'));
    }

    //Save Faqs
    public function saveUpdateFaq(FaqsRequest $request)
    {
        // Validation will be handled automatically before reaching here
        $request->validated();
        try {
            $msg = 'Faq has been Updated successfully!'; // Default message
            // fetching Faq
            $faq = Faqs::query()
                ->where('id', '=', $request->get("id") - 0) // filter by id
                ->first(); // Get first record

            // checking if Faq exists
            if ($faq == null) {
                $faq = new Faqs(); // Create new Faq
                $msg = 'Faq has been Added successfully!'; // Set message

            }
            //updating Faq
            $faq->name = $request->get("name"); // Set name
            $faq->description = $request->get("description"); // Set description

            try {
                $language_list = LanguageLists::query()->select(
                    DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_name') ELSE 'name' END) as page_name"),
                    DB::raw("(CASE WHEN language_code != 'en' THEN  concat(language_code,'_description') ELSE 'description' END) as page_desc"),
                )->where('status', 1)->get();
                foreach ($language_list as $language) {
                    if (Schema::hasColumn('faqs', $language->page_name) && Schema::hasColumn('faqs', $language->page_desc)) {
                        $faq->{$language->page_name} = $request->get($language->page_name);
                        $faq->{$language->page_desc} = $request->get($language->page_desc);
                    }
                }
            } catch (\Exception $e) {
            }

            $faq->status = $request->get("status"); // Set status
            $faq->save(); // Save Faq

            Session::flash('success', $msg); // Display success message
            return redirect(route("get:admin:faqs")); // Redirect to Faqs page
        } catch (Exception $exception) { // Catch exception
            Session::flash('danger', 'Something went wrong while creating or updating Faq!'); // Display error message
            return redirect(route("get:admin:faqs")); // Redirect back to previous page
        }
    }

    //Update Faq status through ajax
    public function updateFaqStatus(Request $request)
    {
        // Checking if id is null
        if ($request->get('id') == Null) {
            // returning response
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        // fetching Faq
        $faq = Faqs::find($request->id);
        // Checking if Faq is not exist in db
        if (!$faq) {
            // returning response
            return response()->json(['success' => false, 'message' => 'FAQ not found']);
        }

        // updating Faq
        $faq->status = !$faq->status;
        $faq->save(); // Save Faq

        // returning response
        return response()->json(['success' => true, 'status' => $faq->status]);
    }

    //Delete Faq through ajax
    public function deleteFaq(Request $request)
    {
        // checking restriction
        // if ($this->is_restricted == 1) {
        //     // returning response
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
        //     ]);
        // }
        // Checking if id is null
        if ($request->get('id') == Null) {
            Session::flash('error', 'Area Not Found!'); // Display error message
            // returning response
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        //fetching faq
        $faq = Faqs::where('id', $request->get('id'))->first();
        // Checking if Faq is not exist in db
        if ($faq == Null) {
            Session::flash('error', 'Faq Plan Not Found!'); // Display error message
            // returning response
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }

        $faq->delete(); // Delete Faq
        Session::flash('success', 'Faq remove successfully!'); // Display success message
        // returning response
        return response()->json([
            'success' => true
        ]);
    }

    //Fetch report issue user chat history from firebase
    public function showReportIssueChat($id)
    {

//        \Log::info("showReportIssueChat => ");
//        \Log::info($id);

        $sender_id = "a_1";//report id
        $user_id = "u_0"; //user id
        $report_name = "";
        //fetching user id from the report issue
        $report_issue = ReportIssues::query()->where('id', '=', $id)->first();

        if ($report_issue == null) {
            Session::flash('danger', 'report issue not found');
            return redirect()->back();
        }

        if (isset($report_issue->provider_type) && $report_issue->provider_type == 0) {
            $user_details = User::query()
                ->select('first_name', 'last_name', 'avatar')
                ->where('id', '=', $report_issue->provider_id - 0)
                ->first();
        } else {
            $user_details = Provider::query()
                ->select('first_name', 'avatar')
                ->where('id', '=', $report_issue->provider_id - 0)
                ->first();
        }

        if ($report_issue != null) {
            if (isset($report_issue->provider_type) && $report_issue->provider_type == 0) {
                $user_id = "u_" . $report_issue->provider_id;
                $user_image = ($user_details != null && $user_details->avatar != null) ? url('/assets/images/profile-images/customer/' . $user_details->avatar) : url('/assets/front/img/clients/default.png');
            } else {
                $user_id = "p_" . $report_issue->provider_id;
                $user_image = ($user_details != null && $user_details->avatar != null) ? url('/assets/images/profile-images/provider/' . $user_details->avatar) : url('/assets/front/img/clients/default.png');
            }
            $report_name = $report_issue->title;
        }

        //fetching user name
        $user_name = $user_details != null ? $user_details->first_name . ' ' . $user_details->last_name : '';
        //fetching user image

//        $default_profile_photo = $this->userPhotoResponse($report_issue->user_id,1,2);
//        $default_img = !Empty($default_profile_photo[0]) ? $default_profile_photo[0]['image'] : '';

        $default_img = "";

        //getting domain name
        $get_host = request()->getHost();

//        \Log::info("get_host => ");
//        \Log::info($get_host);

        $chat_replace_domain = preg_replace("/[\s_\-\.]/", "-", $get_host);

//        \Log::info("chat_replace_domain => ");
//        \Log::info($chat_replace_domain);

        $report_chat_number = $report_issue->reference_no . '-' . $report_issue->id;

        return view('admin.pages.report_issue.chat', compact('id', 'sender_id', 'user_id', 'chat_replace_domain', 'user_name', 'default_img', 'report_name', 'report_chat_number', 'user_image'));
    }

    //Send message Notification to user to firebase live database
    public function sendMessageNotification(Request $request)
    {

        if ($request->get('user_fcm') == Null || $request->get('user_message') == Null) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong!"
            ]);
        }
        //fetching the issue title
        $issue_report = ReportIssues::query()->where('id', '=', $request->get('report_id') - 0)->first();

        if (isset($issue_report->provider_type) && $issue_report->provider_type == 0) {
            $devaicToken = User::query()
                ->select('device_token')
                ->where('id', '=', $issue_report->provider_id - 0)
                ->first();
        } else {
            $devaicToken = Provider::query()
                ->select('device_token')
                ->where('id', '=', $issue_report->provider_id - 0)
                ->first();
        }

        $issue_title = 'Admin';
        //preparing array for sending notification
        $notification_data_array = [
            "sound" => "true",
            "is_report_chat" => "1",
            "user_id" => "a_1",
            "issue_id" => $request->get('report_id') . '',
//            "user_role" => "0",
//            "user_name" => $issue_title."",
            "order_chat_number" => $request->order_chat_number . "",
            "title" => $issue_title . "",
            "body" => ($request->get('is_image') == 0) ? $request->get('user_message') . "" : "Image",
            "message" => ($request->get('is_image') == 0) ? $request->get('user_message') . "" : "Image",
            "desc" => ($request->get('is_image') == 0) ? $request->get('user_message') . "" : "Image",
        ];

        $devaic_token = $devaicToken != null ? $devaicToken->device_token : '';

        //sending notification
        (new AuthAlertClass())->sendFlowNotification($devaic_token, $notification_data_array, 0, null);
        return response()->json([
            "success" => true,
            "message" => "success!",
        ]);
    }

    //set web token of admin
    public function updateWebToken(Request $request)
    {
        $device_token = $request->get('web_token');
        if ($device_token == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $admin = Admin::query()->where('id', Auth::guard('admin')->user()->id)->first();
        if ($admin == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        //updating device token
        $admin->device_token = $device_token;
        $admin->save();
        return response()->json([
            'success' => true,
            'message' => 'success'
        ]);
    }

    //Upload image as a message in firebase
    public function uploadChatImage(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            "id" => "required",
            "chat_image" => "required|file|mimes:jpeg,png,jpg,webp",
        ]);

        // If validation fails, return the first error message
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->first()
            ]);
        }

        // Get the chat number for organizing images in separate folders
        $report_chat_number = $request->report_chat_number;

        try {
            // Check if the request contains an uploaded file
            if ($request->file('chat_image') != null) {
                // Define the path to store the image
                $path = public_path('/assets/images/report-issue-images/' . $report_chat_number . '/');

                // If the directory doesn’t exist, create it
                if (!File::isDirectory($path)) {
                    File::makeDirectory($path, 755, true, true);
                }

                // Get the uploaded file
                $file = $request->file('chat_image');

                // Use the Intervention Image library to read the file
                $img = Image::read($file->getRealPath());
                $img->orient(); // Correct orientation if needed

                // Generate a unique file name
                $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();

                // Resize the image to 300x300 while maintaining aspect ratio
                $img->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($path . $file_new); // Save the resized image to the path

                // Generate the full image URL
                $chat_image_path = url('/assets/images/report-issue-images/' . $report_chat_number . '/' . $file_new);
            }

            // Return a JSON response with success status and image URL
            return response()->json([
                "success" => true,
                "message" => "success!",
                "image_url" => $chat_image_path,
            ]);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json([
                "success" => false,
                "message" => __('user_messages.9'),
            ]);
        }
    }

}
