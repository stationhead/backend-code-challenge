<?php

namespace App\Services\AppleMusic;

use App\Models\Track;
use App\Services\AppleMusic\AppleMusicFetcher;
use App\Services\AppleMusic\Library;
use App\Services\MusicMatchers\MusicMatcherInterface;
use App\Services\MusicMatchers\DispatchFingerprintMatchTrackJob;
use App\Jobs\MusicMatching\FingerprintMatchTrack;
use Illuminate\Foundation\Bus\DispatchesJobs;

use App, Redis, stdClass, Storage;
use App\Services\MusicMatchers\Constants;

use App\Services\MusicMatchers\SearchStrategies\Utilities;
use App\Services\MusicMatchers\SearchStrategies\ExactString;
use App\Services\MusicMatchers\SearchStrategies\TrackAlphaNumOnlyMatch;
use App\Services\MusicMatchers\SearchStrategies\ArtistAlphaNumOnlyMatch;
use App\Services\MusicMatchers\SearchStrategies\FloatingArtist;
use App\Services\MusicMatchers\SearchStrategies\FloatingTrack;
use App\Services\MusicMatchers\SearchStrategies\TheRemover;

use App\Services\AppleMusic\SearchStrategies\RegexMatch;

class AppleMusicSearchMatcher implements MusicMatcherInterface
{
    public function __construct(Library $appleLibrary, AppleMusicFetcher $fetcher, DispatchFingerprintMatchTrackJob $dispatchJob){
        $this->appleLibrary = $appleLibrary;
        $this->fetcher = $fetcher;
        $this->dispatchJob = $dispatchJob;
    }

    public function getServiceUriFieldName():string{
        return 'apple_uri_id';
    }

    public function getServiceName():string{
        return 'AppleMusic';
    }

    public function getShortName():string{
        return 'apple';
    }

    public function getConversionRequirements():string{
        return 'isrc';
    }

    public function getFetcher(){
        return $this->fetcher;
    }

    public function setFetcher(AppleMusicFetcher $fetcher){
        $this->fetcher = $fetcher;
    }

    public function setSourceFetcher($sourceFetcher){
        $this->sourceFetcher = $sourceFetcher;
    }

    public function matchTracksIndirectly(array $input):array{
        foreach($input as $id=>$entry){
            $res = $this->matchTrack($entry);
            if(is_null($res)) continue;
            $input[$id] = $res;
        }
        return $input;
    }

    private function matchTrack(array $entry):?array{
        //first try isrc matching
        if($track = $this->isrcMatch($entry)){
            $info = $this->fetcher->extractDataFromTrack($track);

            if(is_null($info)){
                return null; //fetching track failed
            }

            $entry = array_merge($entry, $info);
            $entry['apple_quality'] = Constants::PERFECT_ISRC_MATCH;
            return $entry;
        }

        //if isrc matching failed, use search matching

        //get the track metadata in preperation for a search match
        $source_id = $entry['id'];
        $metadataResult = $this->sourceFetcher->fetchTracksMetadata([$source_id]);
        if(count($metadataResult )== 0){
            return null; //track no longer available on source fetcher
        }
        $metadata = $metadataResult[$source_id];

        $artist = $metadata['artist'];
        $track = $metadata['track'];
        $id = $metadata['id'];
        $duration = $metadata['duration'];
        $album = $metadata['album'];

        $alphanum_artist = Utilities::stripNonAlphaNumeric($artist);
        $alphanum_track =  Utilities::stripNonAlphaNumeric($track);
        $alphanum_album =  Utilities::stripNonAlphaNumeric($album);

        $term = "$artist $track";

        $searchPatterns = [
            "{$artist} {$track} {$album}",
            "{$artist} {$track}",
            "{$alphanum_artist} {$alphanum_track} {$alphanum_album}",
            "{$alphanum_artist} {$alphanum_track}"
        ];

        $allResults = [];
        //now get the results

        foreach ($searchPatterns as $idx => $term) {
            //apple search hates commas
            $term = str_replace(', ', ',', $term);
            $term = str_replace(',', ' ', $term);

            $term = urlencode($term);
            $results = $this->fetcher->fetchSearchData($term, 'songs');

            if(!array_key_exists('songs', $results) || is_null($results->songs)){
                Redis::sadd('AMF-no-search-results', "$id | $artist | $track | $term");
                continue;
            }

            $results = array_filter($results->songs->data, function($el){
                //remove non-streamable search results
                return property_exists($el->attributes, 'playParams');
            });
            
            foreach($results as $el){
                $allResults[$el->id] = $el;
            }

            $searchQualityModifiers = [
                0,
                0,
                -50,
                -50
            ];

            if($track = $this->searchMatch($results, $metadata)){
                $quality = $track['quality'];
                $track = $track['song'];
                $info = $this->fetcher->extractDataFromTrack($track);
                if(is_null($info)){
                    return null; //fetching failed
                }
                $entry = array_merge($entry, $info);
                $entry['apple_quality'] = $quality + $searchQualityModifiers[$idx];
                return $entry;
            }
        }

        //if all else fails, use fingerprint fallback
        $this->fingerprintFallback($metadata, $allResults);

        return null;
    }

