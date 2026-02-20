<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseHead;
use App\Models\Bank;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Models\Setting;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Expense::with(['expenseHead', 'bank']);

            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('date', function ($row) {
                    return Carbon::parse($row->date)->format('Y-m-d');
                })
                ->addColumn('category', function ($row) {
                    return $row->expenseHead ? '<span class="badge bg-secondary">' . $row->expenseHead->name . '</span>' : '-';
                })
                ->editColumn('amount', function ($row) {
                    $currency = Setting::first()->currency ?? '$';
                    return $currency . number_format($row->amount, 2);
                })
                ->editColumn('status', function ($row) {
                    $class = 'badge ';
                    if ($row->status == 'Approved') $class .= 'active';
                    elseif ($row->status == 'Pending') $class .= 'yellow';
                    else $class .= 'inActive';
                    return '<span class="' . $class . '">' . $row->status . '</span>';
                })
                ->rawColumns(['category', 'status'])
                ->make(true);
        }

        $currency = Setting::first()->currency ?? '$';
        
        // Dashboard Metrics
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $totalExpensesMonth = Expense::whereMonth('date', $currentMonth)
                                     ->whereYear('date', $currentYear)
                                     ->sum('amount');

        $pendingCount = Expense::where('status', 'Pending')->count();

        $totalExpensesYear = Expense::whereYear('date', $currentYear)->sum('amount');

        return view('expense', compact('currency', 'totalExpensesMonth', 'pendingCount', 'totalExpensesYear'));
    }

    public function create()
    {
        $expenseHeads = ExpenseHead::where('status', 1)->get();
        $banks = Bank::where('status', 'Active')->get();
        return view('record-new-expense', [
            'pageTitle' => 'Expenses',
            'expenseHeads' => $expenseHeads,
            'banks' => $banks
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'expense_head_id' => 'required|exists:expense_heads,id',
            'amount' => 'required|numeric|min:0',
            'payment_mode' => 'required',
            'bank_id' => 'required_if:payment_mode,Bank Transfer,Online Transfer,Cheque',
        ]);

        $data = $request->all();
        // Auto-generate reference number if not provided or just use timestamp/random
        if (empty($data['reference_no'])) {
            $data['reference_no'] = 'EXP-' . strtoupper(uniqid());
        }
        
        // Handle bank_id null if Cash
        if ($request->payment_mode == 'Cash') {
            $data['bank_id'] = null;
        }

        Expense::create($data);

        return response()->json(['success' => 'Expense recorded successfully!', 'redirect' => route('expense.index')]);
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Expense::with(['expenseHead', 'bank']);

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        $expenses = $query->get();
        $currency = Setting::first()->currency ?? '$';

        $filename = "expenses_report_" . date('Y-m-d_H-i-s') . ".csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($expenses, $currency) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Reference No', 'Category', 'Description', 'Paid To', 'Payment Mode', 'Bank', 'Amount', 'Status', 'Remarks']);

            foreach ($expenses as $row) {
                fputcsv($file, [
                    $row->date,
                    $row->reference_no,
                    $row->expenseHead->name ?? '',
                    $row->description,
                    $row->paid_to,
                    $row->payment_mode,
                    $row->bank->name ?? '',
                    $currency . number_format($row->amount, 2),
                    $row->status,
                    $row->remarks
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
