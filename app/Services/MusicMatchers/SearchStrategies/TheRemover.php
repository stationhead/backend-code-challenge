<?php

namespace App\Services\MusicMatchers\SearchStrategies;

use App\Services\MusicMatchers\Constants;
use stdClass;
use Redis;
use App;

class TheRemover implements SearchStrategyInterface
{
    public function execute(stdClass $l, stdClass $r):int{
        $l = Utilities::trimAll($l);
        $r = Utilities::trimAll($r);

        if(!Utilities::durationCheck($l, $r)){
            return 0;
        }

        $l->artist = Utilities::removeThe($l->artist);
        $l->track = Utilities::removeThe($l->track);

        $r->artist = Utilities::removeThe($r->artist);
        $r->track = Utilities::removeThe($r->track);

        $subMatcher = App::make(ExactString::class);
        if($quality = $subMatcher->execute($l, $r)){
            Redis::sadd("TR-found", "$l->id-> $l->artist || $r->artist ---- $l->track || $r->track");
            return Constants::REMOVED_THE;
        }else{
            Redis::sadd("TR-no_found", "$l->id-> $l->artist || $r->artist ---- $l->track || $r->track");
        }
        return 0;
    }
}
