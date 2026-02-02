<?php

namespace App\Http\Controllers;

use App\Classes\AdminClass;
use App\Classes\NotificationClass;
use App\Classes\TokenClassApi;
use App\Http\Requests\AddCardRequest;
use App\Http\Requests\OtherServicePackagesRequest;
use App\Http\Requests\OtherServiceProviderStoreRequest;
use App\Http\Requests\ProviderServiceRequest;
use App\Models\AdminAreaList;
use App\Models\OtherServiceCategory;
use App\Models\OtherServiceProviderDetails;
use App\Models\OtherServiceProviderPackages;
use App\Models\OtherServiceProviderTimings;
use App\Models\PromocodeDetails;
use App\Models\Provider;
use App\Models\ProviderAcceptedPackageTime;
use App\Models\ProviderBankDetails;
use App\Models\ProviderDocuments;
use App\Models\ProviderPortfolioImage;
use App\Models\ProviderServices;
use App\Models\ProviderVerification;
use App\Models\RequiredDocuments;
use App\Models\ServiceCategory;
use app\Models\ServiceSettings;
use App\Models\UsedPromocodeDetails;
use App\Models\User;
use App\Models\UserCardDetails;
use App\Models\UserPackageBooking;
use App\Models\UserPackageBookingQuantity;
use App\Models\UserReferHistory;
use App\Models\UserWalletTransaction;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;

class OtherServiceAdminController extends Controller
{
    private $adminClass;

    private $package_status;
    private $order_status;
    private $on_demand_service_id_array;
    private $is_restricted = 0;

    public function __construct(AdminClass $adminClass)
    {
        $this->middleware('auth');
        $this->adminClass = $adminClass;
        $this->package_status = ['', 'pending', 'approved', 'approved', 'rejected', 'cancelled', 'ongoing', 'arrived', 'processing', 'completed', 'failed'];
//        $this->order_status = [2, 3, 6, 7, 8];
        $this->order_status = [8];
        $service_cat = ServiceCategory::query()->whereIN('category_type', [3, 4])->where('status', 1)->get()->pluck('id')->toArray();
        $this->on_demand_service_id_array = $service_cat;
        $this->middleware( function ($request, $next) {
            $is_restrict_admin = $request->get('is_restrict_admin');
            $this->is_restricted = $is_restrict_admin;
            return $next($request);
        });
    }

