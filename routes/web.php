<?php

use Illuminate\Support\Facades\Route;

// Redirect root to Filament admin
Route::get('/', function () {
    return redirect('/admin');
});

// Login route для API редиректов
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// API documentation route
Route::get('/api/documentation', function () {
    return view('api-docs');
})->name('api.docs');
