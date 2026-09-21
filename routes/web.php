<?php

use App\Livewire\Dashboard;
use App\Livewire\Login;
use App\Livewire\PosScreen;
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
    Route::get('/dashboard/events', DashboardEventsController::class)->name('dashboard.events');
    Route::get('/pos', PosScreen::class)->name('pos');
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
