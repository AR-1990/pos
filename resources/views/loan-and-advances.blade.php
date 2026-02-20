@extends('layouts.app')

@section('content')

<!-- Loans & Advances -->
<div class="loan-dashboard">
    <div class="dashboard-card">
        <!-- Top Row - Key Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Total Outstanding</h3>
                        <p class="metric-card__value loan-metric-value--orange">
                            {{ $globalSettings->currency ?? '$' }}{{ number_format($totalOutstanding, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Active Loans</h3>
                        <p class="metric-card__value loan-metric-value--orange">{{ $activeLoansCount }} Employees</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--red">
                        <i class="fa-solid fa-chevrons-down"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Overdue</h3>
                        <p class="metric-card__value text-danger">{{ $overdueLoansCount }} Loan</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter and Actions Section -->
        <div class="cashbook-filter-section">
            <div class="cashbook-filter">
                <form id="filterForm" class="cashbook-filter__dates">
                    <input type="date" class="form-control" id="startDate">
                    <span class="cashbook-filter__to">to</span>
                    <input type="date" class="form-control" id="endDate">
                    <button type="button" class="themeBtn" id="applyFilter">
                        <i class="fa-solid fa-filter me-1"></i> Apply
                    </button>
                    <button type="button" class="themeBtn themeBtn--green" id="exportBtn">
                        <i class="fa-solid fa-file-csv me-1"></i> Export
                    </button>
                </form>
                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('loan-and-advances.create') }}" class="themeBtn themeBtn--green">
                        <i class="fa-solid fa-plus me-1"></i> Grant New Loan
                    </a>
                    <button type="button" class="themeBtn themeBtn--yellow" data-bs-toggle="modal"
                        data-bs-target="#repayLoanModal">
                        <i class="fa-solid fa-money-bill-transfer me-1"></i> Repay Loan
                    </button>
                    <a href="{{ url('payroll') }}" class="themeBtn themeBtn--gray">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Employee Loans Section -->
        <div class="expense-register">
            <div class="expense-register__header">
                <h3 class="expense-register__title">Employee Loans</h3>
            </div>
            <div class="table-responsive">
                <table id="loansTableAjax" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>S. No</th>
                            <th>Employee</th>
                            <th>Date of Apply</th>
                            <th>Loan Type</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Repay Loan Modal -->
<div class="modal fade" id="repayLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Repay Loan (Manual)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('loan-and-advances.repay') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select class="form-select" name="user_id" id="repayEmployeeSelect" required>
                            <option value="">Select Employee</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="repayLoanSelectDiv" style="display:none;">
                        <label class="form-label">Select Loan to Repay</label>
                        <select class="form-select" name="loan_id" id="repayLoanSelect">
                            <option value="">-- Select Specific Loan (Optional) --</option>
                        </select>
                        <div class="form-text text-info">If no loan is selected, the oldest active loan will be repaid
                            first.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repayment Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ $globalSettings->currency ?? '$' }}</span>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Process Repayment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // ... (existing code)

        // Repay Modal - Fetch Loans
        $('#repayEmployeeSelect').change(function () {
            var userId = $(this).val();
            var loanSelect = $('#repayLoanSelect');
            var loanDiv = $('#repayLoanSelectDiv');

            loanSelect.empty().append(
            '<option value="">-- Select Specific Loan (Optional) --</option>');
            loanDiv.hide();

            if (userId) {
                // Using the route we defined in PayrollController for convenience, or we could add one here.
                // Assuming 'get-employee-loan' is available globally or we can use the full URL.
                $.ajax({
                    url: "{{ url('get-employee-loan') }}/" + userId,
                    type: 'GET',
                    success: function (response) {
                        if (response.active_loans && response.active_loans.length > 0) {
                            response.active_loans.forEach(function (loan) {
                                loanSelect.append('<option value="' + loan.id +
                                    '">Loan #' + loan.id + ' (' + loan.type +
                                    ') - Bal: ' + currencySymbol + parseFloat(loan.balance)
                                    .toFixed(2) + '</option>');
                            });
                            loanDiv.show();
                        }
                    }
                });
            }
        });

        var table = $('#loansTableAjax').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('loan-and-advances.index') }}",
                data: function (d) {
                    d.start_date = $('#startDate').val();
                    d.end_date = $('#endDate').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'employee_name',
                    name: 'user.name'
                },
                {
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'type',
                    name: 'type'
                },
                {
                    data: 'amount',
                    name: 'amount'
                },
                {
                    data: 'paid_amount',
                    name: 'paid_amount'
                },
                {
                    data: 'balance',
                    name: 'balance',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ]
        });

        $('#applyFilter').click(function () {
            table.draw();
        });

        $('#exportBtn').click(function () {
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var url = "{{ route('loan-and-advances.export') }}?start_date=" + startDate + "&end_date=" +
                endDate;
            window.location.href = url;
        });
    });

</script>

@endsection
