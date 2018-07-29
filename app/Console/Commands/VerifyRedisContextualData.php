<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App;

use App\Services\ContextualData\ContextualDataFollowing;
use App\Services\ContextualData\ContextualDataBlocking;
use App\Services\ContextualData\ContextualDataFollowedBy;
use App\Services\ContextualData\ContextualDataBroadcastInviteBlocking;

class VerifyRedisContextualData extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:verify_contextual_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'verifies contextual data in redis';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->providers = [
            App::makeWith(ContextualDataFollowing::class, ['verify' => true]),
            App::makeWith(ContextualDataBlocking::class, ['verify' => true]),
            App::makeWith(ContextualDataFollowedBy::class, ['verify' => true]),
            App::makeWith(ContextualDataBroadcastInviteBlocking::class, ['verify' => true]),
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach($this->providers as $provider)
        {
            $provider->regenerate();
        }
    }
}
