<?php
namespace App\Services\Spotify\SearchStrategies;

use App\Services\MusicMatchers\SearchStrategies\RegexMatch as BaseRegexMatch;
use App\Services\MusicMatchers\Constants;

class RegexMatch extends BaseRegexMatch
{
    public function filters():array{
        return [
            [
                'right' => [
                    '/(.*) - Live/',
                ],
                'left' => [
                    '/(.*) \(Live\)/',
                ],
                'quality' => Constants::REGEX_LIVE
            ],
            [
                'right' => [
                    '/(.*) - Remastered/',
                    '/(.*) - Remaster/',
                    '/(.*) - \d+ Remastered Version/',
                    '/(.*) - \d+ Remastered/',
                    '/(.*) \(Re-Recorded\) \[Remastered\]/'
                ],
                'left' => [
                    '/(.*) \(Remastered\)/',
                    '/(.*) \(Remaster\)/',
                    '/(.*) \(\d+ Remastered Version\)/',
                    '/(.*) \(\d+ Remastered\)/',
                    '/(.*) \(Re-Recorded \/ Remastered\)/',
                    '/(.*) \[Remastered\]/',
                    '/(.*) \[\d+ Remastered\]/'
                ],
                'quality' => Constants::REGEX_REMASTERED
            ],
        ];
    }
}
