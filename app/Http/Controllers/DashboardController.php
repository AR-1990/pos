<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\LoanAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // 1. Inventory Metrics: the selected store's stock, or the all-stores total
        $totalProducts = Product::where('status', 'Active')->count();
        $storeId = \App\Models\Store::scopedId();
        $qtyExpr = $storeId !== null
            ? '(SELECT COALESCE(SUM(ss.quantity), 0) FROM store_stocks ss WHERE ss.product_id = products.id AND ss.store_id = ' . (int) $storeId . ')'
            : 'stock_quantity';
        $outOfStock = Product::where('status', 'Active')->whereRaw("$qtyExpr <= 0")->count();
        $lowStock = Product::where('status', 'Active')->whereRaw("$qtyExpr > 0 AND $qtyExpr <= COALESCE(min_stock_level, 10)")->count();
        
        // Products expiring in next 30 days
        $expiringProducts = Product::whereDate('expiry_date', '>=', Carbon::now())
            ->whereDate('expiry_date', '<=', Carbon::now()->addDays(30))
            ->orderBy('expiry_date')
            ->get();
        $expiringCount = $expiringProducts->count();

        // 2. Financial Metrics (drafts are not revenue; returns are stored as negative totals)
        // Sale queries are already limited to the user's store by Sale's global scope
        $finalSales = fn () => Sale::whereIn('status', ['completed', 'returned']);
        $totalOrders = $finalSales()->where('status', 'completed')->count();
        $totalRevenue = $finalSales()->sum('grand_total');
        $todayRevenue = $finalSales()->whereDate('date', Carbon::today())->sum('grand_total');
        $todayOrders = $finalSales()->where('status', 'completed')->whereDate('date', Carbon::today())->count();
        
        // Current Month Expenses
        $currentMonthExpense = Expense::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');

        // Current Month Salary (Net Salary)
        $currentMonthSalary = Payroll::whereMonth('salary_month', $currentMonth)
            ->whereYear('salary_month', $currentYear)
            ->sum('net_salary');

        // Total Outstanding Loan (Amount - Paid Amount for Active/Approved loans)
        $totalLoan = LoanAdvance::whereIn('status', ['Active', 'Approved'])
            ->sum(DB::raw('amount - paid_amount'));

        // 3. Sales Chart Data (Last 7 Days)
        $salesData = Sale::select(
            DB::raw('DATE(date) as date'),
            DB::raw('SUM(grand_total) as total')
        )
        ->whereIn('status', ['completed', 'returned'])
        ->where('date', '>=', Carbon::now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        $salesLabels = [];
        $salesValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $salesLabels[] = Carbon::now()->subDays($i)->format('d M');
            $daySale = $salesData->firstWhere('date', $date);
            $salesValues[] = $daySale ? $daySale->total : 0;
        }

        // 4. Category Distribution
        $categoryData = Product::select('categories.name', DB::raw('count(*) as total'))
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        
        $categoryLabels = $categoryData->pluck('name');
        $categoryValues = $categoryData->pluck('total');

        // 5. Recent Sales
        $recentSales = Sale::with(['customer', 'store'])->whereIn('status', ['completed', 'returned'])->latest()->take(5)->get();

        // 6. Store-wise breakdown, only for admins looking at all stores
        $storeBreakdown = collect();
        if (auth()->user()->canViewAllStores() && !session('store_filter')) {
            $storeBreakdown = \App\Models\Store::where('status', 'Active')
                ->withSum(['sales as today_total' => fn ($q) => $q->whereIn('status', ['completed', 'returned'])->whereDate('date', Carbon::today())], 'grand_total')
                ->withSum(['sales as week_total' => fn ($q) => $q->whereIn('status', ['completed', 'returned'])->where('date', '>=', Carbon::today()->subDays(6))], 'grand_total')
                ->withSum(['sales as all_total' => fn ($q) => $q->whereIn('status', ['completed', 'returned'])], 'grand_total')
                ->withCount(['sales as orders_count' => fn ($q) => $q->where('status', 'completed')])
                ->orderByDesc('week_total')
                ->get();
        }

        $currentStore = auth()->user()->canViewAllStores()
            ? (session('store_filter') ? \App\Models\Store::find(session('store_filter')) : null)
            : auth()->user()->store;

        return view('index', compact(
            'todayRevenue',
            'todayOrders',
            'storeBreakdown',
            'currentStore',
            'totalProducts',
            'outOfStock',
            'lowStock',
            'expiringProducts',
            'expiringCount',
            'totalOrders',
            'totalRevenue',
            'currentMonthExpense',
            'currentMonthSalary',
            'totalLoan',
            'salesLabels',
            'salesValues',
            'categoryLabels',
            'categoryValues',
            'recentSales'
        ));
    }
}
