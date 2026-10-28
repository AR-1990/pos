<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\SubCategory;
use App\Models\Tax;
use App\Models\Vendor;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $storeId = Store::scopedId();
        $query = Product::with(['unit', 'category', 'subCategory'])->latest();

        // Stock column follows the current scope: one store's quantity, or the all-stores total
        if ($storeId !== null) {
            $query->select('products.*')->selectSub(
                StoreStock::withoutGlobalScope('store')->selectRaw('COALESCE(SUM(quantity), 0)')
                    ->whereColumn('store_stocks.product_id', 'products.id')
                    ->where('store_stocks.store_id', $storeId),
                'scoped_stock'
            );
        }

        $products = $query->get();
        if ($storeId === null) {
            $products->each(fn ($p) => $p->scoped_stock = $p->stock_quantity);
        }

        return view('all-products', [
            'pageTitle' => 'Products',
            'products' => $products,
            'stockScope' => $storeId ? (Store::whereKey($storeId)->value('name') ?? 'Store') : ($storeId === 0 ? 'No store' : 'All stores'),
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
            'codePrefix' => $codePrefix,
            'stores' => $this->stockStores(),
            'defaultStoreId' => auth()->user()->canViewAllStores() ? session('store_filter') : auth()->user()->store_id,
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
            // Printable barcode characters only, so every scanner can read the label
            'barcode' => ['nullable', 'string', 'max:48', 'regex:/^[A-Za-z0-9\-\.\/]+$/', 'unique:products,barcode'],
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'opening_stock' => 'nullable|numeric|min:0',
            'store_id' => 'nullable|integer',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0',
            'batch_expiry' => 'nullable|in:Yes,No',
            'expiry_date' => 'nullable|date|required_if:batch_expiry,Yes',
            'status' => 'required|in:Active,Inactive',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
        ]);

        $openingStock = (float) $request->input('opening_stock', 0);
        $storeId = null;
        if ($openingStock > 0) {
            $storeId = $this->resolveStockStoreId($request);
            if (!$storeId) {
                throw ValidationException::withMessages([
                    'store_id' => auth()->user()->canViewAllStores()
                        ? 'Please select the store that holds the opening stock.'
                        : 'You are not assigned to any store. Ask an admin to assign one.',
                ]);
            }
        }

        // Stock only changes through StockService, so the product starts at 0
        $productData = $request->except(['taxes', 'tax_rates', 'batch_expiry', 'opening_stock', 'store_id', 'stock_quantity', 'print_label', '_token']);
        $productData['stock_quantity'] = 0;
        $productData['batch_expiry_enabled'] = $request->batch_expiry === 'Yes';

        // If Batch/Expiry is No, ensure expiry_date is null
        if (!$productData['batch_expiry_enabled']) {
            $productData['expiry_date'] = null;
        }

        $product = DB::transaction(function () use ($request, $productData, $openingStock, $storeId) {
            $product = Product::create($productData);

            // No barcode typed or scanned: give it a scannable in-store EAN-13
            if (blank($product->barcode)) {
                $product->update(['barcode' => Product::generateBarcode($product->id)]);
            }

            if ($request->has('taxes')) {
                $syncData = [];
                foreach ($request->taxes as $taxId) {
                    $syncData[$taxId] = ['tax_rate' => $request->input('tax_rates.' . $taxId)];
                }
                $product->taxes()->sync($syncData);
            }

            if ($openingStock > 0) {
                StockService::move($storeId, $product->id, $openingStock, 'opening', $product, 'Opening stock');
            }

            return $product;
        });

        $message = "Product added. Barcode: {$product->barcode}";
        if ($request->boolean('print_label')) {
            return redirect()->route('products.labels', ['ids' => $product->id])->with('success', $message);
        }

        return redirect()->route('all-products')->with('success', $message);
    }

    public function edit($id)
    {
        $product = Product::with('taxes')->findOrFail($id);
        $categories = Category::where('status', 'Active')->get();
        $subCategories = SubCategory::where('category_id', $product->category_id)->where('status', 'Active')->get();
        $units = MeasurementUnit::where('status', 'Active')->get();
        $vendors = Vendor::where('status', 'Active')->get();
        $taxes = Tax::where('status', 'Active')->get();

        // Read-only; the StoreStock store scope limits employees to their own store
        $storeStocks = StoreStock::with('store')->where('product_id', $product->id)->get();

        return view('edit-product', [
            'pageTitle' => 'Edit Product',
            'product' => $product,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'units' => $units,
            'vendors' => $vendors,
            'taxes' => $taxes,
            'storeStocks' => $storeStocks,
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
            'barcode' => ['nullable', 'string', 'max:48', 'regex:/^[A-Za-z0-9\-\.\/]+$/', 'unique:products,barcode,' . $id],
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0',
            'batch_expiry' => 'nullable|in:Yes,No',
            'expiry_date' => 'nullable|date|required_if:batch_expiry,Yes',
            'status' => 'required|in:Active,Inactive',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
        ]);

        // Stock is never edited here: it changes through Stock Adjustments / Transfers
        $productData = $request->except(['taxes', 'tax_rates', 'batch_expiry', 'stock_quantity', 'opening_stock', 'store_id', '_token', '_method']);
        $productData['batch_expiry_enabled'] = $request->batch_expiry === 'Yes';

        if (!$productData['batch_expiry_enabled']) {
            $productData['expiry_date'] = null;
        }

        // A product is never left without a barcode
        if (blank($productData['barcode'] ?? null)) {
            $productData['barcode'] = Product::generateBarcode($product->id);
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

    /** Stores the user can put opening stock into (employees: only their own). */
    private function stockStores()
    {
        $user = auth()->user();
        $query = Store::where('status', 'Active')->orderBy('name');

        if (!$user->canViewAllStores()) {
            $query->whereKey($user->store_id ?? 0);
        }

        return $query->get();
    }

    private function resolveStockStoreId(Request $request)
    {
        $user = $request->user();

        if (!$user->canViewAllStores()) {
            return $user->store_id ? (int) $user->store_id : null;
        }

        $storeId = $request->input('store_id') ?: session('store_filter');

        return $storeId && Store::where('status', 'Active')->whereKey($storeId)->exists() ? (int) $storeId : null;
    }
}
