<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Stock per store + full movement ledger ----
        Schema::create('store_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);           // signed: + in, - out
            $table->decimal('balance_after', 14, 3);
            $table->string('type', 30);                   // opening, sale, return, void, purchase, transfer_in, transfer_out, adjustment
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // ---- Audit trail ----
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description');
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'created_at']);
        });

        // ---- Cash register shifts ----
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('opened_at');
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->decimal('expected_cash', 14, 2)->nullable();
            $table->decimal('closing_cash', 14, 2)->nullable();
            $table->decimal('difference', 14, 2)->nullable();
            $table->string('status', 10)->default('open');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // ---- Sales: invoice numbers, returns, void, credit ----
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedInteger('invoice_counter')->default(0)->after('address');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('invoice_no', 40)->nullable()->unique()->after('user_id');
            $table->foreignId('return_of_sale_id')->nullable()->after('invoice_no')->constrained('sales')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->after('return_of_sale_id')->constrained()->nullOnDelete();
            $table->string('discount_type', 10)->default('fixed')->after('discount');
            $table->decimal('discount_value', 14, 2)->default(0)->after('discount_type');
            $table->decimal('paid_amount', 14, 2)->default(0)->after('grand_total');
            $table->decimal('due_amount', 14, 2)->default(0)->after('paid_amount');
            $table->string('payment_status', 10)->default('paid')->after('due_amount');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('discount', 14, 2)->default(0)->after('price');
            $table->decimal('tax', 14, 2)->default(0)->after('discount');
            $table->decimal('returned_qty', 14, 3)->default(0)->after('quantity');
        });

        // Money received/refunded against sales (split payments, credit settlements)
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 14, 2);
            $table->string('method', 20);
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 20);                 // cash, card, bank_transfer
            $table->decimal('amount', 14, 2);             // negative for refunds
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->index(['shift_id', 'method']);
        });

        // ---- Purchases & vendor payables ----
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_no', 40)->unique();
            $table->foreignId('store_id')->constrained();
            $table->foreignId('vendor_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_invoice_no')->nullable();
            $table->date('date');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('due_amount', 14, 2)->default(0);
            $table->string('payment_status', 10)->default('unpaid');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });

        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 14, 2);
            $table->string('method', 20);
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // ---- Transfers & adjustments ----
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 40)->unique();
            $table->foreignId('from_store_id')->constrained('stores');
            $table->foreignId('to_store_id')->constrained('stores');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 14, 3);
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no', 40)->unique();
            $table->foreignId('store_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('reason', 20);                 // damage, expired, count, lost, other
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 14, 3);           // signed
            $table->timestamps();
        });

        // ---- Store-wise expenses / cashbook ----
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        Schema::table('cashbooks', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Existing single stock number becomes the first store's opening stock
        $firstStore = DB::table('stores')->orderBy('id')->value('id');
        if ($firstStore) {
            foreach (DB::table('products')->get(['id', 'stock_quantity']) as $p) {
                DB::table('store_stocks')->insert([
                    'store_id' => $firstStore, 'product_id' => $p->id,
                    'quantity' => max(0, (float) $p->stock_quantity),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('cashbooks', fn (Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        Schema::table('expenses', fn (Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        foreach (['stock_adjustment_items', 'stock_adjustments', 'stock_transfer_items', 'stock_transfers',
                  'vendor_payments', 'purchase_items', 'purchases', 'sale_payments', 'customer_payments'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('sale_items', fn (Blueprint $t) => $t->dropColumn(['discount', 'tax', 'returned_qty']));
        Schema::table('sales', function (Blueprint $t) {
            $t->dropConstrainedForeignId('voided_by');
            $t->dropConstrainedForeignId('shift_id');
            $t->dropConstrainedForeignId('return_of_sale_id');
            $t->dropUnique(['invoice_no']);
            $t->dropColumn(['invoice_no', 'discount_type', 'discount_value', 'paid_amount', 'due_amount', 'payment_status', 'voided_at', 'void_reason']);
        });
        Schema::table('stores', fn (Blueprint $t) => $t->dropColumn('invoice_counter'));
        foreach (['shifts', 'audit_logs', 'stock_movements', 'store_stocks'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
