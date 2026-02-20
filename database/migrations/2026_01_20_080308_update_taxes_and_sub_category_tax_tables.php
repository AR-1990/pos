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
        Schema::table('taxes', function (Blueprint $table) {
            $table->decimal('tax_rate', 8, 2)->nullable()->change();
        });

        Schema::table('sub_category_tax', function (Blueprint $table) {
            $table->decimal('tax_rate', 8, 2)->nullable()->after('tax_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_category_tax', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->decimal('tax_rate', 8, 2)->nullable(false)->change();
        });
    }
};
