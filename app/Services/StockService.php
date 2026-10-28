<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StoreStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place stock changes. Every change:
 *  - locks the store's stock row, so two tills can't oversell the same item
 *  - writes a stock_movements ledger row
 *  - keeps products.stock_quantity as the all-stores total
 * Call inside a DB transaction.
 */
class StockService
{
    /**
     * @param  float  $quantity  signed: positive adds stock, negative removes it
     * @param  bool  $allowNegative  only for corrections that must go through
     */
    public static function move(int $storeId, int $productId, float $quantity, string $type, $reference = null, ?string $note = null, bool $allowNegative = false): StockMovement
    {
        if ($quantity == 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity cannot be zero.']);
        }

        $stock = StoreStock::withoutGlobalScope('store')->where('store_id', $storeId)->where('product_id', $productId)->lockForUpdate()->first();
        if (!$stock) {
            StoreStock::insertOrIgnore(['store_id' => $storeId, 'product_id' => $productId, 'quantity' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $stock = StoreStock::withoutGlobalScope('store')->where('store_id', $storeId)->where('product_id', $productId)->lockForUpdate()->first();
        }

        $newBalance = round($stock->quantity + $quantity, 3);
        if ($newBalance < 0 && !$allowNegative) {
            $name = Product::whereKey($productId)->value('name') ?? "Product #$productId";
            throw ValidationException::withMessages([
                'stock' => "Not enough stock for {$name}. Available: " . (float) $stock->quantity . ', needed: ' . abs($quantity) . '.',
            ]);
        }

        $stock->update(['quantity' => $newBalance]);
        Product::whereKey($productId)->update(['stock_quantity' => DB::raw('stock_quantity + ' . (float) $quantity)]);

        return StockMovement::create([
            'store_id' => $storeId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'balance_after' => $newBalance,
            'type' => $type,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->getKey(),
            'user_id' => auth()->id(),
            'note' => $note,
        ]);
    }

    public static function available(int $storeId, int $productId): float
    {
        return (float) StoreStock::withoutGlobalScope('store')->where('store_id', $storeId)->where('product_id', $productId)->value('quantity');
    }
}
