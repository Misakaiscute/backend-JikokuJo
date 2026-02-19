<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\TripRequest;
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

        if (!preg_match('/^\d{8}$/', $date)) 
        {
            return response()->json([
                'errors' => ['Hibás dátum formátum (YYYYMMDD).']
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($time && (!preg_match('/^\d{4}$/', $time) || $time > '2359')) 
        {
            return response()->json([
                'errors' => ['Hibás időformátum (HHMM).']
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $targetMinutes = null;
        if ($time) 
        {
            $hour = (int) substr($time, 0, 2);
            $minute = (int) substr($time, 2, 2);
            $targetMinutes = $hour * 60 + $minute;
        }

        $activeServices = DB::table('calendar_dates')
            ->where('date', $date)
            ->where('exception_type', 1)
            ->pluck('service_id');

        if ($activeServices->isEmpty()) 
        {
            return response()->json([
                'data' => ['trips' => []],
                'errors' => ['Nincs elérhető járat ezen a napon.']
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

            if ($tripsInWindow->isEmpty()) 
            {
                return response()->json([
                    'data' => ['trips' => []],
                    'errors' => ['Nincs elérhető járat ebben az időintervallumban.']
                ], 206, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

                if ($first) 
                {
                    $stops[] = [
                        'id'            => $first->stop_id,
                        'name'          => $first->stop->name ?? 'Ismeretlen megálló',
                        'stop_sequence' => $first->stop_sequence,
                        'arrival_time'  => $first->arrival_time,
                        'headsign'      => $first->stop_headsign ?? null,
                        'location'      => [
                            'lat' => $first->stop->lat ?? null,
                            'lon' => $first->stop->lon ?? null,
                        ],
                    ];
                }

                if ($last && (!$first || $last->stop_sequence !== $first->stop_sequence)) 
                {
                    $stops[] = [
                        'id'            => $last->stop_id,
                        'name'          => $last->stop->name ?? 'Ismeretlen megálló',
                        'stop_sequence' => $last->stop_sequence,
                        'arrival_time'  => $last->arrival_time,
                        'headsign'      => $last->stop_headsign ?? null,
                        'location'      => [
                            'lat' => $last->stop->lat ?? null,
                            'lon' => $last->stop->lon ?? null,
                        ],
                    ];
                }

                $departureMinutes = $first?->departure_time ?? 999999;

                return [
                    'id'                    => $trip->id,
                    'route_id'              => $trip->route_id,
                    'shape_id'              => $trip->shape_id ?? null,
                    'headsign'              => $trip->trip_headsign ?? '',
                    'direction_id'          => (int) ($trip->direction_id ?? 0),
                    'wheelchair_accessible' => (int) ($trip->wheelchair_accessible ?? 0),
                    'bikes_allowed'         => (int) ($trip->bikes_allowed ?? 0),
                    'stops'                 => $stops,
                    '_departure_minutes'    => $departureMinutes,
                ];
            })
            ->sortBy('_departure_minutes')
            ->map(function ($item) {
                unset($item['_departure_minutes']);
                return $item;
            })
            ->values();

        return response()->json([
            'data' => [
                'trips' => $trips,
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function getTripsByStopId(TripRequest $request)
    {
        $date = $request->date ?? Carbon::today()->format('Ymd');
        $time = $request->time ?? Carbon::now()->format('Hi');
        $stopIdsInput = $request->input('ids');

        if (is_null($stopIdsInput)) {
            return response()->json([
                'errors' => ['"ids" paraméter megadása kötelező (tömb vagy vesszővel elválasztott string).']
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (is_string($stopIdsInput)) {
            $stopIdsInput = array_filter(
                array_map('trim', explode(',', $stopIdsInput))
            );
        }

        if (!is_array($stopIdsInput)) {
            $stopIdsInput = [];
        }

        $stopIds = collect($stopIdsInput)
            ->map(fn($id) => trim((string)$id))
            ->filter(fn($id) => $id !== '' && $id !== null)
            ->unique()
            ->values();

        if ($stopIds->isEmpty()) {
            return response()->json([
                'errors' => ['Legalább egy érvényes stop azonosító szükséges az "ids" mezőben.']
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (!preg_match('/^\d{8}$/', $date)) {
            return response()->json([
                'errors' => ['Hibás dátum formátum (YYYYMMDD).']
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if ($time && (!preg_match('/^\d{4}$/', $time) || $time > '2359')) {
            return response()->json([
                'errors' => ['Hibás időformátum (HHMM).']
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $targetMinutes = null;
        if ($time) {
            $hour          = (int) substr($time, 0, 2);
            $minute        = (int) substr($time, 2, 2);
            $targetMinutes = $hour * 60 + $minute;
        }

        $activeServices = DB::table('calendar_dates')
            ->where('date', $date)
            ->where('exception_type', 1)
            ->pluck('service_id');

        if ($activeServices->isEmpty()) {
            return response()->json([
                'data'   => ['trips' => []],
                'errors' => ['Nincs elérhető járat ezen a napon.']
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $tripsQuery = Trip::query()
            ->whereIn('service_id', $activeServices);

        if ($targetMinutes !== null) {
            $windowStart = $targetMinutes;
            $windowEnd   = $targetMinutes + 120;

            $tripsInWindow = DB::table('stop_times')
                ->whereIn('stop_id', $stopIds)
                ->whereBetween('departure_time', [$windowStart, $windowEnd])
                ->distinct()
                ->pluck('trip_id');

            if ($tripsInWindow->isEmpty()) {
                return response()->json([
                    'data'   => ['trips' => []],
                    'errors' => ['Nincs elérhető járat a megadott időintervallumban ezeknél a megállóknál.']
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $tripsQuery->whereIn('id', $tripsInWindow);
        } else {
            $tripsWithStop = DB::table('stop_times')
                ->whereIn('stop_id', $stopIds)
                ->distinct()
                ->pluck('trip_id');

            if ($tripsWithStop->isEmpty()) {
                return response()->json([
                    'data'   => ['trips' => []],
                    'errors' => ['A megadott megálló(k)hoz nem tartozik járat.']
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $tripsQuery->whereIn('id', $tripsWithStop);
        }

        $trips = $tripsQuery
            ->select([
                'id', 'route_id', 'service_id', 'trip_headsign',
                'direction_id', 'shape_id', 'wheelchair_accessible', 'bikes_allowed'
            ])
            ->with([
                'stopTimes' => fn($q) => $q
                    ->select('trip_id', 'stop_id', 'stop_sequence', 'arrival_time', 'departure_time', 'stop_headsign')
                    ->orderBy('stop_sequence'),
                'stopTimes.stop' => fn($q) => $q
                    ->select('id', 'name', 'lat', 'lon', 'code')
            ])
            ->get()
            ->map(function ($trip) use ($stopIds) {
                $timesAtRequestedStops = $trip->stopTimes
                    ->whereIn('stop_id', $stopIds)
                    ->sortBy('stop_sequence');

                $firstAtRequested = $timesAtRequestedStops->first();
                $firstOverall     = $trip->stopTimes->first();
                $lastOverall      = $trip->stopTimes->last();

                $stops = [];

                if ($firstAtRequested) {
                    $stops[] = [
                        'id'            => $firstAtRequested->stop_id,
                        'name'          => $firstAtRequested->stop?->name ?? 'Ismeretlen',
                        'stop_sequence' => $firstAtRequested->stop_sequence,
                        'arrival_time'  => $firstAtRequested->arrival_time,
                        'headsign'      => $firstAtRequested->stop_headsign ?? null,
                        'location'      => [
                            'lat' => $firstAtRequested->stop?->lat,
                            'lon' => $firstAtRequested->stop?->lon,
                        ],
                    ];
                }

                if ($lastOverall && (!$firstAtRequested || $lastOverall->stop_sequence !== $firstAtRequested->stop_sequence)) {
                    $stops[] = [
                        'id'            => $lastOverall->stop_id,
                        'name'          => $lastOverall->stop?->name ?? 'Ismeretlen',
                        'stop_sequence' => $lastOverall->stop_sequence,
                        'arrival_time'  => $lastOverall->arrival_time,
                        'headsign'      => $lastOverall->stop_headsign ?? null,
                        'location'      => [
                            'lat' => $lastOverall->stop?->lat,
                            'lon' => $lastOverall->stop?->lon,
                        ],
                    ];
                }

                $departureMinutes = $firstOverall?->departure_time ?? 999999;

                return [
                    'id'                    => $trip->id,
                    'route_id'              => $trip->route_id,
                    'shape_id'              => $trip->shape_id ?? null,
                    'headsign'              => $trip->trip_headsign ?? '',
                    'direction_id'          => (int) ($trip->direction_id ?? 0),
                    'wheelchair_accessible' => (int) ($trip->wheelchair_accessible ?? 0),
                    'bikes_allowed'         => (int) ($trip->bikes_allowed ?? 0),
                    'stops'                 => $stops,
                    '_departure_minutes'    => $departureMinutes,
                ];
            })
            ->sortBy('_departure_minutes')
            ->map(function ($item) {
                unset($item['_departure_minutes']);
                return $item;
            })
            ->values();

        return response()->json([
            'data'   => ['trips' => $trips],
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
