<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use App\Models\Question;
use Illuminate\Support\Str;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;

class TenantController extends Controller
{

    public function index()
    {
        // $tenants = Tenant::with('questions')->get();
        $tenants = DB::table('tenants')->get();
        return view('admin.Company.company', compact('tenants'));
    }
    public function getData(Request $request)
    {
        $tenants = Tenant::select(['id', 'company_name', 'plan', 'created_at']);

        return DataTables::of($tenants)
            ->addColumn('action', function ($tenant) {

                $edit = '<a href="' . route('company.edit.admin', $tenant->id) . '" 
                            class="btn btn-sm btn-primary">Edit</a>';

                $delete = '<form method="POST" action="' . route('company.destroy.admin', $tenant->id) . '" 
                            style="display:inline-block;">
                            ' . csrf_field() . '
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm(\'Are you sure?\')">Delete</button>
                        </form>';

                return $edit . ' ' . $delete;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.company.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'plan' => 'required|string|max:100',
            'questions' => 'nullable|array',
            'questions.*' => 'nullable|string|max:1000',
        ]);
        
        DB::beginTransaction();
        try {
            // Create user first
            $user = \App\Models\User::create([
                'name' => $data['username'],
                'email' => $data['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                'role_id' => 2, // 2 = user role
            ]);

            // Create company (tenant) for the new user
            $tenant = Tenant::create([
                'owner_id' => $user->id,
                'company_name' => $data['company_name'],
                'plan' => $data['plan'],
                'settings' => [
                    "voice" => "Polly.Joanna",
                    "style" => "friendly",
                    "memory" => true
                ]
            ]);

            // Create questions from submitted array or default
            $submittedQuestions = array_values(array_filter($data['questions'] ?? []));

            if (! empty($submittedQuestions)) {
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

            DB::commit();

            // Redirect back to create form with success message
            return redirect()->route('company.admin')->with('success', 'User and company created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log exception and show error (include message for local debugging)
            logger()->error('Tenant store failed: ' . $e->getMessage());
            $message = env('APP_DEBUG', false) ? $e->getMessage() : 'Failed to create user and company.';
            return redirect()->back()->withInput()->with('error', $message);
        }
    }

    public function edit(Tenant $tenant)
    {
        return view('admin.company.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'plan' => 'required|string|max:100',
            'questions' => 'nullable|array',
            'questions.*' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $tenant->update([
                'company_name' => $data['company_name'],
                'plan' => $data['plan'],
            ]);

            // Delete old questions and create new ones
            $tenant->questions()->delete();

            $submittedQuestions = array_values(array_filter($data['questions'] ?? []));

            if (! empty($submittedQuestions)) {
                foreach ($submittedQuestions as $idx => $prompt) {
                    $prompt = trim($prompt);
                    if ($prompt === '') continue;
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

            DB::commit();

            return redirect()->route('company.admin')->with('success', 'Tenant updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Tenant update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update tenant.');
        }
    }

    public function destroy(Tenant $tenant)
    {
        // delete related questions first to keep data consistent
        if (method_exists($tenant, 'questions')) {
            $tenant->questions()->delete();
        }

        $tenant->delete();

        return redirect()->route('company.admin')->with('success', 'Tenant and related questions deleted successfully!');
    }
}
