<?php

namespace App\Services\Spotify;

class SpotifyGetter
{
    public function getPrimaryArtistURIsFromAlbums($albums)
    {
        $primaryArtistURIs = array_map(function($album) {
            return $this->getPrimaryArtistURIFromAlbum($album);
        }, $albums);

        return array_values(array_unique($primaryArtistURIs));
    }

    public function getPrimaryArtistURIFromAlbum($album)
    {
        $count = [];

        foreach($album->tracks->items as $track) {
            foreach($track->artists as $artist) {
                isset($count[$artist->id]) ? $count[$artist->id]++ : $count[$artist->id] = 1;
            }
        }

        return array_keys($count, max($count))[0];
    }

    public function getArtistURIsFromObjects($objects)
    {
        $artistURIs = array_flatten(array_map(function($object) {
            return $this->getArtistURIsFromObject($object);
        }, $objects));

        return array_values(array_unique($artistURIs));
    }

    public function getArtistURIsFromObject($object)
    {
        return array_map(function($artist) {
            return $artist->id;
        }, $object->artists);
    }

    public function getAlbumURIsFromTracks($tracks)
    {
        $albumURIs = array_map(function($track) {
            return $track->album->id;
        }, $tracks);

        return array_values(array_unique($albumURIs));
    }

    public function getTrackURIsFromAlbums($albums)
    {
        $trackURIs = array_flatten(array_map(function($album) {
            return $this->getTrackURIsFromAlbum($album);
        }, $albums));

        return array_values(array_unique($trackURIs));
    }

    public function getTrackURIsFromAlbum($album)
    {
        return array_map(function($track) {
            return $track->id;
        }, $album->tracks->items);
    }

    public function getTrackURIsFromPlaylist($playlist)
    {
        return array_filter(array_map(function($track) {
            return $track->track->id;
        }, $playlist->tracks->items));
    }

    public function getAlbumURIsFromPlaypluck($playlists)
    {
        $albumURIs = array_flatten(array_map(function($playlist) {
            return $this->getAlbumURIsFromPlaylist($playlist);
        }, $playlists));

        return array_values(array_unique($albumURIs));
    }

    public function getAlbumURIsFromPLaylist($playlist)
    {
        $albumURIs = array_map(function($track) {
            return $track->track->album->id;
        }, $playlist->tracks->items);

        return array_values(array_unique($albumURIs));
    }

    public function getPlaylistsData($playlists)
    {
        return array_map(function($playlist) {
            return ['playlistId' => $playlist->id, 'ownerId' => $playlist->owner->id];
        }, $playlists);
    }

    public function getRelatedArtistURIsFromArtist($artist)
    {
        return array_map(function($relatedArtist) {
            return $relatedArtist->id;
        }, $artist->related_artists);
    }

    public function getIdsFrom($objects)
    {
        return array_map(function($object) {
            return $object->id;
        }, $objects);
    }
}
