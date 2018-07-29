<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use App\Services\MusicMatchers\Constants;
use stdClass;
use Redis;
use Exception;

abstract class RegexMatch implements SearchStrategyInterface
{

    public function execute(stdClass $leftTrack, stdClass $rightTrack):int{
        $filters = $this->filters();

        foreach($filters as $filter){
            $res = $this->regexStringCompare($leftTrack, $rightTrack, $filter['left'], $filter['right']);
            if($res){
                return $filter['quality'];
            }
        }
        return 0;
    }

    private function regexStringCompare (stdClass $leftTrack, stdClass $rightTrack, array $leftRegex, array $rightRegex):bool{
        $artist = trim($leftTrack->artist);
        $track = trim($leftTrack->track);
        $id = $leftTrack->id;
        $duration = $leftTrack->duration;

        $result_track = trim($rightTrack->track);
        $result_artist = trim($rightTrack->artist);
        $result_duration = $rightTrack->duration;

        if(!Utilities::durationCheck($leftTrack, $rightTrack)){
            return false;
        }

        $found = false;
        foreach($leftRegex as $line){
            $sourceHasRegexMatch = preg_match($line, $track, $out);
            if($sourceHasRegexMatch == 0){
                //source track doesn't match regex
                continue;
            }else{
                $found=true;
                $strippedTrack = trim($out[1]);
                break;
            }
        }
        if(!$found) return false;  //none of the regex fits left side

        $found = false;
        foreach($rightRegex as $line){
            $resultHasRegexMatch = preg_match($line, $result_track, $out);
            if($resultHasRegexMatch == 0){
                //result track doesn't appear to match regex
                continue;
            }else{
                $found=true;
                $stripped_result_track = trim($out[1]);
                break;
            }
        }
        if(!$found) return false; //none of the regex fits right side

        if(strcasecmp($stripped_result_track, $strippedTrack) == 0 &&
            strcasecmp($result_artist, $artist) == 0
        ){
            Redis::sadd('regex_found', "$id --> $artist || $result_artist  ---- $track || $result_track");
            return true;
        }else{
            Redis::sadd('regex_no_found', "$id --> $artist || $result_artist  ---- $track || $result_track  ---- $duration || $result_duration");
            return false;
        }
    }

    abstract protected function filters():array;
}
