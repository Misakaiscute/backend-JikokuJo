<?php

use App\Http\Controllers\SearchController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\StopController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//menetrendkereső

Route::get('/queryables', [SearchController::class, 'queryables']);
//összes megállónév + id-jük tömbösítve, + járatnevek
Route::get('/routes/{routeId}/fromWhere/{stopId}/date/{date}', [RouteController::class, 'getArrivalTimesByRouteId']);
//egy route_id-ből és stop_id-ből visszaadja az indulási időt + shapeId-t az adott dátumom
Route::get('/routes/{routeId}/details/{shapeId}', [RouteController::class, 'getRouteStops']);
//a routeId és shaeId-val egy konkrét utat ad vissza a shape pontokkal együtt
Route::get('/routes/{stopId}', [StopController::class, 'getRoutesForStopId']);
//visszaadja az összes járatot + shapeId-t ami érinti a megállót