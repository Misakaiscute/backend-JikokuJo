<?php

namespace App\Http\Controllers;

use DirectoryIterator;
use Illuminate\Http\Request;
use App\Models\Stop;
use App\Models\Route;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    private array $routeTypes = 
    [
        'busz' => [3, 7, 200, 201, 202, 203, 204, 205, 206, 207, 208, 209, 700, 701, 702, 703, 704, 705, 706, 707, 708, 709, 710, 711, 712, 713, 714, 715, 716],
        'vonat' => [2, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 300],
        'villamos' => [0, 900, 901, 902, 903, 904, 905, 906],
        'metró' => [1, 401, 500],
        'troli' => [11, 800],
        'taxi' => [1500, 1501, 1502, 1503, 1504, 1505, 1506, 1507],
        'hév' => [109, 300],
        'egyéb' => [4, 5, 6, 12, 400, 402, 403, 404, 600, 1000, 1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010, 1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020, 1021,
            1100, 1101, 1102, 1103, 1104, 1105, 1106, 1107, 1108, 1109, 1110, 1111, 1112, 1113, 1114, 1200, 1300, 1301, 1302, 1303, 1304, 1305, 1306, 1307, 1400, 1401, 1402, 1600, 1601, 1602, 1603, 1604, 1605],
    ];


    private function getRouteTypeCategory(int $type): string
    {
        return match (true) 
        {
            in_array($type, $this->routeTypes['busz'] ?? []) => 1,
            in_array($type, $this->routeTypes['villamos'] ?? []) => 2,
            in_array($type, $this->routeTypes['metró'] ?? []) => 3,
            in_array($type, $this->routeTypes['troli'] ?? []) => 4,
            in_array($type, $this->routeTypes['vonat'] ?? []) => 5,
            in_array($type, $this->routeTypes['hév'] ?? []) => 6,
            in_array($type, $this->routeTypes['taxi'] ?? []) => 7,
            default => 8,
        };
    }

    public function queryables()
    {
        $stops = Stop::query()
        ->select(
            'name',
            DB::raw("GROUP_CONCAT(id ORDER BY id ASC SEPARATOR ',') AS temp_ids")
        )
        ->groupBy('name')
        ->get()
        ->map(function ($stop) {
            $ids = [];
            if ($stop->temp_ids !== null && $stop->temp_ids !== '') {
                $ids = explode(',', $stop->temp_ids);
                $ids = array_map('trim', $ids);
                $ids = array_filter($ids, fn($v) => $v !== '');
            }
            return [
                'name' => $stop->name,
                'ids'  => $ids,
            ];
        });

        $routes = Route::select('id', 'short_name', 'color', 'type')
                    ->get()
                    ->map(function ($route) {
                        return [
                            'route_id'         => $route->id,
                            'route_short_name' => $route->short_name,
                            'type'             => $this->getRouteTypeCategory($route->type),
                            'color'            => $route->color,
                        ];
                    });

        if ($stops->isEmpty() && $routes->isEmpty()) 
        {
            return response()->json([
                'data'   => ['stops' => [], 'routes' => []],
                'errors' => ['No data available']
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'data'   => [
                'stops'  => $stops,
                'routes' => $routes
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }    
}
