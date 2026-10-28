<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\Store;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreDemoSeeder extends Seeder
{
    /**
     * Multi-store demo data: stores, role permissions, one sales employee per
     * store, products and two weeks of sales spread across the stores.
     */
    public function run(): void
    {
        if (!\App\Models\Setting::exists()) {
            \App\Models\Setting::create([
                'company_name' => 'Koder360 POS', 'currency' => 'Rs', 'timezone' => 'Asia/Karachi', 'country' => 'Pakistan',
                'phone' => '042-111-000-000', 'email' => 'info@koder360.test', 'address' => 'Mall Road, Lahore',
            ]);
        }

        $stores = collect([
            ['name' => 'Main Branch', 'code' => 'ST-001', 'phone' => '042-111-000-001', 'address' => 'Mall Road, Lahore'],
            ['name' => 'Gulberg Outlet', 'code' => 'ST-002', 'phone' => '042-111-000-002', 'address' => 'Main Boulevard, Gulberg III, Lahore'],
            ['name' => 'DHA Outlet', 'code' => 'ST-003', 'phone' => '042-111-000-003', 'address' => 'Phase 5, DHA, Lahore'],
        ])->map(fn ($s) => Store::updateOrCreate(['code' => $s['code']], $s + ['status' => 'Active']));

        $this->seedPermissions();

        // Admins stay unassigned (they see every store); everyone else gets a store
        User::where('email', 'accountant@example.com')->update(['store_id' => $stores[0]->id]);
        User::where('email', 'sales@example.com')->update(['store_id' => $stores[0]->id]);

        $salesRole = Role::where('name', 'Sales')->first();
        $sellers = [$stores[0]->id => User::where('email', 'sales@example.com')->first()];
        foreach ([[1, 'sales2@example.com', 'Gulberg Cashier'], [2, 'sales3@example.com', 'DHA Cashier']] as [$i, $email, $name]) {
            $sellers[$stores[$i]->id] = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make('password'),
                'role_id' => $salesRole?->id,
                'store_id' => $stores[$i]->id,
                'designation' => 'Cashier',
                'status' => 'Active',
                'joining_date' => now(),
                'phone' => '0300-000000' . ($i + 1),
                'address' => $stores[$i]->address,
            ]);
        }

        $products = $this->seedProducts();

        if (Sale::withoutGlobalScope('store')->exists()) {
            return; // don't pile up sales on re-run
        }

        $customers = Customer::pluck('id');
        mt_srand(42);

        DB::transaction(function () use ($stores, $products, $sellers, $customers) {
            // Opening stock in every store, through the stock ledger
            foreach ($stores as $store) {
                foreach ($products as $i => $p) {
                    StockService::move($store->id, $p->id, ($i + 1) % 7 === 0 ? 6 : 60, 'opening', $p, 'Demo opening stock');
                }
            }

            for ($day = 13; $day >= 0; $day--) {
                $date = Carbon::today()->subDays($day);

                foreach ($stores as $store) {
                    $cashier = $sellers[$store->id];
                    $shift = Shift::create([
                        'store_id' => $store->id, 'user_id' => $cashier->id, 'status' => 'open',
                        'opened_at' => $date->copy()->setTime(9, 0), 'opening_cash' => 5000,
                    ]);

                    $count = mt_rand(3, 6);
                    for ($n = 0; $n < $count; $n++) {
                        $at = $date->copy()->setTime(mt_rand(10, 20), mt_rand(0, 59));
                        $this->demoSale($store, $cashier, $shift, $products, $customers, $at);
                    }

                    if ($day > 0) {
                        $expected = $shift->expectedCash();
                        $counted = mt_rand(0, 5) === 0 ? $expected - 50 : $expected;
                        $shift->update([
                            'status' => 'closed', 'closed_at' => $date->copy()->setTime(21, 30),
                            'expected_cash' => $expected, 'closing_cash' => $counted, 'difference' => $counted - $expected,
                        ]);
                    }
                }
            }

            // A partial return per store, linked to its original invoice
            foreach ($stores as $store) {
                $original = Sale::withoutGlobalScope('store')->where('store_id', $store->id)->where('status', 'completed')
                    ->where('due_amount', 0)->where('discount', 0)->with('items')->latest('id')->skip(3)->first();
                $item = $original?->items->first();
                if (!$item) {
                    continue;
                }
                $shift = Shift::withoutGlobalScope('store')->where('store_id', $store->id)->where('status', 'open')->first();
                $refund = round($item->total / $item->quantity, 2);
                $return = Sale::create([
                    'store_id' => $store->id, 'user_id' => $sellers[$store->id]->id, 'invoice_no' => Store::nextInvoiceNo($store->id),
                    'return_of_sale_id' => $original->id, 'shift_id' => $shift?->id, 'customer_id' => $original->customer_id,
                    'date' => Carbon::today()->toDateString(), 'reference_no' => 'Damaged item',
                    'subtotal' => -$item->price, 'discount' => 0, 'tax' => 0, 'grand_total' => -$refund,
                    'paid_amount' => -$refund, 'due_amount' => 0, 'payment_status' => 'paid', 'status' => 'returned', 'payment_method' => 'cash',
                ]);
                $return->items()->create(['product_id' => $item->product_id, 'quantity' => -1, 'price' => $item->price, 'purchase_price' => $item->purchase_price, 'total' => -$refund]);
                $return->payments()->create(['store_id' => $store->id, 'shift_id' => $shift?->id, 'user_id' => $sellers[$store->id]->id, 'method' => 'cash', 'amount' => -$refund]);
                $item->increment('returned_qty', 1);
                StockService::move($store->id, $item->product_id, 1, 'return', $return, 'Return of ' . $original->invoice_no);
            }
        });

        // One held order per store so the drafts list has something in it
        foreach ($stores as $store) {
            $product = $products->random();
            $sale = Sale::create([
                'store_id' => $store->id,
                'user_id' => $sellers[$store->id]?->id,
                'customer_id' => $customers->isNotEmpty() ? $customers->random() : null,
                'date' => Carbon::today()->toDateString(),
                'subtotal' => $product->selling_price,
                'discount' => 0,
                'tax' => 0,
                'grand_total' => $product->selling_price,
                'status' => 'draft',
                'payment_method' => 'cash',
            ]);
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'total' => $product->selling_price,
            ]);
        }
    }

    /**
     * One completed sale with payments the way the POS would record it:
     * mostly cash, some card, some split, and a few on customer credit.
     */
    private function demoSale($store, $cashier, $shift, $products, $customers, Carbon $at): void
    {
        $inStock = $products->filter(fn ($p) => StockService::available($store->id, $p->id) >= 3);
        if ($inStock->isEmpty()) {
            return;
        }
        $lines = $inStock->random(min($inStock->count(), mt_rand(1, 4)));
        $subtotal = 0;
        $items = [];
        foreach ($lines as $product) {
            $qty = mt_rand(1, 3);
            $items[] = [$product, $qty];
            $subtotal += $qty * $product->selling_price;
        }
        $discount = mt_rand(0, 4) === 0 ? round($subtotal * 0.05) : 0;
        $grand = $subtotal - $discount;

        $kind = mt_rand(1, 20);   // 1-2 credit, 3-4 split, 5-9 card, rest cash
        $credit = $kind <= 2 && $customers->isNotEmpty();
        $paid = $credit ? round($grand * 0.4) : $grand;

        $sale = Sale::create([
            'store_id' => $store->id, 'user_id' => $cashier->id, 'invoice_no' => Store::nextInvoiceNo($store->id),
            'shift_id' => $shift->id, 'customer_id' => $customers->isNotEmpty() ? $customers->random() : null,
            'date' => $at->toDateString(), 'subtotal' => $subtotal, 'discount' => $discount,
            'discount_type' => $discount ? 'percent' : 'fixed', 'discount_value' => $discount ? 5 : 0, 'tax' => 0,
            'grand_total' => $grand, 'paid_amount' => $paid, 'due_amount' => $grand - $paid,
            'payment_status' => $credit ? 'partial' : 'paid', 'status' => 'completed',
            'payment_method' => $credit ? 'cash' : ($kind <= 4 ? 'split' : ($kind <= 9 ? 'card' : 'cash')),
        ]);
        $sale->created_at = $at;
        $sale->save();

        foreach ($items as [$product, $qty]) {
            $sale->items()->create([
                'product_id' => $product->id, 'quantity' => $qty, 'price' => $product->selling_price,
                'purchase_price' => $product->purchase_price, 'total' => $qty * $product->selling_price,
            ]);
            StockService::move($store->id, $product->id, -$qty, 'sale', $sale);
        }

        $pay = fn ($method, $amount) => $sale->payments()->create([
            'store_id' => $store->id, 'shift_id' => $shift->id, 'user_id' => $cashier->id, 'method' => $method, 'amount' => $amount,
        ]);
        if ($kind > 2 && $kind <= 4) {
            $card = round($grand / 2);
            $pay('card', $card);
            $pay('cash', $grand - $card);
        } else {
            $pay($sale->payment_method === 'card' ? 'card' : 'cash', $paid);
        }
    }

    private function seedPermissions(): void
    {
        $all = ['dashboard', 'sales', 'stores', 'all_stores', 'products', 'categories', 'sub_categories', 'units',
            'warehouses', 'discounts', 'customers', 'vendors', 'banks', 'cashbook', 'cashbook_heads', 'expenses',
            'expense_heads', 'payroll', 'loans', 'loan_types', 'reports_sales', 'reports_returns', 'reports_stock',
            'reports_top_products', 'reports_profit', 'reports_loss', 'reports_revenue', 'users', 'roles',
            'settings_system', 'taxes', 'shifts', 'customer_credit', 'purchases', 'vendor_payments',
            'stock_ledger', 'stock_transfers', 'stock_adjustments', 'audit_logs'];
        $full = ['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1, 'can_status' => 1];
        $view = ['can_view' => 1, 'can_add' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_status' => 0];

        $matrix = [
            'Admin' => array_fill_keys($all, $full),
            'Accountant' => array_merge(
                array_fill_keys(['cashbook', 'cashbook_heads', 'expenses', 'expense_heads', 'payroll', 'loans', 'loan_types',
                    'banks', 'reports_sales', 'reports_returns', 'reports_profit', 'reports_loss', 'reports_revenue',
                    'customer_credit', 'vendor_payments'], $full),
                array_fill_keys(['dashboard', 'purchases', 'shifts', 'stock_ledger', 'audit_logs'], $view)
            ),
            'Sales' => [
                'dashboard' => $view,
                // add = sell, resume held orders, returns; voiding (delete) stays with admins
                'sales' => ['can_add' => 1] + $view,
                'customers' => ['can_add' => 1] + $view,
                'customer_credit' => ['can_add' => 1] + $view,
                'shifts' => ['can_add' => 1] + $view,
                'products' => $view,
                'stock_ledger' => $view,
                'reports_sales' => $view,
                'reports_returns' => $view,
            ],
        ];

        foreach ($matrix as $roleName => $modules) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                continue;
            }
            foreach ($modules as $module => $perms) {
                RolePermission::updateOrCreate(['role_id' => $role->id, 'module' => $module], $perms);
            }
        }
    }

    private function seedProducts()
    {
        $unit = MeasurementUnit::where('name', 'like', 'Piece%')->first() ?? MeasurementUnit::first();
        $vendor = Vendor::first();

        $catalog = [
            'Electronics' => [['Wireless Earbuds', 2500, 3999], ['USB-C Charger 20W', 900, 1499], ['Power Bank 10000mAh', 2200, 3299], ['Bluetooth Speaker', 3100, 4799]],
            'Clothing' => [['Cotton T-Shirt', 650, 1199], ['Denim Jeans', 1800, 2999], ['Hoodie', 2100, 3499]],
            'Home & Garden' => [['Ceramic Mug', 250, 499], ['LED Desk Lamp', 1400, 2299], ['Cushion Cover (Set of 2)', 500, 899]],
            'Books' => [['Notebook A5', 120, 249], ['Gel Pen Pack', 180, 349]],
            'Beauty & Health' => [['Hand Sanitizer 250ml', 220, 399], ['Face Wash 100ml', 380, 649], ['Vitamin C Tablets', 750, 1199]],
        ];

        $i = 0;
        foreach ($catalog as $categoryName => $items) {
            $category = Category::firstOrCreate(['name' => $categoryName], ['status' => 'Active']);
            $sub = SubCategory::firstOrCreate(
                ['category_id' => $category->id, 'name' => $categoryName . ' - General'],
                ['code' => 'SUB-' . str_pad($category->id, 3, '0', STR_PAD_LEFT), 'status' => 'Active']
            );

            foreach ($items as [$name, $cost, $price]) {
                $i++;
                $code = 'PDC-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                Product::firstOrCreate(['code' => $code], [
                    'sku' => 'SKU-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'name' => $name,
                    'barcode' => '89' . str_pad($i, 11, '0', STR_PAD_LEFT),
                    'category_id' => $category->id,
                    'sub_category_id' => $sub->id,
                    'vendor_id' => $vendor?->id,
                    'unit_id' => $unit?->id,
                    'unit_value' => 1,
                    'purchase_price' => $cost,
                    'selling_price' => $price,
                    'stock_quantity' => 0, // stock is added per store through StockService
                    'min_stock_level' => 10,
                    'expiry_date' => $categoryName === 'Beauty & Health' ? now()->addDays(20 + $i)->toDateString() : null,
                    'status' => 'Active',
                ]);
            }
        }

        return Product::where('status', 'Active')->get();
    }
}
