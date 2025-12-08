<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyCall extends Model
{
     protected $fillable = ['call_sid', 'phone', 'name', 'email', 'dob', 'mobile', 'status'];
}
