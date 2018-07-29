<?php

namespace App\Services\AppleMusic\Support;

use Redis;

class PricingUpdaterRedisTools
{
    public function fetchRedisResults(array $ids, $testing = false){
        if(count($ids) == 0 ) return [];

        $keyName = $testing ? 'se_testing' : 'se';
        $input = [];
        foreach($ids as $id){
            array_push($input, $id.'_0', $id.'_1');
        }
        $res = Redis::connection(config('redis.apple_pricing.db'))->hmget($keyName, $input);

        $output = [];
        for($i = 0; $i < count($res); $i+=2){
            $zero = $res[$i];
            $one = $res[$i+1];
            $ans = null;

            if(is_null($zero) && !is_null($one)) $ans = "1";
            else if(is_null($one) && !is_null($zero)) $ans = "0";
            else if(is_null($one) && is_null($zero)) $ans = null;
            else if($one >= $zero) $ans = "1";
            else if($zero > $one) $ans = "0";

            array_push($output, $ans);
        }

        return $output;
    }
}
