<?php

namespace App\Models;

use Config, DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

use Carbon\Carbon;

use App\Models\STHModelAbstract;

class Track extends STHModelAbstract
{
    protected $guarded = [];


    public function scopePopular($query)
    {
        return $query->orderBy('bite_count', 'desc');
    }

    public function scopeUniversalPlayback($query)
    {
        return $query->whereNotNull('apple_uri_id')->whereNotNull('spotify_uri_id');
    }

    public function scopeTracksLongerThan($query, $duration)
    {
        return $query->where('duration', '>=', $duration);
    }

    public function scopeRecentlyAdded($query){
        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    public function scopeOnService($query, $service = null)
    {
        if (!$service) {
            $service = Request::header("service");
        }
        switch ($service) {
            case "AppleMusic":
                $col = "apple_uri_id";
                break;
            case "Spotify":
                $col = "spotify_uri_id";
                break;
            default:
                throw new \InvalidArgumentException("Unknown service: {$service}.");
        }
        return $query->whereNotNull($col);
    }

}
