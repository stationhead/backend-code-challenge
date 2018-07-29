<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\Users\RemoveGhostUsers;

class ClearGhostUsers extends Command
{
    use WithRedisLock;

    protected $lockExpireTime = 300;
    
    /**
     * @var RemoveGhostUsers Service
     */
    protected $removeGhostService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:clear_ghosts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears ghost users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(RemoveGhostUsers $removeGhostService)
    {
        parent::__construct();
        $this->removeGhostService = $removeGhostService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->removeGhostService->execute();
    }
}
