<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'country' => 'required|string',
        ]);

        $user = User::create([
            'name' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => '2',
        ]);

        Auth::login($user);

        // For now, send all newly-registered users to the admin dashboard.
        return redirect('/admin')->with('success', 'Registration successful!');
    }
}
