<?php

namespace App\Services\AppleMusic\Support;

use Illuminate\Support\Facades\Storage;

class LibraryUpdaterFileManager
{
    public function deleteFiles(){
        //find all the dirs
        $dirs = Storage::disk()->allDirectories();

        //delete the match folder and all files in it
        $target_dirs = array_values(preg_grep('/match\d+/', $dirs));
        if(count($target_dirs) > 0){
            Storage::disk()->deleteDirectory($target_dirs[0]);
        }
    }

    /*
    *   downloads the file at URL and returns the filename
    */
    public function downloadFiles(string $url):string{
        //break apart the url to find the filename
        preg_match('/\/(\w+\.tbz)$/', $url, $out);
        $filename = $out[1];

        //download the file
        Storage::disk()->put($filename, fopen($url, 'r'));

        //return the file name
        return $filename;
    }


    /*
    *   renames the file to apple_tracks_copy for mysqlimport
    */
    public function renameFile(string $path):string{
        $chunks = explode('/', $path);
        array_pop($chunks); //remove the old filename
        array_push($chunks, 'apple_tracks_copy');
        $newPath = implode('/',$chunks);

        Storage::disk()->move($path, $newPath);
        return $newPath;
    }

    /*
    *   extracts the file at path and returns the extracted file path
    */
    public function extractFiles(string $filename):string{
        //extract the file
        $path = base_path("storage/app/$filename");
        $otherPath = base_path("storage/app/");
        $command = "tar -xjf $path -C $otherPath";
        $res = shell_exec($command);

        //delete the compressed file
        Storage::disk()->delete($filename);

        //find the path to the extracted file and return it
        $files = Storage::disk()->allFiles();
        $out = preg_grep('/song_match$/', $files);
        return array_values($out)[0];
    }
}
