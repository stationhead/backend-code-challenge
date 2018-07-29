<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;

use Illuminate\Support\Facades\Redis;

class Job
{
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "onQueue" and "delay" queue helper methods.
    |
    */

    use Queueable;

    public function getElapsedTimeInMS(): int
    {
        return $this->jobEndedAt - $this->jobStartedAt;
    }

    protected function jobFinished()
    {
        $this->clearProperties();
    }

    private function clearProperties()
    {
        foreach($this as $key => $property) {
            unset($this->$key);
        }
    }
}
