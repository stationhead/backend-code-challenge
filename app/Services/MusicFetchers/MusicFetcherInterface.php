<?php

namespace App\Services\MusicFetchers;

use stdClass;

interface MusicFetcherInterface
{
    public function fetchTracks($trackIds, $storefront = 'us');
    public function extractDataFromTrack($input):?array;
    public function extractArt($res):?string;
    public function extractPreview($res):?string;
    public function extractTracks($res):array;

    public function getMatcherClass();
}
