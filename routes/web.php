<?php

use Illuminate\Support\Facades\Route;


// SPA route - all non-API requests go to Vue app
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api|sanctum).*$');
