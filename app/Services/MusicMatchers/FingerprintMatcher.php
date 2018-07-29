<?php

namespace App\Services\MusicMatchers;

use App;
use Redis;
use stdClass;
use App\Models\Track;
use App\Services\MusicMatchers\Constants;
use App\Services\ACRCloud\Fingerprint;
use App\Services\MusicMatchers\SearchStrategies\Utilities;

class FingerprintMatcher
{
    public function __construct(Fingerprint $fingerprint){
        $this->fingerprint = $fingerprint;
    }

    public function execute(Track $track, string $service, stdClass $source, array $results)
    {
        if(count($results) === 0) return false;
        $this->service = $service;
        //feeds in an object with source data
        //feeds in hash of ids, preview urls, durations
        //source:
        //  preview
        //  duration
        //  id

        //results:
        //  preview
        //  duration
        //  id

        $left = (object)[ 'duration' => $source->duration ];
        $url = $source->preview;

        if(is_null($url)){
            return false;
        }

        $sourceResult = $this->downloadAndGetACRID($url);
        $sourceACRID = $sourceResult['acrid'];
        if(is_null($sourceACRID)) return false;

        foreach($results as $result){
            $right = (object)[ 'duration' => $result->duration];
            if(!Utilities::durationCheck($left, $right)) continue;

            $url = $result->preview;
            if(is_null($url)) continue;

            $resultACRID = $this->downloadAndGetACRID($url)['acrid'];
            if(is_null($resultACRID)) continue;

            if($resultACRID == $sourceACRID){
                $this->updateTrack($track, $result->id);
                return true;
            }
        }

        //none of those matched... try the spotify id given
        if($this->service == 'spotify' && $spotifyId = $sourceResult['spotify_id']){
            $this->updateTrack($track, $spotifyId, true);
            return true;
        }

        return false;
    }

    private function updateTrack(Track $track, string $id, $solo = false){
        $quality = $solo ? Constants::ACR_FINGERPRINT_MATCH_SOLO : Constants::ACR_FINGERPRINT_MATCH;
        $track->update([
            "{$this->service}_uri_id"=>$id,
            "{$this->service}_search_quality"=>$quality
        ]);
    }

    private function downloadAndGetACRID(string $url):array{
        $path = $this->downloadSample($url);
        try{
            $fingerprintResult = $this->fingerprint->fingerprint($path);
            $acrid = [
                'acrid'=> $this->getACRID($fingerprintResult),
                'spotify_id'=> $this->getSpotifyID($fingerprintResult),
                'score'=> $this->getScore($fingerprintResult)
            ];
        }catch(\Exception $e){
            \Log::error("Error fingerprinting {$url}");
        }finally{
            unlink($path);
        }
        return $acrid;
    }

    private function getACRID(stdClass $input):?string{
        return $input->metadata->music[0]->acrid ?? null;
    }

    private function getSpotifyID(stdClass $input):?string{
        return $input->metadata->music[0]->external_metadata->spotify->track->id ?? null;
    }

    private function getScore(stdClass $input):?int{
        return $input->metadata->music[0]->score ?? null;
    }

    private function downloadSample(string $url):string{
        $ch = curl_init();
        preg_match('/.*\/(.*)/', $url, $out);
        $fullFileName=$out[1];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        $destination = base_path("storage/app/{$fullFileName}");
        $file = fopen($destination, "w+");
        file_put_contents($destination, $data);
        fclose($file);
        return $destination;
    }
}
