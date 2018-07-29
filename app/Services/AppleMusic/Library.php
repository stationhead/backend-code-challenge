<?php

namespace App\Services\AppleMusic;

use Illuminate\Support\Facades\DB;
use App\Services\AppleMusic\Support\PricingUpdaterRedisTools;
use Redis;

class Library{
    private $db;

    public function __construct(PricingUpdaterRedisTools $tools){
        $this->db = DB::connection(config('stationhead.database.connections.apple'));
        $this->tools = $tools;
    }

    /*
    *   Takes in a isrc string and returns an array of all matched
    */
    public function isrcToApple(string $isrc):?array {
        $res = $this->db->select('SELECT song_id FROM apple_tracks WHERE isrc = ?', [$isrc]);

        if(count($res) == 0) return null;

        //filter out any non streamable ids
        if(config('stationhead.apple_filter_not_streamable')){
            $redis_results = $this->tools->fetchRedisResults(array_column($res, 'song_id'));
            $res = array_filter($res, function($id, $idx)use($redis_results){
                return $redis_results[$idx] == "1";
            }, ARRAY_FILTER_USE_BOTH);
        }

        if(count($res) == 0){
            Redis::sadd('Library-A', $isrc);
            return null;
        }

        return array_column($res, 'song_id');
    }

    public function appleToIsrc(string $apple):?array{
        $res = $this->db->select('SELECT isrc FROM apple_tracks WHERE song_id = ?', [$apple]);

        if(sizeof($res) == 0) return null;

        return array_map(function($result){
            return $result->isrc;
        }, $res);
    }

    public function isIsrcInLibrary(string $isrc):bool {
        return is_array($this->isrcToApple($isrc));
    }

    public function isAppleInLibrary(string $apple):bool {
        return is_array($this->appleToIsrc($apple));
    }
}
