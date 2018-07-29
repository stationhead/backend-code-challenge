<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\RotationsTrack;

use \App\Models\Image as STHImage;
use Storage, Image;

class CreateNewImageSizes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:create_new_image_sizes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'resizes originals to new image specs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $allSizes = config('stationhead.images.size_groups');

        $s3 = Storage::cloud();
        $closure = function($constraint){
            $constraint->aspectRatio();
        };

        $images = \App\Models\Image::where('url', 'LIKE', '%original%')->orWhere('url', 'LIKE', '%defaults%')->get();
        $total = $images->count();

        foreach ($images as $key => $image) {

            $models = STHImage::where([
                'owner_type' => $image->owner_type,
                'owner_id' => $image->owner_id
            ])->get();

            //only download image when it's actually needed
            $img = null;

            //create a list of target image sizes
            $model_name = last(explode('\\', $image->owner_type));
            if($model_name === 'DEFAULT') continue;
            
            $sizeArrays = $allSizes[$model_name];
            $allSizeArrays = array_merge($sizeArrays['async'], $sizeArrays['sync']);
            $newSizes = array_column($allSizeArrays, 0);

            $insert = [];

            foreach ($newSizes as $size) {

                //skip size if already present
                if($models->contains(function($el) use ($size) {
                    return (
                        $el->width == $size
                    );
                })) continue;

                //download image if it hasn't already been downloaded
                if(!$img) $img = Image::make($image->url);

                //resize according to new desired sizes
                preg_match('/.*\.(.*)$/', $image->url, $out);
                $extension = $out[1];

                $img->resize($size, null, $closure)->save('/tmp/resized_new_tmp');
                
                //reupload to s3
                $imageFileName = timeInMs(). '.' . $extension;
                $path = $size . '/' . $size . '/'  . $imageFileName;
                $s3->put($path, $img->__toString(), 'public');

                //save the entries in the Images table
                $entry = [
                    'owner_id' => $image->owner_id,
                    'owner_type' => $image->owner_type,
                    'width' => $size,
                    'height' => $size,
                    'url' => $s3->url($path),
                    'source' => $image->source,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                array_push($insert, $entry);
            }

            if(count($insert) > 0) STHImage::insert($insert);

            $num = $key + 1;
            error_log("Processed {$num} of {$total}");
        }
    }
}
