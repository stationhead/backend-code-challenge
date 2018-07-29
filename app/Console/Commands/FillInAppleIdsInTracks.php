<?php

namespace App\Console\Commands;

use App;
use Redis;
use Illuminate\Console\Command;
use App\Jobs\Apple\FetchAndInsertAppleLibrary;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Models\Track;
use App\Services\TracksScraper\TracksScraper;

class FillInAppleIdsInTracks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:fill_in_apple_ids_in_tracks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fills in apple_uri_id null values in tracks table';

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
        // Redis::del("found");
        Redis::del('AMF-no-search-results');
        Redis::del('AMF-no-matching-result');
        Redis::del("AMF-fetch-from-meta-failed");
        Redis::del("AMF-search-failed");
        Redis::del("TTO-fetcher-failed");
        Redis::del("AMF-search-fallback-failed");
        Redis::del("AMF-fetch-from-meta-failed");

        $ts = App::make(TracksScraper::class);

        $tracks = Track::whereNull('apple_uri_id')->get()->all();
        $chunks = array_chunk($tracks, 20, true);
        $total = count($chunks);
        $time = time();
        foreach($chunks as $key=>$packet){
            //get a new instance so token doesn't expire mid-command
            if($key % 100 == 0) $ts = App::make(TracksScraper::class);

            $input = array_map(function($in){
                return [
                    'id' => $in->spotify_uri_id,
                    'isrc' => $in->isrc
                ];
            }, $packet);

            $res = $ts->execute($input, 'Spotify', true);
            $endTime = time();
            $duration = time() - $time;
            error_log("completed chunk $key of $total -- $duration sec");
            $time = $endTime;
        }
    }
}
