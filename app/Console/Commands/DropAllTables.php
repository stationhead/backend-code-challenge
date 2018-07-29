<?php

namespace App\Console\Commands;

use DB;

use Illuminate\Console\Command;

class DropAllTables extends Command
{
    /**
    * The name and signature of the console command.
    *
    * @var string
    */
    protected $signature = 'migrate:dropAll';

    /**
    * The console command description.
    *
    * @var string
    */
    protected $description = 'Drops All tables from the database';

    /**
    * Create a new command instance.
    *
    * @return void
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Execute the console command.
    *
    * @return mixed
    */
    public function handle()
    {

        if (!$this->confirm('CONFIRM DROP ALL TABLES IN THE CURRENT DATABASE? [Y|N]')) {
             exit('Drop Tables command aborted');
        }

        $databases = [
            env("DB_DATABASE") => 'mysql',
            env("DB_DATABASE_LOGS") => 'mysql_logging',
            env("DB_DATABASE_TEST") => 'mysql_testing',
            env("DB_DATABASE_LOGS_TEST") => 'mysql_logging_testing',
            env("DB_DATABASE_APPLE") => 'mysql_apple',
            env("DB_DATABASE_APPLE_TEST") => 'mysql_apple_testing',
        ];

        foreach($databases as $database => $databaseName) {
            $colname = 'Tables_in_' . $database;
            DB::setDefaultConnection($databaseName);
            $tables = DB::select('SHOW TABLES');

            $droplist = [];

            foreach($tables as $table) {
                $droplist[] = $table->$colname;
            }

            $droplist = implode(',', $droplist);

            if ($droplist == '') continue;

            DB::beginTransaction();
            //turn off referential integrity
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            DB::statement("DROP TABLE $droplist");
            //turn referential integrity back on
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            DB::commit();
            $droplist = [];
        }


        $this->comment(PHP_EOL."If no errors showed up, all tables were dropped".PHP_EOL);

    }
}