    public function getDashboard(Request $request)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $slug="";
        $provider_id = Auth::guard('on_demand')->user()->id;
        $date = date('Y-m-d');
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay   = Carbon::parse($date)->endOfDay();
        /*$provider_service = ProviderServices::query()->where('provider_id', Auth::guard('on_demand')->user()->id)->first();

        if ($provider_service == Null) {
            return redirect()->back();
        }*/
        $total_sales = UserPackageBooking::query()->where('provider_id','=',$provider_id)->where('status', 9)->whereBetween('created_at', [$startOfDay, $endOfDay])->count();
        $total_revenue = round(UserPackageBooking::query()->where('provider_id','=',$provider_id)->where('status', 9)->whereBetween('created_at', [$startOfDay, $endOfDay])->sum('provider_amount'), 2);
        $total_orders = UserPackageBooking::query()->where('provider_id','=',$provider_id)->whereBetween('created_at', [$startOfDay, $endOfDay])->count();
        $total_cancel_orders = UserPackageBooking::query()->where('provider_id','=',$provider_id)->whereIn('status', [5,4])->whereBetween('created_at', [$startOfDay, $endOfDay])->count();
        $service_category = ProviderServices::query()
            ->select('service_category.name as service_name',
                'service_category.category_type',
                'service_category.slug',
                'service_category.icon_name',
                'service_category.id',
                'provider_services.service_cat_id',
                'provider_services.current_status',
                'provider_services.status',
                'provider_services.rejected_reason')
            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            ->where('service_category.status', '=', 1)
            ->where('provider_services.provider_id', '=', $provider_id)
            ->get();
        $order_count[] = 0;
        foreach ($service_category as $key => $category) {
            $order_count[$category->id] = UserPackageBooking::query()->where('service_cat_id', $category->id)->where('provider_id',$provider_id)->where('service_date_time', '>=', $date)->count();
        }
        $view = view('admin.pages.other_services.other_service_dashboard', compact('total_cancel_orders','order_count','service_category','total_sales', 'total_revenue', 'total_orders'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
//        $slug="";
//        $provider_status = $this->adminClass->checkProviderStatus();
//
//        if ($provider_status != Null) {
//            return $provider_status;
//        }
//        $provider_id = Auth::guard('on_demand')->user()->id;
//
//        /*$provider_service = ProviderServices::query()->where('provider_id', Auth::guard('on_demand')->user()->id)->first();
//
//        if ($provider_service == Null) {
//            return redirect()->back();
//        }*/
//        $total_sales = UserPackageBooking::query()->where('provider_id','=',$provider_id)->where('status', 9)->count();
//        $total_revenue = UserPackageBooking::query()->where('provider_id','=',$provider_id)->where('status', 9)->sum('provider_amount');
//        $total_orders = UserPackageBooking::query()->where('provider_id','=',$provider_id)->count();
//
//        $view = view('admin.pages.other_services.provider.service_dashboard', compact('total_sales', 'total_revenue', 'total_orders', 'slug'));
//        if ($request->ajax()) {
//            $view = $view->renderSections();
//            return $this->adminClass->renderingResponce($view);
//        }
//        return $view;
    }

    public function getProviderServiceRegister(Request $request)
    {
//        dd("hello");
        $service_category = ServiceCategory::query()->whereIn('category_type', [3, 4])->where('status', 1)->get();

        if ($service_category != Null) {
            $required_document = [];
            foreach ($service_category as $category) {
                $required_document[$category->id] = RequiredDocuments::query()->where('service_cat_id', '=', $category->id)->where('status', '=', 1)->get();
            }
            if ($request->ajax()) {
                $view = view('admin.pages.other_services.other_service_register', compact('service_category','required_document'))->renderSections();
                return $this->adminClass->renderingResponce($view);
            }
            return view('admin.pages.other_services.other_service_register', compact('service_category','required_document'));
        } else {
            Session::flash('error', 'Service Category Not Found!');
            return redirect()->back();
        }
    }

    public function postProviderServiceRegister(ProviderServiceRequest $request)
    {
        $provider = Provider::query()->where('id', Auth::guard('on_demand')->user()->id)->first();
        if ($provider != Null) {
            $landmark = $request->get('landmark');
            $address = $request->get('address');
            $lat = $request->get('lat');
            $long = $request->get('long');
            $user_name = $request->get('user_name');
            $email = $request->get('email');
            $service_radius = $request->get('service_radius');
            $country_code = $request->get('country_code');
            $contact_number = $request->get('contact_number');
            $service_category_id = $request->get('service_category');
            $service_sub_category_id = $request->get('service_sub_category');
            $package_name = $request->get('package_name');
            $package_price = $request->get('package_price');
            $max_book_quantity = $request->get('max_book_quantity');
            $package_description = $request->get('description');
            $provider_id = $request->get('provider_id');
            $gender = $request->get('gender');

            $provider->gender=$gender;
            $provider->save();


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

            if($provider->login_type != "email"){
                $provider_data = Provider::query()->where('id',$provider_id)->first();
                if($provider_data != NUll){
                    $provider_data->contact_number = ( $contact_number != NUll)? $contact_number:$provider_data->contact_number;
                    $provider_data->country_code = ( $country_code != NUll)? $country_code:$provider_data->country_code;
                    $provider_data->email = ( $email != NUll)? $email:$provider_data->email;
                    $provider_data->gender=$gender;
                    $provider_data->save();
                }
            }
            $current_lat = $get_other_service_provider->lat;
            $current_long = $get_other_service_provider->long;
            $area_id = 0;
//            if ($current_lat != Null && $current_long != Null) {
//                $get_admin_area_list = AdminAreaList::query()->where('status', 1)->get();
//                if ($get_admin_area_list->isNotEmpty()) {
//                    foreach ($get_admin_area_list as $get_area) {
//                        $vertices_x = explode(",", $get_area->latitude);
//                        $vertices_y = explode(",", $get_area->longitude);
//
//                        $points_polygon = count($vertices_x) - 1;
//                        $longitude_x = $current_lat;
//                        $latitude_y = $current_long;
//
//                        if ($this->adminClass->is_in_restricted_area($points_polygon, $vertices_x, $vertices_y, $longitude_x, $latitude_y)) {
//                            $area_id = $get_area->id;
//                            break;
//                        }
//                    }
//                    Provider::query()->where('id', $provider_id)->update(array('area_id' => $area_id));
//                }
//            }
            Provider::query()->where('id', $provider_id)->update(array('service_radius' => $service_radius,'status'=>0));


            //code for add service time
            $general_settings = request()->get("general_settings");
            $default_start_time="";
            $default_end_time= "";
            $notificationClass = New NotificationClass();
            $default_provider_open_close_time =  $notificationClass->defaultProviderOpenCloseTime($default_start_time,$default_end_time);

            $all_day_open_time =isset($default_provider_open_close_time['default_provider_start_time'])?$default_provider_open_close_time['default_provider_start_time']:"";
            $all_day_close_time = isset($default_provider_open_close_time['default_provider_end_time'])?$default_provider_open_close_time['default_provider_end_time']:"";
            $new_time_array = isset($default_provider_open_close_time['default_provider_slot'])?$default_provider_open_close_time['default_provider_slot']:[];

            $get_other_service_provider->start_time = $all_day_open_time;
            $get_other_service_provider->end_time = $all_day_close_time;
            $get_other_service_provider->time_list = "";
            $get_other_service_provider->save();
            $days = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];
            foreach ($days as $day) {

                if( count($new_time_array) > 0){
                    foreach($new_time_array as $single_time_arr)
                    {
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

            //step 2 add service cateogry
            $get_provider_service = ProviderServices::query()->where('provider_id', '=', $provider_id)->first();
            if ($get_provider_service == Null) {
                $get_provider_service = new ProviderServices();
            }

            $get_provider_service->provider_id = $provider_id;
            $get_provider_service->service_cat_id = $service_category_id;
            $get_provider_service->status = 0;
            $get_provider_service->current_status = 1;
            $get_provider_service->save();

            // add package for service
            $provider_service_id = $get_provider_service->id;
            $add_provider_package = OtherServiceProviderPackages::query()->where('provider_service_id', $provider_service_id)->first();
            if ($add_provider_package == Null) {
                $add_provider_package = new OtherServiceProviderPackages();
            }


            $add_provider_package->provider_service_id = $provider_service_id;
            $add_provider_package->sub_cat_id = $service_sub_category_id;
            $add_provider_package->service_cat_id = $service_category_id;
            $add_provider_package->name = $package_name == NULL ? "" : $package_name;
            $add_provider_package->description = $package_description;
            $add_provider_package->price = $package_price;
            $add_provider_package->max_book_quantity = $max_book_quantity;
            $add_provider_package->status = 1;
            $add_provider_package->save();


            //upload document
            $documents_list = $request->file('documents');
            if(isset($documents_list) &&  count($documents_list) > 0){
                //code for document upload
                foreach($documents_list as $key=>$single_document){
                    $require_document = RequiredDocuments::query()->where('id', $key)->first();
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
                        if ($request->file('documents')[$key] != Null) {
                            if ($upload_document != Null) {
                                if (\File::exists(public_path('/assets/images/provider-documents/' . $upload_document->document_file))) {
                                    \File::delete(public_path('/assets/images/provider-documents/' . $upload_document->document_file));
                                }
                            }
                            $file = $request->file('documents')[$key];
                            $file_new = rand(1, 9) . date('sihYdm') . rand(1, 9) . '.' . $file->getClientOriginalExtension();
                            $file->move(public_path() . '/assets/images/provider-documents/', $file_new);
                        }
                        if ($upload_document == Null) {
                            $upload_document = new ProviderDocuments();
                        }
                        $upload_document->provider_service_id = $get_provider_service->id;
                        $upload_document->req_document_id = $require_document->id;
                        $upload_document->document_file = $file_new;
                        $upload_document->status = 0;
                        $upload_document->save();
                    }
                }
            }
            if ($general_settings != Null) {
                if ($general_settings->send_mail == 1) {
                    // admin mail
                    try {
                        if ($general_settings != Null && $general_settings->send_receive_email != Null) {
                            $get_provider_service_list = ProviderServices::query()->select('service_category.name')
                                ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
                                ->where('provider_services.provider_id', $provider_id)
                                ->whereIN('provider_services.service_cat_id', $this->on_demand_service_id_array)->get()
                                ->pluck('name')->toArray();
                            $get_provider_service_count = count($get_provider_service_list);

                            if ($get_provider_service_count > 0) {

                                $mail_type = "admin_new_provider_signup";
                                $to_mail = $general_settings->send_receive_email;
                                $provider_service_list = implode(" , ", $get_provider_service_list);
                                $provider_email = $provider->email;
                                $provider_contact_number = $provider->country_code.$provider->contact_number;
                                $provider_name = ucwords($provider->first_name);
                                $subject = "New Provider Registered";
                                $disp_data = array("##provider_name##" => $provider_name, "##services_name##" => $provider_service_list, "##email##" => $provider_email, "##contact_no##" => $provider_contact_number);
                                $notificationClass = New NotificationClass();
                                $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                            }
                        }
                    } catch (\Exception $e) {
                    }

                    // provider mail
                    if($provider->email != null){
                        $notificationClass = new NotificationClass();
                        try {
                            $mail_type = "provider_signup";
                            $to_mail = $provider->email;
                            $subject = "Welcome to " . $general_settings->mail_site_name;
                            $disp_data = array("##provider_name##" => $provider->first_name);
                            $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                        } catch (\Exception $e) {}
                    }
                }
            }
            Provider::query()->where('id', $provider_id)->update(array('completed_step' => 5,'status'=>1));
            return redirect()->route('get:provider-admin:dashboard')->with("success", "Your service registered successfully!.");
        } else {
            Auth::logout();
            return redirect()->route('post:provider-admin:login')->with("error", "You are not register as on-demand provider.");
        }
    }

    public function getServiceSubCategoryDocument(Request $request)
    {
        $service_category = $request->get('service_category');

        $option_data = "<option disabled selected>Select Service Category</option>";
        $document_data = "";
        if ($service_category > 0) {
            $other_service_sub_category = OtherServiceCategory::query()->where('service_cat_id','=',$service_category)->where('status','=',1)->get();

            foreach ($other_service_sub_category as $single_sub_category){
                $option_data.="<option value=".$single_sub_category->id." >".$single_sub_category->name."</option>";
            }

            $required_document = RequiredDocuments::query()->where('service_cat_id', '=', $service_category)->where('status', '=', 1)->get();
            $document_count = count($required_document);
            foreach ($required_document as $single_required_document){
                $document_data .= '<div class="col-xl-3 col-md-6">
                                            <div class="card comp-card">
                                                <div class="card-body text-center">
                                                    <h6 class="m-b-20">'. ucfirst($single_required_document->name).'</h6>
                                                    <div id="document-preview-'.$single_required_document->id.'">
                                                        <label for="document-upload-'.$single_required_document->id.'" id="document-label-'.$single_required_document->id.'">Upload
                                                            Document</label>
                                                        <input type="file" id="document-upload-'.$single_required_document->id.'"  data-id="'.$single_required_document->id.'" name="documents['.$single_required_document->id.']" class="form-control documents_'.$service_category.' unless_doc" required="">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>';
            }

            if($document_data == ""){
                $document_data .= '<div class="col-12">
                                            <div class="card comp-card">
                                                <div class="card-body text-center">
                                                    <h6 class="m-b-20">No Document Required</h6>
                                                </div>
                                            </div>
                                        </div>';
            }
        }


        return response()->json([
            'success' => true,
            'option_data' => $option_data,
            'document_data' => $document_data,
            'document_count' => $document_count
        ]);
    }

    public function getProviderServices(Request $request)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $services = ProviderServices::query()
            ->select('service_category.name as service_name',
                'service_category.category_type',
                'service_category.slug',
                'service_category.icon_name',
                'provider_services.id',
                'provider_services.service_cat_id',
                'provider_services.current_status',
                'provider_services.status',
                'provider_services.rejected_reason',
                'provider_services.provider_id')
            ->join('service_category', 'service_category.id', '=', 'provider_services.service_cat_id')
            ->where('service_category.status', '=', 1)
            ->where('provider_services.provider_id', '=', Auth::guard('on_demand')->user()->id)->get();
        $total_service_category = OtherServiceCategory::query()
            ->where('other_service_sub_category.status','=',1)
            ->where('service_category.status','=',1)
            ->join('service_category', 'service_category.id', '=', 'other_service_sub_category.service_cat_id')
            ->groupBy('other_service_sub_category.service_cat_id')->get()->count();

        if ($request->ajax()) {
            $view = view('admin.pages.other_services.provider.service_list', compact('services'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.provider.service_list', compact('services','total_service_category'));
    }

    public function getProviderAddServices(Request $request)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $provider_services = ProviderServices::query()->where('provider_id', Auth::guard('on_demand')->user()->id)->get()->pluck('service_cat_id')->toArray();
        $service_category_multiple = OtherServiceCategory::query()
            ->select('service_category.*')
            ->join('service_category', 'service_category.id', '=', 'other_service_sub_category.service_cat_id')
            ->whereNotIn('service_category.id', $provider_services)
            ->whereIn('service_category.category_type', [3, 4])
            ->where('service_category.status',1)->groupBy('other_service_sub_category.service_cat_id')->get();

        if ($request->ajax()) {
            $view = view('admin.pages.super_admin.provider.form_provider_service', compact('service_category_multiple'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.super_admin.provider.form_provider_service', compact('service_category_multiple'));
    }

    public function postProviderAddServices(Request $request)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $services = $request->get('provider_services');
        $provider_id = Auth::guard('on_demand')->user()->id;

        //update provider details complete status
        $provider = Provider::query()->where('id', $provider_id)->first();
        if ($provider != null && $provider->completed_step != 5) {
            $provider->completed_step = 5;
            $provider->save();
        }

        foreach ($services as $service) {
            $check_duplicate = ProviderServices::query()->where('provider_id', $provider_id)->where('service_cat_id', $service)->first();
            $service_category = ServiceCategory::query()->where('id', $service)->first();
            if ($check_duplicate == Null && $service_category != Null) {
                $add_service = new ProviderServices();
                $add_service->provider_id = $provider_id;
                $add_service->service_cat_id = $service_category->id;
                $add_service->current_status = 1;
                $add_service->status = 0;
                $add_service->save();
                Session::flash('success', 'Service Added Successfully!');
            }
        }
        return redirect()->route('get:provider-admin:services');
    }

    public function postProviderDeleteService(Request $request)
    {
        $id = $request->get('id');
        if ($id == Null) {
//            Session::flash('error', 'Other Service Package Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Something Went to Wrong!'
            ]);
        }
        $service = ProviderServices::query()->where('id', $id)->first();
        if ($service == Null) {
//            Session::flash('error', 'Other Service Package Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Service not found'
            ]);
        }
        if ($service != Null) {
            $running_service = UserPackageBooking::query()
                ->where('provider_id',$service->provider_id)
                ->where('service_cat_id',$service->service_cat_id)
                ->whereIn('status',[2,3,6,7,8])
                ->get();
            if(!$running_service->isEmpty()){
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, Currently You have Running a Order Service'
                ]);
            }
            ProviderServices::query()->where('id', $id)->delete();
        }
//        Session::flash('error', 'Other Service Package remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }

