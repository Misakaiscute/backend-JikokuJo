<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $raw_sql = file_get_contents("database\seeders\statements.sql");
        $sql_statements = explode(";",$raw_sql);

        foreach($sql_statements as $sql_statement)
        {
            if(!empty($sql_statement))
            {
                DB::statement(trim($sql_statement). ";");
            }
        }
        self::populate_database();
    }

    public static function populate_database()
    {
        $files = glob('database/seeders/data/*');
        foreach($files as $file)
        {
            if(is_file($file)) 
            {
                unlink($file);
            }
        }
        $dir = database_path('seeders/data');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $target = database_path('seeders/data/budapest_gtfs.zip');
        $in = fopen("https://bkk.hu/gtfs/budapest_gtfs.zip", 'rb');
        $out = fopen($target, 'wb');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
        sleep(5);

        $zip = new \ZipArchive;
        if ($zip->open(database_path("seeders/data") .'/budapest_gtfs.zip') === TRUE) 
        {
            $zip->extractTo(database_path("seeders/data"));
            $zip->close();
        } else {
            echo 'failed';
        }
        
    }
}
