<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use App\Services\MusicMatchers\Constants;
use stdClass;
use Redis;
use App;

class FloatingArtist implements SearchStrategyInterface
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

        //find the shorter string side
        $leftShorter = true;
        if(count($artist) > count($result_artist)) $leftShorter = false;

        $leftSide = $leftShorter ? $artist : $result_artist;
        $rightSide = $leftShorter ? $result_artist : $artist;

        $leftSide = strtolower($leftSide);
        $rightSide = strtolower($rightSide);

        //left side is the shorter side.  see if it's a substring
        if(strpos($rightSide, $leftSide) === false){
            //no substr found. return
            Redis::sadd("FA-no-substr", "$id -> $leftSide || $rightSide");
            return 0;
        }

        if($track == $result_track){
            return Constants::FLOATING_ARTIST_EXACT_TRACK;
            Redis::sadd("FA-found", "$id -> $leftSide || $rightSide ---- $track");
        }
        return 0;
    }
}
