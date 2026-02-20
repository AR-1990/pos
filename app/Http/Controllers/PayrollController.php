<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\User;
use App\Models\LoanAdvance;
use App\Models\LoanRepayment;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $currency = \App\Models\Setting::first()->currency ?? '$';
            $query = Payroll::with('user');

            if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
                $query->whereBetween('salary_month', [$request->start_date, $request->end_date]);
            }

            if ($request->has('status') && $request->status != 'all') {
                $query->where('status', $request->status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($row) {
                    $designation = $row->user->designation ?? 'N/A';
                    return '<strong>' . $row->user->name . '</strong><br><small class="text-muted">' . $designation . '</small>';
                })
                ->addColumn('salary_month', function ($row) {
                    return Carbon::parse($row->salary_month)->format('M, Y');
                })
                ->editColumn('basic_salary', function ($row) use ($currency) {
                    return $currency . number_format($row->basic_salary, 2);
                })
                ->editColumn('allowances', function ($row) use ($currency) {
                    return $currency . number_format($row->allowances, 2);
                })
                ->addColumn('deductions', function ($row) use ($currency) {
                    $totalDeductions = $row->income_tax + $row->fund_deduction + $row->loan_deduction + $row->other_deductions;
                    return '<span class="text-danger">' . $currency . number_format($totalDeductions, 2) . '</span>';
                })
                ->editColumn('net_salary', function ($row) use ($currency) {
                    return '<strong>' . $currency . number_format($row->net_salary, 2) . '</strong>';
                })
                ->editColumn('status', function ($row) {
                    $class = $row->status == 'Paid' ? 'badge bg-success' : 'badge bg-warning text-dark';
                    return '<span class="' . $class . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex">';
                    $btn .= '<a href="'.route('payroll.slip', $row->id).'" target="_blank" class="btn btn-outline-primary btn-sm me-2"><i class="fa-solid fa-file-invoice"></i> Slip</a>';
                    if ($row->status == 'Pending') {
                        $btn .= '<button type="button" onclick="markAsPaid('.$row->id.')" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> Pay</button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['employee_name', 'deductions', 'net_salary', 'status', 'action'])
                ->make(true);
        }

        // Metrics
        $totalSalaries = Payroll::whereMonth('salary_month', Carbon::now()->month)->sum('net_salary');
        $paidEmployees = Payroll::whereMonth('salary_month', Carbon::now()->month)->where('status', 'Paid')->count();
        $totalEmployees = User::where('status', 'Active')->count(); // Assuming active employees
        $pendingPayment = Payroll::whereMonth('salary_month', Carbon::now()->month)->where('status', 'Pending')->sum('net_salary');

        return view('payroll', compact('totalSalaries', 'paidEmployees', 'totalEmployees', 'pendingPayment'));
    }

    public function create()
    {
        $users = User::where('status', 'Active')->get();
        return view('payroll-process', compact('users'));
    }

    public function getEmployeeLoanDetails($id)
    {
        $user = User::findOrFail($id);
        
        $activeLoans = LoanAdvance::where('user_id', $id)
            ->whereIn('status', ['Active', 'Approved'])
            ->get();
            
        $loanBalance = 0;
        $loansList = [];

        foreach ($activeLoans as $loan) {
            $balance = $loan->amount - $loan->paid_amount;
            $loanBalance += $balance;
            
            $loansList[] = [
                'id' => $loan->id,
                'amount' => $loan->amount,
                'paid' => $loan->paid_amount,
                'balance' => $balance,
                'date' => Carbon::parse($loan->date)->format('d M, Y'),
                'type' => $loan->type
            ];
        }
        
        return response()->json([
            'salary' => $user->salary ?? 0,
            'loan_balance' => $loanBalance,
            'active_loans' => $loansList
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'salary_month' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'income_tax' => 'nullable|numeric|min:0',
            'fund_deduction' => 'nullable|numeric|min:0',
            'loan_deduction' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'payment_mode' => 'nullable|string',
            'status' => 'required|in:Paid,Pending',
        ]);

        DB::beginTransaction();
        try {
            $totalDeductions = ($request->income_tax ?? 0) + 
                               ($request->fund_deduction ?? 0) + 
                               ($request->loan_deduction ?? 0) + 
                               ($request->other_deductions ?? 0);
            
            $netSalary = ($request->basic_salary ?? 0) + ($request->allowances ?? 0) - $totalDeductions;

            $payroll = Payroll::create([
                'user_id' => $request->user_id,
                'salary_month' => $request->salary_month,
                'basic_salary' => $request->basic_salary ?? 0,
                'allowances' => $request->allowances ?? 0,
                'income_tax' => $request->income_tax ?? 0,
                'fund_deduction' => $request->fund_deduction ?? 0,
                'loan_deduction' => $request->loan_deduction ?? 0,
                'other_deductions' => $request->other_deductions ?? 0,
                'deduction_reason' => $request->deduction_reason,
                'net_salary' => $netSalary,
                'payment_date' => $request->payment_date,
                'payment_mode' => $request->payment_mode,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            // Handle Loan Deduction
            $repaymentAmount = $request->loan_deduction ?? 0;
            
            // If the explicit reason is Loan Deduction, also include other_deductions
            if ($request->deduction_reason === 'Loan Deduction') {
                $repaymentAmount += ($request->other_deductions ?? 0);
            }

            if ($repaymentAmount > 0) {
                // If specific loan is selected
                if ($request->has('loan_id') && $request->loan_id) {
                    $loan = LoanAdvance::where('id', $request->loan_id)
                        ->where('user_id', $request->user_id)
                        ->first();
                        
                    if ($loan) {
                        $deductAmount = min($loan->amount - $loan->paid_amount, $repaymentAmount);
                        $loan->paid_amount += $deductAmount;
                        if ($loan->paid_amount >= $loan->amount) {
                            $loan->status = 'Paid';
                        }
                        $loan->save();

                        LoanRepayment::create([
                            'loan_advance_id' => $loan->id,
                            'user_id' => $request->user_id,
                            'amount' => $deductAmount,
                            'payment_date' => $request->payment_date ?? now(),
                            'source' => 'Payroll',
                            'ref_id' => $payroll->id,
                            'notes' => 'Deducted from Salary Month: ' . $request->salary_month . ($request->deduction_reason ? ' (' . $request->deduction_reason . ')' : '')
                        ]);
                    }
                } else {
                    // Fallback to oldest loan logic if no specific loan selected
                    $loans = LoanAdvance::where('user_id', $request->user_id)
                        ->whereIn('status', ['Active', 'Approved'])
                        ->orderBy('date', 'asc')
                        ->get();

                    $remainingDeduction = $repaymentAmount;

                    foreach ($loans as $loan) {
                        if ($remainingDeduction <= 0) break;

                        $balance = $loan->amount - $loan->paid_amount;
                        $deductAmount = min($balance, $remainingDeduction);

                        $loan->paid_amount += $deductAmount;
                        $remainingDeduction -= $deductAmount;

                        if ($loan->paid_amount >= $loan->amount) {
                            $loan->status = 'Paid';
                        }

                        $loan->save();
                        
                        LoanRepayment::create([
                            'loan_advance_id' => $loan->id,
                            'user_id' => $request->user_id,
                            'amount' => $deductAmount,
                            'payment_date' => $request->payment_date ?? now(),
                            'source' => 'Payroll',
                            'ref_id' => $payroll->id,
                            'notes' => 'Deducted from Salary Month: ' . $request->salary_month . ($request->deduction_reason ? ' (' . $request->deduction_reason . ')' : '')
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => 'Payroll processed successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error processing payroll: ' . $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        $query = Payroll::with('user');
        $currency = \App\Models\Setting::first()->currency ?? '$';

        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $query->whereBetween('salary_month', [$request->start_date, $request->end_date]);
        }

        $payrolls = $query->orderBy('salary_month', 'desc')->get();

        $filename = "payroll_" . date('Ymd') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, [
            'Employee Name',
            'Salary Month',
            'Basic Salary',
            'Allowances',
            'Income Tax',
            'PF',
            'Loan Deduction',
            'Other Deductions',
            'Net Salary',
            'Status',
            'Payment Date',
            'Payment Mode'
        ]);

        foreach ($payrolls as $row) {
            fputcsv($handle, [
                $row->user->name ?? 'N/A',
                Carbon::parse($row->salary_month)->format('M, Y'),
                $currency . $row->basic_salary,
                $currency . $row->allowances,
                $currency . $row->income_tax,
                $currency . $row->fund_deduction,
                $currency . $row->loan_deduction,
                $currency . $row->other_deductions,
                $currency . $row->net_salary,
                $row->status,
                $row->payment_date,
                $row->payment_mode
            ]);
        }

        fclose($handle);
        exit;
    }

    public function slip($id)
    {
        $payroll = Payroll::with('user')->findOrFail($id);
        return view('payroll-slip', compact('payroll'));
    }

    public function markAsPaid(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);
        if ($payroll->status === 'Paid') {
            return response()->json(['error' => 'Payroll is already marked as paid.'], 422);
        }

        $payroll->status = 'Paid';
        if (!$payroll->payment_date) {
            $payroll->payment_date = Carbon::now()->format('Y-m-d');
        }
        if ($request->filled('payment_mode')) {
            $payroll->payment_mode = $request->payment_mode;
        }
        $payroll->save();

        return response()->json(['success' => 'Payroll marked as paid.']);
    }
}
