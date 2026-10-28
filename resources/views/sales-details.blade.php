@extends('layouts.app')

@section('content')
<div class="sale-details-page">
    <div class="dashboard-content__head">
        <h2 class="heading">Sale Details #{{ $sale->id }}</h2>
        <div>
            <a href="{{ route('sales.slip', $sale->id) }}" target="_blank" class="btn btn-primary me-2">
                <i class="fa-solid fa-print"></i> Print Slip
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="dashboard-card">
        <!-- Header Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="text-primary mb-3">Customer Information</h5>
                <p class="mb-1"><strong>Name:</strong> {{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</p>
                <p class="mb-1"><strong>Phone:</strong> {{ $sale->customer ? $sale->customer->phone : 'N/A' }}</p>
                @if($sale->customer && $sale->customer->code)
                <p class="mb-1"><strong>Customer Code:</strong> {{ $sale->customer->code }}</p>
                @endif
            </div>
            <div class="col-md-6 text-md-end">
                <h5 class="text-primary mb-3">Sale Information</h5>
                <p class="mb-1"><strong>Store:</strong> {{ $sale->store->name ?? "-" }}</p>
                <p class="mb-1"><strong>Cashier:</strong> {{ $sale->user->name ?? "-" }}</p>
                <p class="mb-1"><strong>Date:</strong> {{ $sale->date }}</p>
                <p class="mb-1"><strong>Status:</strong> 
                    <span class="badge {{ $sale->status == 'completed' ? 'bg-success' : ($sale->status == 'returned' ? 'bg-danger' : 'bg-warning') }}">
                        {{ ucfirst($sale->status) }}
                    </span>
                </p>
                <p class="mb-1"><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</p>
                @if($sale->reference_no)
                <p class="mb-1"><strong>Ref No:</strong> {{ $sale->reference_no }}</p>
                @endif
            </div>
        </div>

        <hr class="my-4">

        <!-- Items Table -->
        <h5 class="text-primary mb-3">Order Items</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td class="text-end">{{ $globalSettings->currency ?? '$' }}{{ number_format($item->price, 2) }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ $globalSettings->currency ?? '$' }}{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Subtotal:</th>
                        <th class="text-end">{{ $globalSettings->currency ?? '$' }}{{ number_format($sale->subtotal, 2) }}</th>
                    </tr>
                    @if($sale->discount > 0)
                    <tr>
                        <th colspan="4" class="text-end">Discount:</th>
                        <th class="text-end text-danger">-{{ $globalSettings->currency ?? '$' }}{{ number_format($sale->discount, 2) }}</th>
                    </tr>
                    @endif
                    @if($sale->tax > 0)
                    <tr>
                        <th colspan="4" class="text-end">Tax:</th>
                        <th class="text-end">+{{ $globalSettings->currency ?? '$' }}{{ number_format($sale->tax, 2) }}</th>
                    </tr>
                    @endif
                    <tr class="table-active">
                        <th colspan="4" class="text-end fs-5">Grand Total:</th>
                        <th class="text-end fs-5">
                            <strong class="{{ $sale->grand_total < 0 ? 'text-danger' : 'text-success' }}">
                                {{ $globalSettings->currency ?? '$' }}{{ number_format($sale->grand_total, 2) }}
                            </strong>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
