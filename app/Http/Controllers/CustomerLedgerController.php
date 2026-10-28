<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class CustomerLedgerController extends Controller
{
    /**
     * Customers with outstanding credit (udhaar). All figures come through the
     * Sale model so the store scope applies (employees see only their store).
     */
    public function index(Request $request)
    {
        $currency = $this->currency();

        if ($request->ajax()) {
            $lastPayment = SalePayment::query()
                ->selectRaw('MAX(sale_payments.created_at)')
                ->join('sales as ps', 'ps.id', '=', 'sale_payments.sale_id')
                ->whereColumn('ps.customer_id', 'customers.id')
                ->where('sale_payments.amount', '>', 0);

            $query = Sale::query()
                ->join('customers', 'customers.id', '=', 'sales.customer_id')
                ->where('sales.status', 'completed')
                ->where('sales.due_amount', '>', 0)
                ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.code')
                ->select([
                    'customers.id as customer_id',
                    'customers.name',
                    'customers.phone',
                    'customers.code',
                ])
                ->selectRaw('SUM(sales.due_amount) AS total_due')
                ->selectRaw('COUNT(sales.id) AS invoices_due')
                ->selectRaw('MIN(sales.date) AS oldest_due')
                ->selectSub($lastPayment, 'last_payment');

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', fn ($row) => '<a href="' . route('customer-ledger.show', $row->customer_id) . '" class="fw-semibold text-decoration-none">' . e($row->name) . '</a>'
                    . ($row->code ? '<div class="small text-muted">' . e($row->code) . '</div>' : ''))
                ->editColumn('phone', fn ($row) => e($row->phone ?? '-'))
                ->editColumn('total_due', fn ($row) => '<span class="fw-semibold text-danger">' . $currency . ' ' . number_format((float) $row->total_due, 2) . '</span>')
                ->editColumn('oldest_due', function ($row) {
                    if (!$row->oldest_due) {
                        return '-';
                    }
                    $d = Carbon::parse($row->oldest_due);
                    $days = (int) $d->diffInDays(now()->startOfDay());
                    return $d->format('d M Y') . ' <span class="small ' . ($days > 30 ? 'text-danger' : 'text-muted') . '">(' . $days . 'd)</span>';
                })
                ->editColumn('last_payment', fn ($row) => $row->last_payment ? Carbon::parse($row->last_payment)->format('d M Y') : '<span class="text-muted">Never</span>')
                ->addColumn('action', fn ($row) => '<a href="' . route('customer-ledger.show', $row->customer_id) . '" class="btn btn-primary btn-sm" title="Open ledger"><i class="fa-solid fa-book"></i></a>')
                ->filterColumn('name', fn ($q, $kw) => $q->where(fn ($w) => $w->where('customers.name', 'like', "%{$kw}%")->orWhere('customers.code', 'like', "%{$kw}%")))
                ->filterColumn('phone', fn ($q, $kw) => $q->where('customers.phone', 'like', "%{$kw}%"))
                ->orderColumn('total_due', 'total_due $1')
                ->orderColumn('invoices_due', 'invoices_due $1')
                ->orderColumn('oldest_due', 'oldest_due $1')
                ->orderColumn('last_payment', 'last_payment $1')
                ->rawColumns(['name', 'total_due', 'oldest_due', 'last_payment', 'action'])
                ->make(true);
        }

        $totals = Sale::query()
            ->where('status', 'completed')
            ->where('due_amount', '>', 0)
            ->whereNotNull('customer_id')
            ->selectRaw('COALESCE(SUM(due_amount), 0) AS receivable, COUNT(*) AS invoices, COUNT(DISTINCT customer_id) AS customers, MIN(date) AS oldest')
            ->first();

        return view('customer-ledger.index', [
            'pageTitle' => 'Customer Credit',
            'totals' => $totals,
            'currency' => $currency,
        ]);
    }

    public function show(Request $request, Customer $customer)
    {
        $sales = Sale::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['completed', 'returned'])
            ->with(['returnOf:id,invoice_no', 'store:id,name'])
            ->orderBy('date')->orderBy('id')
            ->get();

        $payments = SalePayment::query()
            ->whereIn('sale_id', $sales->pluck('id'))
            ->with(['sale:id,invoice_no', 'bank:id,name'])
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $customerPayments = CustomerPayment::withoutGlobalScope('store')
            ->whereIn('id', $payments->pluck('customer_payment_id')->filter()->unique())
            ->get()->keyBy('id');

        $entries = collect();
        foreach ($sales as $sale) {
            $total = (float) $sale->grand_total;
            if ($sale->status === 'completed') {
                $entries->push([
                    'sort' => $sale->date . ' ' . $sale->created_at?->format('H:i:s'), 'seq' => 1, 'id' => $sale->id,
                    'date' => $sale->date, 'type' => 'Sale', 'ref' => $sale->number,
                    'details' => 'Invoice' . ($sale->store ? ' at ' . $sale->store->name : ''),
                    'debit' => max($total, 0), 'credit' => max(-$total, 0),
                ]);
            } else {
                $entries->push([
                    'sort' => $sale->date . ' ' . $sale->created_at?->format('H:i:s'), 'seq' => 2, 'id' => $sale->id,
                    'date' => $sale->date, 'type' => 'Return', 'ref' => $sale->number,
                    'details' => $sale->returnOf ? 'Return of ' . $sale->returnOf->number : 'Return',
                    'debit' => max($total, 0), 'credit' => abs(min($total, 0)),
                ]);
            }
        }

        // Till payments: one row each. Credit collections: one row per customer payment.
        foreach ($payments->whereNull('customer_payment_id') as $p) {
            $entries->push($this->paymentEntry($p->created_at, $p->id, 'Payment', $p->sale?->number,
                (SalePayment::METHODS[$p->method] ?? $p->method) . ($p->bank ? ' (' . $p->bank->name . ')' : '') . ($p->amount < 0 ? ' refund' : ' at till'),
                (float) $p->amount));
        }
        foreach ($payments->whereNotNull('customer_payment_id')->groupBy('customer_payment_id') as $cpId => $group) {
            $cp = $customerPayments->get($cpId);
            $first = $group->first();
            $entries->push($this->paymentEntry($cp?->created_at ?? $first->created_at, (int) $cpId, 'Credit received', 'RCPT-' . $cpId,
                (SalePayment::METHODS[$first->method] ?? $first->method) . ' against ' . $group->map(fn ($p) => $p->sale?->number)->filter()->unique()->implode(', ')
                    . ($cp?->note ? ' - ' . $cp->note : ''),
                (float) $group->sum('amount'), $cpId));
        }

        $balance = 0;
        $entries = $entries->sortBy([['sort', 'asc'], ['seq', 'asc'], ['id', 'asc']])->values()->map(function ($e) use (&$balance) {
            $balance = round($balance + $e['debit'] - $e['credit'], 2);
            $e['balance'] = $balance;
            return $e;
        });

        $unpaid = $sales->where('status', 'completed')->where('due_amount', '>', 0)->values();
        $totalDue = round((float) $unpaid->sum('due_amount'), 2);

        return view('customer-ledger.show', [
            'pageTitle' => 'Customer Ledger',
            'customer' => $customer,
            'entries' => $entries,
            'unpaid' => $unpaid,
            'totalDue' => $totalDue,
            'totals' => [
                'debit' => round($entries->sum('debit'), 2),
                'credit' => round($entries->sum('credit'), 2),
            ],
            'banks' => Bank::where('status', 'Active')->orderBy('name')->get(['id', 'name']),
            'methods' => SalePayment::METHODS,
            'currency' => $this->currency(),
        ]);
    }

    public function storePayment(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|gt:0|max:99999999',
            'method' => 'required|in:' . implode(',', array_keys(SalePayment::METHODS)),
            'bank_id' => 'nullable|required_if:method,bank_transfer|exists:banks,id',
            'note' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $amount = round((float) $data['amount'], 2);
        $bankId = $data['method'] === 'bank_transfer' ? (int) $data['bank_id'] : null;

        $payment = DB::transaction(function () use ($customer, $user, $amount, $data, $bankId) {
            // Oldest dues first; the store scope keeps employees to their own store's invoices
            $dueSales = Sale::query()
                ->where('customer_id', $customer->id)
                ->where('status', 'completed')
                ->where('due_amount', '>', 0)
                ->orderBy('date')->orderBy('id')
                ->lockForUpdate()
                ->get();

            $totalDue = round((float) $dueSales->sum('due_amount'), 2);
            if ($totalDue <= 0) {
                throw ValidationException::withMessages(['amount' => 'This customer has no outstanding balance.']);
            }
            if ($amount > $totalDue) {
                throw ValidationException::withMessages(['amount' => 'Amount cannot exceed the total due of ' . number_format($totalDue, 2) . '.']);
            }

            $storeId = $user->canViewAllStores() ? (int) $dueSales->first()->store_id : (int) $user->store_id;
            $shiftIds = [];
            $shiftFor = function ($sid) use (&$shiftIds, $user) {
                if (!array_key_exists($sid, $shiftIds)) {
                    $shiftIds[$sid] = Shift::currentFor($user->id, $sid)?->id;
                }
                return $shiftIds[$sid];
            };

            $payment = CustomerPayment::create([
                'customer_id' => $customer->id,
                'store_id' => $storeId,
                'shift_id' => $shiftFor($storeId),
                'user_id' => $user->id,
                'date' => now()->toDateString(),
                'amount' => $amount,
                'method' => $data['method'],
                'bank_id' => $bankId,
                'note' => $data['note'] ?? null,
            ]);

            $remaining = $amount;
            $allocations = [];
            foreach ($dueSales as $sale) {
                if ($remaining <= 0) {
                    break;
                }
                $due = round((float) $sale->due_amount, 2);
                $apply = round(min($due, $remaining), 2);

                SalePayment::create([
                    'sale_id' => $sale->id,
                    'store_id' => $sale->store_id,
                    'shift_id' => $shiftFor((int) $sale->store_id),
                    'user_id' => $user->id,
                    'customer_payment_id' => $payment->id,
                    'method' => $data['method'],
                    'amount' => $apply,
                    'bank_id' => $bankId,
                ]);

                $newDue = round($due - $apply, 2);
                $sale->update([
                    'paid_amount' => round((float) $sale->paid_amount + $apply, 2),
                    'due_amount' => $newDue,
                    'payment_status' => $newDue <= 0 ? 'paid' : 'partial',
                ]);

                $allocations[] = ['sale_id' => $sale->id, 'invoice' => $sale->number, 'amount' => $apply, 'due_after' => $newDue];
                $remaining = round($remaining - $apply, 2);
            }

            Audit::log('customer_payment.received',
                "Received " . number_format($amount, 2) . " from {$customer->name} (payment #{$payment->id})",
                $payment, ['customer_id' => $customer->id, 'method' => $data['method'], 'allocations' => $allocations]);

            return $payment;
        });

        $message = 'Payment of ' . $this->currency() . ' ' . number_format($amount, 2) . ' received.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'payment_id' => $payment->id,
                'receipt_url' => route('customer-payments.receipt', $payment->id),
                'due' => $customer->dueAmount(),
            ]);
        }

        return redirect()->route('customer-ledger.show', $customer->id)->with('success', $message);
    }

    public function receipt(Request $request, $id)
    {
        $payment = CustomerPayment::with(['customer', 'user:id,name', 'store', 'allocations.sale:id,invoice_no,date,grand_total,due_amount'])
            ->findOrFail($id);

        return view('customer-ledger.receipt', [
            'payment' => $payment,
            'bank' => $payment->bank_id ? Bank::find($payment->bank_id) : null,
            'methods' => SalePayment::METHODS,
            'balance' => $payment->customer->dueAmount(),
            'setting' => \App\Models\Setting::first(),
            'currency' => $this->currency(),
        ]);
    }

    private function paymentEntry($at, int $id, string $type, ?string $ref, string $details, float $amount, $cpId = null): array
    {
        $at = $at ? Carbon::parse($at) : now();

        return [
            'sort' => $at->format('Y-m-d H:i:s'), 'seq' => 3, 'id' => $id,
            'date' => $at->toDateString(), 'type' => $type, 'ref' => $ref ?? '-', 'details' => $details,
            'debit' => $amount < 0 ? abs($amount) : 0, 'credit' => $amount > 0 ? $amount : 0,
            'receipt_id' => $cpId,
        ];
    }

    private function currency(): string
    {
        return optional(\App\Models\Setting::first())->currency ?? 'Rs';
    }
}
