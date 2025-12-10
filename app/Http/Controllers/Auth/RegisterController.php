<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    // Show register form
    public function showRegister()
    {
        // If already authenticated, return the user back to previous page.
        if (Auth::check()) {
            $previous = url()->previous();
            if (empty($previous) || $previous === url()->current()) {
                return redirect('/admin');
            }

            return redirect()->to($previous);
        }

        return view('register');
    }

    // Handle registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::create([
            'name' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => 2,
        ]);

        if($user){
            $tenant = Tenant::create([
                'owner_id' => $user->id,
                'company_name' => $user->name,
                'plan' => 'free',
                'settings' => [
                    "voice" => "Polly.Joanna",
                    "style" => "friendly",
                    "memory" => true
                ]
            ]);
        }

        // After registration, return to register page showing company creation form
        return redirect()->back()->with('success', 'Registration successful! Please Login.');
    }

}
