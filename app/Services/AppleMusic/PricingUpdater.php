<?php

namespace App\Services\AppleMusic;

use Illuminate\Foundation\Bus\DispatchesJobs;

use App\Jobs\Apple\FetchAndInsertPricingTables;

class PricingUpdater
{
    public function handle(array $urls){
        foreach($urls as $url){
            $job = (new FetchAndInsertPricingTables($url))->onQueue(SHQueue('apple_streamable'));
            dispatch($job);
        }
    }
}
