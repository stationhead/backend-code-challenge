<?php

namespace App\Services;

use App\Services\AppleMusic\AppleMusicFetcher;
use App\Services\Spotify\SpotifyFetcher;

use Illuminate\Support\Collection;

class MetadataFetcher
{
    /*
     * structure of output from execute():
     *
     * [
     *      track_id => [
     *          spotify => [
     *              track =>
     *              artist =>
     *              album =>
     *              source_id =>
     *              isrc =>
     *              track_id =>
     *              sourceService =>
     *              duration =>
     *          ],
     *          apple => [
     *              ...
     *          ]
     *      ],
     *      track_id_2 => [
     *          ...
     *      ]
     * ]
     */

    /*
     * $params currently optionally accepts two keys:
     *
     *  'request' : output of Request()->alL()
     *  'service' : the request's service header
     */

    public function execute(Collection $tracks):array
    {
        // create basic structure of output array
        $out = [];
        foreach ($tracks as $track) {
            $out[$track->id] = [
                'spotify' => [],
                'apple' => [],
            ];
        }

        //Parse medata passed down from frontend:

        $service = ['Spotify' => 'spotify', 'AppleMusic' => 'apple'][Request::header("Service")];

        if ($metadata = Request::input("metadata", false)) {
          $keyed = collect($metadata)->keyBy('id');
          $fieldName = $service . '_uri_id';
          foreach ($tracks as $track) {
            if ($entry = $metadata->get($track->$fieldName)) {
              $entry['sourceService'] = $service;
              $entry['duration'] = $track->duration;

              Cache::put("track:{$service}_{$trackID}", $entry, 10080);

              $out[$track->id][$service] = $meta;
            }
            }
          }
        }

        //TODO: Fill metadata from cache if available.

        //Fetch remaining metadat from APIs

        $missingAppleIds = [];
        $appleTracks = $tracks->keyBy('id');
        $metadata = clone($out);
        foreach ($metadata as $trackID => $meta) {
            $track = $appleTracks->get($trackID);
            $info = $meta["apple"];
            //skip if the track isn't available on the service
            if(is_null($track->apple_uri_id)) continue;
            if(empty($info))
            {
                $missingAppleIds[$track->apple_uri_id] = $track->id;
            }
          }

        if(!empty($missingAppleIds)) {
          $metadataFromApple = (new AppleMusicFetcher)->fetchTracksMetadata(array_keys($missingAppleIds));

          foreach ($metadataFromApple as $entry) {
              $trackID = $missingAppleIds[$entry['id']];
              $entry['source_id'] = $entry['id'];
              unset($entry['id']);
              unset($entry['preview']);
              unset($entry['source_albumId']);
              $entry['track_id'] = $trackID;

              //insert into cache
              Cache::put("track:apple_{$trackID}", $entry, 10080);

              $out[$trackID]["apple"] = $entry;
          }
        }

        $missingSpotifyIds = [];
        $spotifyTracks = $tracks->keyBy('id');
        $metadata = clone($out);
        foreach ($metadata as $trackID => $meta) {
            $track = $spotifyTracks->get($trackID);
            $info = $meta["spotify"];
            //skip if the track isn't available on the service
            if(is_null($track->spotify_uri_id)) continue;
            if(empty($info))
            {
                $missingSpotifyIds[$track->apple_uri_id] = $track->id;
            }
          }

        if(!empty($missingSpotifyIds)) {
          $metadataFromSpotify = (new SpotifyMusicFetcher)->fetchTracksMetadata(array_keys($missingSpotifyIds));

          foreach ($metadataFromSpotify as $entry) {
              $trackID = $missingSpotifyIds[$entry['id']];
              $entry['source_id'] = $entry['id'];
              unset($entry['id']);
              unset($entry['preview']);
              unset($entry['source_albumId']);
              $entry['track_id'] = $trackID;

              //insert into cache
              Cache::put("track:spotify_{$trackID}", $entry, 10080);

              $out[$trackID]["spotify"] = $entry;
          }
        }
        return $out;
    }
}
