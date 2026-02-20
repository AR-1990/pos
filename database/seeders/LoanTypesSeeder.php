<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoanType;

class LoanTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $loanTypes = [
            [
                'name' => 'Advance Salary',
                'description' => 'Short-term advance on salary.',
                'status' => 'Active',
            ],
            [
                'name' => 'Personal Loan',
                'description' => 'General purpose personal loan.',
                'status' => 'Active',
            ],
            [
                'name' => 'Emergency Loan',
                'description' => 'Loan for medical or other emergencies.',
                'status' => 'Active',
            ],
            [
                'name' => 'Housing Loan',
                'description' => 'Loan for house purchase or renovation.',
                'status' => 'Active',
            ],
            [
                'name' => 'Vehicle Loan',
                'description' => 'Loan for purchasing a vehicle.',
                'status' => 'Active',
            ],
        ];

        foreach ($loanTypes as $type) {
            LoanType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
