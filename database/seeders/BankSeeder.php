<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bank;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            [
                'name' => 'Meezan Bank',
                'account_title' => 'My Business Account',
                'account_number' => '01010101010101',
                'branch_code' => '0101',
                'branch_name' => 'Main Branch',
                'iban' => 'PK00MEZN0000000101010101',
                'status' => 'Active',
            ],
            [
                'name' => 'HBL',
                'account_title' => 'Main Operating Account',
                'account_number' => '02020202020202',
                'branch_code' => '0202',
                'branch_name' => 'Corporate Branch',
                'iban' => 'PK00HABB0000000202020202',
                'status' => 'Active',
            ],
            [
                'name' => 'UBL',
                'account_title' => 'Savings Account',
                'account_number' => '03030303030303',
                'branch_code' => '0303',
                'branch_name' => 'City Branch',
                'iban' => 'PK00UNIL0000000303030303',
                'status' => 'Active',
            ],
            [
                'name' => 'Bank Alfalah',
                'account_title' => 'Expense Account',
                'account_number' => '04040404040404',
                'branch_code' => '0404',
                'branch_name' => 'Mall Road Branch',
                'iban' => 'PK00ALFH0000000404040404',
                'status' => 'Inactive',
            ],
        ];

        foreach ($banks as $bank) {
            Bank::create($bank);
        }
    }
}
