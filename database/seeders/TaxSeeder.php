<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tax;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            [
                'tax_type' => 'GST',
                'tax_rate' => 18.00,
                'applied_on' => 'Product Sales',
                'effective_date' => now()->toDateString(),
                'status' => 'Active',
            ],
            [
                'tax_type' => 'VAT',
                'tax_rate' => 5.00,
                'applied_on' => 'Services',
                'effective_date' => now()->toDateString(),
                'status' => 'Active',
            ],
            [
                'tax_type' => 'Sales Tax',
                'tax_rate' => 10.00,
                'applied_on' => 'Retail',
                'effective_date' => now()->toDateString(),
                'status' => 'Inactive',
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::create($tax);
        }
    }
}
