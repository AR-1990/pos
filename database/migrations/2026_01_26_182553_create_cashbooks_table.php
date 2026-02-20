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
        Schema::create('cashbooks', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('voucher_no')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('cashbook_head_id')->constrained('cashbook_heads')->onDelete('cascade');
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null');
            $table->string('payment_mode')->nullable(); // Cash, Cheque, etc.
            $table->enum('type', ['Debit', 'Credit']); // Debit = Receipt (In), Credit = Payment (Out)
            $table->decimal('amount', 15, 2);
            $table->string('reference_no')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbooks');
    }
};
