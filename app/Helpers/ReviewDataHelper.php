<?php

namespace App\Helpers;

Class ReviewDataHelper {
    /*
      |--------------------------------------------------------------------------
      | ReviewDataHelper that contains all the Freelancer data methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
      |
     */

    public static function makeFreelancerReviewResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $row) {
                $response[$key]['review_uuid'] = $row['review_uuid'];
                $response[$key]['rating'] = $row['rating'];
                $response[$key]['review'] = $row['review'];
                $response[$key]['content_uuid'] = $row['content_id'];
                $response[$key]['type'] = $row['type'];
                $response[$key]['date'] = date("Y-m-d", strtotime($row['created_at']));
                $response[$key]['reviewer'] = self::getReviewerResponse($row['customer']);
                $response[$key]['reply'] = self::getReviewReplyResponse(!empty($row['reply']) ? $row['reply'] : []);
            }
        }
        return $response;
    }

    public static function getReviewerResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['reviewer_uuid'] = $data['customer_uuid'];
            $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
            $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
//            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['profile_image'] : null;
            $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($data['profile_image']);
        }
        return $response;
    }

    public static function getReviewReplyResponse($data = []) {
        $response = null;
        if (!empty($data)) {
            $response['reply_uuid'] = $data['reply_uuid'];
            $response['user_uuid'] = !empty($data['freelancer']) ? $data['freelancer']['freelancer_uuid'] : null;
            $response['reply'] = !empty($data['reply']) ? $data['reply'] : null;
            $response['date'] = date("Y-m-d", strtotime($data['created_at']));
//            if (!empty($data['customer'])) {
//                $response['first_name'] = !empty($data['customer']['first_name']) ? $data['customer']['first_name'] : null;
//                $response['last_name'] = !empty($data['customer']['last_name']) ? $data['customer']['last_name'] : null;
//                $response['profile_image'] = !empty($data['customer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['customer']['profile_image'] : null;
//                $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($data['customer']['profile_image']);
//            }
            if (!empty($data['freelancer'])) {
                $response['first_name'] = !empty($data['freelancer']['first_name']) ? $data['freelancer']['first_name'] : null;
                $response['last_name'] = !empty($data['freelancer']['last_name']) ? $data['freelancer']['last_name'] : null;
                $response['profile_image'] = !empty($data['freelancer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['freelancer']['profile_image'] : null;
                $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['freelancer']['profile_image']);
            }
        }
        return $response;
    }

    public static function prepareReviewResponse($data = []) {

        $response = [];
        if (!empty($data)) {
            $response['uuid'] = !empty($data['review_uuid']) ? $data['review_uuid'] : null;
            $response['created_at'] = !empty($data['created_at']) ? $data['created_at'] : null;
            $response['first_name'] = !empty($data['customer']['first_name']) ? $data['customer']['first_name'] : null;
            $response['last_name'] = !empty($data['customer']['last_name']) ? $data['customer']['last_name'] : null;
            $response['title'] = !empty($data['appointment']['title']) ? $data['appointment']['title'] : null;
            $response['rating'] = !empty($data['rating']) ? $data['rating'] : 0;
            $response['review'] = !empty($data['review']) ? $data['review'] : null;
            $response['type'] = !empty($data['type']) ? $data['type'] : null;
            $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($data['customer']['profile_image']);
            $response['reply'] = self::getReviewReplyResponse(!empty($data['reply']) ? $data['reply'] : []);
            $time_con = CommonHelper::convertTimeToTimezone($data['appointment']['from_time'], $data['appointment']['saved_timezone'], $data['appointment']['local_timezone']);
            $response['appointment_date'] = $data['appointment']['appointment_date'] . ' ' . $time_con;
        }
        return $response;
    }

}

?>
