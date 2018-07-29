<?php

namespace App\Services\Spotify;

use App\Models\User;

use App\Services\Spotify\SpotifyFetcher;

class CheckForSpotifyMostListened
{
    public function __construct(SpotifyFetcher $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    public function execute($spotifyToken)
    {
        $tracks = $this->fetcher->fetchUsersTop('tracks', 'short_term', $spotifyToken);

        if (count($tracks) != 0) {
            return true;
        }

        $playlists = $this->fetcher->fetchUsersPlaylists($spotifyToken);

        if (count($playlists) != 0) {
            return true;
        }

        $tracks = $this->fetcher->fetchUsersSavedTracks($spotifyToken);

        if (count($tracks) != 0) {
            return true;
        }

        $albums = $this->fetcher->fetchUsersSavedAlbums($spotifyToken);

        if (count($albums) != 0) {
            return true;
        }

        $artists = $this->fetcher->fetchUsersFollowedArtists($spotifyToken);

        if (count($artists) != 0) {
            return true;
        }

        return false;
    }
}
