<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// V1 API Routes
Route::group([
    'prefix' => 'v1',
    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers'
], function () {
    // Load V1 route classes
    foreach (glob(app_path('Http/Routes/V1') . '/*.php') as $file) {
        $className = 'App\\Http\\Routes\\V1\\' . basename($file, '.php');
        if (class_exists($className)) {
            app($className)->map(app('router'));
        }
    }
});

// V2 API Routes
Route::group([
    'prefix' => 'v2', 
    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers'
], function () {
    // Load V2 route classes
    foreach (glob(app_path('Http/Routes/V2') . '/*.php') as $file) {
        $className = 'App\\Http\\Routes\\V2\\' . basename($file, '.php');
        if (class_exists($className)) {
            app($className)->map(app('router'));
        }
    }
});
