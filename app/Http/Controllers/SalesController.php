<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index()
    {
        $customers = Customer::where('status', 'Active')->get();
        $discounts = \App\Models\Discount::where('status', 'Active')->get();
        $banks = \App\Models\Bank::where('status', 'Active')->get();
        
        $recentSales = Sale::with('customer')
            ->whereIn('status', ['completed', 'returned'])
            ->latest()
            ->take(10)
            ->get();
        $draftSales = Sale::with('customer')->where('status', 'draft')->latest()->get();

        return view('sales', [
            'pageTitle' => 'Sales',
            'customers' => $customers,
            'discounts' => $discounts,
            'banks' => $banks,
            'recentSales' => $recentSales,
            'draftSales' => $draftSales
        ]);
    }

    public function searchProducts(Request $request)
    {
        $query = $request->get('q');
        $products = Product::with(['subCategory.taxes'])
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->where('status', 'Active')
            ->limit(20)
            ->get();
        
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable', // Can be 'walk-in' or ID
            'customer_name' => 'required_if:customer_id,walk-in',
            'customer_phone' => 'required_if:customer_id,walk-in',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'status' => 'required|in:completed,draft,returned',
            'payment_method' => 'required|string',
            'bank_id' => 'nullable|exists:banks,id'
        ]);

        try {
            DB::beginTransaction();

            $status = $request->status;
            if ($status === 'completed' && $request->grand_total < 0) {
                $status = 'returned';
            }

            $customerId = $request->customer_id;

            // Handle Walk-in / New Customer Logic
            if ($customerId === 'walk-in' || !$customerId) {
                // Check if customer with phone exists to avoid duplicates? 
                // For now, let's create a new one as requested "customer k table mai bhi add hoga"
                // Or maybe findByPhone?
                $existingCustomer = Customer::where('phone', $request->customer_phone)->first();
                
                if ($existingCustomer) {
                    $customerId = $existingCustomer->id;
                } else {
                    $newCustomer = Customer::create([
                        'name' => $request->customer_name,
                        'phone' => $request->customer_phone,
                        'status' => 'Active',
                        // Add default code or other required fields if any
                        'code' => 'CUST-' . time(), 
                    ]);
                    $customerId = $newCustomer->id;
                }
            }

            $sale = Sale::create([
                'customer_id' => $customerId,
                'date' => $request->date,
                'reference_no' => $request->reference_no,
                'subtotal' => $request->subtotal ?? 0,
                'discount' => $request->discount ?? 0,
                'tax' => $request->tax ?? 0,
                'grand_total' => $request->grand_total,
                'status' => $status,
                'payment_method' => $request->payment_method,
                'bank_id' => $request->bank_id
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $purchasePrice = $product ? $product->purchase_price : 0;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'purchase_price' => $purchasePrice,
                    'total' => $item['total']
                ]);

                if (($status === 'completed' || $status === 'returned') && $product) {
                    $product->decrement('stock_quantity', $item['quantity']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => $request->status === 'draft' ? 'Order saved as draft.' : 'Sale completed successfully.',
                'sale_id' => $sale->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error processing sale: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $sale = Sale::with(['customer', 'items.product.subCategory.taxes'])->findOrFail($id);
        
        if ($sale->status === 'completed' || $sale->status === 'returned') {
            return redirect()->route('sales.index')->with('error', 'Completed/Returned sales cannot be edited.');
        }

        $customers = Customer::where('status', 'Active')->get();
        $discounts = \App\Models\Discount::where('status', 'Active')->get();
        $banks = \App\Models\Bank::where('status', 'Active')->get();

        $recentSales = Sale::with('customer')->where('status', 'completed')->latest()->take(10)->get();
        $draftSales = Sale::with('customer')->where('status', 'draft')->latest()->get();

        return view('sales', [
            'pageTitle' => 'Edit Sale',
            'customers' => $customers,
            'discounts' => $discounts,
            'banks' => $banks,
            'recentSales' => $recentSales,
            'draftSales' => $draftSales,
            'sale' => $sale
        ]);
    }

    public function update(Request $request, $id)
    {
         // Same logic as store, but updating
         // For simplicity, we can delete items and recreate, or update
         // Or just reuse store logic if we treat it as a new version? 
         // But usually we want to update the existing record.
         
         // Let's implement update logic similar to store
        $request->validate([
            'customer_id' => 'nullable',
            'customer_name' => 'required_if:customer_id,walk-in',
            'customer_phone' => 'required_if:customer_id,walk-in',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'status' => 'required|in:completed,draft,returned',
            'payment_method' => 'required|string',
            'bank_id' => 'nullable|exists:banks,id'
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::with('items')->findOrFail($id);
            
            // Restore stock if previous status was completed or returned
            if ($sale->status === 'completed' || $sale->status === 'returned') {
                foreach ($sale->items as $oldItem) {
                    $product = Product::find($oldItem->product_id);
                    if ($product) {
                        $product->increment('stock_quantity', $oldItem->quantity);
                    }
                }
            }

            $status = $request->status;
            if ($status === 'completed' && $request->grand_total < 0) {
                $status = 'returned';
            }

            $customerId = $request->customer_id;

            if ($customerId === 'walk-in' || !$customerId) {
                $existingCustomer = Customer::where('phone', $request->customer_phone)->first();
                if ($existingCustomer) {
                    $customerId = $existingCustomer->id;
                } else {
                    $newCustomer = Customer::create([
                        'name' => $request->customer_name,
                        'phone' => $request->customer_phone,
                        'status' => 'Active',
                        'code' => 'CUST-' . time(), 
                    ]);
                    $customerId = $newCustomer->id;
                }
            }

            $sale->update([
                'customer_id' => $customerId,
                'date' => $request->date,
                'reference_no' => $request->reference_no,
                'subtotal' => $request->subtotal ?? 0,
                'discount' => $request->discount ?? 0,
                'tax' => $request->tax ?? 0,
                'grand_total' => $request->grand_total,
                'status' => $status,
                'payment_method' => $request->payment_method,
                'bank_id' => $request->bank_id
            ]);

            // Sync items (Delete old, add new)
            $sale->items()->delete();
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $purchasePrice = $product ? $product->purchase_price : 0;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'purchase_price' => $purchasePrice,
                    'total' => $item['total']
                ]);

                if ($status === 'completed' || $status === 'returned') {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $product->decrement('stock_quantity', $item['quantity']);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => $request->status === 'draft' ? 'Draft updated.' : 'Sale completed successfully.',
                'sale_id' => $sale->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error updating sale: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $sale = Sale::with(['customer', 'items.product'])->findOrFail($id);
        $setting = \App\Models\Setting::first();
        return view('sales-slip', compact('sale', 'setting')); 
    }

    public function details($id)
    {
        $sale = Sale::with(['customer', 'items.product'])->findOrFail($id);
        return view('sales-details', compact('sale')); 
    }

    public function getSaleDetails($id)
    {
        $sale = Sale::with(['customer', 'items.product.subCategory.taxes'])->find($id);
        
        if (!$sale) {
            return response()->json(['success' => false, 'message' => 'Sale not found']);
        }

        return response()->json(['success' => true, 'sale' => $sale]);
    }
}
