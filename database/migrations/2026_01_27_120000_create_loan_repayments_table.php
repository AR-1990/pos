<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_advance_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('source')->default('Manual'); // Manual, Payroll
            $table->string('ref_id')->nullable(); // payroll_id
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('deduction_reason')->nullable()->after('other_deductions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('deduction_reason');
        });
    }
};
