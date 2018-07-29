<?php

namespace App\Services\Spotify;

use stdClass;
use App\Services\MusicFetchers\MusicFetcherInterface;
use Config;
use Exception;
use Cache;
use App;

use App\Models\User;
use App\Models\SpotifyAccount;
use App\Services\Spotify\SpotifySearchMatcher;

use GuzzleHttp\Client;

class SpotifyFetcher implements MusicFetcherInterface
{
    const ARTIST_API_LIMIT = 50;
    const TRACK_API_LIMIT = 50;
    const ALBUM_API_LIMIT = 20;
    const AUDIO_FEATURE_API_LIMIT = 100;
    const PLAYLIST_API_LIMIT = 50;
    const ARTIST_ALBUM_API_LIMIT = 50;

    const USER_TRACKS_API_LIMIT = 50;
    const USER_ALBUMS_API_LIMIT = 50;
    const USER_TOP_STUFF_API_LIMIT = 50;
    const USER_FOLLOWED_ARTISTS_LIMIT = 50;

    const GRANT_TYPE = 'client_credentials';

    const BASE_URL = "https://api.spotify.com";
    const DEFAULT_CLIENT_OPTIONS = [
        'http_errors' => false,
    ];

    protected $client;
    protected $token;
    protected $sourceFetcher;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function extractDataFromTrack($input):?array{
        if(!property_exists($input->external_ids, 'isrc')) return null;

        return[
            'spotify_uri_id' => $input->id,
            'spotify_duration' => $input->duration_ms,
            'spotify_isrc' => $input->external_ids->isrc
        ];
    }

    public function getMatcherClass(){
        return SpotifySearchMatcher::class;
    }

    public function getToken()
    {
        return $this->token = isset($this->token) ? $this->token : $this->fetchToken();
    }

    public function fetchCurrentUser(string $spotifyToken): stdClass
    {
        return $this->fetchJsonFromUrlWithAuth(
            "/v1/me",
            $spotifyToken
        );
    }

    public function fetchArtists($artistSpotifyIds, $token = null)
    {
        $artistSpotifyIds = array_filter($artistSpotifyIds);

        $groupedIds = $this->groupIdsByApiConstant($artistSpotifyIds, self::ARTIST_API_LIMIT);

        return $this->fetchWithGroupedIds('artists', $groupedIds, $token);
    }

    public function fetchRelatedArtists($artistSpotifyId, $token = null)
    {
        return $this->fetchJsonFromUrlWithAuth(
            "/v1/artists/{$artistSpotifyId}/related-artists",
            $token
        )->artists;
    }

    public function extractArt($res):?string{
        return $res->album->images[0]->url ?? $res[0]->album->images[0]->url ?? null;
    }

    public function extractPreview($res):?string{
        return $res->preview_url ?? $res->preview_url ?? null;
    }

    public function extractTracks($res):array{
        return $res->tracks->items ?? [];
    }

    public function fetchAlbums($albumSpotifyIds, $token = null)
    {
        $albumSpotifyIds = array_filter($albumSpotifyIds);

        $groupedIds = $this->groupIdsByApiConstant($albumSpotifyIds, self::ALBUM_API_LIMIT);

        return $this->fetchWithGroupedIds('albums', $groupedIds, $token);
    }

    public function fetchArtistAlbums($artistSpotifyId, $firstPage = false, $token = null)
    {
        $response = $this->fetchJsonFromUrlWithAuth(
            "/v1/artists/{$artistSpotifyId}/albums?album_type=single,album,appears_on,compilation&limit=".self::ARTIST_ALBUM_API_LIMIT,
            $token
        );

        return $firstPage ? $response->items : $this->fetchPaginatedData($response, $token)->items;
    }

    public function fetchTracks($trackSpotifyIds, $token = null, $storefront = null)
    {
        $trackSpotifyIds = array_filter($trackSpotifyIds);

        $groupedIds = $this->groupIdsByApiConstant($trackSpotifyIds, self::TRACK_API_LIMIT);

        $res = $this->fetchWithGroupedIds('tracks', $groupedIds, $token, null, $storefront);
        return $this->cleanUpDashesInIsrc($res);
    }

    public function fetchTrack($trackId, $token = null, $market = null)
    {
        $res = $this->fetchWithMarket('tracks', [$trackId], $token);
        return $this->cleanUpDashesInIsrc($res->tracks);
    }

    public function fetchArtistTopTracks($artistSpotifyId, $token = null)
    {
        return $this->fetchJsonFromUrlWithAuth(
            "/v1/artists/{$artistSpotifyId}/top-tracks?country=US",
            $token
        )->tracks;
    }

