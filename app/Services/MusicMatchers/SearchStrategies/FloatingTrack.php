<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use App\Services\MusicMatchers\Constants;
use stdClass;
use Redis;
use App;

class FloatingTrack implements SearchStrategyInterface
{
    public function execute(stdClass $leftTrack, stdClass $rightTrack):int{
        $artist = trim($leftTrack->artist);
        $track = trim($leftTrack->track);
        $id = $leftTrack->id;
        $duration = $leftTrack->duration;

        $result_track = trim($rightTrack->track);
        $result_artist = trim($rightTrack->artist);
        $result_duration = $rightTrack->duration;

        if(!Utilities::durationCheck($leftTrack, $rightTrack)){
            return 0;
        }

        //find the shorter string side
        $leftShorter = true;
        if(count($track) > count($result_track)) $leftShorter = false;

        $leftSide = $leftShorter ? $track : $result_track;
        $rightSide = $leftShorter ? $result_track : $track;

        $leftSide = strtolower($leftSide);
        $rightSide = strtolower($rightSide);

        //left side is the shorter side.  see if it's a substring
        if($leftSide == '' || $rightSide == ''){
            return 0;
        }
        if(strpos($rightSide, $leftSide) === false){
            //no substr found. return
            Redis::sadd("FT-no-substr", "$id -> $leftSide || $rightSide");
            return 0;
        }

        if($artist == $result_artist){
            Redis::sadd("FT-found", "$id -> $artist ---- $leftSide || $rightSide");
            return Constants::EXACT_ARTIST_FLOATING_TRACK;
        }
        return 0;
    }
}
