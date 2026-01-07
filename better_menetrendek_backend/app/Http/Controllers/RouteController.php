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
            'trips.shapePoints'
        ])->where('id', $routeId);

        $route = $query->first();

        if (!$route) {
            return response()->json([
                'data'   => ['patterns' => []],
                'errors' => ['Route not found']
            ], 404, [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

                $shapes = $firstTrip->shapePoints
                    ->map(fn($point) => [
                        'lat'           => $point->pt_lat,
                        'lon'           => $point->pt_lon,
                        'sequence'      => $point->pt_sequence,
                        'dist_traveled' => $point->dist_traveled,
                    ])
                    ->values();

                return [
                    'shape_id'      => $shapeId,
                    'direction_id'  => $firstTrip->direction_id,
                    'trip_headsign' => $firstTrip->trip_headsign,
                    'stops'         => $stops,
                    'shapes'       => $shapes,
                    'trip_count'    => $tripsInThisPattern->count(),
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'route_id' => $routeId,
                'patterns' => $patterns
            ],
            'errors' => [], 200, [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ]);
    }

    public function getArrivalTimesByRouteId(string $routeId, ?string $stopId = null, ?string $date = null)
    {
        $targetStopId = $stopId;
        $targetDate = $date ?? now()->format('Ymd');

        $feedInfo = DB::table('feed_info')->first();
        
        if ($feedInfo && ($targetDate < $feedInfo->start_date || $targetDate > $feedInfo->end_date)) {
            return response()->json([
                'data'   => [
                    'times'  => [],
                    'route' => null
                ],
                'errors' => ['Date is outside feed validity period']
            ], 400, [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!$targetStopId) {
            $targetStopId = DB::table('stop_times')
                ->join('trips', 'stop_times.trip_id', '=', 'trips.id')
                ->where('trips.route_id', $routeId)
                ->orderBy('stop_times.stop_sequence')
                ->value('stop_times.stop_id');
        }

        if (!$targetStopId) {
            return response()->json([
                'data'   => [
                    'times'  => [],
                    'route' => null
                ],
                'errors' => ['Route not found or has no trips']
            ], 404, [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $times = DB::table('stop_times')
            ->join('trips', 'stop_times.trip_id', '=', 'trips.id')
            ->where('trips.route_id', $routeId)
            ->where('stop_times.stop_id', $targetStopId)
            ->select([
                'trips.shape_id',
                'trips.trip_headsign',
                'stop_times.arrival_time'
            ])
            ->distinct()
            ->orderBy('stop_times.arrival_time')
            ->get()
            ->map(function ($item) {
                return [
                    'shape_id'      => $item->shape_id ?: null,
                    'trip_headsign' => $item->trip_headsign,
                    'arrival_time'  => $item->arrival_time,
                ];
            });

        $routeInfo = DB::table('routes')
            ->where('id', $routeId)
            ->select('short_name', 'long_name')
            ->first();

        $route = $routeInfo ? [
            'route_id'         => $routeId,
            'short_name' => $routeInfo->short_name,
            'long_name'  => $routeInfo->long_name,
            'date'             => $targetDate
        ] : null;

        if ($times->isEmpty() && !$route) {
            return response()->json([
                'data'   => [
                    'times'  => [],
                    'route' => null
                ],
                'errors' => ['No data available']
            ], 404, [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'data'   => [
                'times'  => $times->toArray(),
                'route' => $route
            ],
            'errors' => []
        ], 200, [],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function getRoutesByStopId(string $stopId)              //JO
    {
        $stop = DB::table('stops')
        ->where('stop_id', $stopId)
        ->select('stop_id', 'name', 'lat', 'lon')
        ->first();

        if (!$stop) {
            return response()->json([
                'data' => [],
                'errors' => [
                    'message' => 'Nem található megállóhely ezzel a stop_id-val: ' . $stopId,
                    'suggestion' => 'Ellenőrizd a stop_id-t, lehet elütés vagy régi azonosító.'
                ]
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $hasSchedule = DB::table('stop_times')
            ->where('stop_id', $stopId)
            ->exists();

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

        $results = DB::table('stop_times as st')
            ->join('trips as t', 'st.trip_id', '=', 't.id')
            ->join('routes as r', 't.route_id', '=', 'r.id')
            ->where('st.stop_id', $stopId)
            ->select([
                'r.id as route_id',
                'r.short_name',
                'r.type',
                'r.color',
            ])
            ->distinct()
            ->orderBy('r.short_name')
            ->get()
            ->values()
            ->toArray();

        return response()->json([
            'data' => $results,
            'errors' => [],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
