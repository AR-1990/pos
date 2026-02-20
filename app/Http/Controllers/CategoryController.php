<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Category::latest();
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
                    $btn .= '<a href="' . route('product-category.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('product-category', ['pageTitle' => 'Settings']);
    }

    public function create()
    {
        return view('add-product-category', ['pageTitle' => 'Settings']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:categories,code|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        Category::create($request->all());

        return redirect()->route('product-category.index')->with('success', 'Category added successfully.');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('add-product-category', ['pageTitle' => 'Settings', 'category' => $category]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:categories,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $category = Category::findOrFail($id);
        $category->update($request->all());

        return redirect()->route('product-category.index')->with('success', 'Category updated successfully.');
    }

    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);
        $category->status = $category->status == 'Active' ? 'Inactive' : 'Active';
        $category->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }

    // Products Context Methods
    public function indexProduct(Request $request)
    {
        if ($request->ajax()) {
            $query = Category::latest();
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
                    $btn .= '<a href="' . route('category.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('category', ['pageTitle' => 'Products']);
    }

    public function createProduct()
    {
        return view('add-category', ['pageTitle' => 'Products']);
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:categories,code|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        Category::create($request->all());

        return redirect()->route('category.index')->with('success', 'Category added successfully.');
    }

    public function editProduct($id)
    {
        $category = Category::findOrFail($id);
        return view('add-category', ['pageTitle' => 'Products', 'category' => $category]);
    }

    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:categories,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $category = Category::findOrFail($id);
        $category->update($request->all());

        return redirect()->route('category.index')->with('success', 'Category updated successfully.');
    }
}
