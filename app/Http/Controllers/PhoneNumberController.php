<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PhoneNumber;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;

class PhoneNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.phone_numbers.index');
    }

    public function getData(Request $request)
    {
        $query = PhoneNumber::with('user')->select('phone_numbers.*');

        return DataTables::of($query)
            ->addColumn('user', fn($row) => $row->user->name ?? '')
            ->addColumn('action', function($row){
                return '
                    <a href="'.route('admin.phone-numbers.edit', $row->id).'"
                       class="btn btn-sm btn-primary">Edit</a>

                    <button data-id="'.$row->id.'"
                            class="btn btn-sm btn-danger delete-btn">
                        Delete
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::where('role_id', 2)->get();
        return view('admin.phone_numbers.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'number'  => 'required|unique:phone_numbers,number',
        ]);

        PhoneNumber::create($request->all());

        return redirect()->route('admin.phone-numbers.index')
            ->with('success', 'Phone number added successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $phoneNumber = PhoneNumber::findOrFail($id);
        $users = User::where('role_id', 2)->get();
        return view('admin.phone_numbers.edit', compact('phoneNumber', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $phone = PhoneNumber::findOrFail($id);

        $request->validate([
            'number' => 'required|unique:phone_numbers,number,' . $id,
        ]);

        $phone->update($request->all());

        return redirect()->route('admin.phone-numbers.index')
            ->with('success', 'Phone number updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        PhoneNumber::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
