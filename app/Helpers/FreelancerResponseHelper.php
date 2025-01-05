<?php

namespace App\Helpers;

use App\Freelancer;
use App\Subscription;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Preview\TrustedComms\CpsContext;
use Illuminate\Support\Facades\URL;

Class FreelancerResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerResponseHelper that contains all the Freelancer response methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
      |
     */

    public static function prepareSignupResponse($data = []) {
        $response = [];
        $response['freelancer_uuid'] = $data['freelancer_uuid'];
        $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
        $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
        $response['company'] = !empty($data['company']) ? $data['company'] : null;
        $response['email'] = !empty($data['email']) ? $data['email'] : null;
        $response['profile_type'] = !empty($data['profile_type']) ? $data['profile_type'] : 0;
        $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
        $response['country_code'] = !empty($data['country_code']) ? $data['country_code'] : null;
        $response['country_name'] = !empty($data['country_name']) ? $data['country_name'] : null;
        $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;

//        $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
        $response['profile_images'] = self::freelancerProfileImagesResponse($data['profile_image']);
        $response['cover_image'] = !empty($data['cover_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_cover_image'] . $data['cover_image'] : null;
        $response['cover_images'] = self::freelancerCoverImagesResponse($data['cover_image']);
        $response['gender'] = !empty($data['gender']) ? $data['gender'] : null;
        $response['onboard_count'] = !empty($data['onboard_count']) ? $data['onboard_count'] : null;
        $response['locations'] = LoginHelper::processFreelancerLocationsResponse((!empty($data['locations']) ? $data['locations'] : []));
        $response['can_travel'] = !empty($data['can_travel']) ? $data['can_travel'] : 0;
        $response['travelling_distance'] = !empty($data['travelling_distance']) ? $data['travelling_distance'] : null;
        $response['travelling_cost_per_km'] = !empty($data['travelling_cost_per_km']) ? $data['travelling_cost_per_km'] : null;
        $response['qualifications'] = self::freelancerQualificationResponse(!empty($data['qualifications']) ? $data['qualifications'] : []);
        $response['currency'] = !empty($data['default_currency']) ? $data['default_currency'] : null;
        $response['receive_subscription_request'] = $data['receive_subscription_request'];
        $response['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['profession']) ? $data['profession'] : []);
        $response['has_bank_detail'] = ($data['has_bank_detail'] == 1) ? true : false;
        $response['keys'] = CommonHelper::configKeys();
        return $response;
    }

    public static function freelancerProfileImagesResponse($image_key = null) {
        $response = null;
        $response['1122'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_1122'] . $image_key : null;
        $response['420'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_420'] . $image_key : null;
        $response['336'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_336'] . $image_key : null;
        $response['240'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_240'] . $image_key : null;
        $response['96'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_96'] . $image_key : null;
        $response['orignal'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $image_key : null;
        Log::info($response);
        return $response;
    }

    public static function freelancerCoverImagesResponse($image_key = null) {
        $response = null;
        $response['1122'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_1122'] . $image_key : null;
        $response['420'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_420'] . $image_key : null;
        $response['336'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_336'] . $image_key : null;
        $response['240'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_240'] . $image_key : null;
        $response['96'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_thumb_96'] . $image_key : null;
        $response['orignal'] = !empty($image_key) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_cover_image'] . $image_key : null;
        return $response;
    }

    public static function freelancerProfileResponse($data = [], $extra_features = [], $local_timezone = 'UTC', $to_currency = 'SAR') {

        $response = [];
        if (!empty($data)) {
            $freelancer_categories = CategoryHelper::prepareSearchFreelancerCategoryResponse($data['freelancer_categories']);
            $flag_online = FreelancerSearchHelper::checkOnlineFaceToFaceFlag($freelancer_categories);
            $response['freelancer_uuid'] = $data['freelancer_uuid'];

            $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
            $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
            $response['company'] = !empty($data['company']) ? $data['company'] : null;
            $response['company_logo'] = !empty($data['company_logo']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['company_logo'] . $data['company_logo'] : null;
            $response['email'] = !empty($data['email']) ? $data['email'] : null;
            $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
            $response['country_code'] = !empty($data['country_code']) ? $data['country_code'] : null;
            $response['country_name'] = !empty($data['country_name']) ? $data['country_name'] : null;
//            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
            $response['profile_images'] = self::freelancerProfileImagesResponse($data['profile_image']);
            $response['profile_card_image'] = !empty($data['profile_card_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_card_image'] : null;
            $response['cover_image'] = !empty($data['cover_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_cover_image'] . $data['cover_image'] : null;
            $response['cover_images'] = self::freelancerCoverImagesResponse($data['cover_image']);
            $response['cover_video'] = !empty($data['cover_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['cover_video'] . $data['cover_video'] : null;
            $response['cover_video_thumb'] = !empty($data['cover_video_thumb']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['cover_video'] . $data['cover_video_thumb'] : null;
//            $response['cover_video_thumb'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";
            $response['gender'] = !empty($data['gender']) ? $data['gender'] : null;
            $response['business_cover_image'] = (!empty($data['freelancer_categories']) && !empty($data['freelancer_categories'][0]['category']) && !empty($data['freelancer_categories'][0]['category']['image'])) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $data['freelancer_categories'][0]['category']['image'] : null;
            $response['bio'] = !empty($data['bio']) ? $data['bio'] : null;
            $response['followers_count'] = !empty($extra_features['followers_count']) ? $extra_features['followers_count'] : 0;
            $response['reviews_count'] = !empty($data['reviews']) ? count($data['reviews']) : 0;
            $review_avg = array_sum(array_column($data['reviews'], 'rating'));
            $response['reviews_avg'] = $response['reviews_count'] > 0 && $review_avg > 0 ? round($review_avg / $response['reviews_count'], 2) : 0;

            $response['post_count'] = !empty($extra_features['post_count']) ? $extra_features['post_count'] : 0;
//            $response['has_story'] = true;
            $response['has_story'] = !empty($extra_features['story']) ? true : false;
            $response['stories'] = !empty($extra_features['story']) ? StoryResponseHelper::processStoriesResponse($extra_features['story']) : null;
            $response['is_following'] = !empty($extra_features['is_following']) ? $extra_features['is_following'] : false;
            $response['is_favourite'] = !empty($extra_features['is_favourite']) ? $extra_features['is_favourite'] : false;
            $response['onboard_count'] = !empty($data['onboard_count']) ? $data['onboard_count'] : null;
            $response['profile_type'] = !empty($data['profile_type']) ? $data['profile_type'] : 0;
            $response['skills'] = !empty($data['skills']) ? $data['skills'] : null;
            $response['qualifications'] = self::freelancerQualificationResponse(!empty($data['qualifications']) ? $data['qualifications'] : []);
            $response['freelancer_categories'] = self::freelancerCategoriesResponse(!empty($data['freelancer_categories']) ? $data['freelancer_categories'] : null);
            $response['locations'] = LoginHelper::processFreelancerLocationsResponse((!empty($data['locations']) ? $data['locations'] : []));
            $response['social_media'] = self::freelancerSocialMediaResponse((!empty($data['social_media']) ? $data['social_media'] : []));

            $response['subscription_settings'] = self::freelancerSubscription_settings((!empty($data['subscription_settings']) ? $data['subscription_settings'] : []));

            $response['can_travel'] = !empty($data['can_travel']) ? $data['can_travel'] : 0;
            $response['travelling_distance'] = !empty($data['travelling_distance']) ? $data['travelling_distance'] : null;
            $response['travelling_cost_per_km'] = !empty($data['travelling_cost_per_km']) ? $data['travelling_cost_per_km'] : null;
            //$response['travelling_cost_per_km'] = !empty($data['travelling_cost_per_km']) ? CommonHelper::getConvertedCurrency($data['travelling_cost_per_km'], $data['default_currency'], $to_currency)  : null;
            $response['is_business'] = !empty($data['is_business']) ? $data['is_business'] : 0;
            $response['business_name'] = !empty($data['business_name']) ? $data['business_name'] : null;
            $response['business_logo'] = !empty($data['business_logo']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['mobile_uploads'] . $data['business_logo'] : null;

            $response['purchase_subscription'] = !empty($data['subscriptions']) ? self::freelancerSubscription($data['subscriptions'], $local_timezone) : null;
            $response['cancel_subscription'] = !empty($data['subscriptions']) ? $data['subscriptions'][0]['auto_renew'] : 1;

            $response['average_rating'] = !empty($extra_features['reviews']) ? self::countRating($extra_features['reviews']) : self::countRating($data['reviews']);
            $response['provides_online_services'] = $flag_online['is_online'];
            $response['provides_face_to_face'] = $flag_online['is_face_to_face'];
            $response['receive_subscription_request'] = $data['receive_subscription_request'];
            $response['currency'] = !empty($data['default_currency']) ? $data['default_currency'] : null;
            $response['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['profession']) ? $data['profession'] : []);
            $response['has_bank_detail'] = ($data['has_bank_detail'] == 1) ? true : false;
            $response['share_url'] = self::prepareShareProfileURL($data);

            $response['added_subscription_once'] = SubscriptionHelper::checkFreelancerSubscriptionSettingExists($data);
            $response['chat_unread_count'] = InboxHelper::getUnreadChatCount($data['freelancer_uuid']);

            if (!empty($data['saved_category'])) {
                $response['freelancer_industry'] = LoginHelper::prepareIndustryResponse($data);
            }
        }

        return $response;
    }

    public static function freelancerSubscription_settings($data = []) {

        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {

                $response[$key]['subscription_settings_uuid'] = (isset($value['subscription_settings_uuid'])) ? $value['subscription_settings_uuid'] : $value['subscription_settings_id'];
                $response[$key]['price'] = $value['price'];
                $response[$key]['currency'] = $value['currency'];
                $response[$key]['type'] = $value['type'];
            }
        }
        return $response;
    }

    public static function freelancerSubscriptionSettingsCurrency($data = [], $from_currency = 'SAR', $to_currency = 'Pound') {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $response[$key]['subscription_settings_uuid'] = $value['subscription_settings_uuid'];
                $response[$key]['price'] = !empty($value['price']) ? (double) CommonHelper::getConvertedCurrency($value['price'], $from_currency, $to_currency) : 0;
                $response[$key]['currency'] = $value['currency'];
                $response[$key]['type'] = $value['type'];
            }
        }
        return $response;
    }

    public static function freelancerSubscription($data = [], $local_timezone) {
        $response = null;
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $subscription_date = strtotime($value['subscription_date']);
                $sub_typ = $value['subscription_setting']['type'];

                if ($sub_typ == 'monthly') {
                    $subscription_date = strtotime("+1 month", $subscription_date);
                }
                if ($sub_typ == 'quarterly') {
                    $subscription_date = strtotime("+3 month", $subscription_date);
                }
                if ($sub_typ == 'annual') {
                    $subscription_date = strtotime("+12 month", $subscription_date);
                }

                if ($subscription_date > strtotime(date('Y-m-d H:i:s'))) {
                    $subscription_date = CommonHelper::convertDateTimeToTimezone($value['subscription_date'], 'UTC', $local_timezone);
                    $response['subscription_uuid'] = $value['subscription_uuid'];
                    $response['subscription_settings_uuid'] = $value['subscription_settings_id'];
                    $response['subscription_date'] = $subscription_date;
                    $response['price'] = $value['subscription_setting']['price'];
                    $response['currency'] = $value['subscription_setting']['currency'];
                    $response['type'] = $value['subscription_setting']['type'];
                }
            }
        }
        return $response;
    }

    public static function freelancerSocialMediaResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $response[$key]['social_media_uuid'] = $value['social_media_uuid'];
                $response[$key]['social_media_link'] = $value['social_media_link'];
                $response[$key]['social_media_type'] = $value['social_media_type'];
            }
        }
        return $response;
    }

    public static function freelancerQualificationResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $response[$key]['qualification_uuid'] = $value['qualification_uuid'];
                $response[$key]['title'] = $value['title'];
                $response[$key]['description'] = $value['description'];
            }
        }
        return $response;
    }

    public static function freelancerCategoriesResponse($data = []) {
        $response = null;

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $response[$key]['freelancer_category_uuid'] = $value['freelancer_category_uuid'];
                $response[$key]['category_uuid'] = CommonHelper::getRecordByUuid('categories', 'id', $value['category_id'], 'category_uuid');
                $response[$key]['sub_category_uuid'] = CommonHelper::getRecordByUuid('sub_categories', 'id', $value['sub_category_id'], 'sub_category_uuid');
                $response[$key]['name'] = $value['name'];
                $response[$key]['is_online'] = !empty($value['is_online']) ? $value['is_online'] : 0;
            }
        }
        return $response;
    }

    public static function makeFreelancerSucscriptionArr($data = [], $to_currency = 'SAR') {
        $response = [];
        foreach ($data as $key => $val) {
            $response[] = array(
                'subscription_settings_uuid' => $val['subscription_settings_uuid'],
                'freelancer_uuid' => CommonHelper::getRecordByUuid('freelancers', 'id', $val['freelancer_id'], 'freelancer_uuid'),
                'type' => $val['type'],
                'price' => !empty($val['price']) ? (double) CommonHelper::getConvertedCurrency($val['price'], $val['currency'], $to_currency) : 0,
                'currency' => $val['currency']
            );
        }
        return $response;
    }

    public static function freelancerProfileResponseWithPost($data) {
        $response = [];
        if (!empty($data)) {
            $response['freelancer_uuid'] = $data['freelancer_uuid'];
            $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
            $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
            $response['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['profession']) ? $data['profession'] : []);
            $response['company'] = !empty($data['company']) ? $data['company'] : null;
            $response['company_logo'] = !empty($data['company_logo']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['company_logo'] . $data['company_logo'] : null;
            $response['email'] = !empty($data['email']) ? $data['email'] : null;
            $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
            $response['country_code'] = !empty($data['country_code']) ? $data['country_code'] : null;
            $response['country_name'] = !empty($data['country_name']) ? $data['country_name'] : null;
//            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
            $response['profile_images'] = self::freelancerProfileImagesResponse($data['profile_image']);
            $response['cover_image'] = !empty($data['cover_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_cover_image'] . $data['cover_image'] : null;
            $response['cover_images'] = self::freelancerCoverImagesResponse($data['cover_image']);
            $response['cover_video'] = !empty($data['cover_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['cover_video'] . $data['cover_video'] : null;
            $response['cover_video_thumb'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";
            $response['gender'] = !empty($data['gender']) ? $data['gender'] : null;
            $response['bio'] = !empty($data['bio']) ? $data['bio'] : null;
            $response['has_story'] = true;
            $response['is_following'] = !empty($is_following) ? true : false;
            $response['onboard_count'] = !empty($data['onboard_count']) ? $data['onboard_count'] : null;
            $response['skills'] = !empty($data['skills']) ? $data['skills'] : null;
            $response['posts'] = PostResponseHelper::getMultiPostResponse($data);
        }
        return $response;
    }

    public static function prepareRecommendedUserResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['freelancer_uuid'] = $data['freelancer_uuid'];
            $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
            $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
            $response['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['profession']) ? $data['profession'] : []);
            $response['reviews_count'] = count($data['reviews']);
            $response['average_rating'] = self::countRating($data['reviews']);
            //$response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
            $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['profile_image']);
        }
        return $response;
    }

    public static function countRating($data = []) {
        $response = 0;
        if (!empty($data)) {
            $rating = 0;
            $key = 0;
            foreach ($data as $key => $value) {
                $rating = ($rating + ((!empty($value['rating'])) ? $value['rating'] : 0));
            }
            $response = ($rating / ($key + 1));
        }
        return $response;
    }

    public static function prepareSuggestedProfilesResponse($suggestions = []) {
        $response = [];
        if (!empty($suggestions)) {
            foreach ($suggestions as $key => $value) {
                $response[$key]['freelancer_uuid'] = $value['freelancer_uuid'];
                $response[$key]['first_name'] = !empty($value['first_name']) ? $value['first_name'] : null;
                $response[$key]['last_name'] = !empty($value['last_name']) ? $value['last_name'] : null;
                $response[$key]['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($value['profession']) ? $value['profession'] : []);
                //$response[$key]['profile_image'] = !empty($value['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $value['profile_image'] : null;
                $response[$key]['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($value['profile_image']);
            }
        }
        return $response;
    }

    public static function prepareSubscribedProfilesResponse($subscriptions = []) {
        $response = [];
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $key => $value) {
                $response[$key]['freelancer_uuid'] = $value['freelancer_uuid'];
                $response[$key]['first_name'] = !empty($value['first_name']) ? $value['first_name'] : null;
                $response[$key]['last_name'] = !empty($value['last_name']) ? $value['last_name'] : null;
                $response[$key]['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($value['profession']) ? $value['profession'] : []);
                //$response[$key]['profile_image'] = !empty($value['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $value['profile_image'] : null;
                $response[$key]['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($value['profile_image']);
            }
        }
        return $response;
    }

    public static function prepareReviewedProfilesResponse($reviews = []) {

        $response = [];
        if (!empty($reviews)) {
            foreach ($reviews as $key => $value) {
                $response[$key]['freelancer_uuid'] = $value['freelancer_uuid'];
                $response[$key]['first_name'] = !empty($value['first_name']) ? $value['first_name'] : null;
                $response[$key]['last_name'] = !empty($value['last_name']) ? $value['last_name'] : null;
                $response[$key]['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($value['profession']) ? $value['profession'] : []);
                //$response[$key]['profile_image'] = !empty($value['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $value['profile_image'] : null;
                $response[$key]['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($value['profile_image']);

                $response[$key]['review'] = !empty($value['latest_reviews']) ? self::freelancerReviewsProfileResponse($value['latest_reviews']) : null;

                $response[$key]['review_created_at'] = !empty($value['latest_reviews']) ? $value['latest_reviews'][0]['created_at'] : null;
            }
        }

        $name = 'review_created_at';
        usort($response, function ($a, $b) use (&$name) {
            return strtotime($b[$name]) - strtotime($a[$name]);
        });

        return $response;
    }

    public static function freelancerReviewsProfileResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            //foreach ($data as $key => $value) {
//                $response['review_uuid'] = $data['review_uuid'];
            $response['rating'] = !empty($data[0]['average_rating']) ? (double) $data[0]['average_rating'] : null;
            $response['review'] = !empty($data[0]['review']) ? $data[0]['review'] : null;
//                $response['review'] = !empty($data['review']) ? $data['review'] : null;
            //}
        }

        return $response;
    }

    public static function appointmentReviewResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['uuid'] = !empty($data['appointment_uuid']) ? $data['appointment_uuid'] : null;
            $response['freelancer_uuid'] = !empty($data['appointment_freelancer']['freelancer_uuid']) ? $data['appointment_freelancer']['freelancer_uuid'] : null;
            $response['first_name'] = !empty($data['appointment_freelancer']['first_name']) ? $data['appointment_freelancer']['first_name'] : null;
            $response['last_name'] = !empty($data['appointment_freelancer']['last_name']) ? $data['appointment_freelancer']['last_name'] : null;
            $response['title'] = !empty($data['title']) ? $data['title'] : null;
            $response['type'] = !empty($data['type']) ? $data['type'] : null;
            $response['created_at'] = !empty($data['created_at']) ? $data['created_at'] : null;
            $time_con = CommonHelper::convertTimeToTimezone($data['from_time'], $data['saved_timezone'], $data['local_timezone']);
            $response['appointment_date'] = $data['appointment_date'] . ' ' . $time_con;
            $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['appointment_freelancer']['profile_image']);
        }
        return $response;
    }

    public static function appointmentReviewCustomerResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $response['uuid'] = !empty($value['review_uuid']) ? $value['review_uuid'] : null;
                $response['created_at'] = !empty($value['created_at']) ? $value['created_at'] : null;
                $response['first_name'] = !empty($value['customer']['first_name']) ? $value['customer']['first_name'] : null;
                $response['last_name'] = !empty($value['customer']['last_name']) ? $value['customer']['last_name'] : null;
                $response['title'] = !empty($value['appointment']['title']) ? $value['appointment']['title'] : null;
                $response['rating'] = !empty($value['rating']) ? $value['rating'] : 0;
                $response['review'] = !empty($value['review']) ? $value['review'] : null;
                $response['type'] = !empty($value['type']) ? $value['type'] : null;
                $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($value['customer']['profile_image']);
            }
        }
        return $response;
    }

    public static function freelancerProfileCurrencyResponse($data = [], $extra_features = [], $local_timezone = 'UTC', $to_currency = 'SAR', $login_user_type = null, $inputs = null) {
        $response = [];

        if (!empty($data)) {

            $url = $_SERVER['HTTP_HOST'];
            $freelancer_categories = CategoryHelper::prepareSearchFreelancerCategoryResponse($data['freelancer_categories']);

            $flag_online = FreelancerSearchHelper::checkOnlineFaceToFaceFlag($freelancer_categories);

            $response['freelancer_uuid'] = $data['freelancer_uuid'];

            $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
            $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
            $response['company'] = !empty($data['company']) ? $data['company'] : null;
            $response['company_logo'] = !empty($data['company_logo']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['company_logo'] . $data['company_logo'] : null;
            $response['email'] = !empty($data['email']) ? $data['email'] : null;
            $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
            $response['country_code'] = !empty($data['country_code']) ? $data['country_code'] : null;
            $response['country_name'] = !empty($data['country_name']) ? $data['country_name'] : null;
//            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
            $response['profile_images'] = self::freelancerProfileImagesResponse($data['profile_image']);
            $response['profile_card_image'] = !empty($data['profile_card_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_card_image'] : null;
            $response['cover_image'] = !empty($data['cover_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_cover_image'] . $data['cover_image'] : null;
            $response['cover_images'] = self::freelancerCoverImagesResponse($data['cover_image']);
            $response['cover_video'] = !empty($data['cover_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['cover_video'] . $data['cover_video'] : null;
            $response['cover_video_thumb'] = !empty($data['cover_video_thumb']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['cover_video'] . $data['cover_video_thumb'] : null;
//            $response['cover_video_thumb'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";
            $response['gender'] = !empty($data['gender']) ? $data['gender'] : null;
            $response['business_cover_image'] = (!empty($data['freelancer_categories']) && !empty($data['freelancer_categories'][0]['category']) && !empty($data['freelancer_categories'][0]['category']['image'])) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $data['freelancer_categories'][0]['category']['image'] : null;
            $response['bio'] = !empty($data['bio']) ? $data['bio'] : null;
            $response['followers_count'] = !empty($extra_features['followers_count']) ? $extra_features['followers_count'] : 0;
            $response['reviews_count'] = !empty($extra_features['reviews_count']) ? $extra_features['reviews_count'] : 0;
            $response['reviews_avg'] = !empty($extra_features['reviews_avg']) ? $extra_features['reviews_avg'] : 0;
            $response['post_count'] = !empty($extra_features['post_count']) ? $extra_features['post_count'] : 0;
//            $response['has_story'] = true;

            $response['has_story'] = !empty($extra_features['story']) ? true : false;
            $response['stories'] = !empty($extra_features['story']) ? StoryResponseHelper::processStoriesResponse($extra_features['story'], $extra_features['data_to_validate'], $login_user_type) : null;

            $response['is_following'] = !empty($extra_features['is_following']) ? $extra_features['is_following'] : false;
            $response['is_favourite'] = !empty($extra_features['is_favourite']) ? $extra_features['is_favourite'] : false;
            $response['onboard_count'] = !empty($data['onboard_count']) ? $data['onboard_count'] : null;
            $response['profile_type'] = !empty($data['profile_type']) ? $data['profile_type'] : 0;
            $response['skills'] = !empty($data['skills']) ? $data['skills'] : null;

            $response['qualifications'] = self::freelancerQualificationResponse(!empty($data['qualifications']) ? $data['qualifications'] : []);

            $response['freelancer_categories'] = self::freelancerCategoriesResponse(!empty($data['freelancer_categories']) ? $data['freelancer_categories'] : null);

            $response['locations'] = LoginHelper::processFreelancerLocationsResponse((!empty($data['locations']) ? $data['locations'] : []));

            $response['social_media'] = self::freelancerSocialMediaResponse((!empty($data['social_media']) ? $data['social_media'] : []));
            $response['subscription_settings'] = !empty($data['subscription_settings']) ? self::freelancerSubscriptionSettingsCurrency($data['subscription_settings'], $data['default_currency'], $to_currency) : [];
            $response['can_travel'] = !empty($data['can_travel']) ? $data['can_travel'] : 0;
            $response['travelling_distance'] = !empty($data['travelling_distance']) ? $data['travelling_distance'] : null;
            //$response['travelling_cost_per_km'] = !empty($data['travelling_cost_per_km']) ? $data['travelling_cost_per_km'] : null;
            $response['travelling_cost_per_km'] = !empty($data['travelling_cost_per_km']) ? CommonHelper::getConvertedCurrency($data['travelling_cost_per_km'], $data['default_currency'], $to_currency) : null;
            $response['is_business'] = !empty($data['is_business']) ? $data['is_business'] : 0;
            $response['business_name'] = !empty($data['business_name']) ? $data['business_name'] : null;

            $response['business_logo'] = !empty($data['business_logo']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['mobile_uploads'] . $data['business_logo'] : null;
            $response['purchase_subscription'] = !empty($data['subscriptions']) ? self::freelancerSubscription($data['subscriptions'], $local_timezone) : null;
            $response['cancel_subscription'] = !empty($data['subscriptions']) ? $data['subscriptions'][0]['auto_renew'] : 1;
            $response['average_rating'] = !empty($extra_features['reviews']) ? self::countRating($extra_features['reviews']) : self::countRating($data['reviews']);
            $response['provides_online_services'] = $flag_online['is_online'];
            $response['provides_face_to_face'] = $flag_online['is_face_to_face'];
            $response['currency'] = !empty($data['default_currency']) ? $data['default_currency'] : null;
            $response['public_chat'] = ($data['public_chat'] == 1) ? true : false;
            // here is the problem
            if($inputs['login_user_type'] == 'freelancer')
            {
                $response['receive_subscription_request'] = $data['receive_subscription_request'];
            }
            else
            {
                $response['receive_subscription_request'] = self::checkFreelancerSubscription($data, $inputs);
            }
            $response['has_subscription_content'] = $data['has_subscription_content'];

            $response['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['profession']) ? $data['profession'] : []);
            $data_string = "freelancer_uuid=" . $data['freelancer_uuid'] . "&currency=" . $data['default_currency'];
            $response['has_bank_detail'] = ($data['has_bank_detail'] == 1) ? true : false;
            $response['freelancer_industry'] = null;

            $response['added_subscription_once'] = SubscriptionHelper::checkFreelancerSubscriptionSettingExists($data);
            if (!empty($data['saved_category'])) {
                $response['freelancer_industry'] = LoginHelper::prepareIndustryResponse($data);
            }

            $encoded_string = base64_encode($data_string);
            if ($login_user_type == "customer") {
                $response['has_subscription'] = $data['check_subscription'];
                $response['has_appointment'] = !empty($data['check_appointment']) ? $data['check_appointment'] : false;
            }
            $response['share_url'] = URL::to('/') . "/getFreelancerProfile?" . $encoded_string;
//            if (strpos($url, 'localhost') !== false) {
//                $response['share_url'] = "http://localhost/wellhello-php-api/getFreelancerProfile" . "?" . $encoded_string;
//            } elseif (strpos($url, 'staging') !== false) {
//                $response['share_url'] = config("general.url.staging_url") . "getFreelancerProfile?" . $encoded_string;
////                $response['share_url'] = config("general.url.staging_url") . "getFreelancerProfile?freelancer_uuid=" . $data['freelancer_uuid'] . '&currency=' . $data['default_currency'];
//            } elseif (strpos($url, 'dev') !== false) {
//                $response['share_url'] = config("general.url.development_url") . "getFreelancerProfile?" . $encoded_string;
//            } elseif (strpos($url, 'production') !== false) {
//                $response['share_url'] = config("general.url.production_url") . "getFreelancerProfile?" . $encoded_string;
//            } elseif (strpos($url, 'dbrefactor') !== false) {
//                $response['share_url'] = 'http://ocirclapi-dbrefactor.7fc6rq3ijt.us-east-2.elasticbeanstalk.com/' . "getFreelancerProfile?" . $encoded_string;
//            }
        }

        return $response;
    }

    public static function checkFreelancerSubscription($data, $inputs) {
        $showSubscription = 0;
        $setting = 0;
        // $getValue = self::getShowSubscriptionValue($inputs);
        $getValue = Freelancer::where('freelancer_uuid',$inputs['freelancer_uuid'])->value('has_subscription_content');
        $subscriptionValue = ($getValue == true) ? 1 : 0;
        if ($subscriptionValue == 1) {
            $subscriptionExtraChecks = self::checkSubscriptionWithFreelancer($inputs, $data['receive_subscription_request']);

            //if freelancer has subscription settings
            if (sizeof($data['subscription_settings']) > 0) {
                $setting = 1;
            }
            // if freelancer has subscription setting but he turn off subscription then o
            if ($setting == 1 && $data['receive_subscription_request'] == 0) {
                $showSubscription = 0;
            }
            //if freelancer has seeting and  turn off/on his subscription but customer purchase his subscription and has some time to complete subscription
            if ($setting == 1  && $subscriptionExtraChecks == 1) {
                $showSubscription = 1;
            }
        }

        // check if  freelancer has video in his all folders
//        if ($showSubscription == 0) {
//        }

        return $showSubscription;
    }

    public static function getShowSubscriptionValue($inputs) {
        $folders = \App\Folder::getFolders('freelancer_id', $inputs['freelancer_id']);
        foreach ($folders as $key => $folder) {
            if (!empty($folder) && !empty($folder['single_post'])) {
                // check if it has a post    
                return true;
            }
        }
        return false;
    }

    public static function checkSubscriptionWithFreelancer($inputs, $receiveSubscription) {
        //if customer has subscription check it

        $subscription = Subscription::checkSubscriptionWithFreelancer($inputs);

        if ($subscription) {
            //if yes then check the subscription time
            $timeCheck = Subscription::checkSubscriptionTime($inputs);
            return ($timeCheck) ? 1 : 0;
        } else {
            //if customer has no subscription but check freelancer setting is on or off
            return ($receiveSubscription == 1) ? 1 : 0;
        }
    }

    public static function prepareShareProfileURL($data) {
        $url = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "production";
        $share_url = "";
        if (!empty($data['freelancer_uuid'])) {
            $data['default_currency'] = !empty($data['default_currency']) ? $data['default_currency'] : "SAR";
            $data_string = "freelancer_uuid=" . $data['freelancer_uuid'] . "&currency=" . $data['default_currency'];
            $encoded_string = base64_encode($data_string);
            $share_url = URL::to('/') . "/getFreelancerProfile?" . $encoded_string;

//            if (strpos($url, 'localhost') !== false) {
//                $share_url = "http://localhost/wellhello-php-api/getFreelancerProfile" . "?" . $encoded_string;
//            } elseif (strpos($url, 'staging') !== false) {
//                $share_url = config("general.url.staging_url") . "getFreelancerProfile?" . $encoded_string;
////                $share_url = config("general.url.staging_url") . "getFreelancerProfile?freelancer_uuid=" . $data['freelancer_uuid'] . '&currency=' . $data['default_currency'];
//            } elseif (strpos($url, 'dev') !== false) {
//                $share_url = config("general.url.development_url") . "getFreelancerProfile?" . $encoded_string;
//            } elseif (strpos($url, 'production') !== false) {
//                $share_url = config("general.url.production_url") . "getFreelancerProfile?" . $encoded_string;
//            } elseif (strpos($url, 'dbrefactor') !== false || strpos($url, 'ocirclapi') !== false) {
//                $share_url = 'http://ocirclapi-dbrefactor.7fc6rq3ijt.us-east-2.elasticbeanstalk.com/' . "getFreelancerProfile?" . $encoded_string;
//            }
        }
        return $share_url;
    }

}

?>
