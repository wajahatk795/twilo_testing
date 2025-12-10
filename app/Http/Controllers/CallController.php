<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallController extends Controller
{
    public function index()
    {
        $calls = DB::table('calls')->get();
        return view('admin.call.index', compact('calls'));  
    }
}
