<?php

use Illuminate\Support\Facades\Route;

// SPA root route
Route::get('/', function () {
    return view('app');
});

// SPA catch-all route - must be last
// Excludes /api/* and /admin/* routes
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|admin).*$');
