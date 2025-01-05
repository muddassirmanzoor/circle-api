<?php

namespace App\Helpers;

use App\Freelancer;
use App\User;
use Illuminate\Support\Facades\Validator;
use App\Notification;
use DB;

Class NotificationHelper {
    /*
      |--------------------------------------------------------------------------
      | NotificationHelper that contains all the notification related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use notification functionality
      |
     */

    public static function getNotifications($inputs) {

        $validation = Validator::make($inputs, NotificationValidationHelper::getNotificationRules()['rules'], NotificationValidationHelper::getNotificationRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : 10;
        $inputs['user_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'],$inputs['profile_uuid'],'user_id');

        $notifications = Notification::getNotification('receiver_id', $inputs['user_id'], $offset, $limit);

        $update_type = ['notification_type' => 'all'];
        $update_notification = Notification::updateNotificationCount('receiver_id', $inputs['user_id'], $update_type);

        $response = self::processNotificationsResponse($notifications,$inputs);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function processNotificationsResponse($data = [],$inputs=null) {

        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {

                $response[$key]['notification_uuid'] = $value['notification_uuid'];

                if($value['notification_type'] == 'subscription_renewal_captured' || $value['notification_type'] == 'subscription_renewal_fail' || $value['notification_type'] == 'subscription_renewal'){
                    $response[$key]['sender_uuid'] = CommonHelper::getRecordByUuid('freelancers','user_id',$value['sender_id'],'freelancer_uuid');
                    $response[$key]['receiver_uuid'] = CommonHelper::getRecordByUuid('customers','user_id',$value['receiver_id'],'customer_uuid');
                }else{

                    $response[$key]['sender_uuid'] = User::getUserChild($inputs['login_user_type'],$value['sender_id']);
                    $response[$key]['receiver_uuid'] = User::getUserChild($inputs['login_user_type'],$value['receiver_id']);
                }


                $response[$key]['uuid'] = $value['notification_uuid'];
                $response[$key]['message'] = $value['message'];

                $response[$key]['class_date'] = !empty($value['date']) ? $value['date'] : null;
                $response[$key]['class_schedule_uuid'] = !empty($value['class_schedule_uuid']) ? $value['class_schedule_uuid'] : null;
                $response[$key]['purchase_time'] = !empty($value['purchase_time']) ? $value['purchase_time'] : null;
                $response[$key]['package_uuid'] = !empty($value['package_uuid']) ? $value['package_uuid'] : null;
                $response[$key]['promo_name'] = !empty($value['name']) ? $value['name'] : null;
                $response[$key]['notification_type'] = $value['notification_type'];

                if ($value['is_read'] == 0) {
                    $response[$key]['is_read'] = false;
                } elseif ($value['is_read'] == 1) {
                    $response[$key]['is_read'] = true;
                }

                $response[$key]['created_at'] = $value['created_at'];
                if (!empty($value['sender_freelancer'])) {
                    $response[$key]['sender_profile_image'] = !empty($value['sender_freelancer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $value['sender_freelancer']['profile_image'] : null;
                    $response[$key]['name'] = $value['sender_freelancer']['first_name'] . ' ' . $value['sender_freelancer']['last_name'];
                } elseif (!empty($value['sender_customer'])) {
                    $response[$key]['sender_profile_image'] = !empty($value['sender_customer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $value['sender_customer']['profile_image'] : null;
                    $response[$key]['name'] = $value['sender_customer']['first_name'] . ' ' . $value['sender_customer']['last_name'];
                } else {
                    $response[$key]['sender_profile_image'] = null;
                    $response[$key]['name'] = "Admin";
                }
            }
        }

        return $response;
    }

    public static function updateNotificationStatus($inputs) {
        $validation = Validator::make($inputs, NotificationValidationHelper::updateNotificationStatusRules()['rules'], NotificationValidationHelper::updateNotificationStatusRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $ids = [];
        if (empty($inputs['notification_uuid'])) {
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['empty_notification_uuid']);
        }
        foreach ($inputs['notification_uuid'] as $index => $uuid) {
            if (empty($uuid)) {
                DB::rollback();
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['empty_notification_uuid']);
            }
            if (!in_array($uuid, $ids)) {
                array_push($ids, $uuid);
            }
        }
        $data = ['is_read' => 1];
        $update_status = Notification::updateNotificationStatus('notification_uuid', $ids, $data);
        if (!$update_status) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_notification_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

    public static function getNotificationsBadgeCount($inputs) {
        $validation = Validator::make($inputs, NotificationValidationHelper::getNotificationRules()['rules'], NotificationValidationHelper::getNotificationRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $check_notification = Notification::getNotificationCount('receiver_uuid', $inputs['profile_uuid']);
        $response['unread_notification_count'] = $check_notification;
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }





}

?>
