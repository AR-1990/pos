@extends('layouts.app')

@section('content')
<div class="app-card">
    <div class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label" for="fAction">Action</label>
            <select class="form-select" id="fAction">
                <option value="">All actions</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="fStart">From</label>
            <input type="date" class="form-control" id="fStart">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="fEnd">To</label>
            <input type="date" class="form-control" id="fEnd">
        </div>
        <div class="col-md-3 text-md-end">
            <button type="button" class="themeBtn themeBtn--gray" id="fReset"><i class="fa-solid fa-rotate"></i> Reset</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover" id="auditTable">
            <thead>
                <tr><th>When</th><th>User</th><th>Store</th><th>Action</th><th>What happened</th><th>IP</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        const table = $('#auditTable').DataTable({
            processing: true,
            serverSide: true,
            order: [],
            ajax: {
                url: @json(route('audit-logs.index')),
                data: d => { d.action = $('#fAction').val(); d.start_date = $('#fStart').val(); d.end_date = $('#fEnd').val(); }
            },
            columns: [
                { data: 'created_at', name: 'created_at', searchable: false },
                { data: 'user_name', name: 'user_name', orderable: false, searchable: false },
                { data: 'store_name', name: 'store_name', orderable: false, searchable: false },
                { data: 'action', name: 'action' },
                { data: 'description', name: 'description' },
                { data: 'ip', name: 'ip', orderable: false },
            ]
        });
        $('#fAction, #fStart, #fEnd').on('change', () => table.draw());
        $('#fReset').on('click', () => { $('#fAction, #fStart, #fEnd').val(''); table.draw(); });
    });
</script>
@endpush
