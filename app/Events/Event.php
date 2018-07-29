<?php

namespace App\Events;

use App\Events\Logs\LogEvent;

abstract class Event
{
    public function onQueue()
    {
        return SHQueue('socket_events');
    }

    public function broadcastWhen()
    {
        return count($this->broadcastOn()) > 0;
    }

    protected function logEvent(array $data, string $table, $addEventTime = true)
    {
        $multi = count($data) > 0 && is_iterable(reset($data));

        event(new LogEvent($data, $table, $multi, $addEventTime));
    }
}
