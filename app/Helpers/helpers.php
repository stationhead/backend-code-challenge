<?php

use Carbon\Carbon;

use App\Models\Account;


use App\Services\Channels\ChannelManager;
use Doctrine\Instantiator\Exception\UnexpectedValueException;
use Illuminate\Foundation\Http\FormRequest as Request;
use App\Exceptions\UnsupportedVersion;

if(! function_exists("configOrFail"))
{
    function configOrFail($key, $default = null)
        {
            $val = config($key, $default);
            if (is_null($val) && is_null($default)) {
                throw new \UnexpectedValueException("Invalid call to config: {$key} missing or null.");
            }
            return $val;
        }
}

/**
 * News up the Stationhead Response class.
 *
 */
if (! function_exists('STHResponse'))
{
    function STHResponse()
    {
        return App::make('App\Responses\STHResponse');
    }
}

/**
 * Create a new Response Builder class.
 *
 */
if (! function_exists('STHResponseBuilder'))
{
    function STHResponseBuilder()
    {
        return App::make('App\Responses\STHPaginatedResponseBuilder');
    }
}

/**
 * News up the Stationhead Cache Manager.
 *
 */
if (! function_exists('STHCache'))
{
    function STHCache($service)
    {
        return App::make($service);
    }
}

/**
 * Returns the current time in milliseconds.
 *
 */
if (! function_exists('timeInMS'))
{
    function timeInMs()
    {
        $timeparts = explode(" ",microtime());
        return (int) ((int) $timeparts[1] * 1000 + ((int) ($timeparts[0] * 1000)));
    }
}

/**
 * Gets the session id
 *
 */
if (! function_exists('SessionID'))
{
    function SessionID()
    {
        $user = AuthUser();
        if(!$user) return 0;
        return App::make('App\Services\Users\GetSessionID')->execute($user);
    }
}



/**
 * Formats the Spotify URI for output to API.
 *
 */
if (! function_exists('spotifyURIFormat'))
{
    function spotifyURIFormat($uri)
    {
        return sprintf("%s:%s:%s", 'spotify', 'track', $uri);
    }
}

/**
 * Formats millisecond timestamps in floating point seconds.
 *
 */
if (! function_exists('TimeInFloatingSeconds'))
{
    function TimeInFloatingSeconds($timeInMS)
    {
        return number_format($timeInMS / 1000, 3,'.', '');
    }
}

/**
 * Get DateTime object from request.
 *
 */
if (! function_exists('timeFromRequest'))
{
    function timeFromRequest($request, $floatingPoints = false)
    {
        $ms = $request->server('REQUEST_TIME');

        if (!$floatingPoints) {
            return \Carbon\Carbon::createFromTimestamp($ms);
        }

        return DatetimeWithFloatingSeconds(TimeInFloatingSeconds($ms));
    }
}

/**
 * Formats string timestamps with accurate floating point seconds
 *
 */
if (! function_exists('DatetimeWithFloatingSeconds'))
{
    function DatetimeWithFloatingSeconds($timeInFloatingSeconds = null)
    {
        $time = ($timeInFloatingSeconds) ?: TimeInFloatingSeconds(timeInMs());
        $float = explode('.', $time)[1];

        $dateTime = \Carbon\Carbon::createFromTimestamp($time)->toDateTimeString();
        return $dateTime.".{$float}";
    }
}

if (! function_exists('AuthUser'))
{
    function AuthUser()
    {
        return Auth::user();
    }
}

if (! function_exists('AuthAdmin'))
{
    function AuthAdmin()
    {
        return \App\Models\Admin\Admin::find(Auth::id());
    }
}

if (! function_exists('AuthAccount'))
{
    function AuthAccount()
    {
        if (AuthAccountExists()) {
            return Auth::user()->currentAccount();
        }

        throw new \App\Exceptions\ModelDoesNotExist('No current account','CurrentAccountUser');
    }
}

if (! function_exists('AuthAccountExists'))
{
    function AuthAccountExists()
    {
        if(Auth::user() && get_class(Auth::user()) === 'App\Models\Admin\Admin') return false;

        if (Auth::user() && Auth::user()->accounts->isEmpty()) {
            throw new \App\Exceptions\ModelDoesNotExist("User has not created an account", 'AccountUser');
        }

        return (Auth::user() && Auth::user()->currentAccount());
    }
}

if (! function_exists('AuthBroadcast'))
{
    function AuthBroadcast()
    {
        if ($broadcast = AuthAccount()->station->broadcast) {
            return $broadcast;
        } else {
            throw new \App\Exceptions\ModelDoesNotExist("You must be currently Broadcasting.");
        }
    }
}

