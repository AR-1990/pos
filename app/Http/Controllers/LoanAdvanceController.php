<?php

namespace App\Http\Controllers;

use App\Models\LoanAdvance;
use App\Models\User;
use App\Models\LoanType;
use App\Models\LoanRepayment;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class LoanAdvanceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $currency = \App\Models\Setting::first()->currency ?? '$';
            $query = LoanAdvance::with('user');

            if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
                $query->whereBetween('date', [$request->start_date, $request->end_date]);
            }

            $query->orderBy('date', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($row) {
                    $designation = $row->user->designation ?? 'N/A';
                    return '<strong>' . $row->user->name . '</strong><br><small class="text-muted">' . $designation . '</small>';
                })
                ->editColumn('date', function ($row) {
                    return Carbon::parse($row->date)->format('d M, Y');
                })
                ->editColumn('amount', function ($row) {
                    return number_format($row->amount, 2);
                })
                ->editColumn('paid_amount', function ($row) {
                    return number_format($row->paid_amount, 2);
                })
                ->addColumn('balance', function ($row) {
                    $balance = $row->amount - $row->paid_amount;
                    return '<span class="text-danger">' . number_format($balance, 2) . '</span>';
                })
                ->editColumn('status', function ($row) {
                    $badges = [
                        'Active' => 'badge bg-primary',
                        'Paid' => 'badge bg-success',
                        'Rejected' => 'badge bg-danger',
                        'Pending' => 'badge bg-warning text-dark',
                    ];
                    $class = $badges[$row->status] ?? 'badge bg-secondary';
                    return '<span class="' . $class . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex">';
                    $btn .= '<a href="' . route('loan-and-advances.show', $row->id) . '" class="btn btn-outline-info btn-sm me-2"><i class="fa-solid fa-eye"></i></a>';
                    $btn .= '<a href="' . route('loan-and-advances.edit', $row->id) . '" class="btn btn-outline-primary btn-sm me-2"><i class="fa-solid fa-pen"></i></a>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['employee_name', 'balance', 'status', 'action'])
                ->make(true);
        }

        // Metrics Calculation
        $activeLoans = LoanAdvance::where('status', 'Active')->get();
        $totalOutstanding = $activeLoans->sum('amount') - $activeLoans->sum('paid_amount');
        $activeLoansCount = $activeLoans->count();
        
        $overdueLoansCount = $activeLoans->filter(function ($loan) {
            if (!$loan->installments || !$loan->repayment_plan) return false;
            
            $startDate = Carbon::parse($loan->date);
            $endDate = $startDate->copy();
            
            switch ($loan->repayment_plan) {
                case 'Monthly Installments':
                    $endDate->addMonths($loan->installments);
                    break;
                case 'Weekly Installments':
                    $endDate->addWeeks($loan->installments);
                    break;
                case 'Bi-weekly Installments':
                    $endDate->addWeeks($loan->installments * 2);
                    break;
                case 'Quarterly Installments':
                    $endDate->addMonths($loan->installments * 3);
                    break;
                case 'Lump Sum':
                    $endDate->addMonth(); // Default to 1 month for lump sum if not specified
                    break;
            }
            
            return Carbon::now()->gt($endDate);
        })->count();

        $users = User::where('status', 'Active')->get();

        return view('loan-and-advances', compact('totalOutstanding', 'activeLoansCount', 'overdueLoansCount', 'users'));
    }

    public function export(Request $request)
    {
        $query = LoanAdvance::with('user');

        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $loans = $query->orderBy('date', 'desc')->get();

        $filename = "loan_advances_" . date('Ymd') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, [
            'Employee Name',
            'Loan Type',
            'Date of Apply',
            'Total Amount',
            'Paid Amount',
            'Balance',
            'Status',
            'Repayment Plan',
            'Installments',
            'Notes'
        ]);

        foreach ($loans as $loan) {
            fputcsv($handle, [
                $loan->user->name ?? 'N/A',
                $loan->type,
                Carbon::parse($loan->date)->format('d M, Y'),
                $currency . $loan->amount,
                $currency . $loan->paid_amount,
                $currency . ($loan->amount - $loan->paid_amount),
                $loan->status,
                $loan->repayment_plan,
                $loan->installments,
                $loan->notes
            ]);
        }

        fclose($handle);
        exit;
    }

    public function create()
    {
        $users = User::where('status', 'Active')->get();
        $loanTypes = LoanType::where('status', 'Active')->orderBy('name')->get();
        return view('grant-new-loan-and-advance', compact('users', 'loanTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'repayment_plan' => 'required',
            'status' => 'required'
        ]);

        LoanAdvance::create($request->all());

        return redirect()->route('loan-and-advances.index')->with('success', 'Loan/Advance granted successfully.');
    }

    public function show($id)
    {
        $loan = LoanAdvance::with(['user', 'repayments' => function($query) {
            $query->orderBy('payment_date', 'asc');
        }])->findOrFail($id);

        // Check for legacy payments (paid_amount > sum of repayments)
        $totalRecordedRepayments = $loan->repayments->sum('amount');
        $legacyPaymentAmount = $loan->paid_amount - $totalRecordedRepayments;

        $allRepayments = $loan->repayments;

        if ($legacyPaymentAmount > 0.01) {
            // Create a virtual repayment object for legacy payments
            $legacyRepayment = new LoanRepayment();
            $legacyRepayment->amount = $legacyPaymentAmount;
            $legacyRepayment->payment_date = $loan->date; // Assume it happened on/after loan creation
            $legacyRepayment->source = 'System'; // Or 'Legacy'
            $legacyRepayment->notes = 'Previous Repayments (Before Tracking)';
            $legacyRepayment->id = 'legacy'; // specific ID to identify

            // Prepend to the collection
            $allRepayments->prepend($legacyRepayment);
        }

        // Calculate running totals
        $runningTotalPaid = 0;
        $repaymentsWithBalance = [];

        foreach ($allRepayments as $repayment) {
            $runningTotalPaid += $repayment->amount;
            $repayment->running_paid = $runningTotalPaid;
            $repayment->running_balance = $loan->amount - $runningTotalPaid;
            $repaymentsWithBalance[] = $repayment;
        }

        // Sort by date desc for display
        $loan->repayments = collect($repaymentsWithBalance)->sortByDesc('payment_date');

        return view('loan-details', compact('loan'));
    }

    public function edit($id)
    {
        $loan = LoanAdvance::findOrFail($id);
        $users = User::where('status', 'Active')->get();
        $loanTypes = LoanType::where('status', 'Active')->orderBy('name')->get();
        return view('grant-new-loan-and-advance', compact('loan', 'users', 'loanTypes'));
    }

    public function update(Request $request, $id)
    {
        $loan = LoanAdvance::findOrFail($id);
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'repayment_plan' => 'required',
            'status' => 'required'
        ]);

        $loan->update($request->all());

        return redirect()->route('loan-and-advances.index')->with('success', 'Loan updated successfully.');
    }

    public function repay(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'loan_id' => 'nullable|exists:loan_advances,id',
        ]);

        $remainingAmount = $request->amount;
        $userId = $request->user_id;

        // If specific loan is selected
        if ($request->has('loan_id') && $request->loan_id) {
            $loan = LoanAdvance::where('id', $request->loan_id)
                ->where('user_id', $userId)
                ->first();

            if (!$loan) {
                return redirect()->back()->with('error', 'Invalid loan selected.');
            }

            $deductAmount = min($loan->amount - $loan->paid_amount, $remainingAmount);
            $loan->paid_amount += $deductAmount;
            
            if ($loan->paid_amount >= $loan->amount) {
                $loan->status = 'Paid';
            }
            $loan->save();

            LoanRepayment::create([
                'loan_advance_id' => $loan->id,
                'user_id' => $userId,
                'amount' => $deductAmount,
                'payment_date' => $request->date,
                'source' => 'Manual',
                'notes' => $request->notes ?? 'Manual Repayment'
            ]);

        } else {
            // Find active loans for the user
            $loans = LoanAdvance::where('user_id', $userId)
                ->whereIn('status', ['Active', 'Approved'])
                ->orderBy('date', 'asc') // Pay oldest loan first
                ->get();

            if ($loans->isEmpty()) {
                return redirect()->back()->with('error', 'No active loans found for this user.');
            }

            foreach ($loans as $loan) {
                if ($remainingAmount <= 0) break;

                $balance = $loan->amount - $loan->paid_amount;
                $deductAmount = min($balance, $remainingAmount);

                $loan->paid_amount += $deductAmount;
                $remainingAmount -= $deductAmount;

                if ($loan->paid_amount >= $loan->amount) {
                    $loan->status = 'Paid';
                }

                $loan->save();

                // Create Repayment Record
                LoanRepayment::create([
                    'loan_advance_id' => $loan->id,
                    'user_id' => $userId,
                    'amount' => $deductAmount,
                    'payment_date' => $request->date,
                    'source' => 'Manual',
                    'notes' => $request->notes ?? 'Manual Repayment'
                ]);
            }
        }

        return redirect()->route('loan-and-advances.index')->with('success', 'Loan repayment processed successfully.');
    }
}
