<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'profile_image',
        'password',
        'role_id',
        'store_id',
        'identity_number',
        'identity_card_front',
        'identity_card_back',
        'joining_date',
        'salary',
        'designation',
        'phone',
        'address',
        'cv_path',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Admins (Super Admin, or any role given "All Stores" view permission)
     * can see and sell for every store. Everyone else is locked to their store.
     */
    public function canViewAllStores()
    {
        return $this->hasPermission('all_stores', 'view');
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($module, $action)
    {
        if (!$this->role) {
            return false;
        }
        return $this->role->hasPermission($module, $action);
    }
}
