<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PathwaySeeder extends Seeder
{
    public function run(): void
    {        
        sanitize_input("pathways.txt");

        $handle = fopen(get_storage_path("pathways.txt"), 'r');

        $batch = [];
        $batchSize = 1000;

        $skip = true;
        while (($line = fgets($handle, 65536)) !== false) {
            if($skip) { $skip = false; continue; }        
            $item = explode(",", trim($line));
        
            if (count($item) < 9) {
                continue;
            }

            $batch[] = [
                "id" => $item[0],
                "mode" => $item[1],
                "is_bidirectional" => $item[2],
                "from_stop_id" => $item[3],
                "to_stop_id" => $item[4],
                "traversal_time" =>$item[5]
            ];

            if (count($batch) >= $batchSize) {
                DB::table("pathways")->upsert(
                    $batch,
                    ['id'],
                    ['mode', 'is_bidirectional', 'from_stop_id', 'to_stop_id', 'traversal_time']
                );
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table("pathways")->upsert(
                $batch,
                ['id'],
                ['mode', 'is_bidirectional', 'from_stop_id', 'to_stop_id', 'traversal_time']
            );
        }

        fclose($handle);
    }
}
