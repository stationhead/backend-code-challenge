<?php

namespace App\Events\Logs;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

use App\Models\Account;

class LogEvent extends Event
{
    use SerializesModels;

    public $log;
    public $table;
    public $multi;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $log, string $table, bool $multi, $addEventTime = true)
    {
        $this->table = $table;
        $this->log = $log;
        $this->multi = $multi;

        if(!$addEventTime) return;

        if($this->multi)
        {
            foreach ($log as $key => $value) {
                $this->log[$key]['event_time'] = date("Y-m-d H:i:s");
            }
        }else {
            $this->log['event_time'] = date("Y-m-d H:i:s");
        }
    }
}
