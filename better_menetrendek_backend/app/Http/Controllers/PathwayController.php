<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PathwayController extends Controller
{
    public function route_stops($shortName)
{
    $baseQuery = DB::table('routes')
        ->join('trips', 'routes.id', '=', 'trips.route_id')
        ->join('stop_times', 'trips.id', '=', 'stop_times.trip_id')
        ->join('stops', 'stop_times.stop_id', '=', 'stops.id')
        ->where('routes.short_name', $shortName)
        ->select(
            'stops.id',
            'stops.name',
            'stops.lat',
            'stops.lon',
            DB::raw('MIN(stop_times.stop_sequence) as stop_sequence'),
            DB::raw('MIN(stop_times.arrival_time) as arrival_time'),
            DB::raw('MIN(stop_times.departure_time) as departure_time')
        )
        ->groupBy(['stops.id', 'stops.name', 'stops.lat', 'stops.lon'])
        ->orderBy('stop_sequence');

    $forward_stops  = $baseQuery->clone()->where('trips.direction_id', 1)->get();
    $backward_stops = $baseQuery->clone()->where('trips.direction_id', 0)->get();

    if ($forward_stops->isEmpty() && $backward_stops->isEmpty()) {
        return response()->json([
            'data'   => ['forward' => [], 'backward' => []],
            'errors' => [['error' => 'Route not found']]
        ], 404);
    }

    return response()->json([
        'data'   => [
            'forward'  => $forward_stops,
            'backward' => $backward_stops
        ],
        'errors' => []
    ], 200);
}
}