if (! function_exists('SnakeToCamel'))
{
    function SnakeToCamel($string)
    {
        return preg_replace_callback("/(?:^|_)([a-z])/", function($matches) {
            return strtoupper($matches[1]);
        }, $string);
    }
}

if (! function_exists('CamelToSnake'))
{
    function CamelToSnake($string)
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $string)), '_');
    }
}

if (! function_exists('SHQueue'))
{
    function SHQueue($name)
    {
        return sprintf("%s_%s", strtoupper(config('stationhead.environment')), config('stationhead.queues.'.$name));
    }
}

if (! function_exists('setJwtAuthModel'))
{
    function setJwtAuthModel($class)
    {
        Config::set('jwt.user', $class);
    }
}

if (! function_exists('setDefaultGuard'))
{
    function setDefaultGuard(string $guard)
    {
        auth()->setDefaultDriver($guard);
    }
}

if (! function_exists('modelAttributes'))
{
    function modelAttributes($model, $array = true)
    {
        return json_decode(json_encode($model),$array);
    }
}

if (! function_exists('getFieldBasedOnService'))
{
    function getFieldBasedOnService($headers = [])
    {
        if (array_key_exists($headers["Service"])) {
            switch ($headers["Service"]) {
                case 'Spotify':
                    return "spotify_id";
            }
        } else {
            throw new InvalidRequest;
        }
    }
}

if (! function_exists('hasPresenceChannel'))
{
    function hasPresenceChannel(Account $account)
    {
        if ($user = $account->currentUser()) {
            return App::make(ChannelManager::class)->isOccupied('presence-'.config('stationhead.environment').'_user_'.$user->id);
        }

        return false;

    }
}

if (! function_exists("atLeaderboardCutoffTime"))
{
    function atLeaderboardCutoffTime()
    {
        $cutoff = configOrFail("stationhead.leaderboard.cutoff_time");
        list($hour, $min) = explode(":", $cutoff);
        
        return Carbon::now()->timezone("America/New_York")
            ->hour((integer) $hour)
            ->minute((integer) $min)
            ->timezone("UTC");
    }
}

if (! function_exists("atHistoryCutoffTime"))
{
    function atHistoryCutoffTime(Carbon $day)
    {
        $cutoff = configOrFail("stationhead.history.cutoff_time");
        list($hour, $min) = explode(":", $cutoff);
        
        return $day->timezone("America/New_York")
            ->hour((integer) $hour)
            ->minute((integer) $min)
            ->timezone("UTC");
    }
}

if (! function_exists("nextHistoryCutoff"))
{
    function nextHistoryCutoff()
    {
        $cutoff = atHistoryCutoffTime(Carbon::now());
        return ($cutoff->gt(Carbon::now())) ? $cutoff : $cutoff->addDay();
    }
}

if ( !function_exists("shortHash")) {
    function shortHash($hashable){

        $arrayReducer = function($array) use (&$arrayReducer) {
            return array_reduce(
                $array,
                function($string, $obj) use (&$arrayReducer) {
                    if( is_array($obj)) {
                        $obj = $arrayReducer($obj);
                    }
                    return $string . ":" . (string) $obj; 
                },
                ""
            );
        };

        if ($hashable instanceof Request) {
            $hashable = $hashable->all();
        }

        if (is_array($hashable)) {
            $hashable = $arrayReducer($hashable);
        }

        $slug = substr(
            strtr(
                base64_encode(
                    hash("md5", (string) $hashable, true)
                ),
                ["+" => "", "/" => "", "=" => ""]
            ),
            2, 11
        );

        return $slug;

    }
}

if ( !function_exists("checkVersion")) {
    function checkVersion(String $requires, String $value)
    {
        if( $value == "EXPERIMENTAL") {
                return true;
        }
        if ($requires == "PENDING") {
            throw new UnsupportedVersion("This feature is pending not currently supported.");
        }
        $requiredParts = explode(".", $requires);
        $valueParts = explode(".", $value);
        if (count($requiredParts) != count($valueParts)) {
            throw new UnsupportedVersion("Your version of Stationhead is out of date. Please visit the App Store and update");
        }
        foreach ($requiredParts as $idx => $requiredValue) {
            if ((integer) $valueParts[$idx] > (integer) $requiredValue) {
                return true;
            }
            if ((integer) $valueParts[$idx] < (integer) $requiredValue) {
                throw new UnsupportedVersion("Your version of Stationhead is out of date. Please visit the App Store and update");
            }
        }
        return true;   
    }
}

