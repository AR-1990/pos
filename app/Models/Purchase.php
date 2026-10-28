<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use BelongsToStore;

    protected $fillable = ['purchase_no', 'store_id', 'vendor_id', 'user_id', 'vendor_invoice_no', 'date', 'subtotal', 'discount', 'tax', 'grand_total', 'paid_amount', 'due_amount', 'payment_status', 'note'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }
}
