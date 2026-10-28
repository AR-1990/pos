<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'product_id', 'quantity', 'balance_after', 'type', 'reference_type', 'reference_id', 'user_id', 'note'];

    protected $casts = ['quantity' => 'float', 'balance_after' => 'float'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
