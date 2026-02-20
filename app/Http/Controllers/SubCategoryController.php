<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Tax;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SubCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SubCategory::with(['category', 'taxes'])->latest();
            
            if ($request->has('status') && $request->status != 'all') {
                $query->where('status', $request->status);
            }
            
            $data = $query->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('category_name', function ($row) {
                    return $row->category ? $row->category->name : 'N/A';
                })
                ->addColumn('taxes', function ($row) {
                    if ($row->taxes->isEmpty()) {
                        return '<span class="badge bg-secondary">None</span>';
                    }
                    return $row->taxes->map(function ($tax) {
                        return '<span class="badge bg-info">' . $tax->tax_type . ' (' . ($tax->pivot->tax_rate ?? 0) . '%)</span>';
                    })->implode(' ');
                })
                ->addColumn('status', function ($row) {
                    $statusClass = $row->status == 'Active' ? 'badge bg-success' : 'badge bg-danger';
                    return '<span class="' . $statusClass . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $toggleText = $row->status == 'Active' ? 'Inactive' : 'Active';
                    $toggleIcon = $row->status == 'Active' ? 'fa-ban' : 'fa-check';
                    $toggleClass = $row->status == 'Active' ? 'btn-danger' : 'btn-success';
                    
                    $btn = '<div class="d-flex gap-2">';
                    $btn .= '<a href="' . route('sub-category.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['taxes', 'status', 'action'])
                ->make(true);
        }
        return view('sub-category', ['pageTitle' => 'Products']);
    }

    public function create()
    {
        $categories = Category::where('status', 'Active')->get();
        $taxes = Tax::where('status', 'Active')->get();
        return view('add-sub-category', ['pageTitle' => 'Products', 'categories' => $categories, 'taxes' => $taxes]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|unique:sub_categories,code|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
            'tax_rates' => 'nullable|array',
            'status' => 'required|in:Active,Inactive',
        ]);

        $subCategory = SubCategory::create($request->except(['taxes', 'tax_rates']));
        
        if ($request->has('taxes')) {
            $syncData = [];
            foreach ($request->taxes as $taxId) {
                $rate = isset($request->tax_rates[$taxId]) ? $request->tax_rates[$taxId] : 0;
                $syncData[$taxId] = ['tax_rate' => $rate];
            }
            $subCategory->taxes()->sync($syncData);
        }

        return redirect()->route('sub-category.index')->with('success', 'Sub Category added successfully.');
    }

    public function edit($id)
    {
        $subCategory = SubCategory::with('taxes')->findOrFail($id);
        $categories = Category::where('status', 'Active')->get();
        $taxes = Tax::where('status', 'Active')->get();
        return view('add-sub-category', ['pageTitle' => 'Products', 'subCategory' => $subCategory, 'categories' => $categories, 'taxes' => $taxes]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|max:255|unique:sub_categories,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
            'tax_rates' => 'nullable|array',
            'status' => 'required|in:Active,Inactive',
        ]);

        $subCategory = SubCategory::findOrFail($id);
        $subCategory->update($request->except(['taxes', 'tax_rates']));

        if ($request->has('taxes')) {
            $syncData = [];
            foreach ($request->taxes as $taxId) {
                $rate = isset($request->tax_rates[$taxId]) ? $request->tax_rates[$taxId] : 0;
                $syncData[$taxId] = ['tax_rate' => $rate];
            }
            $subCategory->taxes()->sync($syncData);
        } else {
            $subCategory->taxes()->detach();
        }

        return redirect()->route('sub-category.index')->with('success', 'Sub Category updated successfully.');
    }

    public function toggleStatus($id)
    {
        $subCategory = SubCategory::findOrFail($id);
        $subCategory->status = $subCategory->status == 'Active' ? 'Inactive' : 'Active';
        $subCategory->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }
}
