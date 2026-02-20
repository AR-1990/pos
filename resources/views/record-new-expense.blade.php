@extends('layouts.app')

@section('content')

<!-- Record New Expense -->
<div class="cashbook-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Record New Expense</h2>
        <a href="{{ route('expense.index') }}" class="themeBtn">
            <i class="fa-regular fa-arrow-left-long"></i>
            Back To Expenses
        </a>
    </div>
    <div class="dashboard-card">
        <div class="cashbook-transaction-form">
            <form id="expenseForm">
                @csrf
                <div class="row g-3">
                    <!-- Row 1 -->
                    <div class="col-md-6">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="date" class="form-control" value="{{ date('Y-m-d') }}" name="date" required>
                            <span class="input-group-text">
                                <i class="fa-regular fa-calendar"></i>
                            </span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Reference Number</label>
                        <input type="text" class="form-control" name="reference_no" placeholder="Leave blank to auto-generate">
                    </div>

                    <!-- Row 2 -->
                    <div class="col-12">
                        <label class="form-label">Expense Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="expense_head_id" required>
                            <option value="" selected disabled>Select Expense Type...</option>
                            @foreach($expenseHeads as $head)
                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Row 3 -->
                    <div class="col-md-4">
                        <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_mode" id="paymentMode" required>
                            <option value="Cash">Cash</option>
                            <option value="Online Transfer">Online Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="col-md-4" id="bankDiv" style="display: none;">
                        <label class="form-label">Bank <span class="text-danger">*</span></label>
                        <select class="form-select" name="bank_id" id="bankId">
                            <option value="" selected disabled>Select Bank...</option>
                            @foreach($banks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->account_number }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0" placeholder="0.00" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Paid To (Vendor/Employee)</label>
                        <input type="text" class="form-control" name="paid_to" placeholder="e.g. ABC Stationary Store">
                    </div>

                    <!-- Row 4 -->
                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="4" placeholder="Additional notes..."></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12">
                        <div class="add-product-form__actions">
                            <button type="submit" class="themeBtn" id="submitBtn">
                                Save Expense
                            </button>
                            <a href="{{ route('expense.index') }}" class="themeBtn themeBtn--gray">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle Bank Dropdown
        $('#paymentMode').change(function() {
            var mode = $(this).val();
            if (mode === 'Cash') {
                $('#bankDiv').hide();
                $('#bankId').prop('required', false);
            } else {
                $('#bankDiv').show();
                $('#bankId').prop('required', true);
            }
        });

        // Trigger change on load
        $('#paymentMode').trigger('change');

        // Form Submission
        $('#expenseForm').submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $('#submitBtn').prop('disabled', true).text('Saving...');

            $.ajax({
                url: "{{ route('expense.store') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Use SweetAlert if available, otherwise alert
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Success', response.success, 'success').then(() => {
                                window.location.href = response.redirect;
                            });
                        } else {
                            alert(response.success);
                            window.location.href = response.redirect;
                        }
                    }
                },
                error: function(xhr) {
                    $('#submitBtn').prop('disabled', false).text('Save Expense');
                    var errors = xhr.responseJSON.errors;
                    var errorMessage = '';
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '\n';
                    });
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        });
    });
</script>
@endpush

@endsection