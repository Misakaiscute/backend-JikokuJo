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

    
}
