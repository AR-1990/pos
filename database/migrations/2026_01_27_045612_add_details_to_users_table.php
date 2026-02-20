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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->string('identity_number')->nullable();
            $table->string('identity_card_front')->nullable();
            $table->string('identity_card_back')->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->string('designation')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('status')->default('Active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn([
                'role_id',
                'identity_number',
                'identity_card_front',
                'identity_card_back',
                'joining_date',
                'salary',
                'designation',
                'phone',
                'address',
                'cv_path',
                'status'
            ]);
        });
    }
};
