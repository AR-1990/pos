<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CashbookHead;
use App\Models\Bank;
use Carbon\Carbon;

class CashbookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have some heads and banks
        $heads = CashbookHead::pluck('id')->toArray();
        $banks = Bank::pluck('id')->toArray();

        if (empty($heads)) {
            $this->command->info('No Cashbook Heads found. Please run CashbookHeadSeeder first.');
            return;
        }

        $transactions = [];
        $startDate = Carbon::now()->startOfMonth();
        
        // Create 20 dummy transactions
        for ($i = 0; $i < 20; $i++) {
            $type = rand(0, 1) ? 'Debit' : 'Credit';
            $amount = rand(1000, 50000);
            $paymentMode = ['Cash', 'Online', 'Check'][rand(0, 2)];
            
            // Only assign bank if not Cash
            $bankId = ($paymentMode !== 'Cash' && !empty($banks)) ? $banks[array_rand($banks)] : null;
            
            $transactions[] = [
                'transaction_date' => $startDate->copy()->addDays(rand(0, 25))->format('Y-m-d'),
                'voucher_no' => 'V-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'description' => $type . ' transaction for ' . ($type == 'Debit' ? 'Income' : 'Expense'),
                'cashbook_head_id' => $heads[array_rand($heads)],
                'bank_id' => $bankId,
                'payment_mode' => $paymentMode,
                'type' => $type,
                'amount' => $amount,
                'reference_no' => 'REF-' . rand(10000, 99999),
                'remarks' => 'Auto-generated dummy transaction',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('cashbooks')->insert($transactions);
    }
}
