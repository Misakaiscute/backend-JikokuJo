<?php

use App\Http\Controllers\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/searchable', [SearchController::class, 'searchable']); 
// Route::get('/users', [UserController::class, 'index'])->middleware('auth:sanctum');
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');