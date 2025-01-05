<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Pusher\PushNotifications\PushNotifications;

class PusherHelper {
    /*
      |--------------------------------------------------------------------------
      | PusherHelper that contains all the Android Pusher methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Android Pusher processes
      |
     */

    public static function getBeamsClient() {
        return new PushNotifications(array(
            "instanceId" => env('PUSHER_PUSH_INSTANCE_ID'),
            "secretKey" => env('PUSHER_PUSH_SECRET_KEY'),
        ));
    }

    public static function sendAndroidNotification($userUUID, $data = null) {
        \Log::info('<========user id in android notification==========>');
        \Log::info(print_r($userUUID, true));
        $beamsClient = PusherHelper::getBeamsClient();
        $profile = \App\Customer::getSingleCustomerDetail('user_id', $userUUID);
        if (!empty($profile)) {
            $profile['profile_uuid'] = (isset($profile['customer_uuid']) && (!empty($profile['customer_uuid']))) ? $profile['customer_uuid'] : null;
        }
        \Log::info('<======= customer profile =========>');
        \Log::info(print_r($profile, true));
        if (empty($profile)) {
            $profile = \App\Freelancer::getFreelancerDetail('user_id', $userUUID);
            $profile['profile_uuid'] = ((isset($profile['freelancer_uuid'])) && (!empty($profile['freelancer_uuid']))) ? $profile['freelancer_uuid'] : null;

            \Log::info('<======= freelancer profile =========>');
            \Log::info(print_r($profile, true));
        }
        $stringUserId = $profile['profile_uuid'];
        Log::info('Pusher Android Sending Notification ', [
            'user_uuid' => $stringUserId,
            'data' => $data,
        ]);
        try {
            $publishResponse = $beamsClient->publishToUsers(
//                    array("6a67dc90-1994-4a93-ba18-c5e9fa1189f4", "8a0c07a0-faad-4eff-a481-18653c08b265"),
//                    array(
//                        "fcm" => array(
//                            "notification" => array(
//                                "title" => "Hi!",
//                                "body" => "This is my first Push Notification!"
//                            )
//                        )
//            ));

                    array($stringUserId),
                    array(
                        "fcm" => array(
//                            "notification" => array(
//                                "title" => $data['message'] ?? 'You have a notification',
//                                "body" => ''
//                            ),
                            "data" => [
                                'data' => $data
                            ]
                        ),
            ));
            \Log::info('<=========== beams client response ========>');
            \Log::info(print_r($publishResponse, true));
        } catch (\Exception $e) {
            Log::info('Pusher Android Notification Error: ', [
                'exception' => $e,
                'user_uuid' => $stringUserId,
                'data' => $data,
            ]);
        }
    }

}
