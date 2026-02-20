<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Tax::latest();
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
                    $btn .= '<a href="' . route('taxes.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('taxes', ['pageTitle' => 'Settings']);
    }

    public function create()
    {
        return view('add-taxes', ['pageTitle' => 'Settings']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tax_type' => 'required|string|max:255',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'applied_on' => 'nullable|string|max:255',
            'effective_date' => 'nullable|date',
            'status' => 'required|in:Active,Inactive',
        ]);

        Tax::create($request->all());

        return redirect()->route('taxes.index')->with('success', 'Tax added successfully.');
    }

    public function edit($id)
    {
        $tax = Tax::findOrFail($id);
        return view('add-taxes', ['pageTitle' => 'Settings', 'tax' => $tax]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tax_type' => 'required|string|max:255',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'applied_on' => 'nullable|string|max:255',
            'effective_date' => 'nullable|date',
            'status' => 'required|in:Active,Inactive',
        ]);

        $tax = Tax::findOrFail($id);
        $tax->update($request->all());

        return redirect()->route('taxes.index')->with('success', 'Tax updated successfully.');
    }

    public function toggleStatus($id)
    {
        $tax = Tax::findOrFail($id);
        $tax->status = $tax->status == 'Active' ? 'Inactive' : 'Active';
        $tax->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
