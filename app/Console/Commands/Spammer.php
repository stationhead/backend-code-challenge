<?php

namespace App\Console\Commands;

use DB, Config;
// use Event, EventBase;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use App\Models\Account;
use App\Models\Station;

class Spammer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:spammer {station_id} {min_accounts} {floating_accounts} {loop_microsec=500000} {timeout_min=15}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulates heavy load on a station by creating dummy accounts and dummy activity';

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
        $env = config('stationhead.environment');
        if($env !== 'staging1' && $env !== 'local')
        {
            error_log("This cannot be used on production. Bye Bye");
            return;
        }

        //handle ctrl-c
        declare(ticks = 1);
        pcntl_signal(SIGTERM, function($signal){
            $this->deleteAccounts($this->allAccounts);
            exit;
        });
        pcntl_signal(SIGINT, function($signal){
            $this->deleteAccounts($this->allAccounts);
            exit;   
        });

        $this->station = Station::find($this->argument('station_id'));

        $min = intval($this->argument('min_accounts'));
        $floating = intval($this->argument('floating_accounts'));
        $delay = intval($this->argument('loop_microsec'));

        error_log('creating accounts');
        $fixedAccounts = $this->createAccounts($min);
        $floatingAccounts = $this->createAccounts($floating)->keyBy('id');
        $this->allAccounts = $fixedAccounts->merge($floatingAccounts);

        $parted = collect();
        $floatingAccounts->each(function($el) use($parted){
            $parted->put($el->id, $el->id);
        });
        $joined = collect();

        error_log("joining {$fixedAccounts->count()} fixed accounts");
        $this->rampUp($fixedAccounts);

        $half = (int)($floatingAccounts->count()/2);
        if($half > 0)
        {
            error_log("joining {$half} floating accounts");
            $this->rampUp($floatingAccounts->take($half));
        }

        error_log("starting primary loop with {$floatingAccounts->count()} floating accounts");
        error_log("{$this->argument('timeout_min')} minutes timeout set");
        $timeout =  $this->argument('timeout_min') * 60 + time();
        while(time() < $timeout)
        {
            $choice = mt_rand(1,100);

            if($choice <= 25)
            {
                // join station
                if($parted->isEmpty()) continue;

                $id = $parted->random();
                $parted->forget($id);
                $joined->put($id, $id);
                $account = $floatingAccounts->get(intval($id));
                $account->joinStation($this->station);
            } else if($choice > 25 && $choice <= 75) {
                // leave station
                if($joined->isEmpty()) continue;

                $id = $joined->random();
                $account = $floatingAccounts->get(intval($id));
                $joined->forget($id);
                $parted->put($id, $id);
                $account->exitStation();
            } else if($choice > 75) {
                // say msg
                if($joined->isEmpty()) continue;

                $id = $joined->random();
                $account = $floatingAccounts->get(intval($id));
                $account->addChat(uniqid());
            }

            usleep($delay);
        }
        error_log('Timeout reached, exiting');
        $this->deleteAccounts($this->allAccounts);
     }

     //gradually join the stations
     private function rampUp(Collection $accounts)
     {
        $accounts->each(function($el){
            $el->joinStation($this->station);
            usleep(200000);  // 0.2 seconds
        });
     }

     private function createAccounts($count):Collection
     {
        $handles = [];
        for ($i=0; $i < $count; $i++) { 
            array_push($handles, ['handle' => strtoupper(uniqid('BOT'))]);
        }

        $res = DB::table('accounts')->insert($handles);
        $accounts = Account::whereIn('handle', $handles)->get();
        return $accounts;
     }


     private function deleteAccounts(Collection $accounts)
     {
        error_log('Cleaning up. Please wait...');
        $accounts->each(function($el){
            $el->exitStation();
            $el->forceDelete();
        });
     }
}


