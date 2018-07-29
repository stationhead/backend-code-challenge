<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use App\Services\MusicMatchers\Constants;
use stdClass;
use Redis;
use App;

class Utilities
{
    public static function trimAll(stdClass $input):stdClass{
        $input->artist = trim($input->artist);
        $input->track = trim($input->track);
        return $input;
    }

    public static function stripNonAlphaNumeric(string $input):string{
        return preg_replace("/[^A-Za-z0-9 ]/", '', $input);
    }

    public static function condenseWhiteSpace(string $input):string{
        return preg_replace("/ +/", ' ', $input);
    }

    public static function durationCheck(stdClass $left, stdClass $right):bool{
        return ($right->duration > ($left->duration*(1.0-Constants::DURATION_THRESHHOLD_PERCENT))
                    && $right->duration < ($left->duration*(1.0+Constants::DURATION_THRESHHOLD_PERCENT)));
    }

    public static function removeThe($input):string{
        return preg_replace('/^[tT]he /', '', $input);
    }

}
