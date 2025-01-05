<?php

namespace App\Console;

//use App\Console\Commands\AppointmentReminderEmail;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ResubscribeUser::class,
        Commands\RemoveExpiredSubscription::class,
        Commands\AppointmentReminderEmail::class,
        Commands\PendingBookingsReminder::class,
        Commands\PremiumFolderStatus::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        Log::channel('cronjobs')->info("Started Jobs");
        // $schedule->command('inspire')
        //          ->hourly();
        //        $schedule->command('appointment:reminder')->hourly();
        //        $schedule->command('pending_booking:reminder')->hourly();
        Log::channel('cronjobs')->info("    Started Job - appointment:reminder");
        $schedule->command('appointment:reminder')
            ->name('appointment:reminder')
            ->cron('* * * * *')
            ->onOneServer();

        Log::channel('cronjobs')->info("    Started Job - pending_booking:reminder");
        $schedule->command('pending_booking:reminder')
            ->name('pending_booking:reminder')
            ->cron('* * * * *')
            ->appendOutputTo('storage/logs/push_notification.txt')
            ->onOneServer();

        Log::channel('cronjobs')->info("    Started Job - change_status:reminder");
        $schedule->command('change_status:reminder')
            ->name('change_status:reminder')
            ->cron('* * * * *')
            ->onOneServer();

        Log::channel('cronjobs')->info("    Started Job - paymentreq:cron");
        $schedule->command('paymentreq:cron')
            ->name('paymentreq:cron')
            ->cron('* * * * *')
            ->onOneServer();

        Log::channel('cronjobs')->info("    Started Job - subscription:remove");
        $schedule->command('subscription:remove')
            ->name('subscription:remove')
            ->daily()
            ->onOneServer();

        Log::channel('cronjobs')->info("    Started Job - recurring_payments:subscription");
        $schedule->command('recurring_payments:subscription')
            ->name('recurring_payments:subscription')
            ->everyMinute()
            ->appendOutputTo('storage/logs/recurring_payments.txt')
            ->onOneServer();

        Log::channel('cronjobs')->info("    Started Job - premium_folder:change_status");
        $schedule->command('premium_folder:change_status')
            ->name('premium_folder:change_status')
            ->everyMinute()
            ->appendOutputTo('storage/logs/premium_folder.txt')
            ->onOneServer();

        Log::channel('cronjobs')->info("    Started Job - subscription fixation");
        $schedule->command('subscription:paymentissue')
            ->name('subscription:paymentissue')
            ->everyMinute()
            ->appendOutputTo('storage/logs/subscriptionfixation.txt')
            ->onOneServer();


        Log::channel('cronjobs')->info("Finished Jobs");

        //        $schedule->command('pending_booking:reminder')
        //                ->hourly();
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
