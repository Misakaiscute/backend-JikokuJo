<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Trip;
use Carbon\Carbon;

class TripController extends Controller
{
    public function getTripsByRouteId_Date(string $route_id, string $date, ?string $time = null)
    {
        if (!preg_match('/^\d{8}$/', $date)) 
        {
            return response()->json([
            'data'   => [],
            'errors' => ['Date must be in YYYYMMDD format']
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $useTimeFilter = false;
        $secondsSinceMidnight = 0;

        if ($time !== null) 
        {
            if (!preg_match('/^\d{4}$/', $time) || $time > '2359' || substr($time, 2) > '59') {
                return response()->json([
                    'data'   => [],
                    'errors' => ['Time must be in HHMM format (e.g. 1430)']
                ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $useTimeFilter = true;
            $hour = (int) substr($time, 0, 2);
            $minute = (int) substr($time, 2, 2);
            $secondsSinceMidnight = $hour * 3600 + $minute * 60;
        }

        $carbonDate = Carbon::createFromFormat('Ymd', $date);
        $dayOfWeek = strtolower($carbonDate->englishDayOfWeek);

        $addedServices = DB::table('calendar_dates')
            ->select('service_id')
            ->where('date', $date)
            ->where('exception_type', 1);

        $removedServices = DB::table('calendar_dates')
            ->where('date', $date)
            ->where('exception_type', 2)
            ->pluck('service_id');

        $activeServices = $addedServices->pluck('service_id')->diff($removedServices);
        
        if ($activeServices->isEmpty()) {
            return response()->json([
                'data'   => ['trips' => []],
                'errors' => []
            ], 206, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $tripsQuery = Trip::where('route_id', $route_id)
            ->whereIn('service_id', $activeServices);

        if ($useTimeFilter) {
            $tripIdsWithFutureStops = DB::table('stop_times')
                ->selectRaw('DISTINCT trip_id')
                ->whereIn('trip_id', $tripsQuery->pluck('id'))
                ->whereRaw("
                    (SUBSTR(departure_time, 1, 2) * 3600 +
                    SUBSTR(departure_time, 4, 2) * 60 +
                    SUBSTR(departure_time, 7, 2)) >= ?
                ", [$secondsSinceMidnight])
                ->pluck('trip_id');

            if ($tripIdsWithFutureStops->isEmpty()) {
                return response()->json([
                    'data'   => ['trips' => []],
                    'errors' => []
                ], 206, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $tripsQuery->whereIn('id', $tripIdsWithFutureStops);
        }

        $trips = $tripsQuery->with([
        'stopTimes' => fn($q) => $q->orderBy('stop_sequence'), 'stopTimes.stop'])->get()
        ->map(function ($trip) {
            $stops = $trip->stopTimes->map(fn($st) => [
                'id' => $st->stop->id,
                'name' => $st->stop->name,
                'location' => [
                    'lat' => (float) $st->stop->lat,
                    'lon' => (float) $st->stop->lon,
                ]
            ]);

            return [
                'id' => $trip->id,
                'short_name' => $trip->short_name,
                'headsign' => $trip->trip_headsign,
                'shape_id' => $trip->shape_id,
                'stops' => $stops,
                'wheelchair_accessible' => (int) ($trip->wheelchair_accessible ?? 0),
                'bikes_allowed' => (int) ($trip->bikes_allowed ?? 0),
            ];
        });

        return response()->json([
            'data'   => ['trips' => $trips->values()],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
