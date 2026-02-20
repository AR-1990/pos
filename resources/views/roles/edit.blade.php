@extends('layouts.app')

@section('content')

<div class="roles-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Edit Role</h2>   
        <a href="{{ route('roles.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ route('roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Role Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $role->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control @error('description') is-invalid @enderror" name="description" value="{{ old('description', $role->description) }}">
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status">
                        <option value="Active" {{ old('status', $role->status) == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $role->status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <h4 class="mb-3">Permissions</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Module</th>
                                    <th class="text-center"><input type="checkbox" id="selectAllView"> View</th>
                                    <th class="text-center"><input type="checkbox" id="selectAllAdd"> Add</th>
                                    <th class="text-center"><input type="checkbox" id="selectAllEdit"> Edit</th>
                                    <th class="text-center"><input type="checkbox" id="selectAllDelete"> Delete</th>
                                    <th class="text-center"><input type="checkbox" id="selectAllStatus"> Active/Deactive</th>
                                    <th class="text-center"><input type="checkbox" id="selectAll"> All</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $key => $label)
                                @php
                                    $perm = $rolePermissions[$key] ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-view" type="checkbox" name="permissions[{{ $key }}][view]" id="view_{{ $key }}" {{ $perm && $perm->can_view ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-add" type="checkbox" name="permissions[{{ $key }}][add]" id="add_{{ $key }}" {{ $perm && $perm->can_add ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-edit" type="checkbox" name="permissions[{{ $key }}][edit]" id="edit_{{ $key }}" {{ $perm && $perm->can_edit ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-delete" type="checkbox" name="permissions[{{ $key }}][delete]" id="delete_{{ $key }}" {{ $perm && $perm->can_delete ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-status" type="checkbox" name="permissions[{{ $key }}][status]" id="status_{{ $key }}" {{ $perm && $perm->can_status ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-all-row" type="checkbox" data-row="{{ $key }}">
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="form-actions mt-4 text-end">
                <button type="submit" class="themeBtn">Update Role</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Select All for each column
        $('#selectAllView').change(function() {
            $('.perm-view').prop('checked', $(this).prop('checked'));
        });
        $('#selectAllAdd').change(function() {
            $('.perm-add').prop('checked', $(this).prop('checked'));
        });
        $('#selectAllEdit').change(function() {
            $('.perm-edit').prop('checked', $(this).prop('checked'));
        });
        $('#selectAllDelete').change(function() {
            $('.perm-delete').prop('checked', $(this).prop('checked'));
        });
        $('#selectAllStatus').change(function() {
            $('.perm-status').prop('checked', $(this).prop('checked'));
        });

        // Select All Global
        $('#selectAll').change(function() {
            $('input[type="checkbox"]').prop('checked', $(this).prop('checked'));
        });

        // Select All for Row
        $('.perm-all-row').change(function() {
            let row = $(this).data('row');
            $(`input[name^="permissions[${row}]"]`).prop('checked', $(this).prop('checked'));
        });
    });
</script>
@endpush

@endsection
