<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;
    use Concerns\BelongsToStore;

    protected $guarded = [];

    public function expenseHead()
    {
        return $this->belongsTo(ExpenseHead::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
