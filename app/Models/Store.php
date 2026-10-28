<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'address',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Next invoice number for a store, e.g. ST-002-000124. Call inside a transaction:
     * the row lock keeps two tills from getting the same number.
     */
    public static function nextInvoiceNo(int $storeId): string
    {
        $store = static::whereKey($storeId)->lockForUpdate()->firstOrFail();
        $store->increment('invoice_counter');

        return $store->code . '-' . str_pad($store->invoice_counter, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Store id that sales should be limited to for the logged-in user.
     * null = no limit (admin viewing all stores).
     * 0    = employee with no store assigned, sees nothing.
     */
    public static function scopedId()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        if ($user->canViewAllStores()) {
            $selected = session('store_filter');
            return $selected ? (int) $selected : null;
        }

        return $user->store_id ? (int) $user->store_id : 0;
    }
}
