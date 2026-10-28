<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = \App\Models\Category::first() ?? \App\Models\Category::create([
            'name' => 'General',
            'status' => 'Active'
        ]);

        $subCategory = \App\Models\SubCategory::first() ?? \App\Models\SubCategory::create([
            'category_id' => $category->id,
            'code' => 'SUB-GEN',
            'name' => 'General Sub',
            'status' => 'Active'
        ]);

        $unit = \App\Models\MeasurementUnit::first() ?? \App\Models\MeasurementUnit::create([
            'name' => 'Piece',
            'short_name' => 'Pcs',
            'status' => 'Active'
        ]);

        $vendor = \App\Models\Vendor::first() ?? \App\Models\Vendor::create([
            'name' => 'General Vendor',
            'email' => 'vendor@example.com',
            'phone' => '1234567890',
            'status' => 'Active'
        ]);

        \App\Models\Product::create([
            'sku' => 'SKU-001',
            'code' => 'PDC-001',
            'name' => 'Sample Product 1',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'vendor_id' => $vendor->id,
            'unit_id' => $unit->id,
            'unit_value' => 1,
            'purchase_price' => 100.00,
            'selling_price' => 150.00,
            'min_stock_level' => 10,
            'status' => 'Active',
        ]);

        \App\Models\Product::create([
            'sku' => 'SKU-002',
            'code' => 'PDC-002',
            'name' => 'Sample Product 2',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'vendor_id' => $vendor->id,
            'unit_id' => $unit->id,
            'unit_value' => 1,
            'purchase_price' => 200.00,
            'selling_price' => 300.00,
            'min_stock_level' => 5,
            'status' => 'Inactive',
        ]);
    }
}
