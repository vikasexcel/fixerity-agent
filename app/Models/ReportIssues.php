<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportIssues extends Model
{
    protected $table = 'report_issues';

    protected $fillable = [
        'reference_no', 'description', 'order_id', 'provider_id', 'provider_type', 'status','service_cat_id','resolved_on'
    ];

    //Function that returns the details of Dream Organizer
    public function userDetails(){
        return $this->hasOne(User::class,'id','user_id')->select('id','first_name','last_name');
    }

    public static function generateReferenceNo()
    {
        return random_int(10000000,99999999);
    }
}
