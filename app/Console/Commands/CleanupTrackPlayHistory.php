<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\Histories\TrackPlayHistory;

class CleanupTrackPlayHistory extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:cleanup_track_play_history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'deletes old track play histories';

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
        TrackPlayHistory::removeOldRecords(
            Carbon::now()->subDays(config('stationhead.track_play_history_limit'))
        );
    }
}
