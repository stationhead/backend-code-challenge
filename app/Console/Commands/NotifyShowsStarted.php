<?php

namespace App\Console\Commands;

use Redis;
use App;
use Carbon\Carbon;
use Illuminate\Console\Command;

use App\Models\Show;
use App\Jobs\Push\PushShowOnNow;

class NotifyShowsStarted extends Command
{
    use WithRedisLock;

    protected $signature = 'stationhead:notify_shows_started {--debug} {--force}';

    protected $description = 'Sends push notifications to subscribers of shows that have started since this command was last run';

    public function __construct(PushShowOnNow $job)
    {
        parent::__construct();
        $this->job = $job;
    }

    public function handle()
    {
        $shows = Show::select("shows.*")->notExpired()->notStarted()
            ->join('stations', "shows.station_id", "stations.id")
            ->join('broadcasts', function ($join) {
                $join->on('broadcasts.station_id', 'stations.id');
                $join->on('broadcasts.created_at', "<", 'shows.time' );
            })
            ->orderBy("shows.time", "asc")
            ->get();

            if ($this->option("debug")) { 
            $this->info("Sending notifications for {$shows->count()} shows.");
        }

        foreach ($shows as $show) {
            $job = App::makeWith(get_class($this->job), ["show" => $show])->onQueue(SHQueue('push_events'));
            dispatch($job);

            $show->start();

            if ($this->option("debug")) { 
                $this->info("Show on {$show->station->owner->handle}: \"{$show->desciption}\" at {$show->time}).  Pushing to accounts: {$job->targetAccounts()->implode("handle", ", ")}.");
            }
        }
    }
}