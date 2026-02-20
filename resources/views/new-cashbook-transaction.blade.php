@extends('layouts.app')

@section('content')

<!-- Cashbook Dashboard -->
<div class="cashbook-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">New Cashbook Transaction</h2>
        <a href="{{ route('cashbook.index') }}" class="themeBtn">
            <i class="fa-regular fa-arrow-left-long"></i>
            Back To Cashbook
        </a>
    </div>
    <div class="dashboard-card">
        <!-- Transaction Type Tabs -->
        <ul class="nav nav-tabs transaction-type-tabs" id="transactionTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="receipt-tab" data-bs-toggle="tab" data-bs-target="#receipt"
                    type="button" role="tab" aria-controls="receipt" aria-selected="true" onclick="setTransactionType('Debit')">
                    Record Receipt
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button"
                    role="tab" aria-controls="payment" aria-selected="false" onclick="setTransactionType('Credit')">
                    Payment (Credit)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="transfer-tab" data-bs-toggle="tab" data-bs-target="#transfer" type="button"
                    role="tab" aria-controls="transfer" aria-selected="false" onclick="setTransactionType('Transfer')">
                    Transfer
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="transactionTabContent">
            <!-- Record Receipt Tab -->
            <div class="tab-pane fade show active" id="receipt" role="tabpanel" aria-labelledby="receipt-tab">
                <div class="cashbook-transaction-form">
                    <form id="transactionForm" action="{{ route('cashbook.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" id="transactionType" value="Debit">
                        <div class="row g-3">
                            <!-- Effective Date -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="transaction_date" value="{{ date('Y-m-d') }}" required>
                                        <span class="input-group-text">
                                            <i class="fa-regular fa-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Voucher No. -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Voucher No.</label>
                                    <input type="text" class="form-control" name="voucher_no" placeholder="GST / VAT">
                                </div>
                            </div>

                            <!-- Description / Particulars -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Description / Particulars</label>
                                    <input type="text" class="form-control" name="description"
                                        placeholder="Brief of description of transaction">
                                </div>
                            </div>

                            <!-- Account Head -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Account Head <span class="text-danger">*</span></label>
                                    <select class="form-select" name="cashbook_head_id" required>
                                        <option value="">Select Account...</option>
                                        @foreach($heads as $head)
                                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Transfer Fields -->
                            <div class="col-md-6 transfer-field" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">Transfer From <span class="text-danger">*</span></label>
                                    <select class="form-select" name="transfer_from" id="transferFrom" onchange="toggleTransferBanks()">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank">Bank</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 transfer-field" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">Transfer To <span class="text-danger">*</span></label>
                                    <select class="form-select" name="transfer_to" id="transferTo" onchange="toggleTransferBanks()">
                                        <option value="Bank">Bank</option>
                                        <option value="Cash">Cash</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 transfer-bank-from" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">From Bank <span class="text-danger">*</span></label>
                                    <select class="form-select" name="from_bank_id">
                                        <option value="">Select Bank...</option>
                                        @foreach($banks as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->account_number }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 transfer-bank-to" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">To Bank <span class="text-danger">*</span></label>
                                    <select class="form-select" name="to_bank_id">
                                        <option value="">Select Bank...</option>
                                        @foreach($banks as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->account_number }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Payment Mode -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                                    <select class="form-select" name="payment_mode" id="paymentMode" onchange="toggleBankDropdown()" required>
                                        <option value="Cash" selected>Cash</option>
                                        <option value="Online Transfer">Online Transfer</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Card Payment">Card Payment</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Bank Dropdown (Hidden by default) -->
                            <div class="col-md-4" id="bankDropdownDiv" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">Bank</label>
                                    <select class="form-select" name="bank_id">
                                        <option value="">Select Bank...</option>
                                        @foreach($banks as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->account_number }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="amount" value="0.00" step="0.01" min="0"
                                        placeholder="0.00" required>
                                </div>
                            </div>

                            <!-- Reference / Cheque No. -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Reference / Cheque No.</label>
                                    <input type="text" class="form-control" name="reference_no" placeholder="Optional reference number.">
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Remarks</label>
                                    <textarea class="form-control" name="remarks" rows="4"
                                        placeholder="Additional notes....."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="themeBtn w-100" id="submitBtn">
                            Record Receipt
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- We can reuse the same form structure for both tabs by just changing the hidden input via JS -->
            <div class="tab-pane fade" id="payment" role="tabpanel" aria-labelledby="payment-tab">
            </div>
            <div class="tab-pane fade" id="transfer" role="tabpanel" aria-labelledby="transfer-tab">
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function setTransactionType(type) {
        document.getElementById('transactionType').value = type;
        const btn = document.getElementById('submitBtn');
        const formContainer = document.querySelector('.cashbook-transaction-form');
        
        // Handle Tab Content Movement
        if(type === 'Debit') {
            document.getElementById('receipt').appendChild(formContainer);
        } else if(type === 'Credit') {
            document.getElementById('payment').appendChild(formContainer);
        } else {
            document.getElementById('transfer').appendChild(formContainer);
        }

        // Handle UI changes
        btn.classList.remove('themeBtn--red');
        
        if(type === 'Debit') {
            btn.textContent = 'Record Receipt';
        } else if (type === 'Credit') {
            btn.textContent = 'Record Payment';
            btn.classList.add('themeBtn--red');
        } else {
            btn.textContent = 'Record Transfer';
        }

        // Toggle Fields
        const isTransfer = type === 'Transfer';
        
        // Hide/Show standard fields
        const paymentModeSelect = document.getElementById('paymentMode');
        const paymentModeDiv = paymentModeSelect.closest('.col-md-4');
        const bankDropdownDiv = document.getElementById('bankDropdownDiv');
        
        if (isTransfer) {
            paymentModeDiv.style.display = 'none';
            bankDropdownDiv.style.display = 'none'; 
            
            // Show Transfer Fields
            document.querySelectorAll('.transfer-field').forEach(el => el.style.display = 'block');
            toggleTransferBanks(); 
        } else {
            paymentModeDiv.style.display = 'block';
            toggleBankDropdown(); 
            
            // Hide Transfer Fields
            document.querySelectorAll('.transfer-field').forEach(el => el.style.display = 'none');
            document.querySelector('.transfer-bank-from').style.display = 'none';
            document.querySelector('.transfer-bank-to').style.display = 'none';
        }
    }

    function toggleTransferBanks() {
        const from = document.getElementById('transferFrom').value;
        const to = document.getElementById('transferTo').value;
        
        document.querySelector('.transfer-bank-from').style.display = (from === 'Bank') ? 'block' : 'none';
        document.querySelector('.transfer-bank-to').style.display = (to === 'Bank') ? 'block' : 'none';
    }

    function toggleBankDropdown() {
        const mode = document.getElementById('paymentMode').value;
        const bankDiv = document.getElementById('bankDropdownDiv');
        if (mode === 'Online Transfer' || mode === 'Cheque' || mode === 'Bank Transfer' || mode === 'Card Payment') {
            bankDiv.style.display = 'block';
        } else {
            bankDiv.style.display = 'none';
        }
    }

    $(document).ready(function() {
        $('#transactionForm').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.success,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "{{ route('cashbook.index') }}";
                        }
                    });
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessage = '';
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '<br>';
                    });
                    Swal.fire({
                        title: 'Error!',
                        html: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    });
</script>
@endpush

@endsection
