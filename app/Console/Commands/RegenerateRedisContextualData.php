<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\ContextualData\ContextualDataFollowing;
use App\Services\ContextualData\ContextualDataBlocking;
use App\Services\ContextualData\ContextualDataFollowedBy;
use App\Services\ContextualData\ContextualDataBroadcastInviteBlocking;

class RegenerateRedisContextualData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:regenerate_contextual_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'regenerate contextual data in redis';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ContextualDataFollowing $following,
        ContextualDataBlocking $blocking,
        ContextualDataBroadcastInviteBlocking $broadcastInviteBlocking,
        ContextualDataFollowedBy $followed
    )
    {
        parent::__construct();
        $this->providers = [
            $following,
            $blocking,
            $broadcastInviteBlocking,
            $followed
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
