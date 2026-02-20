<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('salary_month');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0);
            $table->decimal('income_tax', 15, 2)->default(0);
            $table->decimal('fund_deduction', 15, 2)->default(0);
            $table->decimal('loan_deduction', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('payment_mode')->nullable(); // Cash, Bank Transfer, etc.
            $table->enum('status', ['Paid', 'Pending'])->default('Pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
