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
use Illuminate\Support\Facades\Hash;


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
        return view('admin.Company.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'plan' => 'required|string|max:100',
            'questions' => 'nullable|array',
            'questions.*' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Create user first (without name)
            $user = \App\Models\User::create([
                'name' => $data['company_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => 2,
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

            // Handle questions
            $submittedQuestions = array_values(array_filter($data['questions'] ?? []));
            if (!empty($submittedQuestions)) {
                foreach ($submittedQuestions as $idx => $prompt) {
                    $prompt = trim($prompt);
                    if ($prompt === '') continue;
                    Question::create([
                        'tenant_id' => $tenant->id,
                        'prompt' => $prompt,
                    ]);
                }
            } else {
                Question::create([
                    'tenant_id' => $tenant->id,
                    'prompt' => 'May I have your full name?',
                ]);
            }

            DB::commit();

            return redirect()->route('company.admin')->with('success', 'User and company created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Tenant store failed: ' . $e->getMessage());
            $message = env('APP_DEBUG', false) ? $e->getMessage() : 'Failed to create user and company.';
            return redirect()->back()->withInput()->with('error', $message);
        }
    }


    public function edit($id)
    {
        $tenant = Tenant::with('owner')->findOrFail($id);
        // dd( $tenant->owner->email);

        $users = User::where('role_id', 2)->get();

        return view('admin.Company.edit', compact('tenant', 'users'));
    }

    public function update(Request $request, $id)
    {
        $tenant = Tenant::with('owner')->findOrFail($id);

        $data = $request->validate([
            'email' => 'required|email|unique:users,email,' . ($tenant->owner->id ?? '0'),
            'password' => 'nullable|min:8|confirmed', // password optional
            'company_name' => 'required|string|max:255',
            'plan' => 'required|string|max:100',
            'questions' => 'nullable|array',
            'questions.*' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Update tenant info
            $tenant->update([
                'company_name' => $data['company_name'],
                'plan' => $data['plan'],
            ]);

            // Update owner (user) info
            if ($tenant->owner) {
                $tenant->owner->email = $data['email'];

                // Update password only if user entered a new one
                if (!empty($data['password'])) {
                    $tenant->owner->password = Hash::make($data['password']);
                }

                $tenant->owner->save();
            }

            // Update questions
            $tenant->questions()->delete();
            $submittedQuestions = array_values(array_filter($data['questions'] ?? []));
            if (!empty($submittedQuestions)) {
                foreach ($submittedQuestions as $prompt) {
                    $prompt = trim($prompt);
                    if ($prompt === '') continue;
                    Question::create([
                        'tenant_id' => $tenant->id,
                        'prompt' => $prompt,
                    ]);
                }
            } else {
                Question::create([
                    'tenant_id' => $tenant->id,
                    'prompt' => 'May I have your full name?',
                ]);
            }

            DB::commit();

            return redirect()->route('company.admin')->with('success', 'Tenant and user updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Tenant update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update tenant and user.');
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
