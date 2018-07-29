<?php

namespace App\Console\Commands;

use Redis;
use Carbon\Carbon;
use Illuminate\Console\Command;

use App\Services\Discover\GenerateTrendingSnapshot;

class CalculateDiscoverTrending extends Command
{
    use WithRedisLock;

    protected $lockExpireTime = 300;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:calculate_discover_trending {--f|force} {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches discover_trending results';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GenerateTrendingSnapshot $calculator)
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
        $debug = function ($text = null, $method = null) {return;};
        if ($this->option("debug")) {
            $debug = function ($text, $method = 'info') {
                $this->$method($text);
            };
        }
        return $this->calculator->execute($debug);
    }
}