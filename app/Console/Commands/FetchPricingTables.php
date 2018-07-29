<?php

namespace App\Console\Commands;

use App;
use Illuminate\Console\Command;
use App\Services\AppleMusic\LibraryUpdater;
use Illuminate\Foundation\Bus\DispatchesJobs;


class FetchPricingTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:fetch_pricing_tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the latest pricing tables from Apple Feed and inserts into redis';

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
        App::make(LibraryUpdater::class)->updatePricingTables(false);
    }
}
