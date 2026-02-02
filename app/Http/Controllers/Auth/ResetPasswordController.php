<?php

namespace App\Http\Controllers\Auth;

use App\Classes\AdminClass;
use App\Http\Requests\ChangeAdminPasswordRequest;
use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class ResetPasswordController extends Controller
{
//    use ResetsPasswords;

    private $adminClass;

    protected $redirectTo = '/home';

    public function __construct(AdminClass $adminClass)
    {
        //$this->middleware('guest');
        $this->adminClass = $adminClass;
    }

    //admin change password
    public function getAdminChangePassword(Request $request)
    {
        $view = view('admin.auth.change_password');
        if ($request->ajax()) {
            $view = $view->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return $view;
    }

    public function postAdminChangePassword(ChangeAdminPasswordRequest $request)
    {
        $old_password = $request->get('old_password');
        $new_password = $request->get('new_password');

        $admin = Admin::where('id', Auth::guard('admin')->user()->id)->first();
        if ($admin != Null) {
            if (Hash::check($old_password, $admin->password)) {
                $admin->password = Hash::make($new_password);
                $admin->save();
                Session::flash('success', 'Admin password change successfully!');
                return redirect()->route('get:admin:change_password');
            }
            Session::flash('error', 'old password enter wrong!');
            return redirect()->back();
        }
        Session::flash('error', 'Admin Details Not Found!');
        return redirect()->back();
    }

    //provider change password
    public function getProviderAdminChangePassword(Request $request)
    {
        if (Auth::guard('on_demand')->user()->login_type != "email") {
            Session::flash('error', 'you are login with social parameter!');
            return redirect()->back();
        }
        if ($request->ajax()) {
            $view = view('admin.auth.change_password')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.auth.change_password');
    }

    public function postProviderAdminChangePassword(ChangeAdminPasswordRequest $request)
    {
        $old_password = $request->get('old_password');
        $new_password = $request->get('new_password');
        if (Auth::guard("on_demand")->check()) {
            $admin = Provider::where('id', Auth::guard('on_demand')->user()->id)->whereNull('providers.deleted_at')->first();
            if ($admin != Null) {
                if ($admin->login_type != "email") {
                    Session::flash('error', 'you are login with social parameter!');
                    return redirect()->back();
                }
                if (Hash::check($old_password, $admin->password)) {
                    $admin->password = Hash::make($new_password);
                    $admin->save();
                    Session::flash('success', 'Password change successfully!');
                    return redirect()->route('get:provider-admin:change_password');
                }
                Session::flash('error', 'old password enter wrong!');
                return redirect()->back();
            }
            Session::flash('error', 'Store Provider Details Not Found!');
            return redirect()->back();
        } else {
            Session::flash('error', 'Something went to wrong!');
            return redirect()->back();
        }
    }

    //dispatcher change password
    public function getDispatcherAdminChangePassword(Request $request)
    {
        if ($request->ajax()) {
            $view = view('admin.auth.change_password')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.auth.change_password');
    }

    //account change password
    public function getAccountAdminChangePassword(Request $request)
    {
        if ($request->ajax()) {
            $view = view('admin.auth.change_password')->renderSections();
            return $this->adminClass->renderingResponce($view);
        }
        return view('admin.auth.change_password');
    }

    public function postAccountAdminChangePassword(ChangeAdminPasswordRequest $request)
    {
        $old_password = $request->get('old_password');
        $new_password = $request->get('new_password');

        $admin = Admin::where('id', Auth::guard('admin')->user()->id)->first();
        if ($admin != Null) {
            if (Hash::check($old_password, $admin->password)) {
                $admin->password = Hash::make($new_password);
                $admin->save();
                Session::flash('success', 'Billing Admin password change successfully!');
                return redirect()->route('get:account:change_password');
            }
            Session::flash('error', 'old password enter wrong!');
            return redirect()->back();
        }
        Session::flash('error', 'Billing Admin Details Not Found!');
        return redirect()->back();
    }
    public function postUserChangePassword(Request $request)
    {
        $old_password = $request->get('old_password');
        $new_password = $request->get('new_password');
        $confirm_password = $request->get('confirm_password');

        $user = User::query()->where('id', Auth::guard('user')->user()->id)->whereNull('users.deleted_at')->first();
        if ($user != Null) {
            if (Hash::check($old_password, $user->password)) {
                if ($new_password == $confirm_password){
                    $user->password = Hash::make($new_password);
                    $user->save();
                    Session::flash('success', 'password change successfully!');
                    return redirect()->back();
                }
                Session::flash('error', 'password and confirm password not match!');
                return redirect()->back();
            }
            Session::flash('error', 'old password enter wrong!');
            return redirect()->back();
        }
        Session::flash('error', 'User Details Not Found!');
        return redirect()->back();
    }
}
