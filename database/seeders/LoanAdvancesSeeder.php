<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoanAdvance;
use App\Models\User;
use Carbon\Carbon;

class LoanAdvancesSeeder extends Seeder
{
    public function run()
    {
        // Get all users
        $users = User::all();

        if ($users->count() == 0) {
            $this->command->info('No users found, skipping Loan/Advance seeding.');
            return;
        }

        // Loan Types
        $types = ['Advance Salary', 'Personal Loan', 'Emergency Loan', 'Housing Loan', 'Vehicle Loan'];
        
        // Repayment Plans
        $plans = ['Monthly Installments', 'Weekly Installments', 'Lump Sum'];

        // Statuses
        $statuses = ['Active', 'Paid', 'Pending', 'Rejected'];

        foreach ($users as $user) {
            // Create 1-3 loans per user
            $numberOfLoans = rand(1, 3);

            for ($i = 0; $i < $numberOfLoans; $i++) {
                $amount = rand(5000, 50000);
                $paidAmount = 0;
                $status = $statuses[array_rand($statuses)];
                
                if ($status == 'Paid') {
                    $paidAmount = $amount;
                } elseif ($status == 'Active') {
                    $paidAmount = rand(0, $amount - 1000);
                }

                LoanAdvance::create([
                    'user_id' => $user->id,
                    'type' => $types[array_rand($types)],
                    'amount' => $amount,
                    'date' => Carbon::now()->subDays(rand(1, 180)),
                    'repayment_plan' => $plans[array_rand($plans)],
                    'installments' => rand(1, 12),
                    'paid_amount' => $paidAmount,
                    'status' => $status,
                    'notes' => 'Seeded loan record.',
                ]);
            }
        }
    }
}
