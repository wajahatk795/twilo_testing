<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{

    protected $fillable = ['owner_id', 'company_name', 'plan', 'settings'];
    protected $casts = ['settings' => 'array'];
    public function phoneNumbers()
    {
        return $this->hasMany(PhoneNumber::class);
    }
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
    public function calls()
    {
        return $this->hasMany(CallRecord::class);
    }

    // Questions associated with this tenant
    public function questions()
    {
        return $this->hasMany(\App\Models\Question::class);
    }
    // app/Models/Tenant.php
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
