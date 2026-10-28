<?php

use App\Livewire\Accounts;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Budgets;
use App\Livewire\Dashboard;
use App\Livewire\Goals;
use App\Livewire\Profile;
use App\Livewire\Recurring;
use App\Livewire\Reports;
use App\Livewire\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/entrar', Login::class)->name('login');
    Route::livewire('/cadastro', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/transacoes', Transactions::class)->name('transactions');
    Route::livewire('/relatorios', Reports::class)->name('reports');
    Route::livewire('/recorrentes', Recurring::class)->name('recurring');
    Route::livewire('/orcamentos', Budgets::class)->name('budgets');
    Route::livewire('/metas', Goals::class)->name('goals');
    Route::livewire('/contas', Accounts::class)->name('accounts');
    Route::livewire('/perfil', Profile::class)->name('profile');

    Route::post('/sair', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
