<?php

use App\Http\Controllers\SearchController;
use App\Http\Controllers\RouteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/queryables', [SearchController::class, 'queryables']);
Route::get('/routes/{routeId}/details/{shapeId}', [RouteController::class, 'route_stops']);