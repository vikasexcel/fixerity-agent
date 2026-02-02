<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $current_url = url()->current();
        if (Auth::guard("admin")->check()) {
            if (strpos($current_url, 'store-admin/register') == false && strpos($current_url, 'store-admin/login') == false && strpos($current_url, 'provider-admin/register') == false && strpos($current_url, 'provider-admin/login') == false) {
                return redirect()->route('get:admin:dashboard');
            }
        }
        elseif (Auth::guard("on_demand")->check()) {
            if (strpos($current_url, '/admin/login') == false && strpos($current_url, 'store-admin/register') == false && strpos($current_url, 'store-admin/login') == false) {
                return redirect('get:provider-admin:dashboard');
            }
        }
//        elseif (Auth::guard($guard)->check()) {
//            return redirect('/home');
//        }

        return $next($request);
    }
}
