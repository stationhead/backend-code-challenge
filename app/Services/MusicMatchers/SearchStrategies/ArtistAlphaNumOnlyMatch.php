<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use App\Services\MusicMatchers\Constants;
use stdClass;
use Redis;
use App;

class ArtistAlphaNumOnlyMatch implements SearchStrategyInterface
{
    public function execute(stdClass $leftTrack, stdClass $rightTrack):int{
        $leftTrack = Utilities::trimAll($leftTrack);
        $rightTrack = Utilities::trimAll($rightTrack);

        $artist = $leftTrack->artist;
        $id = $leftTrack->id;
        $result_track = $rightTrack->track;
        $result_artist = $rightTrack->artist;

        if(!Utilities::durationCheck($leftTrack, $rightTrack)){
            return 0;
        }

        //remove non-alphanumeric characters
        $strippedArtist =  Utilities::stripNonAlphaNumeric($leftTrack->artist);
        $strippedResultArtist =  Utilities::stripNonAlphaNumeric($rightTrack->artist);

        //condense whitespace
        $strippedArtist = Utilities::condenseWhiteSpace($strippedArtist);
        $strippedResultArtist = Utilities::condenseWhiteSpace($strippedResultArtist);

        $leftTrack->artist = $strippedArtist;
        $rightTrack->artist = $strippedResultArtist;

        //feed the modified values to the ExactString search strategy.
        $subMatcher = App::make(ExactString::class);
        if($quality = $subMatcher->execute($leftTrack, $rightTrack)){
            Redis::sadd("AANM-found", "$id -> $artist || $result_artist ---- $strippedArtist || $strippedResultArtist");
            return Constants::ALPHANUM_ARTIST_EXACT_TRACK;
        }else{
            Redis::sadd("AANM-no_found", "$id -> $artist || $result_artist ---- $strippedArtist || $strippedResultArtist");
        }
        return 0;
    }
}
