<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'code',
        'name',
        'category_id',
        'sub_category_id',
        'vendor_id',
        'unit_id',
        'unit_value',
        'secondary_unit',
        'pack_size',
        'barcode',
        'purchase_price',
        'selling_price',
        'stock_quantity', // Added
        'min_stock_level',
        'max_stock_level',
        'batch_expiry_enabled',
        'expiry_date',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function unit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'unit_id');
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'product_tax')->withPivot('tax_rate');
    }
}
