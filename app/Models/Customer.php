<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'cnic',
        'address',
        'status',
        'nic_front',
        'nic_back',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    /**
     * Outstanding credit (udhaar) across the stores the current user can see.
     */
    public function dueAmount(): float
    {
        return (float) $this->sales()->where('status', 'completed')->where('due_amount', '>', 0)->sum('due_amount');
    }
}
