<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Services\StockService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Purchase::with(['store:id,name', 'vendor:id,name'])->select('purchases.*');

            if ($request->filled('from_date')) {
                $query->whereDate('purchases.date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('purchases.date', '<=', $request->to_date);
            }
            if (in_array($request->payment_status, ['unpaid', 'partial', 'paid'], true)) {
                $query->where('purchases.payment_status', $request->payment_status);
            }

            $currency = optional(\App\Models\Setting::first())->currency ?? 'Rs';
            $money = fn ($v) => $currency . ' ' . number_format((float) $v, 2);

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('store_name', fn ($row) => e($row->store->name ?? '-'))
                ->addColumn('vendor_name', fn ($row) => e($row->vendor->name ?? '-'))
                ->editColumn('date', fn ($row) => \Carbon\Carbon::parse($row->date)->format('d M Y'))
                ->editColumn('vendor_invoice_no', fn ($row) => e($row->vendor_invoice_no ?: '-'))
                ->editColumn('grand_total', fn ($row) => $money($row->grand_total))
                ->editColumn('paid_amount', fn ($row) => $money($row->paid_amount))
                ->editColumn('due_amount', fn ($row) => $money($row->due_amount))
                ->editColumn('payment_status', fn ($row) => self::statusBadge($row->payment_status))
                ->addColumn('action', fn ($row) => '<a href="' . route('purchases.show', $row->id) . '" class="btn btn-primary btn-sm" title="View"><i class="fa-regular fa-eye"></i></a>')
                ->filterColumn('store_name', fn ($q, $kw) => $q->whereHas('store', fn ($s) => $s->where('name', 'like', "%{$kw}%")))
                ->filterColumn('vendor_name', fn ($q, $kw) => $q->whereHas('vendor', fn ($s) => $s->where('name', 'like', "%{$kw}%")))
                ->orderColumn('date', 'purchases.date $1, purchases.id $1')
                ->rawColumns(['payment_status', 'action'])
                ->make(true);
        }

        return view('purchases.index', ['pageTitle' => 'Purchases']);
    }

    public function create()
    {
        $user = auth()->user();

        return view('purchases.create', [
            'pageTitle' => 'New Purchase',
            'stores' => $this->purchasableStores(),
            'lockedStore' => $user->canViewAllStores() ? null : $user->store,
            'defaultStoreId' => $user->canViewAllStores() ? session('store_filter') : $user->store_id,
            'vendors' => Vendor::where('status', 'Active')->orderBy('name')->get(['id', 'name', 'code']),
            'banks' => Bank::where('status', 'Active')->orderBy('name')->get(['id', 'name', 'account_number']),
        ]);
    }

    public function searchProducts(Request $request)
    {
        $term = trim((string) $request->get('q', ''));
        $storeId = $this->resolveStoreId($request);

        if ($term === '') {
            return response()->json([]);
        }

        $products = Product::where('status', 'Active')
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            })
            // Exact barcode/code matches first so scanners pick the right item
            ->orderByRaw('CASE WHEN barcode = ? OR code = ? OR sku = ? THEN 0 ELSE 1 END', [$term, $term, $term])
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'code', 'sku', 'barcode', 'purchase_price']);

        return response()->json($products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'sku' => $p->sku,
            'barcode' => $p->barcode,
            'purchase_price' => round((float) $p->purchase_price, 2),
            'stock' => $storeId ? StockService::available($storeId, $p->id) : null,
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'nullable|integer',
            'vendor_id' => ['required', Rule::exists('vendors', 'id')->where('status', 'Active')],
            'vendor_invoice_no' => 'nullable|string|max:100',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('status', 'Active')],
            'items.*.quantity' => 'required|numeric|gt:0|max:9999999',
            'items.*.unit_cost' => 'required|numeric|min:0|max:999999999',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,bank_transfer',
            'bank_id' => ['nullable', Rule::exists('banks', 'id')->where('status', 'Active')],
            'note' => 'nullable|string|max:1000',
        ], [
            'items.required' => 'Add at least one product.',
            'items.*.product_id.exists' => 'One of the products is missing or inactive.',
            'items.*.quantity.gt' => 'Quantity must be greater than zero.',
        ]);

        $storeId = $this->resolveStoreId($request);
        if (!$storeId) {
            throw ValidationException::withMessages(['store_id' => $this->noStoreMessage()]);
        }

        // Merge duplicate product rows; totals are always computed here.
        $lines = [];
        foreach ($data['items'] as $row) {
            $pid = (int) $row['product_id'];
            $qty = round((float) $row['quantity'], 3);
            $lineTotal = round($qty * round((float) $row['unit_cost'], 2), 2);
            if (!isset($lines[$pid])) {
                $lines[$pid] = ['product_id' => $pid, 'quantity' => 0, 'total' => 0];
            }
            $lines[$pid]['quantity'] = round($lines[$pid]['quantity'] + $qty, 3);
            $lines[$pid]['total'] = round($lines[$pid]['total'] + $lineTotal, 2);
        }
        foreach ($lines as &$line) {
            $line['unit_cost'] = round($line['total'] / $line['quantity'], 2);
        }
        unset($line);

        $subtotal = round(array_sum(array_column($lines, 'total')), 2);
        $discount = round((float) ($data['discount'] ?? 0), 2);
        $tax = round((float) ($data['tax'] ?? 0), 2);
        $paid = round((float) ($data['paid_amount'] ?? 0), 2);

        if ($discount > $subtotal) {
            throw ValidationException::withMessages(['discount' => 'Discount cannot be more than the subtotal.']);
        }
        $grandTotal = round($subtotal - $discount + $tax, 2);
        if ($paid > $grandTotal) {
            throw ValidationException::withMessages(['paid_amount' => 'Paid amount cannot be more than the grand total (' . number_format($grandTotal, 2) . ').']);
        }

        $method = $paid > 0 ? ($data['payment_method'] ?? null) : null;
        if ($paid > 0 && !$method) {
            throw ValidationException::withMessages(['payment_method' => 'Select a payment method.']);
        }
        if ($method === 'bank_transfer' && empty($data['bank_id'])) {
            throw ValidationException::withMessages(['bank_id' => 'Select a bank for bank transfers.']);
        }
        $bankId = $method === 'bank_transfer' ? (int) $data['bank_id'] : null;

        $purchase = DB::transaction(function () use ($data, $storeId, $lines, $subtotal, $discount, $tax, $grandTotal, $paid, $method, $bankId) {
            $due = round($grandTotal - $paid, 2);

            $purchase = Purchase::create([
                'purchase_no' => 'TMP-' . uniqid('', true),
                'store_id' => $storeId,
                'vendor_id' => $data['vendor_id'],
                'user_id' => auth()->id(),
                'vendor_invoice_no' => $data['vendor_invoice_no'] ?? null,
                'date' => $data['date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'grand_total' => $grandTotal,
                'paid_amount' => $paid,
                'due_amount' => $due,
                'payment_status' => self::paymentStatus($paid, $due),
                'note' => $data['note'] ?? null,
            ]);
            $purchase->purchase_no = 'PUR-' . str_pad($purchase->id, 6, '0', STR_PAD_LEFT);
            $purchase->save();

            foreach ($lines as $line) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'total' => $line['total'],
                ]);

                StockService::move($storeId, $line['product_id'], $line['quantity'], 'purchase', $purchase, 'Purchase ' . $purchase->purchase_no);

                Product::whereKey($line['product_id'])->update(['purchase_price' => $line['unit_cost']]);
            }

            if ($paid > 0) {
                VendorPayment::create([
                    'vendor_id' => $purchase->vendor_id,
                    'purchase_id' => $purchase->id,
                    'store_id' => $storeId,
                    'user_id' => auth()->id(),
                    'date' => $data['date'],
                    'amount' => $paid,
                    'method' => $method,
                    'bank_id' => $bankId,
                    'note' => 'Paid at purchase',
                ]);
            }

            $vendorName = Vendor::whereKey($purchase->vendor_id)->value('name');
            Audit::log('purchase.created', "Purchase {$purchase->purchase_no} from {$vendorName}", $purchase, [
                'grand_total' => $grandTotal,
                'paid' => $paid,
                'items' => count($lines),
            ]);

            return $purchase;
        });

        return response()->json([
            'success' => true,
            'message' => 'Purchase ' . $purchase->purchase_no . ' saved.',
            'purchase_id' => $purchase->id,
            'redirect' => route('purchases.show', $purchase->id),
        ]);
    }

    public function show($id)
    {
        $query = Purchase::with(['store', 'vendor', 'user', 'items.product', 'payments' => fn ($q) => $q->orderBy('date')->orderBy('id')]);

        // Admins may open any store's purchase (e.g. right after saving one for another store)
        if (auth()->user()->canViewAllStores()) {
            $query->withoutGlobalScope('store');
        }

        $purchase = $query->findOrFail($id);
        $banks = Bank::whereIn('id', $purchase->payments->pluck('bank_id')->filter())->pluck('name', 'id');

        return view('purchases.show', [
            'pageTitle' => 'Purchase ' . $purchase->purchase_no,
            'purchase' => $purchase,
            'banks' => $banks,
        ]);
    }

    public static function paymentStatus(float $paid, float $due): string
    {
        if ($due <= 0) {
            return 'paid';
        }

        return $paid > 0 ? 'partial' : 'unpaid';
    }

    public static function statusBadge(?string $status): string
    {
        $class = ['paid' => 'bg-success', 'partial' => 'bg-warning', 'unpaid' => 'bg-danger'][$status] ?? 'bg-secondary';

        return '<span class="badge ' . $class . '">' . e(ucfirst((string) $status)) . '</span>';
    }

    private function purchasableStores()
    {
        $user = auth()->user();
        $query = Store::where('status', 'Active')->orderBy('name');

        if (!$user->canViewAllStores()) {
            $query->whereKey($user->store_id ?? 0);
        }

        return $query->get();
    }

    /**
     * Employees always buy for their own store. Admins pick one in the form,
     * falling back to the store chosen in the header.
     */
    private function resolveStoreId(Request $request)
    {
        $user = $request->user();

        if (!$user->canViewAllStores()) {
            return $user->store_id;
        }

        $storeId = $request->input('store_id') ?: session('store_filter');

        return $storeId && Store::where('status', 'Active')->whereKey($storeId)->exists() ? (int) $storeId : null;
    }

    private function noStoreMessage()
    {
        return auth()->user()->canViewAllStores()
            ? 'Please select a store for this purchase.'
            : 'You are not assigned to any store. Ask an admin to assign one.';
    }
}
