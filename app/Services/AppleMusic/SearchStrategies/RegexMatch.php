<?php
namespace App\Services\AppleMusic\SearchStrategies;

use App\Services\MusicMatchers\SearchStrategies\RegexMatch as BaseRegexMatch;
use App\Services\MusicMatchers\Constants;

class RegexMatch extends BaseRegexMatch
{
    public function filters():array{
        return [
            [
                'left' => [
                    '/(.*) - Live/',
                ],
                'right' => [
                    '/(.*) \(Live\)/',
                ],
                'quality' => Constants::REGEX_LIVE
            ],
            [
                'left' => [
                    '/(.*) - Remastered/',
                    '/(.*) - Remaster/',
                    '/(.*) - \d+ Remastered Version/',
                    '/(.*) - \d+ Remastered/',
                    '/(.*) \(Re-Recorded\) \[Remastered\]/'
                ],
                'right' => [
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
