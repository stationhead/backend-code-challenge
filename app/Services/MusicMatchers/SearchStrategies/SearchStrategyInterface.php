<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use stdClass;

interface SearchStrategyInterface
{
    public function execute(stdClass $leftTrack, stdClass $rightTrack):int;
}
