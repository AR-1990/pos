<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Warehouse::latest();
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
                    $btn .= '<a href="' . route('ware-house.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('ware-house', ['pageTitle' => 'Settings']);
    }

    public function create()
    {
        return view('add-ware-house', ['pageTitle' => 'Settings']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:warehouses,code|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        Warehouse::create($request->all());

        return redirect()->route('ware-house.index')->with('success', 'Warehouse added successfully.');
    }

    public function edit($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        return view('add-ware-house', ['pageTitle' => 'Settings', 'warehouse' => $warehouse]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code,' . $id,
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->all());

        return redirect()->route('ware-house.index')->with('success', 'Warehouse updated successfully.');
    }

    public function toggleStatus($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->status = $warehouse->status == 'Active' ? 'Inactive' : 'Active';
        $warehouse->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
