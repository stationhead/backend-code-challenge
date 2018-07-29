<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\RotationsTrack;

class TrimRotations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:trim_large_rotations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'removes least played tracks from rotations to ensure they are below the threshold';

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
        $limit = config('stationhead.app_data.rotation.track_limit');

        $largeRotations = RotationsTrack::havingRaw("count(*) > {$limit}")
                                        ->groupBy('rotation_id')
                                        ->with(['rotation'])
                                        ->whereNull('deleted_at')
                                        ->get()
                                        ->pluck('rotation');

        if($largeRotations->isEmpty()) return;

        foreach ($largeRotations as $rotation) {
            $numberToWipe = $rotation->rotationsTracks()->count() - $limit;
            
            if($numberToWipe <= 0) continue;

            $tracksToWipe = $rotation->leastPlayedTracks($numberToWipe);
            $this->deleteTracks($rotation, $tracksToWipe);
            $rotation->invalidateCache();
        }
    }

    private function deleteTracks($rotation, $tracks)
    {
        $trackIdChucks = $tracks->pluck('id')->chunk(1000);
        foreach ($trackIdChucks as $chunk) {
            RotationsTrack::where('rotation_id', $rotation->id)->whereIn('track_id', $chunk)->delete();
        }
    }
}
