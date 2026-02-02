<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSettings extends Model
{
    protected $table = 'general_settings';

    public static function currency_fomate($amount){
        return number_format($amount,2);
    }
}
