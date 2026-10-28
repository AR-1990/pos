<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Store::withCount('users')
                ->addSelect([
                    // Bypass the store scope so the totals are always per-store
                    'today_sales' => Sale::withoutGlobalScope('store')
                        ->selectRaw('COALESCE(SUM(grand_total), 0)')
                        ->whereColumn('sales.store_id', 'stores.id')
                        ->whereIn('status', ['completed', 'returned'])
                        ->whereDate('date', now()->toDateString()),
                    'total_sales' => Sale::withoutGlobalScope('store')
                        ->selectRaw('COALESCE(SUM(grand_total), 0)')
                        ->whereColumn('sales.store_id', 'stores.id')
                        ->whereIn('status', ['completed', 'returned']),
                ])
                ->latest();

            if ($request->has('status') && $request->status != 'all') {
                $query->where('status', $request->status);
            }

            $currency = optional(\App\Models\Setting::first())->currency ?? '$';

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('today_sales', fn ($row) => $currency . ' ' . number_format($row->today_sales, 2))
                ->editColumn('total_sales', fn ($row) => $currency . ' ' . number_format($row->total_sales, 2))
                ->editColumn('status', function ($row) {
                    $statusClass = $row->status == 'Active' ? 'badge bg-success' : 'badge bg-danger';
                    return '<span class="' . $statusClass . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex gap-2">';
                    if (auth()->user()->hasPermission('stores', 'edit')) {
                        $btn .= '<a href="' . route('stores.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>';
                    }
                    if (auth()->user()->hasPermission('stores', 'status')) {
                        $toggleText = $row->status == 'Active' ? 'Inactive' : 'Active';
                        $toggleIcon = $row->status == 'Active' ? 'fa-ban' : 'fa-check';
                        $toggleClass = $row->status == 'Active' ? 'btn-danger' : 'btn-success';
                        $btn .= '<button onclick="toggleStatus(' . $row->id . ')" class="btn ' . $toggleClass . ' btn-sm" title="Mark as ' . $toggleText . '"><i class="fa-solid ' . $toggleIcon . '"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('stores', ['pageTitle' => 'Stores']);
    }

    public function create()
    {
        return view('add-store', ['pageTitle' => 'Stores']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:stores,code|max:50',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        Store::create($data);

        return redirect()->route('stores.index')->with('success', 'Store added successfully.');
    }

    public function edit($id)
    {
        $store = Store::findOrFail($id);
        return view('add-store', ['pageTitle' => 'Stores', 'store' => $store]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:stores,code,' . $id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        Store::findOrFail($id)->update($data);

        return redirect()->route('stores.index')->with('success', 'Store updated successfully.');
    }

    public function toggleStatus($id)
    {
        $store = Store::findOrFail($id);
        $store->status = $store->status == 'Active' ? 'Inactive' : 'Active';
        $store->save();

        return response()->json(['success' => 'Status updated successfully.']);
    }

    /**
     * Header store switcher for admins: limit every sales screen to one store, or all.
     */
    public function switch(Request $request)
    {
        abort_unless($request->user()->canViewAllStores(), 403);

        $storeId = $request->input('store_id');
        if ($storeId && Store::whereKey($storeId)->exists()) {
            session(['store_filter' => (int) $storeId]);
        } else {
            session()->forget('store_filter');
        }

        return back();
    }
}
