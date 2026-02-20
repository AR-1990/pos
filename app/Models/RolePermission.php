<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = [
        'role_id',
        'module',
        'can_view',
        'can_add',
        'can_edit',
        'can_delete',
        'can_status'
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_add' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_status' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
