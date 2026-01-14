<?php

use App\Http\Controllers\SearchController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use App\Http\Requests\UserRequest;

//menetrendkereső

Route::get('/queryables', [SearchController::class, 'queryables']);
//összes megállónév + járatnevek
Route::get('/stop/{stop_id}/trips/{date}/{time}', [TripController::class, 'getTripsByStopId']);
//visszaadja az összes utat ami érint egy megállót
Route::get('/trips/{route_id}/time/{date}/{time}', [TripController::class, 'getTripsByRouteId']);
//visszaadja egy route tripjeit és annak megállóit a routeId és időpont alapján (YYYYMMDD pl {20260107}), (HHMM pl {1431})
Route::get('/routes/{route_id}/shapes', [RouteController::class, 'getShapesByRouteId']);
//visszaadja az összes lehetséges shapejét egy routenak



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
    Route::delete('/user/delete', [UserController::class, 'destroy']);
    Route::post('/routes/favourite/toggle', [UserController::class, 'toggleFavouriteRoute']);
    //hozzáadja vagy kiveszi a routeot a kedvencek közül, a "route_id"-t és a "minutes" változót (indulási ideje percben) json bodyból veszi ki a requestből
});





