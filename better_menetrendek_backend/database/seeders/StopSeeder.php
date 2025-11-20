<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StopSeeder extends Seeder
{
    public function run(): void
    {
        sanitize_input("stops.txt");
        
        $handle = fopen(get_storage_path("stops.txt"), 'r');

        $batch = [];
        $batchSize = 500;

        $skip = true;
        while (($line = fgets($handle)) !== false) {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            $batch[] = [
                "id" => $item[0],
                "name" => preg_replace_callback('/"([^"]*)"/', function($matches) {
                    return str_replace(';', ',', $matches[1]);
                }, $item[1]),
                "lat" => $item[2],
                "lon" => $item[3],
                "code" => $item[4],
                "locations_type" => ($item[5] === '' ? 0 : $item[5]),
                "location_sub_type" => ($item[6] === '' ? 0 : $item[6]),
                "parent_station" => ($item[7] === '' ? 0 : $item[7]),
                "wheelchair_borading" => ($item[8] === '' ? 0 : $item[8])
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
