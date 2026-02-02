<?php

namespace app\Models;

use App\Classes\NotificationClass;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function generateAccessToken($id)
    {
        $this->access_token = random_int(1, 99) . date('siHYdm') . random_int(1, 99);
        $this->save();
        return $this->access_token;
    }

    public function InviteCode($id, $name)
    {
//        $name = strtoupper(substr($name, 0, 3));
        $name = strtoupper(substr(str_replace(' ','',$name), 0, 3));
        $name = strtoupper(substr(str_replace(' ','',$name), 0));
        $id = (2) * ($id);
        $this->invite_code = $name . $id;
        $this->save();
        return $this->invite_code;
    }

    public static function String2Stars($string){
        if(\Request::get('is_restrict_admin') != 1)
        {
            return $string;
        }
        $cha = strlen($string) - 4;
        $first = 0;
        $last = $cha; $rep = '*';
        $begin = substr($string, 0, $first);
        $middle = str_repeat($rep, strlen(substr($string, $first, $last)));
        $end = substr($string, $last);
        $stars = $begin . $middle . $end;
        return $stars;
    }

    public static function ContactNumber2Stars($string)
    {
        if(\Request::get('is_restrict_admin') != 1)
        {
            return $string;
        }
//        $coverWith = function($string, $char, $number) {
//            return substr($string, 0, -4) . str_repeat($char, 4);
//        };
        //return $string;
        $cha = strlen($string) - 4;
        $first = 0;
        $last = $cha;
        $rep = '*';
        $begin = substr($string, 0, $first);
        $middle = str_repeat($rep, strlen(substr($string, $first, $last)));
        $end = substr($string, $last);
        $stars = $begin . $middle . $end;
        return $stars;
    }

    public static function Email2Stars($string)
    {
        if(\Request::get('is_restrict_admin') != 1)
        {
            return $string;
        }

        $first = 2;
        $f_char = strlen($string) - $first;
        //return substr($string, 0, -$f_char) . str_repeat("*", $f_char);
        $first_string = substr($string, $first);
        $last_char = strlen($first_string) - 4;
        $last = $last_char;
        $rep = '*';
        $begin = substr($string, 0, -$f_char);
//        $begin = substr($string, 0, $first);
        $middle = str_repeat($rep, strlen(substr($string, $first, $last)));
        $end = substr($first_string, $last);
        $stars = $begin . $middle . $end;
        return $stars;
    }

    public static function AdminLogout()
    {
//        Auth::logout();
        Auth::guard('admin')->logout();
    }

    public static function UserLogout()
    {
//        Auth::logout();
        Auth::guard('user')->logout();
    }
    public static function navCartCount()
    {
        if (Auth::guard("user")->check()) {
            $cart_count = UserOrderCart::where('user_id',Auth::guard("user")->user()->id)->where('order_add_by',1)->count();
        }
        else{
            $cart_count = 0;
        }
        return $cart_count;
    }

    public static function getServiceList($for = 0){

            $service_category_list =  ServiceCategory::query()->where('status',1)->where('id' ,'!=',3)->get();
            $service_list ="";
            $ride_service_list = '';
            $delivery_service_list = '';
            $courier_service_list = '';
            $other_service_list = '';
            $coupon_service_list = "";
            $coupon_service_list = '<li class="dropdown-item ">
                                        <a href="'.route('get:coupon-list').'"  title="Coupon Lists" >Coupon Deal</a>
                                    </li>';
            $menu_title  = "Service";
            if($for == 1)
            {
                $menu_title = "Product";
            }
            foreach ($service_category_list as $singel_service)
            {
                if($singel_service->category_type == 1 || $singel_service->category_type == 5 ){
                    if($singel_service->slug != "bike-rental" && $singel_service->slug != "taxi-rental" )
                    {
                        $ride_service_list .= '<li class="dropdown-item " ><a href="'.route("get:transport-service-booking",[$singel_service->slug]) .'" title="'.$singel_service->name.'" >'.ucwords(strtolower($singel_service->name)).'</a></li>';
                    }
                } elseif($singel_service->category_type == 2) {
                    $delivery_service_list .= '<li class="dropdown-item" ><a href="'.route('get:storehomepage',[$singel_service->slug]).'" title="'.$singel_service->name.'" >'.ucwords(strtolower($singel_service->name)).'</a></li>';
                } elseif($singel_service->category_type == 3 || $singel_service->category_type == 4 ) {
                    $other_service_list .= '<li class="dropdown-item sidebyside" ><a href="'.route("get:service-booking",[$singel_service->slug] ).'" title="'.$singel_service->name.'">'.ucwords(strtolower($singel_service->name)).'</a></li>';
                }
            }

            $service_list .= '<li class="nav-item dropdown cursorpointer">
                <a class="nav-link dropdown-toggle cursorpointer"  id="dropdown2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.$menu_title.'</a>
                <ul class="dropdown-menu" aria-labelledby="dropdown2">
                    <li class="dropdown-item dropdown">
                        <a class="dropdown-toggle cursorpointer" id="dropdown2-1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Ride Service</a>
                        <ul class="dropdown-menu dropdown-scroll" aria-labelledby="dropdown2-1">
                            '.$ride_service_list.'
                        </ul>
                    </li>
                    <li class="dropdown-item ">
                        <a class="dropdown-toggle cursorpointer" id="dropdown2-2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Delivery Service</a>
                        <ul class="dropdown-menu dropdown-scroll" aria-labelledby="dropdown2-2">
                            '.$delivery_service_list.'
                        </ul>
                    </li>
                    <li class="dropdown-item">
                        <a class="dropdown-toggle cursorpointer" id="dropdown2-2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Other Service</a>
                        <ul class="dropdown-menu dropdown-scroll" aria-labelledby="dropdown2-2">
                            '.$other_service_list.'
                        </ul>
                    </li>
                    '.$coupon_service_list.'
                </ul>
            </li>';

            return $service_list;
    }
    public static function getOtherServiceList($for = 0){

            $service_category_list =  ServiceCategory::query()->where('status',1)->get();
            $service_list ="";
            $ride_service_list = '';
            $delivery_service_list = '';
            $courier_service_list = '';
            $other_service_list = '';
            $coupon_service_list = "";

            $menu_title  = "Service";
            if($for == 1)
            {
                $menu_title = "Product";
            }
            foreach ($service_category_list as $singel_service)
            {
                if($singel_service->category_type == 3 || $singel_service->category_type == 4 ) {
                    $other_service_list .= '<li class="dropdown-item sidebyside" ><a href="' . route("get:service-booking", [$singel_service->slug]) . '" title="' . $singel_service->name . '">' . ucwords(strtolower($singel_service->name)) . '</a></li>';
                }
            }

            $service_list .= '<li class="nav-item dropdown cursorpointer">
                        <a class="nav-link dropdown-toggle cursorpointer"  id="dropdown2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.$menu_title.'</a>
                        <ul class="dropdown-menu dropdown-scroll" aria-labelledby="dropdown2-2">
                            '.$other_service_list.'
                        </ul>
            </li>';

            return $service_list;
    }

    public static function timezonedetails($timezonename=""){
        $timezone = "";
        if($timezonename != ""){
            $timezoneMapping = [
                'Asia/Calcutta' => 'Asia/Kolkata'
            ];
            $timezonename = $timezoneMapping[$timezonename] ?? $timezonename;
            $timezone = (new DateTime('now', new DateTimeZone($timezonename)))->format('T (P)');
        }
        return $timezone;
    }

}