    public function fetchPlaylistWithTracks($playlistData, $userSpotifyToken = null, $fetchAllTracks = true)
    {
        $response = $this->fetchJsonFromUrlWithAuth(
            "/v1/users/{$playlistData['ownerId']}/playlists/{$playlistData['playlistId']}",
            $userSpotifyToken
        );

        if ($fetchAllTracks) {
            $response->tracks = $this->fetchPaginatedData($response->tracks, $userSpotifyToken);
        }

        return $response;
    }

    public function fetchUsersPlaylists(string $userSpotifyToken)
    {
        $response = $this->fetchJsonFromUrlWithAuth(
            "/v1/me/playlists?limit=".strval(self::PLAYLIST_API_LIMIT),
            $userSpotifyToken
        );

        return $response->items;
    }

    public function fetchUsersSavedTracks(string $userSpotifyToken)
    {
        $response = $this->fetchJsonFromUrlWithAuth(
            "/v1/me/tracks?limit=".strval(self::USER_TRACKS_API_LIMIT),
            $userSpotifyToken
        );

        $tracksWithMeta = $response->items;

        return $this->stripMetaDataFromSavedItems($tracksWithMeta, 'track');
    }

    public function fetchUsersSavedAlbums(string $userSpotifyToken)
    {
        $response = $this->fetchJsonFromUrlWithAuth(
            "/v1/me/albums?limit=".strval(self::USER_ALBUMS_API_LIMIT),
            $userSpotifyToken
        );

        $albumsWithMeta = $response->items;

        return $this->stripMetaDataFromSavedItems($albumsWithMeta, 'album');
    }

    public function fetchUsersTop($noun, $term, string $userSpotifyToken)
    {
        $response = $this->fetchJsonFromUrlWithAuth(
            "/v1/me/top/{$noun}?time_range={$term}&limit=".strval(self::USER_TOP_STUFF_API_LIMIT),
            $userSpotifyToken
        );

        return $this->fetchPaginatedData($response, $userSpotifyToken)->items;
    }

    public function fetchUsersFollowedArtists(string $userSpotifyToken)
    {
        $response = $this->fetchJsonFromUrlWithAuth(
            "/v1/me/following?type=artist&limit=".strval(self::USER_FOLLOWED_ARTISTS_LIMIT),
            $userSpotifyToken
        );

        return $response->artists->items;
    }

    public function fetchNewReleases($token = null)
    {
        $response = $this->fetchJsonFromUrlWithAuth(
            "/v1/browse/new-releases",
            $token
        )->albums;

        return $this->fetchPaginatedData($response, $token, 'albums')->items;
    }

    public function fetchTracksMetadata(array $trackIds, $token = null, $storefront = null):array
    {
        $res = $this->fetchTracks($trackIds, $token, $storefront);
        $output = [];
        foreach($res as $track){
            $artist = $track->artists[0]->name;
            $title = $track->name;
            $album = $track->album->name;
            $id = $track->id;
            if(!property_exists($track->external_ids, 'isrc')){
                continue;  //track removed by spotify
            }
            $isrc = $track->external_ids->isrc;

            $output[$id] = [
                'artist'=>$artist,
                'track'=>$title,
                'id'=>$id,
                'isrc'=>$isrc,
                'album'=>$album,
                'duration' =>$track->duration_ms,
                'sourceService' => 'spotify'
            ];
        }

        return $output;
    }

    public function fetchTracksWithAlbumMetadata(array $trackIds, $token = null, $storefront = null, $imageSize = 300)
    {
        $data = $this->fetchTracks($trackIds, $token, $storefront);

        return array_map(function($track) use ($imageSize){
            if(!property_exists($track->external_ids, 'isrc')){
                return null;  //track removed by spotify
            }
            $image = null;
            foreach ($track->album->images as $choice) {
                if (!$image) {
                    $image = $choice;
                    continue;
                }
                if ($image->height >= $imageSize && $choice->height >= $imageSize) {
                    if ($choice->height < $image->height) {
                        $image = $choice;
                    }
                } else {
                    if ($choice->height > $image->height) {
                        $image = $choice;
                    }
                }
            }
            
            return [
                'id'=>$track->id,
                'isrc'=> $track->external_ids->isrc,
                'album_id'=>$track->album->id,
                'album_image' => $image->url ?? null,
                'sourceService' => 'spotify'
            ];
        }, $data);
    }

    public function fetchSearchData($query, $itemTypes, $market = 'US', $cursor = 0, $token = null)
    {
        // return Cache::remember("SF_search:{$query} {$itemTypes} {$market} {$cursor}", 600000, function() use($query, $itemTypes, $market, $cursor, $token){
            return $this->fetchJsonFromUrlWithAuth(
                "/v1/search",
                $token,
                TRUE,
                [
                    'query' => [
                        'q' => $query,
                        'type' => $itemTypes,
                        'limit' => Config::get('stationhead.pagination.search_limit'),
                        'market' => $market,
                        'offset' => $cursor
                    ]
                ]
            );
        // });
    }

