<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShapeSeeder extends Seeder
{
    public function run(): void
    {        
        $handle = fopen(get_storage_path("shapes.txt"), 'r');

        $batch = [];
        $batchSize = 500;

        $skip = true;
        while (($line = fgets($handle)) !== false) {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            $batch[] = [
                "id" => ($item[0] === '' ? 0 : $item[0]),
                "pt_sequence" => ($item[1] === '' ? 0 : $item[1]),
                "pt_lat" => ($item[2] === '' ? 0 : $item[2]),
                "pt_lon" => ($item[3] === '' ? 0 : $item[3]),
                "dist_traveled" => ($item[4] === '' ? 0 : $item[4])
            ];

            if (count($batch) >= $batchSize) {
                DB::table("shapes")->upsert(
                    $batch,
                    ['id', 'pt_sequence'],
                    ['pt_lat', 'pt_lon', 'dist_traveled']
                );
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table("shapes")->upsert(
                $batch,
                ['id', 'pt_sequence'],
                ['pt_lat', 'pt_lon', 'dist_traveled']
            );
        }

        fclose($handle);
    }
}
