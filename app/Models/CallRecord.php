<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallRecord extends Model
{
    protected $table = 'calls';
    protected $fillable = ['tenant_id', 'lead_id', 'call_sid', 'from', 'to', 'transcript', 'ai_history', 'status'];
    protected $casts = ['ai_history' => 'array'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
