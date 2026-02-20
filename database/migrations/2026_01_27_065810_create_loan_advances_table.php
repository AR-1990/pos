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
        Schema::create('loan_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // Advance Salary, Personal Loan, etc.
            $table->decimal('amount', 15, 2);
            $table->date('date'); // Disbursement Date
            $table->string('repayment_plan')->nullable(); // Monthly, Weekly, etc.
            $table->integer('installments')->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('status', ['Active', 'Paid', 'Rejected', 'Pending'])->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_advances');
    }
};
