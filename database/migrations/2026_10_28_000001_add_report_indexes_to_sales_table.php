<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dashboard, reports and the store scope all filter sales by store, status and date.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['store_id', 'status', 'date'], 'sales_store_status_date_index');
            $table->index(['status', 'date'], 'sales_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_status_date_index');
            $table->dropIndex('sales_store_status_date_index');
        });
    }
};
