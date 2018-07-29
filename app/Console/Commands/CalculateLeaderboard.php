<?php

namespace App\Console\Commands;

use Redis;
use Carbon\Carbon;
use Illuminate\Console\Command;

use App\Services\Leaderboard\CalculateAllLeaderboards;

class CalculateLeaderboard extends Command
{
    use WithRedisLock;

    protected $lockExpireTime = 300;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:calculate_leaderboard {--f|force} {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the leaderboards';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CalculateAllLeaderboards $calculator)
    {
        parent::__construct();
        $this->calculator = $calculator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return $this->calculator->execute(
            $this->option("force"),
            $this->option("debug") 
            ? function ($text, $method = 'info') {
                $this->$method($text);
            }
            : function($text = null, $method = null) {return;}
        );
    }
}