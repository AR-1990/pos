<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'sub_category_tax')->withPivot('tax_rate');
    }
}
