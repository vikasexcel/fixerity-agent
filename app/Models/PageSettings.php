<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;

class PageSettings extends Model
{
    protected $table = 'page_settings';

    public function getSlugForCustom($name){
        $slug = trim(strtolower(str_replace(" ","",$name)));
        return $slug;
    }
}
