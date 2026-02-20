<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        if ($users->count() === 0) {
            $this->command->info('No users found, skipping Payroll seeding.');
            return;
        }

        // Seed last 3 months payroll for each user
        foreach ($users as $user) {
            for ($m = 0; $m < 3; $m++) {
                $salaryMonth = Carbon::now()->startOfMonth()->subMonths($m);

                $basic = rand(30000, 120000);
                $allowances = rand(0, (int)($basic * 0.3));
                $incomeTax = (int)($basic * 0.05);
                $pf = rand(0, 2000);
                $loanDeduction = rand(0, 5000);
                $otherDeductions = rand(0, 1500);

                $totalDeductions = $incomeTax + $pf + $loanDeduction + $otherDeductions;
                $net = $basic + $allowances - $totalDeductions;

                Payroll::create([
                    'user_id' => $user->id,
                    'salary_month' => $salaryMonth->format('Y-m-d'),
                    'basic_salary' => $basic,
                    'allowances' => $allowances,
                    'income_tax' => $incomeTax,
                    'fund_deduction' => $pf,
                    'loan_deduction' => $loanDeduction,
                    'other_deductions' => $otherDeductions,
                    'net_salary' => $net,
                    'payment_date' => $salaryMonth->copy()->addDays(28)->format('Y-m-d'),
                    'payment_mode' => 'Online Transfer',
                    'status' => rand(0, 1) ? 'Paid' : 'Pending',
                    'notes' => 'Seeded payroll record.',
                ]);
            }
        }
    }
}
