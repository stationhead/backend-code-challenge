<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use App\Services\MusicMatchers\Constants;
use stdClass;
use Redis;
use App;

class TrackAlphaNumOnlyMatch implements SearchStrategyInterface
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

        //remove non-alphanumeric characters
        $strippedTrack =  Utilities::stripNonAlphaNumeric($track);
        $strippedResultTrack =  Utilities::stripNonAlphaNumeric($result_track);

        //condense whitespace
        $strippedTrack = Utilities::condenseWhiteSpace($strippedTrack);
        $strippedResultTrack = Utilities::condenseWhiteSpace($strippedResultTrack);

        $leftTrack->track = $strippedTrack;
        $rightTrack->track = $strippedResultTrack;

        //feed the modified values to the ExactString search strategy.
        $subMatcher = App::make(ExactString::class);
        if($quality = $subMatcher->execute($leftTrack, $rightTrack)){
            Redis::sadd("ANM-found", "$id -> $track || $result_track ---- $strippedTrack || $strippedResultTrack");
            return Constants::EXACT_ARTIST_ALPHANUM_TRACK;
        }else{
            Redis::sadd("ANM-no_found", "$id -> $track || $result_track ---- $strippedTrack || $strippedResultTrack");
        }
        return 0;
    }
}
