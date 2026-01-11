<?php

use App\Http\Controllers\SearchController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use App\Http\Requests\UserRequest;

//menetrendkereső

Route::get('/queryables', [SearchController::class, 'queryables']);
//összes megállónév + járatnevek
Route::get('/stops/{stop_id}/routes', [RouteController::class, 'getRoutesByStopId']);
//visszaadja az összes járatot ami érint egy megállót
Route::get('/routes/{route_id}/shapes', [RouteController::class, 'getShapesByRouteId']);
//visszaadja az összes lehetséges shapejét egy routenak
Route::get('/routes/{route_id}/time/{date}/{time}', [TripController::class, 'getTripsByRouteId_Date']);
//visszaadja egy route tripjeit és annak megállóit a routeId és időpont alapján (YYYYMMDD pl 20260107), (HHMM pl 1431)


//user

Route::post('/user/login/{rememberUser}', [UserController::class, 'login']);
//rememberUser változó (default false) arra ha a login tokenje 7 napig legyen érvényes (default 1 nap) 
Route::post('/user/register', [UserController::class, 'store']);
Route::middleware('auth:sanctum')->group(function () 
{
    Route::get('/user', function (UserRequest $request) {
        return $request->user();
    });
    Route::put('/user/update', [UserController::class, 'update']);
    Route::delete('/user/delete', function (UserRequest $request) {
        return $request->user()->delete;
    });
    Route::post('/routes/favourite/toggle', [UserController::class, 'toggleFavouriteRoute']);
    //hozzáadja vagy kiveszi a routeot a kedvencek közül, a "route_id"-t és a "minutes" változót (indulási ideje percben) json bodyból veszi ki a requestből
});





