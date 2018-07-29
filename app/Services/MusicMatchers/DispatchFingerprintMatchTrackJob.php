<?php

namespace App\Services\MusicMatchers;

use App\Jobs\MusicMatching\FingerprintMatchTrack;
use Illuminate\Foundation\Bus\DispatchesJobs;

class DispatchFingerprintMatchTrackJob
{
    public function handle($metadata, $service, $source, $results)
    {
        $job = (new FingerprintMatchTrack($metadata, $service, $source, $results))->onQueue(SHQueue('acr_cloud'));
        dispatch($job);
    }
}
