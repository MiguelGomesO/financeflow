<?php

use App\Livewire\Dashboard;
use App\Livewire\Transactions;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');
Route::livewire('/transacoes', Transactions::class)->name('transactions');
