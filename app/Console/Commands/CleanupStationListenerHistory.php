<?php

namespace App\Console\Commands;

use App;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\Histories\StationListenerHistory;
use App\Services\Favorites\FavoriteStationsGenerator;

class CleanupStationListenerHistory extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:cleanup_station_listener_history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes Old Station Listener Histories.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FavoriteStationsGenerator $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        StationListenerHistory::removeOldRecords(
            Carbon::now()->subDays(config('stationhead.station_listener_history_limit'))
        );

        $this->generator->execute();
    }
}
