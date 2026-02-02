<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProviderDocuments;

class RequiredDocuments extends Model
{
    protected $table = 'required_documents';

    function provider_documents(){
        return $this->belongsTo(ProviderDocuments::class,'id', 'req_document_id');
    }
}
