<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\Histories\TrackBiteHistory;

class CleanupTrackBiteHistory extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:cleanup_track_bite_history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes Old Track Bite Histories.';

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
        TrackBiteHistory::removeOldRecords(
            Carbon::now()->subDays(config('stationhead.track_bite_history_limit'))
        );
    }
}