    public function getProviderUpdateServiceCurrentStatus(Request $request)
    {
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'something went to wrong!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $provider_service = ProviderServices::where('id', $id)->first();
        if ($provider_service == Null) {
            Session::flash('error', 'provider Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($provider_service->current_status == 1) {
            $provider_service->current_status = 0;
            $provider_service->save();
//            Session::flash('success', $service_sub_category->name . ' category disabled successfully!');
        } else {
            $provider_service->current_status = 1;
            $provider_service->save();
//            Session::flash('success', $service_sub_category->name . ' category enable successfully!');
        }
        return response()->json([
            'success' => true,
            'status' => $provider_service->current_status
        ]);
    }

    public function getProviderServiceDashboard(Request $request, $slug)
    {

        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();
        if ($service_category == Null) {
            return redirect()->back();
        }
        $provider_id = Auth::guard('on_demand')->user()->id;
        $provider_service = ProviderServices::query()->where('provider_id', Auth::guard('on_demand')->user()->id)->where('service_cat_id', $service_category->id)->first();
        if ($provider_service == Null) {
            return redirect()->back();
        }
        $date = date('Y-m-d');

        $total_sales = UserPackageBooking::query()->where('provider_id','=',$provider_id)->where('service_cat_id', $service_category->id)->where('status', 9)->whereDate('created_at', '>=', $date)->count();
        $total_revenue = UserPackageBooking::query()->where('provider_id','=',$provider_id)->where('service_cat_id', $service_category->id)->where('status', 9)->whereDate('created_at', '>=', $date)->sum('provider_amount');
        $total_orders = UserPackageBooking::query()->where('provider_id','=',$provider_id)->where('service_cat_id', $service_category->id)->whereDate('created_at', '>=', $date)->count();

        $view = view('admin.pages.other_services.provider.service_dashboard', compact('total_sales', 'total_revenue', 'total_orders', 'slug'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getProviderServicePackageList(Request $request, $slug)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $service_category = ServiceCategory::query()->select('id','name','category_type')->where('slug', $slug)->first();
        if ($service_category == Null) {
            return redirect()->back();
        }
        $package_list = OtherServiceProviderPackages::query()->select('other_service_provider_packages.id',
            'other_service_sub_category.name as sub_cat_name',
            'other_service_provider_packages.name as package_name',
            'other_service_provider_packages.max_book_quantity',
            'other_service_provider_packages.price',
            'other_service_provider_packages.status',
            'other_service_provider_packages.provider_service_id')
            ->join('other_service_sub_category', 'other_service_sub_category.id', '=', 'other_service_provider_packages.sub_cat_id')
            ->join('provider_services', 'provider_services.id', '=', 'other_service_provider_packages.provider_service_id')
            ->join('service_category', 'service_category.id', '=', 'other_service_provider_packages.service_cat_id')
            ->where('provider_services.provider_id', Auth::guard('on_demand')->user()->id)
            ->where('service_category.slug', $slug)
            ->get();
        if (!$package_list->isEmpty()) {
            $view = view('admin.pages.other_services.packages.manage', compact('package_list', 'slug', 'service_category'));
        } else {
//            Session::flash('error', 'category not found!');
            $view = view('admin.pages.other_services.packages.manage', compact('slug', 'service_category'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getProviderServiceAddPackage(Request $request, $slug)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'category not found!');
            return redirect()->back();
        }
        $provider_id = Auth::guard('on_demand')->user()->id;
        $service_category_list = OtherServiceCategory::select('id', 'name', 'status')->where('service_cat_id', $service_category->id)->where('status','=',1)->get();
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.packages.form', compact('slug', 'service_category', 'service_category_list', 'provider_id'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.packages.form', compact('slug', 'service_category', 'service_category_list', 'provider_id'));
    }

    public function getProviderServiceEditPackage(Request $request, $slug, $id)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $package = OtherServiceProviderPackages::where('id', $id)->first();
        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if($package != Null)
        {
            $service_category_list = OtherServiceCategory::select('id', 'name', 'status')->where("service_cat_id",$package->service_cat_id)->get();
        }else{
            Session::flash('error', 'sub category not found!');
            return redirect()->back();
        }
        if ($package != Null) {
            $view = view('admin.pages.other_services.packages.form', compact('package', 'service_category', 'service_category_list', 'slug'));
        } else {
            Session::flash('error', 'category not found!');
            $view = view('admin.pages.other_services.packages.form', compact('slug', 'service_category', 'service_category_list'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getProviderServiceUpdatePackage(OtherServicePackagesRequest $request, $slug)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }

        //update provider details complete status
        $provider_data =  Provider::query()->where('id','=',$request->get('provider_id'))->whereNull('providers.deleted_at')->first();
        if($provider_data != Null){
            if($provider_data->completed_step == 2)
            {
                $provider_data->completed_step=3;
            }
            $provider_data->save();
        }

        if ($request->get('id') == Null) {
            if ($request->get('provider_id') != Null) {
                $service_category = ServiceCategory::where('slug', $slug)->first();
                if ($service_category != Null) {
                    $provider_service = ProviderServices::where('provider_id', $request->get('provider_id'))->where('service_cat_id', $service_category->id)->first();
                    if ($provider_service != Null) {
                        $add_package = new OtherServiceProviderPackages();
                        $add_package->provider_service_id = $provider_service->id;
                        $add_package->sub_cat_id = $request->get('category');
                        $add_package->service_cat_id = $service_category->id;
                        $add_package->name = $request->get('package_name');
                        $add_package->description = $request->get('description');
                        $add_package->price = $request->get('package_price');
                        $add_package->max_book_quantity = $request->get('max_book_quantity');
                        $add_package->status = $request->get('status');
                        $add_package->save();
                        Session::flash('success', 'package add successfully!');
                        return redirect()->route('get:provider-admin:service-package-list', [$service_category->slug]);
                    } else {
                        Session::flash('error', 'something went to wrong!');
                        return redirect()->back();
                    }
                } else {
                    Session::flash('error', 'something went to wrong!');
                    return redirect()->back();
                }
            } else {
                Session::flash('error', 'something went to wrong!');
                return redirect()->back();
            }
        } else {
            $edit_package = OtherServiceProviderPackages::where('id', $request->get('id'))->first();
            if ($edit_package == Null) {
                Session::flash('error', 'something went to wrong!');
                return redirect()->back();
            }
            $service_category = ServiceCategory::where('slug', $slug)->first();
            if ($service_category == Null) {
                Session::flash('error', 'something went to wrong!');
                return redirect()->back();
            }
            $edit_package->service_cat_id = $service_category->id;
            $edit_package->sub_cat_id = $request->get('category');
            $edit_package->name = $request->get('package_name');
            $edit_package->description = $request->get('description');
            $edit_package->price = $request->get('package_price');
            $edit_package->max_book_quantity = $request->get('max_book_quantity');
            $edit_package->status = $request->get('status');
            $edit_package->save();
            Session::flash('success', 'package updated successfully!');
            return redirect()->route('get:provider-admin:service-package-list', [$service_category->slug]);
        }
    }

    public function getProviderServiceUpdatePackageStatus(Request $request)
    {
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $service_package = OtherServiceProviderPackages::where('id', $id)->first();
        if ($service_package == Null) {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        if ($service_package->status == 1) {
            $service_package->status = 0;
            $service_package->save();
//            Session::flash('success', $service_sub_category->name . ' category disabled successfully!');
        } else {
            $service_package->status = 1;
            $service_package->save();
//            Session::flash('success', $service_sub_category->name . ' category enable successfully!');
        }
        return response()->json([
            'success' => true,
            'status' => $service_package->status
        ]);
    }

    public function getProviderServiceDeletePackage(Request $request)
    {
        $id = $request->get('id');
        if ($id == Null) {
            Session::flash('error', 'Other Service Package Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $package = OtherServiceProviderPackages::query()->where('id', $id)->first();
        if ($package == Null) {
            Session::flash('error', 'Other Service Package Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $running_service = UserPackageBooking::query()
            ->whereRaw("find_in_set($id,package_id)")
            ->whereIn('status',[2,3,6,7,8])
            ->count();

        if($running_service > 0){
            return response()->json([
                'success' => false,
                'message' => 'Try again later ! Currently this package is in use'
            ]);
        }
        OtherServiceProviderPackages::query()->where('id', $id)->delete();
//        Session::flash('error', 'Other Service Package remove successfully!');
        return response()->json([
            'success' => true
        ]);
    }

    public function getProviderOtherServiceOrderList(Request $request, $slug, $status)
    {

        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $service_category = ServiceCategory::query()->where('slug', $slug)->first();

        if ($service_category == Null) {
            Session::flash('error', 'Service category not found!');
            return redirect()->back();
        }
        if ($status == "all") {
            $newStatus = [];
        } elseif ($status == "pending") {
            $newStatus = [1];
        } elseif ($status == "approved") {
            $newStatus = [2, 3];
        } elseif ($status == "rejected") {
            $newStatus = [4];
        } elseif ($status == "ongoing") {
            $newStatus = [6, 7, 8];
        } elseif ($status == "completed") {
            $newStatus = [9];
        } elseif ($status == "cancelled") {
            $newStatus = [5, 10];
        } else {
            $newStatus = [];
        }
        $provider_details = Provider::select('id', 'first_name')->where('id', Auth::guard('on_demand')->user()->id)->whereNull('providers.deleted_at')->first();
        if ($provider_details == Null) {
            return redirect()->back();
        }
        if (count($newStatus) > 0) {
            $order_list = UserPackageBooking::query()->select('id', 'user_name', 'order_package_list', 'total_pay', 'status')->where('service_cat_id', $service_category->id)->where('provider_id', $provider_details->id)->whereIN('status', $newStatus)->orderBy('service_date_time','desc')->get();
        } else {
            $order_list = UserPackageBooking::query()->select('id', 'user_name', 'order_package_list', 'total_pay', 'status')->where('service_cat_id', $service_category->id)->where('provider_id', $provider_details->id)->orderBy('service_date_time','desc')->get();
        }
        $order_status = $this->package_status;
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.other_service_order_list', compact('slug', 'order_list', 'order_status', 'provider_details','service_category','status'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.other_service_order_list', compact('slug', 'order_list', 'order_status', 'provider_details','service_category','status'));
    }

    public function getProviderOtherServiceOrderDetails(Request $request, $slug, $order_id)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            return redirect()->back();
        }
        $orders_details = UserPackageBooking::query()->select(
            'user_service_package_booking.id',
            'user_service_package_booking.order_no',
            'user_service_package_booking.booking_time_zone',
            'user_service_package_booking.user_name', 'user_service_package_booking.provider_name',
            'user_service_package_booking.service_date_time',
            'user_service_package_booking.service_time',
            'user_service_package_booking.service_date',
            'user_service_package_booking.total_item_cost',
            'user_service_package_booking.delivery_address',
            'user_service_package_booking.extra_amount',
            'user_service_package_booking.tax',
            'user_service_package_booking.tip',
            'user_service_package_booking.created_at',
            'user_service_package_booking.order_type',
            'user_service_package_booking.service_date',
            'user_service_package_booking.service_time',
            'user_service_package_booking.book_start_time',
            'user_service_package_booking.book_end_time',
            'user_service_package_booking.remark','user_service_package_booking.refer_discount',
            'user_service_package_booking.total_pay', 'user_service_package_booking.status'
            , 'users.country_code'
            , 'users.contact_number',
            'used_promocode_details.discount_amount as promo_code_discount',
            'used_promocode_details.promocode_name as promo_code_name'
            , 'user_service_package_booking.cancel_by'
            , 'user_service_package_booking.cancel_reason'
            , 'user_service_package_booking.payment_type'
            , 'user_service_package_booking.payment_status'
            , 'user_service_package_booking.refund_amount', 'user_service_package_booking.cancel_charge', 'user_service_package_booking.user_refund_status'
        )
            ->join('users', 'users.id', '=', 'user_service_package_booking.user_id')
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', 'user_service_package_booking.promo_code')
            ->where('user_service_package_booking.id','=', $order_id)
            ->where('service_category.slug','=', $slug)
//            ->whereNull('users.deleted_at')
            ->first();
        $orders_status = "----";
        $orders_status_array = $this->order_status;
        if ($orders_details != Null) {
            $package_list = UserPackageBookingQuantity::select('num_of_items', 'package_name', 'sub_category_name', 'price_for_one')
                ->where('order_id', $orders_details->id)
                ->get();
            $total_package_cost = 0;
            foreach ($package_list as $key => $package) {
                $total_package_item_cost = round($package->price_for_one * $package->num_of_items, 2);
                $total_package_cost = $total_package_cost + $total_package_item_cost;
            }
            if ($orders_details->status == 1) {
                $orders_status = "Pending";
            } elseif ($orders_details->status == 2 || $orders_details->status == 3) {
                $orders_status = "Confirmed";
            } elseif ($orders_details->status == 4) {
                $orders_status = "Rejected";
            } elseif ($orders_details->status == 5) {
                $orders_status = "Cancelled";
            } elseif ($orders_details->status == 6) {
                $orders_status = "Ongoing";
            } elseif ($orders_details->status == 7) {
                $orders_status = "Arrived";
            } elseif ($orders_details->status == 8) {
                $orders_status = "Processing";
            } elseif ($orders_details->status == 9) {
                $orders_status = "Completed";
            } elseif ($orders_details->status == 10) {
                $orders_status = "Failed";
            }
            $view = view('admin.pages.other_services.other_service_order_details', compact('slug', 'orders_status', 'orders_status_array', 'orders_details', 'package_list'));
        } else {
            $view = view('admin.pages.other_services.other_service_order_details', compact('slug', 'orders_status', 'orders_status_array'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getProviderOtherServiceDocument(Request $request, $slug)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }

        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }

        $provider_service_details = ProviderServices::query()->where('provider_id', Auth::guard('on_demand')->user()->id)->where('service_cat_id', $service_category->id)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }

        $required_documents = RequiredDocuments::query()
            ->where('service_cat_id', $service_category->id)
            ->where('status',1)
            ->get();
        $provider_documents=[];

        foreach ($required_documents as $key => $required_document) {
            $document_details = ProviderDocuments::where('provider_service_id', '=', $provider_service_details->id)->where('req_document_id', $required_document->id)->first();
            if ($required_document->service_cat_id == $service_category->id) {
                $provider_documents[$key] = $document_details;
            } else {
                $provider_documents[$key] = Null;
            }
        }
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.document.form', compact('required_documents', 'provider_documents', 'service_category', 'slug'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.document.form', compact('required_documents', 'provider_documents', 'service_category', 'slug'));
    }

    public function postProviderOtherServiceDocument(Request $request, $slug)
    {
        //validations
        $validator = Validator::make($request->all(), [
            "document_file" => "mimes:jpeg,png,jpg,webp",
        ]);
        if ($validator->fails()) {
            Session::flash('error', "Please upload a file in JPEG, PNG, JPG, or WEBP format.");
            return redirect()->back();
        }

        $service_category = ServiceCategory::select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }
        $provider_service_details = ProviderServices::query()->where('provider_id', Auth::guard('on_demand')->user()->id)->where('service_cat_id', $service_category->id)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }
        $id = $request->get('id');
        $get_document = RequiredDocuments::where('id', $id)->where('service_cat_id', $service_category->id)->first();
        if($request->file('document_file') != Null){
            if ($get_document != Null) {
                $find_document = ProviderDocuments::where('provider_service_id', $provider_service_details->id)->where('req_document_id', $get_document->id)->first();
                if ($find_document != Null) {
                    if ($request->file('document_file')) {
                        if (\File::exists(public_path('/assets/images/provider-documents/' . $find_document->file_name))) {
                            \File::delete(public_path('/assets/images/provider-documents/' . $find_document->file_name));
                        }
                        $image = $request->file('document_file');
                        $destinationPath = public_path('/assets/images/provider-documents/');
                        $img = Image::read($image->getRealPath());
                        $img->orient();
                        $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $image->getClientOriginalExtension();
                        $img->resize(500, 500, function ($constraint) {
                            $constraint->aspectRatio();
                        })->save($destinationPath . $file_new);
                        $find_document->req_document_id = $get_document->id;
                        $find_document->document_file = $file_new;
                        $find_document->status = 0;
                        $find_document->save();
                    }
                    Session::flash('success', $get_document->name . ' Updated Successfully!');
                } else {
                    if ($request->file('document_file')) {
                        $image = $request->file('document_file');
                        $destinationPath = public_path('/assets/images/provider-documents/');
                        $img = Image::read($image->getRealPath());
                        $img->orient();
                        $file_new = rand(1, 9) . date('siHYdm') . rand(1, 9) . '.' . $image->getClientOriginalExtension();
                        $img->resize(500, 500, function ($constraint) {
                            $constraint->aspectRatio();
                        })->save($destinationPath . $file_new);
                        $documents = new ProviderDocuments();
                        $documents->provider_service_id = $provider_service_details->id;
                        $documents->req_document_id = $get_document->id;
                        $documents->document_file = $file_new;
                        $documents->status = 0;
                        $documents->save();
                        Session::flash('success', $get_document->name . ' Upload Successfully!');
                    }
                }
            } else {
                Session::flash('error', 'Document Name Not Found!');
            }
        } else {
            return redirect()->back()->with('error','Please Upload Document File!');
        }
        return redirect()->route('get:provider-admin:other_service_provider_document', $slug);
    }

    public function getProviderOtherServiceAllOrderList(Request $request, $status)
    {

        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        if ($status == "all") {
            $newStatus = [];
        } elseif ($status == "pending") {
            $newStatus = [1];
        } elseif ($status == "approved") {
            $newStatus = [2, 3];
        } elseif ($status == "rejected") {
            $newStatus = [4];
        } elseif ($status == "ongoing") {
            $newStatus = [6, 7, 8];
        } elseif ($status == "completed") {
            $newStatus = [9];
        } elseif ($status == "cancelled") {
            $newStatus = [5, 10];
        } else {
            $newStatus = [];
        }
        $provider_details = Provider::query()->select('id', 'first_name')->where('id', Auth::guard('on_demand')->user()->id)->whereNull('providers.deleted_at')->first();
        if ($provider_details == Null) {
            return redirect()->back();
        }
        if (count($newStatus) > 0) {
            $order_list = UserPackageBooking::query()->select('id', 'user_name', 'order_package_list', 'total_pay', 'status','service_date_time')->where('provider_id', $provider_details->id)->whereIN('status', $newStatus)->orderBy('service_date_time', 'desc')->get();
        } else {
            $order_list = UserPackageBooking::query()->select('id', 'user_name', 'order_package_list', 'total_pay', 'status','service_date_time')->where('provider_id', $provider_details->id)->orderBy('service_date_time', 'desc')->get();
        }
        $order_status = $this->package_status;
        if ($request->ajax()) {
            $view = view('admin.pages.other_services.other_service_order_list', compact('order_list', 'status','order_status', 'provider_details'))->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.pages.other_services.other_service_order_list', compact('order_list', 'status','order_status', 'provider_details'));
    }

    public function getProviderOtherServiceAllOrderDetails(Request $request, $order_id)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $orders_details = UserPackageBooking::select('user_service_package_booking.id', 'user_service_package_booking.order_no','user_service_package_booking.order_type',
            'user_service_package_booking.user_name', 'user_service_package_booking.provider_name','user_service_package_booking.payment_type',
            'user_service_package_booking.service_date_time',
            'user_service_package_booking.total_item_cost',
            'user_service_package_booking.delivery_address',
            'user_service_package_booking.service_date',
            'user_service_package_booking.payment_status',
            'user_service_package_booking.service_time',
            'user_service_package_booking.booking_time_zone',
            'user_service_package_booking.book_start_time',
            'user_service_package_booking.book_end_time',
            'user_service_package_booking.tax',
            'user_service_package_booking.refer_discount',
            'user_service_package_booking.extra_amount',
            'user_service_package_booking.tip',
            'user_service_package_booking.remark','user_service_package_booking.created_at',
            'user_service_package_booking.total_pay', 'user_service_package_booking.status',
            'used_promocode_details.discount_amount as promo_code_discount',
            'used_promocode_details.promocode_name as promo_code_name'
            , 'users.country_code'
            , 'users.contact_number'
            ,'user_service_package_booking.cancel_by'
            ,'user_service_package_booking.cancel_reason'
//            , 'providers.time_zone as booking_time_zone'
        )
            ->join('users', 'users.id', '=', 'user_service_package_booking.user_id')
            ->join('providers', 'providers.id', '=', 'user_service_package_booking.provider_id')
            ->join('service_category', 'service_category.id', '=', 'user_service_package_booking.service_cat_id')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', 'user_service_package_booking.promo_code')
            ->where('user_service_package_booking.id', $order_id)
//            ->whereNull('users.deleted_at')
            ->first();
        $orders_status = "----";
        $orders_status_array = $this->order_status;
        if ($orders_details != Null) {
            $package_list = UserPackageBookingQuantity::select('num_of_items', 'package_name', 'sub_category_name', 'price_for_one')
                ->where('order_id', $orders_details->id)
                ->get();
            $total_package_cost = 0;
            foreach ($package_list as $key => $package) {
                $total_package_item_cost = round($package->price_for_one * $package->num_of_items, 2);
                $total_package_cost = $total_package_cost + $total_package_item_cost;
            }
            if ($orders_details->status == 1) {
                $orders_status = "Pending";
            } elseif ($orders_details->status == 2 || $orders_details->status == 3) {
                $orders_status = "Confirmed";
            } elseif ($orders_details->status == 4) {
                $orders_status = "Rejected";
            } elseif ($orders_details->status == 5) {
                $orders_status = "Cancelled";
            } elseif ($orders_details->status == 6) {
                $orders_status = "Ongoing";
            } elseif ($orders_details->status == 7) {
                $orders_status = "Arrived";
            } elseif ($orders_details->status == 8) {
                $orders_status = "Processing";
            } elseif ($orders_details->status == 9) {
                $orders_status = "Completed";
            } elseif ($orders_details->status == 10) {
                $orders_status = "Failed";
            }
            $view = view('admin.pages.other_services.other_service_order_details', compact('orders_status', 'orders_status_array', 'orders_details', 'package_list'));
        } else {
            $view = view('admin.pages.other_services.other_service_order_details', compact('orders_status', 'orders_status_array'));
        }
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function getProviderServiceEditProfileBKP(Request $request)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $provider_id = Auth::guard('on_demand')->user()->id;
        $provider = Provider::query()->where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        if ($provider == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }
        $provider_other_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_id)->first();
        $bank_details = ProviderBankDetails::query()->where('provider_id', $provider_id)->first();
        $sun_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'SUN')->first();
        $mon_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'MON')->first();
        $tue_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'TUE')->first();
        $wed_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'WED')->first();
        $thu_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'THU')->first();
        $fri_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'FRI')->first();
        $sat_day_open_time = OtherServiceProviderTimings::query()->where('provider_id', '=', $provider->id)->where('day', '=', 'SAT')->first();
        $all_day_open_time = [];

        $count = 0;
        $check_get_data = 0;
        $time_status='';
        if($provider_other_details != NULL)
            $time_status = $provider_other_details->time_slot_status;
        if ($sun_day_open_time != Null) {
            $check_get_data = 1;
            $sun_day_open_time = explode(',', $sun_day_open_time->open_time_list);
        }
        else{
            $sun_day_open_time = [];
        }
        if ($mon_day_open_time != Null) {
            $check_get_data = 1;
            $mon_day_open_time = explode(',', $mon_day_open_time->open_time_list);
        }
        else{
            $mon_day_open_time = [];
        }
        if ($tue_day_open_time != Null) {
            $check_get_data = 1;
            $tue_day_open_time = explode(',', $tue_day_open_time->open_time_list);
        }
        else{
            $tue_day_open_time = [];
        }
        if ($wed_day_open_time != Null) {
            $check_get_data = 1;
            $wed_day_open_time = explode(',', $wed_day_open_time->open_time_list);
        }
        else{
            $wed_day_open_time = [];
        }
        if ($thu_day_open_time != Null) {
            $check_get_data = 1;
            $thu_day_open_time = explode(',', $thu_day_open_time->open_time_list);
        }
        else{
            $thu_day_open_time = [];
        }
        if ($fri_day_open_time != Null) {
            $check_get_data = 1;
            $fri_day_open_time = explode(',', $fri_day_open_time->open_time_list);
        }
        else{
            $fri_day_open_time = [];
        }
        if ($sat_day_open_time != Null) {
            $check_get_data = 1;
            $sat_day_open_time = explode(',', $sat_day_open_time->open_time_list);
        }
        else{
            $sat_day_open_time = [];
        }

        if ($sun_day_open_time == $mon_day_open_time) {
            $count = $count + 1;
        }
        if ($sun_day_open_time == $tue_day_open_time) {
            $count = $count + 1;
        }
        if ($sun_day_open_time == $wed_day_open_time) {
            $count = $count + 1;
        }
        if ($sun_day_open_time == $thu_day_open_time) {
            $count = $count + 1;
        }
        if ($sun_day_open_time == $fri_day_open_time) {
            $count = $count + 1;
        }
        if ($sun_day_open_time == $sat_day_open_time) {
            $count = $count + 1;
        }

        $all_day = 0;
        if ($count === 6) {
            $all_day = 1;
            $all_day_open_time = $sun_day_open_time;
            $sun_day_open_time = [];
            $mon_day_open_time = [];
            $tue_day_open_time = [];
            $wed_day_open_time = [];
            $thu_day_open_time = [];
            $fri_day_open_time = [];
            $sat_day_open_time = [];
        }
        if ($check_get_data == 0) {
            $all_day = 1;
        }
        $view = view('admin.pages.other_services.provider.form', compact('provider', 'provider_other_details', 'bank_details',
            'all_day', 'all_day_open_time', 'sun_day_open_time', 'mon_day_open_time', 'tue_day_open_time', 'wed_day_open_time', 'thu_day_open_time', 'fri_day_open_time', 'sat_day_open_time','time_status'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        } else {
            return $view;
        }
    }

    public function getProviderServiceEditProfile(Request $request)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $provider_id = Auth::guard('on_demand')->user()->id;
        $provider = Provider::query()->where('id', $provider_id)->whereNull('providers.deleted_at')->first();
        if ($provider == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }
        $provider_other_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_id)->first();
        $bank_details = ProviderBankDetails::query()->where('provider_id', $provider_id)->first();

        $time_status = isset($provider_other_details->time_slot_status)?$provider_other_details->time_slot_status:0;
        $all_day = isset($provider_other_details->all_day)?$provider_other_details->all_day:0;

        $general_settings = request()->get("general_settings");
        $default_start_time= ($general_settings->default_start_time != Null)?$general_settings->default_start_time:"00:00:00";
        $default_end_time= ($general_settings->default_end_time != Null)?$general_settings->default_end_time:"23:00:00";
        $notificationClass = New NotificationClass();
        $default_open_close_time =  $notificationClass->defaultProviderOpenCloseTime($default_start_time,$default_end_time);
        $default_slot= isset($default_open_close_time['default_provider_slot'])?$default_open_close_time['default_provider_slot']:[];

        $days_arr = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];
        $time_slot_list =[];
        foreach ($days_arr as $single_day) {
            foreach ($default_slot as $key => $single_default_slot) {
                $get_open_timings = OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtolower($single_day))->get();
                $selected = 0;
                foreach ($get_open_timings as $get_single_open_timing) {
                    if ($single_default_slot['start_time'] == $get_single_open_timing->provider_open_time && $single_default_slot['end_time'] == $get_single_open_timing->provider_close_time){
                        $selected = 1;
                    }
                }
                $time_slot_list[strtolower($single_day)][] = array(
                    'start_time' => $single_default_slot['start_time'],
                    'end_time' => $single_default_slot['end_time'],
                    'display_start_time' => date("h:i A", strtotime($single_default_slot['start_time'])),
                    'display_end_time' => date("h:i A", strtotime($single_default_slot['end_time'])),
                    'selected' => $selected,
                );
            }
        }

        $view = view('admin.pages.other_services.provider.form', compact('provider', 'provider_other_details', 'bank_details', 'time_slot_list','days_arr','time_status','all_day'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        } else {
            return $view;
        }
    }

    public function postProviderServiceEditProfile(OtherServiceProviderStoreRequest $request)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $provider_id = Auth::guard('on_demand')->user()->id;
        $provider = $this->adminClass->AddServiceProvider($request);
        $provider_details = OtherServiceProviderDetails::query()->where('provider_id', $provider_id)->first();
        if ($provider_details == Null) {
            $provider_details = new OtherServiceProviderDetails();
            $provider_details->provider_id = $provider_id;
        }

        //update provider type status
        $provider_data =  Provider::query()->where('id','=',$provider->id)->whereNull('providers.deleted_at')->first();
        if($provider_data != Null){
            $provider_data->provider_type = 3;
            $provider_data->save();
        }

        $provider_details->address = $request->get('address');
        $provider_details->lat = $request->get('lat');
        $provider_details->long = $request->get('long');
        $provider_details->min_order = ($request->get('min_order') == null) ? 0 : $request->get('min_order');
        $provider_details->landmark = $request->get('landmark');
        $open_time_status = $request->get('open_time_status') != Null ? 1 : 0;
        $provider_details->time_slot_status = $open_time_status;
        $all_day = ($request->get('all_day') != Null)?$request->get('all_day'):0;
        $provider_details->all_day = $all_day;
        $provider_details->save();

        $day_open_time = $request->get('day_open_time');

        //slot inseert and update
        $not_del_id_array=[];
        $i=0;

        if($all_day == 1){
            $activeday = strtolower($request->get('activeday'));
            $day_array  = array("SUN","MON","TUE","WED","THU","FRI","SAT");
            foreach ($day_array as $key => $day) {
                if( count($day_array) > 0){
                    if (isset($day_open_time[$activeday])) {
                        foreach ($day_open_time[$activeday] as $singel_day) {
                            $single_time_arr = explode("-",$singel_day);
                            $provider_open_time =  $single_time_arr[0];
                            $provider_close_time =  $single_time_arr[1];

                            $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtoupper(trim(($day))))->where('provider_open_time', $provider_open_time)->where('provider_close_time', $provider_close_time)->first();
                            if ($get_open_timing == Null) {
                                $get_open_timing_ins = new OtherServiceProviderTimings();
                                $get_open_timing_ins->provider_id = $provider->id;
                                $get_open_timing_ins->day = strtoupper(trim(($day)));
                                $get_open_timing_ins->provider_open_time = $provider_open_time;
                                $get_open_timing_ins->provider_close_time = $provider_close_time;
                                $get_open_timing_ins->open_time_list = "";
                                $get_open_timing_ins->save();
                                $not_del_id_array[$i] = $get_open_timing_ins->id;
                            } else {
                                //get not delete id
                                $not_del_id_array[$i] = $get_open_timing->id;
                            }
                            $i++;
                        }
                        if(count($not_del_id_array) > 0){
                            OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtoupper(trim(($day))))->whereNotIn('id', $not_del_id_array)->delete();
                        }
                    } else {
                        OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->delete();
                    }
                }
            }
        } else {
            $day_array  = array("SUN","MON","TUE","WED","THU","FRI","SAT");
            if($day_open_time != Null) {
                foreach ($day_open_time as $key => $day) {
                    $day_name = strtoupper($key);
                    if (($key_data = array_search($day_name, $day_array)) !== false) {
                        unset($day_array[$key_data]);
                    }
                    if( count($day) > 0){
                        foreach($day as $singel_day) {
                            $single_time_arr = explode("-",$singel_day);
                            $provider_open_time =  $single_time_arr[0];
                            $provider_close_time =  $single_time_arr[1];
                            $get_open_timing = OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtoupper(trim(($key))))->where('provider_open_time', $provider_open_time)->where('provider_close_time', $provider_close_time)->first();
                            if ($get_open_timing == Null) {
                                $get_open_timing_ins = new OtherServiceProviderTimings();
                                $get_open_timing_ins->provider_id = $provider->id;
                                $get_open_timing_ins->day = strtoupper(trim(($key)));
                                $get_open_timing_ins->provider_open_time = $provider_open_time;
                                $get_open_timing_ins->provider_close_time = $provider_close_time;
                                $get_open_timing_ins->open_time_list = "";
                                $get_open_timing_ins->save();
                                $not_del_id_array[$i] = $get_open_timing_ins->id;
                            } else {
                                //get not delete id
                                $not_del_id_array[$i] = $get_open_timing->id;
                            }
                            $i++;
                        }
                        if(count($not_del_id_array) > 0){
                            OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->where('day', strtoupper(trim(($key))))->whereNotIn('id', $not_del_id_array)->delete();
                        }
                    }
                }
                if(count($day_array) > 0){
                    OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->whereIn('day',$day_array)->whereNotIn('id', $not_del_id_array)->delete();
                }
            } else {
                OtherServiceProviderTimings::query()->where('provider_id', $provider->id)->delete();
            }
        }

        if ($request->get('account_number') != Null && $request->get('holder_name') != Null && $request->get('bank_name') != Null && $request->get('bank_location') != Null && $request->get('payment_email') != Null && $request->get('bic_swift_code') != Null) {
            $provider_bank_details = ProviderBankDetails::query()->where('provider_id', $provider_id)->first();
            if ($provider_bank_details == Null) {
                $provider_bank_details = new ProviderBankDetails();
                $provider_bank_details->provider_id = $provider_id;
            }
            $provider_bank_details->account_number = $request->get('account_number');
            $provider_bank_details->holder_name = $request->get('holder_name');
            $provider_bank_details->bank_name = $request->get('bank_name');
            $provider_bank_details->bank_location = $request->get('bank_location');
            $provider_bank_details->payment_email = $request->get('payment_email');
            $provider_bank_details->bic_swift_code = $request->get('bic_swift_code');
            $provider_bank_details->save();
        }
        Session::flash('success', 'Provider profile updated successfully!');
        return redirect()->route('get:provider-admin:edit-profile');
    }

    public function getProviderOtherServiceEarningReport(Request $request, $slug)
    {
        $service_category = ServiceCategory::query()->select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }
        $package_order_list = UserPackageBooking::query()->select('user_service_package_booking.id',
            'user_service_package_booking.order_no',
            'user_service_package_booking.order_type',
            'user_service_package_booking.service_date_time',
            'user_service_package_booking.user_name',
            'user_service_package_booking.provider_name',
            'user_service_package_booking.total_item_cost',
            'user_service_package_booking.service_time',
            'user_service_package_booking.extra_amount',
            'user_service_package_booking.tax',
            'user_service_package_booking.tip',
            'user_service_package_booking.refer_discount',
            'user_service_package_booking.total_pay',
            'user_service_package_booking.provider_amount',
            'user_service_package_booking.refer_discount',
            'user_service_package_booking.created_at',
            'user_service_package_booking.user_id',
            'user_service_package_booking.admin_commission',
            'user_service_package_booking.provider_pay_settle_status',
            'user_service_package_booking.provider_id',
            'user_service_package_booking.payment_type','used_promocode_details.discount_amount as promocode')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', '=','user_service_package_booking.promo_code')
            ->where('user_service_package_booking.status', 9)
            ->where('user_service_package_booking.service_cat_id', $service_category->id)
            ->where('user_service_package_booking.provider_id', Auth::guard('on_demand')->user()->id);

        $from_date = Date('Y-m-d');
        $to_date = Date('Y-m-d');
        $from = Date('Y-m-d', strtotime($from_date)) . " 00:00:00";
        $to = Date('Y-m-d', strtotime($to_date)) . " 23:59:59";

        $package_order_list->whereDate('user_service_package_booking.service_date_time', '>=', $from);
        $package_order_list->whereDate('user_service_package_booking.service_date_time', '<=', $to);

        $package_order_list = $package_order_list->orderBy('user_service_package_booking.id', "desc")->get();
        $total_amount = $package_order_list->sum('total_pay');
        $site_commission = $package_order_list->sum('admin_commission');
        $provider_earning = $package_order_list->sum('provider_amount');
        $total_discount = $package_order_list->sum('promocode');
        $refer_total = $package_order_list->sum('refer_discount');
        $collect_from_provider = ($package_order_list->where('payment_type', 1)->sum('total_pay')) - ($package_order_list->where('payment_type', 1)->sum('provider_amount'));
        $view = view('admin.pages.other_services.earning_report.provider_earning_report', compact('slug',
            'service_category', 'from_date', 'to_date', 'package_order_list', 'total_amount', 'site_commission',
            'provider_earning', 'collect_from_provider', 'total_discount','refer_total'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postProviderOtherServiceEarningReport(Request $request, $slug)
    {
        $service_category = ServiceCategory::query()->select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }
        $from_date = ($request['from_date'] != Null && $request['to_date'] != Null) ? $request['from_date'] : Null;
        $to_date = ($request['from_date'] != Null && $request['to_date'] != Null) ? $request['to_date'] : Null;
        $payment_type = $request['payment_type'] != Null ? $request['payment_type'] : Null;
        $provider_pay_type = $request['provider_pay_type'] != Null ? $request['provider_pay_type'] : Null;

        $package_order_list = UserPackageBooking::query()->select('user_service_package_booking.id',
            'user_service_package_booking.order_no',
            'user_service_package_booking.order_type',
            'user_service_package_booking.service_date_time',
            'user_service_package_booking.user_name',
            'user_service_package_booking.provider_name',
            'user_service_package_booking.total_item_cost',
            'user_service_package_booking.service_time',
            'user_service_package_booking.extra_amount',
            'user_service_package_booking.tax',
            'user_service_package_booking.tip',
            'user_service_package_booking.refer_discount',
            'user_service_package_booking.total_pay',
            'user_service_package_booking.provider_amount',
            'user_service_package_booking.refer_discount',
            'user_service_package_booking.created_at',
            'user_service_package_booking.user_id',
            'user_service_package_booking.admin_commission',
            'user_service_package_booking.provider_pay_settle_status',
            'user_service_package_booking.provider_id',
            'user_service_package_booking.payment_type','used_promocode_details.discount_amount as promocode')
            ->leftJoin('used_promocode_details', 'used_promocode_details.id', '=','user_service_package_booking.promo_code')
            ->where('user_service_package_booking.status', 9)
            ->where('user_service_package_booking.service_cat_id', $service_category->id)
            ->where('user_service_package_booking.provider_id', Auth::guard('on_demand')->user()->id);
        if ($from_date != Null && $to_date != Null) {
            $from = Date('Y-m-d', strtotime($from_date)) . " 00:00:00";
            $to = Date('Y-m-d', strtotime($to_date)) . " 23:59:59";
            $package_order_list->whereDate('service_date_time', '>=', $from);
            $package_order_list->whereDate('service_date_time', '<=', $to);
        }
        $used_promo_cods = [];
//        $collect_payment = [];
//        $pay_payment = [];

        if ($payment_type != Null) {
            //cash & card
            $package_order_list->where('payment_type', $payment_type);
        }
        if ($provider_pay_type != Null) {
            $package_order_list->where('provider_pay_settle_status', $provider_pay_type);
        }
        $package_order_list = $package_order_list->orderBy('id', "desc")->get();
//        $promo_total=0;
//        foreach($package_order_list as $order){
//            $promo = UsedPromocodeDetails::query()->where('id',$order->promo_code)->first();
//            if($promo != Null){
//                $used_promo_cods[$order->id] = $promo->discount_amount;
//                $promo_total=$promo_total+$promo->discount_amount;
//            }
//            if($order->payment_type == 1)
//            {
//                $collect_payment[$order->id] = $order->total_pay - $order->provider_amount;
//                $pay_payment[$order->id] = 0;
//            }
//            else{
//                $collect_payment[$order->id] = 0;
//                $pay_payment[$order->id] = $order->provider_amount;
//            }
//        }
        $total_amount = $package_order_list->sum('total_pay');
        $site_commission = $package_order_list->sum('admin_commission');
        $provider_earning = $package_order_list->sum('provider_amount');
        $refer_total = $package_order_list->sum('refer_discount');
        $total_discount = $package_order_list->sum('promocode');
        $collect_from_provider = ($package_order_list->where('payment_type', 1)->sum('total_pay')) - ($package_order_list->where('payment_type', 1)->sum('provider_amount'));
        $view = view('admin.pages.other_services.earning_report.provider_earning_report', compact('slug','payment_type', 'provider_pay_type',
            'service_category','used_promo_cods' ,'from_date', 'to_date', 'package_order_list', 'total_amount', 'site_commission',
            'provider_earning', 'collect_from_provider','refer_total','total_discount'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;

    }

    public function getUpdateOtherServiceOrderStatus(Request $request)
    {
        if($this->is_restricted == 1){
            return response()->json([
                'success' => false,
                'message' => 'Add / Edit / Delete Property has been disabled in the Demo Admin Panel. We will provide the enabled features in the main clone script.'
            ]);
        }
        $validator = Validator::make($request->all(), [
            "id" => "required|numeric",
            "update_status" => "required|numeric|in:5,9"
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => $validator->errors()->first()
            ]);
        }
        $id = $request->get('id');
        $update_status = $request->get('update_status');
        if ($update_status == 5) {
            $validator = Validator::make($request->all(), [
                "reason" => "required"
            ]);
            if ($validator->fails()) {
                return response()->json([
                    "status" => false,
                    "message" => $validator->errors()->first()
                ]);
            }
        }
        $order_details = UserPackageBooking::query()->where('id', $id)->first();
        if ($order_details == Null) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        $user_details = User::query()->where('id', $order_details->user_id)->first();
        //$user_details = User::select('device_token', 'login_device')->where('id', $order_details->user_id)->first();
        if ($order_details->status == 4 || $order_details->status == 5) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ]);
        }
        switch ($update_status) {
            case 5:
                if ($order_details->status != 4 && $order_details->status != 5 && $order_details->status != 9 && $order_details->status != 10) {
                    $order_status = $order_details->status;
                    $order_details->status = $update_status;
                    $order_details->cancel_by = "admin";
                    $order_details->cancel_reason = $request->get('reason');
                    $order_details->save();
                    if ($user_details != Null) {
                        $notificationClss = New NotificationClass();
                        $notificationClss->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status, $user_details->language);
                    }

                    ProviderAcceptedPackageTime::query()->where('provider_id', '=', $order_details->provider_id)->where('order_id', '=', $order_details->id)->delete();

                    $other_provider_details = Provider::query()->where('id', $order_details->provider_id)->whereNull('providers.deleted_at')->first();
                    if ($other_provider_details != Null) {
                        $notificationClss = New NotificationClass();
                        $notificationClss->providerOrderCancelRequestNotification($order_details->id, $order_details->status, $other_provider_details->device_token, $other_provider_details->language);
                    }
                    //deleting chat from firebase
                    (new FirebaseService())->deleteOrderChat($order_details->order_no,$order_details->id);
                    if ($order_status < 8){
                        if ($order_details->promo_code > 0){
                            $used_promocode_details = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                            if ($used_promocode_details != Null) {
                                $get_promocode = PromocodeDetails::query()->where('id', $used_promocode_details->promocode_id)->first();
                                if ($get_promocode != Null) {
                                    $count_promocode = $get_promocode->total_usage - 1;
                                    $get_promocode->total_usage = ($count_promocode > 0)? $count_promocode : 0;
                                    $get_promocode->save();
                                }
                                $used_promocode_details->status = 2;
                                $used_promocode_details->save();
                            }
                        }

                        if ($order_details->refer_discount > 0) {
//                    $user = User::query()->where('id', $order_details->user_id)->whereNull('deleted_at')->first();
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
                        'success' => false,
                        'message' => 'Data not found'
                    ]);
                }
                break;
            case 9:
                if ($order_details->status > 1 && $order_details->status < 9) {
                    if ($order_details->status != 4 && $order_details->status != 5 && $order_details->status != 9 && $order_details->status != 10) {
                        $order_details->status = $update_status;
                        $order_details->extra_amount = $request->get('extra_charge') ?? 0;
                        UserPackageBooking::query()->where('id', $order_details->id)->update(['extra_amount' => $order_details->extra_amount]);

                        //check weather the tax and admin commission is applied
                        $find_tax = ServiceSettings::query()->where('service_cat_id', $order_details->service_cat_id)->first();
                        $get_discount = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                        if($get_discount != null){
                            $promo_code_discount = $get_discount->discount_amount;
                        }else{
                            $promo_code_discount = 0;
                        }

                        if ($find_tax != Null) {
                            $provider_details = Provider::where('id', $order_details->provider_id)->whereNull('providers.deleted_at')->first();
                            $get_tax = $find_tax->tax;
                            $admin_commission = $find_tax->admin_commission;
                        } else {
                            $get_tax = 0;
                            $admin_commission = 0;
                        }
                        $order_details->BookingCost($order_details->total_item_cost, $get_tax, $admin_commission, $promo_code_discount, $order_details->refer_discount,$order_details->extra_amount);

                        $service_category = ServiceCategory::query()->where('id', $order_details->service_cat_id)->first();
                        if ($service_category == Null) {
                            Session::flash('error', __('user_messages.9'));
                            return redirect()->back();
                        }
                        $notificationClss = new NotificationClass();

                        $general_settings = request()->get("general_settings");
                        //auto settle code for admin complete
                        if ($update_status == 9)
                        {
                            if ($general_settings->auto_settle_wallet == 1 && $order_details->provider_pay_settle_status != 1) {
                                $provider_id = $order_details->provider_id;
                                $service_cat_id = $order_details->service_cat_id;
                                $order_no = $order_details->order_no;
                                $transaction_type = 0;
                                $subject = '';
                                $subject_code = 0;
                                $wallet_provider_type = 3;

                                if ($order_details->payment_type == 1) {
                                    //if payment type is cash
                                    $transaction_type = 2;
                                    $add_update_wallet_bal = $order_details->admin_commission + $order_details->tax;
                                    $subject = "Debited By admin - " . $service_category->name . " Booking # " . $order_details->order_no;
                                    $subject_code = 13;
                                } elseif ($order_details->payment_type == 2 || $order_details->payment_type == 3) {
                                    //if payment type is not cash then
                                    $transaction_type = 1;
                                    $add_update_wallet_bal = ($order_details->provider_amount + $order_details->tip);
                                    $subject = "Credited by Admin for your earning - " . $service_category->name . " Booking # " . $order_details->order_no;
                                    $subject_code = 14;
                                }

                                $provider_wallet_update = $notificationClss->providerUpdateWalletBalance($provider_id, $wallet_provider_type,$transaction_type, $add_update_wallet_bal, $subject, $subject_code, $order_no);
                                if ($provider_wallet_update) {
                                    $order_details->provider_pay_settle_status = 1;
                                    $order_details->save();
                                }
                            }
                        }
                        //auto settle code for admin end

                        $order_details->status = $update_status;
                        $order_details->payment_status = 1;
                        $order_details->save();

                        if ($order_details->promo_code > 0){
                            $used_promocode_details = UsedPromocodeDetails::query()->where('id', $order_details->promo_code)->first();
                            if ($used_promocode_details != Null) {
                                $used_promocode_details->status = 1;
                                $used_promocode_details->save();
                            }
                        }

                        ProviderAcceptedPackageTime::query()->where('provider_id', '=', $order_details->provider_id)->where('order_id', '=', $order_details->id)->delete();

                        $other_provider_details = OtherServiceProviderDetails::query()->where('provider_id', $order_details->provider_id)->first();

                        if ($other_provider_details != Null) {
                            $other_provider_details->total_completed_order = $other_provider_details->total_completed_order + 1;
                            $other_provider_details->save();
                        }
                        //deleting chat from firebase
                        (new FirebaseService())->deleteOrderChat($order_details->order_no,$order_details->id);
                        $notificationClass = new NotificationClass();
                        if ($user_details != Null) {
                            $notificationClass->userProviderPackageNotification($order_details->id, $user_details->device_token, $order_details->status, $user_details->language);
                        }
                        $other_provider_details_data = Provider::query()->where('id', $order_details->provider_id)->first();
                        if ($other_provider_details_data != Null) {
                            $notificationClass->providerOrderPackageNotification($order_details->id, $other_provider_details_data->device_token,$order_details->status, $other_provider_details_data->language,1);
                        }

                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Data not found'
                        ]);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data not found'
                    ]);
                }
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ]);
        }
        return response()->json([
            'success' => true
        ]);
    }

    /* If user is not verified then redirect to verify OTP screen */
    public function getProviderNotVerified(Request $request){
        $provider_id = Auth::guard('on_demand')->user()->id;
        //$provider_status = Auth::guard('on_demand')->user()->status;
        //if ($provider_status != 3){
        //    return redirect()->back();
        //}
        if(Auth::guard('on_demand')->check()) {
            $verified = Auth::guard('on_demand')->user()->verified_at;
            if ($verified == Null) {
                $view = view('admin.pages.other_services.provider_not_verify');
                if ($request->ajax()) {
                    $view = $view->renderSections();
                    return $this->adminClass->renderingResponce($view);
                }
                return $view;
            } else {
                $is_register = Auth::guard('on_demand')->user()->is_register;
                if ($is_register == 0) {
                    return redirect()->route('get:provider-admin:sign_up_pending');
                }
                return redirect()->route('get:provider-admin:dashboard');
            }
        } else {
            Auth::logout();
            return redirect()->route('get:provider-admin:login');
        }
    }

    /*Resend otp*/
    public function getProviderResendVerificationLink(Request $request){
        $provider_id = Auth::guard('on_demand')->user()->id;
        ProviderVerification::where('provider_id', $provider_id)->delete();
        $tokenClassApi = new TokenClassApi();
        $tokenClassApi->sendProviderSmsVerification($provider_id);

        Session::flash('success', 'Fresh OTP has been sent to your Registered Phone Number');
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            Session::flash('success', 'Fresh OTP has been sent to your Registered Phone Number');
            return $provider_status;
        }
        return redirect()->back();
    }

    /* Verify OTP */
    public function postProviderOtpVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "provider_id" => "required",
            "otp_1" => "required|numeric",
            "otp_2" => "required|numeric",
            "otp_3" => "required|numeric",
            "otp_4" => "required|numeric",
        ]);
        if ($validator->fails()) {
            Session::flash('error', 'OTP Fields required!');
            return redirect()->back();
        }
        $provider_details = Provider::query()->where('id', $request->get('provider_id'))->first();
        if ($provider_details != Null) {
            $otp = $request->get('otp_1') . $request->get('otp_2') . $request->get('otp_3') . $request->get('otp_4');

            $settings = request()->get('general_settings');
            if ($settings == Null) {
                Session::flash('error', 'Something went to wrong!');
                return redirect()->back();
            }
            if ($settings->is_otp_verification != Null && $settings->is_otp_verification == 1 && $provider_details->is_default_user == 0 && $provider_details->fix_user_show == 0) {
                if (isset($settings->otp_method)) {
                    if ($settings->otp_method == 1) {
                        $get_otp = ProviderVerification::query()->where('provider_id', $provider_details->id)->first();
                        if ($get_otp == Null) {
                            Session::flash('error', 'OTP Details not found!');
                            return redirect()->back();
                        }
                        if ($settings->twilio_service_key == Null || $settings->twilio_auth_token == Null || $settings->twilio_verify_service_key == Null) {
                            Session::flash('error', 'Something went to wrong!');
                            return redirect()->back();
                        }
                        try {
                            $twilio = new Client($settings->twilio_service_key, $settings->twilio_auth_token);
                            $option = [
                                'To' => $provider_details->country_code.$provider_details->contact_number,
                                'VerificationSid' => $get_otp->token,
                            ];
                            $verification_check = $twilio->verify->v2->services($settings->twilio_verify_service_key)->verificationChecks->create($otp, $option);
                            if ($verification_check->status == "approved") {
                                $verification_sid = $verification_check->sid;
                                if ($verification_sid != $get_otp->token) {
                                    Session::flash('error', 'Entered OTP is Incorrect!');
                                    return redirect()->back();
                                }
                                ProviderVerification::query()->where('provider_id', $provider_details->id)->delete();

                                $provider_details->verified_at = date('Y-m-d H:i:s');
                                $provider_details->verified_at = date('Y-m-d H:i:s');
                                $provider_details->save();
                                $provider_status = $this->adminClass->checkProviderStatus();
                                if ($provider_status != Null) {
//                                    Session::flash('error', 'New OTP Send to your Registered Number');
                                    return $provider_status;
                                }
                                return redirect()->back();
                            } else {
                                Session::flash('error', 'Entered OTP is Incorrect!');
                                return redirect()->back();
                            }
                        } catch (\Exception $e) {
                            Session::flash('error', 'Entered OTP is Incorrect!');
                            return redirect()->back();
                        }
                    }
                    //elseif ($settings->otp_method == 2){}
                    else{
                        Session::flash('error', 'Verify method does not exists!');
                        return redirect()->back();
                    }
                } else {
                    Session::flash('error', 'Verify method does not exists');
                    return redirect()->back();
                }
            } else {
                if ($otp == "1234") {
                    ProviderVerification::query()->where('provider_id', $provider_details->id)->delete();

                    $provider_details->verified_at = date('Y-m-d H:i:s');
                    $provider_details->save();
                    $provider_status = $this->adminClass->checkProviderStatus();
                    if ($provider_status != Null) {
//                        Session::flash('error', 'New OTP Send to your Registered Number');
                        return $provider_status;
                    }
//                    Session::flash('success', 'Provider Admin Login Successfully.');
//                    return redirect()->route('get:provider-admin:dashboard');
                    return redirect()->back();
                } else {
                    Session::flash('error', 'Entered OTP is Incorrect!');
                    return redirect()->back();
                }
            }
        } else {
            Session::flash('error', 'Provider not found!.');
            return redirect()->back();
        }
    }

    /*Portfolio page*/
    public function getProviderOtherServicePortfolio(Request $request,$slug)
    {
        $id = Auth::guard('on_demand')->user()->id;
        $service_category = ServiceCategory::query()->select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }
        $portfolio_lists = ProviderPortfolioImage::query()->where('service_cat_id', '=', $service_category->id)->where('provider_id', '=', $id)->get();
        $view = view('admin.pages.other_services.portfolio.form_manage', compact('portfolio_lists','slug'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    /* Upload portfolio image */
    public function postProviderOtherServicePortfolio(Request $request,$slug)
    {

        $validator = Validator::make($request->all(), [
            "portfolio_image" => "mimes:jpeg,jpg,png|required|max:2024"
        ]);
        if ($validator->fails()) {
            $msg = isset($validator->errors()->getMessages()['portfolio_image'][0])?$validator->errors()->getMessages()['portfolio_image'][0]:"Please Upload Valid Image";
            Session::flash('error', $msg);
            return redirect()->back();
        }
        $id = Auth::guard('on_demand')->user()->id;
        $service_category = ServiceCategory::query()->select('id', 'name')->where('slug', $slug)->first();
        if ($service_category == Null) {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }
        $total_upload_ort_image = ProviderPortfolioImage::query()->where('provider_id','=',$id)->where('service_cat_id','=',$service_category->id)->count();
        if($total_upload_ort_image >= 9){
            Session::flash('error', 'Sorry! You can upload Maximum 9 image in your portfolio ');
            return redirect()->back();
        }

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
            $provider_portfolio_document->service_cat_id = $service_category->id;
            $provider_portfolio_document->provider_id = $id;
            $provider_portfolio_document->image = $file_new;
            $provider_portfolio_document->status = 1;
            $provider_portfolio_document->save();
        }
        Session::flash('success', 'Portfolio Added Successfully!');
        return redirect()->route('get:provider-admin:other_service_portfolio',['slug'=>$slug]);
    }

    /* Delete Portfolio */
    public function getOtherServiceProviderDeletePortfolio(Request $request)
    {
        $id = $request->get('portfolio_id');
        if ($id == Null) {
            Session::flash('error', 'Category Not Found!');
            return response()->json([
                'success' => false,
                'errorMsg' => "something went to wrong",
            ]);
        }
        $user_id = Auth::guard('on_demand')->user()->id;
        ProviderPortfolioImage::query()->where('id','=',$id)->delete();
        return response()->json([
            "success" => true,
            'errorMsg' => "portfolio deleted successfully"
        ]);
    }

    public function getProviderManageCard(Request $request)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $id = Auth::guard('on_demand')->user()->id;
        $card_lists = UserCardDetails::query()->where('card_provider_type', '=', 3)->where('user_id', '=', $id)->get();
        $view = view('admin.pages.other_services.card.form_manage', compact('card_lists'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postProviderUpdateCard(AddCardRequest $request)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }

        $id = Auth::guard('on_demand')->user()->id;
        $exp_date_arr = explode("-", $request->get('expiry_date'));
        $year = isset($exp_date_arr[0]) ? $exp_date_arr[0] : 0;
        $month = isset($exp_date_arr[1]) ? $exp_date_arr[1] : 1;
        $card_details = new UserCardDetails();
        $card_details->user_id = $id;
        $card_details->card_provider_type = 3;
        $card_details->holder_name = $request->get('card_holder_name');
        $card_details->card_number = $request->get('card_number');
        $card_details->month = $month;
        $card_details->year = $year;
        $card_details->cvv = $request->get('cvv');
        $card_details->save();

        Session::flash('success', 'Card Added Successfully!');
        return redirect()->route('get:provider-admin:manage_card');
    }

    public function getProviderDeleteCard(Request $request,$card_id = 0)
    {
        $id = $request->get('card_id');
        if ($id == Null) {
            Session::flash('error', 'Category Not Found!');
            return response()->json([
                'success' => false,
                'errorMsg' => "something went to wrong",
            ]);
        }
        $user_id = Auth::guard('on_demand')->user()->id;
        try {
            UserCardDetails::query()->where('card_provider_type', "=", 3)
                ->where('user_id', "=", $user_id)->where('id', "=", $id)->delete();
            return response()->json([
                "success" => true,
                'errorMsg' => "card deleted successfully"
            ]);
        }Catch(\Exception $e){
            return response()->json([
                "success" => false,
                'errorMsg' => "Something went wrong"
            ]);
        }
    }

    public function getProviderWallet(Request $request, $page = 1)
    {
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $id = Auth::guard('on_demand')->user()->id;
        $per_page_rec = 10;
        $per_page_rec = ($page > 0) ? $per_page_rec : 5;

        $get_wallet_history = UserWalletTransaction::query()
            ->where('user_id', $id)
            ->where('wallet_provider_type', '=', 3)
            ->orderBy('id', 'desc')
            ->get();
//            ->paginate($per_page_rec);


        $total_avail_bal = UserWalletTransaction::query()->select('remaining_balance')
            ->where('user_id', $id)
            ->where('wallet_provider_type', '=', 3)
            ->orderBy('id', 'desc')
            ->first();

        $card_list = UserCardDetails::query()->select('card_details.id', 'card_details.holder_name as card_holder_name', 'card_details.card_number as card_number')
            ->where('user_id', '=', $id)
            ->orderBy('id', 'desc')
            ->where('card_provider_type', '=', 3)
            ->get();

        $view = view('admin.pages.other_services.wallet.wallet', compact('get_wallet_history', 'total_avail_bal', 'card_list'));
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postProviderWallet(Request $request, $page = 1){
        $provider_status = $this->adminClass->checkProviderStatus();
        if ($provider_status != Null) {
            return $provider_status;
        }
        $provider_id = Auth::guard('on_demand')->user()->id;
        $amount = $request->get('amount');
        $card_id = $request->get('card_id');
        if ($amount > 0) {
            $check_user_card = UserCardDetails::query()->where('user_id', '=', $provider_id)->where('card_provider_type', '=', 3)->where('id', '=', $card_id)->first();
            if ($check_user_card != Null) {

                $provider_data = Provider::query()->where('id','=',$provider_id)->first();
                $provider_name = "";
                if($provider_data != Null){
                    $provider_name = $provider_data->first_name;
                }

                $get_current_wallet_data = UserWalletTransaction::query()->where('user_id', $provider_id)->where('wallet_provider_type', '=', 3)->orderBy('id', 'desc')->first();

                $add_wallet = new UserWalletTransaction();
                $add_wallet->user_id = $provider_id;
                $add_wallet->wallet_provider_type = 3;
                $add_wallet->transaction_type = 1;
                $add_wallet->amount = $amount;
                $add_wallet->order_no = $provider_name;
                $add_wallet->subject = "credited by ".$provider_name;
                if ($get_current_wallet_data != Null) {
                    $add_wallet->remaining_balance = $get_current_wallet_data->remaining_balance + $amount;
                } else {
                    $add_wallet->remaining_balance = $amount;
                }
                $add_wallet->subject_code = 1;
                $add_wallet->save();

                Session::flash('success', 'Amount Has been successfully added');
                return redirect()->back();
            } else {
                Session::flash('warning', 'Invalid Card Detail!.');
                return redirect()->back();
            }
        } else {
            Session::flash('warning', 'amount must be greater than 0!.');
            return redirect()->back();
        }
    }
}
