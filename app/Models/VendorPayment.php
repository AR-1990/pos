<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPayment extends Model
{
    protected $fillable = ['vendor_id', 'purchase_id', 'store_id', 'user_id', 'date', 'amount', 'method', 'bank_id', 'note'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
