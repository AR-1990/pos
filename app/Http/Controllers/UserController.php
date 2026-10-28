<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = User::with(['role', 'store']);
            
            // Filter by status if provided
            if ($request->has('status') && $request->status != 'all') {
                $query->where('status', $request->status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('role_name', function ($row) {
                    return $row->role ? '<span class="badge bg-info">'.$row->role->name.'</span>' : '-';
                })
                ->addColumn('store_name', function ($row) {
                    return $row->store ? e($row->store->name) : '<span class="text-muted">All / none</span>';
                })
                ->filterColumn('role_name', function($query, $keyword) {
                    $query->whereHas('role', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->editColumn('status', function ($row) {
                    $class = $row->status == 'Active' ? 'badge bg-success' : 'badge bg-danger';
                    return '<span class="' . $class . '" style="cursor:pointer" onclick="toggleStatus(' . $row->id . ')">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex">';
                    $btn .= '<a href="'.route('users.show', $row->id).'" class="btn btn-outline-info btn-sm me-2"><i class="fa-solid fa-eye"></i></a>';
                    $btn .= '<a href="'.route('users.edit', $row->id).'" class="btn btn-outline-primary btn-sm me-2"><i class="fa-solid fa-pen"></i></a>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['role_name', 'store_name', 'status', 'action'])
                ->make(true);
        }
        return view('users.index', ['pageTitle' => 'Users Management']);
    }

    public function create()
    {
        $roles = Role::where('status', 'Active')->get();
        $stores = \App\Models\Store::where('status', 'Active')->orderBy('name')->get();
        return view('users.create', ['pageTitle' => 'Add User', 'roles' => $roles, 'stores' => $stores]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
            'role_id' => 'required',
            'store_id' => 'nullable|exists:stores,id',
            'identity_number' => 'nullable',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'identity_card_front' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'identity_card_back' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cv_path' => 'nullable|mimes:pdf,doc,docx|max:2048',
            'status' => 'required|in:Active,Inactive'
        ]);

        $data = $request->except(['password', 'password_confirmation', 'profile_image', 'identity_card_front', 'identity_card_back', 'cv_path']);
        $data['password'] = Hash::make($request->password);

        // Ensure directory exists
        if (!file_exists(public_path('uploads/users'))) {
            mkdir(public_path('uploads/users'), 0777, true);
        }

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time() . '_profile.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $data['profile_image'] = 'uploads/users/' . $filename;
        }

        if ($request->hasFile('identity_card_front')) {
            $file = $request->file('identity_card_front');
            $filename = time() . '_front.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $data['identity_card_front'] = 'uploads/users/' . $filename;
        }

        if ($request->hasFile('identity_card_back')) {
            $file = $request->file('identity_card_back');
            $filename = time() . '_back.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $data['identity_card_back'] = 'uploads/users/' . $filename;
        }

        if ($request->hasFile('cv_path')) {
            $file = $request->file('cv_path');
            $filename = time() . '_cv.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $data['cv_path'] = 'uploads/users/' . $filename;
        }

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show($id)
    {
        $user = User::with('role')->findOrFail($id);
        return view('users.show', ['pageTitle' => 'User Details', 'user' => $user]);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::where('status', 'Active')->get();
        $stores = \App\Models\Store::where('status', 'Active')->orderBy('name')->get();
        return view('users.edit', ['pageTitle' => 'Edit User', 'user' => $user, 'roles' => $roles, 'stores' => $stores]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

         $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role_id' => 'required',
            'store_id' => 'nullable|exists:stores,id',
            'password' => 'nullable|confirmed|min:6',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'identity_card_front' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'identity_card_back' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cv_path' => 'nullable|mimes:pdf,doc,docx|max:2048',
            'status' => 'required|in:Active,Inactive'
        ]);
        
        $data = $request->except(['password', 'password_confirmation', 'profile_image', 'identity_card_front', 'identity_card_back', 'cv_path']);
        
        if($request->filled('password')) {
             $data['password'] = Hash::make($request->password);
        }

        // Ensure directory exists
        if (!file_exists(public_path('uploads/users'))) {
            mkdir(public_path('uploads/users'), 0777, true);
        }

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time() . '_profile.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $data['profile_image'] = 'uploads/users/' . $filename;
            
            // Optional: delete old file
            if ($user->profile_image && file_exists(public_path($user->profile_image))) {
                unlink(public_path($user->profile_image));
            }
        }

        if ($request->hasFile('identity_card_front')) {
            $file = $request->file('identity_card_front');
            $filename = time() . '_front.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $data['identity_card_front'] = 'uploads/users/' . $filename;
            
            // Optional: delete old file
            if ($user->identity_card_front && file_exists(public_path($user->identity_card_front))) {
                unlink(public_path($user->identity_card_front));
            }
        }

        if ($request->hasFile('identity_card_back')) {
            $file = $request->file('identity_card_back');
            $filename = time() . '_back.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $data['identity_card_back'] = 'uploads/users/' . $filename;
            
             // Optional: delete old file
            if ($user->identity_card_back && file_exists(public_path($user->identity_card_back))) {
                unlink(public_path($user->identity_card_back));
            }
        }

        if ($request->hasFile('cv_path')) {
            $file = $request->file('cv_path');
            $filename = time() . '_cv.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $data['cv_path'] = 'uploads/users/' . $filename;
            
             // Optional: delete old file
            if ($user->cv_path && file_exists(public_path($user->cv_path))) {
                unlink(public_path($user->cv_path));
            }
        }
        
        $user->update($data);
        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status == 'Active' ? 'Inactive' : 'Active';
        $user->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
