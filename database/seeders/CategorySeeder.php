<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'CAT001',
                'name' => 'Electronics',
                'description' => 'Electronic devices and accessories',
                'status' => 'active',
            ],
            [
                'code' => 'CAT002',
                'name' => 'Clothing',
                'description' => 'Men and Women clothing',
                'status' => 'active',
            ],
            [
                'code' => 'CAT003',
                'name' => 'Home & Garden',
                'description' => 'Furniture and home decor',
                'status' => 'active',
            ],
            [
                'code' => 'CAT004',
                'name' => 'Toys & Hobbies',
                'description' => 'Kids toys and hobby items',
                'status' => 'active',
            ],
            [
                'code' => 'CAT005',
                'name' => 'Books',
                'description' => 'Educational and fictional books',
                'status' => 'active',
            ],
             [
                'code' => 'CAT006',
                'name' => 'Sports',
                'description' => 'Sports equipment and gear',
                'status' => 'active',
            ],
            [
                'code' => 'CAT007',
                'name' => 'Beauty & Health',
                'description' => 'Cosmetics and health products',
                'status' => 'inactive',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['code' => $category['code']], // Check by code to avoid duplicates
                $category
            );
        }
    }
}
