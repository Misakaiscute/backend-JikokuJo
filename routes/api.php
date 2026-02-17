<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShapeController;
use App\Http\Controllers\StopController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use App\Http\Requests\UserRequest;

//menetrendkereső

Route::get('/queryables', [SearchController::class, 'queryables']);
//összes megállónév + járatnevek
Route::get('/stop/{stop_id}/time/{date}/{time}', [TripController::class, 'getTripsByStopId']);
//visszaadja az összes utat ami érint egy megállót
Route::get('/route/{route_id}/time/{date}/{time}', [TripController::class, 'getTripsByRouteId']);
//visszaadja egy route tripjeit és annak megállóit a routeId és időpont alapján (YYYYMMDD pl {20260107}), (HHMM pl {1431})
Route::get('/trip/{trip_id}/shapes', [ShapeController::class, 'getShapesByTripId']);
//visszaadja az összes shape pontját egy tripnek
Route::get('/trip/{trip_id}/stops', [StopController::class, 'getStopsByTripId']);
//visszaadja az összes megállóját egy tripnek



//user

Route::post('/user/login/{rememberUser}', [UserController::class, 'login']);
//rememberUser változó (default false) arra ha a login tokenje 7 napig legyen érvényes (default 1 nap) 
//adatok: email, password
Route::post('/user/register', [UserController::class, 'store']);
//adatok: first_name, second_name, email, password, password_confirmation
Route::middleware('auth:sanctum')->group(function () 
{
    Route::get('/user', [UserController::class, 'get']);
    Route::put('/user/update', [UserController::class, 'update']);
    //meglévő mezők bármelyikét beadhatod, ami ne változik kihagyhatod
    Route::delete('/user/delete', [UserController::class, 'destroy']);
    //token alapján töröl nem kell beadni semmit
    Route::post('/routes/favourite/toggle', [UserController::class, 'toggleFavourite']);
    //hozzáadja vagy kiveszi a routeot a kedvencek közül, a "route_id"-t és a "minutes" változót (indulási ideje percben) json bodyból veszi ki a requestből
});





