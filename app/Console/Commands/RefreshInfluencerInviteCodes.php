<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\InviteCode;

class RefreshInfluencerInviteCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:refresh_influencer_invite_codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes all influencer codes to the config limit';

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
        InviteCode::refreshInfluencerInviteCodes();
    }
}
