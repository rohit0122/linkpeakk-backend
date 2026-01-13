<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (request()->is('api/*') || request()->expectsJson()) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized access or invalid endpoint.',
            'data' => []
        ], 200);
    }
    return response('404 | Unauthorized access.', 200);
});
