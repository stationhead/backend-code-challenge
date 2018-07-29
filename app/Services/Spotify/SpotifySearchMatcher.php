<?php

namespace App\Services\Spotify;

use stdClass, Config, App, Exception, Redis;
use App\Models\Track;
use App\Services\MusicMatchers\MusicMatcherInterface;
use App\Services\MusicMatchers\FingerprintMatcher;
use App\Services\MusicMatchers\DispatchFingerprintMatchTrackJob;
use App\Jobs\Musicmatching\FingerprintMatchTrack;
use Illuminate\Foundation\Bus\DispatchesJobs;

use App\Services\MusicMatchers\SearchStrategies\Utilities;
use App\Services\MusicMatchers\SearchStrategies\IsrcMatch;
use App\Services\MusicMatchers\SearchStrategies\ExactString;
use App\Services\MusicMatchers\SearchStrategies\TrackAlphaNumOnlyMatch;
use App\Services\MusicMatchers\SearchStrategies\ArtistAlphaNumOnlyMatch;
use App\Services\MusicMatchers\SearchStrategies\FloatingArtist;
use App\Services\MusicMatchers\SearchStrategies\FloatingTrack;
use App\Services\MusicMatchers\SearchStrategies\TheRemover;

use App\Services\Spotify\SearchStrategies\RegexMatch;

class SpotifySearchMatcher implements MusicMatcherInterface
{
    public function __construct(SpotifyFetcher $fetcher,
                                FingerprintMatcher $fingerprintMatcher,
                                DispatchFingerprintMatchTrackJob $dispatchJob){
        $this->fetcher = $fetcher;
        $this->fingerprintMatcher = $fingerprintMatcher;
        $this->dispatchJob = $dispatchJob;
    }

    public function matchTracksIndirectly(array $input):array{
        $output = [];
        foreach($input as $key=>$entry){
            $result = $this->fetchTrackFromMetadata($entry);
            if($result != null) array_push($output, $result);
        }
        return $output;
    }

    public function fetchTrackFromMetadata(array $metadata) {
        //first get search results
        $artist = $metadata['artist'];
        $track = $metadata['track'];
        $album = $metadata['album'];
        $sourceIsrc = $metadata['isrc'];

        $alphanum_artist = Utilities::stripNonAlphaNumeric($metadata['artist']);
        $alphanum_track =  Utilities::stripNonAlphaNumeric($metadata['track']);
        $alphanum_album =  Utilities::stripNonAlphaNumeric($metadata['album']);

        $searchPatterns = [
            "artist:{$artist} album:{$album} track:{$track}",
            "artist:{$artist} track:{$track}",
            "artist:{$alphanum_artist} album:{$alphanum_album} track:{$alphanum_track}",
            "artist:{$alphanum_artist} track:{$alphanum_track}"
        ];

        $allResults = [];

        //now try each search pattern
        foreach($searchPatterns as $idx=>$query){
            //spotify search hates dashes and pluses
            $query = str_replace('-', '', $query);
            $query = str_replace('+', '', $query);
            $results = $this->fetcher->fetchSearchData($query, 'track');

            //this pattern yielded no results, try the next one
            if(count($results->tracks->items) === 0) continue;
            $allResults = array_merge($allResults, $results->tracks->items);

            $match = $this->searchMatch($results->tracks->items, $metadata);

            //no match found, try next search pattern
            if(is_null($match)) continue;

            //we found a match! output the result

            //the fuzzier search pattern imposes a penalty on the search quality.
            //each array element corresponds to the same index in $searchPatterns
            $searchQualityModifiers = [
                0,
                0,
                -50,
                -50
            ];
            $results = $this->fetcher->extractDataFromTrack($match['track']);
            \Log::info("modifier: {$searchQualityModifiers[$idx]}");
            $results['spotify_quality'] = $match['quality'] + $searchQualityModifiers[$idx];
            return array_merge($metadata, $results);
        }
        Redis::incr('SSM-no_results');

        $this->fingerprintFallback($metadata, $allResults);
        return null;
    }

    private function fingerprintFallback(array $metadata, array $allResults){
        //need to loop through the results and prepare the objects
        $results = [];
        foreach($allResults as $result){
            $results[$result->id] = (object)[
                'preview' => $result->preview_url,
                'duration' => $result->duration_ms,
                'id'=> $result->id
            ];
        }
        $source = (object)[
            'preview' => $metadata['preview'],
            'duration' => $metadata['duration'],
            'id' => $metadata['id']
        ];

        $this->dispatchJob->handle($metadata, 'spotify', $source, $results);
    }

    private function searchMatch(array $results, array $metadata):?array{

        foreach($results as $entry){
            $leftTrack = (object)$metadata;

            $rightTrack = new stdClass();
            $rightTrack->track = $entry->name;
            $rightTrack->artist = $entry->artists[0]->name;
            $rightTrack->duration = $entry->duration_ms;
            $rightTrack->isrc = $entry->external_ids->isrc;


            $searchStrategies = [
                IsrcMatch::class,
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
                        'track' => $entry,
                        'quality' => $returnedQuality
                    ];
                }
            }
        }

        //nothing matched :(
        return null;
    }

    public function getServiceUriFieldName():string{
        return 'spotify_uri_id';
    }

    public function getServiceName():string{
        return 'Spotify';
    }

    public function getShortName():string{
        return 'spotify';
    }

    public function getConversionRequirements():string{
        return 'metadata';
    }

    public function setSourceFetcher($fetcher){
        $this->sourceFetcher = $fetcher;
    }

    public function getFetcher(){
        return $this->fetcher;
    }
}
