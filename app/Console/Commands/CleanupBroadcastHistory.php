<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\Histories\BroadcastHistory;

class CleanupBroadcastHistory extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:cleanup_broadcast_history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes Old Broadcast Histories.';

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
        BroadcastHistory::removeOldRecords(
            Carbon::now()->subDays(config('stationhead.broadcast_history_limit'))
        );
    }
}