    public function fetchAudioFeatures($trackSpotifyIds, $token = null)
    {
        $groupedIds = $this->groupIdsByApiConstant($trackSpotifyIds, self::AUDIO_FEATURE_API_LIMIT);

        return $this->fetchWithGroupedIds('audio_features', $groupedIds, $token, 'audio-features');
    }

    private function fetchPaginatedData($response, $token, $noun = null)
    {
        $nextPage = $response->next;

        while(isset($nextPage)) {
            $nextResponse = $this->fetchPage($nextPage, $token, $noun);

            $nextPage = $nextResponse->next;

            $response->items = array_merge(
                $response->items,
                $nextResponse->items
            );
        }

        return $response;
    }

    private function fetchPage($nextPage, $token, $noun)
    {
        if(isset($noun)) {
            return $this->fetchJsonFromUrlWithAuth($nextPage, $token, FALSE)->$noun;
        } else {
            return $this->fetchJsonFromUrlWithAuth($nextPage, $token, FALSE);
        }
    }

    private function groupIdsByApiConstant($ids, $limit)
    {
        return array_chunk($ids, $limit);
    }

    private function fetchWithGroupedIds($noun, $groupedIds, $token, $routeNoun = null, $storefront = null)
    {
        if (!$routeNoun) {
            $routeNoun = $noun;
        }

        $objects = [];

        foreach($groupedIds as $ids) {
            if(is_null($storefront)){
                $objects = array_merge($objects, $this->fetch($routeNoun, $ids, $token)->$noun);
            }else{
                $objects = array_merge($objects, $this->fetchWithMarket($routeNoun, $ids, $token, $storefront)->$noun);
            }
        }

        return $objects;
    }

    private function fetch($noun, $ids, $token)
    {
        $id_string = implode(",", $ids);
        $json = $this->fetchJsonFromUrlWithAuth("/v1/{$noun}?ids={$id_string}", $token);
        return $json;
    }

    private function fetchWithMarket($noun, $ids, $token, $market = 'US')
    {
        $id_string = implode(",", $ids);
        $json = $this->fetchJsonFromUrlWithAuth("/v1/{$noun}?market={$market}&ids={$id_string}", $token);
        return $json;
    }

    private function fetchJsonFromUrlWithAuth($url, $token, $useBaseUrl = TRUE, $options = [])
    {
        $url = $useBaseUrl ? self::BASE_URL.$url : $url;
        $response = $this->client->get($url, $this->combineOptionsAndToken($options, $token));

        if($response->getStatusCode() == 502){
            return $this->retryRequest502($response, $url, $token, FALSE, $options);
        }
        if ($response->getStatusCode() >= 400) {
            $this->checkStatusCode($response);
            return $this->retryRequest($response, $url, $token, FALSE, $options);
        }

        return json_decode($response->getBody());
    }

    private function combineOptionsAndToken($options, $token)
    {
        $token = $token ? $token : $this->getToken();

        return array_merge(
            self::DEFAULT_CLIENT_OPTIONS,
            ['headers' => ['Authorization' => "Bearer {$token}"]],
            $options
        );
    }

    private function checkStatusCode($response)
    {
        if ($response->getStatusCode() !== 429) {
            throw new Exception($response->getBody());
        }
    }

    private function retryRequest502($response, $url, $token, $useBaseUrl, $options)
    {
        sleep(3);
        error_log("retrying request due to 502");
        return $this->fetchJsonFromUrlWithAuth($url, $token, $useBaseUrl, $options);
    }

    private function retryRequest($response, $url, $token, $useBaseUrl, $options)
    {
        sleep($response->getHeaders()['Retry-After'][0] + 1);
        return $this->fetchJsonFromUrlWithAuth($url, $token, $useBaseUrl, $options);
    }

    private function fetchToken()
    {
        $response = $this->client->post('https://accounts.spotify.com/api/token', [
            'form_params' => [
                'client_id' => config('stationhead.spotify.client_id'),
                'client_secret' => config('stationhead.spotify.client_secret'),
                'grant_type' => self::GRANT_TYPE
            ]
        ]);

        return json_decode($response->getBody())->access_token;
    }

    public function stripMetaDataFromSavedItems($objects, $noun)
    {
        return array_map(function($object) use($noun) {
            return $object->$noun;
        }, $objects);
    }

    public function stripDashes(string $input):string {
        return str_replace('-', '', $input);
    }

    private function cleanUpDashesInIsrc($input)
    {
        foreach ($input as $key => $value) {
            if(!property_exists($value->external_ids, 'isrc')) continue;
            $isrc = $value->external_ids->isrc;
            $input[$key]->external_ids->isrc = $this->stripDashes($isrc);
        }
        return $input;
    }
}
