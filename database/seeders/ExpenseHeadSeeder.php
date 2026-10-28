<?php

namespace Database\Seeders;

use App\Models\ExpenseHead;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseHeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $heads = [
            ['name' => 'Rent Expense', 'code' => 'EXP-001', 'status' => 1],
            ['name' => 'Electricity Bill', 'code' => 'EXP-002', 'status' => 1],
            ['name' => 'Salaries', 'code' => 'EXP-003', 'status' => 1],
            ['name' => 'Office Maintenance', 'code' => 'EXP-004', 'status' => 1],
            ['name' => 'Entertainment', 'code' => 'EXP-005', 'status' => 1],
            ['name' => 'Stationery', 'code' => 'EXP-006', 'status' => 0],
        ];

        foreach ($heads as $head) {
            ExpenseHead::firstOrCreate(['code' => $head['code']], $head);
        }
    }
}
