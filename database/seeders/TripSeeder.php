<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class TripSeeder extends Seeder
{

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE trips');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        sanitize_input("trips.txt");

        $handle = fopen(get_storage_path("trips.txt"), 'r');

        $batch = [];
        $batchSize = 3000;

        $skip = true;
        while (($line = fgets($handle, 65536)) !== false) 
        {
            if($skip) { $skip = false; continue; }
                
            $item = explode(",", trim($line));

            if (count($item) < 9) 
            {
                continue;
            }

            $batch[] = 
            [
                "route_id" => $item[0],
                "id" => $item[1],
                "service_id" => $item[2],
                "trip_headsign" => (strpos($item[3], ";") === false ? trim($item[3], '"') : switch_commas($item[3], true)),
                "direction_id" => $item[4],
                "block_id" => $item[5],
                "shape_id" => $item[6],
                "wheelchair_accessible" => ($item[7] === '' ? 0 : $item[7]),
                "bikes_allowed" => ($item[8] === '' ? 0 : $item[8])
            ];

            if (count($batch) >= $batchSize) 
            {
                DB::table("trips")->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) 
        {
            DB::table("trips")->insert($batch);
        }

        fclose($handle);
    }
}
