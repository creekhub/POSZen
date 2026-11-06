<?php

use App\Livewire\Dashboard;
use App\Livewire\Contacts;
use App\Livewire\Customers;
use App\Livewire\Login;
use App\Livewire\LoyaltyCardModule;
use App\Livewire\PosScreen;
use App\Livewire\Products;
use App\Livewire\Report;
use App\Livewire\ReportStateOfAccount;
use App\Livewire\ReportSalesTrend;
use App\Livewire\ReportProductSales;
use App\Livewire\ReportFavoriteProducts;
use App\Http\Controllers\DashboardEventsController;
use App\Http\Controllers\ReportProductSalesProductOptionsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/customers', Customers::class)->name('customers');
    Route::get('/products', Products::class)->name('products');
    Route::get('/contacts', Contacts::class)->name('contacts');
    Route::get('/reports', Report::class)->name('reports');
    Route::get('/reports/sales-trend', ReportSalesTrend::class)->name('reports.sales-trend');
    Route::get('/reports/favorite-products', ReportFavoriteProducts::class)->name('reports.favorite-products');
    Route::get('/reports/favorite-products/product-options', ReportProductSalesProductOptionsController::class)->name('reports.favorite-products.product-options');
    Route::get('/reports/state-of-account', ReportStateOfAccount::class)->name('reports.state-of-account');
    Route::get('/reports/product-sales', ReportProductSales::class)->name('reports.product-sales');
    Route::get('/reports/product-sales/product-options', ReportProductSalesProductOptionsController::class)->name('reports.product-sales.product-options');
    Route::get('/loyalty-cards', LoyaltyCardModule::class)->name('loyalty.overview');
    Route::get('/loyalty-cards/cards', LoyaltyCardModule::class)->name('loyalty.cards');
    Route::get('/loyalty-cards/rules', LoyaltyCardModule::class)->name('loyalty.rules');
    Route::get('/loyalty-cards/transactions', LoyaltyCardModule::class)->name('loyalty.transactions');
    Route::get('/dashboard/events', DashboardEventsController::class)->name('dashboard.events');
    Route::get('/pos', PosScreen::class)->name('pos');
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
