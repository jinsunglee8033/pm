<?php

namespace App\Console;

use App\Model\Commission;
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
        //
        Commands\TestBroadcast::class,
        Commands\RefreshPortInStatus::class,
        Commands\CheckManualRTR::class,
        Commands\CreateBill::class,
        Commands\RegenComm::class,
        Commands\EmidaImport::class,
        Commands\GSSImport::class,
        Commands\ReupImport::class,
        Commands\ApplyQueue::class,
        Commands\ACHPost::class,
        Commands\ACHConfirm::class,
        Commands\ACHBounce::class,
        Commands\ACHWeekday::class,
        Commands\ATTBatchSchedule::class,
        Commands\ActivationLimit::class,
        Commands\Daily::class,
        Commands\CashPickup::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        $schedule->command('emida:import')->daily();

        $schedule->command('reup:import')->daily();

        $schedule->command('daily:process')->daily();

        $schedule->command('port-in:check')->everyFiveMinutes();

//        $schedule->command('manual-rtr:check')->everyMinute();
        
        // $schedule->command('queue:apply-pending')->everyMinute();

        $schedule->command('bill:create')->weeklyOn(1, '04:00');

        $schedule->command('ach:weekday')->cron('00 04 * * 2,3,4,5');

        $schedule->command('ach:post')->cron('00 10 * * 1,2,3,4,5');

        $schedule->command('ach:confirm')->cron('00 9,12,15,17,18,21 * * *');

        $schedule->command('ach:bounce')->cron('00 10,13,16,17,18,19,22 * * *');

        $schedule->command('gss:import')->daily();

        $schedule->command('att:batch-schedule')->dailyAt('07:00');

        $schedule->command('activation:limit')->everyFiveMinutes();

        $schedule->command('daily:cash-pickup')->dailyAt('04:30');


    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
