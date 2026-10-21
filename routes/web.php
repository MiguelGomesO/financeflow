<?php

use App\Livewire\Budgets;
use App\Livewire\Dashboard;
use App\Livewire\Goals;
use App\Livewire\Transactions;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');
Route::livewire('/transacoes', Transactions::class)->name('transactions');
Route::livewire('/orcamentos', Budgets::class)->name('budgets');
Route::livewire('/metas', Goals::class)->name('goals');
