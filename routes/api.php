<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShapeController;
use App\Http\Controllers\StopController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChannelActivityController;
//
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;

//menetrendkereső

Route::get('/queryables', [SearchController::class, 'queryables']);
//összes megállónév + járatnevek
Route::post('/stop/trip', [TripController::class, 'getTripsByStopId']);
//visszaadja az összes utat ami érint egy megállót
//adatok: date, time, ids
Route::post('/route/trip', [TripController::class, 'getTripsByRouteId']);
//ITT VÁLTOZOTT
//visszaadja egy route tripjeit és annak megállóit a routeId és időpont alapján 
//adatok: date (YYYYMMDD), time (HHMM), route_id
Route::post('/trip/shapes', [ShapeController::class, 'getShapesByTripId']);
//ITT VÁLTOZOTT
//visszaadja az összes shape pontját egy tripnek
//adatok: trip_id
Route::post('/trip/stops', [StopController::class, 'getStopsByTripId']);
//ITT VÁLTOZOTT 
//visszaadja az összes megállóját egy tripnek
//adatok: trip_id

//user

Route::post('/user/login', [UserController::class, 'login']);
//ITT VÁLTOZOTT
//rememberUser változó (default false) arra ha a login tokenje 7 napig legyen érvényes (default 1 nap) 
//adatok: email, password, remember_user
Route::post('/user/register', [UserController::class, 'store']);
//adatok: first_name, second_name, email, password, password_confirmation
Route::middleware('auth:sanctum')->group(function () 
{
    Route::get('/user', [UserController::class, 'get']);
    Route::post('/user/logout', [UserController::class, 'log_out']);
    Route::put('/user/update', [UserController::class, 'update']);
    //meglévő mezők bármelyikét beadhatod, ami ne változik kihagyhatod
    Route::delete('/user/delete', [UserController::class, 'destroy']);
    //token alapján töröl nem kell beadni semmit
    Route::post('/routes/favourite/toggle', [UserController::class, 'toggleFavourite']);
    //hozzáadja vagy kiveszi a routeot a kedvencek közül, adatok: route_id, time (HHMM)
    Route::get('/user/favourites', [UserController::class, 'favourites']);
    //favouritek listázása
    Route::post('/channel-activity', [ChannelActivityController::class, 'ping']);
});

//elkezdi a folyamatos streaelést a létrehozott csatornán, autoatikusan leáll ha senki nincs a channelben
//a csatorna neve mindig "trip.{trip_id}" 


Route::post('/broadcasting/auth-debug', function (Request $request) {
    Log::info('AUTH-DEBUG REQUEST', [
        'all' => $request->all(),
        'cookies' => $request->cookies->all(),
        'user' => $request->user()?->id,
        'auth_check' => auth()->check(),
        'header_x_xsrf_token' => $request->header('X-XSRF-TOKEN'),
        'header_x_requested_with' => $request->header('X-Requested-With'),
    ]);

    try {
        return Broadcast::auth($request);
    } catch (\Throwable $e) {
        Log::error('AUTH-DEBUG EXCEPTION', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
})->middleware(['api', 'auth:sanctum']);

