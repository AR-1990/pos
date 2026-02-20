<?php

namespace Database\Seeders;

use App\Models\CashbookHead;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CashbookHeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $heads = [
            ['name' => 'Opening Balance', 'code' => 'CB-001', 'status' => 'Active'],
            ['name' => 'Cash Sales', 'code' => 'CB-002', 'status' => 'Active'],
            ['name' => 'Capital Injection', 'code' => 'CB-003', 'status' => 'Active'],
            ['name' => 'Petty Cash', 'code' => 'CB-004', 'status' => 'Active'],
            ['name' => 'Loan Recovery', 'code' => 'CB-005', 'status' => 'Inactive'],
        ];

        foreach ($heads as $head) {
            CashbookHead::create($head);
        }
    }
}
