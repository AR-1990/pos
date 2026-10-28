<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndUsersSeeder::class,
            LoanAdvancesSeeder::class,
            LoanTypesSeeder::class,
            PayrollSeeder::class,
            CategorySeeder::class,
            MeasurementUnitSeeder::class,
            VendorSeeder::class,
            WarehouseSeeder::class,
            CustomerSeeder::class,
            DiscountSeeder::class,
            ExpenseHeadSeeder::class,
            CashbookHeadSeeder::class,
            StoreDemoSeeder::class,
        ]);
    }
}
