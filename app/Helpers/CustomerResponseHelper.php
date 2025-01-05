<?php

namespace App\Helpers;

use App\Notification;
use App\Appointment;
use App\Wallet;

Class CustomerResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | CustomerResponseHelper that contains all the customer methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use customer processes
      |
     */

    public static function prepareSignupResponse($data = []) {
        $response = [];
        $response['customer_uuid'] = $data['customer_uuid'];
        $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
        $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
        $response['email'] = !empty($data['email']) ? $data['email'] : null;
        $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
        $response['country_code'] = !empty($data['country_code']) ? $data['country_code'] : null;
        $response['country_name'] = !empty($data['country_name']) ? $data['country_name'] : null;
        $response['profile_images'] = self::customerProfileImagesResponse($data['profile_image']);
        $response['cover_image'] = !empty($data['cover_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_cover_image'] . $data['cover_image'] : null;
        $response['cover_images'] = self::customerCoverImagesResponse($data['cover_image']);
        $response['gender'] = !empty($data['gender']) ? $data['gender'] : null;
        $response['onboard_count'] = !empty($data['onboard_count']) ? $data['onboard_count'] : null;
        $response['currency'] = !empty($data['default_currency']) ? $data['default_currency'] : null;
        if (!empty($data['address'])) {
            $response['location']['address'] = !empty($data['address']) ? $data['address'] : null;
            $response['location']['lat'] = !empty($data['lat']) ? $data['lat'] : null;
            $response['location']['lng'] = !empty($data['lng']) ? $data['lng'] : null;
        } else {
            $response['location'] = null;
        }
        $response['keys'] = CommonHelper::configKeys();
        return $response;
    }

    public static function prepareNewSignupResponse($data = []) {
        $response = [];
        $response['customer_uuid'] = $data['new_customer_uuid'];
        $response['email'] = !empty($data['email']) ? $data['email'] : null;
        $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
        $response['ip_address'] = !empty($data['ip_address']) ? $data['ip_address'] : null;
        $response['user_type'] = !empty($data['user_type']) ? $data['user_type'] : null;
        $response['module'] = !empty($data['module']) ? $data['module'] : null;
        return $response;
    }

    public static function customerProfileImagesResponse($image_key = null) {
        $response = null;
        $response['1122'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_1122'] . $image_key : null;
        $response['420'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_420'] . $image_key : null;
        $response['336'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_336'] . $image_key : null;
        $response['240'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_240'] . $image_key : null;
        $response['96'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_96'] . $image_key : null;
        $response['orignal'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $image_key : null;
        return $response;
    }

    public static function adminProfileImagesResponse() {
        $response = null;
        $response['1122'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/6012a4e8733201611834600.png";
        $response['420'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/6012a4e8733201611834600.png";
        $response['336'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/6012a4e8733201611834600.png";
        $response['240'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/6012a4e8733201611834600.png";
        $response['96'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/6012a4e8733201611834600.png";
        $response['orignal'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/6012a4e8733201611834600.png";
//      $response['orignal'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/601298f16c8851611831537.png";

        return $response;
    }

    public static function customerCoverImagesResponse($image_key = null) {
        $response = null;
        $response['1122'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_1122'] . $image_key : null;
        $response['420'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_420'] . $image_key : null;
        $response['336'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_336'] . $image_key : null;
        $response['240'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_240'] . $image_key : null;
        $response['96'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_thumb_96'] . $image_key : null;
        $response['orignal'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_cover_image'] . $image_key : null;
        return $response;
    }

    public static function prepareLoginResponse($data = []) {
        $response = null;

        $response['balance'] = Wallet::getWalletTotalAmount($data);
        $response['customer_uuid'] = $data['customer_uuid'];
        $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
        $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
        $response['email'] = !empty($data['email']) ? $data['email'] : null;
        $response['country_code'] = !empty($data['country_code']) ? $data['country_code'] : null;
        $response['country_name'] = !empty($data['country_name']) ? $data['country_name'] : null;
        $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
        $response['profile_images'] = self::customerProfileImagesResponse($data['profile_image']);
//        $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['profile_image'] : null;
        $response['cover_image'] = !empty($data['cover_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_cover_image'] . $data['cover_image'] : null;
        $response['cover_images'] = self::customerCoverImagesResponse($data['cover_image']);
        $response['gender'] = !empty($data['gender']) ? $data['gender'] : null;
        $response['onboard_count'] = $data['onboard_count'];
        $response['keys'] = CommonHelper::configKeys();

        $response['appointment_badge_count'] = Notification::getNotificationBadgeCount('receiver_id', $data['id'], 'appointment');
        $response['profile_badge_count'] = Notification::getNotificationBadgeCount('receiver_id', $data['user_id'], 'other');
        $response['chat_unread_count'] = InboxHelper::getUnreadChatCount($data['customer_uuid']);
        $response['is_added_interest'] = CustomerLoginHelper::checkCustomerInterests($data);
        $response['feedback'] = !empty($data['appointment_review']) ? FreelancerResponseHelper::appointmentReviewResponse($data['appointment_review']) : null;

        $response['currency'] = !empty($data['default_currency']) ? $data['default_currency'] : null;
        $response['industry_image'] = isset($data['interests'][0]['category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $data['interests'][0]['category']['image'] : null;
        $response['customer_type'] = !empty($data['type']) ? $data['type'] : 'regular';


        if (!empty($data['address'])) {
            $response['location']['address'] = !empty($data['address']) ? $data['address'] : null;
            $response['location']['lat'] = !empty($data['lat']) ? $data['lat'] : null;
            $response['location']['lng'] = !empty($data['lng']) ? $data['lng'] : null;
            $response['location']['address_comments'] = !empty($data['address_comments']) ? $data['address_comments'] : null;
        } else {
            $response['location'] = null;
        }
        $response['environment'] = ENV('ENVIRONMENT');

        return $response;
    }

    public static function prepareGuestLoginResponse($data = []) {
        $response = null;
        $response['customer_uuid'] = $data['customer_uuid'];
        $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
        $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
        $response['email'] = !empty($data['email']) ? $data['email'] : null;
        $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
        $response['profile_images'] = self::customerProfileImagesResponse($data['profile_image']);
//        $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['profile_image'] : null;
        $response['cover_image'] = !empty($data['cover_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_cover_image'] . $data['cover_image'] : null;
        $response['cover_images'] = self::customerCoverImagesResponse($data['cover_image']);
        $response['gender'] = !empty($data['gender']) ? $data['gender'] : null;
        $response['location'] = null;
        $response['business_cover_image'] = null;
        $response['appointment_badge_count'] = null; /* Notification::getNotificationBadgeCount('receiver_uuid', $data['customer_uuid'], 'appointment'); */
        $response['profile_badge_count'] = null; /* Notification::getNotificationBadgeCount('receiver_uuid', $data['customer_uuid'], 'other'); */
        return $response;
    }

    public static function customerListResponse($data = [], $inputs = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $customer) {
                $response[$key]['id'] = $customer['id'];
                $response[$key]['customer_uuid'] = $customer['customer_uuid'];
                $response[$key]['type'] = "customer";
                $response[$key]['first_name'] = $customer['first_name'];
                $response[$key]['last_name'] = $customer['last_name'];
                $response[$key]['email'] = $customer['email'];
                $response[$key]['phone_number'] = $customer['phone_number'];
                $response[$key]['gender'] = $customer['gender'];
                $response[$key]['freelancer_id'] = $customer['freelancer_id'];
                $response[$key]['appointments_count'] = Appointment::getClientAppointmentsCount($inputs['freelancer_id'], $customer['id']);
                $response[$key]['profile_images'] = self::customerProfileImagesResponse($customer['profile_image']);
                $response[$key]['posts'] = PostResponseHelper::getMultiPostResponse($customer);
            }
        }
        return $response;
    }

    public static function ClientsListResponse($data = [], $inputs = []) {

        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $customer) {


                $response[$key]['id'] = $customer['customer']['id'];
                $response[$key]['customer_uuid'] = $customer['customer']['customer_uuid'];
                $response[$key]['type'] = !empty($customer['customer']['freelancer_id']) ? 'walkin_customer' : 'customer';
                $response[$key]['first_name'] = $customer['customer']['first_name'];
                $response[$key]['last_name'] = $customer['customer']['last_name'];
                $response[$key]['email'] = $customer['customer']['email'];
                $response[$key]['phone_number'] = $customer['customer']['phone_number'];
                $response[$key]['gender'] = $customer['customer']['gender'];
                $response[$key]['appointments_count'] = Appointment::getClientAppointmentsCount($inputs['freelancer_id'], $customer['customer']['id']);
//                $response[$key]['profile_image'] = !empty($customer['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $customer['profile_image'] : null;
                $response[$key]['profile_images'] = self::customerProfileImagesResponse($customer['customer']['profile_image']);
                $response[$key]['posts'] = PostResponseHelper::getMultiPostResponse($customer['customer']);
            }
        }

        return $response;
    }

    public static function updateCustomerListResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $customer) {
                $response[$key]['customer_uuid'] = !empty($customer['customer_uuid']) ? $customer['customer_uuid'] : null;
                $response[$key]['first_name'] = !empty($customer['first_name']) ? $customer['first_name'] : null;
                $response[$key]['last_name'] = !empty($customer['last_name']) ? $customer['last_name'] : null;
                $response[$key]['email'] = !empty($customer['email']) ? $customer['email'] : null;
                $response[$key]['phone_number'] = !empty($customer['phone_number']) ? $customer['phone_number'] : null;
                $response[$key]['gender'] = !empty($customer['gender']) ? $customer['gender'] : null;
                $response[$key]['onboard_count'] = !empty($customer['onboard_count']) ? $customer['onboard_count'] : null;
                $response[$key]['country_code'] = !empty($customer['country_code']) ? $customer['country_code'] : null;
                $response[$key]['country_name'] = !empty($customer['country_name']) ? $customer['country_name'] : null;
                $response[$key]['date_of_birth'] = !empty($customer['dob']) ? $customer['dob'] : null;
                $response[$key]['profile_images'] = self::customerProfileImagesResponse($customer['profile_image']);
                $response[$key]['industry_image'] = isset($customer['interests'][0]['category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $customer['interests'][0]['category']['image'] : null;
                $response[$key]['cover_images'] = self::customerCoverImagesResponse($customer['cover_image']);
                if (!empty($customer['address'])) {
                    $response[$key]['location']['address'] = !empty($customer['address']) ? $customer['address'] : null;
                    $response[$key]['location']['lat'] = !empty($customer['lat']) ? $customer['lat'] : null;
                    $response[$key]['location']['lng'] = !empty($customer['lng']) ? $customer['lng'] : null;
                    $response[$key]['location']['address_comments'] = !empty($customer['address_comments']) ? $customer['address_comments'] : null;
                } else {
                    $response[$key]['location'] = null;
                }
                $response[$key]['chat_unread_count'] = InboxHelper::getUnreadChatCount($customer['customer_uuid']);
            }
        }
        return $response;
    }

}

?>
