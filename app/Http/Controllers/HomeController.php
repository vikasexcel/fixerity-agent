<?php

namespace App\Http\Controllers;

use App\Models\GeneralSettings;
use App\Models\PageSettings;
use App\Models\Provider;
use App\Models\ProviderServices;
use App\Models\ProviderVerification;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class HomeController extends Controller
{
    public function __construct()
    {
//        $this->middleware('auth');
    }

    public function index()
    {
        return view('home');
    }

    public function getTermsAndConditions(Request $request)
    {
        //1=user;
        $get_page_data = PageSettings::query()->where('name', 'LIKE', "%terms%")->where('type', 1)->first();
        if ($get_page_data != Null) {
            return view('terms-and-condition-new', compact('get_page_data'));
        }
        return view('terms-and-condition-new');
    }

    public function getProviderTermsAndConditions(Request $request)
    {
        //2=provider
        $get_page_data = PageSettings::query()->where('name', 'LIKE', "%terms%")->where('type', 2)->first();
        if ($get_page_data != Null) {
            return view('terms-and-condition-new', compact('get_page_data'));
        }
        return view('terms-and-condition-new');
    }

    public function getPrivacyPolicy(Request $request)
    {
        //1=user;
        $title = "privacy policy";
        $get_page_data = PageSettings::query()->where('name', 'LIKE', "%privacy%")->where('type', 1)->first();
        if ($get_page_data != Null) {

            return view('terms-and-condition-new', compact('get_page_data', 'title'));
        }
        return view('terms-and-condition-new', compact('title'));
    }

    public function getDisclaimer(Request $request)
    {
        //1=user;
        $title = "disclaimer";
        $get_page_data = PageSettings::query()->where('name', 'LIKE', "%disclaimer%")->where('type', 1)->first();
        if ($get_page_data != Null) {

            return view('terms-and-condition-new', compact('get_page_data', 'title'));
        }
        return view('terms-and-condition-new', compact('title'));
    }

    public function getFaq(Request $request)
    {
        //1=user;
        $title = "faq";
        $get_page_data = PageSettings::query()->where('name', 'LIKE', "%faq%")->where('type', 1)->first();
        if ($get_page_data != Null) {
            return view('terms-and-condition-new', compact('get_page_data', 'title'));
        }
        return view('terms-and-condition-new', compact('title'));
    }

    public function getProviderPrivacyPolicy(Request $request)
    {
        //2=provider
        $get_page_data = PageSettings::query()->where('name', 'LIKE', "%privacy%")->where('type', 2)->first();
        if ($get_page_data != Null) {
            return view('terms-and-condition-new', compact('get_page_data'));
        }
        return view('terms-and-condition-new');
    }

    public function getFile($filename)
    {
        $icon = GeneralSettings::query()->first();
        if ($icon != Null) {
            $icon = asset('assets/images/website-logo-icon/' . $icon->website_favicon);
        } else {
            $icon = '';
        }
        return "<head><link rel='icon' href='' type='image/x-icon'></head><body style='margin: 0px; background: #0e0e0e; text-align: center;'><img style='-webkit-user-select: none; margin-left: auto;margin-right: auto; position: relative; top: 50%; transform: translateY(-50%);' src='" . asset('/assets/images/provider-documents/' . $filename) . "'></body>";
    }

    public function getSecurity(Request $request)
    {
        //1=user;
        $title = "Security";
        $get_page_data = PageSettings::query()->where('name', 'LIKE', "%security%")->where('type', 1)->first();
        if ($get_page_data != Null) {
            return view('terms-and-condition-new', compact('get_page_data', 'title'));
        }
        return view('terms-and-condition-new', compact('title'));
    }

    public function postDataDeletionStatus($reference)
    {
        $user = User::query()->where('login_id', $reference)->first();
        if ($user == Null) {
            $provider = Provider::query()->where('login_id', $reference)->first();
            if ($provider != NUll) {
                return view('success_deletion');
            }
            return view('failed_deletion');
        } else {
            return view('success_deletion');
        }
    }

    /* -----------------------------------For Play-Store, App-Store Upload account_deletion-------------------------- */
    // provider_type = 1:store,2:driver,3:provider

    // getAccountDeletion login
    public function getAccountDeletion()
    {
        $guard = "";
        if (Auth::guard('user')->check()) {
            $guard = 'user';
            $user_details = User::query()->where('id', Auth::guard($guard)->id())->whereNull('deleted_at')->first();
        } elseif (Auth::guard('on_demand')->check()) {
            $guard = 'on_demand';
            $user_details = Provider::query()->where('id', Auth::guard($guard)->id())
                ->where('provider_type', '=', 3)
                ->whereNull('deleted_at')->first();
        } else {
            $user_details = null;
        }
        if ($user_details != null) {
            return redirect()->route('get:account:deletion:profile');
        }

        // account_deletion
        return view('account_deletion.login');
    }

    // store AccountDeletion login
//    public function postAccountDeletion(Request $request)
//    {
//
//        \Log::info("\n postAccountDeletion \n");
//        $validator = Validator::make($request->all(), [
//            'contact_number' => 'required',
//            'country_code' => 'required',
//            'password' => 'required',
//            'full_number' => 'nullable',
//            'roles' => 'required|numeric',
//        ]);
//
//        if ($validator->fails()) {
//            return redirect()->back()->with('error', $validator->errors()->first());
//        }
//        $settings = request()->get('general_settings');
//
//        $roles = $request->get('roles');
//        $country_code = $request->get('country_code');
//        $contact_number = $request->get('contact_number');
//        $password = Hash::make($request->get('password'));
//        $user_type = ($roles > 0) ? ($roles == 3) ? "on_demand" : "user" : "user";
//        if($user_type='user')
//        {
//            $super_admin = User::where('contact_number', $contact_number)->first();
//        }
//        else{
//            $super_admin = Provider::where('contact_number', $contact_number)->first();
//        }
//            if ($super_admin != null && Hash::check($request->get('password'), $super_admin->password)) {
//                if ($roles > 0) {
//                    $provider = Provider::query()->where('contact_number', '=', $contact_number)
//                        ->where('country_code', '=', $country_code)
//                        ->where('provider_type', '=', $roles)
//                        ->whereNull('deleted_at')->first();
//                    if ($provider == null) {
//                        //$provider = new Provider();
//                        return redirect()->route('get:account:deletion:login')->with('error', 'Your account is not registered with us please try again later.');
//                    }
//                    $provider->contact_number = $contact_number;
//                    $provider->country_code = $country_code;
//                    $provider->password = $password;
//                    $provider->provider_type = $roles;
//                    $provider->status = 1;
//                    $provider->save();
//                    Auth::guard($user_type)->login($provider);
//                }
//
//            else {
//                $user = User::query()->where('contact_number', '=', $contact_number)
//                    ->where('country_code','=',$country_code)
//                    ->whereNull('deleted_at')->first();
//                if ($user == null) {
//                 //  $user = new User();
//                    return redirect()->route('get:account:deletion:login')->with('error','Your account is not registered with us please try again later.');
//                }
//                $user->contact_number = $contact_number;
//                $user->country_code = $country_code;
//                $user->password = $password;
//                $user->status = 1;
//                $user->save();
//                Auth::guard($user_type)->login($user);
//            }
//
//            return redirect()->route('get:account:deletion:profile')->with('success', 'Login SuccessFully!');
//        }
//    }

    public function postAccountDeletion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_number' => 'required',
            'country_code' => 'required',
            'password' => 'required',
            'full_number' => 'nullable',
            'roles' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $settings = request()->get('general_settings');

        $roles = $request->get('roles');
        $country_code = $request->get('country_code');
        $contact_number = $request->get('contact_number');
        $password = Hash::make($request->get('password'));

        $user = User::where('contact_number', $contact_number)
            ->where('country_code', $country_code)
            ->whereNull('deleted_at')->first();

        $provider = Provider::where('contact_number', $contact_number)
            ->where('country_code', $country_code)
            ->where('provider_type', $roles)
            ->whereNull('deleted_at')->first();

        if ($user != null && Hash::check($request->get('password'), $user->password)) {
            $user->contact_number = $contact_number;
            $user->country_code = $country_code;
            $user->status = 1;
            $user->save();
            Auth::guard('user')->login($user);
            return redirect()->route('get:account:deletion:profile')->with('success', 'Login Successfully!');
        } elseif ($provider != null && Hash::check($request->get('password'), $provider->password)) {
            $provider->contact_number = $contact_number;
            $provider->country_code = $country_code;
            $provider->provider_type = $roles;
            $provider->status = 1;
            $provider->save();
            Auth::guard('on_demand')->login($provider);
            return redirect()->route('get:account:deletion:profile')->with('success', 'Login Successfully!');
        } else {
            return redirect()->route('get:account:deletion:login')->with('error', 'Your account is not registered with us, please try again later.');
        }
    }
    // get AccountDeletionVerification
    public function getAccountDeletionVerification(Request $request) {
        $guard = "";
        if (Auth::guard('user')->check()){
            $guard = 'user';
        }
        elseif (Auth::guard('on_demand')->check()) {
            $guard = 'on_demand';
        }
        else {
            Auth::logout();
            return redirect()->route('get:account:deletion:login')->with('error','Something went wrong');
        }
        return view('account_deletion.verification',compact('guard'));
    }

    // store AccountDeletionVerification
    public function postAccountDeletionVerification(Request $request) {
        $guard = "";
        if (Auth::guard('user')->check()){
            $guard = 'user';
            $user_details = User::query()->where('id',Auth::guard($guard)->id())->whereNull('deleted_at')->first();

            $get_token = UserVerification::query()->where('user_id', "=", $user_details->id)->first();
        }
        elseif (Auth::guard('on_demand')->check()) {
            $guard = 'on_demand';
            $user_details = Provider::query()->where('id',Auth::guard($guard)->id())
                ->where('provider_type','=',3)
                ->whereNull('deleted_at')->first();

            $get_token = ProviderVerification::query()->where('provider_id', "=", $user_details->id)->first();
        }
//
        else {
            Auth::logout();
            return redirect()->route('get:account:deletion:login')->with('error','Something went wrong');
        }

        $otp = $request->get('otp_1').$request->get('otp_2').$request->get('otp_3').$request->get('otp_4');
        $settings = request()->get('general_settings');
        if ($settings->is_otp_verification == 0){
            if ($otp == "1234") {
                $user_details->verified_at = date('Y-m-d H:i:s');
                $user_details->save();

                return redirect()->route('get:account:deletion:profile');
            } else {
                return redirect()->back()->with('error','Invalid OTP');
            }
        }
        try {
            if ($get_token != Null) {
                if($guard == "user"){
                    if ($otp == trim($get_token->token)) {
                        $user_details->verified_at = date('Y-m-d H:i:s');
                        $user_details->save();
                        UserVerification::where('user_id', $user_details->id)->delete();
                        return redirect()->route('get:account:deletion:profile');
                    } else {
                        return redirect()->back()->with('error','Invalid OTP');
                    }
                } else {
                    if ($otp == trim($get_token->token)) {
                        $user_details->verified_at = date('Y-m-d H:i:s');
                        $user_details->save();
                        ProviderVerification::where('provider_id', $user_details->id)->delete();
                        return redirect()->route('get:account:deletion:profile');
                    } else {
                        return redirect()->back()->with('error','Invalid OTP');
                    }

                }
            } else {
                return redirect()->back()->with('error','Invalid OTP');
            }
        } catch (\Exception $e) {
            Log::info($e);
            Session::flash('error', 'Something went wrong');
            return view('account_deletion.login');
//            return redirect()->route('get:account:deletion:login')->with('error','Something went wrong');
        }
    }

    // get AccountDeletion Resend VerificationCode
    public function getAccountDeletionRensendVerificationCode(Request $request) {
        $settings = request()->get('general_settings');
        if ($settings->is_otp_verification != 0) {
            $guard = ""; $id = 0;
            if (Auth::guard('user')->check()) {
                $guard = 'user';
                $id = Auth::guard($guard)->id();

                if ($id > 0) {
                    (new TokenClassApi())->sendUserSmsVerification($id);
                }

                return redirect()->back()->with('success', 'Otp sent successfully!');
            }
            elseif (Auth::guard('on_demand')->check()) {
                $guard = 'on_demand';
                $id = Auth::guard($guard)->id();

                if ($id > 0) {
                    (new TokenClassApi())->sendProviderSmsVerification($id);
                }

                return redirect()->back()->with('success', 'Otp sent successfully!');
            } else {
                Auth::logout();
                return redirect()->route('get:account:deletion:login')->with('error', 'Something went wrong');
            }
        }

        return redirect()->back()->with('success', 'Otp sent successfully!');
    }

    // get Account Deletion Logout
    public function getAccountDeletionLogout($guard) {
        Auth::guard($guard)->logout();
        return redirect()->route('get:account:deletion:login');
    }

    // get Account Deletion Profile
    public function getAccountDeletionProfile() {
        $guard = "";
        if (Auth::guard('user')->check()){
            $guard = 'user';
            $user_details = User::query()->where('id',Auth::guard($guard)->id())->whereNull('deleted_at')->first();
        }
        elseif (Auth::guard('on_demand')->check()) {
            $guard = 'on_demand';
            $user_details = Provider::query()->where('id',Auth::guard($guard)->id())
                ->where('provider_type','=',3)
                ->whereNull('deleted_at')->first();
        }

        else {
            Auth::logout();
            return redirect()->route('get:account:deletion:login')->with('error','Something went wrong');
        }

        return view('account_deletion.profile',compact('guard','user_details'));
    }

    // store Account Deletion Profile
    public function postAccountDeletionDeleteAccount(Request $request) {
        $guard=$request->get('guard');
        $id=$request->get('id');

        if ($guard == "user") {
            User::query()->whereKey($id)->delete();
        } else {
                Provider::query()->whereKey($id)->delete();
            }
        Auth::logout();
        return redirect()->route('get:account:deletion:login')->with('success','Account Delete Successfully!');
    }

    //social Account Deletion Login
    public function redirectToGoogle($guards,$provider){
        if (Session::has('previous_url')){
            Session::forget('previous_url');
        }
        Session::put('previous_url',url()->previous());

        return Socialite::driver($provider)->with(['state' => $guards])->redirect();
    }

    //social Account Deletion Login callback function
    public function handleGoogleCallback(Request $request, $provider){
//        try {
//            $user = Socialite::driver($provider)->user();
//        } catch (InvalidStateException $e) {
//            $user = Socialite::driver($provider)->stateless()->user();
//        }
//        $guards = $request->get("state");
//        \Log::info("=====================Guard Check================");
//        \Log::info($guards);
//
//        try {
//            if($guards == "on_demand") {
//                $userstore = Provider::query()->where('login_id', $user->id)->first();
//            }
//            elseif($guards == "user") {
//                $userstore = User::query()->where('login_id',$user->id)->first();
//            }
//            else {
//                return redirect()->route("get:account:deletion:login");
//            }
//            if ($userstore != null){
//                Auth::guard($guards)->login($userstore);
//                if (Session::has('previous_url')) {
//                    $previous_url = Session::get('previous_url');
//                    Session::forget('previous_url');
//                } else {
//                    $previous_url = "";
//                }
//                if ($previous_url == route('get:account:deletion:login')){
//                    return redirect()->route('get:account:deletion:profile');
//                }
//
//                return redirect()->route('get:provider-admin:dashboard');
//            } else {
//                if ($guards == "on_demand") {
//                    $userstore = new Provider();
//                    $userstore->provider_type = 3;
//                    $userstore->web_verified_at = now();
//                } else {
//                    $userstore = new User();
//                }
//            }
//
//            $userstore->first_name = $user->name;
//            $userstore->email = $user->email;
//            $userstore->login_id = $user->id;
//            $userstore->is_register = 1;
//            $userstore->verified_at = now();
//            $userstore->login_type = $provider;
//            $userstore->status = 0;
//            $userstore->save();
//
//            Auth::guard($guards)->login($userstore);
////            if ($guards == "on_demand"){
////                $register_service = new ProviderServices();
////                $register_service->provider_id = $userstore->id;
////                $register_service->service_cat_id = $this->homecleaning;
////                $register_service->current_status = 1;
////                $register_service->is_sponsor = 0;
////                $register_service->status = 1;
////                $register_service->save();
////            }
//
//            if (Session::has('previous_url')) {
//                $previous_url = Session::get('previous_url');
//                Session::forget('previous_url');
//            } else {
//                $previous_url = "";
//            }
//            if ($previous_url == route('get:account:deletion:login')){
//                return redirect()->route('get:account:deletion:profile');
//            }
//
//            return redirect()->route('get:provider-admin:dashboard');
//        } catch (\Exception $e) {
//            \Log::info($e->getMessage());
//        }
        $guards = $request->get("state");
        \Log::info($guards);
//        $g = Auth::guard("driver")->user();
        if($guards == "user"){
            return $this->handleUserCallback($provider);
        }elseif($guards == "on_demand"){
            return $this->handleProviderCallback($provider);
        }
    }

    /* -----------------------------------End For Play-Store, App-Store Upload account_deletion---------------------- */
    public function handleProviderCallback($provider){
        try{
            try{
                try{
                    $user = Socialite::driver($provider)->user();
                }catch (InvalidStateException $e){
                    $user = Socialite::driver($provider)->stateless()->user();
                }
            }catch (\Exception $e){
                Session::flash('error', 'Something went to wrong!');
                return redirect()->route("get:provider-admin:login");
            }
            $login_type = $provider;
            $login_id = $user->id;
            $contact_number = $user->email."";
            $full_name = $user->name."";
            $profile_image = "";
            $gender = "";

            $provider_details = Provider::query()->where('login_type','=', $login_type)->where('login_id','=', $login_id)->where('provider_type','=',3)->whereNull('deleted_at')->first();
            $previous_url = request()->session()->get("login_previous_url");
            if ($previous_url == route("get:account:deletion:login")) {
                if($provider_details == Null){
                    Session::flash("error", "Your account is not Registered with us. Please try again later!");
                    return redirect()->route("get:account:deletion:login");
                }
                Auth::guard('on_demand')->login($provider_details, true);
                return redirect()->route("get:account:deletion:profile");
            }

            if ($provider_details == Null) {
                $contact_number = $contact_number;
                $is_update = 0;
                $provider_id = 0;
                if ($contact_number != Null){
                    if (!is_numeric($contact_number)) {
                        $check_email = Provider::query()->where('email', $contact_number)->where('provider_type','=',3)->whereNull('deleted_at')->first();
                        if ($check_email != Null){
                            if ($check_email->login_type != "email"){
                                Session::flash('error', 'Email Already Exist!');
                                return redirect()->route("get:provider-admin:login");
                            } else {
                                $is_update = 1;
                                $provider_id = $check_email->id;
                            }
                        }
                    }
                }

                $is_new_or_not = 0;
                if ($is_update == 1){
                    $provider_details = Provider::query()->where('id','=', $provider_id)->where('provider_type','=',3)->whereNull('deleted_at')->first();
                    if ($provider_details == Null){
                        $provider_details = new Provider();
                        $is_new_or_not = 1;
                    }
                    if ($provider_details->status == 2) {
                        Session::flash('error', 'Your account is currently blocked, so not authorised to allow any activity!');
                        return redirect()->route("get:provider-admin:login");
                    }
                } else{
                    $provider_details = new Provider();
                    $is_new_or_not = 1;
                }

                //$provider_details = new Provider();
                $provider_details->provider_type = 3;
                $provider_details->is_register = 1;
                $provider_details->login_type = $login_type;
                $provider_details->login_id = $login_id;
//                $provider_details->web_verified_at = date('Y-m-d H:i:s');
                //in case of account created in provider admin via social login then also verify that provider for app also
                $provider_details->verified_at = date('Y-m-d H:i:s');
                $provider_details->save();

                if ($is_new_or_not == 1) {
                    $provider_details->status = 3;
                    if(trim($full_name) != Null) {
                        $provider_details->first_name = ucwords(strtolower(trim($full_name)));
                    }
                    if ($contact_number != Null) {
                        if (is_numeric($contact_number)) {
                            $provider_details->contact_number = $contact_number;
                        } else {
                            $provider_details->email = $contact_number;
                        }
                    }
                    if ($gender != Null) {
                        if ($gender == "male") {
                            $provider_details->gender = 1;
                        } elseif ($gender == "female") {
                            $provider_details->gender = 2;
                        } else {
                            $provider_details->gender = 0;
                        }
                    }
                    $provider_details->save();
                    /*if (request()->get("general_settings") != Null){
                        if (request()->get("general_settings")->send_mail == 1) {
                            if($provider_details->email != null){
                                $notificationClass = new NotificationClass();
                                try {
                                    $mail_type = "provider_signup";
                                    $to_mail = $provider_details->email;
                                    $subject = "Welcome to " . request()->get("general_settings")->mail_site_name;
                                    $disp_data = array("##provider_name##" => $provider_details->first_name);
                                    $mail_return_data = $notificationClass->sendMail($subject, $to_mail, $mail_type, $disp_data);
                                } catch (\Exception $e) {}
                            }
                        }
                    }*/
                }
            } else {
                if ($provider_details->status == 2) {
                    Session::flash('error', 'Your account is currently blocked, so not authorised to allow any activity!');
                    return redirect()->route("get:provider-admin:login");
                }
                $provider_details->verified_at = date('Y-m-d H:i:s');
                $provider_details->save();
            }
            Auth::guard('on_demand')->login($provider_details, true);
            if(Auth::guard("on_demand")->check()){
//                $verified = Auth::guard('on_demand')->user()->web_verified_at;
//                if ($verified == Null) {
//                    return redirect()->route('get:provider-admin:not_verified');
//                }
                if (Auth::guard('on_demand')->user()->status == 3) {
                    return redirect()->route('get:provider-admin:service-register')->with("success", "Provider Admin Register Successfully.");
                }
                return redirect()->route('get:provider-admin:dashboard')->with("success", "Provider Admin Login Successfully.");
            }else {
                Auth::logout();
                Session::flash('error', 'Something went to wrong!');
                return redirect()->route("get:provider-admin:login");
            }
        }catch (\Exception $e){
            Session::flash('error', 'Something went to wrong!');
            return redirect()->route('get:provider-admin:login');
        }
    }





}
