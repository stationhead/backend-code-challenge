<?php

namespace App\Console\Commands;

use App;
use Excel;
use Carbon\Carbon;
use Illuminate\Console\Command;

use App\Services\Reports\Views\ActiveUsersReport;
use App\Services\Reports\ReportMailer;
use App\Exceptions\InvalidRequest;

class ActiveUsersReportCommand extends Command
{
    use WithRedisLock;

    protected $sleepDelayTime = 30;

    /**
     * @var UpdateAllQueuesService
     */
    protected $updateAllQueues;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:active_users_report {--type= : Daily, Weekly, or Monthly} {--email : Sends an email with the report to the specified accounts in Redis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the Active Users Report and emails them';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ReportMailer $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->option('type');
        if(!$type) throw new InvalidRequest('No type specified');

        $type = strtolower($type);

        if(!in_array($type, ['daily', 'weekly', 'monthly'])) 
                throw new InvalidRequest("{$type} is invalid type.  Must be daily, weekly, or monthly");

        $report = App::makeWith(ActiveUsersReport::class, [
            'start' => Carbon::now(),
            'end' => Carbon::now(),
            'subject' => $type,
            'timezone' => 'America/New_York'
        ]);

        switch ($type) {
            case 'daily':
                $date = Carbon::now()->subDays(1)->toDateString();
                $file = "Daily_Active_Users_{$date}";
                break;

            case 'weekly':
                $date = Carbon::now()->startOfWeek()->subDays(7)->toDateString();
                $file = "Weekly_Active_Users_{$date}";
                break;

            case 'monthly':
                $date = Carbon::now()->subMonths(1)->format('M_Y');
                $file = "Monthly_Active_Users_{$date}";
                break;
        }

        $file .= '.xlsx';

        Excel::store($report, $file, 'local');

        if($this->option('email'))
        {
            $path = base_path("storage/app/{$file}");
            $this->mailer->execute($path, $type);
            if(file_exists($path)) unlink($path);
        }
    }
}
