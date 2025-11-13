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
        
    }
}
