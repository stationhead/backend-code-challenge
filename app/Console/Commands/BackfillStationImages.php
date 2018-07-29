<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App;
use DB;

use Illuminate\Console\Command;

use App\Services\Station\CreateAndStoreAlbumCollage;
use App\Models\Account;
use App\Models\Station;

class BackfillStationImages extends Command
{

    protected $signature = 'stationhead:backfill_station_images {start_at=0}';

    protected $description ='Creates station collage images for accounts that have none';

    public function handle()
    {
        $count = 0;
        $startAt = $this->argument('start_at');
        $this->info("Beginning Station backfill");
        while (true) {
            $accounts = Account::select("accounts.*")
                ->join("stations", "stations.owner_id","accounts.id")
                ->leftJoin("images", function($join) {
                    $join->on("images.owner_id", "stations.id");
                    $join->on("images.owner_type", DB::raw('"' . Station::class . '"'));
                })
                ->whereNull("images.id")
                ->orderBy("accounts.id", "asc")
                ->where("accounts.id",  ">", $startAt)
                ->take(5)
                ->get();

            if ($accounts->isEmpty()) {
                    $this->info("Total {$count} stations processed.");
                    return;
            }
            
            foreach ($accounts as $account) {
                $startAt = $account->id;
                $user = $account->currentUser();
                if (is_null($user)) {
                    $this->info("Abandoned Account #{$account->id}: {$account->handle}");
                    continue;
                }
                $service = ( is_null($user->spotify_token)) ? "AppleMusic" : "Spotify";

                $maker = App::make(CreateAndStoreAlbumCollage::class);
                $maker->execute($account->station, $service);
                $count++;
                $this->output->write(".");
            }
            if (($count % 50) > 45) {
                $this->info("{$count} stations processed so far . . .");
            }
            sleep(15);
        }
    }
}
