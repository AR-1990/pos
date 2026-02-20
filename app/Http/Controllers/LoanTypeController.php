<?php

namespace App\Http\Controllers;

use App\Models\LoanType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LoanTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = LoanType::latest();
            if ($request->has('status') && $request->status != 'all') {
                $query->where('status', $request->status);
            }
            $data = $query->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {
                    $statusClass = $row->status == 'Active' ? 'badge bg-success' : 'badge bg-danger';
                    return '<span class="' . $statusClass . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $toggleText = $row->status == 'Active' ? 'Inactive' : 'Active';
                    $toggleIcon = $row->status == 'Active' ? 'fa-ban' : 'fa-check';
                    $toggleClass = $row->status == 'Active' ? 'btn-danger' : 'btn-success';
                    
                    $btn = '<div class="d-flex gap-2">';
                    $btn .= '<a href="' . route('loan-types.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('loan-types', ['pageTitle' => 'Settings']);
    }

    public function create()
    {
        return view('add-loan-type', ['pageTitle' => 'Settings']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:loan_types,name',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        LoanType::create($request->all());

        return redirect()->route('loan-types.index')->with('success', 'Loan Type added successfully.');
    }

    public function edit($id)
    {
        $loanType = LoanType::findOrFail($id);
        return view('add-loan-type', ['pageTitle' => 'Settings', 'loanType' => $loanType]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:loan_types,name,' . $id,
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $loanType = LoanType::findOrFail($id);
        $loanType->update($request->all());

        return redirect()->route('loan-types.index')->with('success', 'Loan Type updated successfully.');
    }

    public function toggleStatus($id)
    {
        $loanType = LoanType::findOrFail($id);
        $loanType->status = $loanType->status == 'Active' ? 'Inactive' : 'Active';
        $loanType->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
