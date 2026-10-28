<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($request->ajax()) {
            $currency = $this->currency();
            $query = Shift::query()
                ->with(['store:id,name', 'user:id,name'])
                ->select('shifts.*');

            if ($request->filled('status') && in_array($request->status, ['open', 'closed'], true)) {
                $query->where('shifts.status', $request->status);
            }

            $money = fn ($v) => $v === null ? '<span class="text-muted">-</span>' : $currency . ' ' . number_format((float) $v, 2);

            return DataTables::of($query)
                ->addColumn('store_name', fn ($row) => e($row->store->name ?? '-'))
                ->addColumn('cashier', fn ($row) => e($row->user->name ?? '-'))
                ->editColumn('id', fn ($row) => '#' . $row->id)
                ->editColumn('opened_at', fn ($row) => $row->opened_at?->format('d M Y h:i A'))
                ->editColumn('closed_at', fn ($row) => $row->closed_at?->format('d M Y h:i A') ?? '<span class="text-muted">-</span>')
                ->editColumn('opening_cash', fn ($row) => $money($row->opening_cash))
                ->editColumn('expected_cash', fn ($row) => $money($row->status === 'open' ? $row->expectedCash() : $row->expected_cash))
                ->editColumn('closing_cash', fn ($row) => $money($row->closing_cash))
                ->editColumn('difference', function ($row) use ($money) {
                    if ($row->difference === null) {
                        return $money(null);
                    }
                    $class = (float) $row->difference < 0 ? 'text-danger fw-semibold' : ((float) $row->difference > 0 ? 'text-success fw-semibold' : '');
                    return '<span class="' . $class . '">' . $money($row->difference) . '</span>';
                })
                ->editColumn('status', fn ($row) => $row->status === 'open'
                    ? '<span class="badge bg-success">Open</span>'
                    : '<span class="badge bg-secondary">Closed</span>')
                ->addColumn('action', fn ($row) => '<a href="' . route('shifts.show', $row->id) . '" class="btn btn-primary btn-sm" title="Z-report"><i class="fa-solid fa-file-invoice"></i></a>')
                ->filterColumn('store_name', fn ($q, $kw) => $q->whereHas('store', fn ($s) => $s->where('name', 'like', "%{$kw}%")))
                ->filterColumn('cashier', fn ($q, $kw) => $q->whereHas('user', fn ($s) => $s->where('name', 'like', "%{$kw}%")))
                ->rawColumns(['closed_at', 'opening_cash', 'expected_cash', 'closing_cash', 'difference', 'status', 'action'])
                ->make(true);
        }

        $storeId = $this->resolveStoreId($request);
        $shift = $this->openShiftFor($user, $storeId);
        $summary = $shift ? $this->liveSummary($shift) : null;

        return view('shifts.index', [
            'pageTitle' => 'Cash Register Shifts',
            'shift' => $shift,
            'summary' => $summary,
            'stores' => $user->canViewAllStores() ? Store::where('status', 'Active')->orderBy('name')->get(['id', 'name']) : collect(),
            'storeId' => $storeId,
            'currency' => $this->currency(),
        ]);
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'opening_cash' => 'required|numeric|min:0|max:99999999',
            'store_id' => 'nullable|integer',
            'note' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $storeId = $this->resolveStoreId($request);
        if (!$storeId) {
            return $this->fail($request, $user->canViewAllStores()
                ? 'Please select a store to open the shift at.'
                : 'You are not assigned to any store. Ask an admin to assign one.');
        }

        $shift = DB::transaction(function () use ($user, $storeId, $data) {
            // Serialise opens for this user so two clicks can't create two shifts
            User::whereKey($user->id)->lockForUpdate()->first();

            if (Shift::currentFor($user->id, $storeId)) {
                return null;
            }

            $shift = Shift::create([
                'store_id' => $storeId,
                'user_id' => $user->id,
                'opened_at' => now(),
                'opening_cash' => round((float) $data['opening_cash'], 2),
                'status' => 'open',
                'note' => $data['note'] ?? null,
            ]);

            Audit::log('shift.opened', "Shift #{$shift->id} opened with opening cash " . number_format($shift->opening_cash, 2), $shift, [
                'opening_cash' => (float) $shift->opening_cash,
            ]);

            return $shift;
        });

        if (!$shift) {
            return $this->fail($request, 'You already have an open shift at this store. Close it before opening a new one.');
        }

        return $this->ok($request, 'Shift opened.', ['shift' => $this->shiftJson($shift)]);
    }

    public function close(Request $request, $id)
    {
        $data = $request->validate([
            'closing_cash' => 'required|numeric|min:0|max:99999999',
            'note' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $result = DB::transaction(function () use ($id, $user, $data) {
            $shift = Shift::withoutGlobalScope('store')->whereKey($id)->lockForUpdate()->firstOrFail();

            if ((int) $shift->user_id !== (int) $user->id) {
                $canCloseOthers = $user->hasPermission('shifts', 'edit')
                    && ($user->canViewAllStores() || (int) $shift->store_id === (int) $user->store_id);
                if (!$canCloseOthers) {
                    return [403, 'You can only close your own shift.'];
                }
            }

            if ($shift->status !== 'open') {
                return [422, 'This shift is already closed.'];
            }

            $expected = round($shift->expectedCash(), 2);
            $closing = round((float) $data['closing_cash'], 2);
            $difference = round($closing - $expected, 2);

            $note = trim((string) ($data['note'] ?? ''));
            $shift->update([
                'closed_at' => now(),
                'expected_cash' => $expected,
                'closing_cash' => $closing,
                'difference' => $difference,
                'status' => 'closed',
                'note' => $note !== '' ? trim(($shift->note ? $shift->note . "\n" : '') . $note) : $shift->note,
            ]);

            Audit::log('shift.closed', "Shift #{$shift->id} closed, difference " . number_format($difference, 2), $shift, [
                'expected_cash' => $expected,
                'closing_cash' => $closing,
                'difference' => $difference,
                'closed_by_other' => (int) $shift->user_id !== (int) $user->id,
            ]);

            return [200, $shift];
        });

        [$code, $payload] = $result;
        if ($code !== 200) {
            return $this->fail($request, $payload, $code);
        }

        return $this->ok($request, 'Shift closed. Difference: ' . $this->currency() . ' ' . number_format($payload->difference, 2), [
            'shift' => $this->shiftJson($payload),
            'report_url' => route('shifts.show', $payload->id),
        ], route('shifts.show', $payload->id));
    }

    /**
     * JSON for the POS: the logged-in user's open shift at a store (default: their own store).
     */
    public function current(Request $request)
    {
        $user = $request->user();

        if ($user->canViewAllStores()) {
            $storeId = $request->input('store_id') ?: $user->store_id ?: session('store_filter');
        } else {
            $storeId = $user->store_id;
        }

        $shift = $storeId ? Shift::currentFor($user->id, (int) $storeId) : null;

        return response()->json(['shift' => $shift ? $this->shiftJson($shift) : null]);
    }

    /**
     * Z-report.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $shift = Shift::withoutGlobalScope('store')->with(['store', 'user'])->findOrFail($id);

        if (!$user->canViewAllStores() && (int) $shift->store_id !== (int) $user->store_id) {
            abort(403, 'Unauthorized action.');
        }

        $sales = Sale::withoutGlobalScope('store')->where('shift_id', $shift->id)
            ->selectRaw("
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS sales_count,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN grand_total ELSE 0 END), 0) AS sales_total,
                SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) AS returns_count,
                COALESCE(SUM(CASE WHEN status = 'returned' THEN grand_total ELSE 0 END), 0) AS returns_total,
                SUM(CASE WHEN status = 'void' THEN 1 ELSE 0 END) AS voids_count,
                COALESCE(SUM(CASE WHEN status = 'void' THEN grand_total ELSE 0 END), 0) AS voids_total,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN discount ELSE 0 END), 0) AS discount_total,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN tax ELSE 0 END), 0) AS tax_total,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN due_amount ELSE 0 END), 0) AS credit_given
            ")->first();

        // Money in/out of this shift by method. Till payments vs. credit (udhaar) collections.
        $payments = SalePayment::withoutGlobalScope('store')->where('shift_id', $shift->id)
            ->selectRaw('method,
                COALESCE(SUM(CASE WHEN customer_payment_id IS NULL AND amount > 0 THEN amount ELSE 0 END), 0) AS received,
                COALESCE(SUM(CASE WHEN customer_payment_id IS NULL AND amount < 0 THEN amount ELSE 0 END), 0) AS refunded,
                COALESCE(SUM(CASE WHEN customer_payment_id IS NOT NULL THEN amount ELSE 0 END), 0) AS credit_collected,
                COALESCE(SUM(amount), 0) AS net,
                COUNT(*) AS cnt')
            ->groupBy('method')
            ->get()
            ->keyBy('method');

        $creditCollected = CustomerPayment::withoutGlobalScope('store')->where('shift_id', $shift->id)
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total')->first();

        $expected = $shift->status === 'open' ? round($shift->expectedCash(), 2) : (float) $shift->expected_cash;

        return view('shifts.show', [
            'pageTitle' => 'Z-Report: Shift #' . $shift->id,
            'shift' => $shift,
            'sales' => $sales,
            'payments' => $payments,
            'methods' => SalePayment::METHODS,
            'creditCollected' => $creditCollected,
            'expected' => $expected,
            'currency' => $this->currency(),
        ]);
    }

    // ---------------------------------------------------------------------

    private function openShiftFor($user, $storeId): ?Shift
    {
        if ($storeId && ($shift = Shift::currentFor($user->id, $storeId))) {
            return $shift;
        }

        // Admin with no store picked: show whichever shift they have open
        if ($user->canViewAllStores()) {
            return Shift::withoutGlobalScope('store')
                ->where('user_id', $user->id)->where('status', 'open')
                ->latest('opened_at')->first();
        }

        return null;
    }

    private function liveSummary(Shift $shift): array
    {
        $cash = SalePayment::withoutGlobalScope('store')->where('shift_id', $shift->id)->where('method', 'cash')
            ->selectRaw('COALESCE(SUM(CASE WHEN customer_payment_id IS NULL THEN amount ELSE 0 END), 0) AS till,
                COALESCE(SUM(CASE WHEN customer_payment_id IS NOT NULL THEN amount ELSE 0 END), 0) AS credit')
            ->first();
        $sales = Sale::withoutGlobalScope('store')->where('shift_id', $shift->id)->where('status', 'completed')
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(grand_total), 0) AS total')->first();

        return [
            'cash_sales' => (float) $cash->till,
            'cash_credit' => (float) $cash->credit,
            'expected' => round($shift->expectedCash(), 2),
            'sales_count' => (int) $sales->cnt,
            'sales_total' => (float) $sales->total,
        ];
    }

    private function resolveStoreId(Request $request)
    {
        $user = $request->user();

        if (!$user->canViewAllStores()) {
            return $user->store_id ? (int) $user->store_id : null;
        }

        $storeId = $request->input('store_id') ?: session('store_filter') ?: $user->store_id;

        return $storeId && Store::where('status', 'Active')->whereKey($storeId)->exists() ? (int) $storeId : null;
    }

    private function shiftJson(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'store_id' => $shift->store_id,
            'opened_at' => $shift->opened_at?->toIso8601String(),
            'opening_cash' => (float) $shift->opening_cash,
            'expected_cash' => $shift->status === 'open' ? round($shift->expectedCash(), 2) : (float) $shift->expected_cash,
        ];
    }

    private function currency(): string
    {
        return optional(\App\Models\Setting::first())->currency ?? 'Rs';
    }

    private function ok(Request $request, string $message, array $extra = [], ?string $redirect = null)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message] + $extra);
        }

        return ($redirect ? redirect($redirect) : redirect()->route('shifts.index'))->with('success', $message);
    }

    private function fail(Request $request, string $message, int $code = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $code);
        }
        abort_if($code === 403, 403, $message);

        return back()->withInput()->with('error', $message);
    }
}
