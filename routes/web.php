<?php

use Illuminate\Support\Facades\Route;

// Redirect root to Filament admin
Route::get('/', function () {
    return redirect('/admin');
});

// API documentation route (если нужно)
Route::get('/api/documentation', function () {
    return view('api-docs');
})->name('api.docs');
