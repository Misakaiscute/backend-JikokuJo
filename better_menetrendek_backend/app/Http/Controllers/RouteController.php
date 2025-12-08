<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function getRouteStops($routeId, $shapeId = null)
    {
        $query = Route::with([
            'trips.stopTimes.stop',
            'trips.shape'
        ])->where('id', $routeId);

        $route = $query->first();

        if (!$route) {
            return response()->json([
                'data'   => ['patterns' => []],
                'errors' => [['error' => 'Route not found']]
            ], 404);
        }

        $patterns = $route->trips
            ->when($shapeId, fn($trips) => $trips->where('shape_id', $shapeId))
            ->groupBy('shape_id')
            ->map(function ($tripsInThisPattern, $shapeId) {
                $firstTrip = $tripsInThisPattern->first();

                $stops = $firstTrip->stopTimes
                    ->sortBy('stop_sequence')
                    ->map(fn($st) => [
                        'id'            => $st->stop->id,
                        'name'          => $st->stop->name,
                        'lat'           => $st->stop->lat,
                        'lon'           => $st->stop->lon,
                        'stop_sequence' => $st->stop_sequence,
                    ])
                    ->values();

                return [
                    'shape_id'      => $shapeId,
                    'direction_id'  => $firstTrip->direction_id,
                    'trip_headsign' => $firstTrip->trip_headsign,
                    'stops'         => $stops,
                    'trip_count'    => $tripsInThisPattern->count(),
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'route_id'  => $routeId,
                'patterns'  => $patterns
            ],
            'errors' => []
        ]);
    }


    public function getInfoByRouteId(string $routeId)
    {
        $tripsWithArrivals = Trip::with(['stopTimes' => function ($query) {
            $query->select('trip_id', 'arrival_time', 'stop_sequence');
        }])
        ->select('id', 'route_id', 'service_id', 'trip_headsign', 'direction_id', 'shape_id')
        ->where('route_id', $routeId)
        ->orderBy('id')
        ->get()
        ->map(function ($trip) {
            return response()->json([
                'id'       => $trip->id,
                'route_id'      => $trip->route_id,
                'service_id'    => $trip->service_id,
                'trip_headsign' => $trip->trip_headsign,
                'direction_id'  => $trip->direction_id,
                'shape_id'      => $trip->shape_id,
                'stop_times'    => $trip->stopTimes->sortBy('stop_sequence')->pluck('arrival_time', 'stop_sequence')->all()
            ]);
        });
    } 


    public function getArrivalTimeByRouteId(string $routeId, ?string $stopId = null)
    {
        $targetStopId = $stopId;

        if (!$targetStopId) {
            $targetStopId = DB::table('stop_times')
                ->join('trips', 'stop_times.trip_id', '=', 'trips.id')
                ->where('trips.route_id', $routeId)
                ->min('stop_times.stop_sequence')
                ? DB::table('stop_times')
                    ->join('trips', 'stop_times.trip_id', '=', 'trips.id')
                    ->where('trips.route_id', $routeId)
                    ->orderBy('stop_times.stop_sequence')
                    ->value('stop_times.id')
                : null;
        }

        if (!$targetStopId) {
            return response()->json([
                'data'   => [
                    'stops'  => [],
                    'routes' => []
                ],
                'errors' => [['error' => 'Route not found or has no trips']]
            ], 404);
        }

        $trips = Trip::query()
            ->select([
                'trips.id',
                'trips.shape_id',
                'trips.trip_headsign',
                'trips.direction_id',
                'stop_times.arrival_time',
                'stop_times.departure_time',
                'stop_times.stop_sequence'
            ])
            ->join('stop_times', function ($join) use ($targetStopId) {
                $join->on('trips.id', '=', 'stop_times.trip_id')
                    ->where('stop_times.stop_id', '=', $targetStopId);
            })
            ->where('trips.route_id', $routeId)
            ->orderBy('stop_times.arrival_time')
            ->get()
            ->map(function ($trip) use ($targetStopId) {
                return [
                    'trip_id'         => $trip->id,
                    'shape_id'        => $trip->shape_id ?: null,
                    'trip_headsign'   => $trip->trip_headsign,
                    'direction_id'    => $trip->direction_id,
                    'arrival_time'    => $trip->arrival_time,
                    'departure_time'  => $trip->departure_time,
                    'stop_id'         => $targetStopId,
                    'stop_sequence'   => $trip->stop_sequence,
                ];
            });

        $routeInfo = DB::table('routes')
            ->where('route_id', $routeId)
            ->select('route_short_name', 'route_long_name')
            ->first();

        $routeNames = $routeInfo ? [[
            'route_id'         => $routeId,
            'route_short_name' => $routeInfo->route_short_name,
            'route_long_name'  => $routeInfo->route_long_name,
        ]] : [];

        if ($trips->isEmpty() && empty($routeNames)) {
            return response()
                ->json([
                    'data'   => [
                        'stops'  => [],
                        'routes' => []
                    ],
                    'errors' => [['error' => 'No data available']]
                ], 404);
        }

        return response()->json([
            'data'   => [
                'stops'  => $trips->toArray(),
                'routes' => $routeNames
            ],
            'errors' => []
        ], 200);
    }
}
