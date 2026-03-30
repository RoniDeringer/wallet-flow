<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\ClientsController;
use App\Http\Controllers\Api\Client\DepositsController;
use App\Http\Controllers\Api\Client\TransfersController;
use App\Http\Controllers\Api\Client\TransactionsController;
use App\Http\Controllers\Api\Client\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/admin/clients', [ClientsController::class, 'index']);

Route::get('/me/deposits', [DepositsController::class, 'index']);
Route::post('/me/deposits', [DepositsController::class, 'store']);
Route::get('/me/wallet', [WalletController::class, 'show']);
Route::get('/me/transactions', [TransactionsController::class, 'index']);
Route::post('/me/transfers', [TransfersController::class, 'store']);
