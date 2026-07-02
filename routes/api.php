<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
});

Route::middleware('auth:api')->group(function (): void {
    Route::get('me', [MeController::class, 'show']);
    Route::get('me/sections', [MeController::class, 'sections']);
});

Route::middleware(['auth:api', 'section.access:products'])->group(function (): void {
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/export/pdf', [ProductController::class, 'exportPdf']);
    Route::get('products/export/excel', [ProductController::class, 'exportExcel']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
});

Route::middleware(['auth:api', 'section.access:users'])->group(function (): void {
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/export/pdf', [UserController::class, 'exportPdf']);
    Route::get('users/export/excel', [UserController::class, 'exportExcel']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
});

Route::middleware(['auth:api', 'section.access:profiles'])->group(function (): void {
    Route::get('profiles', [ProfileController::class, 'index']);
    Route::post('profiles', [ProfileController::class, 'store']);
    Route::get('profiles/export/pdf', [ProfileController::class, 'exportPdf']);
    Route::get('profiles/export/excel', [ProfileController::class, 'exportExcel']);
    Route::get('profiles/{id}', [ProfileController::class, 'show']);
    Route::put('profiles/{id}', [ProfileController::class, 'update']);
    Route::delete('profiles/{id}', [ProfileController::class, 'destroy']);
});

Route::get('sections', [SectionController::class, 'index'])->middleware('auth:api');
