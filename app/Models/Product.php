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

    /**
     * In-store EAN-13 for a product: "200" (GS1 range reserved for in-store codes)
     * + the zero-padded id + check digit. Unique because ids are; falls back to a
     * random 2xx code if someone typed that exact number on another product.
     */
    public static function generateBarcode(int $id): string
    {
        $code = static::ean13('200' . str_pad((string) $id, 9, '0', STR_PAD_LEFT));

        while (static::where('barcode', $code)->where('id', '!=', $id)->exists()) {
            $code = static::ean13('2' . random_int(10, 99) . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT));
        }

        return $code;
    }

    /**
     * Appends the EAN-13 check digit to 12 digits.
     */
    public static function ean13(string $twelve): string
    {
        $sum = 0;
        foreach (str_split($twelve) as $i => $digit) {
            $sum += (int) $digit * ($i % 2 ? 3 : 1);
        }

        return $twelve . ((10 - $sum % 10) % 10);
    }
}
