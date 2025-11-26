<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RouteSeeder extends Seeder
{
    public function run(): void
    {        
        sanitize_input("routes.txt");

        $handle = fopen(get_storage_path("routes.txt"), 'r');

        $batch = [];
        $batchSize = 1000;

        $skip = true;
        while (($line = fgets($handle)) !== false) {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            $batch[] = [
                "agency_id" => $item[0],
                "id" => $item[1],
                "short_name" => $item[2],
                "long_name" => $item[3],
                "type" => ($item[4] === '' ? 0 : $item[4]),
                "desc" => preg_replace_callback('/"([^"]*)"/', function($matches) {
                    return str_replace(';', ',', $matches[1]);
                }, $item[5]),
                "color" => $item[4],
                "text_color" => $item[4],
                "sort_order" => ($item[4] === '' ? 0 : $item[4]),
            ];

            if (count($batch) >= $batchSize) {
                DB::table("routes")->upsert(
                    $batch,
                    ['id'],
                    ['agency_id', 'short_name', 'long_name', 'type', 'desc', 'color', 'text_color', 'sort_order']
                );
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table("routes")->upsert(
                $batch,
                ['id'],
                ['agency_id', 'short_name', 'long_name', 'type', 'desc', 'color', 'text_color', 'sort_order']
            );
        }

        fclose($handle);
    }
}
