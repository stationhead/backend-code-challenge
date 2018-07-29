<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\InviteCode;

class ExpireSentInviteCodes extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:expire_sent_invite_codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'expires invite codes sent that were not used';

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
        InviteCode::refreshUnusedSinglesSent();
    }
}
