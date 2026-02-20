<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Head Office',
                'code' => 'HO-001',
                'address' => 'Karachi, Pakistan',
                'status' => 'Active',
            ],
            [
                'name' => 'Branch Office - North',
                'code' => 'BR-002',
                'address' => 'Lahore, Pakistan',
                'status' => 'Active',
            ],
            [
                'name' => 'Warehouse - South',
                'code' => 'WH-003',
                'address' => 'Karachi, Pakistan',
                'status' => 'Active',
            ],
            [
                'name' => 'Warehouse - Central',
                'code' => 'WH-004',
                'address' => 'Faisalabad, Pakistan',
                'status' => 'Inactive',
            ],
            [
                'name' => 'Outlet - Clifton',
                'code' => 'OT-005',
                'address' => 'Karachi, Pakistan',
                'status' => 'Active',
            ],
            [
                'name' => 'Outlet - DHA',
                'code' => 'OT-006',
                'address' => 'Karachi, Pakistan',
                'status' => 'Active',
            ],
            [
                'name' => 'Distribution Center',
                'code' => 'DC-007',
                'address' => 'Multan, Pakistan',
                'status' => 'Active',
            ],
            [
                'name' => 'Service Center',
                'code' => 'SC-008',
                'address' => 'Islamabad, Pakistan',
                'status' => 'Inactive',
            ],
            [
                'name' => 'Regional Office',
                'code' => 'RO-009',
                'address' => 'Peshawar, Pakistan',
                'status' => 'Active',
            ],
            [
                'name' => 'Temporary Office',
                'code' => 'TO-010',
                'address' => 'Rawalpindi, Pakistan',
                'status' => 'Active',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::firstOrCreate(
                ['code' => $warehouse['code']],
                $warehouse
            );
        }
    }
}
