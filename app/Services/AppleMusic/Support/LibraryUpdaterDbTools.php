<?php

namespace App\Services\AppleMusic\Support;

use Illuminate\Support\Facades\DB;

class LibraryUpdaterDbTools
{
    protected $db;

    public function __construct(){
        $this->db = DB::connection(config('stationhead.database.connections.apple'));
    }

    public function duplicateTable($fullUpdate){
        $this->raw('DROP TABLE IF EXISTS apple_tracks_copy');
        $this->raw('CREATE TABLE apple_tracks_copy LIKE apple_tracks');
        
        if(!$fullUpdate) $this->raw('INSERT INTO apple_tracks_copy SELECT * FROM apple_tracks');
    }

    public function insertDbFile($path){
        $test = config('stationhead.app_env') == 'testing' ? '_testing' : '';

        $username = config("database.connections.mysql_apple{$test}.username");
        $password = config("database.connections.mysql_apple{$test}.password");
        $hostname = config("database.connections.mysql_apple{$test}.host");
        $db = config("database.connections.mysql_apple{$test}.database");
        $command = "mysqlimport --local --compress --user={$username} --password={$password} --host={$hostname} --fields-terminated-by=\"\x01\" --lines-terminated-by=\"\x02\\n\" --ignore-lines=34 --columns=export_date,song_id,isrc,@x,@x {$db} {$path}";
        shell_exec($command);
    }

    public function rotateTables(){
        $this->raw('DROP TABLE apple_tracks');
        $this->raw('RENAME TABLE apple_tracks_copy TO apple_tracks');
    }

    private function raw($query){
        return $this->db->getPdo()->exec($query);
    }

}
