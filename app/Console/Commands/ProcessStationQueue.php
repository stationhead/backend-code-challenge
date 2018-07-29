<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\Queues\UpdateAllQueues;

class ProcessStationQueue extends Command
{
    use WithRedisLock;

    protected $lockExpireTime = 300;
    
    /**
     * @var UpdateAllQueuesService
     */
    protected $updateAllQueues;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:process_queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check All Station Queues and Re-Generate.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(UpdateAllQueues $updateAllQueues)
    {
        parent::__construct();
        $this->updateAllQueues = $updateAllQueues;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->updateAllQueues->execute();
    }
}
