<?php

use Illuminate\Support\Facades\Route;

// Test route without view
Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Laravel is working correctly',
        'app' => config('app.name'),
        'debug' => config('app.debug')
    ]);
});

// Redirect all web routes to Nuxt (handled by nginx proxy)
// This prevents Laravel from serving any pages - all UI is handled by Nuxt via API
Route::fallback(function () {
    // This is handled by nginx @frontend fallback which proxies to Nuxt
    abort(404);
});
