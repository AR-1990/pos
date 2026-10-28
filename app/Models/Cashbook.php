<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashbook extends Model
{
    use HasFactory;
    use Concerns\BelongsToStore;

    protected $fillable = [
        'store_id',
        'transaction_date',
        'voucher_no',
        'description',
        'cashbook_head_id',
        'bank_id',
        'payment_mode',
        'type',
        'amount',
        'reference_no',
        'remarks',
    ];

    public function cashbookHead()
    {
        return $this->belongsTo(CashbookHead::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
