<?php

namespace App\Listeners;

use App\Events\NotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Helpers\ProcessNotificationHelper;

class SendNotification {

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NotificationEvent  $event
     * @return void
     */
    public function handle(NotificationEvent $event) {
        \Log::info('=========in notification events========');
        \Log::info('========event data==============');
        \Log::info($event->data);
        \Log::info('========event inputs==============');
        \Log::info($event->inputs);
        $sendNotification = true;
        $data = $event->data;
        $inputs = $event->inputs;
        $inputs['login_user_type'] = 'customer';
        $inputs['freelancer_uuid'] = \App\Helpers\CommonHelper::getFreelancerUuidByid($inputs['freelancer_id']);
        $inputs['customer_uuid'] = \App\Helpers\CommonHelper::getCutomerUUIDByid($inputs['customer_id']);
        if ((isset($data['purchased_package_uuid'])) && (!empty($data['purchased_package_uuid']))) {
            // send notification for package
            if (isset($data['appointment_uuid'])) {
                // its appointment package
                $sendNotification = ProcessNotificationHelper::sendAppointmentNotification($data, $inputs);
            } else {
                // its class package
                $inputs['booking'][0] = $data;
                $sendNotification = ProcessNotificationHelper::sendMultipleClassBookingNotification($inputs);
            }
        } elseif ((isset($data['appointment_uuid'])) && (empty($data['purchased_package_uuid']))) {
            // single appointment
            $sendNotification = ProcessNotificationHelper::sendAppointmentNotification($data, $inputs);
        } elseif ((isset($data['class_booking_uuid'])) && (empty($data['purchased_package_uuid']))) {
            // single class booking
            $sendNotification = ProcessNotificationHelper::sendClassBookingNotification($data, $inputs);
        } elseif ((isset($data['subscription_uuid'])) && (!empty($data['subscription_uuid']))) {
            // subscription case
            \Log::info('=========subscription notification case========');
            $inputs['subscriber_id'] = $inputs['customer_id'];
            $inputs['subscribed_id'] = $inputs['freelancer_id'];
            $sendNotification = ProcessNotificationHelper::sendSubscriberNotification($inputs, $data);
        }
        elseif ((isset($data['purchased_premium_folders_uuid'])) && (!empty($data['purchased_premium_folders_uuid']))) {
            $sendNotification = ProcessNotificationHelper::sendPremiumFolderNotification($data, $inputs);
        }
        return $sendNotification;
    }

}
