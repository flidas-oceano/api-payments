<?php

namespace App\Console;

use App\Http\Controllers\CronosController;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('telescope:prune --hours=48')->daily();
        $schedule->command('passport:purge')->hourly();
       /*  $schedule->call(function () {
            $response = Http::get('https://oceanomedicina.net/api-payments/public/api/processElements');
            return response()->json($response);
        })->everyThirtyMinutes(); //->everyMinute(); everyThirtyMinutes // */
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
