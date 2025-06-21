<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Hello';
});

Route::controller(App\Http\Controllers\AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
    Route::get('/me', 'me')->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin'], function () {
        Route::apiResource('users', App\Http\Controllers\UserController::class);
        Route::apiResource('categories', App\Http\Controllers\CategoryController::class);
        Route::apiResource('warehouses', App\Http\Controllers\WarehouseController::class);
        Route::apiResource('items', App\Http\Controllers\ItemController::class);
        Route::apiResource('units', App\Http\Controllers\ItemUnitController::class);
        Route::apiResource('stocks', App\Http\Controllers\StockController::class);
    });

    Route::group(['middleware' => 'role:borrower'], function () {
        Route::apiResource('items', App\Http\Controllers\ItemController::class)->except('store', 'update', 'destroy');
        Route::apiResource('units', App\Http\Controllers\ItemUnitController::class)->except('store', 'update', 'destroy');
        Route::apiResource('categories', App\Http\Controllers\CategoryController::class)->except('store', 'update', 'destroy');
        Route::apiResource('warehouses', App\Http\Controllers\WarehouseController::class)->except('store', 'update', 'destroy');
    });
});
