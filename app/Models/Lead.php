<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['user_id', 'name', 'email', 'phone', 'metadata', 'source'];
    protected $casts = ['metadata' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function calls()
    {
        return $this->hasMany(CallRecord::class);
    }
}
