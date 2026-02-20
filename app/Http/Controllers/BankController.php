<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BankController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Bank::query();
            
            if ($request->has('status') && $request->status != 'all') {
                $data->where('status', $request->status);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $editBtn = '<a href="'.route('bank.edit', $row->id).'" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen"></i></a>';
                    $statusBtn = '<button onclick="toggleStatus('.$row->id.')" class="btn btn-sm '.($row->status == 'Active' ? 'btn-success' : 'btn-danger').'"><i class="fa-solid fa-power-off"></i></button>';
                    return '<div class="btn-group gap-2">'.$editBtn.$statusBtn.'</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('bank');
    }

    public function create()
    {
        return view('add-bank');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_title' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'branch_code' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'status' => 'nullable|in:Active,Inactive',
        ]);

        Bank::create([
            'name' => $request->name,
            'account_title' => $request->account_title,
            'account_number' => $request->account_number,
            'branch_code' => $request->branch_code,
            'branch_name' => $request->branch_name,
            'iban' => $request->iban,
            'status' => $request->status ?? 'Active',
        ]);

        return redirect()->route('bank.index')->with('success', 'Bank added successfully.');
    }

    public function edit($id)
    {
        $bank = Bank::findOrFail($id);
        return view('edit-bank', compact('bank'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_title' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'branch_code' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive',
        ]);

        $bank = Bank::findOrFail($id);
        $bank->update([
            'name' => $request->name,
            'account_title' => $request->account_title,
            'account_number' => $request->account_number,
            'branch_code' => $request->branch_code,
            'branch_name' => $request->branch_name,
            'iban' => $request->iban,
            'status' => $request->status,
        ]);

        return redirect()->route('bank.index')->with('success', 'Bank updated successfully.');
    }

    public function toggleStatus($id)
    {
        $bank = Bank::findOrFail($id);
        $bank->status = $bank->status == 'Active' ? 'Inactive' : 'Active';
        $bank->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
