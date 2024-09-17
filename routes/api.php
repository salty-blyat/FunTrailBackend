<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Hotel\HotelController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\Restaurant\RestaurantController;
use App\Http\Controllers\User\UserController;

/* 
                        ================================
                        ||     Level of user types    ||
                        ================================
                        //- admin                     || 
                        ||- restaurant  \   hotel     ||       
                        ||- customer                  ||      
                        ================================
 */


// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    /* protected routes */
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// protected routes
Route::middleware('auth:sanctum')->prefix('hotel')->group(function () {
    // work done
    Route::get('list',                      [HotelController::class, 'index']);
    Route::post('create',                   [HotelController::class, 'store']);
    Route::post('update',                   [HotelController::class, 'update']);
    Route::post('delete',                   [HotelController::class, 'destroy']);
    Route::get('show/{id}',                 [HotelController::class, 'show']);
    Route::get('rooms/{id}',                [HotelController::class, 'rooms']);

    Route::post('book',                     [HotelController::class, 'book']);
});

// protected routes
Route::middleware('auth:sanctum')->prefix('restaurant')->group(function () {

    Route::get('list',                      [RestaurantController::class, 'index']);
    Route::post('create',                   [RestaurantController::class, 'store']);
    Route::post('update',                   [RestaurantController::class, 'update']);
    Route::post('delete',                   [RestaurantController::class, 'destroy']);
    Route::get('show/{id}',                 [RestaurantController::class, 'show']);
    // Route::get('products/{id}',          [RestaurantController::class, '']);
    // Route::post('checkout',              [RestaurantController::class, 'checkout']);

});



// public route 
Route::prefix('search')->group(function () {
    Route::get('hotel', [HotelController::class, 'search']);
});


// protected routes
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('list',          [UserController::class, 'index']);
    Route::post('create',       [UserController::class, 'store']);
    Route::post('update/{id}',  [UserController::class, 'update']);
    Route::post('delete',       [UserController::class, 'destroy']);
    Route::get('show/{id}',     [UserController::class, 'show']);
});


Route::prefix('popular')->group(function () {
    // work done
    Route::get('hotels', [HotelController::class, 'popular']);

    // not done
    // work on this
    Route::get('restaurants', [RestaurantController::class, 'popular']);
});
