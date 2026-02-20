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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reference_no')->nullable();
            $table->foreignId('expense_head_id')->constrained('expense_heads')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('payment_mode')->default('Cash'); // Cash, Bank Transfer, Cheque, Online Transfer
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null');
            $table->string('paid_to')->nullable(); // Vendor/Employee name
            $table->text('remarks')->nullable();
            $table->string('status')->default('Approved'); // Pending, Approved, Rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
