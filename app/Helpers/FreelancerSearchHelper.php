<?php

namespace App\Helpers;

use App\Freelancer;
use App\Subscription;
use App\Profession;
use App\Appointment;
use App\Message;
use App\Classes;
use App\ClassBooking;
use App\Customer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;

Class FreelancerSearchHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerSearchHelper that contains all the freelancer related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use freelancer processes
      |
     */

    /**
     * Description of FreelancerSearchHelper
     *
     * @author ILSA Interactive
     */
    public static function searchFreelancers($inputs) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::searchFreelancerRules()['rules'], FreelancerValidationHelper::searchFreelancerRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['subscription_ids'] = [];
        $inputs['logged_in_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid']);
        if (!empty($inputs['is_subscribed']) && $inputs['is_subscribed'] == true) {
            $subscription_ids = Subscription::getSubscribedIds('subscriber_uuid', $inputs['logged_in_uuid']);
            $inputs['subscription_ids'] = $subscription_ids;
        }
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : null;
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : null;
        $result = Freelancer::searchFreelancers($inputs, $limit, $offset);
        $response = self::makeFreelancersListResponse($result);
        return CommonHelper::jsonSuccessResponse(FollowerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function makeFreelancersListResponse($data_array = []) {
        $response = [];
        $url = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'production';
        if (!empty($data_array)) {
            foreach ($data_array as $key => $data) {

                $freelancer_categories = CategoryHelper::prepareSearchFreelancerCategoryResponse($data['freelancer_categories']);
                $flag_online = self::checkOnlineFaceToFaceFlag($freelancer_categories);
                $response[$key]['freelancer_uuid'] = $data['freelancer_uuid'];
                $response[$key]['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
                $response[$key]['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
                $response[$key]['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['profession']) ? $data['profession'] : []);
//                $response[$key]['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
                $response[$key]['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['profile_image']);
                $response[$key]['profile_card_image'] = !empty($data['profile_card_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_card_image'] : null;
                $response[$key]['lat'] = !empty($data['primary_location']['location']['lat']) ? $data['primary_location']['location']['lat'] : null;
                $response[$key]['lng'] = !empty($data['primary_location']['location']['lng']) ? $data['primary_location']['location']['lng'] : null;
                $response[$key]['distance'] = !empty($data['distance']) ? $data['distance'] : null;
                $response[$key]['provides_online_services'] = $flag_online['is_online'];
                $response[$key]['provides_face_to_face'] = $flag_online['is_face_to_face'];
                $response[$key]['is_liked'] = (!empty($data['likes_count']) && $data['likes_count'] > 0) ? true : false;
                $response[$key]['is_following'] = (!empty($data['following']) && $data['following'] > 0) ? true : false;
                $response[$key]['is_favourite'] = (!empty($data['favourites']) && $data['favourites'] > 0) ? true : false;
                $response[$key]['is_subscription_profiles'] = (!empty($data['subscriptions']) && $data['subscriptions'] > 0) ? true : false;
                // $response[$key]['country'] = self::extractCountryCityName($data, 'country');
                $response[$key]['country']  = (!empty($data['country']) && $data['country'] > 0) ? true : false;
                // $response[$key]['city'] = self::extractCountryCityName($data, 'city');
                $response[$key]['city'] = (!empty($data['city']) && $data['city'] > 0) ? true : false;
                $response[$key]['share_url'] = null;
                $response[$key]['reviews_count'] = $data['reviews_count'];
                $response[$key]['average_rating'] = (isset($data['reviews']) && !empty($data['reviews'][0])) ? (float) $data['reviews'][0]['average_rating'] : 0;
                $response[$key]['freelancer_categories'] = $freelancer_categories;
                $data_string = "freelancer_uuid=" . $data['freelancer_uuid'] . "&currency=" . $data['default_currency'];
                $encoded_string = base64_encode($data_string);
                $response[$key]['share_url'] = URL::to('/') . "/getFreelancerProfile?" . $encoded_string;
//                if (strpos($url, 'localhost') !== false) {
//                    $response[$key]['share_url'] = "http://localhost/wellhello-php-api/getFreelancerProfile" . "?" . $encoded_string;
//                } elseif (strpos($url, 'staging') !== false) {
//                    $response[$key]['share_url'] = config("general.url.staging_url") . "getFreelancerProfile?" . $encoded_string;
////                $response['share_url'] = config("general.url.staging_url") . "getFreelancerProfile?freelancer_uuid=" . $data['freelancer_uuid'] . '&currency=' . $data['default_currency'];
//                } elseif (strpos($url, 'dev') !== false) {
//                    $response[$key]['share_url'] = config("general.url.development_url") . "getFreelancerProfile?" . $encoded_string;
//                } elseif (strpos($url, 'production') !== false) {
//                    $response[$key]['share_url'] = config("general.url.production_url") . "getFreelancerProfile?" . $encoded_string;
//                } elseif ((strpos($url, 'ocirclapi') !== false) || (strpos($url, 'dbrefactor') !== false)) {
//                    $response[$key]['share_url'] = 'http://ocirclapi-dbrefactor.7fc6rq3ijt.us-east-2.elasticbeanstalk.com/' . "getFreelancerProfile?" . $encoded_string;
//                }
            }
        }
        // $unique = self::uniquAsoc($response,'freelancer_uuid');
        return $response;
    }
    public static function uniquAsoc($array,$key){
        $resArray=[];
        foreach($array as $val){
          if(empty($resArray)){
            array_push($resArray,$val);
          }else{
            $value=array_column($resArray,$key);
            if(!in_array($val[$key],$value)){
                array_push($resArray,$val);
              }
          }
        }

        return $resArray;
    }
    public static function extractCountryCityName($data, $column) {
        $value = '';
        if (isset($data['locations']) && !empty($data['locations'])) {
            foreach ($data['locations'] as $location) {
                if (!empty($location['location'])) {
                    $value = $location['location'][$column];
                }
            }
        }
        return $value;
    }

    public static function checkOnlineFaceToFaceFlag($freelancer_categories) {
        $response = [];
        $is_online = false;
        $is_face_to_face = false;
        if (!empty($freelancer_categories)) {
            foreach ($freelancer_categories as $cateory) {
                if ($cateory['is_online'] == 1) {
                    $is_online = true;
                    break;
                }
            }
            foreach ($freelancer_categories as $cateory) {
                if ($cateory['is_online'] == 0) {
                    $is_face_to_face = true;
                    break;
                }
            }
        }
        $response['is_online'] = $is_online;
        $response['is_face_to_face'] = $is_face_to_face;
        return $response;
    }

    public static function searchChatUsers($inputs) {
        $validation = Validator::make($inputs, ChatValidationHelper::searchChatUsersRules()['rules'], ChatValidationHelper::searchChatUsersRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $response = [];
        $search_key = !empty($inputs['search_key']) ? $inputs['search_key'] : null;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : null;
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : null;
        $get_subscription_ids = [];
        $get_appointment_ids = [];
        $prepare_inbox_ids = [];
        $get_booking_ids = [];
        if ($inputs['login_user_type'] == "freelancer") {
            $get_subscription_ids = Subscription::getFavouriteProfileIds('subscribed_id', $inputs['login_user_id'], 'subscriber_id');
            $get_appointment_ids = Appointment::getFavIdsOfFutureAppointments('freelancer_id', $inputs['login_user_id'], 'customer_id');
            $get_class_ids = Classes::getClassIds('freelancer_id', $inputs['login_user_id']);
            $get_booking_ids = ClassBooking::getFavIds('class_id', $get_class_ids, 'customer_id');
            $get_inbox_ids = Message::pluckFavIds($inputs['logged_in_uuid']);
            $prepare_inbox_ids = !empty($get_inbox_ids) ? self::prepareCustomerInboxIds($get_inbox_ids) : [];
        } elseif ($inputs['login_user_type'] == "customer") {
            $get_subscription_ids = Subscription::getFavouriteProfileIds('subscriber_id', $inputs['login_user_id'], 'subscribed_id');
            $get_appointment_ids = Appointment::getFavIdsOfFutureAppointments('customer_id', $inputs['login_user_id'], 'freelancer_id');
            $get_class_ids = ClassBooking::pluckClassBookingIds('customer_id', $inputs['login_user_id'], 'class_id');
            $get_booking_ids = Classes::pluckFavoriteIds('id', $get_class_ids, 'freelancer_id');
            $get_inbox_ids = Message::pluckFavIds($inputs['logged_in_uuid']);
            $prepare_inbox_ids = !empty($get_inbox_ids) ? self::prepareFreelancerInboxIds($get_inbox_ids) : [];
        } else {
            return CommonHelper::jsonErrorResponse("Invalid type provided");
        }
        $merge_array = array_values(array_unique(array_merge($get_subscription_ids, $get_appointment_ids, $get_booking_ids, $prepare_inbox_ids), SORT_REGULAR));
//        $results = Customer::searchCustomersForChat($search_key, $get_subscriber_ids, $limit, $offset);

        $results = ($inputs['login_user_type'] == "customer") ? Freelancer::searchFreelancersForChat($search_key, $merge_array, $limit, $offset) : Customer::searchCustomersForChat($search_key, $merge_array, $limit, $offset);
        $response = self::redirectResponseCall($inputs, ($results) ? $results : []);
        return CommonHelper::jsonSuccessResponse(FollowerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function prepareCustomerInboxIds($data = []) {
        $ids = [];
        foreach ($data as $key => $record) {
            if ($record['receiver_type'] == "customer" || $record['sender_type'] == "customer") {
                if (($record['receiver_type'] == "customer") && !empty($record['receiver_uuid']) && !in_array($record['receiver_uuid'], $ids)) {
                    $id = Customer::where('customer_uuid', $record['receiver_uuid'])->first()->id;
//                    array_push($ids, $record['receiver_uuid']);
                    array_push($ids, $id);
                } elseif (($record['sender_type'] == "customer") && !empty($record['sender_uuid']) && !in_array($record['sender_uuid'], $ids)) {
                    $id = Customer::where('customer_uuid', $record['sender_uuid'])->first()->id;
                    array_push($ids, $id);
//                    array_push($ids, $record['sender_uuid']);
                }
            }
        }
        return ($ids) ? $ids : [];
    }

    public static function prepareFreelancerInboxIds($data = []) {
        $ids = [];
        foreach ($data as $key => $record) {
            if (($record['chat_with'] == 'user')) {
                if ($record['receiver_type'] == "freelancer" || $record['sender_type'] == "freelancer") {
                    if (($record['receiver_type'] == "freelancer") && !empty($record['receiver_uuid']) && (!in_array($record['receiver_uuid'], $ids))) {
                        $id = Freelancer::where('freelancer_uuid', $record['receiver_uuid'])->first()->id;
                        array_push($ids, $id);
//                    array_push($ids, $record['receiver_id']);
                    } if (($record['sender_type'] == "freelancer") && !empty($record['sender_uuid']) && (!in_array($record['sender_uuid'], $ids))) {
                        $id = Freelancer::where('freelancer_uuid', $record['sender_uuid'])->first()->id;
                        array_push($ids, $id);
//                    array_push($ids, $record['sender_id']);
                    }
                }
            }
//            elseif (($record['chat_with'] == 'admin')) {
//                if (($record['sender_type'] == "customer") && !empty($record['sender_uuid']) && (!in_array($record['sender_uuid'], $ids))) {
//                    $id = Customer::where('customer_uuid', $record['sender_uuid'])->first()->id;
//                    array_push($ids, $id);
//                }
//                if (($record['receiver_type'] == "customer") && !empty($record['receiver_uuid']) && (!in_array($record['receiver_uuid'], $ids))) {
//                    $id = Customer::where('customer_uuid', $record['receiver_uuid'])->first()->id;
//                    array_push($ids, $id);
//                }
////                    array_push($ids, $record['sender_id']);
//            }
        }
        return ($ids) ? $ids : [];
    }

    public static function redirectResponseCall($inputs = [], $results = []) {
        $response = [];
        $data = [];
        if ($inputs['login_user_type'] == "freelancer") {
            $data = InboxHelper::getChatRelatedFreelancerData($inputs);
            $response = SearchResponseHelper::searchedCustomersResponse($results, $data);
        }
        if ($inputs['login_user_type'] == "customer") {
            $data = InboxHelper::getChatRelatedCustomerData($inputs);
            $response = SearchResponseHelper::searchedFreelancersResponse($results, $data);
        }
        return !empty($response) ? $response : [];
    }

}

?>
