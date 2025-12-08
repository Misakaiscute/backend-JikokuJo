<?php

use App\Http\Controllers\SearchController;
use App\Http\Controllers\RouteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/queryables', [SearchController::class, 'queryables']);  //összes megállónév + idjük tömbösítve, + járatnevek
Route::get('/routes/{routeId}', [RouteController::class, 'getInfoByRouteId']);  //egy route_id-ből visszaadja az időpontokat + shapeId-kat
Route::get('/routes/{routeId}/fromWhere/{stopId}', [RouteController::class, 'getArrivalTimeByRouteId']);  //egy route_id-ből és stop_idből visszaadja az indulási időt + shapeId-t
Route::get('/routes/{routeId}/details/{shapeId}', [RouteController::class, 'getRouteStops']); //a routeId és shaeId-val egy konkrét utat ad vissza a shape pontokkal együtt