<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateFreshAllDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:freshAll {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate refreshes all the databases';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    public function handle()
    {
        $this->call(
            'migrate:fresh',
            [
                '--database' => config('stationhead.database.connections.main'),
                '--seed' => $this->option('seed')
            ]
        );
        $this->call(
            'migrate:fresh',
            [
                '--database' => config('stationhead.database.connections.logging'),
                '--path' => ['database/migrations/logs', 'database/migrations/admin']
            ]
        );
        $this->call(
            'migrate:fresh',
            [
                '--database' => config('stationhead.database.connections.main_testing')
            ]
        );
        $this->call(
            'migrate:fresh',
            [
                '--database' => config('stationhead.database.connections.logging_testing'),
                '--path' => ['database/migrations/logs', 'database/migrations/admin']
            ]
        );
        $this->call(
            'migrate:fresh',
            [
                '--database' => config('stationhead.database.connections.apple'),
                '--path' => ['database/migrations/apple']
            ]
        );
        $this->call(
            'migrate:fresh',
            [
                '--database' => config('stationhead.database.connections.apple_testing'),
                '--path' => ['database/migrations/apple']
            ]
        );
    }
}
