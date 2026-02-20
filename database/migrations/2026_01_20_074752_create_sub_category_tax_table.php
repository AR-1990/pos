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
        Schema::create('sub_category_tax', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_category_id')->constrained('sub_categories')->onDelete('cascade');
            $table->foreignId('tax_id')->constrained('taxes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_category_tax');
    }
};
