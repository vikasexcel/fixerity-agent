<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $table = 'super_admin';

    protected $guard = 'admin';


    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function generateAccessToken($id)
    {
        $this->access_token = random_int(1, 99) . date('sihYdm') . random_int(1, 99);
        $this->save();
        return $this->access_token;
    }

    public function InviteCode($id, $name)
    {
//        $name = strtoupper(substr($name, 0, 3));
        $name = strtoupper(substr(str_replace(' ','',$name), 0, 3));
        $id = (2) * ($id);
        $this->invite_code = $name . $id;
        $this->save();
        return $this->invite_code;
    }
    public function SiteSetting($id)
    {
        $site_settings = GeneralSettings::first();
        dd($site_settings);
        return $site_settings;
    }
}
