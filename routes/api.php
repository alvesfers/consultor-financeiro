<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransactionsController;
use App\Http\Controllers\Api\GeminiTransactionController;

Route::name('gemini.')->prefix('gemini')->group(function () {
    Route::post('parse-text',  [GeminiTransactionController::class, 'parseText'])->name('parse-text');
    Route::post('parse-image', [GeminiTransactionController::class, 'parseImage'])->name('parse-image');
});


Route::post('transactions', [TransactionsController::class, 'store'])->name('transactions.store');
