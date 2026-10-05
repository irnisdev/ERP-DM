<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(\Illuminate\Support\Facades\Auth::check() ? 'dashboard' : 'login');
});

Route::middleware(['auth'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // ---- Employee (PRD 02 §7)
    Route::resource('employees', App\Http\Controllers\EmployeeController::class);

    Route::prefix('employees/{employee}')->name('employees.')->group(function () {
        Route::get('family', [App\Http\Controllers\EmployeeFamilyController::class, 'index'])
            ->name('family.index');
        Route::post('family', [App\Http\Controllers\EmployeeFamilyController::class, 'store'])
            ->name('family.store');
        Route::delete('family/{member}', [App\Http\Controllers\EmployeeFamilyController::class, 'destroy'])
            ->name('family.destroy');

        Route::get('insurances', [App\Http\Controllers\EmployeeInsuranceController::class, 'index'])
            ->name('insurances.index');
        Route::post('insurances', [App\Http\Controllers\EmployeeInsuranceController::class, 'store'])
            ->name('insurances.store');
        Route::delete('insurances/{insurance}', [App\Http\Controllers\EmployeeInsuranceController::class, 'destroy'])
            ->name('insurances.destroy');
    });

    // ---- Customer (PRD 02 §8)
    Route::resource('customers', App\Http\Controllers\CustomerController::class);
    Route::get('customers/lookup/duplicates', [App\Http\Controllers\CustomerController::class, 'checkDuplicates'])
        ->name('customers.check-duplicates');

    // ---- Product (PRD 02 §9)
    Route::resource('products', App\Http\Controllers\ProductController::class);

    Route::prefix('products/{product}')->name('products.')->group(function () {
        Route::get('prices', [App\Http\Controllers\ProductPriceController::class, 'index'])
            ->name('prices.index');
        Route::post('prices', [App\Http\Controllers\ProductPriceController::class, 'store'])
            ->name('prices.store');
        Route::delete('prices/{price}', [App\Http\Controllers\ProductPriceController::class, 'destroy'])
            ->name('prices.destroy');
    });

    Route::resource('product-categories', App\Http\Controllers\ProductCategoryController::class)
        ->parameters(['product-categories' => 'productCategory'])
        ->except('show');
    Route::resource('brands', App\Http\Controllers\BrandController::class)->except('show');
    Route::resource('units', App\Http\Controllers\UnitController::class)->except('show');

    // ---- Service (PRD 02 §10)
    Route::resource('services', App\Http\Controllers\ServiceController::class);

    Route::prefix('services/{service}')->name('services.')->group(function () {
        Route::get('prices', [App\Http\Controllers\ServicePriceController::class, 'index'])
            ->name('prices.index');
        Route::post('prices', [App\Http\Controllers\ServicePriceController::class, 'store'])
            ->name('prices.store');
        Route::delete('prices/{price}', [App\Http\Controllers\ServicePriceController::class, 'destroy'])
            ->name('prices.destroy');
    });

    Route::resource('service-categories', App\Http\Controllers\ServiceCategoryController::class)
        ->parameters(['service-categories' => 'serviceCategory'])
        ->except('show');

    // ---- Package
    Route::resource('packages', App\Http\Controllers\ServicePackageController::class)
        ->parameters(['packages' => 'servicePackage']);

    // ---- Supplier (PRD 02 §11)
    Route::resource('suppliers', App\Http\Controllers\SupplierController::class);

    Route::prefix('suppliers/{supplier}')->name('suppliers.')->group(function () {
        Route::get('bank-accounts', [App\Http\Controllers\SupplierBankAccountController::class, 'index'])
            ->name('bank-accounts.index');
        Route::post('bank-accounts', [App\Http\Controllers\SupplierBankAccountController::class, 'store'])
            ->name('bank-accounts.store');
        Route::delete('bank-accounts/{account}', [App\Http\Controllers\SupplierBankAccountController::class, 'destroy'])
            ->name('bank-accounts.destroy');
    });

    // ---- Booking & Treatment (PRD 02 §11)
    Route::resource('bookings', App\Http\Controllers\BookingController::class);
    Route::post('bookings/{booking}/transition', [App\Http\Controllers\BookingController::class, 'transition'])
        ->name('bookings.transition');
    Route::post('bookings/{booking}/sales', [App\Http\Controllers\SalesTransactionController::class, 'fromBooking'])
        ->name('bookings.sales.store');

    // ---- POS & Sales (PRD 02 §12, §14)
    Route::get('pos', [App\Http\Controllers\PosController::class, 'create'])->name('pos.create');
    Route::post('pos', [App\Http\Controllers\PosController::class, 'store'])->name('pos.store');
    Route::get('pos/catalog', [App\Http\Controllers\PosController::class, 'catalog'])->name('pos.catalog');

    Route::get('sales', [App\Http\Controllers\SalesTransactionController::class, 'index'])
        ->name('sales.index');
    Route::get('sales/{transaction}', [App\Http\Controllers\SalesTransactionController::class, 'show'])
        ->name('sales.show');
    Route::post('sales/{transaction}/pay', [App\Http\Controllers\SalesTransactionController::class, 'pay'])
        ->name('sales.pay');
    Route::post('sales/{transaction}/cancel', [App\Http\Controllers\SalesTransactionController::class, 'cancel'])
        ->name('sales.cancel');

    Route::get('commissions', [App\Http\Controllers\CommissionController::class, 'index'])
        ->name('commissions.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'store']);
});

Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');