<?php

namespace App\Http\Controllers;

use Config;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

use League\Fractal\Pagination\Cursor;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Transformers\TrackTransformer;

use App\Models\Track;

class PlayController extends Controller
{
    /**
     * Track
     *
     */
    protected $track;

    /**
     * TrackTransformer
     *
     */
    protected $transformer;

    public function __construct(Track $track)
    {
        $this->track = $track;
    }

     public function store(Request $request)
     {
         $tracks = Request::input("tracks");
         $metadata = (new MetadataFetcher)->execute($tracks);
         return json_encode($metadata);

     }

}
