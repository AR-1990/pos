<?php

namespace App\Http\Controllers;

use App\Models\ExpenseHead;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ExpenseHeadController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ExpenseHead::query();
            
            if ($request->has('status') && $request->status != 'all') {
                $status = $request->status == 'Active' ? 1 : 0;
                $data->where('status', $status);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('status', function($row){
                    return $row->status ? 'Active' : 'Inactive';
                })
                ->addColumn('action', function($row){
                    $editBtn = '<a href="'.route('expense-head.edit', $row->id).'" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen"></i></a>';
                    $statusBtn = '<button onclick="toggleStatus('.$row->id.')" class="btn btn-sm '.($row->status ? 'btn-success' : 'btn-danger').'"><i class="fa-solid fa-power-off"></i></button>';
                    return '<div class="btn-group gap-2">'.$editBtn.$statusBtn.'</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('expense-head');
    }

    public function create()
    {
        return view('add-expense-head');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'status' => 'nullable|in:Active,Inactive',
        ]);

        ExpenseHead::create([
            'name' => $request->name,
            'code' => $request->code,
            'status' => $request->status == 'Active' ? 1 : 0,
        ]);

        return redirect()->route('expense-head.index')->with('success', 'Expense Head added successfully.');
    }

    public function edit($id)
    {
        $expenseHead = ExpenseHead::findOrFail($id);
        return view('edit-expense-head', compact('expenseHead'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive',
        ]);

        $expenseHead = ExpenseHead::findOrFail($id);
        $expenseHead->update([
            'name' => $request->name,
            'code' => $request->code,
            'status' => $request->status == 'Active' ? 1 : 0,
        ]);

        return redirect()->route('expense-head.index')->with('success', 'Expense Head updated successfully.');
    }

    public function toggleStatus($id)
    {
        $expenseHead = ExpenseHead::findOrFail($id);
        $expenseHead->status = !$expenseHead->status;
        $expenseHead->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
