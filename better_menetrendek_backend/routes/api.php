<?php

use App\Http\Controllers\SearchController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TripController;
use Illuminate\Http\Request;

//menetrendkereső

Route::get('/queryables', [SearchController::class, 'queryables']);
//összes megállónév + járatnevek
Route::get('/stops/{stop_id}/routes', [RouteController::class, 'getRoutesByStopId']);
//visszaadja az összes járatot ami érint egy megállót
Route::get('/routes/{route_id}/shapes', [RouteController::class, 'getShapesByRouteId']);
//visszaadja az összes lehetséges shapejét egy routenak
Route::get('/routes/{route_d}/time/{date}/{time}', [TripController::class, 'getTripsByRouteId_Date']);
//visszaadja tripet és annak megállóit a routeId és időpont alapján (YYYYMMDD pl 20260107), (HHMM pl 1431)

