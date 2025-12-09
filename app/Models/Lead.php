<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['tenant_id', 'name', 'email', 'phone', 'metadata', 'source'];
    protected $casts = ['metadata' => 'array'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function calls()
    {
        return $this->hasMany(CallRecord::class);
    }
}
