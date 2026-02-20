<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Vendor::latest();
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
                    $btn .= '<a href="' . route('vendor.show', $row->id) . '" class="btn btn-info btn-sm text-white" title="View"><i class="fa-solid fa-eye"></i></a>';
                    $btn .= '<a href="' . route('vendor.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    $btn .= '</div>';
                    
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('vendor', ['pageTitle' => 'Vendors']);
    }

    public function create()
    {
        return view('add-vendor', ['pageTitle' => 'Add Vendor']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'code' => 'required|string|unique:vendors,code|max:255',
            'phone' => 'nullable|string|max:20',
            'cnic' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
            'nic_front' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'nic_back' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('nic_front')) {
            $file = $request->file('nic_front');
            $filename = time() . '_front.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/vendors'), $filename);
            $data['nic_front'] = 'uploads/vendors/' . $filename;
        }

        if ($request->hasFile('nic_back')) {
            $file = $request->file('nic_back');
            $filename = time() . '_back.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/vendors'), $filename);
            $data['nic_back'] = 'uploads/vendors/' . $filename;
        }

        Vendor::create($data);

        return redirect()->route('vendor.index')->with('success', 'Vendor created successfully.');
    }

    public function show($id)
    {
        $vendor = Vendor::findOrFail($id);
        return view('view-vendor', compact('vendor'));
    }

    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        return view('add-vendor', compact('vendor'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'code' => 'required|string|max:255|unique:vendors,code,' . $id,
            'phone' => 'nullable|string|max:20',
            'cnic' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
            'nic_front' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'nic_back' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $vendor = Vendor::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('nic_front')) {
            // Delete old file if exists
            if ($vendor->nic_front && file_exists(public_path($vendor->nic_front))) {
                unlink(public_path($vendor->nic_front));
            }
            
            $file = $request->file('nic_front');
            $filename = time() . '_front.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/vendors'), $filename);
            $data['nic_front'] = 'uploads/vendors/' . $filename;
        }

        if ($request->hasFile('nic_back')) {
            // Delete old file if exists
            if ($vendor->nic_back && file_exists(public_path($vendor->nic_back))) {
                unlink(public_path($vendor->nic_back));
            }

            $file = $request->file('nic_back');
            $filename = time() . '_back.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/vendors'), $filename);
            $data['nic_back'] = 'uploads/vendors/' . $filename;
        }

        $vendor->update($data);

        return redirect()->route('vendor.index')->with('success', 'Vendor updated successfully.');
    }

    public function toggleStatus($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = $vendor->status == 'Active' ? 'Inactive' : 'Active';
        $vendor->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
