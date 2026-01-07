<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Shape;
use App\Models\Stop;
use App\Models\StopTime;

class RouteController extends Controller
{
    public function getShapesByRouteId($route_id)
    {
        $shapes = Shape::whereIn('id', function ($query) use ($route_id) {
            $query->select('shape_id')
                  ->from('trips')
                  ->where('route_id', $route_id)
                  ->distinct();
        })
        ->orderBy('id')
        ->orderBy('pt_sequence')
        ->select('id', 'pt_lat', 'pt_lon')
        ->get()
        ->groupBy('id')
        ->map(function ($points, $shape_id) {
            return [
                'shape_id' => $shape_id,
                'points'   => $points->map(function ($point) {
                    return [
                        'lat' => (float) $point->pt_lat,
                        'lon' => (float) $point->pt_lon,
                    ];
                })->values()->all()
            ];
        })
        ->values()
        ->all();

        return response()->json([
            'data' => [
                'shapes' => $shapes,
            ],
            'errors' => [], 200, [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ]);
    }

    public function getRoutesByStopId(string $stop_id)
    {
        $stop = Stop::where('id', $stop_id)
            ->select('id', 'name', 'lat', 'lon')
            ->first();

        if (!$stop) {
            return response()->json([
                'data' => [],
                'errors' => [
                    'message' => 'Nem található megállóhely ezzel a stop_id-val: ' . $stop_id,
                    'suggestion' => 'Ellenőrizd a stop_id-t, lehet elütés vagy régi azonosító.'
                ]
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $hasSchedule = StopTime::where('stop_id', $stop_id)->exists();

        if (!$hasSchedule) {
            return response()->json([
                'data' => [],
                'errors' => [],
                'warnings' => [
                    'message' => 'Nem aktív megállóhely',
                    'details' => 'A megálló létezik, de nincs menetrend szerinti járat hozzárendelve (pl. tárolóterület, üzem, belső pont).',
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $results = StopTime::where('stop_id', $stop_id)
            ->with(['trip.route'])
            ->select('trip_id')
            ->distinct()
            ->get()
            ->pluck('trip.route')
            ->unique('id')
            ->sortBy('short_name')
            ->map(function ($route) {
                return [
                    'route_id'   => $route->id,
                    'short_name' => $route->short_name,
                    'type'       => $route->type,
                    'color'      => $route->color,
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'data' => $results,
            'errors' => [],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
