<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use App\Models\Question;

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
        $users = User::select(['id', 'name', 'email', 'created_at']);

        return DataTables::of($users)
            ->addColumn('action', function ($user) {
                return '<a href="' . route('users.edit', $user->id) . '" class="btn btn-sm btn-primary">Edit</a>';
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
            'company_name' => 'required|string|max:255',
            'plan' => 'required|string|max:100',
            'questions' => 'nullable|array',
            'questions.*' => 'nullable|string|max:1000',
        ]);
        DB::beginTransaction();
        try {
            $tenant = Tenant::create([
                'owner_id' => $request->owner_id,
                'company_name' => $data['company_name'],
                'plan' => $data['plan'],
                'settings' => [
                    "voice" => "Polly.Joanna",
                    "style" => "friendly",
                    "memory" => true
                ]
            ]);

            // Create questions from submitted array or default
            $submittedQuestions = array_filter($data['questions'] ?? []);
            
            if (! empty($submittedQuestions)) {
                foreach ($submittedQuestions as $prompt) {
                    Question::firstOrCreate([
                        'tenant_id' => $tenant->id,
                        'prompt' => $prompt,
                    ]);
                }
            } else {
                // Create default question if none provided
                Question::firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'prompt' => 'May I have your full name?',
                ]);
            }

            DB::commit();

            return redirect()->route('company.admin')->with('success', 'Tenant created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log exception and show error
            logger()->error('Tenant store failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create tenant.');
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

            $submittedQuestions = array_filter($data['questions'] ?? []);
            
            if (! empty($submittedQuestions)) {
                foreach ($submittedQuestions as $prompt) {
                    Question::create([
                        'tenant_id' => $tenant->id,
                        'prompt' => $prompt,
                    ]);
                }
            } else {
                // Create default question if none provided
                Question::create([
                    'tenant_id' => $tenant->id,
                    'prompt' => 'May I have your full name?',
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
