<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StopSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE stops');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        sanitize_input("stops.txt");

        $handle = fopen(get_storage_path("stops.txt"), 'r');

        $batch = [];
        $batchSize = 2000;

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
                "id" => $item[0],
                "name" => (strpos($item[1], ";") === false ? trim($item[1], '"') : switch_commas($item[1], true)),
                "lat" => $item[2],
                "lon" => $item[3],
                "code" => $item[4],
                "location_type" => ($item[5] === '' ? 0 : $item[5]),
                "location_sub_type" => ($item[6] === '' ? 0 : $item[6]),
                "parent_station" => ($item[7] === '' ? 0 : $item[7]),
                "wheelchair_boarding" => ($item[8] === '' ? 0 : $item[8])
            ];

            if (count($batch) >= $batchSize) 
            {
                DB::table("stops")->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) 
        {
            DB::table("stops")->insert($batch);
        }

        fclose($handle);
    }
}
