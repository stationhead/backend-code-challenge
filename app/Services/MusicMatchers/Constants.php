<?php

namespace App\Services\MusicMatchers;

class Constants{
    const DURATION_THRESHHOLD_PERCENT =  0.05;

    const PERFECT_ISRC_MATCH = 255;
    const HUMAN_MATCH = 240;

    const ARTIST_TITLE_EXACT_MATCH = 150;
    const REGEX_LIVE = 149;
    const REGEX_REMASTERED = 148;
    const REMOVED_THE = 147;
    const EXACT_ARTIST_ALPHANUM_TRACK = 145;
    const ALPHANUM_ARTIST_EXACT_TRACK = 144;
    const FLOATING_ARTIST_EXACT_TRACK = 140;
    const EXACT_ARTIST_FLOATING_TRACK = 139;

    const ACR_FINGERPRINT_MATCH = 120;
    const ACR_FINGERPRINT_MATCH_SOLO = 110;

    const NULL_APPLE_ID = 0;
    const NULL_SPOTIFY_ID = 0;
}
