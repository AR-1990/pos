<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description', 'status'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * Check if role has permission for a specific module and action
     */
    public function hasPermission($module, $action)
    {
        // Super Admin role usually has all permissions
        if ($this->name === 'Super Admin') {
            return true;
        }

        $permission = $this->permissions->where('module', $module)->first();
        
        if (!$permission) {
            return false;
        }

        switch ($action) {
            case 'view':
                return $permission->can_view;
            case 'add':
            case 'create':
            case 'store':
                return $permission->can_add;
            case 'edit':
            case 'update':
                return $permission->can_edit;
            case 'delete':
            case 'destroy':
                return $permission->can_delete;
            case 'status':
                return $permission->can_status;
            default:
                return false;
        }
    }
}
