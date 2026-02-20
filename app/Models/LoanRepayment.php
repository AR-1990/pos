<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_advance_id',
        'user_id',
        'amount',
        'payment_date',
        'source',
        'ref_id',
        'notes',
    ];

    public function loanAdvance()
    {
        return $this->belongsTo(LoanAdvance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
