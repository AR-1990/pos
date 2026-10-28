<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StockController extends Controller
{
    const TYPE_BADGES = [
        'opening' => ['Opening', 'bg-info'],
        'purchase' => ['Purchase', 'bg-success'],
        'sale' => ['Sale', 'bg-primary'],
        'return' => ['Return', 'bg-warning'],
        'void' => ['Void', 'bg-secondary'],
        'transfer_in' => ['Transfer In', 'bg-success'],
        'transfer_out' => ['Transfer Out', 'bg-warning'],
        'adjustment' => ['Adjustment', 'bg-danger'],
    ];

    /**
     * Stock levels. Admin with no store selected: one column per active store + total.
     * Otherwise only the selected / own store. One grouped query.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $stores = Store::where('status', 'Active')->orderBy('name')->get(['id', 'name', 'code']);

        if ($user->canViewAllStores()) {
            $storeId = $request->has('store_id') ? ($request->input('store_id') ?: null) : session('store_filter');
            $storeId = $storeId && $stores->contains('id', (int) $storeId) ? (int) $storeId : null;
        } else {
            $storeId = (int) ($user->store_id ?? 0);
        }

        $query = DB::table('products')
            ->leftJoin('store_stocks as ss', 'ss.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('measurement_units as mu', 'mu.id', '=', 'products.unit_id')
            ->select('products.id', 'products.code', 'products.name', 'products.barcode', 'products.status',
                'products.purchase_price', 'products.min_stock_level', 'categories.name as category_name', 'mu.name as unit_name')
            ->groupBy('products.id', 'products.code', 'products.name', 'products.barcode', 'products.status',
                'products.purchase_price', 'products.min_stock_level', 'categories.name', 'mu.name')
            ->orderBy('products.name');

        $perStore = $storeId === null;
        if ($perStore) {
            foreach ($stores as $s) {
                $query->selectRaw('COALESCE(SUM(CASE WHEN ss.store_id = ? THEN ss.quantity END), 0) as s_' . (int) $s->id, [$s->id]);
            }
            $query->selectRaw('COALESCE(SUM(ss.quantity), 0) as total_qty');
        } else {
            $query->selectRaw('COALESCE(SUM(CASE WHEN ss.store_id = ? THEN ss.quantity END), 0) as total_qty', [$storeId]);
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->input('category_id'));
        }
        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(fn ($q) => $q->where('products.name', 'like', $term)->orWhere('products.code', 'like', $term)
                ->orWhere('products.barcode', 'like', $term)->orWhere('products.sku', 'like', $term));
        }

        $level = $request->input('level', 'all');
        $minExpr = 'COALESCE(products.min_stock_level, 10)';
        $qtyExpr = $perStore ? 'COALESCE(SUM(ss.quantity), 0)' : 'COALESCE(SUM(CASE WHEN ss.store_id = ' . (int) $storeId . ' THEN ss.quantity END), 0)';
        if ($level === 'out') {
            $query->havingRaw("$qtyExpr <= 0");
        } elseif ($level === 'low') {
            $query->havingRaw("$qtyExpr > 0 AND $qtyExpr <= $minExpr");
        }

        $rows = $query->get();

        $summary = [
            'products' => $rows->count(),
            'units' => $rows->sum('total_qty'),
            'value' => $rows->sum(fn ($r) => $r->total_qty * $r->purchase_price),
            'low' => $rows->filter(fn ($r) => $r->total_qty > 0 && $r->total_qty <= ($r->min_stock_level ?? 10))->count(),
            'out' => $rows->filter(fn ($r) => $r->total_qty <= 0)->count(),
        ];

        return view('stock.index', [
            'pageTitle' => 'Stock Levels',
            'rows' => $rows,
            'stores' => $stores,
            'storeId' => $storeId,
            'perStore' => $perStore,
            'currentStore' => $storeId ? $stores->firstWhere('id', $storeId) : null,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'summary' => $summary,
            'level' => $level,
        ]);
    }

    /** Stock ledger (every stock_movements row), server-side DataTable. */
    public function movements(Request $request)
    {
        if ($request->ajax()) {
            $query = StockMovement::query()
                ->with(['product:id,name,code', 'store:id,name', 'user:id,name', 'reference'])
                ->select('stock_movements.*');

            if ($request->user()->canViewAllStores() && $request->filled('store_id')) {
                $query->where('stock_movements.store_id', $request->input('store_id'));
            }
            if ($request->filled('product')) {
                $term = '%' . $request->input('product') . '%';
                $query->whereHas('product', fn ($q) => $q->where('name', 'like', $term)->orWhere('code', 'like', $term)->orWhere('barcode', 'like', $term));
            }
            if ($request->filled('product_id')) {
                $query->where('stock_movements.product_id', $request->input('product_id'));
            }
            if ($request->filled('type')) {
                $query->where('stock_movements.type', $request->input('type'));
            }
            if ($request->filled('start_date')) {
                $query->whereDate('stock_movements.created_at', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('stock_movements.created_at', '<=', $request->input('end_date'));
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->editColumn('created_at', fn ($r) => $r->created_at?->format('d M Y, h:i A'))
                ->addColumn('store_name', fn ($r) => e($r->store->name ?? '-'))
                ->addColumn('product_name', fn ($r) => $r->product
                    ? e($r->product->name) . '<br><small class="text-muted">' . e($r->product->code) . '</small>' : '-')
                ->editColumn('type', function ($r) {
                    [$label, $class] = self::TYPE_BADGES[$r->type] ?? [ucfirst(str_replace('_', ' ', $r->type)), 'bg-secondary'];
                    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
                })
                ->editColumn('quantity', fn ($r) => $r->quantity > 0
                    ? '<strong class="text-success">+' . self::qty($r->quantity) . '</strong>'
                    : '<strong class="text-danger">' . self::qty($r->quantity) . '</strong>')
                ->editColumn('balance_after', fn ($r) => self::qty($r->balance_after))
                ->addColumn('user_name', fn ($r) => e($r->user->name ?? '-'))
                ->addColumn('reference_link', fn ($r) => self::referenceLink($r))
                ->editColumn('note', fn ($r) => e($r->note ?? ''))
                ->filterColumn('product_name', fn ($q, $kw) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%$kw%")->orWhere('code', 'like', "%$kw%")))
                ->filterColumn('store_name', fn ($q, $kw) => $q->whereHas('store', fn ($s) => $s->where('name', 'like', "%$kw%")))
                ->filterColumn('user_name', fn ($q, $kw) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%$kw%")))
                ->rawColumns(['product_name', 'type', 'quantity', 'reference_link'])
                ->make(true);
        }

        return view('stock.movements', [
            'pageTitle' => 'Stock Ledger',
            'types' => self::TYPE_BADGES,
            'stores' => Store::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Product lookup for transfer/adjustment forms. Returns available qty at ?store_id.
     * Exact barcode/code matches come first so a scanner + Enter picks the right item.
     */
    public function productSearch(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('stock_transfers', 'add') && !$user->hasPermission('stock_adjustments', 'add') && !$user->hasPermission('stock_ledger', 'view')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $storeId = (int) $request->input('store_id');
        if (!$user->canViewAllStores()) {
            $storeId = (int) ($user->store_id ?? 0);
        }

        $term = trim((string) $request->input('q', ''));
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if ($term === '' && !$ids) {
            return response()->json([]);
        }

        $query = Product::query()
            ->leftJoin('store_stocks as ss', fn ($j) => $j->on('ss.product_id', '=', 'products.id')->where('ss.store_id', '=', $storeId))
            ->leftJoin('measurement_units as mu', 'mu.id', '=', 'products.unit_id');

        if ($ids) {
            // Refresh available quantities for rows already on the form (e.g. store changed)
            $query->whereIn('products.id', $ids);
        } else {
            $query->where('products.status', 'Active')
                ->where(fn ($q) => $q->where('products.name', 'like', "%$term%")
                    ->orWhere('products.code', 'like', "%$term%")
                    ->orWhere('products.sku', 'like', "%$term%")
                    ->orWhere('products.barcode', 'like', "%$term%"))
                ->orderByRaw('CASE WHEN products.barcode = ? OR products.code = ? OR products.sku = ? THEN 0 ELSE 1 END', [$term, $term, $term])
                ->orderBy('products.name')
                ->limit(15);
        }

        $products = $query->get(['products.id', 'products.name', 'products.code', 'products.barcode', 'mu.name as unit_name', DB::raw('COALESCE(ss.quantity, 0) as available')]);

        return response()->json($products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'barcode' => $p->barcode,
            'unit' => $p->unit_name,
            'available' => (float) $p->available,
        ]));
    }

    /** Printable barcode label sheet. */
    public function labels(Request $request)
    {
        $preselected = collect();
        if ($request->filled('ids')) {
            $ids = array_filter(array_map('intval', explode(',', $request->input('ids'))));
            $preselected = Product::whereIn('id', $ids)->get(['id', 'name', 'code', 'barcode', 'selling_price']);
        }

        return view('stock.labels', [
            'pageTitle' => 'Barcode Labels',
            'preselected' => $preselected,
        ]);
    }

    public function labelSearch(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        return response()->json(Product::query()
            ->where(fn ($q) => $q->where('name', 'like', "%$term%")->orWhere('code', 'like', "%$term%")
                ->orWhere('sku', 'like', "%$term%")->orWhere('barcode', 'like', "%$term%"))
            ->orderByRaw('CASE WHEN barcode = ? OR code = ? OR sku = ? THEN 0 ELSE 1 END', [$term, $term, $term])
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'code', 'barcode', 'selling_price']));
    }

    public static function qty($value): string
    {
        $s = number_format((float) $value, 3, '.', ',');
        return rtrim(rtrim($s, '0'), '.');
    }

    private static function referenceLink(StockMovement $m): string
    {
        $ref = $m->reference;
        if (!$m->reference_type || !$m->reference_id) {
            return '-';
        }

        $user = auth()->user();
        $label = null;
        $url = null;

        switch ($m->reference_type) {
            case StockTransfer::class:
                $label = $ref->transfer_no ?? 'Transfer #' . $m->reference_id;
                $url = $user->hasPermission('stock_transfers', 'view') ? route('stock-transfers.show', $m->reference_id) : null;
                break;
            case StockAdjustment::class:
                $label = $ref->adjustment_no ?? 'Adjustment #' . $m->reference_id;
                $url = $user->hasPermission('stock_adjustments', 'view') ? route('stock-adjustments.show', $m->reference_id) : null;
                break;
            case Product::class:
                $label = 'Product';
                $url = $user->hasPermission('products', 'edit') ? route('products.edit', $m->reference_id) : null;
                break;
            case 'App\\Models\\Sale':
                $label = $ref->invoice_no ?? 'Sale #' . $m->reference_id;
                $url = $user->hasPermission('sales', 'view') && \Route::has('sales.details') ? route('sales.details', $m->reference_id) : null;
                break;
            case 'App\\Models\\Purchase':
                $label = $ref->purchase_no ?? ($ref->reference_no ?? 'Purchase #' . $m->reference_id);
                $url = $user->hasPermission('purchases', 'view') && \Route::has('purchases.show') ? route('purchases.show', $m->reference_id) : null;
                break;
            default:
                $label = class_basename($m->reference_type) . ' #' . $m->reference_id;
        }

        return $url ? '<a href="' . e($url) . '">' . e($label) . '</a>' : e($label);
    }
}
