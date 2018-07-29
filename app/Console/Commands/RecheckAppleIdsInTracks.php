<?php

namespace App\Console\Commands;

use App;
use Redis;
use Illuminate\Console\Command;
use App\Jobs\Apple\FetchAndInsertAppleLibrary;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Models\Track;
use App\Services\TracksScraper\TracksScraper;
use App\Jobs\Apple\ProcessRecheckChunk;

class RecheckAppleIdsInTracks extends Command
{
    const BATCH_SIZE = 300;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:recheck_apple_ids_in_tracks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rechecks apple_uri_id values in tracks table';

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
         Redis::del('AMF-no-search-results');
         Redis::del('AMF-no-matching-result');
         Redis::del("AMF-fetch-from-meta-failed");
         Redis::del("AMF-search-failed");
         Redis::del("TTO-fetcher-failed");
         Redis::del("AMF-search-fallback-failed");
         Redis::del("AMF-fetch-from-meta-failed");

         $tracks = Track::whereNotNull('apple_uri_id')->get();
         $chunks = $tracks->chunk(self::BATCH_SIZE);

         foreach($chunks as $key=>$packet){
             $job = (new ProcessRecheckChunk($packet))->onQueue(SHQueue('apple_track_updater'));
             dispatch($job);
         }
     }
}