    private function fingerprintFallback(array $metadata, array $allResults){
        $source_id = $metadata['id'];
        $sourceTrackInfo = $this->sourceFetcher->fetchTrack($source_id)[0];
        $source=(object)[
            'id'=>$sourceTrackInfo->id,
            'preview'=>$sourceTrackInfo->preview_url ?? null,
            'duration'=>$sourceTrackInfo->duration_ms
        ];

        $cleanResults = array_map(function($el){
            return (object)[
                'id'=>$el->id,
                'preview'=>$el->attributes->previews[0]->url ?? null,
                'duration'=>$el->attributes->durationInMillis ?? null
            ];
        }, $allResults);

        $this->dispatchJob->handle($metadata, 'apple', $source, $cleanResults);
    }

    private function isrcMatch(array $entry):?stdClass{
        $source_id = $entry['id'];
        $res = $this->appleLibrary->isrcToApple($entry['isrc']);
        $track = null;

        if(!is_null($res)){
            //found results
            $tracks = $this->fetcher->fetchTracks($res);
            if(count($tracks) > 0){
                //at least one good match!
                $track = $tracks[0]; //picking the first
            }
        }
        return $track;
    }

    //returns an array if it finds a match
    //otherwise retuns null
    private function searchMatch(array $results, array $metadata):?array{
        foreach($results as $song){
            if(!property_exists($song->attributes, 'name')){
                //this result doesn't have a name attribute.  no idea why
                Redis::sadd('AMF-no-name-attr', $song->id);
                continue;
            }

            if(!property_exists($song->attributes, 'durationInMillis')){
                //this result doesn't have a duration attribute.  no idea why
                Redis::sadd('AMF-no-duration-attr', $song->id);
                continue;
            }

            //prepare the variables to send
            $leftTrack = (object)$metadata;
            $rightTrack = new stdClass();
            $rightTrack->track = $song->attributes->name;
            $rightTrack->artist = $song->attributes->artistName;
            $rightTrack->duration = $song->attributes->durationInMillis;

            $searchStrategies = [
                ExactString::class,
                RegexMatch::class,
                TrackAlphaNumOnlyMatch::class,
                FloatingArtist::class,
                TheRemover::class,
                ArtistAlphaNumOnlyMatch::class,
                // FloatingTrack::class
            ];

            foreach($searchStrategies as $stratClass){
                $strat = App::make($stratClass);
                if($returnedQuality = $strat->execute($leftTrack, $rightTrack)){
                    return [
                        'song' => $song,
                        'quality' => $returnedQuality
                    ];
                }
            }
        }
        return null;
    }
}
