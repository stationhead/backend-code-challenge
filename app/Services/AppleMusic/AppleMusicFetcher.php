<?php

namespace App\Services\AppleMusic;

use stdClass, Exception;
use GuzzleHttp\Client;
use App\Services\AppleMusic\AppleMusicTokenGenerator as TokenGen;
use App\Services\MusicFetchers\MusicFetcherInterface;
use App\Services\AppleMusic\AppleMusicSearchMatcher;

class AppleMusicFetcher implements MusicFetcherInterface
{
    const BASE_URL = "https://api.music.apple.com/v1/";
    const TRACK_API_LIMIT = 300;
    const ALBUM_API_LIMIT = 10; //didn't actually check this
    const DEFAULT_CLIENT_OPTIONS = [
        'http_errors' => false,
    ];

    protected $client;
    protected $token;
    protected $tokenGen;
    protected $sentRetry;

    public function __construct(Client $client, TokenGen $token){
        $this->client = $client;
        $this->tokenGen = $token;
        $this->token = $token->execute();
    }

    public function getMatcherClass(){
        return AppleMusicSearchMatcher::class;
    }

    public function fetchTrack($trackId, $storefront = null){
        $res = $this->fetchTracks([$trackId], $storefront);
        return $res == null ? null : $res[0];
    }

    public function fetchTracks($trackIds, $storefront = null){
        $trackIds = array_filter($trackIds);
        $tracksString = serialize($trackIds);
        $groupedIds = $this->groupIdsByApiConstant($trackIds, self::TRACK_API_LIMIT);
        return $this->fetchWithGroupedIds('songs', $groupedIds, $storefront);
    }

    public function fetchAlbums(array $albumIds, $storefront = null){
        $trackIds = array_filter($albumIds);
        $groupedIds = $this->groupIdsByApiConstant($albumIds, self::ALBUM_API_LIMIT);
        return $this->fetchWithGroupedIds('albums', $groupedIds, $storefront);
    }

    public function fetchSearchData($query, $itemTypes, $market = null){
        $query = str_replace(' ', '+', $query);
        $market = $market ?? AuthUser()->country ?? 'us';
        $res = $this->fetchJsonFromUrlWithAuth("catalog/{$market}/search?types={$itemTypes}&term={$query}&limit=20");
        return ($res->results);
    }

    public function fetchTracksWithAlbumMetadata(array $trackIds, $token = null, $storefront = null, $imageSize = 300)
    {
        $data = $this->fetchTracks($trackIds, $token, $storefront);

        $albumIds = array_map(function($track){
            return $track->relationships->albums->data[0]->id;
        }, $data);

        $albums = $this->fetchAlbums($albumIds, $storefront);
        $keyedAlbums = [];
        foreach ($albums as $album) {
            $keyedAlbums[$album->id] = $album;
        }

        return array_map(function($track) use ($keyedAlbums, $imageSize) {

            $albumId = $track->relationships->albums->data[0]->id;
            $image = $this->extractArt($keyedAlbums[$albumId], $imageSize);

            return [
                'id'=>$track->id,
                'isrc'=> $track->attributes->isrc,
                'album_id'=>$albumId,
                'album_image' => $image,
                'sourceService' => 'apple'
            ];
        }, $data);
    }
    public function fetchTracksMetadata(array $trackIds, $storefront = null){
        //gets us track name, artist name, and album id
        $tracks = $this->fetchTracks($trackIds, $storefront);

        //filter out album ids
        $albumIds = array_map(function($track){
            return $track->relationships->albums->data[0]->id;
        }, $tracks);

        $albums = $this->fetchAlbums($albumIds, $storefront);

        $output = [];
        foreach($tracks as $track){
            $trackId = $track->id;
            $trackName = $track->attributes->name;
            $artist = $track->attributes->artistName;
            $albumId = $track->relationships->albums->data[0]->id;
            $duration = $track->attributes->durationInMillis;
            $isrc = $track->attributes->isrc;
            $preview = $track->attributes->previews[0]->url ?? null;

            $output[$trackId] = ['track'=>$trackName,
                                'artist'=> $artist,
                                'source_albumId'=>$albumId,
                                'id'=>$trackId,
                                'isrc'=>$isrc,
                                'duration'=>$duration,
                                'preview'=>$preview,
                                'sourceService'=>'apple',
                                'album'=>""];
        }

        //creates a fast lookup array for album_id -> album_name
        $albumArray = [];
        foreach($albums as $album){
            $albumTitle = $album->attributes->name;
            $id = $album->id;

            $albumArray[$id]=$albumTitle;
        }

        //goes through each output value and fills in album name
        foreach($output as $value){
            if(array_key_exists($value['source_albumId'], $albumArray)){
                $album = $albumArray[($value['source_albumId'])];
                $output[$value['id']]['album'] = $album;
            }else{
                //can't find the album. really weird. issue on 5-10 tracks
                //remove the track from the output and move on
                //effectively skips the track
                unset($output[$value['id']]);
            }
        }

        return $output;
    }

