<?php

namespace App\Console\Commands;

use DB;
use Carbon\Carbon;

use Illuminate\Console\Command;

class CleanupExpiredShows extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:cleanup_expired_shows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes Expired Shows.';

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
     * @return void
     */
    public function handle()
    {
        $now = Carbon::now()->toDateTimeString();
        $query = "
UPDATE shows
INNER JOIN stations 
	ON shows.station_id = stations.id 
LEFT JOIN broadcasts 
	ON broadcasts.station_id = stations.id 
SET shows.deleted_at = '{$now}', shows.updated_at = '{$now}' 
WHERE expires_at < '{$now}' 
	AND broadcasts.id IS NULL 
	AND shows.deleted_at IS NULL
        ";
        DB::update($query);
    }
}
