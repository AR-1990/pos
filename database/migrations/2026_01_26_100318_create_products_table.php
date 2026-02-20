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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('sub_category_id')->constrained('sub_categories')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('measurement_units')->onDelete('cascade');
            $table->string('secondary_unit')->nullable();
            $table->string('pack_size')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('selling_price', 10, 2);
            $table->integer('min_stock_level')->nullable();
            $table->integer('max_stock_level')->nullable();
            $table->boolean('batch_expiry_enabled')->default(false);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
