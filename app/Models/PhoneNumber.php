<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneNumber extends Model
{
    protected $fillable = ['user_id', 'label', 'number', 'twilio_sid', 'ivr_flow'];
    protected $casts = ['ivr_flow' => 'array'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
