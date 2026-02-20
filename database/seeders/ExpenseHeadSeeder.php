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
            ['name' => 'Rent Expense', 'code' => 'EXP-001', 'status' => 'Active'],
            ['name' => 'Electricity Bill', 'code' => 'EXP-002', 'status' => 'Active'],
            ['name' => 'Salaries', 'code' => 'EXP-003', 'status' => 'Active'],
            ['name' => 'Office Maintenance', 'code' => 'EXP-004', 'status' => 'Active'],
            ['name' => 'Entertainment', 'code' => 'EXP-005', 'status' => 'Active'],
            ['name' => 'Stationery', 'code' => 'EXP-006', 'status' => 'Inactive'],
        ];

        foreach ($heads as $head) {
            ExpenseHead::create($head);
        }
    }
}
