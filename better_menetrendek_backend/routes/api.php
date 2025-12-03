<?php

use App\Http\Controllers\StopController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/stops', [StopController::class, 'index_stops']); 
// Route::get('/users', [UserController::class, 'index'])->middleware('auth:sanctum');
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');