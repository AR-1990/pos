@extends('layouts.app')

@section('content')
<style>
    .walk-in-fields { display: none; }
    .cart-table-container { max-height: 400px; overflow-y: auto; }
</style>

<!-- Sales Dashboard -->
<div class="sales-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($sale) ? 'Edit Sale' : 'Add Sales' }}</h2>
    </div>
    <div class="dashboard-card">
        <!-- Sales Form and Payment Summary Row -->
        <div class="row g-3 mb-4">
            <!-- Left Column - Sales Form -->
            <div class="col-lg-8">
                <div class="sales-form">
                    <div class="row g-3 align-items-end">
                        <!-- Document Type -->
                        <div class="col-md-4">
                            <label class="form-label">Document Type</label>
                            <div class="d-flex gap-2">
                                <select class="form-select" id="document_type">
                                    <option selected>Sales Invoice</option>
                                    <option>Sales Order</option>
                                    <option>Quotation</option>
                                </select>
                                <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#returnModal" title="Return / Exchange">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Customer -->
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <div class="input-group">
                                <select class="form-select" id="customer_id">
                                    <option value="walk-in" {{ isset($sale) && !$sale->customer_id ? 'selected' : '' }}>Walk-in Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ isset($sale) && $sale->customer_id == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->phone }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" id="salesDate" value="{{ isset($sale) ? $sale->date : date('Y-m-d') }}">
                        </div>

                        <!-- Walk-in Customer Fields -->
                        <div class="col-md-6 walk-in-fields">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="customer_name" value="{{ isset($sale) && $sale->customer ? $sale->customer->name : '' }}">
                        </div>
                        <div class="col-md-6 walk-in-fields">
                            <label class="form-label">Customer Phone</label>
                            <input type="text" class="form-control" id="customer_phone" value="{{ isset($sale) && $sale->customer ? $sale->customer->phone : '' }}">
                        </div>

                        <!-- Reference / PO No. -->
                        <div class="col-md-12">
                            <label class="form-label">Reference / PO No. (Optional)</label>
                            <input type="text" class="form-control" id="reference_no" placeholder="Optional" value="{{ $sale->reference_no ?? '' }}">
                        </div>

                        <hr class="my-3">

                        <!-- Product Search -->
                        <div class="col-md-12">
                            <label class="form-label">Search Product</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="product_search" placeholder="Type product name, code or SKU...">
                                <div id="product_results" class="list-group position-absolute w-100" style="z-index: 1000; display:none;"></div>
                            </div>
                        </div>

                        <!-- Cart Table -->
                        <div class="col-md-12 mt-4">
                            <h5>Cart Items</h5>
                            <div class="table-responsive cart-table-container">
                                <table class="table table-bordered" id="cartTable">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Cart items will be added here -->
                                        <tr id="emptyCartRow">
                                            <td colspan="5" class="text-center">No items in cart</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Payment Summary -->
            <div class="col-lg-4">
                <div class="payment-summary">
                    <h3 class="payment-summary__title">Payment Summary</h3>
                    <div class="payment-summary__content">
                        <div class="payment-summary__row">
                            <span>Subtotal:</span>
                            <strong id="summarySubtotal">{{ $globalSettings->currency ?? '$' }}0.00</strong>
                        </div>
                        <div class="payment-summary__row">
                            <span>Discount:</span>
                            <select class="form-select form-select-sm w-50" id="summaryDiscount">
                                <option value="0">None</option>
                                @foreach($discounts as $d)
                                    <option value="{{ $d->amount }}" {{ isset($sale) && $sale->discount == $d->amount ? 'selected' : '' }}>
                                        {{ $d->name }} ({{ number_format($d->amount, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="payment-summary__row">
                            <span>Tax:</span>
                            <input type="number" class="form-control form-control-sm w-25 text-end" id="summaryTax" value="{{ isset($sale) ? $sale->tax : 0 }}" readonly>
                        </div>
                        <div class="payment-summary__row payment-summary__row--total">
                            <span>Grand Total:</span>
                            <strong>{{ $globalSettings->currency ?? '$' }}<span id="summaryGrandTotal">0.00</span></strong>
                        </div>
                        
                         <div class="mt-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method">
                                <option value="cash" {{ isset($sale) && $sale->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="card" {{ isset($sale) && $sale->payment_method == 'card' ? 'selected' : '' }}>Card</option>
                                <option value="bank_transfer" {{ isset($sale) && $sale->payment_method == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            </select>
                        </div>

                        <div class="mt-3" id="bank_div" style="display:none;">
                            <label class="form-label">Select Bank</label>
                            <select class="form-select" id="bank_id">
                                <option value="">Select Bank</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}" {{ isset($sale) && $sale->bank_id == $bank->id ? 'selected' : '' }}>
                                        {{ $bank->name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="payment-summary__actions">
                        <button class="themeBtn themeBtn--white w-100" onclick="submitSale('completed')">
                            {{ isset($sale) ? 'Update & Complete' : 'Complete Sale & Print' }}
                        </button>
                        <button class="themeBtn themeBtn--gray w-100" onclick="submitSale('draft')">
                            {{ isset($sale) ? 'Update Draft' : 'Save & Draft' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Draft Sales Table -->
        @if($draftSales->count() > 0)
        <div class="sales-table-section mt-5">
            <h4 class="text-warning">Draft / Held Orders</h4>
            <div class="table-responsive">
                <table class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($draftSales as $draft)
                        <tr>
                            <td>{{ $draft->id }}</td>
                            <td>{{ $draft->date }}</td>
                            <td>{{ $draft->customer->name ?? 'N/A' }}</td>
                            <td>{{ $globalSettings->currency ?? '$' }}{{ number_format($draft->grand_total, 2) }}</td>
                            <td>
                                <a href="{{ route('sales.edit', $draft->id) }}" class="btn btn-sm btn-warning" title="Resume Sale">
                                    <i class="fa-solid fa-play"></i> Resume
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Recent Sales Table -->
        <div class="sales-table-section mt-5">
            <h4>Recent Completed Sales</h4>
            <div class="table-responsive">
                <table class="dataTable table table-hover tables">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSales as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td>{{ $s->date }}</td>
                            <td>{{ $s->customer->name ?? 'N/A' }}</td>
                            <td>{{ $globalSettings->currency ?? '$' }}{{ number_format($s->grand_total, 2) }}</td>
                            <td>
                                <span class="badge {{ $s->status == 'completed' ? 'bg-success' : ($s->status == 'returned' ? 'bg-danger' : 'bg-warning') }}">
                                    {{ ucfirst($s->status) }}
                                </span>
                            </td>
                            <td>
                                @if($s->status == 'completed' || $s->status == 'returned')
                                <a href="{{ route('sales.slip', $s->id) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Print Slip">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewSale({{ $s->id }})" title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @else
                                <a href="{{ route('sales.edit', $s->id) }}" class="btn btn-sm btn-outline-warning" title="Edit/Resume">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Load Sale for Return</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Sale ID</label>
                    <input type="number" class="form-control" id="returnSaleId" placeholder="Enter Sale ID">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="loadReturnSale()">Load</button>
            </div>
        </div>
    </div>
</div>

<!-- Sale Details Modal -->
<div class="modal fade" id="viewSaleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sale Details #<span id="viewSaleId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Customer:</strong> <span id="viewCustomer"></span></p>
                        <p><strong>Phone:</strong> <span id="viewPhone"></span></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>Date:</strong> <span id="viewDate"></span></p>
                        <p><strong>Ref No:</strong> <span id="viewRef"></span></p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="viewItems"></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th id="viewSubtotal"></th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Discount:</th>
                                <th id="viewDiscount"></th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Tax:</th>
                                <th id="viewTax"></th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Grand Total:</th>
                                <th id="viewGrandTotal"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const currencySymbol = "{{ $globalSettings->currency ?? '$' }}";
    @php
        $cartData = [];
        if(isset($sale) && $sale->items) {
            $isCompleted = $sale->status === 'completed';
            $cartData = $sale->items->map(function($item) use ($isCompleted) {
                $stock = $item->product->stock_quantity;
                if ($isCompleted) {
                    $stock += $item->quantity;
                }
                
                $taxRate = 0;
                if($item->product->subCategory && $item->product->subCategory->taxes) {
                    foreach($item->product->subCategory->taxes as $tax) {
                        $taxRate += $tax->pivot->tax_rate;
                    }
                }

                return [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => (float)$item->price,
                    'quantity' => (float)$item->quantity,
                    'stock' => (float)$stock,
                    'total' => (float)$item->total,
                    'taxRate' => (float)$taxRate
                ];
            });
        }
    @endphp
    let cart = @json($cartData);

    $(document).ready(function() {
        // Toggle Walk-in Fields
        $('#customer_id').change(function() {
            if ($(this).val() === 'walk-in') {
                $('.walk-in-fields').slideDown();
            } else {
                $('.walk-in-fields').slideUp();
            }
        });
        // Trigger initially
        $('#customer_id').trigger('change');

        // Toggle Bank Dropdown
        $('#payment_method').change(function() {
            let method = $(this).val();
            if (method === 'card' || method === 'bank_transfer') {
                $('#bank_div').slideDown();
            } else {
                $('#bank_div').slideUp();
                $('#bank_id').val('');
            }
        });
        $('#payment_method').trigger('change');

        // Initial Render
        renderCart();

        // Product Search
        $('#product_search').on('input', function() {
            let query = $(this).val();
            if (query.length > 1) {
                $.ajax({
                    url: "{{ route('sales.search-products') }}",
                    data: { q: query },
                    success: function(products) {
                        let html = '';
                        products.forEach(p => {
                            let stockLabel = p.stock_quantity > 0 ? `<span class="badge bg-success">Stock: ${p.stock_quantity}</span>` : `<span class="badge bg-danger">Out of Stock</span>`;
                            
                            let taxRate = 0;
                            if(p.sub_category && p.sub_category.taxes) {
                                p.sub_category.taxes.forEach(t => taxRate += parseFloat(t.pivot.tax_rate));
                            }

                            html += `<a href="#" class="list-group-item list-group-item-action product-item" 
                                data-id="${p.id}" 
                                data-name="${p.name}" 
                                data-price="${p.selling_price}" 
                                data-stock="${p.stock_quantity}"
                                data-code="${p.code}"
                                data-tax-rate="${taxRate}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>${p.name} (${p.code})</span>
                                    <span>Price: ${p.selling_price} | Tax: ${taxRate}% | ${stockLabel}</span>
                                </div>
                            </a>`;
                        });
                        $('#product_results').html(html).show();
                    }
                });
            } else {
                $('#product_results').hide();
            }
        });

        // Add Product to Cart
        $(document).on('click', '.product-item', function(e) {
            e.preventDefault();
            let product = $(this).data();
            let stock = parseFloat(product.stock);

            if (stock <= 0) {
                alert('This product is out of stock.');
                return;
            }
            
            // Check if exists
            let existing = cart.find(i => i.id === product.id);
            if (existing) {
                if (existing.quantity + 1 > existing.stock) {
                    alert('Cannot add more. Out of stock.');
                    return;
                }
                existing.quantity++;
                existing.total = existing.quantity * existing.price;
            } else {
                cart.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    quantity: 1,
                    stock: stock,
                    total: parseFloat(product.price),
                    taxRate: parseFloat(product.taxRate) || 0
                });
            }
            
            renderCart();
            $('#product_results').hide();
            $('#product_search').val('');
        });

        // Remove from Cart
        $(document).on('click', '.remove-item', function() {
            let index = $(this).data('index');
            cart.splice(index, 1);
            renderCart();
        });

        // Update Quantity
        $(document).on('change', '.cart-qty', function() {
            let index = $(this).data('index');
            let newQty = parseFloat($(this).val());
            let item = cart[index];

            if (newQty > 0 && newQty > item.stock) {
                alert('Quantity exceeds available stock (' + item.stock + ')');
                $(this).val(item.quantity); // Reset to previous valid qty
                return;
            }

            cart[index].quantity = newQty;
            cart[index].total = newQty * cart[index].price;
            renderCart();
        });

        // Update calculations when discount changes
        $('#summaryDiscount').on('change', function() {
            calculateTotals();
        });
    });

    function renderCart() {
        let html = '';
        let totalTax = 0;

        if (cart.length === 0) {
            html = '<tr id="emptyCartRow"><td colspan="5" class="text-center">No items in cart</td></tr>';
        } else {
            cart.forEach((item, index) => {
                let rowClass = item.quantity < 0 ? 'table-danger' : '';
                
                // Calculate tax for this item
                // Assuming tax is exclusive (added to price)
                let itemTax = (item.price * item.quantity) * (item.taxRate / 100);
                totalTax += itemTax;

                html += `
                    <tr class="${rowClass}">
                        <td>${item.name} <small class="text-muted">(${item.taxRate}%)</small></td>
                        <td>${currencySymbol}${item.price.toFixed(2)}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm cart-qty" 
                                data-index="${index}" value="${item.quantity}" step="1" style="width: 80px">
                        </td>
                        <td>${currencySymbol}${item.total.toFixed(2)}</td>
                        <td>
                            <button class="btn btn-sm btn-danger remove-item" data-index="${index}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        $('#cartTable tbody').html(html);
        $('#summaryTax').val(totalTax.toFixed(2));
        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = cart.reduce((sum, item) => sum + item.total, 0);
        let discount = parseFloat($('#summaryDiscount').val()) || 0;
        let tax = parseFloat($('#summaryTax').val()) || 0;
        
        let grandTotal = subtotal - discount + tax;

        $('#summarySubtotal').text(subtotal.toFixed(2));
        $('#summaryGrandTotal').text(grandTotal.toFixed(2));
    }

    function loadReturnSale() {
        let id = $('#returnSaleId').val();
        if(!id) return;

        $.ajax({
            url: "{{ url('sales') }}/" + id + "/json",
            success: function(res) {
                if(res.success) {
                    let sale = res.sale;
                    
                    if(sale.customer_id) {
                         $('#customer_id').val(sale.customer_id).trigger('change');
                    } else {
                         $('#customer_id').val('walk-in').trigger('change');
                         setTimeout(() => {
                             $('#customer_name').val(sale.customer ? sale.customer.name : '');
                             $('#customer_phone').val(sale.customer ? sale.customer.phone : '');
                         }, 100);
                    }

                    cart = [];
                    
                    sale.items.forEach(item => {
                        // For return, we need tax rate too.
                        // Assuming the JSON response includes product details with tax
                        // We might need to update the JSON response in controller to include tax info
                        
                        let taxRate = 0;
                        if(item.product && item.product.sub_category && item.product.sub_category.taxes) {
                            item.product.sub_category.taxes.forEach(t => taxRate += parseFloat(t.pivot.tax_rate));
                        }

                        cart.push({
                            id: item.product_id,
                            name: item.product.name,
                            price: parseFloat(item.price),
                            quantity: -1 * parseFloat(item.quantity),
                            stock: parseFloat(item.product.stock_quantity),
                            total: -1 * parseFloat(item.total),
                            taxRate: taxRate
                        });
                    });
                    
                    renderCart();
                    $('#returnModal').modal('hide');
                } else {
                    alert('Sale not found.');
                }
            },
            error: function() {
                alert('Error loading sale.');
            }
        });
    }

    function viewSale(id) {
        $.ajax({
            url: "{{ url('sales') }}/" + id + "/json",
            success: function(res) {
                if(res.success) {
                    let sale = res.sale;
                    $('#viewSaleId').text(sale.id);
                    $('#viewCustomer').text(sale.customer ? sale.customer.name : 'Walk-in');
                    $('#viewPhone').text(sale.customer ? sale.customer.phone : 'N/A');
                    $('#viewDate').text(sale.date);
                    $('#viewRef').text(sale.reference_no || 'N/A');

                    let itemsHtml = '';
                    sale.items.forEach(item => {
                        let rowClass = item.quantity < 0 ? 'table-danger' : '';
                        itemsHtml += `
                            <tr class="${rowClass}">
                                <td>${item.product.name}</td>
                                <td>${parseFloat(item.quantity)}</td>
                                <td>${currencySymbol}${parseFloat(item.price).toFixed(2)}</td>
                                <td>${currencySymbol}${parseFloat(item.total).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    $('#viewItems').html(itemsHtml);
                    
                    $('#viewSubtotal').text(currencySymbol + parseFloat(sale.subtotal).toFixed(2));
                    $('#viewDiscount').text(currencySymbol + parseFloat(sale.discount).toFixed(2));
                    $('#viewTax').text(currencySymbol + parseFloat(sale.tax).toFixed(2));
                    $('#viewGrandTotal').text(currencySymbol + parseFloat(sale.grand_total).toFixed(2));

                    $('#viewSaleModal').modal('show');
                } else {
                    alert('Sale details not found.');
                }
            },
            error: function() {
                alert('Error fetching details.');
            }
        });
    }

    function submitSale(status) {
        if (cart.length === 0) {
            alert('Please add items to the cart first.');
            return;
        }

        let customerId = $('#customer_id').val();
        let customerName = $('#customer_name').val();
        let customerPhone = $('#customer_phone').val();

        if (customerId === 'walk-in' && (!customerName || !customerPhone)) {
             if(!customerName || !customerPhone) {
                alert('Please enter Walk-in Customer Name and Phone.');
                return;
             }
        }

        let isEdit = {{ isset($sale) ? 'true' : 'false' }};
        let saleId = {{ isset($sale) ? $sale->id : 'null' }};
        let url = isEdit ? "{{ url('sales') }}/" + saleId : "{{ route('sales.store') }}";
        let type = isEdit ? "PUT" : "POST";

        let data = {
            _token: "{{ csrf_token() }}",
            customer_id: customerId,
            customer_name: customerName,
            customer_phone: customerPhone,
            date: $('#salesDate').val(),
            reference_no: $('#reference_no').val(),
            items: cart.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                price: item.price,
                total: item.total
            })),
            subtotal: parseFloat($('#summarySubtotal').text()),
            discount: parseFloat($('#summaryDiscount').val()) || 0,
            tax: parseFloat($('#summaryTax').val()) || 0,
            grand_total: parseFloat($('#summaryGrandTotal').text()),
            payment_method: $('#payment_method').val(),
            bank_id: $('#bank_id').val(),
            status: status
        };

        $.ajax({
            url: url,
            type: type,
            data: data,
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    if (status === 'completed') {
                         window.open("{{ url('sales') }}/" + response.sale_id + "/details", '_blank');
                    }
                    window.location.href = "{{ route('sales.index') }}";
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON.message || 'Unknown error'));
            }
        });
    }
</script>
@endpush