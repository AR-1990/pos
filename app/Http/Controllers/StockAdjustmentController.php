<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\StoreStock;
use App\Models\Store;
use App\Services\StockService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = StockAdjustment::query()
                ->with(['store:id,name', 'user:id,name'])
                ->withCount('items')
                ->withSum(['items as qty_in' => fn ($q) => $q->where('quantity', '>', 0)], 'quantity')
                ->withSum(['items as qty_out' => fn ($q) => $q->where('quantity', '<', 0)], 'quantity')
                ->select('stock_adjustments.*');

            if ($request->filled('reason')) {
                $query->where('stock_adjustments.reason', $request->input('reason'));
            }
            if ($request->filled('start_date')) {
                $query->whereDate('stock_adjustments.date', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('stock_adjustments.date', '<=', $request->input('end_date'));
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->editColumn('adjustment_no', fn ($r) => '<a href="' . route('stock-adjustments.show', $r->id) . '" class="fw-semibold">' . e($r->adjustment_no) . '</a>')
                ->editColumn('date', fn ($r) => \Carbon\Carbon::parse($r->date)->format('d M Y'))
                ->addColumn('store_name', fn ($r) => e($r->store->name ?? '-'))
                ->editColumn('reason', fn ($r) => '<span class="badge bg-secondary">' . e(StockAdjustment::REASONS[$r->reason] ?? $r->reason) . '</span>')
                ->addColumn('qty_change', function ($r) {
                    $parts = [];
                    if ($r->qty_in > 0) {
                        $parts[] = '<span class="text-success fw-semibold">+' . StockController::qty($r->qty_in) . '</span>';
                    }
                    if ($r->qty_out < 0) {
                        $parts[] = '<span class="text-danger fw-semibold">' . StockController::qty($r->qty_out) . '</span>';
                    }
                    return implode(' / ', $parts) ?: '0';
                })
                ->addColumn('user_name', fn ($r) => e($r->user->name ?? '-'))
                ->addColumn('action', fn ($r) => '<a href="' . route('stock-adjustments.show', $r->id) . '" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-regular fa-eye"></i></a>')
                ->filterColumn('store_name', fn ($q, $kw) => $q->whereHas('store', fn ($s) => $s->where('name', 'like', "%$kw%")))
                ->filterColumn('user_name', fn ($q, $kw) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%$kw%")))
                ->rawColumns(['adjustment_no', 'reason', 'qty_change', 'action'])
                ->make(true);
        }

        return view('stock-adjustments.index', [
            'pageTitle' => 'Stock Adjustments',
            'reasons' => StockAdjustment::REASONS,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $stores = Store::where('status', 'Active')->orderBy('name');
        if (!$user->canViewAllStores()) {
            $stores->whereKey($user->store_id ?? 0);
        }
        $stores = $stores->get(['id', 'name', 'code']);

        return view('stock-adjustments.create', [
            'pageTitle' => 'New Stock Adjustment',
            'stores' => $stores,
            'defaultStore' => $user->canViewAllStores() ? (session('store_filter') ?: $stores->first()?->id) : $user->store_id,
            'locked' => !$user->canViewAllStores(),
            'reasons' => StockAdjustment::REASONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'nullable|integer',
            'reason' => ['required', Rule::in(array_keys(StockAdjustment::REASONS))],
            'date' => 'nullable|date',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.direction' => 'nullable|in:add,remove',
        ], [
            'items.required' => 'Add at least one product.',
        ]);

        $user = $request->user();
        $storeId = $user->canViewAllStores() ? (int) ($data['store_id'] ?? 0) : (int) ($user->store_id ?? 0);
        if (!Store::where('status', 'Active')->whereKey($storeId)->exists()) {
            throw ValidationException::withMessages(['store_id' => $user->canViewAllStores()
                ? 'Select an active store.'
                : 'You are not assigned to any store. Ask an admin to assign one.']);
        }

        $reason = $data['reason'];
        $isCount = $reason === 'count';

        // Merge duplicate rows: counts keep the last entered number, removals/additions add up
        $lines = [];
        foreach ($data['items'] as $item) {
            $pid = (int) $item['product_id'];
            $qty = (float) $item['quantity'];
            if ($isCount) {
                $lines[$pid] = $qty;
                continue;
            }
            $sign = ($reason === 'other' && ($item['direction'] ?? 'remove') === 'add') ? 1 : -1;
            $lines[$pid] = round(($lines[$pid] ?? 0) + $sign * $qty, 3);
        }

        $label = StockAdjustment::REASONS[$reason];
        $note = $label . (!empty($data['note']) ? ': ' . Str::limit($data['note'], 200) : '');

        $adjustment = DB::transaction(function () use ($storeId, $reason, $data, $lines, $isCount, $user, $note) {
            $changes = [];
            foreach ($lines as $pid => $qty) {
                if ($isCount) {
                    // Lock + read the current balance (unscoped: the store was chosen explicitly above)
                    $current = (float) StoreStock::withoutGlobalScope('store')
                        ->where('store_id', $storeId)->where('product_id', $pid)->lockForUpdate()->value('quantity');
                    $diff = round($qty - $current, 3);
                } else {
                    $diff = $qty;
                }
                if ($diff != 0) {
                    $changes[$pid] = $diff;
                }
            }

            if (!$changes) {
                throw ValidationException::withMessages(['items' => $isCount
                    ? 'Counted quantities match the current stock. Nothing to adjust.'
                    : 'Enter a quantity greater than 0 for at least one product.']);
            }

            $adjustment = StockAdjustment::create([
                'adjustment_no' => 'TMP-' . Str::uuid(),
                'store_id' => $storeId,
                'user_id' => $user->id,
                'date' => $data['date'] ?? now()->toDateString(),
                'reason' => $reason,
                'note' => $data['note'] ?? null,
            ]);
            $adjustment->update(['adjustment_no' => 'ADJ-' . str_pad($adjustment->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($changes as $pid => $diff) {
                $adjustment->items()->create(['product_id' => $pid, 'quantity' => $diff]);
                StockService::move($storeId, $pid, $diff, 'adjustment', $adjustment, $note);
            }

            return $adjustment;
        });

        $storeName = Store::whereKey($storeId)->value('name');
        Audit::log('stock.adjustment', "Adjustment {$adjustment->adjustment_no} at {$storeName} ({$label})", $adjustment, [
            'reason' => $reason,
            'lines' => $adjustment->items()->count(),
        ], $storeId);

        $message = "Adjustment {$adjustment->adjustment_no} saved.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'id' => $adjustment->id, 'redirect' => route('stock-adjustments.show', $adjustment->id)]);
        }

        return redirect()->route('stock-adjustments.show', $adjustment->id)->with('success', $message);
    }

    public function show($id)
    {
        $adjustment = StockAdjustment::with(['items.product.unit', 'store', 'user'])->findOrFail($id);

        return view('stock-adjustments.show', [
            'pageTitle' => 'Stock Adjustment ' . $adjustment->adjustment_no,
            'adjustment' => $adjustment,
            'reasons' => StockAdjustment::REASONS,
        ]);
    }
}
