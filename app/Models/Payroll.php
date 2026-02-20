<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'salary_month',
        'basic_salary',
        'allowances',
        'income_tax',
        'fund_deduction',
        'loan_deduction',
        'other_deductions',
        'deduction_reason',
        'net_salary',
        'payment_date',
        'payment_mode',
        'status',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
