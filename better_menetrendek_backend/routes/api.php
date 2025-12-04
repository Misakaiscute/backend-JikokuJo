<?php

use App\Http\Controllers\SearchController;
use App\Http\Controllers\PathwayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/searchables', [SearchController::class, 'searchables']); 
Route::get('/route_stops/{shortName}', [PathwayController::class, 'route_stops']); 