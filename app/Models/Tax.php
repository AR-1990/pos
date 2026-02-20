<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_type',
        'tax_rate',
        'applied_on',
        'effective_date',
        'status',
    ];

    public function subCategories()
    {
        return $this->belongsToMany(SubCategory::class, 'sub_category_tax')->withPivot('tax_rate');
    }
}
