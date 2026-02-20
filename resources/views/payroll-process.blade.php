@extends('layouts.app')

@section('content')

<!-- Payroll Process -->
<div class="payroll-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Employee Salary Sheet</h2>
        <a href="{{ route('payroll.index') }}" class="themeBtn">
            <i class="fa-regular fa-arrow-left-long"></i>
            Back To Payroll
        </a>
    </div>
    <div class="dashboard-card">
        <div class="cashbook-transaction-form">
            <form id="payrollForm">
                @csrf
                <!-- Employee Details Section -->
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <h4 class="mb-3 payroll-section-title">Employee Details</h4>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Select Employee</label>
                            <select class="form-select" name="user_id" id="userId" required>
                                <option value="" selected>Choose Employee...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->designation ?? 'N/A' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Salary Month</label>
                            <div class="input-group">
                                <input type="date" class="form-control" name="salary_month" id="salaryMonth" value="{{ date('Y-m-d') }}" required>
                                <span class="input-group-text">
                                    <i class="fa-regular fa-calendar"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <!-- Earnings Section -->
                    <div class="col-md-6">
                        <div class="payroll-section-box payroll-section-box--green">
                            <h4 class="mb-3 payroll-section-title payroll-section-title--green">
                                <i class="fa-solid fa-circle-plus me-2"></i>Earnings
                            </h4>
                            <div class="mb-3">
                                <label class="form-label">Basic Salary</label>
                                <input type="number" class="form-control calc-input" name="basic_salary" id="basicSalary" value="0.00" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Allowances (Total)</label>
                                <input type="number" class="form-control calc-input" name="allowances" id="allowances" value="0.00" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Deductions Section -->
                    <div class="col-md-6">
                        <div class="payroll-section-box payroll-section-box--red">
                            <h4 class="mb-3 payroll-section-title payroll-section-title--red">
                                <i class="fa-solid fa-circle-minus me-2"></i>Deductions
                            </h4>
                            <div class="mb-3">
                                <label class="form-label">Income Tax</label>
                                <input type="number" class="form-control calc-input" name="income_tax" id="incomeTax" value="0.00" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Provident Fund (PF)</label>
                                <input type="number" class="form-control calc-input" name="fund_deduction" id="pf" value="0.00" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Advance Salary / Loan</label>
                                <div class="d-flex flex-column gap-2">
                                    <input type="number" class="form-control calc-input" name="loan_deduction" id="loanDeduction" value="0.00" step="0.01" min="0" placeholder="0.00">
                                    <select class="form-select" name="loan_id" id="loanSelect" style="display:none;">
                                        <option value="">-- Select Specific Loan (Optional) --</option>
                                    </select>
                                </div>
                                <p class="text-danger mt-1 mb-0 payroll-outstanding-balance" id="loanBalanceText" style="display:none;">Outstanding Balance: <strong>{{ $globalSettings->currency ?? '$' }}0.00</strong></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deduction Reason</label>
                                <select class="form-select" name="deduction_reason">
                                    <option value="">Select Reason (Optional)</option>
                                    <option value="Late Arrival">Late Arrival</option>
                                    <option value="Loan Deduction">Loan Deduction</option>
                                    <option value="Advance Salary">Advance Salary</option>
                                    <option value="Damages/Fine">Damages / Fine</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div id="deductionInfo" class="form-text text-warning mt-1" style="display:none;">
                                    <i class="fa-solid fa-circle-info me-1"></i> This will be recorded as a loan repayment.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Other Deductions</label>
                                <input type="number" class="form-control calc-input" name="other_deductions" id="otherDeductions" value="0.00" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <!-- Payment Details Section -->
                    <div class="col-md-6">
                        <h4 class="mb-3 payroll-section-title">
                            <i class="fa-solid fa-circle-plus"></i> Payment Details
                        </h4>
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <div class="input-group">
                                <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}">
                                <span class="input-group-text">
                                    <i class="fa-regular fa-calendar"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Mode</label>
                            <select class="form-select" name="payment_mode">
                                <option value="Online Transfer" selected>Online Transfer</option>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="Paid" selected>Paid</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                    </div>

                    <!-- Net Payable Amount -->
                    <div class="col-md-6">
                        <div class="net-payable-box">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Earnings:</span>
                                <span id="totalEarnings" class="text-white">{{ $globalSettings->currency ?? '$' }}0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Total Deductions:</span>
                                <span id="totalDeductions" class="text-white">{{ $globalSettings->currency ?? '$' }}0.00</span>
                            </div>
                            <hr class="border-white opacity-50">
                            <h5 class="net-payable-title mt-2">Net Payable Amount</h5>
                            <h3 class="net-payable-value" id="netPayable">{{ $globalSettings->currency ?? '$' }}0.00</h3>
                            <p class="net-payable-subtitle">(Earnings - Deductions)</p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Salary Details / Notes</label>
                            <textarea class="form-control" name="notes" rows="4" placeholder="Enter detailed salary breakdown or additional notes..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Process Payroll Button -->
                <button type="submit" class="themeBtn w-100">
                    Process Payroll
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const currencySymbol = "{{ $globalSettings->currency ?? '$' }}";
    document.addEventListener("DOMContentLoaded", function() {
        // Calculate Net Salary
        const calcInputs = document.querySelectorAll('.calc-input');
        const netPayableDisplay = document.getElementById('netPayable');
        const totalEarningsDisplay = document.getElementById('totalEarnings');
        const totalDeductionsDisplay = document.getElementById('totalDeductions');

        function calculateNetSalary() {
            let earnings = (parseFloat(document.getElementById('basicSalary').value) || 0) +
                           (parseFloat(document.getElementById('allowances').value) || 0);
            
            let deductions = (parseFloat(document.getElementById('incomeTax').value) || 0) +
                             (parseFloat(document.getElementById('pf').value) || 0) +
                             (parseFloat(document.getElementById('loanDeduction').value) || 0) +
                             (parseFloat(document.getElementById('otherDeductions').value) || 0);
            
            let netSalary = earnings - deductions;
            
            netPayableDisplay.textContent = currencySymbol + netSalary.toFixed(2);
            totalEarningsDisplay.textContent = currencySymbol + earnings.toFixed(2);
            totalDeductionsDisplay.textContent = currencySymbol + deductions.toFixed(2);
        }

        calcInputs.forEach(input => {
            input.addEventListener('input', calculateNetSalary);
        });

        // Fetch Loan Balance
        $('#userId').change(function() {
            var userId = $(this).val();
            if (userId) {
                $.ajax({
                    url: "{{ url('get-employee-loan') }}/" + userId,
                    type: 'GET',
                    success: function(response) {
                        // Auto-populate salary
                        $('#basicSalary').val(parseFloat(response.salary).toFixed(2));
                        
                        // Show loan balance
                        $('#loanBalanceText strong').text(currencySymbol + parseFloat(response.loan_balance).toFixed(2));
                        $('#loanBalanceText').show();
                        
                        // Populate Loan Select Dropdown
                        var loanSelect = $('#loanSelect');
                        loanSelect.empty().append('<option value="">-- Select Specific Loan (Optional) --</option>');
                        
                        if (response.active_loans && response.active_loans.length > 0) {
                            response.active_loans.forEach(function(loan) {
                                loanSelect.append('<option value="' + loan.id + '">Loan #' + loan.id + ' (' + loan.type + ') - Bal: ' + currencySymbol + parseFloat(loan.balance).toFixed(2) + '</option>');
                            });
                            loanSelect.show();
                        } else {
                            loanSelect.hide();
                        }
                        
                        // Trigger calculation to update Net Salary
                        const event = new Event('input');
                        document.getElementById('basicSalary').dispatchEvent(event);
                    }
                });
            } else {
                $('#loanBalanceText').hide();
                $('#loanSelect').hide().empty();
                $('#basicSalary').val('0.00'); // Reset salary
            }
        });

        // Form Submission
        $('#payrollForm').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                url: "{{ route('payroll.store') }}",
                type: 'POST',
                data: formData,
                success: function(response) {
                    alert(response.success);
                    window.location.href = "{{ route('payroll.index') }}";
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.error || 'Something went wrong!';
                    alert(errors);
                }
            });
        });

        // Toggle Deduction Info
        $('select[name="deduction_reason"]').change(function() {
            if ($(this).val() === 'Loan Deduction') {
                $('#deductionInfo').show();
            } else {
                $('#deductionInfo').hide();
            }
        });

        // Trigger on load
        $('select[name="deduction_reason"]').trigger('change');
    });
</script>

@endsection