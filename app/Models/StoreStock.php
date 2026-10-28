<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class StoreStock extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'product_id', 'quantity'];

    protected $casts = ['quantity' => 'float'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
