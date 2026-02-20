<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\ExpenseHead;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Expense Heads
        $heads = ['Rent', 'Utilities', 'Office Supplies', 'Maintenance', 'Travel', 'Salaries', 'Other'];
        foreach ($heads as $head) {
            ExpenseHead::firstOrCreate(['name' => $head], ['code' => strtoupper(substr($head, 0, 3)), 'status' => 1]);
        }

        // Seed Expenses
        Expense::truncate();
        
        $heads = ExpenseHead::all();
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 50; $i++) {
            Expense::create([
                'date' => $faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'reference_no' => 'EXP-' . $faker->unique()->numberBetween(1000, 9999),
                'expense_head_id' => $heads->random()->id,
                'description' => $faker->sentence(4),
                'amount' => $faker->randomFloat(2, 50, 5000),
                'payment_mode' => $faker->randomElement(['Cash', 'Online Transfer']),
                'paid_to' => $faker->company,
                'status' => $faker->randomElement(['Approved', 'Pending', 'Rejected']),
                'remarks' => $faker->sentence,
            ]);
        }
    }
}
