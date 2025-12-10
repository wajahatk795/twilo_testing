<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class leadController extends Controller
{
    public function lead()
    {
        $lead = DB::table('leads')->get();
        return view('admin.lead.index' , compact('lead'));
    }
    public function create()
    {
        $users = User::where('role_id', 2)->get();
        return view('admin.lead.create', compact('users'));
    }
}
