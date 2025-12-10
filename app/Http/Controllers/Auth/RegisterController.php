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

    // Handle company creation after registration
    public function registerCompany(Request $request)
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect()->route('auth.register')->with('error', 'Please register first.');
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'plan' => 'required|string|max:100',
            'questions' => 'nullable|array',
            'questions.*' => 'nullable|string|max:1000',
        ]);

        // Create company (tenant) for the authenticated user
        $tenant = Tenant::create([
            'owner_id' => Auth::id(),
            'company_name' => $validated['company_name'],
            'plan' => $validated['plan'],
            'settings' => [
                "voice" => "Polly.Joanna",
                "style" => "friendly",
                "memory" => true
            ]
        ]);

        // Create questions from submitted array or default
        $submittedQuestions = array_values(array_filter($validated['questions'] ?? []));
        
        if (!empty($submittedQuestions)) {
            foreach ($submittedQuestions as $idx => $prompt) {
                $prompt = trim($prompt);
                if ($prompt === '') continue;
                // generate a safe field name from prompt (fallback to question_{n})
                $fieldName = Str::slug(substr($prompt, 0, 40));
                if ($fieldName === '') {
                    $fieldName = 'question_' . ($idx + 1);
                }
                Question::create([
                    'tenant_id' => $tenant->id,
                    'prompt' => $prompt,
                    'field' => $fieldName,
                ]);
            }
        } else {
            // Create default question if none provided
            Question::create([
                'tenant_id' => $tenant->id,
                'prompt' => 'May I have your full name?',
                'field' => 'full_name',
            ]);
        }

        return redirect('/admin')->with('success', 'Registration and company creation successful!');
    }
}
