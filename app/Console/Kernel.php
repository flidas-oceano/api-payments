<?php

namespace App\Console;

use App\Console\Commands\MpCommand;
use App\Console\Commands\GitPullAndCleanLaravelLogs;
use App\Console\Commands\RebillCommand;
use App\Console\Commands\StripeCommand;
use App\Http\Controllers\CronosController;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        GitPullAndCleanLaravelLogs::class,
        MpCommand::class,
        StripeCommand::class,
        RebillCommand::class,
    ];

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

        $schedule->call(function () {
            $response = Http::get('https://oceanomedicina.net/api-payments/public/api/processElements');
            return response()->json($response);
        })->everyFifteenMinutes(); //->everyMinute(); everyFifteenMinutes //


        $schedule->call(function () {
            $response = Http::get('https://oceanomedicina.net/api-payments/public/api/rebill/checkPendingPayments');
            return response()->json($response);
        })->everyFiveMinutes();

        $schedule->command('sales-order:mp 100 1')->dailyAt('05:40:06')->timezone('America/Argentina/Buenos_Aires');
        $schedule->command('sales-order:mp 100 2')->dailyAt('05:45:06')->timezone('America/Argentina/Buenos_Aires');
        $schedule->command('sales-order:mp 100 3')->dailyAt('05:50:06')->timezone('America/Argentina/Buenos_Aires');
        $schedule->command('sales-order:mp 100 4')->dailyAt('05:55:06')->timezone('America/Argentina/Buenos_Aires');
        $schedule->command('sales-order:rebill 100 1')->dailyAt('06:00:06')->timezone('America/Argentina/Buenos_Aires');
        $schedule->command('sales-order:rebill 100 2')->dailyAt('06:05:06')->timezone('America/Argentina/Buenos_Aires');
        $schedule->command('sales-order:rebill 100 3')->dailyAt('06:10:06')->timezone('America/Argentina/Buenos_Aires');
        $schedule->command('sales-order:rebill 100 4')->dailyAt('06:15:06')->timezone('America/Argentina/Buenos_Aires');
        $schedule->command('sales-order:stripe')->dailyAt('06:20:06')->timezone('America/Argentina/Buenos_Aires');
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
