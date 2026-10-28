<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Voided sales need their own status (the original enum only had completed/draft/returned).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE sales MODIFY status ENUM('completed','draft','returned','void') NULL DEFAULT 'completed'");
    }

    public function down(): void
    {
        DB::statement("UPDATE sales SET status = 'completed' WHERE status = 'void'");
        DB::statement("ALTER TABLE sales MODIFY status ENUM('completed','draft','returned') NULL DEFAULT 'completed'");
    }
};
