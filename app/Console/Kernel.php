<?php

namespace App\Console;

use App\Console\Commands\GetWeatherInfo;
use App\Console\Commands\PollAISMT;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        PollAISMT::class,
        GetWeatherInfo::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //  $schedule->command('cdt:scrape-mt-imo')->days([1, 3, 5]);
        $filePath = storage_path('logs/ais_mt_poll.log');
        $schedule->command('cdt:poll-ais-mt')
            ->everyMinute()
            ->runInBackground()
            ->appendOutputTo($filePath);

        $schedule
            ->command('get:weather-info')
            ->cron('* */4 * * *')
            ->appendOutputTo(storage_path('logs/get-weather-info.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
