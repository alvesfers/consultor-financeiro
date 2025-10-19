<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AiTransactionController;
use App\Http\Controllers\Api\TransactionsController;

Route::name('ai.')->prefix('ai')->group(function () {
    Route::post('parse-text',  [AiTransactionController::class, 'parseText'])->name('parse-text');
    Route::post('parse-audio', [AiTransactionController::class, 'parseAudio'])->name('parse-audio');
    Route::post('parse-image', [AiTransactionController::class, 'parseImage'])->name('parse-image');
});

Route::post('transactions', [TransactionsController::class, 'store'])->name('transactions.store');
