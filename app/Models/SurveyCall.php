<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyCall extends Model
{
    protected $fillable = [
        'phone','call_sid','name','email','dob','mobile','status','conversation','state',
        'q1_speech','q2_speech','q3_speech'
    ];

    protected $casts = [
        'conversation' => 'array'
    ];
}
