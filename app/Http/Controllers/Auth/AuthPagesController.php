<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthPagesController extends Controller
{
    public function __construct()
    {
        $urlPrevious = url()->previous();
        $urlBase = url()->to('/');

        if(($urlPrevious != $urlBase) && (substr($urlPrevious, 0, strlen($urlBase)) === $urlBase)) {
            session()->put('url.intended', $urlPrevious);
        }
    }

    public function getAdminLogin()
    {
        Auth::guard('on_demand')->logout();
        /*if (Auth::guard() == "admin") {
            return redirect()->route('get:admin:dashboard');
        }*/
        if(Auth::guard('admin')->check())
        {
            return redirect()->route('get:admin:dashboard');
        }
        return view('admin.auth.super_admin.login');
    }

    public function getProvideLogin()
    {
        Auth::guard('admin')->logout();
        Auth::guard('on_demand')->logout();
        /*if (Auth::guard() == "on_demand") {
            return redirect()->route('get:provider-admin:dashboard');
        }*/
        if(Auth::guard('on_demand')->check())
        {
            return redirect()->route('get:provider-admin:dashboard');
        }
        return view('admin.auth.service_provider.provider-login');
    }

    public function getProvideRegister()
    {
        Auth::guard('admin')->logout();
        Auth::guard('on_demand')->logout();
        return view('admin.auth.service_provider.provider-register');
    }

}
