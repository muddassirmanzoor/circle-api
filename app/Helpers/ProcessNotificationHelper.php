<?php

namespace App\Helpers;

use App\Appointment;
use App\User;
use App\UserDevice;
use App\Freelancer;
use App\Customer;
use App\Notification;
use App\NotificationSetting;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Validator;

/*
  All methods related to user notifications will be here
 */

class ProcessNotificationHelper {

    public static function processFreelancer($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['freelancer_uuid'] = (!empty($data['freelancer_uuid'])) ? $data['freelancer_uuid'] : "";
            $response['freelancer_id'] = $data['id'];
            $response['user_id'] = $data['user_id'];
            $response['first_name'] = (!empty($data['first_name'])) ? $data['first_name'] : "";
            $response['last_name'] = (!empty($data['last_name'])) ? $data['last_name'] : "";
            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
            //            $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['profile_image']);
        }
        return $response;
    }

    public static function processCustomer($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['customer_uuid'] = $data['customer_uuid'];
            $response['customer_id'] = $data['id'];
            $response['user_id'] = $data['user_id'];
            $response['first_name'] = (!empty($data['first_name'])) ? $data['first_name'] : "";
            $response['last_name'] = (!empty($data['last_name'])) ? $data['last_name'] : "";
            //            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['profile_image'] : null;
            //            $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($data['profile_image']);
            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['profile_image'] : null;
        }
        return $response;
    }

    public static function processChatFreelancer($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['uuid'] = !empty($data['freelancer_uuid']) ? $data['freelancer_uuid'] : "";
            $response['id'] = !empty($data['freelancer_uuid']) ? $data['freelancer_uuid'] : "";
            $response['freelancer_id'] = !empty($data['id']) ? $data['id'] : "";
            //            $response['first_name'] = (!empty($data['first_name'])) ? $data['first_name'] : "";
            //            $response['last_name'] = (!empty($data['last_name'])) ? $data['last_name'] : "";
            $response['name'] = $data["first_name"] .' '. $data['last_name'];
            $response['type'] = "freelancer";
            //            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
            //            $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['profile_image']);
            $response['image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
        }
        return $response;
    }

    public static function processChatCustomer($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['uuid'] = !empty($data['customer_uuid']) ? $data['customer_uuid'] : "";
            $response['id'] = !empty($data['customer_uuid']) ? $data['customer_uuid'] : "";
            $response['customer_id'] = !empty($data['id']) ? $data['id'] : "";
            $response['type'] = "customer";
            //            $response['first_name'] = (!empty($data['first_name'])) ? $data['first_name'] : "";
            //            $response['last_name'] = (!empty($data['last_name'])) ? $data['last_name'] : "";
            $response['name'] = $data["first_name"] .' ' .$data['last_name'];
            $response['image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['profile_image'] : null;
            //            $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($data['profile_image']);
        }
        return $response;
    }

    public static function sendFollowerNotification($inputs = [], $follower = [], $notificationType = 'new_follower') {
        $receiver_data = self::processReceiver($inputs);
        $sender_data = self::processSender($inputs);
        $data = [];
        $data['sender'] = $sender_data['sender'];
        $data['receiver'] = $receiver_data['receiver'];
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' has started following you',
            'save_message' => ' has started following you',
            'data' => $data,
            'follow_uuid' => $follower['follow_uuid'],
        ];
        //        if (!empty($receiver_data['device_token'])) {
        //        return PushNotificationHelper::send_voip_notification_to_user($receiver_data['voip_device_token'], $messageData);
        $notification_inputs = self::prepareInputs($messageData);
        $check = self::checkAndUpdateNotification($notification_inputs);
        $save_notification = Notification::addNotification($notification_inputs);
        $check_notification_setting = NotificationSetting::getSettingsWithType('user_id', $receiver_data['receiver']['user_id'], 'new_follower');
        if (!empty($check_notification_setting)) {
            //                return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
            return PushNotificationHelper::send_notification_to_user_devices($receiver_data['receiver']['user_id'], $messageData);
        }
        //            return PushNotificationHelper::send_notification_to_user("1043cb44ca36801c17aec373ff28bd0c7bfcb0ad52ccf3ce0e94928806d1d09b", $messageData);
        //        }
    }

    public static function sendAddToFavouriteNotification($inputs = [], $favourite = [], $notificationType = 'add_to_favourite')
    {
        $receiver_data = self::processReceiver($inputs);
        $sender_data = self::processSender($inputs);
        $data = [];
        $data['sender'] = $sender_data['sender'];
        $data['receiver'] = $receiver_data['receiver'];
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' has added you into Favourite',
            'save_message' => ' has added you into Favourite',
            'data' => $data,
            'favourite_uuid' => $favourite['favourite_uuid'],
        ];
        $notification_inputs = self::prepareInputs($messageData);
        $check = self::checkAndUpdateNotification($notification_inputs);
        $save_notification = Notification::addNotification($notification_inputs);
        $check_notification_setting = NotificationSetting::getSettingsWithType('user_id', $receiver_data['receiver']['user_id'], 'add_to_favourite');
        if (!empty($check_notification_setting)) {
            return PushNotificationHelper::send_notification_to_user_devices($receiver_data['receiver']['user_id'], $messageData);
        }
    }

    public static function checkAndUpdateNotification($notification_inputs = [])
    {
        $check = Notification::checkNotification($notification_inputs);
        if (!empty($check)) {
            $update = Notification::updateNotification($notification_inputs, ['is_archive' => 1]);
        }
        return true;
    }

    public static function processSender($inputs = [])
    {
        $sender = ['sender' => [], 'device_token' => ''];
        $profile = Customer::getSingleCustomer('id', $inputs['customer_id']);
        if (!empty($profile)) {
            $sender['sender'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $inputs['customer_id']);
            if (!empty($device)) {
                $sender['device_token'] = !empty($device) ? $device['device_token'] : '';
            }
        }
        return $sender;
    }

    public static function processReceiver($inputs = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];
        $profile = Freelancer::checkFreelancer('id', $inputs['freelancer_id']);
        if (!empty($profile)) {
            $receiver['receiver'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $inputs['freelancer_id']);
            $receiver['device_token'] = !empty($device) ? $device['device_token'] : '';
        }
        return $receiver;
    }

    public static function prepareInputs($messageData = [])
    {
        $notification_inputs = [];
        //        if (!empty($data)) {
        if (isset($messageData['data']['receiver']['freelancer_id'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['sender']['customer_id'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }

        $notification_inputs['message'] = $messageData['save_message'];
        $notification_inputs['notification_type'] = $messageData['type'];
        $notification_inputs['is_read'] = 0;
        if ($messageData['type'] == "new_follower") {
            $notification_inputs['notification_uuid'] = $messageData['follow_uuid'];
        }
        if ($messageData['type'] == "new_rating") {
            $notification_inputs['notification_uuid'] = $messageData['review_uuid'];
        }
        if ($messageData['type'] == "new_like") {
            $notification_inputs['notification_uuid'] = $messageData['post_uuid'];
        }
        if ($messageData['type'] == "add_to_favourite") {
            $notification_inputs['notification_uuid'] = $messageData['favourite_uuid'];
        }

        //        }
        return $notification_inputs;
    }

    public static function sendSubscriberNotification($inputs = [], $subscription = [], $notificationType = 'new_subscriber')
    {
        $receiver_data = self::processSubscriptionReceiver($inputs);
        $sender_data = self::processSubscriptionSender($inputs);
        $data = [];
        $data['sender'] = $sender_data['sender'];
        $data['receiver'] = $receiver_data['receiver'];
        $data['subscription']['subscription_uuid'] = $subscription['subscription_uuid'];
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' has bought a subscription',
            'save_message' => 'has bought a subscription',
            'data' => $data,
            'subscription_uuid' => $subscription['subscription_uuid'],
        ];
        $notification_inputs = self::prepareSubscriptionInputs($messageData);
        $save_notification = Notification::addNotification($notification_inputs);
        return PushNotificationHelper::send_notification_to_user_devices($receiver_data['receiver']['user_id'], $messageData);
        //        if (!empty($receiver_devices)) {
        ////        return PushNotificationHelper::send_voip_notification_to_user($receiver_data['voip_device_token'], $messageData);
        //
        ////            $check = self::checkAndUpdateNotification($notification_inputs);
        //
        ////            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
        //        }
    }

    public static function sendSubscriberReminderNotification($inputs = [], $subscription = [], $notificationType = 'subscription_renewal_capture', $walletRecords = null)
    {

        $receiver_data = self::processRecurringSubscriptionReceiver($inputs);

        $sender_data = self::processRecurringSubscriptionSender($inputs);

        $data = [];
        $data['sender'] = $sender_data['sender'];
        $data['receiver'] = $receiver_data['receiver'];
        $data['subscription']['subscription_uuid'] = $subscription['subscription_uuid'];

        $msg = ($walletRecords != null) ? $walletRecords['msg'] : 'your subscription going to expire please renew it';

        $messageData = [
            'type' => $notificationType,
            'message' => $receiver_data['receiver']['first_name'] . ' ' . $msg,
            'save_message' => $msg,
            'data' => $data,
            'subscription_uuid' => $subscription['subscription_uuid'],
        ];

        $notification_inputs = self::prepareRecurringSubscriptionInputs($messageData);

        $save_notification = Notification::addNotification($notification_inputs);

        return PushNotificationHelper::send_notification_to_user_devices($receiver_data['receiver']['user_id'], $messageData);
    }

    public static function updateSubscriptionNotification($inputs = [], $notificationType = 'subscription_update')
    {

        $receiver_data = self::processSubscriptionReceiver($inputs);

        $sender_data = self::processSubscriptionSender($inputs);

        $data = [];
        $data['sender'] = $sender_data['sender'];
        $data['receiver'] = $receiver_data['receiver'];
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' ' . $inputs['message'],
            'save_message' => $inputs['message'],
            'data' => $data,
            'subscription_uuid' => $inputs['subscription_uuid'],
        ];
        $notification_inputs = self::prepareSubscriptionInputs($messageData);
        $save_notification = Notification::addNotification($notification_inputs);
        return PushNotificationHelper::send_notification_to_user_devices($inputs['subscribed_id'], $messageData);
    }

    public static function processSubscriptionSender($inputs = [])
    {
        $sender = ['sender' => [], 'device_token' => ''];

        $profile = Customer::getSingleCustomer('id', $inputs['subscriber_id']);

        if (empty($profile)) {
            $profile = Freelancer::checkFreelancer('id', $inputs['subscriber_id']);
        }
        if (!empty($profile['customer_uuid'])) {
            $sender['sender'] = self::processCustomer($profile);
        }
        if (!empty($profile['freelancer_uuid'])) {
            $sender['sender'] = self::processFreelancer($profile);
        }
        $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
        if (!empty($device)) {
            $sender['device_token'] = $device['device_token'];
        }

        return $sender;
    }

    public static function processRecurringSubscriptionSender($inputs = [])
    {
        $sender = ['sender' => [], 'device_token' => ''];

        $profile = Freelancer::checkFreelancer('id', $inputs['subscribed_id']);

        if (!empty($profile['freelancer_uuid'])) {
            $sender['sender'] = self::processFreelancer($profile);
        }

        $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
        if (!empty($device)) {
            $sender['device_token'] = $device['device_token'];
        }

        return $sender;
    }

    public static function processSubscriptionReceiver($inputs = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];

        $profile = Freelancer::checkFreelancer('id', $inputs['subscribed_id']);

        if (!empty($profile)) {
            $receiver['receiver'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            $receiver['device_token'] = $device['device_token'];
        }
        return $receiver;
    }
    public static function processPremiumFolderSender($inputs = [])
    {
        $sender = ['sender' => [], 'device_token' => ''];

        $profile = Customer::getSingleCustomer('id', $inputs['customer_id']);
        if (!empty($profile)) {
            $sender['sender'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $sender['device_token'] = $device['device_token'];
            }
        }
        return $sender;
    }
    public static function processPremiumFolderReceiver($inputs = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];
        $profile = Freelancer::checkFreelancer('id', $inputs['freelancer_id']);

        if (!empty($profile)) {
            $receiver['receiver'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            $receiver['device_token'] = $device['device_token'];
        }
        return $receiver;
    }
    public static function processRecurringSubscriptionReceiver($inputs = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];

        $profile = Customer::getSingleCustomer('id', $inputs['subscriber_id']);;

        if (!empty($profile)) {
            $receiver['receiver'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            $receiver['device_token'] = $device['device_token'];
        }
        return $receiver;
    }

    public static function prepareSubscriptionInputs($messageData = [])
    {
        $notification_inputs = [];

        if (isset($messageData['data']['receiver']['freelancer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['sender']['customer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        if (isset($messageData['data']['sender']['freelancer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        $notification_inputs['notification_uuid'] = $messageData['subscription_uuid'];
        $notification_inputs['message'] = $messageData['save_message'];
        $notification_inputs['notification_type'] = $messageData['type'];
        $notification_inputs['is_read'] = 0;

        return $notification_inputs;
    }
    public static function preparePremiumFolderInputs($messageData = [])
    {
        $notification_inputs = [];

        if (isset($messageData['data']['receiver']['freelancer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['sender']['customer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        if (isset($messageData['data']['sender']['freelancer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        $notification_inputs['notification_uuid'] = $messageData['data']['purchase_uuid'];
        $notification_inputs['message'] = $messageData['save_message'];
        $notification_inputs['notification_type'] = $messageData['type'];
        $notification_inputs['is_read'] = 0;
        return $notification_inputs;
    }
    public static function prepareRecurringSubscriptionInputs($messageData = [])
    {
        $notification_inputs = [];

        if (isset($messageData['data']['receiver']['freelancer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['receiver']['customer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['sender']['customer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        if (isset($messageData['data']['sender']['freelancer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        $notification_inputs['notification_uuid'] = $messageData['subscription_uuid'];
        $notification_inputs['message'] = $messageData['save_message'];
        $notification_inputs['notification_type'] = $messageData['type'];
        $notification_inputs['is_read'] = 0;

        return $notification_inputs;
    }

    public static function sendClassBookingCancelledNotification($inputs = [], $notificationType = 'class_booking_cancelled')
    {

        if ($inputs['login_user_type'] == "customer") {
            $sender_data = self::processClassCustomerSender($inputs);

            $receiver_data = self::processFreelancerClassReceiver($inputs);

            return self::processClassBookingCancelNotificationData($sender_data, $receiver_data, $inputs, $inputs, $notificationType = 'class_booking_cancelled');
        } elseif ($inputs['login_user_type'] == "freelancer") {
            $sender_data = self::processFreelancerCancelClassSender($inputs);
            $receiver_data = self::processCustomerCancelClassReceiver($inputs);
            return self::processClassBookingCancelNotificationData($sender_data, $receiver_data, $inputs, $inputs, $notificationType = 'new_class_booking');
        }
        return true;
    }

    public static function sendClassBookingNotification($data = [], $inputs = [], $notificationType = 'new_class_booking')
    {

        if ($inputs['login_user_type'] == "customer") {
            $sender_data = self::processCustomerSender($data);
            $receiver_data = self::processFreelancerReceiver($data);
            return self::processClassBookingNotificationData($sender_data, $receiver_data, $data, $inputs, $notificationType = 'new_class_booking');
        } elseif ($inputs['login_user_type'] == "freelancer") {
            $sender_data = self::processFreelancerSender($data);
            $receiver_data = self::processCustomerReceiver($data);
            return self::processClassBookingNotificationData($sender_data, $receiver_data, $data, $inputs, $notificationType = 'new_class_booking');
        }
        return true;
    }

    public static function processClassBookingCancelNotificationData($sender_data = [], $receiver_data = [], $notification_data = [], $inputs = [], $notificationType = 'new_class_booking')
    {
        $data = [];
        $data['sender'] = [];
        $data['receiver'] = [];
        if (!empty($sender_data['sender'])) {
            $data['sender'] = $sender_data['sender'];
        }
        if (!empty($receiver_data['receiver'])) {
            $data['receiver'] = $receiver_data['receiver'];
        }
        $data['class']['class_uuid'] = $notification_data['class_uuid'];
        $data['class']['class_schedule_uuid'] = $notification_data['class_schedule_uuid'];
        $data['class']['class_date'] = $notification_data['class_date'];
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' has Cancelled on a class',
            'save_message' => ' has Cancelled on a class',
            'data' => $data,
            'class_uuid' => $notification_data['class_uuid'],
            'class_schedule_uuid' => $notification_data['class_schedule_uuid'],
            'class_date' => $notification_data['class_date'],
        ];

        $notification_inputs = self::prepareClassBookingNotificationInputs($messageData);

        $save_notification = Notification::addNotification($notification_inputs);

        return PushNotificationHelper::send_notification_to_user_devices(!empty($data['receiver']['freelancer_uuid']) ? $data['receiver']['user_id'] : $data['receiver']['user_id'], $messageData);
        //        if (!empty($receiver_data['device_token'])) {
        //            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
        //        }
    }

    public static function processClassBookingNotificationData($sender_data = [], $receiver_data = [], $notification_data = [], $inputs = [], $notificationType = 'new_class_booking')
    {
        $data = [];
        $data['sender'] = [];
        $data['receiver'] = [];
        if (!empty($sender_data['sender'])) {
            $data['sender'] = $sender_data['sender'];
        }
        if (!empty($receiver_data['receiver'])) {
            $data['receiver'] = $receiver_data['receiver'];
        }

        $classUuid = CommonHelper::getRecordByUuid('classes', 'id', $notification_data['class_id'], 'class_uuid');
        $scheduleUuid = CommonHelper::getRecordByUuid('class_schedules', 'id', $notification_data['class_schedule_id'], 'class_schedule_uuid');
        $data['class']['class_uuid'] = $classUuid;
        $data['class']['class_schedule_uuid'] = $scheduleUuid;
        $data['class']['class_date'] = !empty($notification_data['class_date']) ? $notification_data['class_date'] : $notification_data['schedule']['class_date'];
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' has booked on a class',
            'save_message' => ' has booked on a class',
            'data' => $data,
            'class_uuid' => $classUuid,
            'class_schedule_uuid' => $scheduleUuid,
            'class_date' => $data['class']['class_date'],
        ];

        $notification_inputs = self::prepareClassBookingNotificationInputs($messageData);
        $save_notification = Notification::addNotification($notification_inputs);
        return PushNotificationHelper::send_notification_to_user_devices(!empty($data['receiver']['freelancer_uuid']) ? $data['receiver']['user_id'] : $data['receiver']['user_id'], $messageData);
        //        if (!empty($receiver_data['device_token'])) {
        //            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
        //        }
    }

    public static function sendRescheduledAppointmentNotification($data = [], $inputs = [], $notificationType = 'reschedule_appointment')
    {

        if ($inputs['login_user_type'] == "customer") {
            $sender_data = self::processRescheduledCustomer($data['appointment_customer']);
            $receiver_data = self::processRescheduledFreelancer($data['appointment_freelancer']);
            return self::processRescheduleAppointmentNotification($sender_data, $receiver_data, $data, $inputs, $notificationType = 'reschedule_appointment');
        } elseif ($inputs['login_user_type'] == "freelancer") {
            $sender_data = self::processRescheduleFreelancerSender($data['appointment_freelancer']);
            $receiver_data = self::processRescheduleCustomerReceiver($data['appointment_customer']);
            return self::processRescheduleAppointmentNotification($sender_data, $receiver_data, $data, $inputs, $notificationType = 'reschedule_appointment');
        }
    }

    public static function processRescheduleAppointmentNotification($sender_data = [], $receiver_data = [], $notification_data = [], $inputs = [], $notificationType = 'reschedule_appointment')
    {
        $data = [];
        $data['sender'] = [];
        $data['receiver'] = [];
        $check_notification_setting = [];
        if (!empty($sender_data['sender'])) {
            $data['sender'] = $sender_data['sender'];
        }
        if (!empty($receiver_data['receiver'])) {
            $data['receiver'] = $receiver_data['receiver'];
        }
        $data['appointment']['appointment_uuid'] = $notification_data['appointment_uuid'];
        $message = ($inputs['login_user_type'] == "freelancer") ? (' has rescheduled your booking') : (' has rescheduled their booking');
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . $message,
            'save_message' => $message,
            'data' => $data,
            'appointment_uuid' => $notification_data['appointment_uuid'],
        ];
        $notification_inputs = self::prepareAppointmentNotificationInputs($messageData);
        $save_notification = Notification::addNotification($notification_inputs);
        if ($inputs['login_user_type'] == "customer") {
            $check_notification_setting = NotificationSetting::getSettingsWithType('user_id', $data['receiver']['user_id'], 'new_appointment');
        } elseif ($inputs['login_user_type'] == "freelancer" && !empty($data['receiver']['customer_id'])) {
            $check_notification_setting = NotificationSetting::getSettingsWithType('user_id', $data['receiver']['user_id'], 'new_appointment');
            //        } if (!empty($receiver_data['device_token']) && !empty($check_notification_setting)) {
        }
        if (!empty($check_notification_setting)) {
            return PushNotificationHelper::send_notification_to_user_devices(!empty($data['receiver']['user_id']) ? $data['receiver']['user_id'] : $data['receiver']['customer_id'], $messageData);
            //            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
        }
    }

    public static function processRescheduleFreelancerSender($profile = [])
    {
        $sender = ['sender' => [], 'device_token' => ''];
        if (!empty($profile)) {
            $sender['sender'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $sender['device_token'] = $device['device_token'];
            }
        }
        return $sender;
    }

    public static function processRescheduledFreelancer($profile = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];
        if (!empty($profile)) {
            $receiver['receiver'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $receiver['device_token'] = $device['device_token'];
            }
        }
        return $receiver;
    }

    public static function processRescheduleCustomerReceiver($profile = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];
        if (!empty($profile)) {
            $receiver['receiver'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $receiver['device_token'] = $device['device_token'];
            }
        }
        return $receiver;
    }

    public static function processRescheduledCustomer($profile = [])
    {

        $sender = ['sender' => [], 'device_token' => ''];

        if (!empty($profile)) {
            $sender['sender'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $sender['device_token'] = $device['device_token'];
            }
        }
        return $sender;
    }

    public static function sendAppointmentNotification($data = [], $inputs = [], $notificationType = 'new_appointment')
    {
        if ($inputs['login_user_type'] == "customer") {
            $sender_data = self::processCustomerSender($data);

            $receiver_data = self::processFreelancerReceiver($data);

            return self::processAppointmentNotificationData($sender_data, $receiver_data, $data, $inputs, $notificationType = 'new_appointment');
        } elseif ($inputs['login_user_type'] == "freelancer") {

            $sender_data = self::processFreelancerSender($data);

            $receiver_data = self::processCustomerReceiver($data);

            return self::processAppointmentNotificationData($sender_data, $receiver_data, $data, $inputs, $notificationType = 'new_appointment');
        } elseif ($inputs['login_user_type'] != "freelancer" || $inputs['login_user_type'] != "customer") {
            return self::processAdminAsSender($data, $inputs, $notificationType = 'new_appointment');
        }
    }

    public static function processAppointmentNotificationData($sender_data = [], $receiver_data = [], $notification_data = [], $inputs = [], $notificationType = 'new_appointment')
    {


        $inputs['freelanceId'] = CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['freelancer_uuid'], 'id');
        $inputs['customerId'] = CommonHelper::getRecordByUuid('customers', 'customer_uuid', $inputs['customer_uuid'], 'id');

        $data = [];
        $data['sender'] = [];
        $data['receiver'] = [];
        $check_notification_setting = [];
        if (!empty($sender_data['sender'])) {
            $data['sender'] = $sender_data['sender'];
        }
        if (!empty($receiver_data['receiver'])) {
            $data['receiver'] = $receiver_data['receiver'];
        }

        $isPackage = false;
        $packageBooking =  Appointment::where('appointment_uuid')->first();
        if($packageBooking) {
            $isPackage = $packageBooking->package_id >= 0 ? true : false;
        }

        if ($isPackage) {
            $data['appointment']['package_uuid'] = $inputs['package_uuid'];
            $data['appointment']['purchase_time'] = $inputs['purchase_time'];
            $data['appointment']['appointment_uuid'] = $notification_data['appointment_uuid'];
            $messageData = [
                'type' => "appointment_package",
                'message' => $sender_data['sender']['first_name'] . ' has booked a package',
                'save_message' => ' has booked a package',
                'data' => $data,
                'appointment_uuid' => $notification_data['appointment_uuid'],
                'purchase_time' => $inputs['purchase_time'],
                'package_uuid' => $inputs['package_uuid'],
            ];
        } else {
            $data['appointment'] = self::prepareAppointmentData($notification_data);
            $messageData = [
                'type' => $notificationType,
                'message' => $sender_data['sender']['first_name'] . ' has sent a new booking request',
                'save_message' => ' has sent a new booking request',
                'data' => $data,
                'appointment_uuid' => $notification_data['appointment_uuid'],
            ];
        }

        $notification_inputs = self::prepareAppointmentNotificationInputs($messageData);

        $save_notification = Notification::addNotification($notification_inputs);
        if ($inputs['login_user_type'] == "customer") {
            $user_id = CommonHelper::getRecordByUuid('freelancers', 'id', $inputs['freelanceId'], 'user_id');
            // $check_notification_setting = NotificationSetting::getSettingsWithType('user_id', $inputs['freelanceId'], 'new_appointment');
            $check_notification_setting = NotificationSetting::getSettingsWithType('user_id', $user_id, 'new_appointment');
        } elseif ($inputs['login_user_type'] == "freelancer") {
            $user_id =  CommonHelper::getRecordByUuid('customers', 'id', $inputs['customerId'], 'user_id');
            //$check_notification_setting = NotificationSetting::getSettingsWithType('user_id', $inputs['customerId'], 'new_appointment');
            $check_notification_setting = NotificationSetting::getSettingsWithType('user_id', $user_id, 'new_appointment');
            //        } if (!empty($receiver_data['device_token']) && !empty($check_notification_setting)) {
        }
        if (!empty($check_notification_setting)) {
            //            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
            return PushNotificationHelper::send_notification_to_user_devices(!empty($data['receiver']['user_id']) ? $data['receiver']['user_id'] : $inputs['customerId'], $messageData);
        }
    }

    public static function prepareAppointmentData($data = [])
    {
        $appointment = [];
        if (!empty($data)) {
            $appointment['appointment_uuid'] = $data['appointment_uuid'];
        }
        return $appointment;
    }

    public static function processAdminAsSender($inputs = [], $inputs_data = [], $notificationType = 'new_appointment')
    {
        $receiver_data[0] = self::processFreelancerReceiver($inputs);
        $receiver_data[1] = self::processCustomerReceiver($inputs);
        $sender_data = self::processAdminSender($inputs);
        foreach ($receiver_data as $key => $receiver) {
            $data['sender'] = $sender_data['sender'];
            $data['receiver'] = $receiver_data;
            $messageData = [
                'type' => $notificationType,
                'message' => $sender_data['sender']['first_name'] . ' has created a new appointment',
                'save_message' => ' has created a new appointment',
                'data' => $data,
                'appointment_uuid' => $inputs['appointment_uuid'],
            ];

            $notification_inputs = self::prepareAppointmentInsertionInputs($messageData);
            $insert_notification = Notification::insertNotification($notification_inputs);
            return PushNotificationHelper::send_notification_to_user_devices(!empty($data['receiver']['freelancer_uuid']) ? $data['receiver']['freelancer_uuid'] : $data['receiver']['customer_uuid'], $messageData);
            //            if (!empty($receiver['device_token'])) {
            //                return PushNotificationHelper::send_notification_to_user($receiver['device_token'], $messageData);
            //            }
        }
    }

    public static function processAdminSender($inputs = [])
    {
        $sender = ['sender' => [], 'device_token' => ''];
        $sender['sender'] = self::processAdminSenderInputs();
        $sender['device_token'] = "1043cb44ca36801c17aec373ff28bd0c7bfcb0ad52ccf3ce0e94928806d1d09b";
        return $sender;
    }

    public static function processAdminSenderInputs()
    {
        $response = [];
        $response['admin_uuid'] = "1099fde0-d690-11e8-8356-33b02efa9k0g";
        $response['first_name'] = "Admin";
        $response['last_name'] = "Circle";
        $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse();
        return $response;
    }

    public static function prepareAppointmentInsertionInputs($messageData = [])
    {
        $notification_inputs = [];
        $notification_inputs[0]['notification_uuid'] = self::getUniqueNotificationUUID();
        $notification_inputs[0]['receiver_uuid'] = $messageData['data']['receiver'][0]['receiver']['freelancer_uuid'];
        $notification_inputs[0]['sender_uuid'] = $messageData['data']['sender']['admin_uuid'];
        $notification_inputs[0]['uuid'] = $messageData['appointment_uuid'];
        $notification_inputs[0]['message'] = $messageData['save_message'];
        $notification_inputs[0]['notification_type'] = $messageData['type'];
        $notification_inputs[0]['is_read'] = 0;

        $notification_inputs[1]['notification_uuid'] = self::getUniqueNotificationUUID();
        $notification_inputs[1]['receiver_uuid'] = $messageData['data']['receiver'][1]['receiver']['customer_uuid'];
        $notification_inputs[1]['sender_uuid'] = $messageData['data']['sender']['admin_uuid'];
        $notification_inputs[1]['uuid'] = $messageData['appointment_uuid'];
        $notification_inputs[1]['message'] = $messageData['save_message'];
        $notification_inputs[1]['notification_type'] = $messageData['type'];
        $notification_inputs[1]['is_read'] = 0;

        return $notification_inputs;
    }

    public static function getUniqueNotificationUUID()
    {
        $data['notification_uuid'] = Uuid::uuid4()->toString();
        $validation = Validator::make($data, NotificationValidationHelper::$add_notification_uuid_rules);
        if ($validation->fails()) {
            //$this->getUniquePostImageUUID();
        }
        return $data['notification_uuid'];
    }

    public static function processClassCustomerSender($inputs = [])
    {

        $sender = ['sender' => [], 'device_token' => ''];
        $profile = Customer::getSingleCustomer('id', $inputs['customer_id']);
        if (!empty($profile)) {
            $sender['sender'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $sender['device_token'] = $device['device_token'];
            }
        }
        return $sender;
    }

    public static function processCustomerSender($inputs = [])
    {

        $sender = ['sender' => [], 'device_token' => ''];
        $profile = Customer::getSingleCustomer('id', $inputs['customer_id']);
        if (!empty($profile)) {
            $sender['sender'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $inputs['customer_id']);
            if (!empty($device)) {
                $sender['device_token'] = $device['device_token'];
            }
        }
        return $sender;
    }

    public static function processFreelancerSender($inputs = [])
    {

        $sender = ['sender' => [], 'device_token' => ''];
        $profile = Freelancer::checkFreelancer('id', $inputs['freelancer_id']);

        if (!empty($profile)) {
            $sender['sender'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $inputs['freelancer_id']);
            if (!empty($device)) {
                $sender['device_token'] = $device['device_token'];
            }
        }
        return $sender;
    }

    public static function processFreelancerCancelClassSender($inputs = [])
    {

        $sender = ['sender' => [], 'device_token' => ''];
        $profile = Freelancer::checkFreelancer('id', $inputs['freelancer_id']);

        if (!empty($profile)) {
            $sender['sender'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $sender['device_token'] = $device['device_token'];
            }
        }
        return $sender;
    }

    public static function processFreelancerReceiver($inputs = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];
        $profile = Freelancer::checkFreelancer('id', $inputs['freelancer_id']);

        if (!empty($profile)) {
            $receiver['receiver'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $inputs['freelancer_id']);
            if (!empty($device)) {
                $receiver['device_token'] = $device['device_token'];
            }
        }
        return $receiver;
    }

    public static function processFreelancerClassReceiver($inputs = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];
        $profile = Freelancer::checkFreelancer('id', $inputs['freelancer_id']);

        if (!empty($profile)) {
            $receiver['receiver'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $receiver['device_token'] = $device['device_token'];
            }
        }
        return $receiver;
    }

    public static function processCustomerCancelClassReceiver($inputs = [])
    {

        $receiver = ['receiver' => [], 'device_token' => ''];
        $profile = [];
        if (!empty($inputs['customer_id'])) {
            $profile = Customer::getSingleCustomer('id', $inputs['customer_id']);
        }

        if (!empty($profile)) {
            $receiver['receiver'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $receiver['device_token'] = $device['device_token'];
            }
        }
        return $receiver;
    }

    public static function processCustomerReceiver($inputs = [])
    {

        $receiver = ['receiver' => [], 'device_token' => ''];
        $profile = [];
        if (!empty($inputs['customer_id'])) {
            $profile = Customer::getSingleCustomer('id', $inputs['customer_id']);
        }

        if (!empty($profile)) {
            $receiver['receiver'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $inputs['customer_id']);
            if (!empty($device)) {
                $receiver['device_token'] = $device['device_token'];
            }
        }
        return $receiver;
    }

    public static function prepareAppointmentNotificationInputs($messageData = [])
    {

        $notification_inputs = [];

        if (isset($messageData['data']['receiver']['freelancer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
            //$notification_inputs['receiver_user'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['receiver']['customer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
            //$notification_inputs['receiver_user'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['sender']['customer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
            //$notification_inputs['sender_user'] = $messageData['data']['sender']['user_id'];
        }
        if (isset($messageData['data']['sender']['freelancer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
            //$notification_inputs['sender_user'] = $messageData['data']['sender']['user_id'];
        }

        if (isset($messageData['data']['sender']['admin_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['admin_uuid'];
        }
        $notification_inputs['notification_uuid'] = $messageData['appointment_uuid'];
        $notification_inputs['purchase_time'] = isset($messageData['purchase_time']) ? $messageData['purchase_time'] : null;
        $notification_inputs['package_uuid'] = isset($messageData['package_uuid']) ? $messageData['package_uuid'] : null;
        $notification_inputs['message'] = $messageData['save_message'];
        $notification_inputs['notification_type'] = $messageData['type'];
        $notification_inputs['is_read'] = 0;

        return $notification_inputs;
    }

    public static function sendRatingNotification($inputs = [], $review = [], $notificationType = 'new_rating')
    {
        $receiver_data = self::processRatingReceiver($inputs);
        $sender_data = self::processRatingSender($inputs);
        $data = [];
        $data['sender'] = $sender_data['sender'];
        $data['receiver'] = $receiver_data['receiver'];
        $data['review']['review_uuid'] = $review['review_uuid'];
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' has sent a review',
            'save_message' => ' has sent a review',
            //            'save_message' => ' has rated your ' . $inputs['type'],
            'data' => $data,
            'review_uuid' => $review['review_uuid'],
        ];

        //        if (!empty($receiver_data['device_token'])) {
        $notification_inputs = self::prepareInputs($messageData);
        //            $check = self::checkAndUpdateNotification($notification_inputs);
        $save_notification = Notification::addNotification($notification_inputs);
        return PushNotificationHelper::send_notification_to_user_devices($data['receiver']['user_id'], $messageData);
        //        return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
        //        }
    }

    public static function processRatingSender($inputs = [])
    {
        $sender = ['sender' => [], 'device_token' => ''];
        $profile = Customer::getSingleCustomer('customer_uuid', $inputs['customer_uuid']);
        if (!empty($profile)) {
            $sender['sender'] = self::processCustomer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            if (!empty($device)) {
                $sender['device_token'] = $device['device_token'];
            }
        }
        return $sender;
    }

    public static function processRatingReceiver($inputs = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];
        $profile = Freelancer::checkFreelancer('freelancer_uuid', $inputs['freelancer_uuid']);
        if (!empty($profile)) {
            $receiver['receiver'] = self::processFreelancer($profile);
            $device = UserDevice::getUserDevice('user_id', $profile['user_id']);
            $receiver['device_token'] = $device['device_token'];
        }
        return $receiver;
    }

    public static function prepareClassBookingNotificationInputs($messageData = [])
    {
        $notification_inputs = [];

        if (isset($messageData['data']['receiver']['freelancer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['receiver']['customer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['sender']['customer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        if (isset($messageData['data']['sender']['freelancer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        $notification_inputs['notification_uuid'] = $messageData['class_uuid'];
        $notification_inputs['date'] = !empty($messageData['class_date']) ? $messageData['class_date'] : null;
        $notification_inputs['message'] = $messageData['save_message'];
        $notification_inputs['class_schedule_uuid'] = $messageData['class_schedule_uuid'];
        $notification_inputs['notification_type'] = $messageData['type'];
        $notification_inputs['is_read'] = 0;

        return $notification_inputs;
    }

    public static function sendLikeNotification($inputs = [], $like = [], $notificationType = 'new_like')
    {

        $receiver_data = self::processLikeReceiver($like);
        $sender_data = self::processLikeSender($like);
        $data = [];
        $data['sender'] = $sender_data['sender'];
        $data['receiver'] = $receiver_data['receiver'];
        $data['post'] = self::preparePostData($inputs);
        $messageData = [
            'type' => $notificationType,
            'message' => ' User has liked your post',
            'message' => $sender_data['sender']['first_name'] . ' liked your post',
            'save_message' => ' liked your post',
            'data' => $data,
            //            'notification_send_type' => "mutable",
            'post_uuid' => $inputs['post_uuid'],
        ];
        $notification_inputs = self::prepareInputs($messageData);

        $check = self::checkAndUpdateNotification($notification_inputs);

        $save_notification = Notification::addNotification($notification_inputs);
        //i change profile_uuid to with freelancer_uuid because profile_uuid now replace with user_id which is the PK of users table
        //actually we want freelancer record so i used freelancer_uuid here
        //        return PushNotificationHelper::send_notification_to_user_devices($like['profile_uuid'], $messageData);
        return PushNotificationHelper::send_notification_to_user_devices($receiver_data['receiver']['user_id'], $messageData);
        //        if (!empty($receiver_data['device_token'])) {
        //            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
        //        }
    }

    public static function preparePostData($data)
    {
        if (!empty($data)) {
            $post['post_uuid'] = $data['post_uuid'];
        }
        return ($post) ? $post : [];
    }

    public static function processLikeSender($inputs = [])
    {
        $sender = ['sender' => [], 'device_token' => ''];
        //liked_by_id is the PK of users table
        $profile = Customer::getSingleCustomer('user_id', $inputs['liked_by_id']);
        if (!empty($profile)) {
            $sender['sender'] = self::processCustomer($profile);
            // user_id in user_devices table is the FK of users table and $input['liked_by_id'] is also PK of users table
            $device = UserDevice::getUserDevice('user_id', $inputs['liked_by_id']);
            if (!empty($device)) {
                $sender['device_token'] = $device['device_token'];
            }
        }
        return $sender;
    }

    public static function processLikeReceiver($inputs = [])
    {
        $receiver = ['receiver' => [], 'device_token' => ''];
        //$input['user_id'] is a like table record and user_id is the parent_key of freelancers table record
        $profile = Freelancer::checkFreelancer('user_id', $inputs['user_id']);
        if (!empty($profile)) {
            $receiver['receiver'] = self::processFreelancer($profile);
            //$input is the record of like table
            //$input['user_id'] is the parent_key of freelancer who own the post
            $device = UserDevice::getUserDevice('user_id', $inputs['user_id']);
            if (!empty($device)) {
                $receiver['device_token'] = $device['device_token'];
            }
        }
        return $receiver;
    }

    public static function sendAdminAppointmentStatusNotification($inputs = [], $appointment = [], $notificationType = 'change_appointment_status')
    {
        $receivers = [];
        $receivers['customer'] = self::processCustomerReceiver([
            'customer_uuid' => $appointment['customer_uuid'],
        ]);
        $receivers['freelancer'] = self::processFreelancerReceiver([
            'freelancer_uuid' => $appointment['freelancer_uuid']
        ]);
        $sender_data = [
            'sender' => [
                'admin_uuid' => $inputs['logged_in_uuid'] ?? '',
                'first_name' => 'Admin',
                'last_name' => '',
                'profile_image' => null,
            ],
            'device_token' => ''
        ];

        foreach ($receivers as $userType => $receiver) :
            $data = [];
            $data['sender'] = $sender_data['sender'];
            $data['receiver'] = $receiver['receiver'];
            $data['appointment']['appointment_uuid'] = $appointment['appointment_uuid'];
            //            $message = ($userType == "freelancer") ? ('has ' . $inputs['status'] . ' your booking') : ('has ' . $inputs['status'] . ' their booking');
            $message = ' has ' . $inputs['status'] . ' your booking';
            $sender_name = $sender_data['sender']['first_name'] ?? '';
            $messageData = [
                'type' => $notificationType,
                'message' => $sender_name . $message,
                'save_message' => $message,
                'data' => $data,
                'appointment_uuid' => $appointment['appointment_uuid'],
            ];
            $notification_inputs = self::prepareAppointmentNotificationInputs($messageData);
            $save_notification = Notification::addNotification($notification_inputs);
            if (!$save_notification) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
            }
            //            var_dump(!empty($data['receiver']['freelancer_uuid']) ? $data['receiver']['freelancer_uuid'] : $data['receiver']['customer_uuid'], $messageData);
            return PushNotificationHelper::send_notification_to_user_devices(!empty($data['receiver']['freelancer_uuid']) ? $data['receiver']['freelancer_uuid'] : $data['receiver']['customer_uuid'], $messageData);
        endforeach;

        //        die();
        //        $check_notification_setting = NotificationSetting::getSettingsWithType('profile_uuid', $receiver_data['receiver']['freelancer_uuid'], $inputs['status']);
        //        if (!empty($receiver_data['device_token'])) {
        //            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
        //        }
    }

    public static function sendAppointmentStatusNotification($inputs = [], $appointment = [], $notificationType = 'change_appointment_status')
    {
        if ($inputs['login_user_type'] == 'freelancer') {
            $receiver_data = self::processCustomerReceiver($inputs);

            $sender_data = self::processFreelancerSender($inputs);
        } else {
            $receiver_data = self::processFreelancerReceiver($inputs);
            $sender_data = self::processCustomerSender($inputs);
        }
        $data = [];
        $data['sender'] = $sender_data['sender'];
        $data['receiver'] = $receiver_data['receiver'];
        $data['appointment']['appointment_uuid'] = $appointment['appointment_uuid'];
        $message = ($inputs['login_user_type'] == "freelancer") ? (' has ' . $inputs['status'] . ' your booking') : (' has ' . $inputs['status'] . ' their booking');
        $sender_name = $sender_data['sender']['first_name'] ?? '';
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_name . $message,
            'save_message' => $message,
            'data' => $data,
            'appointment_uuid' => $appointment['appointment_uuid'],
        ];
        $notification_inputs = self::prepareAppointmentNotificationInputs($messageData);

        $save_notification = Notification::addNotification($notification_inputs);
        if (!$save_notification) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
        }

        if (!empty($data['receiver'])) {
            return PushNotificationHelper::send_notification_to_user_devices(!empty($data['receiver']['user_id']) ? $data['receiver']['user_id'] : $data['receiver']['user_id'], $messageData);
            //        $check_notification_setting = NotificationSetting::getSettingsWithType('profile_uuid', $receiver_data['receiver']['freelancer_uuid'], $inputs['status']);
            //        if (!empty($receiver_data['device_token'])) {
            //            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
            //        }
        }
    }

    public static function sendMessageNotification($data = [], $receiver = [], $loginUser, $isCallback, $notificationType = 'new_message')
    {
        $uuid = "";
        //        if (isset($data['media']) && !empty($data['media'])) {
        //            unset($data['media']);
        //        }
        if ($data['reciever_type'] == "customer") {
            $receiver_data = self::processChatCustomer($receiver);
            $uuid = $receiver_data['customer_id'];
        } elseif ($data['reciever_type'] == "freelancer") {
            $receiver_data = self::processChatFreelancer($receiver);
            $uuid = $receiver_data['freelancer_id'];
        }
        if ($data['sender']['type'] == "customer") {
            $sender_data = self::processChatCustomer($loginUser);
        } elseif ($data['sender']['type'] == "freelancer") {
            $sender_data = self::processChatFreelancer($loginUser);
        }
        //        $receiver_token = UserDevice::getUserDevice('profile_uuid', $uuid);
        $message_data = self::prepareMessageData($data);
        $message_data['sender'] = $sender_data;
        $message_data['receiver'] = $receiver_data;
        $messageData = [
            'type' => $notificationType,
            'alert-message' => $sender_data['name'] . ' has sent you a message',
            'save_message' => '  has sent you a new message',
            'message' => !empty($message_data['message']) ? ($message_data['message']) : "",
            'chat_with' => !empty($message_data['chat_with']) ? ($message_data['chat_with']) : "user",
            'message_uuid' => $message_data['message_uuid'],
            'id' => $message_data['id'],
            'media' => !empty($message_data['media']) ? $message_data['media'] : [],
            'sender' => $message_data['sender'],
            'receiver_id' => $message_data['receiver']['id'],
            'receiver_type' => $message_data['receiver']['type'],
            'created_on' => $message_data['created_on'],
            //            'data' => $message_data,
            'notification_send_type' => 'mutable',
            'status' => 'delivered',
        ];
        if ($messageData['receiver_type'] == 'freelancer') {
            $receiver_id = \App\Freelancer::where('freelancer_uuid', $messageData['receiver_id'])->first()->user_id;
        } elseif ($messageData['receiver_type'] == 'customer') {
            $receiver_id = \App\Customer::where('customer_uuid', $messageData['receiver_id'])->first()->user_id;
        }
        return PushNotificationHelper::send_notification_to_user_devices($receiver_id, $messageData, "chat");
        //        if (!empty($receiver_token['device_token'])) {
        ////        return PushNotificationHelper::send_voip_notification_to_user($receiver_data['voip_device_token'], $messageData);
        //            return PushNotificationHelper::send_notification_to_user($receiver_token['device_token'], $messageData);
        //
        ////            return PushNotificationHelper::send_notification_to_user("1043cb44ca36801c17aec373ff28bd0c7bfcb0ad52ccf3ce0e94928806d1d09b", $messageData);
        //        }
    }

    public static function prepareMessageData($message_data = [])
    {
        $data = [];
        if (!empty($message_data)) {
            $data['message'] = !empty($message_data['message']) ? $message_data['message'] : "";
            $data['message_uuid'] = $message_data['message_uuid'];
            $data['id'] = $message_data['id'];
            $data['created_on'] = $message_data['created_on'];
            if (isset($message_data['media']) && !empty($message_data['media'])) {
                $data['media'] = self::prepareMessageMedia($message_data['media']);
            }
        }
        return $data;
    }

    public static function prepareMessageMedia($media = [])
    {
        if (!empty($media)) {
            $data['width'] = $data['height'] = null;
            $data = [
                'attachment_type' => !empty($media['attachment_type']) ? $media['attachment_type'] : "",
                'attachment' => !empty($media['attachment']) ? $media['attachment'] : null,
                'video_thumbnail' => null,
            ];
            if ($media['attachment_type'] == "video") {
                $thumb = explode(".", $media['video_thumbnail']);
                $data = [
                    'attachment_type' => !empty($media['attachment_type']) ? $media['attachment_type'] : "",
                    'attachment' => !empty($media['attachment']) ? $media['attachment'] : null,
                    'video_thumbnail' => !empty($media['attachment']) ? $thumb[0] . ".jpg" : null,
                ];
                $resolution = getimagesize($media['video_thumbnail']);
                $data['width'] = (float) $resolution[0];
                $data['height'] = (float) $resolution[1];
            }

            if ($media['attachment_type'] == "image") {
                $resolution = getimagesize($media['attachment']);
                $data['width'] = (float) $resolution[0];
                $data['height'] = (float) $resolution[1];
            }
        }
        return $data;
    }

    public static function sendMultipleClassBookingNotification($data = [], $notificationType = 'new_class_booking')
    {
        \Log::info('===================in multple class booking notification==========');
        \Log::info(print_r($data, true));
        if ($data['login_user_type'] == "customer") {
            //            $data['customer_id'] = CommonHelper::getCutomerIdByUuid($data['logged_in_uuid']);
            $sender_data = self::processCustomerSender($data);
            $receiver_data = self::processFreelancerReceiver($data);

            return self::processClassPackgeNotificationData($sender_data, $receiver_data, $data, $notificationType = 'class_package');
        }
        return true;
    }

    public static function processClassPackgeNotificationData($sender_data = [], $receiver_data = [], $notification_data = [], $notificationType = 'class_package')
    {
        $data = [];
        $data['sender'] = [];
        $data['receiver'] = [];
        if (!empty($sender_data['sender'])) {
            $data['sender'] = $sender_data['sender'];
        }
        if (!empty($receiver_data['receiver'])) {
            $data['receiver'] = $receiver_data['receiver'];
        }
        $data['class']['class_uuid'] = $notification_data['booking'][0]['class_object']['class_uuid'];
        $data['class']['class_schedule_uuid'] = $notification_data['booking'][0]['schedule']['class_schedule_uuid'];
        $data['class']['package_uuid'] = CommonHelper::getRecordByUuid('packages', 'id', $notification_data['booking'][0]['package_id'], 'package_uuid');
        $data['class']['purchase_time'] = $notification_data['booking'][0]['created_at'];
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' has booked a class pass',
            'save_message' => ' has booked a class pass',
            'data' => $data,
            'class_uuid' => $data['class']['class_uuid'],
            'class_schedule_uuid' => $data['class']['class_schedule_uuid'],
            'package_uuid' => $data['class']['package_uuid'],
            'purchase_time' => $data['class']['purchase_time'],
        ];
        $notification_inputs = self::prepareClassPackgeNotificationInputs($messageData);

        $save_notification = Notification::addNotification($notification_inputs);

        return PushNotificationHelper::send_notification_to_user_devices(!empty($data['receiver']['freelancer_uuid']) ? $data['receiver']['user_id'] : $data['receiver']['user_id'], $messageData);
        //        if (!empty($receiver_data['device_token'])) {
        //            return PushNotificationHelper::send_notification_to_user($receiver_data['device_token'], $messageData);
        //        }
    }

    public static function prepareClassPackgeNotificationInputs($messageData = [])
    {
        $notification_inputs = [];

        if (isset($messageData['data']['receiver']['freelancer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['receiver']['customer_uuid'])) {
            $notification_inputs['receiver_id'] = $messageData['data']['receiver']['user_id'];
        }
        if (isset($messageData['data']['sender']['customer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        if (isset($messageData['data']['sender']['freelancer_uuid'])) {
            $notification_inputs['sender_id'] = $messageData['data']['sender']['user_id'];
        }
        $notification_inputs['notification_uuid'] = $messageData['class_uuid'];
        $notification_inputs['class_schedule_uuid'] = !empty($messageData['class_schedule_uuid']) ? $messageData['class_schedule_uuid'] : null;
        $notification_inputs['package_uuid'] = !empty($messageData['package_uuid']) ? $messageData['package_uuid'] : null;
        $notification_inputs['purchase_time'] = !empty($messageData['purchase_time']) ? $messageData['purchase_time'] : null;
        $notification_inputs['date'] = !empty($messageData['class_date']) ? $messageData['class_date'] : null;
        $notification_inputs['message'] = $messageData['save_message'];
        $notification_inputs['notification_type'] = $messageData['type'];
        $notification_inputs['is_read'] = 0;

        return $notification_inputs;
    }
    public static function sendPremiumFolderNotification($data = [], $inputs = [], $notificationType = 'premium_folder_unlock')
    {
        $purchase_uuid = $data['purchase']['purchases_uuid'];
        $receiver_data = self::processPremiumFolderReceiver($inputs);
        $sender_data = self::processPremiumFolderSender($inputs);
        $data = [];
        $data['sender'] = $sender_data['sender'];
        $data['receiver'] = $receiver_data['receiver'];
        $data['purchase_uuid'] = $purchase_uuid;
        // $data['subscription']['subscription_uuid'] = $subscription['subscription_uuid'];
        $messageData = [
            'type' => $notificationType,
            'message' => $sender_data['sender']['first_name'] . ' has bought a premium folder',
            'save_message' => 'has bought a premium folder',
            'data' => $data,
        ];
        $notification_inputs = self::preparePremiumFolderInputs($messageData);
        $save_notification = Notification::addNotification($notification_inputs);
        return PushNotificationHelper::send_notification_to_user_devices($receiver_data['receiver']['user_id'], $messageData);
    }
    public static function sendFreelancerNotification($appointment_data, $freelancer = [], $customer = [], $notificationType = 'appointment_reminder')
    {
        if (!empty($appointment_data)) {
            $notificationType = "appointment_reminder";
            $data = [];
            $sender_data = \App\Helpers\ProcessNotificationHelper::processCustomer($customer);
            $receiver_data = \App\Helpers\ProcessNotificationHelper::processFreelancer($freelancer);
            //            $device = UserDevice::getUserDevice('user_id', $freelancer['id']);
            $data['sender'] = [];
            $data['receiver'] = [];
            $data['appointment']['appointment_uuid'] = $appointment_data['appointment_uuid'];
            if (!empty($sender_data)) {
                $data['sender'] = $sender_data;
            }
            if (!empty($receiver_data)) {
                $data['receiver'] = $receiver_data;
            }
            $messageData = [
                'type' => $notificationType,
                'message' => 'You have a pending booking request with ' . $sender_data['first_name'],
                'data' => $data,
                'appointment_uuid' => $appointment_data['appointment_uuid'],
            ];
            Log::channel('pushnotification')->info('Checking notification sendings'. json_encode($freelancer['freelancer_uuid']));
            $check_notification_setting = NotificationSetting::getSettingsWithType('user_id', $freelancer['user_id'], 'new_appointment');
            //            if (!empty($device['device_token']) && !empty($check_notification_setting)) {
            if (!empty($check_notification_setting)) {
                Log::channel('pushnotification')->info('Notification enabled');
                //                return PushNotificationHelper::send_notification_to_user($device['device_token'], $messageData);
                return PushNotificationHelper::send_notification_to_user_devices($freelancer['user_id'], $messageData);
            }
        }
    }
}

// end of helper class
