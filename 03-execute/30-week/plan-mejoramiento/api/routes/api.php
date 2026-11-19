<?php

use Illuminate\Support\Facades\Route;
use Lab\Adapters\Http\Access;
use Lab\Adapters\Http\AuthController;
use Lab\Adapters\Http\EquipmentController;
use Lab\Adapters\Http\CoverController;
use Lab\Adapters\Http\ResearcherController;
use Lab\Adapters\Http\LoanController;
use Lab\Adapters\Http\ReportController;

Route::get('/health', static fn () => response()->json(['status' => 'ok']));
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware(Access::class)->group(function () {
    Route::get('/categories', [EquipmentController::class, 'categories']);
    Route::get('/equipment', [EquipmentController::class, 'search']);
    Route::get('/equipment/{id}', [EquipmentController::class, 'get']);
    Route::get('/equipment/{id}/cover', [CoverController::class, 'get']);
    Route::get('/researchers', [ResearcherController::class, 'search']);
    Route::get('/researchers/{id}', [ResearcherController::class, 'get']);
    Route::post('/researchers', [ResearcherController::class, 'create']);
    Route::post('/loans', [LoanController::class, 'lend']);
    Route::get('/loans', [LoanController::class, 'search']);
    Route::get('/loans/overdue', [LoanController::class, 'overdue']);
    Route::get('/loans/{id}', [LoanController::class, 'get']);
    Route::post('/loans/{loanId}/items/{itemId}/return', [LoanController::class, 'returnItem']);
});
Route::middleware(Access::class . ':admin')->group(function () {
    Route::post('/users', [AuthController::class, 'create']);
    Route::post('/equipment', [EquipmentController::class, 'create']);
    Route::put('/equipment/{id}', [EquipmentController::class, 'update']);
    Route::delete('/equipment/{id}', [EquipmentController::class, 'retire']);
    Route::put('/equipment/{id}/cover', [CoverController::class, 'upload']);
    Route::delete('/equipment/{id}/cover', [CoverController::class, 'remove']);
    Route::get('/reports/most-borrowed', [ReportController::class, 'get']);
});
