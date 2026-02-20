<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    private $modules = [
        'dashboard' => 'Dashboard',
        'sales' => 'Sales (POS)',
        'products' => 'Products',
        'categories' => 'Product Categories',
        'sub_categories' => 'Product Sub-Categories',
        'units' => 'Measurement Units',
        'warehouses' => 'Warehouses',
        'discounts' => 'Discounts',
        'customers' => 'Customers',
        'vendors' => 'Vendors',
        'banks' => 'Banks',
        'cashbook' => 'Cashbook',
        'cashbook_heads' => 'Cashbook Heads',
        'expenses' => 'Expenses',
        'expense_heads' => 'Expense Heads',
        'payroll' => 'Payroll',
        'loans' => 'Loan & Advances',
        'loan_types' => 'Loan Types',
        'reports_sales' => 'Report: Sales',
        'reports_returns' => 'Report: Returns',
        'reports_stock' => 'Report: Stock',
        'reports_top_products' => 'Report: Top Products',
        'reports_profit' => 'Report: Profit',
        'reports_loss' => 'Report: Loss',
        'reports_revenue' => 'Report: Revenue',
        'users' => 'Users',
        'roles' => 'Roles & Permissions',
        'settings_system' => 'System Settings',
        'taxes' => 'General Settings (Taxes)',
    ];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Role::query();
            
            // Filter by status if provided
            if ($request->has('status') && $request->status != 'all') {
                $query->where('status', $request->status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    $class = $row->status == 'Active' ? 'badge bg-success' : 'badge bg-danger';
                    if (auth()->user()->hasPermission('roles', 'status')) {
                        return '<span class="' . $class . '" style="cursor:pointer" onclick="toggleStatus(' . $row->id . ')">' . $row->status . '</span>';
                    }
                    return '<span class="' . $class . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '';
                    if (auth()->user()->hasPermission('roles', 'edit')) {
                        $btn .= '<a href="'.route('roles.edit', $row->id).'" class="btn btn-outline-primary btn-sm me-2"><i class="fa-solid fa-pen"></i></a>';
                    }
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('roles.index', ['pageTitle' => 'Roles Management']);
    }

    public function create()
    {
        return view('roles.create', [
            'pageTitle' => 'Add Role',
            'modules' => $this->modules
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'status' => 'required|in:Active,Inactive'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        if ($request->has('permissions')) {
            foreach ($request->permissions as $module => $perms) {
                // Verify module exists
                if (!array_key_exists($module, $this->modules)) continue;

                RolePermission::create([
                    'role_id' => $role->id,
                    'module' => $module,
                    'can_view' => isset($perms['view']),
                    'can_add' => isset($perms['add']),
                    'can_edit' => isset($perms['edit']),
                    'can_delete' => isset($perms['delete']),
                    'can_status' => isset($perms['status']),
                ]);
            }
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        
        // Map permissions for easier access in view
        $permissions = [];
        foreach ($role->permissions as $perm) {
            $permissions[$perm->module] = $perm;
        }

        return view('roles.edit', [
            'role' => $role,
            'rolePermissions' => $permissions,
            'modules' => $this->modules,
            'pageTitle' => 'Edit Role'
        ]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        
        $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
            'status' => 'required|in:Active,Inactive'
        ]);

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        // Clear existing permissions
        $role->permissions()->delete();

        if ($request->has('permissions')) {
            foreach ($request->permissions as $module => $perms) {
                if (!array_key_exists($module, $this->modules)) continue;

                RolePermission::create([
                    'role_id' => $role->id,
                    'module' => $module,
                    'can_view' => isset($perms['view']),
                    'can_add' => isset($perms['add']),
                    'can_edit' => isset($perms['edit']),
                    'can_delete' => isset($perms['delete']),
                    'can_status' => isset($perms['status']),
                ]);
            }
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $role->status = $role->status == 'Active' ? 'Inactive' : 'Active';
        $role->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
