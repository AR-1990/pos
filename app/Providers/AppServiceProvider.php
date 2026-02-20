<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
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

        try {
            if (Schema::hasTable('settings')) {
                $settings = Setting::first();
                View::share('globalSettings', $settings);
            }

            // Share notifications with the header view
            View::composer('layouts.header', function ($view) {
                $notifications = [];

                if (Schema::hasTable('products')) {
                    // 1. Out of Stock (Critical)
                    $outOfStock = Product::where('stock_quantity', '<=', 0)
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
                    $lowStock = Product::where('stock_quantity', '<=', 10)
                        ->where('stock_quantity', '>', 0)
                        ->latest()
                        ->limit(5)
                        ->get();
                        
                    foreach ($lowStock as $product) {
                        $notifications[] = [
                            'type' => 'warning',
                            'icon' => 'fa-triangle-exclamation',
                            'title' => 'Low Stock Alert',
                            'message' => "{$product->name} has only {$product->stock_quantity} left.",
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
                }

                $view->with('notifications', $notifications);
            });

        } catch (\Exception $e) {
            // Log error or ignore
        }
    }
}
