<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerJob extends Model
{
    protected $table = 'buyer_jobs';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'budget_min',
        'budget_max',
        'start_date',
        'end_date',
        'service_category_id',
        'sub_category_id',
        'lat',
        'long',
        'status',
        'priorities',
    ];

    protected $casts = [
        'priorities' => 'array',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
