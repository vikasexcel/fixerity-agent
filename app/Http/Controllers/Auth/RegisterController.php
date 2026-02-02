<?php

namespace App\Http\Controllers\Auth;

use App\Classes\NotificationClass;
use App\Classes\TokenClassApi;
use App\Http\Requests\ProviderRegisterRequest;
use App\Models\OtherServiceProviderDetails;
use App\Models\OtherServiceProviderTimings;
use App\Models\Provider;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;


class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

//    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function postProviderRegister(ProviderRegisterRequest $request)
    {
        Auth::guard('admin')->logout();
        Auth::guard('on_demand')->logout();

        $provider = new Provider();
//      $provider->name = $request->get('name');
        $provider->first_name = $request->get('name');
        $provider->email = $request->get('email');
        $provider->country_code = $request->get('country_code');
        $provider->provider_type = 3;
//        $provider->contact_number = $request->get('full_number');
        $provider->contact_number = $request->get('contact_number');
        $provider->password = Hash::make($request->get('password'));
        $provider->login_type = "email";
        $provider->is_register = 1;
        $provider->gender = $request->get('gender');
        $provider->last_active = date('Y-m-d H:i:s');
        $provider->status = 3;
//        $provider->verified_at = date('Y-m-d H:i:s');
        $provider->verified_at = Null;
        $provider->save();

        $provider_id = $provider->id;
        $get_other_service_provider = OtherServiceProviderDetails::query()->where('provider_id', '=', $provider_id)->first();

        if ($get_other_service_provider == Null) {
            $get_other_service_provider = new OtherServiceProviderDetails();
        }
        $get_other_service_provider->provider_id = $provider_id;
        $get_other_service_provider->save();

        //code for add service time
        $general_settings = request()->get("general_settings");

        Auth::guard('on_demand')->login($provider, false);

        $tokenClassApi = new TokenClassApi();
        $tokenClassApi->sendProviderSmsVerification($provider->id);

        $verified = Auth::guard('on_demand')->user()->verified_at;
        if ($verified == Null) {
            return redirect()->route('get:provider-admin:not_verified');
        }
        if (Auth::guard('on_demand')->user()->status == 3) {
//            dd("dsjsdjf");
            return redirect()->route('get:provider-admin:service-register')->with("success", "Provider Admin Login Successfully.");
        }
        return redirect()->route('get:provider-admin:dashboard')->with("success", "Provider Admin Login Successfully.");
    }
}
