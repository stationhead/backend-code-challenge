<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\AccountsFollow;
use App\Models\PushSetting;

class AddMissingPushSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:add_missing_push_settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add push_settings to deleted accounts_follows';

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
        $accountsFollows = AccountsFollow::withTrashed()
            ->with(['pushSetting'])
            ->get();

        $accountsFollows->each(function ($accountsFollow) {
            if (is_null($accountsFollow->pushSetting)) {

                PushSetting::create([
                    'accounts_follow_id' => $accountsFollow->id,
                    'throttle_level' => config("stationhead.throttle.levels.throttled"),
                ]);
            }
        });
    }
}
