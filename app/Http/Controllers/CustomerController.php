<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Customer::select('*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {
                    if ($row->status == 'Active') {
                        return '<span class="badge active">Active</span>';
                    } else {
                        return '<span class="badge inActive">Inactive</span>';
                    }
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                                <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="' . route('customer.show', $row->id) . '">View</a></li>
                                    <li><a class="dropdown-item" href="' . route('customer.edit', $row->id) . '">Edit</a></li>
                                    <li><a class="dropdown-item toggle-status" href="javascript:void(0)" data-id="' . $row->id . '">' . ($row->status == 'Active' ? 'Deactivate' : 'Activate') . '</a></li>
                                </ul>
                            </div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('customer');
    }

    public function create()
    {
        return view('add-customer');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'code' => 'required|unique:customers',
            'phone' => 'nullable',
            'cnic' => 'nullable',
            'address' => 'nullable',
            'nic_front' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'nic_back' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('nic_front')) {
            $file = $request->file('nic_front');
            $filename = time() . '_front.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/customers'), $filename);
            $data['nic_front'] = 'uploads/customers/' . $filename;
        }

        if ($request->hasFile('nic_back')) {
            $file = $request->file('nic_back');
            $filename = time() . '_back.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/customers'), $filename);
            $data['nic_back'] = 'uploads/customers/' . $filename;
        }

        Customer::create($data);

        return redirect()->route('customer.index')->with('success', 'Customer created successfully.');
    }

    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return view('view-customer', compact('customer'));
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('add-customer', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'code' => 'required|unique:customers,code,' . $id,
            'phone' => 'nullable',
            'cnic' => 'nullable',
            'address' => 'nullable',
            'nic_front' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'nic_back' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $customer = Customer::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('nic_front')) {
            // Delete old file if exists
            if ($customer->nic_front && file_exists(public_path($customer->nic_front))) {
                unlink(public_path($customer->nic_front));
            }

            $file = $request->file('nic_front');
            $filename = time() . '_front.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/customers'), $filename);
            $data['nic_front'] = 'uploads/customers/' . $filename;
        }

        if ($request->hasFile('nic_back')) {
            // Delete old file if exists
            if ($customer->nic_back && file_exists(public_path($customer->nic_back))) {
                unlink(public_path($customer->nic_back));
            }

            $file = $request->file('nic_back');
            $filename = time() . '_back.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/customers'), $filename);
            $data['nic_back'] = 'uploads/customers/' . $filename;
        }

        $customer->update($data);

        return redirect()->route('customer.index')->with('success', 'Customer updated successfully.');
    }

    public function toggleStatus($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->status = $customer->status == 'Active' ? 'Inactive' : 'Active';
        $customer->save();

        return response()->json(['success' => 'Status changed successfully.']);
    }
}
