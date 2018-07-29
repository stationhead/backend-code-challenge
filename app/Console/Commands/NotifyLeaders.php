<?php

namespace App\Console\Commands;

use Redis;
use Carbon\Carbon;
use Illuminate\Console\Command;

use App\Services\Leaderboard\LeaderboardNotifier;

class NotifyLeaders extends Command
{
    use WithRedisLock;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:notify_leaders {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends push notifications to accounts on the leaderboard';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LeaderboardNotifier $notifier)
    {
        parent::__construct();
        $this->notifier = $notifier;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return $this->notifier->execute(
            $this->option("debug") 
            ? function ($text, $method = 'info') {
                $this->$method($text);
            }
            : function($text = null, $method = null) {return;}
        );
    }
}