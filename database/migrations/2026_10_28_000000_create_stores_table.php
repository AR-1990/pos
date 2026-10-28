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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        // Employees belong to one store; null means not assigned (admins usually)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('role_id')->constrained('stores')->nullOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')->constrained('stores')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('store_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::dropIfExists('stores');
    }
};
