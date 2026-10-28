<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    // Employees only ever see their own store's sales; admins see all
    // (or the store picked in the header switcher)
    use BelongsToStore;

    protected $fillable = [
        'store_id',
        'user_id',
        'invoice_no',
        'return_of_sale_id',
        'shift_id',
        'customer_id',
        'date',
        'reference_no',
        'subtotal',
        'discount',
        'discount_type',
        'discount_value',
        'tax',
        'grand_total',
        'paid_amount',
        'due_amount',
        'payment_status',
        'status',
        'payment_method',
        'bank_id',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'voided_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returnOf()
    {
        return $this->belongsTo(Sale::class, 'return_of_sale_id');
    }

    public function returns()
    {
        return $this->hasMany(Sale::class, 'return_of_sale_id');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Invoice number shown to people; falls back to the id for old rows.
     */
    public function getNumberAttribute(): string
    {
        return $this->invoice_no ?: '#' . $this->id;
    }
}
