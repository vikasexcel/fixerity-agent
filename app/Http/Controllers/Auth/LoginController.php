<?php

namespace App\Http\Controllers\Auth;

use App\Classes\TokenClassApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactLoginRequest;
use App\Http\Requests\EmailLoginRequest;
use App\Models\Admin;
use App\Models\Provider;
use App\Models\ProviderServices;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use http\Exception;
//use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
//    /*
//    |--------------------------------------------------------------------------
//    | Login Controller
//    |--------------------------------------------------------------------------
//    |
//    | This controller handles authenticating users for the application and
//    | redirecting them to your home screen. The controller uses a trait
//    | to conveniently provide its functionality to your applications.
//    |
//    */
//
//    use AuthenticatesUsers;

//
//    /**
//     * Where to redirect users after login.
//     *
//     * @var string
//     */
//    protected $redirectTo = '/home';
//
//    /**
//     * Create a new controller instance.
//     *
//     * @return void
//     */

    private $store_service_id_array;

    public function __construct()
    {
        $this->store_service_id_array = [5, 6, 7, 8, 9, 10];
        $this->middleware('guest')->except('logout');
        $this->middleware('guest:admin')->except('logout');
    }

    //post admin login
    public function postSuperAdminLogin(EmailLoginRequest $request)
    {
        Auth::guard('admin')->logout();
        Auth::guard('on_demand')->logout();
        $roles = $request->get('roles');
        $super_admin = Admin::where('email', $request->get('email'))->first();

        if ($super_admin != Null) {
            if ($super_admin->roles == $roles || $super_admin->roles == "4") {
                if (Hash::check($request->get('password'), $super_admin->password)) {
                    if ($super_admin->roles == 1 || $super_admin->roles == 2 || $super_admin->roles == 3 || $super_admin->roles == 4 ) {
                        if ($super_admin->roles == 1 || $super_admin->roles == 4 ) {
                            Auth::logout();

                            if (Auth::guard('admin')->attempt(['email' => $request->get('email'), 'password' => $request->get('password')], $request->get('remember'))) {
                                return redirect()->intended(route('get:admin:dashboard'))->with("success", "Admin Login Successfully.");
//                                dd( redirect()->intended('defaultpage')->getTargetUrl());
//                                return redirect()->route('get:admin:dashboard')->with("success", "Super Admin Login Successfully.");
                            } else {
                                Auth::logout();
                                return redirect()->back()->with("error", "Your email and password was wrong. Please enter right credential.");
                            }
                        } elseif ($super_admin->roles == 2) {
                            Auth::logout();
                            if (Auth::guard('admin')->attempt(['email' => $request->get('email'), 'password' => $request->get('password')], $request->get('remember'))) {
                                return redirect()->intended(route('get:dispatcher:manual_ride_booking'))->with("success", "Dispatcher Admin Login Successfully.");
//                                return redirect()->route('get:dispatcher:manual_ride_booking')->with("success", "Dispatcher Admin Login Successfully.");
                            } else {
                                Auth::logout();
                                return redirect()->back()->with("error", "Your email and password was wrong. Please enter right credential.");
                            }
                        } elseif ($super_admin->roles == 3) {
                            Auth::logout();
                            if (Auth::guard('admin')->attempt(['email' => $request->get('email'), 'password' => $request->get('password')], $request->get('remember'))) {
//                                return redirect()->route('get:account:dashboard')->with("success", "Billing Account Admin Login Successfully.");
                                return redirect()->intended(route('get:account:dashboard'))->with("success", "Billing Account Admin Login Successfully.");
                            } else {
                                Auth::logout();
                                return redirect()->back()->with("error", "Your email and password was wrong. Please enter right credential.");
                            }
                        } else {
                            return redirect()->back()->with("error", "Your email and password was wrong. Please enter right credential.");
                        }
                    } else {
                        return redirect()->back()->with("error", "Your email and password was wrong. Please enter right credential.");
                    }
                } else {
                    Auth::logout();
                    return redirect()->back()->with("error", "Your email and password was wrong. Please enter right credential.");
                }
            } else {
                return redirect()->back()->with("error", "Only Administration are Allowed.");
            }
        } else {
            return redirect()->back()->with("error", "Your email and password was wrong. Please enter right credential.");
        }
    }

    public function postProviderAdminLogin(Request $request)
    {
        $contact_number = $request->get('contact_number');
        $country_code = $request->get('country_code');
        Auth::guard('admin')->logout();
        Auth::guard('on_demand')->logout();
//        $provider_admin = Provider::where('email', $request->get('email'))->first();
        $provider_admin = Provider::query()->where('contact_number', $contact_number)
            ->where('country_code', $country_code)
            ->where('provider_type', '=',3)
            ->whereNull('providers.deleted_at')
            ->first();
//        $provider_admin->web_verified_at = Null;
//        $provider_admin->save();

        if ($provider_admin != Null) {
            if (Hash::check($request->get('password'), $provider_admin->password)) {
                Auth::logout();
                if (Auth::guard('on_demand')->attempt(['contact_number' => $contact_number, 'country_code' => $country_code, 'password' => $request->get('password')], $request->get('remember'))) {

                    $tokenClassApi = new TokenClassApi();
                    $tokenClassApi->sendProviderSmsVerification($provider_admin->id);

                    $verified = Auth::guard('on_demand')->user()->verified_at;
                    if ($verified == Null) {
                        return redirect()->route('get:provider-admin:not_verified');
                    }

                    if (Auth::guard('on_demand')->user()->status == 3) {
                        return redirect()->route('get:provider-admin:service-register')->with("success", "Provider Admin Login Successfully.");
                    }
                    $provider_id = Auth::guard('on_demand')->user()->id;
                    $provider_service = ProviderServices::where('provider_id', $provider_id)->first();
                    if ($provider_service != Null) {
                        $service_category = ServiceCategory::where('id', $provider_service->service_cat_id)->first();
                        if ($service_category == Null) {
                            Auth::logout();
                            return redirect()->back()->with("error", "something went to wrong.");
                        }
                        if (!in_array($service_category->category_type, [3, 4])) {
                            Auth::logout();
                            return redirect()->back()->with("error", "You are not register as on-demand provider.");
                        }
                    }
                    return redirect()->route('get:provider-admin:dashboard')->with("success", "Provider Admin Login Successfully.");
                } else {
                    Auth::logout();
                    return redirect()->back()->with("error", "Your contact or password is wrong. Please enter right credential.");
                }
            } else {
                Auth::logout();
                return redirect()->back()->with("error", "Your contact or password is wrong. Please enter right credential.");
            }
        } else {
            return redirect()->back()->with("error", "Your contact or password is wrong. Please enter right credential.");
        }
    }

    //post logout
    public function logout(Request $request, $guard)
    {
        if ($guard == 'admin') {
            Auth::guard('admin')->logout();
            return redirect()->route('get:admin:login')->with("success", "Admin Logout Successfully.");
        } elseif ($guard == 'on_demand') {
            Auth::guard('on_demand')->logout();
            return redirect()->route('get:provider-admin:login')->with("success", "Provider Logout Successfully.");
        }elseif($guard == "user"){
            Auth::guard('user')->logout();
            return redirect()->route('get:homepage')->with('success',"User Logout Successfully.");
        }else {
            Auth::logout();
            return redirect()->route('get:homepage')->with("success", "User Logout Successfully.");
        }
    }

    //get Admin change password form
    public function getAdminChangePassword()
    {
        return view('admin.auth.change_password');
    }

    //social Login
    public function redirectToProvider($provider)
    {
//        dd($provider);
        return Socialite::driver($provider)->redirect();
    }

    //social loggid user existing check function
    public function findOrCreateUser($user, $provider)
    {
        $authUser = Provider::where('login_id', $user->id)->whereNull('providers.deleted_at')->first();
        if ($authUser) {
            return $authUser;
        }
        $userstore = Provider::where('email', $user->email)->whereNull('providers.deleted_at')->first();
        if ($userstore) {
            return response()->json([
                "status" => 0,
            ]);
        }
        $userstore = new Provider();
        $userstore->name = $user->name;
        $userstore->email = $user->email;
        $userstore->login_id = $user->id;
        $userstore->login_type = $provider;
        $userstore->status = 0;
        $userstore->save();
        return $userstore;
    }

    //social Login callback function
    public function handleProviderCallback($provider)
    {
        try {
            $user = Socialite::driver($provider)->stateless()->user();
            $authUser = $this->findOrCreateUser($user, $provider);
            if ($decoded['status'] = json_decode($authUser) == false) {
                return redirect()->route('get:store-admin:login')->with("error", "already login with this Id. Please login with different Id.");
            }
            Auth::logout();
            return redirect()->route('get:store-admin:dashboard')->with("success", "Store Admin Login Successfully.");

        } catch (Exception $e) {
            return redirect('auth/' . $provider);
        }
    }

    public function redirectToGoogle(){
        \Log::info("redirectToGoogle");
        return Socialite::driver("google")->redirect();
    }
    public function handleGoogleCallback(){

        try {
            $user = Socialite::driver('google')->user();

            $finduser = Provider::query()->where('login_id', $user->id)->first();

            if($finduser <> null){
                Auth::guard('on_demand')->login($finduser);
                return redirect()->route('get:provider-admin:dashboard');
//                return redirect()->route('get:provider-admin:dashboard');
            }else{
                $newUser = new Provider();
                $newUser->first_name = $user->name;
                $newUser->email = $user->email;
                $newUser->login_id = $user->id;
                $newUser->verified_at = Carbon::now();
                $newUser->login_type = "google";
                $newUser->provider_type = 3;
                $newUser->is_register = 1;
                $newUser->avatar = $user->getAvatar();
                $newUser->status = 1;
                $newUser->save();

//                $register_service = new ProviderServices();
//                $register_service->provider_id = $newUser->id;
//                $register_service->service_cat_id = 23;
//                $register_service->current_status = 1;
//                $register_service->is_sponsor = 0;
//                $register_service->status = 1;
//                $register_service->save();
//                $fetchUser = Provider::query()->where('id',$newUser->id)->first()->toArray();

                Auth::guard('on_demand')->login($newUser);
                return redirect()->route('get:provider-admin:dashboard');

//                return redirect()->route('get:provider-admin:dashboard');
            }

        } catch (\Exception $e) {
            \Log::info($e->getMessage());
        }
    }
}
