<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    protected $table = 'service_category';

    public function slug($name)
    {
        $name = str_replace(' ', '-', trim(strtolower($name)));
        return $name;
    }
}
