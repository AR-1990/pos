<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Tax;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['unit', 'category', 'subCategory'])->latest()->get();
        return view('all-products', [
            'pageTitle' => 'Products',
            'products' => $products
        ]);
    }

    public function create()
    {
        $categories = Category::where('status', 'Active')->get();
        $units = MeasurementUnit::where('status', 'Active')->get();
        $vendors = Vendor::where('status', 'Active')->get();
        // Fetch all active taxes (including Services/Retail) so they appear if assigned to SubCategory
        $taxes = Tax::where('status', 'Active')->get();
        
        // Generate random 3 char prefix for SKU and Code
        $skuPrefix = 'SKU-';
        $codePrefix = 'PDC-';

        return view('add-products', [
            'pageTitle' => 'Products',
            'categories' => $categories,
            'units' => $units,
            'vendors' => $vendors,
            'taxes' => $taxes,
            'skuPrefix' => $skuPrefix,
            'codePrefix' => $codePrefix
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sku' => 'required|string|unique:products,sku|max:255',
            'code' => 'required|string|unique:products,code|max:255',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'unit_id' => 'required|exists:measurement_units,id',
            'unit_value' => 'nullable|numeric|min:0',
            'secondary_unit' => 'nullable|string|max:255',
            'pack_size' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'nullable|numeric|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0',
            'batch_expiry' => 'nullable|in:Yes,No',
            'expiry_date' => 'nullable|date|required_if:batch_expiry,Yes',
            'status' => 'required|in:Active,Inactive',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
        ]);

        $productData = $request->except(['taxes', 'batch_expiry']);
        $productData['batch_expiry_enabled'] = $request->batch_expiry === 'Yes';
        
        // If Batch/Expiry is No, ensure expiry_date is null
        if (!$productData['batch_expiry_enabled']) {
            $productData['expiry_date'] = null;
        }

        $product = Product::create($productData);

        if ($request->has('taxes')) {
            $syncData = [];
            foreach ($request->taxes as $taxId) {
                $syncData[$taxId] = ['tax_rate' => $request->input('tax_rates.' . $taxId)];
            }
            $product->taxes()->sync($syncData);
        }

        return redirect()->route('all-products')->with('success', 'Product added successfully.');
    }

    public function edit($id)
    {
        $product = Product::with('taxes')->findOrFail($id);
        $categories = Category::where('status', 'Active')->get();
        $subCategories = SubCategory::where('category_id', $product->category_id)->where('status', 'Active')->get();
        $units = MeasurementUnit::where('status', 'Active')->get();
        $vendors = Vendor::where('status', 'Active')->get();
        $taxes = Tax::where('status', 'Active')->get();

        return view('edit-product', [
            'pageTitle' => 'Edit Product',
            'product' => $product,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'units' => $units,
            'vendors' => $vendors,
            'taxes' => $taxes
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $request->validate([
            'sku' => 'required|string|max:255|unique:products,sku,' . $id,
            'code' => 'required|string|max:255|unique:products,code,' . $id,
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'unit_id' => 'required|exists:measurement_units,id',
            'unit_value' => 'nullable|numeric|min:0',
            'secondary_unit' => 'nullable|string|max:255',
            'pack_size' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'nullable|numeric|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0',
            'batch_expiry' => 'nullable|in:Yes,No',
            'expiry_date' => 'nullable|date|required_if:batch_expiry,Yes',
            'status' => 'required|in:Active,Inactive',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
        ]);

        $productData = $request->except(['taxes', 'batch_expiry']);
        $productData['batch_expiry_enabled'] = $request->batch_expiry === 'Yes';
        
        if (!$productData['batch_expiry_enabled']) {
            $productData['expiry_date'] = null;
        }

        $product->update($productData);

        if ($request->has('taxes')) {
            $syncData = [];
            foreach ($request->taxes as $taxId) {
                $syncData[$taxId] = ['tax_rate' => $request->input('tax_rates.' . $taxId)];
            }
            $product->taxes()->sync($syncData);
        } else {
            $product->taxes()->detach();
        }

        return redirect()->route('all-products')->with('success', 'Product updated successfully.');
    }

    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->status = $product->status === 'Active' ? 'Inactive' : 'Active';
        $product->save();

        return response()->json(['success' => true, 'status' => $product->status]);
    }

    public function getSubCategories($categoryId)
    {
        $subCategories = SubCategory::where('category_id', $categoryId)
            ->where('status', 'Active')
            ->get(['id', 'name']);
        
        return response()->json($subCategories);
    }

    public function getSubCategoryTaxes($subCategoryId)
    {
        $subCategory = SubCategory::with(['taxes' => function($query) {
            $query->select('taxes.id', 'taxes.tax_type'); // Select necessary fields
        }])->findOrFail($subCategoryId);

        // Format the response to include tax_id and rate
        $taxes = $subCategory->taxes->map(function($tax) {
            return [
                'id' => $tax->id,
                'name' => $tax->tax_type,
                'rate' => $tax->pivot->tax_rate
            ];
        });

        return response()->json($taxes);
    }
}
