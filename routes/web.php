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

// Welcome route with view
Route::get('/', function () {
    return view('welcome');
});
