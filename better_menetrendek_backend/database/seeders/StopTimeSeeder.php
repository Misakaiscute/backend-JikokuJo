<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StopTimeSeeder extends Seeder
{
    function minutes($time){
        $time = explode(':', $time);
        return ($time[0]*60) + ($time[1]) + ($time[2]/60);
        }
    public function run(): void
    {
        sanitize_input("stop_times.txt");
        
        $handle = fopen(get_storage_path("stop_times.txt"), 'r');

        $batch = [];
        $batchSize = 50000;

        $skip = true;
        while (($line = fgets($handle)) !== false) {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            $batch[] = [
                "trip_id" => $item[0],
                "stop_id" => $item[1],
                "arrival_time" => self::minutes($item[2]),
                "departure_time" => self::minutes($item[3]),
                "stop_sequence" => $item[4],
                "stop_headsign" => ($item[5] === '' ? 0 : preg_replace_callback('/"([^"]*)"/', function($matches) {
                    return str_replace(';', ',', $matches[1]);
                }, $item[5])),
                "pickup_type" => ($item[6] === '' ? 0 : $item[6]),
                "drop_off_type" => ($item[7] === '' ? 0 : $item[7]),
                "shape_dist_traveled" => $item[8]
            ];

            if (count($batch) >= $batchSize) {
                DB::table("stop_times")->upsert(
                    $batch,
                    ['trip_id', 'stop_id', 'stop_sequence'],
                    ['arrival_time', 'departure_time', 'stop_headsign', 'pickup_type', 'drop_off_type', 'shape_dist_traveled']
                );
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table("stop_times")->upsert(
                $batch,
                ['trip_id', 'stop_id', 'stop_sequence'],
                ['arrival_time', 'departure_time', 'stop_headsign', 'pickup_type', 'drop_off_type', 'shape_dist_traveled']
            );
        }

        fclose($handle);
    }
}
