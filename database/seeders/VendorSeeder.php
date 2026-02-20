<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = [
            [
                'name' => 'Tech Supplies Co.',
                'code' => 'V-001',
                'phone' => '0300-1234567',
                'cnic' => '42201-1234567-1',
                'address' => 'Shop #12, Techno Plaza, Karachi',
                'status' => 'Active',
            ],
            [
                'name' => 'Office Mart',
                'code' => 'V-002',
                'phone' => '0333-7654321',
                'cnic' => '42101-7654321-2',
                'address' => 'Plot 45, Industrial Area, Lahore',
                'status' => 'Active',
            ],
            [
                'name' => 'Global Traders',
                'code' => 'V-003',
                'phone' => '0345-1122334',
                'cnic' => '42301-1122334-3',
                'address' => 'Warehouse 3, Port Qasim, Karachi',
                'status' => 'Inactive',
            ],
            [
                'name' => 'Ali Electronics',
                'code' => 'V-004',
                'phone' => '0321-9988776',
                'cnic' => '42401-9988776-4',
                'address' => 'Saddar Market, Karachi',
                'status' => 'Active',
            ],
            [
                'name' => 'Best Stationery',
                'code' => 'V-005',
                'phone' => '0312-5566778',
                'cnic' => '42501-5566778-5',
                'address' => 'Blue Area, Islamabad',
                'status' => 'Active',
            ],
        ];

        foreach ($vendors as $vendor) {
            Vendor::firstOrCreate(
                ['code' => $vendor['code']],
                $vendor
            );
        }
    }
}
