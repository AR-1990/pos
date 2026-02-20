<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MeasurementUnitController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseHeadController;
use App\Http\Controllers\CashbookHeadController;
use App\Http\Controllers\CashbookController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoanAdvanceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanTypeController;
use App\Http\Controllers\ProfileController;

// Auth Routes
Auth::routes();

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:dashboard,view');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');

    // Sales Routes
    Route::middleware(['permission:sales,view'])->group(function () {
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/search-products', [SalesController::class, 'searchProducts'])->name('sales.search-products');
        Route::get('/sales/{id}/slip', [SalesController::class, 'show'])->name('sales.slip');
        Route::get('/sales/{id}/details', [SalesController::class, 'details'])->name('sales.details');
        Route::get('/sales/{id}/json', [SalesController::class, 'getSaleDetails'])->name('sales.json');
    });
    Route::post('/sales', [SalesController::class, 'store'])->name('sales.store')->middleware('permission:sales,add');
    Route::get('/sales/{id}/edit', [SalesController::class, 'edit'])->name('sales.edit')->middleware('permission:sales,edit');
    Route::put('/sales/{id}', [SalesController::class, 'update'])->name('sales.update')->middleware('permission:sales,edit');

    // Products Routes
    Route::middleware(['permission:products,view'])->group(function () {
        Route::get('/all-products', [ProductController::class, 'index'])->name('all-products');
        Route::get('/get-sub-categories/{categoryId}', [ProductController::class, 'getSubCategories'])->name('get-sub-categories');
        Route::get('/get-sub-category-taxes/{subCategoryId}', [ProductController::class, 'getSubCategoryTaxes'])->name('get-sub-category-taxes');
    });
    Route::get('/add-products', [ProductController::class, 'create'])->name('add-products')->middleware('permission:products,add');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store')->middleware('permission:products,add');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit')->middleware('permission:products,edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update')->middleware('permission:products,edit');
    Route::post('/products/{id}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status')->middleware('permission:products,status');

    // Category Routes (Products Context)
    Route::middleware(['permission:categories,view'])->group(function () {
        Route::get('/category', [CategoryController::class, 'indexProduct'])->name('category.index');
    });
    Route::get('/category/create', [CategoryController::class, 'createProduct'])->name('category.create')->middleware('permission:categories,add');
    Route::post('/category', [CategoryController::class, 'storeProduct'])->name('category.store')->middleware('permission:categories,add');
    Route::get('/category/{id}/edit', [CategoryController::class, 'editProduct'])->name('category.edit')->middleware('permission:categories,edit');
    Route::put('/category/{id}', [CategoryController::class, 'updateProduct'])->name('category.update')->middleware('permission:categories,edit');
    Route::post('/category/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('category.toggle-status')->middleware('permission:categories,status');

    // Sub Category Routes
    Route::middleware(['permission:sub_categories,view'])->group(function () {
        Route::get('/sub-category', [SubCategoryController::class, 'index'])->name('sub-category.index');
    });
    Route::get('/sub-category/create', [SubCategoryController::class, 'create'])->name('sub-category.create')->middleware('permission:sub_categories,add');
    Route::post('/sub-category', [SubCategoryController::class, 'store'])->name('sub-category.store')->middleware('permission:sub_categories,add');
    Route::get('/sub-category/{id}/edit', [SubCategoryController::class, 'edit'])->name('sub-category.edit')->middleware('permission:sub_categories,edit');
    Route::put('/sub-category/{id}', [SubCategoryController::class, 'update'])->name('sub-category.update')->middleware('permission:sub_categories,edit');
    Route::post('/sub-category/{id}/toggle-status', [SubCategoryController::class, 'toggleStatus'])->name('sub-category.toggle-status')->middleware('permission:sub_categories,status');

    Route::get('/accounts', function () {
        return view('accounts', ['pageTitle' => 'Accounts']);
    });

    Route::middleware(['permission:cashbook,view'])->group(function () {
        Route::get('/cashbook', [CashbookController::class, 'index'])->name('cashbook.index');
        Route::get('/cashbook/export', [CashbookController::class, 'export'])->name('cashbook.export');
    });
    Route::get('/new-cashbook-transaction', [CashbookController::class, 'create'])->name('cashbook.create')->middleware('permission:cashbook,add');
    Route::post('/cashbook', [CashbookController::class, 'store'])->name('cashbook.store')->middleware('permission:cashbook,add');

    Route::middleware(['permission:expenses,view'])->group(function () {
        Route::get('/expense', [ExpenseController::class, 'index'])->name('expense.index');
        Route::get('/expense/export', [ExpenseController::class, 'export'])->name('expense.export');
    });
    Route::get('/record-new-expense', [ExpenseController::class, 'create'])->name('expense.create')->middleware('permission:expenses,add');
    Route::post('/record-new-expense', [ExpenseController::class, 'store'])->name('expense.store')->middleware('permission:expenses,add');

    Route::middleware(['permission:payroll,view'])->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/export', [PayrollController::class, 'export'])->name('payroll.export');
        Route::get('/get-employee-loan/{userId}', [PayrollController::class, 'getEmployeeLoanDetails'])->name('payroll.get-loan');
        Route::get('/payroll/{id}/slip', [PayrollController::class, 'slip'])->name('payroll.slip');
    });
    Route::get('/payroll-process', [PayrollController::class, 'create'])->name('payroll.create')->middleware('permission:payroll,add');
    Route::post('/payroll-process', [PayrollController::class, 'store'])->name('payroll.store')->middleware('permission:payroll,add');
    Route::post('/payroll/{id}/pay', [PayrollController::class, 'markAsPaid'])->name('payroll.pay')->middleware('permission:payroll,edit');

    Route::middleware(['permission:loans,view'])->group(function () {
        Route::get('/loan-and-advances', [LoanAdvanceController::class, 'index'])->name('loan-and-advances.index');
        Route::get('/loan-and-advances/export', [LoanAdvanceController::class, 'export'])->name('loan-and-advances.export');
        Route::get('/loan-and-advances/{id}/details', [LoanAdvanceController::class, 'show'])->name('loan-and-advances.show');
    });
    Route::get('/grant-new-loan-and-advance', [LoanAdvanceController::class, 'create'])->name('loan-and-advances.create')->middleware('permission:loans,add');
    Route::post('/loan-and-advances', [LoanAdvanceController::class, 'store'])->name('loan-and-advances.store')->middleware('permission:loans,add');
    Route::post('/loan-and-advances/repay', [LoanAdvanceController::class, 'repay'])->name('loan-and-advances.repay')->middleware('permission:loans,edit');
    Route::get('/loan-and-advances/{id}/edit', [LoanAdvanceController::class, 'edit'])->name('loan-and-advances.edit')->middleware('permission:loans,edit');
    Route::put('/loan-and-advances/{id}', [LoanAdvanceController::class, 'update'])->name('loan-and-advances.update')->middleware('permission:loans,edit');

    Route::get('/profit-report', [ReportController::class, 'profitReport'])->name('reports.profit')->middleware('permission:reports_profit,view');
    Route::get('/profit-report/export', [ReportController::class, 'exportProfit'])->name('reports.profit.export')->middleware('permission:reports_profit,view');

    Route::get('/loss-report', [ReportController::class, 'profitReport'])->name('reports.loss')->middleware('permission:reports_loss,view'); // Alias for Profit Report

    Route::get('/revenue-report', [ReportController::class, 'revenueReport'])->name('reports.revenue')->middleware('permission:reports_revenue,view');
    Route::get('/revenue-report/export', [ReportController::class, 'exportRevenue'])->name('reports.revenue.export')->middleware('permission:reports_revenue,view');

    // Report Routes
    Route::get('/reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales')->middleware('permission:reports_sales,view');
    Route::get('/reports/returns', [ReportController::class, 'returnReport'])->name('reports.returns')->middleware('permission:reports_returns,view');
    Route::get('/reports/sales/export', [ReportController::class, 'exportSales'])->name('reports.sales.export')->middleware('permission:reports_sales,view');
    Route::get('/reports/returns/export', [ReportController::class, 'exportReturns'])->name('reports.returns.export')->middleware('permission:reports_returns,view');

    Route::get('/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock')->middleware('permission:reports_stock,view');
    Route::get('/reports/stock/export', [ReportController::class, 'exportStock'])->name('reports.stock.export')->middleware('permission:reports_stock,view');

    Route::get('/reports/top-products', [ReportController::class, 'topProductsReport'])->name('reports.top-products')->middleware('permission:reports_top_products,view');
    Route::get('/reports/top-products/export', [ReportController::class, 'exportTopProducts'])->name('reports.top-products.export')->middleware('permission:reports_top_products,view');

    //Admin Routes 
    Route::get('/system-settings', [SystemSettingsController::class, 'index'])->name('system-settings.index')->middleware('permission:settings_system,view');
    Route::post('/system-settings', [SystemSettingsController::class, 'update'])->name('system-settings.update')->middleware('permission:settings_system,edit');

    Route::get('/general-settings', function () {
        return view('general-settings', ['pageTitle' => 'Settings']);
    });

    Route::middleware(['permission:taxes,view'])->group(function () {
        Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
    });
    Route::get('/add-taxes', [TaxController::class, 'create'])->name('taxes.create')->middleware('permission:taxes,add');
    Route::post('/taxes', [TaxController::class, 'store'])->name('taxes.store')->middleware('permission:taxes,add');
    Route::get('/taxes/{id}/edit', [TaxController::class, 'edit'])->name('taxes.edit')->middleware('permission:taxes,edit');
    Route::put('/taxes/{id}', [TaxController::class, 'update'])->name('taxes.update')->middleware('permission:taxes,edit');
    Route::post('/taxes/{id}/toggle-status', [TaxController::class, 'toggleStatus'])->name('taxes.toggle-status')->middleware('permission:taxes,status');

    // Product Category Routes (Settings)
    Route::middleware(['permission:categories,view'])->group(function () {
        Route::get('/product-category', [CategoryController::class, 'index'])->name('product-category.index');
    });
    Route::get('/product-category/create', [CategoryController::class, 'create'])->name('product-category.create')->middleware('permission:categories,add');
    Route::post('/product-category', [CategoryController::class, 'store'])->name('product-category.store')->middleware('permission:categories,add');
    Route::get('/product-category/{id}/edit', [CategoryController::class, 'edit'])->name('product-category.edit')->middleware('permission:categories,edit');
    Route::put('/product-category/{id}', [CategoryController::class, 'update'])->name('product-category.update')->middleware('permission:categories,edit');
    Route::post('/product-category/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('product-category.toggle-status')->middleware('permission:categories,status');

    // Measurement Unit Routes
    Route::middleware(['permission:units,view'])->group(function () {
        Route::get('/measurement-unit', [MeasurementUnitController::class, 'index'])->name('measurement-unit.index');
    });
    Route::get('/add-measurement', [MeasurementUnitController::class, 'create'])->name('measurement-unit.create')->middleware('permission:units,add');
    Route::post('/measurement-unit', [MeasurementUnitController::class, 'store'])->name('measurement-unit.store')->middleware('permission:units,add');
    Route::get('/measurement-unit/{id}/edit', [MeasurementUnitController::class, 'edit'])->name('measurement-unit.edit')->middleware('permission:units,edit');
    Route::put('/measurement-unit/{id}', [MeasurementUnitController::class, 'update'])->name('measurement-unit.update')->middleware('permission:units,edit');
    Route::post('/measurement-unit/{id}/toggle-status', [MeasurementUnitController::class, 'toggleStatus'])->name('measurement-unit.toggle-status')->middleware('permission:units,status');

    // Warehouse Routes
    Route::middleware(['permission:warehouses,view'])->group(function () {
        Route::get('/ware-house', [WarehouseController::class, 'index'])->name('ware-house.index');
    });
    Route::get('/add-ware-house', [WarehouseController::class, 'create'])->name('ware-house.create')->middleware('permission:warehouses,add');
    Route::post('/ware-house', [WarehouseController::class, 'store'])->name('ware-house.store')->middleware('permission:warehouses,add');
    Route::get('/ware-house/{id}/edit', [WarehouseController::class, 'edit'])->name('ware-house.edit')->middleware('permission:warehouses,edit');
    Route::put('/ware-house/{id}', [WarehouseController::class, 'update'])->name('ware-house.update')->middleware('permission:warehouses,edit');
    Route::post('/ware-house/{id}/toggle-status', [WarehouseController::class, 'toggleStatus'])->name('ware-house.toggle-status')->middleware('permission:warehouses,status');

    // Discount Routes
    Route::middleware(['permission:discounts,view'])->group(function () {
        Route::get('/discount', [DiscountController::class, 'index'])->name('discount.index');
    });
    Route::get('/add-discount', [DiscountController::class, 'create'])->name('discount.create')->middleware('permission:discounts,add');
    Route::post('/discount', [DiscountController::class, 'store'])->name('discount.store')->middleware('permission:discounts,add');
    Route::get('/discount/{id}/edit', [DiscountController::class, 'edit'])->name('discount.edit')->middleware('permission:discounts,edit');
    Route::put('/discount/{id}', [DiscountController::class, 'update'])->name('discount.update')->middleware('permission:discounts,edit');
    Route::post('/discount/{id}/toggle-status', [DiscountController::class, 'toggleStatus'])->name('discount.toggle-status')->middleware('permission:discounts,status');

    // Vendor Routes
    Route::middleware(['permission:vendors,view'])->group(function () {
        Route::get('/vendor', [VendorController::class, 'index'])->name('vendor.index');
        Route::get('/vendor/{id}', [VendorController::class, 'show'])->name('vendor.show');
    });
    Route::get('/add-vendor', [VendorController::class, 'create'])->name('vendor.create')->middleware('permission:vendors,add');
    Route::post('/vendor', [VendorController::class, 'store'])->name('vendor.store')->middleware('permission:vendors,add');
    Route::get('/vendor/{id}/edit', [VendorController::class, 'edit'])->name('vendor.edit')->middleware('permission:vendors,edit');
    Route::put('/vendor/{id}', [VendorController::class, 'update'])->name('vendor.update')->middleware('permission:vendors,edit');
    Route::post('/vendor/{id}/toggle-status', [VendorController::class, 'toggleStatus'])->name('vendor.toggle-status')->middleware('permission:vendors,status');

    // Customer Routes
    Route::middleware(['permission:customers,view'])->group(function () {
        Route::get('/customer', [CustomerController::class, 'index'])->name('customer.index');
        Route::get('/customer/{id}', [CustomerController::class, 'show'])->name('customer.show');
    });
    Route::get('/add-customer', [CustomerController::class, 'create'])->name('customer.create')->middleware('permission:customers,add');
    Route::post('/customer', [CustomerController::class, 'store'])->name('customer.store')->middleware('permission:customers,add');
    Route::get('/customer/{id}/edit', [CustomerController::class, 'edit'])->name('customer.edit')->middleware('permission:customers,edit');
    Route::put('/customer/{id}', [CustomerController::class, 'update'])->name('customer.update')->middleware('permission:customers,edit');
    Route::post('/customer/{id}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customer.toggle-status')->middleware('permission:customers,status');

    // Bank Routes
    Route::middleware(['permission:banks,view'])->group(function () {
        Route::get('/bank', [BankController::class, 'index'])->name('bank.index');
    });
    Route::get('/bank/create', [BankController::class, 'create'])->name('bank.create')->middleware('permission:banks,add');
    Route::post('/bank', [BankController::class, 'store'])->name('bank.store')->middleware('permission:banks,add');
    Route::get('/bank/{id}/edit', [BankController::class, 'edit'])->name('bank.edit')->middleware('permission:banks,edit');
    Route::put('/bank/{id}', [BankController::class, 'update'])->name('bank.update')->middleware('permission:banks,edit');
    Route::post('/bank/{id}/toggle-status', [BankController::class, 'toggleStatus'])->name('bank.toggle-status')->middleware('permission:banks,status');

    // Expense Head Routes
    Route::middleware(['permission:expense_heads,view'])->group(function () {
        Route::get('/expense-head', [ExpenseHeadController::class, 'index'])->name('expense-head.index');
    });
    Route::get('/expense-head/create', [ExpenseHeadController::class, 'create'])->name('expense-head.create')->middleware('permission:expense_heads,add');
    Route::post('/expense-head', [ExpenseHeadController::class, 'store'])->name('expense-head.store')->middleware('permission:expense_heads,add');
    Route::get('/expense-head/{id}/edit', [ExpenseHeadController::class, 'edit'])->name('expense-head.edit')->middleware('permission:expense_heads,edit');
    Route::put('/expense-head/{id}', [ExpenseHeadController::class, 'update'])->name('expense-head.update')->middleware('permission:expense_heads,edit');
    Route::post('/expense-head/{id}/toggle-status', [ExpenseHeadController::class, 'toggleStatus'])->name('expense-head.toggle-status')->middleware('permission:expense_heads,status');

    // Cashbook Head Routes
    Route::middleware(['permission:cashbook_heads,view'])->group(function () {
        Route::get('/cashbook-head', [CashbookHeadController::class, 'index'])->name('cashbook-head.index');
    });
    Route::get('/cashbook-head/create', [CashbookHeadController::class, 'create'])->name('cashbook-head.create')->middleware('permission:cashbook_heads,add');
    Route::post('/cashbook-head', [CashbookHeadController::class, 'store'])->name('cashbook-head.store')->middleware('permission:cashbook_heads,add');
    Route::get('/cashbook-head/{id}/edit', [CashbookHeadController::class, 'edit'])->name('cashbook-head.edit')->middleware('permission:cashbook_heads,edit');
    Route::put('/cashbook-head/{id}', [CashbookHeadController::class, 'update'])->name('cashbook-head.update')->middleware('permission:cashbook_heads,edit');
    Route::post('/cashbook-head/{id}/toggle-status', [CashbookHeadController::class, 'toggleStatus'])->name('cashbook-head.toggle-status')->middleware('permission:cashbook_heads,status');

    // Role Routes
    Route::middleware(['permission:roles,view'])->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    });
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create')->middleware('permission:roles,add');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store')->middleware('permission:roles,add');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit')->middleware('permission:roles,edit');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update')->middleware('permission:roles,edit');
    Route::post('/roles/{id}/toggle-status', [RoleController::class, 'toggleStatus'])->name('roles.toggle-status')->middleware('permission:roles,status');

    // Loan Type Routes
    Route::middleware(['permission:loan_types,view'])->group(function () {
        Route::get('/loan-types', [LoanTypeController::class, 'index'])->name('loan-types.index');
    });
    Route::get('/loan-types/create', [LoanTypeController::class, 'create'])->name('loan-types.create')->middleware('permission:loan_types,add');
    Route::post('/loan-types', [LoanTypeController::class, 'store'])->name('loan-types.store')->middleware('permission:loan_types,add');
    Route::get('/loan-types/{id}/edit', [LoanTypeController::class, 'edit'])->name('loan-types.edit')->middleware('permission:loan_types,edit');
    Route::put('/loan-types/{id}', [LoanTypeController::class, 'update'])->name('loan-types.update')->middleware('permission:loan_types,edit');
    Route::post('/loan-types/{id}/toggle-status', [LoanTypeController::class, 'toggleStatus'])->name('loan-types.toggle-status')->middleware('permission:loan_types,status');

    // User Routes
    Route::middleware(['permission:users,view'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{id}/show', [UserController::class, 'show'])->name('users.show');
    });
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create')->middleware('permission:users,add');
    Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('permission:users,add');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('permission:users,edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update')->middleware('permission:users,edit');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status')->middleware('permission:users,status');

});
