<?php

namespace App\Services\AppleMusic;

use Carbon\Carbon;

use Jose\Factory\JWKFactory;
use Jose\Factory\JWSFactory;


class AppleMusicTokenGenerator
{
    public function execute(): string
    {
        $key = JWKFactory::createFromKeyFile(
            config("stationhead.apple.music_kit_private_key_path"),
            '',
            [
                'kid' => config("stationhead.apple.music_kit_key_id"),
                'alg' => 'ES256',
                'use' => 'sig',
            ]
        );

        $claims = [
            'iat' => Carbon::now()->timestamp,              // Issued at
            'exp' => Carbon::now()->addSeconds(config('stationhead.apple.api_key_ttl'))->timestamp, // Expires at
            'iss' => config("stationhead.apple.team_id")    // Issuer
        ];

        $jws = JWSFactory::createJWSToCompactJSON(
            $claims,                      // The payload or claims to sign
            $key,                         // The key used to sign
            [                             // Protected headers. Muse contains at least the algorithm
                'alg' => 'ES256',
                'kid' => config("stationhead.apple.music_kit_key_id")
            ]
        );

        return $jws;
    }
}
