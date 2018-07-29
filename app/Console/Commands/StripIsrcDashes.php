<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\Track;

class StripIsrcDashes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:strip_isrc_dashes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'strips any dashes from isrcs in tracks table';

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
        $toChange = Track::where('isrc','like','%-%')->get();
        print_r("Found ".count($toChange)." tracks that have dashes\n");

        foreach ($toChange as $key => $record) {
            $isrc = $record->isrc;
            $strippedIsrc = $this->stripDashes($record->isrc);
            $mirroredResult = Track::where('isrc', '=', $strippedIsrc)->get();

            if(count($mirroredResult)>0){
                error_log("$key: ** found duplicate on $isrc ... skipping");
            }else{
                error_log("$key: updating $isrc ...");
                $record->isrc = $strippedIsrc;
                $record->save();
            }
        }
    }

    private function stripDashes(string $input):string {
        return str_replace('-', '', $input);
    }
}
