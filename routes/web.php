<?php

use Illuminate\Support\Facades\Route;

// Vue.js SPA root route
Route::get('/', function () {
    return view('app');
});

// Login route для API редиректов (для неаутентифицированных API запросов)
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// SPA catch-all route - must be last
// Исключает /api/* и /admin/* маршруты
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|admin).*$');
