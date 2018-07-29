<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\Histories\StationShareHistory;

class CleanupStationShareHistory extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:cleanup_station_share_history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes Old Station Share Histories.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        StationShareHistory::removeOldRecords(
            Carbon::now()->subDays(configOrFail('stationhead.station_share_history_limit'))
        );
    }
}
