<?php

namespace App\Services\Spotify;

use stdClass;

use App\Services\Spotify\SpotifyFetcher;

use App\Exceptions\InvalidCredentials;

class AuthorizeSpotifyAccount
{
    private $spotifyFetcher;

    public function __construct(SpotifyFetcher $spotifyFetcher)
    {
        $this->spotifyFetcher = $spotifyFetcher;
    }

    public function execute(String $spotifyID, String $spotifyToken): stdClass
    {
        $spotifyAccount = $this->spotifyFetcher->fetchCurrentUser($spotifyToken);

        if ($spotifyAccount->id != $spotifyID) {
            throw new InvalidCredentials("The spotify token passed up is for a different account.");
        }

        return $spotifyAccount;
    }
}
