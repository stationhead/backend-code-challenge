<?php

namespace App\Console\Commands;

use DB;

use Illuminate\Console\Command;
use App\Models\Account;

use App\Jobs\Logs\SendToLoggly;

class VerifyAccountFollowCounts extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:verify_account_follow_counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies that the follow counts in the account table match with the actual counts using a counting query';

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
        $res = DB::select('select count(*) as badCount
                from accounts
                join (SELECT account_id, count(*) AS count
                FROM accounts_follows
                where deleted_at is null
                group by account_id) as correct
                    on correct.account_id = accounts.id
                where count != accounts.following_count');
        $aCount = $res[0]->badCount;

        if($aCount !== 0){
            \Log::error("Following count not in sync!");
            error_log("Following count not in sync!");

            $job = (new SendToLoggly('error', 'Following count not in sync!'))
                ->onQueue(SHQueue('logging'));

            dispatch($job);
        }

        $res = DB::select('select count(*) as badCount
                from accounts
                join (SELECT following_id, count(*) AS count
                FROM accounts_follows
                where deleted_at is null
                group by following_id) as correct
                    on correct.following_id = accounts.id
                where count != accounts.follower_count');
        $bCount = $res[0]->badCount;

        if($bCount !== 0){
            \Log::error("Follower count not in sync!");
            error_log("Follower count not in sync!");

            $job = (new SendToLoggly('error', "Follower count not in sync!"))
                ->onQueue(SHQueue('logging'));

            dispatch($job);
        } 
    }
}
