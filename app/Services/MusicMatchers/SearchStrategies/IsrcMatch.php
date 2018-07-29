<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use App\Services\MusicMatchers\Constants;
use stdClass;

class IsrcMatch implements SearchStrategyInterface
{
    public function execute(stdClass $leftTrack, stdClass $rightTrack):int{
        $leftIsrc = $leftTrack->isrc;
        $rightIsrc = $rightTrack->isrc;

        if($leftIsrc == $rightIsrc){
            return Constants::PERFECT_ISRC_MATCH;
        }
        return 0;
    }
}