    public function extractPreview($res):?string{
        return $res->attributes->previews[0]->url ?? null;
    }

    public function extractArt($res, $size = 400):?string{
        $str = $res->attributes->artwork->url ?? "";
        $str = str_replace('{w}', $size, $str);
        $str = str_replace('{h}', $size, $str);

        if($str === "") return null;
        return $str;
    }

    public function extractTracks($res):array{
        return $res->songs->data ?? [];
    }

    public function extractDataFromTrack($input):?array{
        if(!property_exists($input, 'attributes') || !property_exists($input->attributes, 'durationInMillis')){
            return null;
        }
        return [
            'apple_uri_id' => $input->id,
            'apple_duration' => $input->attributes->durationInMillis,
            'apple_isrc' => $input->attributes->isrc
        ];
    }

    public function fetchCurrentStorefront(string $musicUserToken): string
    {
        return $this->fetch("me/storefront", $musicUserToken)[0]->id;
    }

    // private methods //

    private function groupIdsByApiConstant(array $input, $constant):array {
        return array_chunk($input, $constant);
    }

    private function fetchWithGroupedIds($noun, $groupedIds, $storefront){
        $objects = [];
        $storefront = $storefront ?? AuthUser()->country ?? 'us';
        foreach($groupedIds as $ids){
            $idString = implode(",", $ids);
            $url = "catalog/{$storefront}/{$noun}?ids={$idString}";
            $objects = array_merge($objects, ($this->fetch($url)));
        }
        return $objects;
    }

    private function fetch(string $url, string $musicUserToken = ""): array {
        $headers = $musicUserToken != "" ? ["Music-User-Token" => $musicUserToken] : [];
        $json = $this->fetchJsonFromUrlWithAuth($url, true, [], $headers);
        return $json->data;
    }

    private function fetchJsonFromUrlWithAuth($url, $useBaseUrl = TRUE, $options = [], $headers = []){
        $url = $useBaseUrl ? self::BASE_URL.$url : $url;
        $options = array_merge(
                self::DEFAULT_CLIENT_OPTIONS, 
                ['headers' => array_merge(
                        ['Authorization' => "Bearer {$this->token}"], 
                        $headers
                )], 
                $options
            );

        $response = $this->client->get($url, $options);

        if($response->getStatusCode() >= 400){
            $this->checkStatusCode($response);
            return $this->retryRequest($response, $url, FALSE, $options);
        }

        return json_decode($response->getBody());
    }

    private function checkStatusCode($response)
    {        
        if ($response->getStatusCode() !== 429 || $this->sentRetry) {
            throw new Exception(var_export($response, true));
        }
    }

    private function retryRequest($response, $url, $useBaseUrl, $options){
        $this->sentRetry = true;
        $this->token = $this->tokenGen->execute();
        return $this->fetchJsonFromUrlWithAuth($url, $useBaseUrl, $options);
    }

}
