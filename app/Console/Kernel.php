<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    public function commands()
    {
        $this->load(__DIR__.'/Commands');
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        // Delete Laravel Log Files.
        $schedule->exec('stationhead:log_cleanup')
            ->everyThirtyMinutes();

        if (config('stationhead.environment') == 'local' || config('stationhead.box') == 'app' && config('stationhead.environment') != 'local') {

            $cutoff = configOrFail("stationhead.history.cutoff_time");

            $schedule->command('stationhead:cleanup_track_play_history')
                ->dailyAt($cutoff);

            $schedule->command('stationhead:cleanup_station_listener_history')
                ->dailyAt($cutoff);

            $schedule->command('stationhead:cleanup_broadcast_history')
                ->dailyAt($cutoff);

            $schedule->command('stationhead:cleanup_track_bite_history')
                ->dailyAt($cutoff);

            $schedule->command('stationhead:cleanup_station_share_history')
                ->dailyAt($cutoff);

            $schedule->command('stationhead:process_queue')
                ->everyMinute();

            $schedule->command('stationhead:cleanup_expired_shows')
                ->everyMinute();

            $schedule->command('stationhead:refresh_influencer_invite_codes')
                ->daily();

            $schedule->command('stationhead:expire_old_pending_users')
                ->daily();

            $schedule->command('stationhead:verify_contextual_data')
                ->daily();

            $schedule->command('stationhead:verify_account_follow_counts')
                ->daily();

            $schedule->command('stationhead:expire_sent_invite_codes')
                ->hourly();

            $schedule->command('stationhead:clear_ghosts')
                ->hourly();

            $schedule->command('stationhead:fetch_apple_library')
                ->dailyAt('06:00');

            $schedule->command('stationhead:active_users_report --email --type=daily')
                ->dailyAt('06:00');

            $schedule->command('stationhead:active_users_report --email --type=weekly')
                ->weekly()->mondays()->at('06:15');

            $schedule->command('stationhead:active_users_report --email --type=monthly')
                ->monthlyOn(1, '06:30');

            $schedule->command('stationhead:calculate_leaderboard')
                ->dailyAt($this->fromDatetime(
                    atLeaderboardCutoffTime()->addMinutes(2)
                ));

            $schedule->command('stationhead:calculate_discover_trending')
                ->everyTenMinutes();

            $schedule->command('stationhead:notify_leaders')
                ->dailyAt($this->newYorkToUTC(configOrFail("stationhead.leaderboard.notification_time")));
            
            $schedule->command('stationhead:notify_shows_started')
                ->everyMinute();
        }
    }

    private function newYorkToUTC($hhmm)
    {
        list($hour, $min) = explode(":", $hhmm);
        return $this->fromDatetime(
            Carbon::now("America/New_York")
                ->hour($hour)
                ->minute($min)
                ->timezone("UTC")
        );
    }

    private function fromDatetime(Carbon $dt)
    {
        $dt->timezone("UTC");
        return "{$dt->hour}:{$dt->minute}";
    }
}
