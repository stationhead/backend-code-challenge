<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\PendingUser;

class ExpireOldPendingUsers extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:expire_old_pending_users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'expires the pending users';

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
        PendingUser::expireRecordsOlderThan(
            Carbon::now()->subDays(config('stationhead.pending_user_expiration_limit'))
        );
    }
}
