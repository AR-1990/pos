<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\Store;
use App\Services\StockService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = StockTransfer::query()
                ->with(['fromStore:id,name', 'toStore:id,name', 'user:id,name'])
                ->withCount('items')
                ->withSum('items', 'quantity')
                ->select('stock_transfers.*');

            if ($request->filled('start_date')) {
                $query->whereDate('stock_transfers.date', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('stock_transfers.date', '<=', $request->input('end_date'));
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->editColumn('transfer_no', fn ($r) => '<a href="' . route('stock-transfers.show', $r->id) . '" class="fw-semibold">' . e($r->transfer_no) . '</a>')
                ->editColumn('date', fn ($r) => \Carbon\Carbon::parse($r->date)->format('d M Y'))
                ->addColumn('from_store', fn ($r) => e($r->fromStore->name ?? '-'))
                ->addColumn('to_store', fn ($r) => e($r->toStore->name ?? '-'))
                ->addColumn('total_qty', fn ($r) => StockController::qty($r->items_sum_quantity ?? 0))
                ->addColumn('user_name', fn ($r) => e($r->user->name ?? '-'))
                ->addColumn('action', fn ($r) => '<a href="' . route('stock-transfers.show', $r->id) . '" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-regular fa-eye"></i></a>')
                ->filterColumn('from_store', fn ($q, $kw) => $q->whereHas('fromStore', fn ($s) => $s->where('name', 'like', "%$kw%")))
                ->filterColumn('to_store', fn ($q, $kw) => $q->whereHas('toStore', fn ($s) => $s->where('name', 'like', "%$kw%")))
                ->filterColumn('user_name', fn ($q, $kw) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%$kw%")))
                ->rawColumns(['transfer_no', 'action'])
                ->make(true);
        }

        return view('stock-transfers.index', ['pageTitle' => 'Stock Transfers']);
    }

    public function create()
    {
        $user = auth()->user();
        $allStores = Store::where('status', 'Active')->orderBy('name')->get(['id', 'name', 'code']);
        $fromStores = $user->canViewAllStores() ? $allStores : $allStores->where('id', $user->store_id)->values();
        $defaultFrom = $user->canViewAllStores() ? (session('store_filter') ?: $fromStores->first()?->id) : $user->store_id;

        return view('stock-transfers.create', [
            'pageTitle' => 'New Stock Transfer',
            'fromStores' => $fromStores,
            'toStores' => $allStores,
            'defaultFrom' => $defaultFrom,
            'locked' => !$user->canViewAllStores(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_store_id' => 'nullable|integer',
            'to_store_id' => 'required|integer',
            'date' => 'nullable|date',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0',
        ], [
            'items.required' => 'Add at least one product.',
            'items.*.quantity.gt' => 'Quantity must be greater than 0.',
        ]);

        $user = $request->user();
        $fromId = $user->canViewAllStores() ? (int) ($data['from_store_id'] ?? 0) : (int) ($user->store_id ?? 0);
        $toId = (int) $data['to_store_id'];

        $active = Store::where('status', 'Active')->whereIn('id', [$fromId, $toId])->pluck('name', 'id');
        if (!$active->has($fromId)) {
            throw ValidationException::withMessages(['from_store_id' => $user->canViewAllStores()
                ? 'Select an active store to transfer from.'
                : 'You are not assigned to any store. Ask an admin to assign one.']);
        }
        if (!$active->has($toId)) {
            throw ValidationException::withMessages(['to_store_id' => 'Select an active store to transfer to.']);
        }
        if ($fromId === $toId) {
            throw ValidationException::withMessages(['to_store_id' => 'The destination store must be different from the source store.']);
        }

        // Merge duplicate product rows
        $lines = [];
        foreach ($data['items'] as $item) {
            $pid = (int) $item['product_id'];
            $lines[$pid] = round(($lines[$pid] ?? 0) + (float) $item['quantity'], 3);
        }

        $transfer = DB::transaction(function () use ($fromId, $toId, $data, $lines, $user) {
            $transfer = StockTransfer::create([
                'transfer_no' => 'TMP-' . Str::uuid(),
                'from_store_id' => $fromId,
                'to_store_id' => $toId,
                'user_id' => $user->id,
                'date' => $data['date'] ?? now()->toDateString(),
                'note' => $data['note'] ?? null,
            ]);
            $transfer->update(['transfer_no' => 'TRF-' . str_pad($transfer->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($lines as $productId => $qty) {
                $transfer->items()->create(['product_id' => $productId, 'quantity' => $qty]);
                StockService::move($fromId, $productId, -$qty, 'transfer_out', $transfer, $transfer->transfer_no);
                StockService::move($toId, $productId, $qty, 'transfer_in', $transfer, $transfer->transfer_no);
            }

            return $transfer;
        });

        $stores = Store::whereIn('id', [$fromId, $toId])->pluck('name', 'id');
        Audit::log('stock.transfer', "Transfer {$transfer->transfer_no} {$stores[$fromId]} → {$stores[$toId]}", $transfer, [
            'from_store_id' => $fromId,
            'to_store_id' => $toId,
            'lines' => count($lines),
            'quantity' => array_sum($lines),
        ], $fromId);

        $message = "Transfer {$transfer->transfer_no} saved.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'id' => $transfer->id, 'redirect' => route('stock-transfers.show', $transfer->id)]);
        }

        return redirect()->route('stock-transfers.show', $transfer->id)->with('success', $message);
    }

    public function show($id)
    {
        $transfer = StockTransfer::with(['items.product.unit', 'fromStore', 'toStore', 'user'])->findOrFail($id);

        return view('stock-transfers.show', [
            'pageTitle' => 'Stock Transfer ' . $transfer->transfer_no,
            'transfer' => $transfer,
        ]);
    }
}
