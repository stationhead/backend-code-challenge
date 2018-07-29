<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Account;
use App\Jobs\Push\PushGodPush;

class GodPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:god_push {--handle=} {--message=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push to all devices';

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
        $handle = $this->option('handle');
        $message = $this->option('message');

        if (is_null($handle) || is_null($message)) {
            $this->warn('command requires both handle and message in order to proceed.');
            return;
        }

        $account = Account::where('handle', $handle)->first();

        if (is_null($account)) {
            $this->warn("{$handle} is not a real account.");
            return;
        }

        if ($this->confirm("Are you sure you send {$message} to all users linking to account {$handle}? [yes|no]")) {
            $job = (new PushGodPush($account, $message))
                    ->onQueue(SHQueue('push_events'));

            dispatch($job);
            $this->info("Push dispatched! Success!");
        } else {
            $this->info("No selected, please start over if you want to initiate a god push.");
        }
    }
}
