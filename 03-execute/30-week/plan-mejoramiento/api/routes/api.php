<?php

use Illuminate\Support\Facades\Route;
use Lab\Adapters\Http\LoanController;

Route::get('/health', static fn () => response()->json(['status' => 'ok']));
Route::post('/loans', [LoanController::class, 'lend']);
Route::post('/loans/{loanId}/items/{itemId}/return', [LoanController::class, 'returnItem']);
