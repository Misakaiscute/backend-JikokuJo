<?php

namespace App\Http\Controllers;

use App\Models\RouteModel;

class RouteController extends Controller
{
    public function route_stops($routeId, $shapeId = null)
    {
        $query = RouteModel::with([
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
}
