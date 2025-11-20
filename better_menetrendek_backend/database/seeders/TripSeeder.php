<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class TripSeeder extends Seeder
{

    public function run(): void
    {
        sanitize_input("trips.txt");
        
        $handle = fopen(get_storage_path("trips.txt"), 'r');

        $batch = [];
        $batchSize = 500;

        $skip = true;
        while (($line = fgets($handle)) !== false) {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            $batch[] = [
                "route_id" => $item[0],
                "id" => $item[1],
                "service_id" => $item[2],
                "trip_headsign" => preg_replace_callback('/"([^"]*)"/', function($matches) {
                    return str_replace(';', ',', $matches[1]);
                }, $item[3]),
                "direction_id" => $item[4],
                "block_id" => $item[5],
                "shape_id" => $item[6],
                "wheelchair_accessible" => ($item[7] === '' ? 0 : $item[7]),
                "bikes_allowed" => ($item[8] === '' ? 0 : $item[8])
            ];

            if (count($batch) >= $batchSize) {
                DB::table("trips")->updateOrInsert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table("trips")->updateOrInsert($batch);
        }

        fclose($handle);
    }
}
