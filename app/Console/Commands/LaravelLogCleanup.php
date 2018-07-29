<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use File;

class LaravelLogCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:log_cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Empties the bloated Laravel Logs.';

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
        File::delete(File::glob(storage_path('logs/*.log')));
    }
}
