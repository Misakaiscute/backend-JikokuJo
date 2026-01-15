<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Trip;
use App\Models\Stop;
use App\Models\StopTime;
use Carbon\Carbon;

class TripController extends Controller
{
    public function getTripsByRouteId(string $route_id, ?string $date = null, ?string $time = null)
    {
        $date = $date ?? Carbon::today()->format('Ymd');
        $time = $time ?? Carbon::now()->format('Hi');

        if (!preg_match('/^\d{8}$/', $date)) {
            return response()->json([
                'errors' => ['Incorrect date format (YYYYMMDD).']
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($time && (!preg_match('/^\d{4}$/', $time) || $time > '2359')) {
            return response()->json([
                'errors' => ['Incorrect time format (HHMM).']
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $targetMinutes = null;
        if ($time) {
            $hour = (int) substr($time, 0, 2);
            $minute = (int) substr($time, 2, 2);
            $targetMinutes = $hour * 60 + $minute;
        }

        $activeServices = DB::table('calendar_dates')
            ->where('date', $date)
            ->where('exception_type', 1)
            ->pluck('service_id');

        if ($activeServices->isEmpty()) {
            return response()->json([
                'data' => ['trips' => []],
                'message' => 'No trips are available.'
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $tripsQuery = Trip::query()
            ->where('route_id', $route_id)
            ->whereIn('service_id', $activeServices);

        if ($targetMinutes !== null) {
            $windowStart = $targetMinutes;
            $windowEnd   = $targetMinutes + 120;

            $tripsInWindow = DB::table('stop_times')
                ->whereIn('trip_id', $tripsQuery->select('id'))
                ->whereBetween('departure_time', [$windowStart, $windowEnd])
                ->distinct()
                ->pluck('trip_id');

            if ($tripsInWindow->isEmpty()) {
                return response()->json([
                    'data' => ['trips' => []],
                    'message' => 'No routes are available in this time frame.'
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $tripsQuery->whereIn('id', $tripsInWindow);
        }

        $trips = $tripsQuery
            ->select([
                'id',
                'route_id',
                'service_id',
                'trip_headsign',
                'direction_id',
                'shape_id',
                'wheelchair_accessible',
                'bikes_allowed'
            ])
            ->with([
                'stopTimes' => fn($q) => $q
                    ->select('trip_id', 'stop_id', 'stop_sequence', 'arrival_time', 'departure_time', 'stop_headsign')
                    ->orderBy('stop_sequence'),
                'stopTimes.stop' => fn($q) => $q
                    ->select('id', 'name', 'lat', 'lon', 'code')
            ])
            ->get()
            ->map(function ($trip) {
                $first = $trip->stopTimes->first();
                $last  = $trip->stopTimes->last();

                $stops = [];

                if ($first) {
                    $stops[] = $this->formatStop($first);
                }

                if ($last && (!$first || $last->stop_sequence !== $first->stop_sequence)) {
                    $stops[] = $this->formatStop($last);
                }

                return [
                    'id'                   => $trip->id,
                    'route_id'             => $trip->route_id,
                    'shape_id'             => $trip->shape_id ?? null,
                    'headsign'             => $trip->trip_headsign ?? '',
                    'direction_id'         => (int) ($trip->direction_id ?? 0),
                    'wheelchair_accessible'=> (int) ($trip->wheelchair_accessible ?? 0),
                    'bikes_allowed'        => (int) ($trip->bikes_allowed ?? 0),
                    'stops'                => $stops,
                ];
            });

        return response()->json([
            'data' => [
                'trips' => $trips->values(),
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function getTripsByStopId(string $stop_id, ?string $date = null, ?string $time = null)
    {
        $date = $date ?? Carbon::today()->format('Ymd');
        $time = $time ?? Carbon::now()->format('Hi');

        if (!preg_match('/^\d{8}$/', $date)) {
            return response()->json([
                'errors' => ['Incorrect date format (YYYYMMDD).']
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($time && (!preg_match('/^\d{4}$/', $time) || $time > '2359')) {
            return response()->json([
                'errors' => ['Incorrect time format (HHMM).']
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $targetMinutes = null;
        if ($time) {
            $hour = (int) substr($time, 0, 2);
            $minute = (int) substr($time, 2, 2);
            $targetMinutes = $hour * 60 + $minute;
        }

        $activeServices = DB::table('calendar_dates')
            ->where('date', $date)
            ->where('exception_type', 1)
            ->pluck('service_id');

        if ($activeServices->isEmpty()) {
            return response()->json([
                'data' => ['trips' => []],
                'message' => 'No trips are available.'
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $tripsQuery = Trip::query()
            ->whereIn('service_id', $activeServices);

        if ($targetMinutes !== null) {
            $windowStart = $targetMinutes;
            $windowEnd   = $targetMinutes + 120;

            $tripsInWindow = DB::table('stop_times')
                ->where('stop_id', $stop_id)
                ->whereBetween('departure_time', [$windowStart, $windowEnd])
                ->distinct()
                ->pluck('trip_id');

            if ($tripsInWindow->isEmpty()) {
                return response()->json([
                    'data' => ['trips' => []],
                    'message' => 'Nincs indulás a megadott megállóból a kiválasztott időablakban'
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $tripsQuery->whereIn('id', $tripsInWindow);
        } else {
            return response()->json([
                'data' => ['trips' => []],
                'message' => 'No trips are available.'
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $tripsQuery->whereIn('id', $tripsInWindow);
        }

        $trips = $tripsQuery
            ->select([
                'id',
                'route_id',
                'service_id',
                'trip_headsign',
                'direction_id',
                'shape_id',
                'wheelchair_accessible',
                'bikes_allowed'
            ])
            ->with([
                'stopTimes' => fn($q) => $q
                    ->select('trip_id', 'stop_id', 'stop_sequence', 'arrival_time', 'departure_time', 'stop_headsign')
                    ->orderBy('stop_sequence'),
                'stopTimes.stop' => fn($q) => $q
                    ->select('id', 'name', 'lat', 'lon', 'code')
            ])
            ->get()
            ->map(function ($trip) use ($stop_id) {
                $first = $trip->stopTimes()->where('stop_id', $stop_id)->first();
                $last  = $trip->stopTimes->last();

                $stops = [];

                if ($first) {
                    $stops[] = $this->formatStop($first);
                }

                if ($last && (!$first || $last->stop_sequence !== $first->stop_sequence)) {
                    $stops[] = $this->formatStop($last);
                }

                return [
                    'id'                   => $trip->id,
                    'route_id'             => $trip->route_id,
                    'shape_id'             => $trip->shape_id ?? null,
                    'headsign'             => $trip->trip_headsign ?? '',
                    'direction_id'         => (int) ($trip->direction_id ?? 0),
                    'wheelchair_accessible'=> (int) ($trip->wheelchair_accessible ?? 0),
                    'bikes_allowed'        => (int) ($trip->bikes_allowed ?? 0),
                    'stops'                => $stops,
                ];
            });

        return response()->json([
            'data' => [
                'trips' => $trips->values(),
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function formatStop($stopTime)
    {
        return [
            'stop_id'        => $stopTime->stop_id,
            'stop_name'      => $stopTime->stop->stop_name ?? $stopTime->stop->name ?? '',
            'stop_sequence'  => $stopTime->stop_sequence,
            'arrival_time'   => $stopTime->arrival_time,
            'location'       => [
                'lat' => (float) ($stopTime->stop->stop_lat ?? $stopTime->stop->lat ?? 0),
                'lon' => (float) ($stopTime->stop->stop_lon ?? $stopTime->stop->lon ?? 0),
            ],
        ];
    }
}
