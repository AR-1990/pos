<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Discount::latest();
            if ($request->has('status') && $request->status != 'all') {
                $query->where('status', $request->status);
            }
            $data = $query; // let DataTables paginate in SQL
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
                    $btn .= '<a href="' . route('discount.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('discount', ['pageTitle' => 'Settings']);
    }

    public function create()
    {
        return view('add-discount', ['pageTitle' => 'Settings']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ]);

        Discount::create($request->all());

        return redirect()->route('discount.index')->with('success', 'Discount added successfully.');
    }

    public function edit($id)
    {
        $discount = Discount::findOrFail($id);
        return view('add-discount', ['pageTitle' => 'Settings', 'discount' => $discount]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ]);

        $discount = Discount::findOrFail($id);
        $discount->update($request->all());

        return redirect()->route('discount.index')->with('success', 'Discount updated successfully.');
    }

    public function toggleStatus($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->status = $discount->status == 'Active' ? 'Inactive' : 'Active';
        $discount->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
