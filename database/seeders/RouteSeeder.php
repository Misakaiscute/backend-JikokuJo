<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RouteSeeder extends Seeder
{
    public function run(): void
    {        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE routes');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        sanitize_input("routes.txt");

        $handle = fopen(get_storage_path("routes.txt"), 'r');

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
                "agency_id" => $item[0],
                "id" => $item[1],
                "short_name" => $item[2],
                "long_name" => $item[3],
                "type" => ($item[4] === '' ? 0 : $item[4]),
                "desc" => (strpos($item[5], ";") === false ? trim($item[5], '"') : switch_commas($item[5], true)),
                "color" => $item[6],
                "text_color" => $item[7],
                "sort_order" => ($item[8] === '' ? 0 : $item[8]),
            ];

            if (count($batch) >= $batchSize) 
            {
                DB::table("routes")->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) 
        {
            DB::table("routes")->insert($batch);
        }

        fclose($handle);
    }
}
