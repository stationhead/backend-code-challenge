<?php

namespace App\Services\ACRCloud;

use \CurlFile;

class Fingerprint
{
    public function fingerprint($path){
        $http_method = "POST";
        $http_uri = "/v1/identify";
        $data_type = "audio";
        $signature_version = "1" ;
        $timestamp = time() ;

        $host = config('stationhead.acr_cloud.host');
        $access_key =  config('stationhead.acr_cloud.key');
        $access_secret =  config('stationhead.acr_cloud.secret');
        $requrl = "http://{$host}/v1/identify";

        $string_to_sign = $http_method . "\n" .
                          $http_uri ."\n" .
                          $access_key . "\n" .
                          $data_type . "\n" .
                          $signature_version . "\n" .
                          $timestamp;
        $signature = hash_hmac("sha1", $string_to_sign, $access_secret, true);
        $signature = base64_encode($signature);
        // suported file formats: mp3,wav,wma,amr,ogg, ape,acc,spx,m4a,mp4,FLAC, etc
        // File size: < 1M , You'de better cut large file to small file, within 15 seconds data size is better
        $file = $path;
        $filesize = filesize($file);
        $cfile = new CurlFile($file, "m4a");
        $postfields = array(
                       "sample" => $cfile,
                       "sample_bytes"=>$filesize,
                       "access_key"=>$access_key,
                       "data_type"=>$data_type,
                       "signature"=>$signature,
                       "signature_version"=>$signature_version,
                       "timestamp"=>$timestamp);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        // echo $result;
        //$response = curl_exec($ch);
        //if ($response == true) {
        //    $info = curl_getinfo($ch);
        //} else {
        //    $errmsg = curl_error($ch);
        //    print $errmsg;
        //}
        curl_close($ch);
        return json_decode($result);
    }
}
