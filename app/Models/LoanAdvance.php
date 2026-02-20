<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanAdvance extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'date',
        'repayment_plan',
        'installments',
        'paid_amount',
        'status',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class);
    }
}
