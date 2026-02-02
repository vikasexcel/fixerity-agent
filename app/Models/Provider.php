<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    protected $table = 'providers';

    protected $guard = ['store', 'on_demand'];

    Private $store_array_id = [5, 6, 7, 8, 9, 10];

    public function generateAccessToken($provider_id)
    {
        $this->access_token = random_int(1, 99) . date('siHYdm') . random_int(1, 99);
        $this->save();
        return $this->access_token;
    }
}
