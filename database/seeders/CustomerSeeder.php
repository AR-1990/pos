<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Head Office',
                'code' => 'HO-001',
                'phone' => '0301-1234567',
                'cnic' => '42101-1234567-1',
                'address' => 'Karachi, Pakistan',
                'status' => 'Inactive',
            ],
            [
                'name' => 'Branch Office - North',
                'code' => 'BR-002',
                'phone' => '0302-2345678',
                'cnic' => '35202-2345678-2',
                'address' => 'Lahore, Pakistan',
                'status' => 'Inactive',
            ],
            [
                'name' => 'Branch Office - South',
                'code' => 'BR-003',
                'phone' => '0303-3456789',
                'cnic' => '42103-3456789-3',
                'address' => 'Karachi, Pakistan',
                'status' => 'Active',
            ],
            [
                'name' => 'Warehouse - Central',
                'code' => 'WH-004',
                'phone' => '0304-4567890',
                'cnic' => '34104-4567890-4',
                'address' => 'Faisalabad, Pakistan',
                'status' => 'Inactive',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(['code' => $customer['code']], $customer);
        }
    }
}
