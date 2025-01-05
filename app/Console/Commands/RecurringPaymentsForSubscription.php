<?php

namespace App\Console\Commands;

use App\Helpers\SubscriptionHelper;
use Illuminate\Console\Command;
use App\Subscription;
use Illuminate\Support\Facades\Log;

class RecurringPaymentsForSubscription extends Command {

    /**

     * @author ILSA Interactive

     * @var string

     */
    protected $signature = 'recurring_payments:subscription';

    /**

     * The console command description.

     *

     * @var string

     */
    protected $description = 'This command will auto track ended subscription and go for payment';

    /**

     * Create a new command instance.

     *

     * @return void

     */

    public function __construct() {

        parent::__construct();
    }

    /**

     * Execute the console command.

     *

     * @return mixed

     */
    public function handle() {
        Log::info('Recuuring payments cron job');
        try {
            //code...
            Log::channel('recurring_payments')->debug('Recurring Payment Cron Job Started');
            SubscriptionHelper::recurringSubscriptionPayment();
        } catch (\Throwable $th) {
            Log::channel('recurring_payments')->debug($th->getMessage());
        }
    }



}
