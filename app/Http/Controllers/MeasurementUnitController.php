<?php

namespace App\Http\Controllers;

use App\Models\MeasurementUnit;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MeasurementUnitController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = MeasurementUnit::latest();
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
                    $btn .= '<a href="' . route('measurement-unit.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('measurement-unit', ['pageTitle' => 'Settings']);
    }

    public function create()
    {
        return view('add-measurement', ['pageTitle' => 'Settings']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:measurement_units,name|max:255',
            'status' => 'required|in:Active,Inactive',
        ]);

        MeasurementUnit::create($request->all());

        return redirect()->route('measurement-unit.index')->with('success', 'Measurement Unit added successfully.');
    }

    public function edit($id)
    {
        $measurement = MeasurementUnit::findOrFail($id);
        return view('add-measurement', ['pageTitle' => 'Settings', 'measurement' => $measurement]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:measurement_units,name,' . $id,
            'status' => 'required|in:Active,Inactive',
        ]);

        $measurement = MeasurementUnit::findOrFail($id);
        $measurement->update($request->all());

        return redirect()->route('measurement-unit.index')->with('success', 'Measurement Unit updated successfully.');
    }

    public function toggleStatus($id)
    {
        $measurement = MeasurementUnit::findOrFail($id);
        $measurement->status = $measurement->status == 'Active' ? 'Inactive' : 'Active';
        $measurement->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
