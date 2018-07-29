<?php

namespace App\Console\Commands;

use App;
use Redis;
use Illuminate\Console\Command;
use App\Jobs\Apple\FetchAndInsertAppleLibrary;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Models\Track;
use App\Services\TracksScraper\TracksScraper;

use App\Services\AppleMusic\AppleMusicFetcher;

class VerifyStreamable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:verify_apple_ids_for_streamable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'queries the apple api for every apple_uri_id and verifies theyre streamable';

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
        $tracks = Track::whereNotNull('apple_uri_id')->get();
        $chunks = $tracks->chunk(20);
        $tracks = $tracks->keyBy('apple_uri_id');

        $fetcher = App::make(AppleMusicFetcher::class);

        $toWipe = collect();
        $count =$chunks->count();

        foreach ($chunks as $key => $chunk) {
            $found = $toWipe->count();
            error_log("Chunk {$key} of {$count} -- found {$found}");

            $allIds = $chunk->pluck('apple_uri_id')->all();
            $res = $fetcher->fetchTracks($allIds);

            $filtered = array_filter($res, function($el) {
                return !property_exists($el->attributes, 'playParams');
            });
            $badIds = array_column($filtered, 'id');

            if(count($filtered) > 0) { 
                $diff = array_diff($allIds, $badIds);
                if(count($diff) + count($badIds) !== count($allIds))
                {
                    error_log('we have a missing id');
                    \Log::error('Mising id found in verify streamable console command: '.var_export($allIds, true));
                    die();
                }
                foreach ($badIds as $id) {
                    $toWipe->push($id);
                }
                Redis::sadd('nonstreamable_bad_found', ...$badIds);
                // error_log(var_export($toWipe, true));
            }
        }

        $total = count($toWipe);
        error_log("found {$total} incorrect apple ids");

        foreach($toWipe as $badId)
        {
            $track = $tracks->get($badId);
            Redis::sadd('nulled_apple_uri_id_track_ids', $track->id);
            $track->update(['apple_uri_id' => null]);
        }
    }
}

//started 5:10