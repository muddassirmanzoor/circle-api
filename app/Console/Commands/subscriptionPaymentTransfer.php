<?php

namespace App\Console\Commands;

use App\FreelancerEarning;
use App\Helpers\AfterPayment\Transition\Transition;
use App\payment\checkout\Checkout;
use App\Purchases;
use App\PurchasesTransition;
use App\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class subscriptionPaymentTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:paymentissue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $subscriptions = Subscription::where('payment_status', 'pending_payment')
            ->where('subscription_date', '<=', Carbon::now()->addMinutes(-1))
            ->get();
        foreach ($subscriptions as $subscription) {
            $purchaseEntry = Purchases::where('subscription_id', $subscription->id)->orderByDesc('id')->first();
            $subscriptionStatus = 'pending_payment';
            $earningStatus = 'pending';
            if ($purchaseEntry) {
                $purchaseStatus = 'pending';
                $purchaseTransaction = PurchasesTransition::where('purchase_id', $purchaseEntry->id)->first();
                if ($purchaseTransaction) {
                    $paymentDetail = Checkout::getPaymentDetail($purchaseTransaction->checkout_transaction_id, 'payments');
                    if ($paymentDetail->status == 'Captured') {
                        PurchasesTransition::where('id', $purchaseTransaction->id)->update(Transition::updatePurchaseTransition($paymentDetail, ''));
                        $purchaseStatus = 'completed';
                        $subscriptionStatus = 'captured';
                        $earningStatus = 'completed';
                    }else{
                        $purchaseStatus = 'rejected';
                        $subscriptionStatus = 'failed';
                        $earningStatus = 'failed';
                    }
                }
                $purchaseEntry->status = $purchaseStatus;
                $purchaseEntry->save();
            }

            $freelancerEarning = FreelancerEarning::where('subscription_id', $subscription->id)->first();
            if ($freelancerEarning) {
                $freelancerEarning->transfer_status = $earningStatus;
                $freelancerEarning->save();
            }
            $subscription->payment_status = $subscriptionStatus;
            $subscription->save();
        }
    }
}
