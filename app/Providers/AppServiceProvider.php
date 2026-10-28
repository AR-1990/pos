<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Custom Blade Directives for Permissions
        Blade::if('permission', function ($module, $action) {
            return auth()->check() && auth()->user()->hasPermission($module, $action);
        });

        // Settings are only needed when a view renders; load once per request then
        View::composer('*', function ($view) {
            static $settings = false;
            if ($settings === false) {
                try {
                    $settings = Setting::first();
                } catch (\Exception $e) {
                    $settings = null; // table not migrated yet
                }
            }
            $view->with('globalSettings', $settings);
        });

        try {
            // Share notifications with the header view (cached briefly: 4 queries otherwise run on every page)
            View::composer('layouts.header', function ($view) {
                // Stock alerts follow the store the user is looking at
                $storeId = \App\Models\Store::scopedId();
                $view->with('notifications', Cache::remember('header_notifications_' . ($storeId ?? 'all'), 60, function () use ($storeId) {
                $notifications = [];
                $qty = $storeId !== null
                    ? '(SELECT COALESCE(SUM(ss.quantity), 0) FROM store_stocks ss WHERE ss.product_id = products.id AND ss.store_id = ' . (int) $storeId . ')'
                    : 'products.stock_quantity';
                $withQty = fn () => Product::where('status', 'Active')->select('products.*')->selectRaw("$qty as stock_quantity");

                    // 1. Out of Stock (Critical)
                    $outOfStock = $withQty()->whereRaw("$qty <= 0")
                        ->latest()
                        ->limit(5)
                        ->get();
                        
                    foreach ($outOfStock as $product) {
                        $notifications[] = [
                            'type' => 'danger',
                            'icon' => 'fa-circle-xmark',
                            'title' => 'Out of Stock',
                            'message' => "{$product->name} is out of stock!",
                            'time' => 'Urgent',
                        ];
                    }

                    // 2. Low Stock (Warning) - 10 items or less
                    $lowStock = $withQty()->whereRaw("$qty > 0 AND $qty <= COALESCE(products.min_stock_level, 10)")
                        ->latest()
                        ->limit(5)
                        ->get();
                        
                    foreach ($lowStock as $product) {
                        $notifications[] = [
                            'type' => 'warning',
                            'icon' => 'fa-triangle-exclamation',
                            'title' => 'Low Stock Alert',
                            'message' => "{$product->name} has only " . (float) $product->stock_quantity . " left.",
                            'time' => 'Attention',
                        ];
                    }

                    // 3. Expired (Critical)
                    $expired = Product::whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '<', now())
                        ->latest()
                        ->limit(5)
                        ->get();
                        
                    foreach ($expired as $product) {
                        $notifications[] = [
                            'type' => 'danger',
                            'icon' => 'fa-calendar-xmark',
                            'title' => 'Product Expired',
                            'message' => "{$product->name} expired on {$product->expiry_date}.",
                            'time' => 'Urgent',
                        ];
                    }

                    // 4. Expiring Soon (Info) - Within 30 days
                    $expiringSoon = Product::whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '>=', now())
                        ->whereDate('expiry_date', '<=', now()->addDays(30))
                        ->orderBy('expiry_date', 'asc')
                        ->limit(5)
                        ->get();
                        
                    foreach ($expiringSoon as $product) {
                        $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($product->expiry_date), false);
                        // Ensure daysLeft is positive integer for display
                        $daysLeft = abs((int)$daysLeft);
                        
                        $notifications[] = [
                            'type' => 'info',
                            'icon' => 'fa-clock',
                            'title' => 'Expiring Soon',
                            'message' => "{$product->name} expires in {$daysLeft} days.",
                            'time' => 'Upcoming',
                        ];
                    }

                return $notifications;
                }));
            });

        } catch (\Exception $e) {
            // Log error or ignore
        }
    }
}
