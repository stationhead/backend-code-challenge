<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateUpdateAllDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:updateAll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs new migrations on all databases';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    public function handle()
    {
        $this->call(
            'migrate',
            [
                '--database' =>  config('stationhead.database.connections.main')
            ]
        );
        $this->call(
            'migrate',
            [
                '--database' => config('stationhead.database.connections.logging'),
                '--path' => 'database/migrations/logs'
            ]
        );
        $this->call(
            'migrate',
            [
                '--database' => config('stationhead.database.connections.logging'),
                '--path' => 'database/migrations/admin'
            ]
        );
        $this->call(
            'migrate',
            [
                '--database' => config('stationhead.database.connections.main_testing')
            ]
        );
        $this->call(
            'migrate',
            [
                '--database' => config('stationhead.database.connections.logging_testing'),
                '--path' => 'database/migrations/logs'
            ]
        );
        $this->call(
            'migrate',
            [
                '--database' => config('stationhead.database.connections.logging_testing'),
                '--path' => 'database/migrations/admin'
            ]
        );
    }
}
