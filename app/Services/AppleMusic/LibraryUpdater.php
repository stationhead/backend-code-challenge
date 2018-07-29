<?php

namespace App\Services\AppleMusic;

use App\Services\AppleMusic\Support\LibraryUpdaterDbTools;
use App\Services\AppleMusic\Support\LibraryUpdaterFileManager;
use App\Console\Commands\RecheckAppleIdsInTracks;

use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;

class LibraryUpdater
{
    const FULL_BASE_URL = 'feeds.itunes.apple.com/feeds/epf/v4/current/current/';
    const INCREMENTAL_BASE_URL = 'feeds.itunes.apple.com/feeds/epf/v4/current/current/incremental/current/';

    protected $redis;
    protected $auth_str;

    public function __construct(Client $client,
                                LibraryUpdaterDbTools $dbTools,
                                LibraryUpdaterFileManager $fileManager,
                                PricingUpdater $pricingUpdater,
                                RecheckAppleIdsInTracks $rechecker){
                                    
        $this->redis = Redis::connection(config('redis.apple_library.db'));
        $this->auth_str = config('stationhead.apple.username') . ':' . config('stationhead.apple.password');
        $this->username = config('stationhead.apple.username');
        $this->password = config('stationhead.apple.password');

        $this->client = $client;
        $this->dbTools = $dbTools;
        $this->fileManager = $fileManager;
        $this->pricingUpdater = $pricingUpdater;
        $this->rechecker = $rechecker;
    }

    public function checkAndUpdate(){
        if($this->isFullUpdateRequired()){
            $this->update(false);
        }

        if($this->isIncrementalUpdateRequired()){
            $this->update(true);
        }
    }

    public function updatePricingTables(bool $incremental){
        $this->pricingUpdater->handle($this->getPriceDownloadLinks($incremental));
    }

    public function update(bool $incremental){
        //start service that starts pricing update jobs
        $this->updatePricingTables($incremental);

        $this->fileManager->deleteFiles();

        $last_full = $this->redis->get('last_full_update');
        $last_inc = $this->redis->get('last_incremental_update');

        $url = $this->findDownloadURL($incremental);
        $filename = $this->fileManager->downloadFiles($url);
        $path = $this->fileManager->extractFiles($filename);
        $path = $this->fileManager->renameFile($path);
        $full_path = base_path("storage/app/$path");

        $this->dbTools->duplicateTable($incremental);
        $res = $this->dbTools->insertDbFile($full_path);
        $this->dbTools->rotateTables();

        $date = $this->getDateStampFromUrl($incremental ? self:: INCREMENTAL_BASE_URL : self::FULL_BASE_URL);
        $this->redis->set(($incremental ? 'last_incremental_update' : 'last_full_update'),$date);

        $this->fileManager->deleteFiles();

        //recheck all the apple ids on the tracks table
        $this->rechecker->handle();
    }

    /*
    *   reads the feed html and sees whether or not there is a new update by comparing
    *   the remote date stamp to the one we have recorded locally
    */
    public function isFullUpdateRequired():bool {
        $match_date = $this->getDateStampFromUrl(self::FULL_BASE_URL);
        $last_full_update = $this->redis->get('last_full_update');

        if($last_full_update == null) return true; // previous download not known, download anyway

        //the last recorded update is same as current on web. no update needed
        if($match_date <= $last_full_update) return false;

        return true;
    }

    /*
    * checks if there are any incremental updates on the apple feed site.
    * also checks the date stamp.  returns true if there's a newer incremental update
    */
    public function isIncrementalUpdateRequired():bool {
        $html = $this->getPageHtml(self::FULL_BASE_URL);
        preg_match('/incremental/',$html, $out);

        if( sizeof($out) == 0 ) return false; //no incremental update since latest full dump, no need to update

        $last_incremental_update = $this->redis->get('last_incremental_update');

        if($last_incremental_update == null) return true; //there's a incremental update, but we've no record of downloading it. needs update

        $latest_date = $this->getDateStampFromUrl(self::INCREMENTAL_BASE_URL);

        //the last recorded update is same as current on web. no update needed
        if($latest_date <= $last_incremental_update) return false;

        return true;
    }


    // private functions //

    private function getPriceDownloadLinks(bool $incremental){
        $url_root = $incremental ? self::INCREMENTAL_BASE_URL : self::FULL_BASE_URL;
        $date = $this->getDateStampFromUrl($incremental ? self:: INCREMENTAL_BASE_URL : self::FULL_BASE_URL);

        $html = $this->getPageHtml($url_root."pricing{$date}/");

        preg_match_all('/href="(song_price.*.tbz)"/', $html, $out);

        return array_map(function($filename) use($url_root, $date){
            return "https://{$this->auth_str}@{$url_root}pricing{$date}/{$filename}";
        }, $out[1]);
    }

    private function getPageHtml($url){
        return (string)$this->client->request('GET', "https://{$url}",['auth'=>[$this->username, $this->password]])->getBody();
        // return file_get_contents('https://' . $this->auth_str . '@' . $url);
    }

    private function getDateStampFromUrl($url){
        $html = $this->getPageHtml($url);
        preg_match('/match+(\d+)\//', $html, $match_date);
        return $match_date[1];
    }

    /*
    *   Scrapes the apple feed site and gets the appropriate download URL
    */
    private function findDownloadURL(bool $incremental):string {
        $url_root = $incremental ? self::INCREMENTAL_BASE_URL : self::FULL_BASE_URL;
        $date = $this->getDateStampFromUrl($incremental ? self:: INCREMENTAL_BASE_URL : self::FULL_BASE_URL);

        return 'https://' . $this->auth_str . '@' . $url_root . 'match' . $date . '/song_match.tbz';
    }
}
