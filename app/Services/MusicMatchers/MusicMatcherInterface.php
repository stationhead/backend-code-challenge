<?php

namespace App\Services\MusicMatchers;

use stdClass;

interface MusicMatcherInterface
{
    public function matchTracksIndirectly(array $input):array;
    public function getServiceUriFieldName():string;
    public function getServiceName():string;
    public function getShortName():string;
    public function getConversionRequirements():string;
    public function setSourceFetcher($fetcher);
    public function getFetcher();
}
