<?php

namespace App\Http\Controllers;

use App\Models\CashbookHead;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CashbookHeadController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CashbookHead::query();
            
            if ($request->has('status') && $request->status != 'all') {
                $data->where('status', $request->status);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $editBtn = '<a href="'.route('cashbook-head.edit', $row->id).'" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen"></i></a>';
                    $statusBtn = '<button onclick="toggleStatus('.$row->id.')" class="btn btn-sm '.($row->status == 'Active' ? 'btn-success' : 'btn-danger').'"><i class="fa-solid fa-power-off"></i></button>';
                    return '<div class="btn-group gap-2">'.$editBtn.$statusBtn.'</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('cashbook-head');
    }

    public function create()
    {
        return view('add-cashbook-head');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'status' => 'nullable|in:Active,Inactive',
        ]);

        CashbookHead::create([
            'name' => $request->name,
            'code' => $request->code,
            'status' => $request->status ?? 'Active',
        ]);

        return redirect()->route('cashbook-head.index')->with('success', 'Cashbook Head added successfully.');
    }

    public function edit($id)
    {
        $cashbookHead = CashbookHead::findOrFail($id);
        return view('edit-cashbook-head', compact('cashbookHead'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive',
        ]);

        $cashbookHead = CashbookHead::findOrFail($id);
        $cashbookHead->update([
            'name' => $request->name,
            'code' => $request->code,
            'status' => $request->status,
        ]);

        return redirect()->route('cashbook-head.index')->with('success', 'Cashbook Head updated successfully.');
    }

    public function toggleStatus($id)
    {
        $cashbookHead = CashbookHead::findOrFail($id);
        $cashbookHead->status = $cashbookHead->status == 'Active' ? 'Inactive' : 'Active';
        $cashbookHead->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
