<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Helpers\FavouriteValidationHelper;
use App\Helpers\FavouriteMessageHelper;
use App\Helpers\FreelancerResponseHelper;
use App\BookMark;
use App\Freelancer;
use App\Customer;
use App\Favourite;
use App\Subscription;
use DB;

Class FavouriteHelper {

    public static function processFavourite($inputs) {
        $validation = Validator::make($inputs, FavouriteValidationHelper::profileFavouriteRules()['rules'], FavouriteValidationHelper::profileFavouriteRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getRecordByUuid('freelancers','freelancer_uuid',$inputs['freelancer_uuid']);
        $inputs['customer_id'] = CommonHelper::getRecordByUuid('customers','customer_uuid', $inputs['customer_uuid']);

        $result = false;
        if ($inputs['process_type'] == 'favourite') {
            $favourite_inputs = ['customer_id' => $inputs['customer_id'], 'freelancer_id' => $inputs['freelancer_id']];
            $check = Favourite::checkFavourite($inputs['freelancer_id'], $inputs['customer_id']);
            if ($check) {
                return CommonHelper::jsonErrorResponse(FavouriteMessageHelper::getMessageData('error', $inputs['lang'])['already_exists']);
            }
            $result = Favourite::addFavourite($inputs);
            DB::commit();
//            ProcessNotificationHelper::sendAddToFavouriteNotification($favourite_inputs, $result);
            return CommonHelper::jsonSuccessResponse(FavouriteMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
        }
        if ($inputs['process_type'] == 'unfavourite') {
            $result = Favourite::checkAndRemove($inputs['freelancer_id'], $inputs['customer_id']);
            DB::commit();
            return CommonHelper::jsonSuccessResponse(FavouriteMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
        }
        DB::rollBack();
        return CommonHelper::jsonErrorResponse(FavouriteMessageHelper::getMessageData('error', $inputs['lang'])['save_error']);
    }

    public static function getFavouriteScreenData($inputs) {
        $validation = Validator::make($inputs, FavouriteValidationHelper::favouriteBookmarkRules()['rules'], FavouriteValidationHelper::favouriteBookmarkRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $book_mark_post_ids = BookMark::getBookMarkedPostIds('customer_uuid', $inputs['profile_uuid'], 'profile_uuid');
        return self::getProfileSubscriber($inputs, $book_mark_post_ids);
    }

    public static function getProfileSubscriber($inputs, $book_mark_post_ids) {
        $response = [];
        $offset = ($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = ($inputs['limit']) ? $inputs['limit'] : 20;
        if ($inputs['login_user_type'] == 'freelancer') {
            $profiles = Freelancer::getFreelancerDetailByIds('freelancer_uuid', $book_mark_post_ids, $inputs['type'], $offset, $limit);
            $response = self::makrFreelancerApiResponse($inputs, $profiles);
        } else {
            $profiles = Customer::getCustomerDetailByIds('customer_uuid', $book_mark_post_ids, $inputs['type'], $offset, $limit);
            $response = self::makrCustomerApiResponse($inputs, $profiles);
        }
        return $response;
    }

    public static function makrFreelancerApiResponse($inputs, $profiles) {
        $response = [];
        if (!empty($profiles)) {
            foreach ($profiles as $key => $profile) {
                $response[$key] = FreelancerResponseHelper::freelancerProfileResponseWithPost($profile);
            }
            return CommonHelper::jsonSuccessResponse(FavouriteMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        }
        return CommonHelper::jsonErrorResponse(FavouriteMessageHelper::getMessageData('error', $inputs['lang'])['record_not_found']);
    }

    public static function makrCustomerApiResponse($inputs, $profiles) {
        $response = [];
        if (!empty($profiles)) {
            $response = CustomerResponseHelper::customerListResponse($profiles);
            return CommonHelper::jsonSuccessResponse(FavouriteMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        }
        return CommonHelper::jsonErrorResponse(FavouriteMessageHelper::getMessageData('error', $inputs['lang'])['record_not_found']);
    }

    public static function getFavouriteProfilesData($inputs) {
        $validation = Validator::make($inputs, FavouriteValidationHelper::favouriteBookmarkRules()['rules'], FavouriteValidationHelper::favouriteBookmarkRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['profile_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'],$inputs['profile_uuid']);
        if (strtolower($inputs['type']) == 'public') {
            $favourite_ids = Favourite::getFavouriteProfileIds('customer_id', $inputs['profile_id'], 'freelancer_id');
        }

        elseif (strtolower($inputs['type']) == 'subscriber') {

            $favourite_ids = Subscription::getFavouriteProfileIds('subscriber_id', $inputs['profile_id'], 'subscribed_id');
        }

       return self::getFavouriteProfilesDataProcess($inputs, $favourite_ids,$inputs['local_timezone']);
    }

    public static function getFavouriteProfilesDataProcess($inputs, $favourite_ids, $local_timezone = 'UTC') {
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : null;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : null;
        $profiles = Freelancer::getMultipleProfiles('id', $favourite_ids,$inputs, $offset, $limit);

        return self::makeFreelancerApiResponse($inputs, $profiles, $local_timezone);
    }

    public static function getProfileSubscriberData($inputs, $favourites) {
        $response = [];
        $offset = ($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = ($inputs['limit']) ? $inputs['limit'] : 20;
        if (strtolower($inputs['login_user_type']) == 'customer') {
            $profiles = Freelancer::getFreelancerProfile('freelancer_uuid', $favourites, $offset, $limit);
            $response = self::makeFreelancerApiResponse($inputs, $profiles, $local_timezone);
        }
        return $response;
    }

    public static function makeFreelancerApiResponse($inputs, $profiles, $local_timezone) {

        $response = [];
        $data =[];
        if (!empty($profiles)) {

            foreach ($profiles as $key => $profile) {
                $data['is_favourite'] = Favourite::checkFavourite($profile['id'], $inputs['profile_id']);

                $response[$key] = FreelancerResponseHelper::freelancerProfileResponse($profile, $data, $local_timezone);
            }
            return CommonHelper::jsonSuccessResponse(FavouriteMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        }
        return CommonHelper::jsonErrorResponse(FavouriteMessageHelper::getMessageData('error', $inputs['lang'])['record_not_found']);
    }

}
