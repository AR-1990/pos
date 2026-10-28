<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Models\Store;
use App\Models\StoreStock;
use App\Services\StockService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalesController extends Controller
{
    public function index()
    {
        return view('sales', $this->posData() + ['pageTitle' => 'POS']);
    }

    public function edit($id)
    {
        $sale = Sale::with(['customer', 'items.product.subCategory.taxes'])->findOrFail($id);

        if ($sale->status !== 'draft') {
            return redirect()->route('sales.index')->with('error', 'Only held (draft) orders can be edited. Use Return or Void for completed sales.');
        }

        $stock = StoreStock::withoutGlobalScope('store')->where('store_id', $sale->store_id)
            ->whereIn('product_id', $sale->items->pluck('product_id'))->pluck('quantity', 'product_id');

        $resumeCart = $sale->items->map(fn ($i) => [
            'id' => $i->product_id,
            'name' => $i->product->name ?? 'Product #' . $i->product_id,
            'code' => $i->product->code ?? '',
            'price' => (float) ($i->product->selling_price ?? $i->price),
            'tax_rate' => $i->product ? $this->taxRate($i->product) : 0,
            'stock' => (float) ($stock[$i->product_id] ?? 0),
            'quantity' => (float) $i->quantity,
            'discount' => (float) $i->discount,
        ])->values();

        return view('sales', $this->posData($sale->store_id) + ['pageTitle' => 'Resume Order', 'sale' => $sale, 'resumeCart' => $resumeCart]);
    }

    private function posData($forceStoreId = null): array
    {
        $stores = $this->sellableStores();
        $storeId = $forceStoreId ?: $this->defaultStoreId($stores);
        $shift = $storeId ? Shift::currentFor(auth()->id(), $storeId) : null;

        return [
            'currentShift' => $shift ? ['id' => $shift->id, 'opened_at' => $shift->opened_at->format('h:i A'), 'store_id' => $shift->store_id] : null,
            'customers' => Customer::where('status', 'Active')->orderBy('name')->get(['id', 'name', 'phone']),
            'banks' => \App\Models\Bank::where('status', 'Active')->get(['id', 'name', 'account_number']),
            'stores' => $stores,
            'defaultStoreId' => $storeId,
            'recentSales' => Sale::with(['customer:id,name', 'store:id,name'])
                ->whereIn('status', ['completed', 'returned', 'void'])
                ->latest()->take(15)->get(),
            'draftSales' => Sale::with(['customer:id,name', 'store:id,name'])->where('status', 'draft')->latest()->get(),
        ];
    }

    public function searchProducts(Request $request)
    {
        $query = trim((string) $request->get('q'));
        $storeId = $this->resolveStoreId($request);

        $products = Product::with(['subCategory.taxes'])
            ->where('status', 'Active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            // Exact barcode / code matches first so a scanner + Enter picks the right item
            ->orderByRaw('CASE WHEN barcode = ? OR code = ? OR sku = ? THEN 0 ELSE 1 END', [$query, $query, $query])
            ->orderBy('name')
            ->limit(20)
            ->get();

        $stock = $storeId
            ? StoreStock::withoutGlobalScope('store')->where('store_id', $storeId)->whereIn('product_id', $products->pluck('id'))->pluck('quantity', 'product_id')
            : collect();

        return response()->json($products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'barcode' => $p->barcode,
            'price' => (float) $p->selling_price,
            'tax_rate' => $this->taxRate($p),
            'stock' => (float) ($stock[$p->id] ?? 0),
            'exact' => in_array($query, [$p->barcode, $p->code, $p->sku], true),
        ]));
    }

    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);
        if ($sale->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Completed sales cannot be changed. Use Return or Void.'], 422);
        }

        return $this->save($request, $sale);
    }

    /**
     * Create or update a sale. Prices, discounts, tax and totals are always computed here
     * from the database; the browser only sends products, quantities and payments.
     */
    private function save(Request $request, ?Sale $draft)
    {
        $data = $request->validate([
            'status' => 'required|in:completed,draft',
            'customer_id' => 'nullable',
            'customer_name' => 'required_if:customer_id,walk-in|nullable|string|max:255',
            'customer_phone' => 'required_if:customer_id,walk-in|nullable|string|max:30',
            'date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.method' => ['required', Rule::in(array_keys(SalePayment::METHODS))],
            'payments.*.amount' => 'required|numeric|gt:0',
            'payments.*.bank_id' => 'nullable|required_if:payments.*.method,bank_transfer|exists:banks,id',
            'payments.*.reference' => 'nullable|string|max:100',
        ], [
            'items.*.quantity.gt' => 'Quantity must be greater than zero.',
            'payments.*.bank_id.required_if' => 'Select the bank for the bank transfer payment.',
            'customer_name.required_if' => 'Enter the walk-in customer name.',
            'customer_phone.required_if' => 'Enter the walk-in customer phone.',
        ]);

        $storeId = $this->resolveStoreId($request, $draft?->store_id);
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => $this->noStoreMessage()], 422);
        }

        $completing = $data['status'] === 'completed';
        $user = $request->user();

        $shift = null;
        if ($completing) {
            $shift = Shift::currentFor($user->id, $storeId);
            if (!$shift) {
                return response()->json(['success' => false, 'code' => 'no_shift', 'message' => 'Open a shift (cash register) for this store before completing sales.'], 422);
            }
        }

        $totals = $this->computeTotals($data);

        $payments = collect($data['payments'] ?? []);
        $paid = round($payments->sum('amount'), 2);
        if ($completing && $paid - $totals['grand_total'] > 0.009) {
            return response()->json(['success' => false, 'message' => 'Payments (' . number_format($paid, 2) . ') are more than the bill (' . number_format($totals['grand_total'], 2) . '). Enter the amount applied; change is calculated at the till.'], 422);
        }
        $due = $completing ? round($totals['grand_total'] - $paid, 2) : 0;

        $isWalkIn = empty($data['customer_id']) || $data['customer_id'] === 'walk-in';
        if ($completing && $due > 0.009 && $isWalkIn) {
            return response()->json(['success' => false, 'message' => 'Credit (unpaid balance) is only allowed for a saved customer. Select a customer or take full payment.'], 422);
        }

        try {
            $sale = DB::transaction(function () use ($data, $draft, $storeId, $completing, $user, $shift, $totals, $payments, $paid, $due, $isWalkIn) {
                $customerId = $isWalkIn ? $this->walkInCustomer($data) : (int) $data['customer_id'];
                if (!$isWalkIn && !Customer::whereKey($customerId)->exists()) {
                    throw ValidationException::withMessages(['customer_id' => 'Customer not found.']);
                }

                $attributes = [
                    'store_id' => $storeId,
                    'customer_id' => $customerId,
                    'date' => $data['date'],
                    'reference_no' => $data['reference_no'] ?? null,
                    'subtotal' => $totals['subtotal'],
                    'discount' => $totals['discount'],
                    'discount_type' => $totals['discount_type'],
                    'discount_value' => $totals['discount_value'],
                    'tax' => $totals['tax'],
                    'grand_total' => $totals['grand_total'],
                    'paid_amount' => $completing ? $paid : 0,
                    'due_amount' => $due,
                    'payment_status' => !$completing ? 'due' : ($due <= 0.009 ? 'paid' : ($paid > 0 ? 'partial' : 'due')),
                    'status' => $data['status'],
                    'payment_method' => $payments->count() > 1 ? 'split' : ($payments->first()['method'] ?? ($completing ? 'credit' : 'cash')),
                    'bank_id' => $payments->firstWhere('method', 'bank_transfer')['bank_id'] ?? null,
                    'shift_id' => $shift?->id,
                ];

                if ($draft) {
                    $sale = Sale::whereKey($draft->id)->lockForUpdate()->first();
                    if ($sale->status !== 'draft') {
                        throw ValidationException::withMessages(['status' => 'This order was already completed.']);
                    }
                    $sale->update($attributes);
                    $sale->items()->delete();
                } else {
                    $sale = Sale::create($attributes + ['user_id' => $user->id]);
                }

                if ($completing) {
                    $sale->update(['invoice_no' => Store::nextInvoiceNo($storeId), 'user_id' => $user->id]);
                }

                foreach ($totals['lines'] as $line) {
                    $sale->items()->create($line);
                    if ($completing) {
                        StockService::move($storeId, $line['product_id'], -$line['quantity'], 'sale', $sale);
                    }
                }

                if ($completing) {
                    foreach ($payments as $p) {
                        $sale->payments()->create([
                            'store_id' => $storeId,
                            'shift_id' => $shift->id,
                            'user_id' => $user->id,
                            'method' => $p['method'],
                            'amount' => round($p['amount'], 2),
                            'bank_id' => $p['method'] === 'bank_transfer' ? $p['bank_id'] : null,
                            'reference' => $p['reference'] ?? null,
                        ]);
                    }
                    Audit::log('sale.completed', "Sale {$sale->invoice_no} completed for " . number_format($sale->grand_total, 2), $sale, ['due' => $due]);
                }

                return $sale;
            });
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $completing ? "Sale {$sale->invoice_no} completed." : 'Order held as draft.',
            'sale_id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'due' => $due,
        ]);
    }

    /**
     * Server-side pricing: price from the product, line discount capped at the line amount,
     * tax from the product's sub-category taxes, invoice discount fixed or percent.
     */
    private function computeTotals(array $data): array
    {
        // Merge repeated products into one line
        $wanted = [];
        foreach ($data['items'] as $item) {
            $pid = (int) $item['product_id'];
            $wanted[$pid]['quantity'] = ($wanted[$pid]['quantity'] ?? 0) + (float) $item['quantity'];
            $wanted[$pid]['discount'] = ($wanted[$pid]['discount'] ?? 0) + (float) ($item['discount'] ?? 0);
        }

        $products = Product::with('subCategory.taxes')->whereIn('id', array_keys($wanted))->get()->keyBy('id');

        $lines = [];
        $subtotal = $lineDiscounts = $taxTotal = 0;
        foreach ($wanted as $pid => $w) {
            $product = $products[$pid];
            if ($product->status !== 'Active') {
                throw ValidationException::withMessages(['items' => "{$product->name} is not active."]);
            }
            $qty = round($w['quantity'], 3);
            $price = (float) $product->selling_price;
            $gross = round($qty * $price, 2);
            $lineDiscount = round(min($w['discount'], $gross), 2);
            $lineTax = round(($gross - $lineDiscount) * $this->taxRate($product) / 100, 2);

            $subtotal += $gross;
            $lineDiscounts += $lineDiscount;
            $taxTotal += $lineTax;

            $lines[] = [
                'product_id' => $pid,
                'quantity' => $qty,
                'price' => $price,
                'discount' => $lineDiscount,
                'tax' => $lineTax,
                'purchase_price' => (float) $product->purchase_price,
                'total' => round($gross - $lineDiscount + $lineTax, 2),
            ];
        }

        $type = $data['discount_type'] ?? 'fixed';
        $value = (float) ($data['discount_value'] ?? 0);
        $base = $subtotal - $lineDiscounts;
        if ($type === 'percent' && $value > 100) {
            throw ValidationException::withMessages(['discount_value' => 'Discount cannot be more than 100%.']);
        }
        $invoiceDiscount = round($type === 'percent' ? $base * $value / 100 : $value, 2);
        if ($invoiceDiscount > $base + 0.001) {
            throw ValidationException::withMessages(['discount_value' => 'Discount cannot be more than the bill.']);
        }

        return [
            'lines' => $lines,
            'subtotal' => round($subtotal, 2),
            'discount' => round($lineDiscounts + $invoiceDiscount, 2),
            'discount_type' => $type,
            'discount_value' => $value,
            'tax' => round($taxTotal, 2),
            'grand_total' => round($base - $invoiceDiscount + $taxTotal, 2),
        ];
    }

    /**
     * Partial or full return against a completed invoice.
     */
    public function storeReturn(Request $request, $id)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0',
            'refund_method' => ['required', Rule::in(array_keys(SalePayment::METHODS))],
            'bank_id' => 'nullable|required_if:refund_method,bank_transfer|exists:banks,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        try {
            $return = DB::transaction(function () use ($data, $id, $user) {
                $original = Sale::with('items.product')->whereKey($id)->lockForUpdate()->firstOrFail();
                if ($original->status !== 'completed') {
                    throw ValidationException::withMessages(['sale' => 'Only completed sales can be returned.']);
                }

                $shift = Shift::currentFor($user->id, $original->store_id);
                if (!$shift) {
                    throw ValidationException::withMessages(['shift' => 'Open a shift at ' . ($original->store->name ?? 'this store') . ' before processing returns.']);
                }

                $lines = [];
                foreach ($data['items'] as $req) {
                    $qty = round((float) $req['quantity'], 3);
                    if ($qty <= 0) {
                        continue;
                    }
                    $item = $original->items->firstWhere('id', (int) $req['sale_item_id']);
                    if (!$item) {
                        throw ValidationException::withMessages(['items' => 'Item does not belong to this sale.']);
                    }
                    $returnable = round($item->quantity - $item->returned_qty, 3);
                    if ($qty > $returnable + 0.0001) {
                        throw ValidationException::withMessages(['items' => "Only {$returnable} of {$item->product->name} can still be returned."]);
                    }
                    $lines[] = [$item, $qty];
                }
                if (!$lines) {
                    throw ValidationException::withMessages(['items' => 'Enter a quantity for at least one item.']);
                }

                // Share of the invoice-level discount, so refunds match what was actually paid
                $itemsNet = max(0.01, $original->items->sum(fn ($i) => $i->total));
                $invoiceShare = max(0, ($original->subtotal - $original->items->sum('discount') + $original->tax) - $original->grand_total);

                $subtotal = $discount = $tax = $refund = 0;
                $returnItems = [];
                foreach ($lines as [$item, $qty]) {
                    $ratio = $qty / $item->quantity;
                    $lineTotal = round($item->total * $ratio, 2);
                    $lineInvoiceDiscount = round($invoiceShare * ($item->total / $itemsNet) * $ratio, 2);
                    $subtotal += round($item->price * $qty, 2);
                    $discount += round($item->discount * $ratio, 2) + $lineInvoiceDiscount;
                    $tax += round($item->tax * $ratio, 2);
                    $refund += $lineTotal - $lineInvoiceDiscount;
                    $returnItems[] = [
                        'product_id' => $item->product_id,
                        'quantity' => -$qty,
                        'price' => $item->price,
                        'discount' => round($item->discount * $ratio, 2),
                        'tax' => round($item->tax * $ratio, 2),
                        'purchase_price' => $item->purchase_price,
                        'total' => -$lineTotal,
                    ];
                    $item->increment('returned_qty', $qty);
                }
                $refund = round($refund, 2);

                // Unpaid balance on the original is reduced first; only the rest is paid back
                $dueReduction = min($refund, (float) $original->due_amount);
                $cashBack = round($refund - $dueReduction, 2);

                $return = Sale::create([
                    'store_id' => $original->store_id,
                    'user_id' => $user->id,
                    'invoice_no' => Store::nextInvoiceNo($original->store_id),
                    'return_of_sale_id' => $original->id,
                    'shift_id' => $shift->id,
                    'customer_id' => $original->customer_id,
                    'date' => now()->toDateString(),
                    'reference_no' => $data['reason'] ?? null,
                    'subtotal' => -round($subtotal, 2),
                    'discount' => round($discount, 2),
                    'tax' => -round($tax, 2),
                    'grand_total' => -$refund,
                    'paid_amount' => -$cashBack,
                    'due_amount' => 0,
                    'payment_status' => 'paid',
                    'status' => 'returned',
                    'payment_method' => $data['refund_method'],
                    'bank_id' => $data['bank_id'] ?? null,
                ]);

                foreach ($returnItems as $ri) {
                    $return->items()->create($ri);
                    StockService::move($original->store_id, $ri['product_id'], -$ri['quantity'], 'return', $return, 'Return of ' . $original->number);
                }

                if ($cashBack > 0) {
                    $return->payments()->create([
                        'store_id' => $original->store_id,
                        'shift_id' => $shift->id,
                        'user_id' => $user->id,
                        'method' => $data['refund_method'],
                        'amount' => -$cashBack,
                        'bank_id' => $data['refund_method'] === 'bank_transfer' ? $data['bank_id'] : null,
                    ]);
                }
                if ($dueReduction > 0) {
                    $newDue = round($original->due_amount - $dueReduction, 2);
                    $original->update(['due_amount' => $newDue, 'payment_status' => $newDue <= 0.009 ? 'paid' : 'partial']);
                }

                Audit::log('sale.returned', "Return {$return->invoice_no} against {$original->number}, refund " . number_format($cashBack, 2), $return, ['original_sale_id' => $original->id, 'due_reduced' => $dueReduction, 'reason' => $data['reason'] ?? null]);

                return $return;
            });
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['success' => true, 'message' => "Return {$return->invoice_no} saved. Refund " . number_format(abs($return->paid_amount), 2) . '.', 'sale_id' => $return->id]);
    }

    /**
     * Cancel a completed sale: stock goes back, payments are reversed.
     */
    public function void(Request $request, $id)
    {
        $data = $request->validate(['reason' => 'required|string|max:255']);
        $user = $request->user();

        try {
            $sale = DB::transaction(function () use ($id, $data, $user) {
                $sale = Sale::with(['items', 'payments'])->whereKey($id)->lockForUpdate()->firstOrFail();
                if ($sale->status !== 'completed') {
                    throw ValidationException::withMessages(['sale' => 'Only completed sales can be voided.']);
                }
                if ($sale->returns()->exists()) {
                    throw ValidationException::withMessages(['sale' => 'This sale has returns against it and cannot be voided. Return the remaining items instead.']);
                }

                $shift = Shift::currentFor($user->id, $sale->store_id);
                foreach ($sale->items as $item) {
                    StockService::move($sale->store_id, $item->product_id, (float) $item->quantity, 'void', $sale, 'Void ' . $sale->number);
                }
                foreach ($sale->payments as $p) {
                    $sale->payments()->create([
                        'store_id' => $sale->store_id,
                        'shift_id' => $shift?->id ?? $p->shift_id,
                        'user_id' => $user->id,
                        'method' => $p->method,
                        'amount' => -$p->amount,
                        'bank_id' => $p->bank_id,
                        'reference' => 'Void reversal',
                    ]);
                }

                $sale->update([
                    'status' => 'void',
                    'due_amount' => 0,
                    'voided_at' => now(),
                    'voided_by' => $user->id,
                    'void_reason' => $data['reason'],
                ]);
                Audit::log('sale.voided', "Sale {$sale->number} voided: {$data['reason']}", $sale, ['grand_total' => $sale->grand_total]);

                return $sale;
            });
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['success' => true, 'message' => "Sale {$sale->number} voided. Stock restored."]);
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);
        if ($sale->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Only held orders can be deleted. Void completed sales instead.'], 422);
        }
        $sale->items()->delete();
        $sale->delete();
        Audit::log('sale.draft_deleted', "Held order #{$id} deleted", null, [], $sale->store_id);

        return response()->json(['success' => true, 'message' => 'Held order deleted.']);
    }

    public function show($id)
    {
        $sale = Sale::with(['customer', 'store', 'user', 'items.product', 'payments.bank', 'returnOf'])->findOrFail($id);
        $setting = \App\Models\Setting::first();
        return view('sales-slip', compact('sale', 'setting'));
    }

    public function details($id)
    {
        $sale = Sale::with(['customer', 'store', 'user', 'items.product', 'payments.bank', 'returnOf', 'returns', 'voidedBy'])->findOrFail($id);
        return view('sales-details', compact('sale'));
    }

    /**
     * Sale lookup for the POS (view + returns). Accepts an id or an invoice number.
     */
    public function getSaleDetails($id)
    {
        $sale = Sale::with(['customer', 'store', 'items.product', 'payments'])
            ->where(fn ($q) => $q->where('invoice_no', $id)->orWhere('id', ctype_digit((string) $id) ? (int) $id : 0))
            ->first();

        if (!$sale) {
            return response()->json(['success' => false, 'message' => 'Sale not found'], 404);
        }

        $sale->items->each(fn ($i) => $i->setAttribute('returnable', max(0, round($i->quantity - $i->returned_qty, 3))));

        return response()->json(['success' => true, 'sale' => $sale]);
    }

    private function walkInCustomer(array $data): int
    {
        $existing = Customer::where('phone', $data['customer_phone'])->first();
        if ($existing) {
            return $existing->id;
        }

        return Customer::create([
            'name' => $data['customer_name'],
            'phone' => $data['customer_phone'],
            'status' => 'Active',
            'code' => 'CUST-' . now()->format('ymdHis') . random_int(10, 99),
        ])->id;
    }

    private function taxRate(Product $product): float
    {
        return (float) ($product->subCategory?->taxes->sum(fn ($t) => (float) $t->pivot->tax_rate) ?? 0);
    }

    /**
     * Stores the current user is allowed to sell for.
     */
    private function sellableStores()
    {
        $user = auth()->user();
        $query = Store::where('status', 'Active')->orderBy('name');

        if (!$user->canViewAllStores()) {
            $query->whereKey($user->store_id ?? 0);
        }

        return $query->get();
    }

    private function defaultStoreId($stores)
    {
        $preferred = session('store_filter') ?: auth()->user()->store_id;
        return $stores->firstWhere('id', $preferred)?->id ?? ($stores->count() === 1 ? $stores->first()->id : null);
    }

    /**
     * Employees always sell for their own store. Admins pick one in the POS,
     * falling back to the store chosen in the header, then the sale's current store.
     */
    private function resolveStoreId(Request $request, $fallback = null)
    {
        $user = $request->user();

        if (!$user->canViewAllStores()) {
            return $user->store_id;
        }

        $storeId = $request->input('store_id') ?: session('store_filter') ?: $fallback;

        return $storeId && Store::where('status', 'Active')->whereKey($storeId)->exists() ? (int) $storeId : null;
    }

    private function noStoreMessage()
    {
        return auth()->user()->canViewAllStores()
            ? 'Please select a store for this sale.'
            : 'You are not assigned to any store. Ask an admin to assign one.';
    }
}
