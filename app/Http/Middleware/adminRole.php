<?php

namespace App\Http\Middleware;

use App\Models\AdminCategoryPermission;
use App\Models\AdminModule;
use App\Models\AdminPermission;
use App\Models\ServiceCategory;
use Closure;
use Illuminate\Support\Facades\Auth;

class adminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public $admin_role;
    public $admin_id;
    public  $admin_menu_list;
    public  $admin_main_menu_list;
    public  $is_restrict_admin;
    public $is_all_service = 0;
    public $admin_category_id_list = [];

    public function handle($request, Closure $next)
    {
        $this->admin_role = Auth::guard("admin")->user()->roles;
        $this->admin_id = Auth::guard("admin")->user()->id;
        $this->is_restrict_admin = Auth::guard("admin")->user()->is_restrict_admin;
        $is_false_redirect = 0;

        if($this->admin_role == 1 || $this->admin_role == 2 ||$this->admin_role == 3 )
        {
            $this->admin_menu_list =AdminModule::query()->select(
                'admin_module.id','admin_module.parent_id','admin_module.name','admin_module.module_name','admin_module.route_path','admin_module.image')
                ->where('admin_module.status','=',1)
                ->where('admin_module.parent_id','=',0)
                ->orderBy('admin_module.seq','asc')
                ->get();
//            dd($this->admin_menu_list);
            foreach ($this->admin_menu_list as $single_parent)
            {

                $child_list = AdminModule::query()->select(
                    'admin_module.id','admin_module.parent_id','admin_module.name','admin_module.module_name','admin_module.route_path','admin_module.route_path_arr','admin_module.image')
                    ->where('admin_module.status','=',1)
                    ->where('admin_module.parent_id','=',$single_parent->id)
//                  ->orderBy('admin_module.seq','asc')
                    ->get();

                $this->admin_main_menu_list[]= [
                    'parent_menu' => $single_parent,
                    'child_menu' => $child_list,
                ];
            }
        }
        else{
            $this->is_all_service = 1;
            $get_category_wise_list = AdminCategoryPermission::query()->select('admin_category_permission.id','admin_category_permission.admin_id', 'admin_category_permission.module_id','admin_category_permission.permission', 'admin_module.id','admin_module.parent_id', 'admin_module.name','admin_module.module_name','admin_module.route_path','admin_module.image')
                ->leftJoin('admin_module','admin_module.id','admin_category_permission.module_id')
                ->where('admin_module.status','=',1)
                ->whereRaw("find_in_set('1',admin_category_permission.permission)")
                ->where('admin_module.parent_id','=',0)
                ->where('admin_category_permission.admin_id',$this->admin_id)
                ->groupBy('admin_category_permission.module_id')
                ->orderBy('admin_module.seq','asc')
                ->get()->toArray();
            $admin_menu_list = AdminPermission::query()->select('admin_permission.id','admin_permission.admin_id', 'admin_permission.module_id','admin_permission.permission', 'admin_module.id','admin_module.parent_id', 'admin_module.name','admin_module.module_name','admin_module.route_path','admin_module.image')
                ->leftJoin('admin_module','admin_module.id','admin_permission.module_id')
                ->where('admin_module.status','=',1)
                ->whereRaw("find_in_set('1',admin_permission.permission)")
                ->where('admin_module.parent_id','=',0)
                ->where('admin_permission.admin_id',$this->admin_id)
                ->orderBy('admin_module.seq','asc')
                ->get()->toArray();

            $this->admin_menu_list = [];
            $this->admin_menu_list = array_merge($this->admin_menu_list, $get_category_wise_list);
            $this->admin_menu_list = array_merge($this->admin_menu_list, $admin_menu_list);
            foreach ($this->admin_menu_list as $single_parent) {
                $child_list =AdminPermission::query()->select('admin_module.id','admin_module.parent_id','admin_module.name', 'admin_module.module_name','admin_module.route_path','admin_module.route_path_arr','admin_module.image')
                    ->leftJoin('admin_module','admin_module.id','admin_permission.module_id')
                    ->where('admin_module.status','=',1)
                    ->whereRaw("find_in_set('1',admin_permission.permission)")
                    ->where('admin_module.parent_id','=',$single_parent['id'])
                    ->where('admin_permission.admin_id',$this->admin_id)
                    ->orderBy('admin_module.seq','asc')
                    ->get();
                $this->admin_main_menu_list[]= [
                    'parent_menu' => $single_parent,
                    'child_menu' => $child_list,
                ];
            }
        }
//        dd($this->admin_main_menu_list);

        if ($this->is_all_service == 1){
            $second_seg = request()->segment(2);
            $third_seg = request()->segment(3);
            $is_next_redirect = 0;
            if (strtolower($second_seg) == "transport"){
                if (request()->routeIs("get:admin:transport_service_list") == true) {
                    $get_admin_module = AdminModule::query()->where('route_path', '=', "get:admin:transport_service_list")->first();
                    if ($get_admin_module != null) {
                        $get_admin_category_list = AdminCategoryPermission::query()->where('admin_id', '=', $this->admin_id)->where('module_id', '=', $get_admin_module->id)->get();
                        $this->admin_category_id_list = $get_admin_category_list->pluck("service_cat_id")->toArray();
                    }
                } else {
                    $is_next_redirect = 1;
                }
            }
            elseif (strtolower($second_seg) == "store") {
                if (request()->routeIs("get:admin:store_service_list") == true){
                    $get_admin_module = AdminModule::query()->where('route_path','=',"get:admin:store_service_list")->first();
                    if ($get_admin_module != null) {
                        $get_admin_category_list = AdminCategoryPermission::query()->where('admin_id', '=', $this->admin_id)->where('module_id', '=', $get_admin_module->id)->get();
                        $this->admin_category_id_list = $get_admin_category_list->pluck("service_cat_id")->toArray();
                    }
                }  else {
                    $is_next_redirect = 1;
                }
            }
            elseif (strtolower($second_seg) == "provider-services"){
                if (request()->routeIs("get:admin:other_service_list") == true){
                    $get_admin_module = AdminModule::query()->where('route_path','=',"get:admin:other_service_list")->first();
                    if ($get_admin_module != null) {
                        $get_admin_category_list = AdminCategoryPermission::query()->where('admin_id', '=', $this->admin_id)->where('module_id', '=', $get_admin_module->id)->get();
                        $this->admin_category_id_list = $get_admin_category_list->pluck("service_cat_id")->toArray();
                    }
                } else {
                    $is_next_redirect = 1;
                }
            }
            else{
                $sec_last= request()->segment(count(request()->segments())-1);
                $last= request()->segment(count(request()->segments()));
                $match_url = $sec_last."/".$last;
                if($last !="dashboard") {
                    $module_id = AdminModule::query()->where('match_url','=',$match_url)->first();
                    if($module_id!=null) {
                        $mod_id = $module_id->id;
                        $is_allow = AdminPermission::query()->where('admin_id','=',$this->admin_id)->where('module_id','=',$mod_id)->first();
                        if($is_allow == NULL) {
                            $is_cat_allow = AdminCategoryPermission::query()->where('admin_id','=',$this->admin_id)->where('module_id','=',$mod_id)->first();
                            if($is_cat_allow == NULL) {
                                $is_false_redirect = 1;
                                //return redirect('/admin/dashboard')->with('error', "you don't have permission to access this module");
                            }
                        }
                    }
                } else {
                    $this->admin_category_id_list = [];
                    $get_admin_category_list = AdminCategoryPermission::query()->where('admin_id', '=', $this->admin_id)->get();
                    $this->admin_category_id_list = array_merge($this->admin_category_id_list, $get_admin_category_list->pluck("service_cat_id")->toArray());
                    $module_id = AdminModule::query()->where('module_category_type','=',6)->first();
                    if ($module_id != Null){
                        $is_coupon = AdminPermission::query()->where('admin_id','=',$this->admin_id)->where('module_id','=',$module_id->id)->first();
                        if ($is_coupon != Null){
                            $this->admin_category_id_list = array_merge($this->admin_category_id_list, [3]);
                        }
                    }
                }
            }
            if ($is_next_redirect == 1){
                $url_path = request()->path();
                $get_module_detail = AdminModule::query()->where('match_url','=',$url_path)->first();
                if ($get_module_detail != Null){
                    $is_allow = AdminPermission::query()->where('admin_id', '=', $this->admin_id)->where('module_id', '=', $get_module_detail->id)->first();
                    if($is_allow == NULL) {
                        $is_false_redirect = 1;
                        //return redirect('/admin/dashboard')->with('error', "you don't have permission to access this module");
                    }
                } else {
                    $check_service_category = ServiceCategory::query()->where("slug", "=", $third_seg)->first();
                    if ($check_service_category != Null){
                        $is_allow = AdminCategoryPermission::query()->where('service_cat_id', '=', $check_service_category->id)->where('admin_id', '=', $this->admin_id)->first();
                        if($is_allow == NULL) {
                            $is_false_redirect = 1;
                            //return redirect('/admin/dashboard')->with('error', "you don't have permission to access this module");
                        }
                    }
                }
            }
            $this->admin_category_id_list = [];
            $get_admin_category_list = AdminCategoryPermission::query()->where('admin_id', '=', $this->admin_id)->get();
            $this->admin_category_id_list = array_merge($this->admin_category_id_list, $get_admin_category_list->pluck("service_cat_id")->toArray());
        }


        if ($is_false_redirect == 1){
            $this->admin_category_id_list = [];
            $get_admin_category_list = AdminCategoryPermission::query()->where('admin_id', '=', $this->admin_id)->get();
            $this->admin_category_id_list = array_merge($this->admin_category_id_list, $get_admin_category_list->pluck("service_cat_id")->toArray());
            $module_id = AdminModule::query()->where('module_category_type','=',6)->first();
            if ($module_id != Null){
                $is_coupon = AdminPermission::query()->where('admin_id','=',$this->admin_id)->where('module_id','=',$module_id->id)->first();
                if ($is_coupon != Null) {
                    $this->admin_category_id_list = array_merge($this->admin_category_id_list, [3]);
                }
            }
        }

        $request->attributes->add([
            'is_restrict_admin' => $this->is_restrict_admin,
            'is_all_service' => $this->is_all_service,
            'admin_category_id_list' => $this->admin_category_id_list,
        ]);
        view()->composer('*',function($view) {
            $view->with('admin_role', $this->admin_role);
            $view->with('admin_id', $this->admin_id);
            $view->with('admin_main_menu_list', $this->admin_main_menu_list);
            $view->with('is_all_service', $this->is_all_service);
            $view->with('admin_category_id_list', $this->admin_category_id_list);
        });
        if ($is_false_redirect == 1){
//            return redirect('/admin/dashboard')->with('error', "You don't have permission to access this module");
        }
        return $next($request);
    }
}

