<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Using raw SQL to modify enum as it's the most reliable way without doctrine/dbal
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('completed', 'draft', 'returned') DEFAULT 'completed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('completed', 'draft') DEFAULT 'completed'");
    }
};
