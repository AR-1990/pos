<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Discount;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discounts = [
            [
                'name' => 'Flat Discount',
                'amount' => 500,
                'status' => 'Inactive',
            ],
            [
                'name' => 'Percentage Discount',
                'amount' => 10,
                'status' => 'Inactive',
            ],
            [
                'name' => 'Seasonal Discount',
                'amount' => 15,
                'status' => 'Active',
            ],
            [
                'name' => 'Promotional Discount',
                'amount' => 20,
                'status' => 'Inactive',
            ],
            [
                'name' => 'Bulk Purchase Discount',
                'amount' => 1000,
                'status' => 'Active',
            ],
            [
                'name' => 'Loyalty Discount',
                'amount' => 5,
                'status' => 'Active',
            ],
            [
                'name' => 'Coupon Discount',
                'amount' => 750,
                'status' => 'Active',
            ],
            [
                'name' => 'Clearance Discount',
                'amount' => 25,
                'status' => 'Inactive',
            ],
            [
                'name' => 'Special Offer',
                'amount' => 2000,
                'status' => 'Active',
            ],
            [
                'name' => 'Festival Discount',
                'amount' => 30,
                'status' => 'Active',
            ],
        ];

        foreach ($discounts as $discount) {
            Discount::firstOrCreate(
                ['name' => $discount['name']],
                $discount
            );
        }
    }
}
