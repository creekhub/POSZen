<?php

use App\Livewire\Dashboard;
use App\Livewire\Contacts;
use App\Livewire\Customers;
use App\Livewire\Login;
use App\Livewire\PosScreen;
use App\Livewire\Products;
use App\Livewire\Report;
use App\Livewire\ReportStateOfAccount;
use App\Livewire\ReportSalesTrend;
use App\Livewire\ReportProductSales;
use App\Http\Controllers\DashboardEventsController;
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
    Route::get('/reports/state-of-account', ReportStateOfAccount::class)->name('reports.state-of-account');
    Route::get('/reports/product-sales', ReportProductSales::class)->name('reports.product-sales');
    Route::get('/dashboard/events', DashboardEventsController::class)->name('dashboard.events');
    Route::get('/pos', PosScreen::class)->name('pos');
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
