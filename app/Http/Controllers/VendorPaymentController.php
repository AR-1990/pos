<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorPaymentController extends Controller
{
    public function index()
    {
        // Purchase carries the store scope, so these totals only cover what the user may see.
        $totals = Purchase::query()
            ->selectRaw('vendor_id, COUNT(*) as purchases_count, SUM(grand_total) as total_purchased, SUM(paid_amount) as total_paid, SUM(due_amount) as total_due')
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $vendors = Vendor::where(function ($q) use ($totals) {
            $q->where('status', 'Active')->orWhereIn('id', $totals->keys());
        })->orderBy('name')->get(['id', 'name', 'code', 'phone', 'status']);

        $vendorRows = $vendors->map(function ($v) use ($totals) {
            $t = $totals->get($v->id);

            return (object) [
                'id' => $v->id,
                'name' => $v->name,
                'code' => $v->code,
                'phone' => $v->phone,
                'status' => $v->status,
                'purchases_count' => (int) ($t->purchases_count ?? 0),
                'total_purchased' => (float) ($t->total_purchased ?? 0),
                'total_paid' => (float) ($t->total_paid ?? 0),
                'total_due' => (float) ($t->total_due ?? 0),
            ];
        })->sortByDesc('total_due')->values();

        $duePurchases = Purchase::with('store:id,name')
            ->where('due_amount', '>', 0)
            ->orderBy('date')->orderBy('id')
            ->get(['id', 'purchase_no', 'vendor_id', 'store_id', 'date', 'grand_total', 'due_amount'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'vendor_id' => $p->vendor_id,
                'label' => $p->purchase_no . ' (' . \Carbon\Carbon::parse($p->date)->format('d M Y') . ($p->store ? ', ' . $p->store->name : '') . ')',
                'due' => (float) $p->due_amount,
            ])->values();

        $paymentsQuery = VendorPayment::with(['vendor:id,name', 'purchase:id,purchase_no', 'store:id,name'])
            ->latest('date')->latest('id');
        $scoped = Store::scopedId();
        if ($scoped !== null) {
            $paymentsQuery->where('store_id', $scoped);
        }

        return view('vendor-payments.index', [
            'pageTitle' => 'Vendor Payments',
            'vendorRows' => $vendorRows,
            'duePurchases' => $duePurchases,
            'recentPayments' => $paymentsQuery->take(25)->get(),
            'banks' => Bank::where('status', 'Active')->orderBy('name')->get(['id', 'name', 'account_number']),
            'bankNames' => Bank::pluck('name', 'id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'amount' => 'required|numeric|gt:0|max:999999999',
            'method' => 'required|in:cash,card,bank_transfer',
            'bank_id' => ['nullable', 'required_if:method,bank_transfer', Rule::exists('banks', 'id')->where('status', 'Active')],
            'date' => 'required|date',
            'purchase_id' => 'nullable|integer',
            'note' => 'nullable|string|max:255',
        ], [
            'bank_id.required_if' => 'Select a bank for bank transfers.',
            'amount.gt' => 'Amount must be greater than zero.',
        ]);

        $amount = round((float) $data['amount'], 2);
        $bankId = $data['method'] === 'bank_transfer' ? (int) $data['bank_id'] : null;

        $result = DB::transaction(function () use ($data, $amount, $bankId) {
            // Global store scope keeps employees to their own store's purchases.
            $query = Purchase::where('vendor_id', $data['vendor_id'])->where('due_amount', '>', 0);

            if (!empty($data['purchase_id'])) {
                $purchases = $query->whereKey($data['purchase_id'])->lockForUpdate()->get();
                if ($purchases->isEmpty()) {
                    throw ValidationException::withMessages(['purchase_id' => 'That purchase has nothing due for this vendor.']);
                }
            } else {
                $purchases = $query->orderBy('date')->orderBy('id')->lockForUpdate()->get();
            }

            $totalDue = round((float) $purchases->sum('due_amount'), 2);
            if ($totalDue <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nothing is due for this vendor.']);
            }
            if ($amount > $totalDue) {
                throw ValidationException::withMessages(['amount' => 'Amount cannot be more than the balance due (' . number_format($totalDue, 2) . ').']);
            }

            $remaining = $amount;
            $allocations = [];
            foreach ($purchases as $purchase) {
                if ($remaining <= 0) {
                    break;
                }
                $apply = round(min($remaining, (float) $purchase->due_amount), 2);
                if ($apply <= 0) {
                    continue;
                }

                VendorPayment::create([
                    'vendor_id' => $purchase->vendor_id,
                    'purchase_id' => $purchase->id,
                    'store_id' => $purchase->store_id,
                    'user_id' => auth()->id(),
                    'date' => $data['date'],
                    'amount' => $apply,
                    'method' => $data['method'],
                    'bank_id' => $bankId,
                    'note' => $data['note'] ?? null,
                ]);

                $paid = round((float) $purchase->paid_amount + $apply, 2);
                $due = round((float) $purchase->grand_total - $paid, 2);
                $purchase->update([
                    'paid_amount' => $paid,
                    'due_amount' => max($due, 0),
                    'payment_status' => PurchaseController::paymentStatus($paid, $due),
                ]);

                $allocations[] = ['purchase_id' => $purchase->id, 'purchase_no' => $purchase->purchase_no, 'amount' => $apply];
                $remaining = round($remaining - $apply, 2);
            }

            $vendor = Vendor::find($data['vendor_id']);
            Audit::log(
                'vendor_payment.created',
                'Paid ' . number_format($amount, 2) . ' to ' . $vendor->name . ' against ' . implode(', ', array_column($allocations, 'purchase_no')),
                $vendor,
                ['amount' => $amount, 'method' => $data['method'], 'allocations' => $allocations],
                count(array_unique($purchases->pluck('store_id')->all())) === 1 ? $purchases->first()->store_id : null
            );

            return $allocations;
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment of ' . number_format($amount, 2) . ' recorded against ' . count($result) . ' purchase(s).',
            'allocations' => $result,
        ]);
    }
}
