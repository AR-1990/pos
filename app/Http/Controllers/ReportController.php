<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function salesReport(Request $request)
    {
        if ($request->ajax()) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query = Sale::with('customer')
                ->where('status', 'completed')
                ->when($startDate, function ($query, $startDate) {
                    return $query->whereDate('date', '>=', $startDate);
                })
                ->when($endDate, function ($query, $endDate) {
                    return $query->whereDate('date', '<=', $endDate);
                })
                ->latest();

            return DataTables::of($query)
                ->addColumn('customer_name', function ($row) {
                    return $row->customer ? $row->customer->name : 'Walk-in';
                })
                ->editColumn('payment_method', function ($row) {
                    return ucfirst(str_replace('_', ' ', $row->payment_method));
                })
                ->editColumn('subtotal', function ($row) {
                    return number_format($row->subtotal, 2);
                })
                ->editColumn('discount', function ($row) {
                    return number_format($row->discount, 2);
                })
                ->editColumn('tax', function ($row) {
                    return number_format($row->tax, 2);
                })
                ->editColumn('grand_total', function ($row) {
                    return '<strong>' . number_format($row->grand_total, 2) . '</strong>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('sales.details', $row->id) . '" class="btn btn-sm btn-outline-info" title="View Details"><i class="fa-solid fa-eye"></i></a>';
                })
                ->rawColumns(['grand_total', 'action'])
                ->make(true);
        }

        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();

        return view('reports.sales', compact('startDate', 'endDate'));
    }

    public function exportSales(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $currency = \App\Models\Setting::first()->currency ?? '$';

        $query = Sale::with('customer')
            ->where('status', 'completed')
            ->when($startDate, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            ->latest();

        $sales = $query->get();

        $filename = "sales_report_" . date('Y-m-d') . ".csv";
        
        $callback = function() use ($sales, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Invoice No', 'Customer', 'Payment Method', 'Subtotal', 'Discount', 'Tax', 'Grand Total']);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->date,
                    $sale->invoice_number,
                    $sale->customer ? $sale->customer->name : 'Walk-in',
                    ucfirst(str_replace('_', ' ', $sale->payment_method)),
                    $currency . number_format($sale->subtotal, 2),
                    $currency . number_format($sale->discount, 2),
                    $currency . number_format($sale->tax, 2),
                    $currency . number_format($sale->grand_total, 2)
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function returnReport(Request $request)
    {
        if ($request->ajax()) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query = Sale::with('customer')
                ->where('status', 'returned')
                ->when($startDate, function ($query, $startDate) {
                    return $query->whereDate('date', '>=', $startDate);
                })
                ->when($endDate, function ($query, $endDate) {
                    return $query->whereDate('date', '<=', $endDate);
                })
                ->latest();

            return DataTables::of($query)
                ->addColumn('customer_name', function ($row) {
                    return $row->customer ? $row->customer->name : 'Walk-in';
                })
                ->editColumn('grand_total', function ($row) {
                    return '<strong class="text-danger">' . number_format($row->grand_total, 2) . '</strong>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('sales.details', $row->id) . '" class="btn btn-sm btn-outline-info" title="View Details"><i class="fa-solid fa-eye"></i></a>';
                })
                ->rawColumns(['grand_total', 'action'])
                ->make(true);
        }

        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();

        return view('reports.returns', compact('startDate', 'endDate'));
    }

    public function exportReturns(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $currency = \App\Models\Setting::first()->currency ?? '$';

        $query = Sale::with('customer')
            ->where('status', 'returned')
            ->when($startDate, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            ->latest();

        $returns = $query->get();

        $filename = "returns_report_" . date('Y-m-d') . ".csv";
        
        $callback = function() use ($returns, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Invoice No', 'Customer', 'Refund Amount']);

            foreach ($returns as $return) {
                fputcsv($handle, [
                    $return->date,
                    $return->invoice_number,
                    $return->customer ? $return->customer->name : 'Walk-in',
                    $currency . number_format($return->grand_total, 2)
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function revenueReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $currency = \App\Models\Setting::first()->currency ?? '$';

        if ($request->ajax()) {
            $dates = [];
            $currentDate = Carbon::parse($startDate);
            $lastDate = Carbon::parse($endDate);

            // Fetch data once for the range to minimize queries in loop
            // Optimization: Fetch grouped by date
            $sales = Sale::selectRaw('date, count(*) as count, sum(grand_total) as total')
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $returns = Sale::selectRaw('date, sum(grand_total) as total')
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', 'returned')
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            while ($currentDate->lte($lastDate)) {
                $date = $currentDate->toDateString();
                
                $daySales = $sales->get($date);
                $dayReturns = $returns->get($date);

                $orders = $daySales ? $daySales->count : 0;
                $grossSales = $daySales ? $daySales->total : 0;
                $returnAmount = $dayReturns ? $dayReturns->total : 0;
                $netRevenue = $grossSales - $returnAmount;

                // Only add if there is activity
                if ($orders > 0 || $returnAmount > 0) {
                    $dates[] = [
                        'date' => $date,
                        'orders' => $orders,
                        'gross_sales' => $grossSales,
                        'returns' => $returnAmount,
                        'net_revenue' => $netRevenue
                    ];
                }
                
                $currentDate->addDay();
            }

            return DataTables::of(collect($dates))
                ->editColumn('date', function($row) {
                    return Carbon::parse($row['date'])->format('d M, Y');
                })
                ->editColumn('gross_sales', function($row) use ($currency) {
                    return $currency . number_format($row['gross_sales'], 2);
                })
                ->editColumn('returns', function($row) use ($currency) {
                    return $currency . number_format($row['returns'], 2);
                })
                ->editColumn('net_revenue', function($row) use ($currency) {
                    return '<strong>' . $currency . number_format($row['net_revenue'], 2) . '</strong>';
                })
                ->rawColumns(['net_revenue'])
                ->make(true);
        }

        // Summary Cards Data
        $totalSales = Sale::whereBetween('date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('grand_total');
            
        $totalReturns = Sale::whereBetween('date', [$startDate, $endDate])
            ->where('status', 'returned')
            ->sum('grand_total');
            
        $netRevenue = $totalSales - $totalReturns;

        return view('revenue-report', compact('startDate', 'endDate', 'totalSales', 'totalReturns', 'netRevenue'));
    }

    public function exportRevenue(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $currency = \App\Models\Setting::first()->currency ?? '$';

        // Reuse logic
        $sales = Sale::selectRaw('date, count(*) as count, sum(grand_total) as total')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $returns = Sale::selectRaw('date, sum(grand_total) as total')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'returned')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $data = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);

        while ($currentDate->lte($lastDate)) {
            $date = $currentDate->toDateString();
            
            $daySales = $sales->get($date);
            $dayReturns = $returns->get($date);

            $orders = $daySales ? $daySales->count : 0;
            $grossSales = $daySales ? $daySales->total : 0;
            $returnAmount = $dayReturns ? $dayReturns->total : 0;
            $netRevenue = $grossSales - $returnAmount;

            if ($orders > 0 || $returnAmount > 0) {
                $data[] = [
                    'date' => $date,
                    'orders' => $orders,
                    'gross_sales' => $grossSales,
                    'returns' => $returnAmount,
                    'net_revenue' => $netRevenue
                ];
            }
            $currentDate->addDay();
        }

        $filename = "revenue_report_" . $startDate . "_to_" . $endDate . ".csv";
        
        $callback = function() use ($data, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Orders', 'Gross Sales', 'Returns', 'Net Revenue']);

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $row['orders'],
                    $currency . number_format($row['gross_sales'], 2),
                    $currency . number_format($row['returns'], 2),
                    $currency . number_format($row['net_revenue'], 2)
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function profitReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $currency = \App\Models\Setting::first()->currency ?? '$';

        if ($request->ajax()) {
            $sales = Sale::with(['items.product'])
                ->whereBetween('date', [$startDate, $endDate])
                ->whereIn('status', ['completed', 'returned'])
                ->get();
            
            $expenses = \App\Models\Expense::whereBetween('date', [$startDate, $endDate])->get();
            $payrolls = \App\Models\Payroll::whereBetween('payment_date', [$startDate, $endDate])->get();

            $dates = [];
            $currentDate = Carbon::parse($startDate);
            $lastDate = Carbon::parse($endDate);

            while ($currentDate->lte($lastDate)) {
                $date = $currentDate->toDateString();
                
                // Revenue & COGS
                $daySales = $sales->where('date', $date);
                $dayRevenue = 0;
                $dayCOGS = 0;

                foreach ($daySales as $sale) {
                    if ($sale->status == 'completed') {
                        $dayRevenue += $sale->grand_total;
                        foreach ($sale->items as $item) {
                            $purchasePrice = $item->purchase_price > 0 ? $item->purchase_price : ($item->product->purchase_price ?? 0);
                            $dayCOGS += ($item->quantity * $purchasePrice);
                        }
                    } elseif ($sale->status == 'returned') {
                        $dayRevenue -= $sale->grand_total; // Reduce Revenue
                        foreach ($sale->items as $item) {
                            $purchasePrice = $item->purchase_price > 0 ? $item->purchase_price : ($item->product->purchase_price ?? 0);
                            $dayCOGS -= ($item->quantity * $purchasePrice); // Reduce COGS (Inventory returned)
                        }
                    }
                }

                // Expenses
                $dayExpenses = $expenses->where('date', $date)->sum('amount');
                
                // Payroll
                $dayPayroll = $payrolls->where('payment_date', $date)->sum(function($payroll) {
                    return $payroll->basic_salary + $payroll->allowances;
                });

                $totalExpenses = $dayExpenses + $dayPayroll;
                $grossProfit = $dayRevenue - $dayCOGS;
                $netProfit = $grossProfit - $totalExpenses;

                if ($dayRevenue != 0 || $dayCOGS != 0 || $totalExpenses != 0) {
                    $dates[] = [
                        'date' => $date,
                        'revenue' => $dayRevenue,
                        'cogs' => $dayCOGS,
                        'gross_profit' => $grossProfit,
                        'expenses' => $totalExpenses,
                        'net_profit' => $netProfit
                    ];
                }
                
                $currentDate->addDay();
            }

            return DataTables::of(collect($dates))
                ->editColumn('date', function($row) {
                    return Carbon::parse($row['date'])->format('d M, Y');
                })
                ->editColumn('revenue', function($row) use ($currency) {
                    return $currency . number_format($row['revenue'], 2);
                })
                ->editColumn('cogs', function($row) use ($currency) {
                    return $currency . number_format($row['cogs'], 2);
                })
                ->editColumn('gross_profit', function($row) use ($currency) {
                    return $currency . number_format($row['gross_profit'], 2);
                })
                ->editColumn('expenses', function($row) use ($currency) {
                    return $currency . number_format($row['expenses'], 2);
                })
                ->editColumn('net_profit', function($row) use ($currency) {
                    $color = $row['net_profit'] >= 0 ? 'text-success' : 'text-danger';
                    return '<strong class="' . $color . '">' . $currency . number_format($row['net_profit'], 2) . '</strong>';
                })
                ->rawColumns(['net_profit'])
                ->make(true);
        }

        // Summary Calculations
        $sales = Sale::with(['items.product'])
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'returned'])
            ->get();
            
        $totalRevenue = 0;
        $totalCOGS = 0;

        foreach ($sales as $sale) {
            if ($sale->status == 'completed') {
                $totalRevenue += $sale->grand_total;
                foreach ($sale->items as $item) {
                    $purchasePrice = $item->purchase_price > 0 ? $item->purchase_price : ($item->product->purchase_price ?? 0);
                    $totalCOGS += ($item->quantity * $purchasePrice);
                }
            } elseif ($sale->status == 'returned') {
                $totalRevenue -= $sale->grand_total;
                foreach ($sale->items as $item) {
                    $purchasePrice = $item->purchase_price > 0 ? $item->purchase_price : ($item->product->purchase_price ?? 0);
                    $totalCOGS -= ($item->quantity * $purchasePrice);
                }
            }
        }

        $totalExpenses = \App\Models\Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $totalPayroll = \App\Models\Payroll::whereBetween('payment_date', [$startDate, $endDate])
            ->get()
            ->sum(function($payroll) {
                return $payroll->basic_salary + $payroll->allowances;
            });
            
        $totalAllExpenses = $totalExpenses + $totalPayroll;
        $grossProfit = $totalRevenue - $totalCOGS;
        $netProfit = $grossProfit - $totalAllExpenses;

        return view('profit-report', compact('startDate', 'endDate', 'totalRevenue', 'totalCOGS', 'grossProfit', 'totalAllExpenses', 'netProfit'));
    }

    public function exportProfit(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $currency = \App\Models\Setting::first()->currency ?? '$';

        // Reuse logic (could extract to service, but copying for speed now)
        $sales = Sale::with(['items.product'])
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'returned'])
            ->get();
        
        $expenses = \App\Models\Expense::whereBetween('date', [$startDate, $endDate])->get();
        $payrolls = \App\Models\Payroll::whereBetween('payment_date', [$startDate, $endDate])->get();

        $data = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);

        while ($currentDate->lte($lastDate)) {
            $date = $currentDate->toDateString();
            
            $daySales = $sales->where('date', $date);
            $dayRevenue = 0;
            $dayCOGS = 0;

            foreach ($daySales as $sale) {
                if ($sale->status == 'completed') {
                    $dayRevenue += $sale->grand_total;
                    foreach ($sale->items as $item) {
                        $purchasePrice = $item->purchase_price > 0 ? $item->purchase_price : ($item->product->purchase_price ?? 0);
                        $dayCOGS += ($item->quantity * $purchasePrice);
                    }
                } elseif ($sale->status == 'returned') {
                    $dayRevenue -= $sale->grand_total;
                    foreach ($sale->items as $item) {
                        $purchasePrice = $item->purchase_price > 0 ? $item->purchase_price : ($item->product->purchase_price ?? 0);
                        $dayCOGS -= ($item->quantity * $purchasePrice);
                    }
                }
            }

            $dayExpenses = $expenses->where('date', $date)->sum('amount');
            $dayPayroll = $payrolls->where('payment_date', $date)->sum(function($payroll) {
                return $payroll->basic_salary + $payroll->allowances;
            });

            $totalExpenses = $dayExpenses + $dayPayroll;
            
            if ($dayRevenue != 0 || $dayCOGS != 0 || $totalExpenses != 0) {
                $data[] = [
                    'date' => $date,
                    'revenue' => $dayRevenue,
                    'cogs' => $dayCOGS,
                    'gross_profit' => $dayRevenue - $dayCOGS,
                    'expenses' => $totalExpenses,
                    'net_profit' => ($dayRevenue - $dayCOGS) - $totalExpenses
                ];
            }
            $currentDate->addDay();
        }

        $filename = "profit_report_" . $startDate . "_to_" . $endDate . ".csv";
        
        $callback = function() use ($data, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Revenue', 'COGS', 'Gross Profit', 'Expenses', 'Net Profit']);

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $currency . number_format($row['revenue'], 2),
                    $currency . number_format($row['cogs'], 2),
                    $currency . number_format($row['gross_profit'], 2),
                    $currency . number_format($row['expenses'], 2),
                    $currency . number_format($row['net_profit'], 2)
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function stockReport(Request $request)
    {
        if ($request->ajax()) {
            $currency = \App\Models\Setting::first()->currency ?? '$';
            $query = \App\Models\Product::with(['category', 'subCategory', 'unit']);

            return DataTables::of($query)
                ->addColumn('category_name', function ($row) {
                    return $row->category ? $row->category->name : '-';
                })
                ->editColumn('stock_quantity', function ($row) {
                    $color = $row->stock_quantity <= $row->min_stock_level ? 'text-danger' : 'text-success';
                    return '<strong class="' . $color . '">' . $row->stock_quantity . ' ' . ($row->unit ? $row->unit->name : '') . '</strong>';
                })
                ->addColumn('expiry_date', function ($row) {
                    if (!$row->expiry_date) return '-';
                    $date = Carbon::parse($row->expiry_date);
                    $class = $date->isPast() ? 'text-danger fw-bold' : ($date->diffInDays(now()) <= 30 ? 'text-warning fw-bold' : '');
                    return '<span class="' . $class . '">' . $date->format('d M, Y') . '</span>';
                })
                ->editColumn('purchase_price', function ($row) use ($currency) {
                    return $currency . number_format($row->purchase_price, 2);
                })
                ->editColumn('selling_price', function ($row) use ($currency) {
                    return $currency . number_format($row->selling_price, 2);
                })
                ->addColumn('stock_value', function ($row) use ($currency) {
                    return $currency . number_format($row->stock_quantity * $row->purchase_price, 2);
                })
                ->addColumn('potential_profit', function ($row) use ($currency) {
                    return $currency . number_format($row->stock_quantity * ($row->selling_price - $row->purchase_price), 2);
                })
                ->rawColumns(['stock_quantity', 'expiry_date'])
                ->make(true);
        }

        $totalStockValue = \App\Models\Product::sum(\Illuminate\Support\Facades\DB::raw('stock_quantity * purchase_price'));
        $totalItems = \App\Models\Product::sum('stock_quantity');

        return view('reports.stock', compact('totalStockValue', 'totalItems'));
    }

    public function exportStock(Request $request)
    {
        $products = \App\Models\Product::with(['category', 'unit'])->get();
        $currency = \App\Models\Setting::first()->currency ?? '$';

        $filename = "stock_report_" . date('Y-m-d') . ".csv";
        
        $callback = function() use ($products, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Name', 'Category', 'Purchase Price', 'Selling Price', 'Stock Quantity', 'Unit', 'Expiry Date', 'Stock Value']);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->code,
                    $product->name,
                    $product->category ? $product->category->name : '-',
                    $currency . number_format($product->purchase_price, 2),
                    $currency . number_format($product->selling_price, 2),
                    $product->stock_quantity,
                    $product->unit ? $product->unit->name : '-',
                    $product->expiry_date ? $product->expiry_date : '-',
                    $currency . number_format($product->stock_quantity * $product->purchase_price, 2)
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function topProductsReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $currency = \App\Models\Setting::first()->currency ?? '$';

        if ($request->ajax()) {
            $query = \App\Models\SaleItem::with(['product'])
                ->select('product_id', 
                    \Illuminate\Support\Facades\DB::raw('sum(quantity) as total_qty'), 
                    \Illuminate\Support\Facades\DB::raw('sum(total) as total_revenue'))
                ->whereHas('sale', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate])
                      ->where('status', 'completed');
                })
                ->groupBy('product_id')
                ->orderByDesc('total_qty');

            return DataTables::of($query)
                ->addColumn('product_name', function ($row) {
                    return $row->product ? $row->product->name : 'Unknown Product';
                })
                ->addColumn('product_code', function ($row) {
                    return $row->product ? $row->product->code : '-';
                })
                ->editColumn('total_revenue', function ($row) use ($currency) {
                    return $currency . number_format($row->total_revenue, 2);
                })
                ->make(true);
        }

        return view('reports.top-products', compact('startDate', 'endDate'));
    }
    
    public function exportTopProducts(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $currency = \App\Models\Setting::first()->currency ?? '$';

        $items = \App\Models\SaleItem::with(['product'])
            ->select('product_id', 
                \Illuminate\Support\Facades\DB::raw('sum(quantity) as total_qty'), 
                \Illuminate\Support\Facades\DB::raw('sum(total) as total_revenue'))
            ->whereHas('sale', function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])
                  ->where('status', 'completed');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->get();

        $filename = "top_products_" . $startDate . "_to_" . $endDate . ".csv";
        
        $callback = function() use ($items, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Product Code', 'Product Name', 'Total Sold Qty', 'Total Revenue']);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->product ? $item->product->code : '-',
                    $item->product ? $item->product->name : 'Unknown',
                    $item->total_qty,
                    $currency . number_format($item->total_revenue, 2)
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}
