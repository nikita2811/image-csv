<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserImportController;
use App\Http\Controllers\ImageController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/csv-upload', [UserImportController::class, 'csvUpload']);
Route::post('/images/chunk', [ImageController::class, 'chunk']);
Route::post('/complete', [ImageController::class, 'complete']);
