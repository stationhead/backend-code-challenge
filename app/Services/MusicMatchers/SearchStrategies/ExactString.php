<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use App\Services\MusicMatchers\Constants;
use stdClass;
use Redis;

class ExactString implements SearchStrategyInterface
{
    public function execute(stdClass $leftTrack, stdClass $rightTrack):int{
        $leftTrack = Utilities::trimAll($leftTrack);
        $rightTrack = Utilities::trimAll($rightTrack);

        $artist = $leftTrack->artist;
        $track = $leftTrack->track;
        $id = $leftTrack->id;
        $duration = $leftTrack->duration;

        $result_track = $rightTrack->track;
        $result_artist = $rightTrack->artist;
        $result_duration = $rightTrack->duration;

        if(!Utilities::durationCheck($leftTrack, $rightTrack)){
            return 0;
        }

        if(strcasecmp($result_track, $track) == 0 &&
            strcasecmp($result_artist, $artist) == 0
        ){
            Redis::sadd('found', "$id --> $artist || $result_artist  ---- $track || $result_track");
            return Constants::ARTIST_TITLE_EXACT_MATCH;
        }else{
            Redis::sadd('no_found', "$id --> $artist || $result_artist  ---- $track || $result_track  ---- $duration || $result_duration");
            return 0;
        }
    }
}
