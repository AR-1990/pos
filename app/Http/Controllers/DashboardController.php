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

        // 1. Inventory Metrics
        $totalProducts = Product::count();
        $outOfStock = Product::where('stock_quantity', '<=', 0)->count();
        $lowStock = Product::whereRaw('stock_quantity <= COALESCE(min_stock_level, 10)')->count();
        
        // Products expiring in next 30 days
        $expiringProducts = Product::whereDate('expiry_date', '>=', Carbon::now())
            ->whereDate('expiry_date', '<=', Carbon::now()->addDays(30))
            ->orderBy('expiry_date')
            ->get();
        $expiringCount = $expiringProducts->count();

        // 2. Financial Metrics
        $totalOrders = Sale::count();
        $totalRevenue = Sale::sum('grand_total');
        
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
        $recentSales = Sale::with('customer')->latest()->take(5)->get();

        return view('index', compact(
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
