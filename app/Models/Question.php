<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    
    protected $fillable = ['tenant_id','prompt'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
