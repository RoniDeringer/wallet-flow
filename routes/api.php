<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\ClientsController;
use App\Http\Controllers\Api\Admin\LedgerTransactionsController;
use App\Http\Controllers\Api\Admin\LedgerResetController;
use App\Http\Controllers\Api\Client\DepositsController;
use App\Http\Controllers\Api\Client\OverviewController;
use App\Http\Controllers\Api\Client\ReversalsController;
use App\Http\Controllers\Api\Client\TransfersController;
use App\Http\Controllers\Api\Client\TransactionsController;
use App\Http\Controllers\Api\Client\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/clients', [ClientsController::class, 'index']);
    Route::post('/admin/clients', [ClientsController::class, 'store']);
    Route::post('/admin/ledger/reset', [LedgerResetController::class, 'store']);
    Route::get('/admin/ledger/transactions', [LedgerTransactionsController::class, 'index']);

    Route::get('/me/deposits', [DepositsController::class, 'index']);
    Route::post('/me/deposits', [DepositsController::class, 'store']);
    Route::get('/me/overview', [OverviewController::class, 'show']);
    Route::get('/me/wallet', [WalletController::class, 'show']);
    Route::get('/me/transactions', [TransactionsController::class, 'index']);
    Route::post('/me/transfers', [TransfersController::class, 'store']);
    Route::post('/me/transactions/{transactionId}/reversal', [ReversalsController::class, 'store']);
});
