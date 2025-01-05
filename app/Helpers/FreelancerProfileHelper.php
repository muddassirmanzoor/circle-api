<?php

namespace App\Helpers;

use App\SubscriptionSetting;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Freelancer;
use App\Customer;
use App\Follower;
use App\Review;
use App\Package;
use App\Post;
use App\Classes;
use App\Story;
use App\Favourite;
use App\StoryView;
use App\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

Class FreelancerProfileHelper {

    public static function getAboutme($inputs = []) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerUuidRules()['rules'], FreelancerValidationHelper::freelancerUuidRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
        if (!$freelancer) {
            DB::rollBack();
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['empty_error']);
        }
        DB::commit();
        $response = FreelancerResponseHelper::freelancerAboutme($freelancer);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getSubscriptionSettings($inputs = []) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerUuidRules()['rules'], FreelancerValidationHelper::freelancerUuidRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $subscriptions = SubscriptionSetting::getFreelancerSubscriptions('freelancer_id', $inputs['freelancer_id']);
        if (!$subscriptions) {
            DB::rollBack();
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['empty_error']);
        }
        $response = FreelancerResponseHelper::makeFreelancerSucscriptionArr($subscriptions, $inputs['currency']);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getFreelancerProfile($inputs = []) {

//        if( env('APP_ENVIRONMENT') === 'dev')
//        {
//            if(isset(getallheaders()['Apikey']))
//            {
//                $apikey = getallheaders()['Apikey'];
//            }
//            else
//            {
//                $apikey = null;
//            }
//        }
//        else
//        {
//            if(isset(getallheaders()['Apikey']))
//            {
//                $apikey = getallheaders()['Apikey'];
//            }
//            else
//            {
//                $apikey = null;
//            }
//        }
//
//        if ($apikey == null) {
//            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
//                preg_match("/iPhone|Android|iPad|iPod|webOS|Linux/", $_SERVER['HTTP_USER_AGENT'], $matches);
//                $os = current($matches);
//                switch ($os) {
//                    case 'iPhone':
//                        // return Redirect::route('install-app');
//                    return redirect('https://apps.apple.com/us/app/circl/id1526462647');
//                        break;
//                    case 'Android':
//                        return Redirect::route('install-app');
//    //                return redirect('https://play.google.com/store/apps');
//                        break;
//                    case 'iPad':
//                        return Redirect::route('install-app');
//    //                return redirect('itms-apps://itunes.apple.com/us');
//                        break;
//                    case 'iPod':
//                        return Redirect::route('install-app');
//    //                return redirect('itms-apps://itunes.apple.com/us');
//                        break;
//                    case 'webOS':
//                        return Redirect::route('install-app');
//    //                return redirect('https://apps.apple.com/us');
//                        break;
//                    case 'Linux':
//    //                return Route::view('/welcome', 'welcome');
//                        return Redirect::route('install-app');
//    //                return redirect('https://apps.apple.com/us');
//                        break;
//                    default:
//                        return Redirect::route('install-app');
//                }
//            }
//        }

        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerProfileRules()['rules'], FreelancerValidationHelper::freelancerProfileRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $data = [];
        $freelancer['check_subscription'] = false;
        $freelancer['check_appointment'] = false;
        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);

        $logedinId = CommonHelper::getRecordByUserType($inputs['login_user_type'],$inputs['logged_in_uuid']);

        $freelancerId = $freelancer['id'];
        $inputs['freelancer_id'] = $freelancerId;
        $userId = $freelancer['user_id'];

        $data['followers_count'] = Follower::getFollowersCount('following_id', $freelancerId);

        $data['is_following'] = Follower::checkFollowing($logedinId, $freelancerId);

        $data['is_favourite'] = Favourite::checkFavourite($freelancerId, $logedinId);

        $data['reviews_count'] = Review::getReviewsCount('reviewed_id', $freelancerId);

        $data['reviews_avg'] = (double) Review::getReviewsAvg('reviewed_id', $freelancerId);

        $data['post_count'] = Post::getPostCount('freelance_id', $freelancerId, true);

        $data['reviews'] = Review::getReviews('reviewed_id', $freelancerId);

        $data['story'] = self::checkStory($inputs,$freelancerId);

        if ($inputs['login_user_type'] == "customer") {
            $inputs['customer_uuid'] = $inputs['logged_in_uuid'];
            $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['logged_in_uuid']);

            $chat_data = ClientHelper::getClientChatData($inputs);
            $freelancer['check_subscription'] = $chat_data['has_subscription'];
            $freelancer['check_appointment'] = $chat_data['has_appointment'];
    //            $freelancer['check_subscription'] = Subscription::checkSubscriber($inputs['logged_in_uuid'], $inputs['freelancer_uuid']);
    //            $check_appointment = \App\Appointment::checkHasAppointment($inputs['logged_in_uuid'], $inputs['freelancer_uuid']);
    //            $freelancer['check_appointment'] = !empty($check_appointment) ? true : false;
        }
        date_default_timezone_set($inputs['local_timezone']);
        $current_date_time = date('Y-m-d H:i:s');
        $current_date = strtotime(date('Y-m-d'));
        $current_time = strtotime(date('H:i:s'));

        $session_packages = Package::getPackagesWithType('freelancer_id', $freelancerId, 'session', $current_date_time);
        $class_packages = Package::getPackagesWithType('freelancer_id',$freelancerId, 'class', $current_date_time);
        $has_class = self::classHasSchedules( Classes::checkFreelancerUpcomingClassesCondition('freelancer_id', $freelancerId, $current_date, $current_time));

        if (empty($freelancer)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        $story_uuid_array = StoryView::pluckData('user_id', $logedinId, 'story_id');

        $data['data_to_validate'] = ['story_uuid_array' => $story_uuid_array];

        $response = FreelancerResponseHelper::freelancerProfileCurrencyResponse($freelancer, $data, 'UTC', $inputs['currency'], $inputs['login_user_type'],$inputs);

        $response['has_class'] = $has_class;
//        $active_session_packages = PackageHelper::getfreelancerActivePackages($session_packages);
        $response['session_packages'] = PackageResponseHelper::ProfilePackagesResponse($session_packages);
//        $active_class_packages = PackageHelper::getfreelancerActivePackages($class_packages);
        $response['class_packages'] = PackageResponseHelper::ProfilePackagesResponse($class_packages);

        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function classHasSchedules($classes){
        $check = false;
       if(!empty($classes)){
           foreach ($classes as $class){
               if(!empty($class['schedule'])){
                   $check = true;
               }
           }
       }

       return $check;

    }

    public static function checkStory($inputs,$freelanceId=null) {

        if($freelanceId !=null){
            $story = Story::getActiveStories('freelancer_id', $freelanceId);
        }else{
            $story = Story::getActiveStories('freelancer_id', $inputs['freelancer_id']);
        }
        return $story;
    }

    public static function deleteMedia($inputs = []) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::deleteMediaRules()['rules'], FreelancerValidationHelper::deleteMediaRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $data = [];
        $data[$inputs['type']] = null;
        if (strtolower($inputs['type']) == 'cover_video') {
            $data['cover_video_thumb'] = null;
        }
        if (strtolower($inputs['login_user_type']) == 'freelancer') {
            $update = Freelancer::updateFreelancer('freelancer_uuid', $inputs['logged_in_uuid'], $data);
        }
        if (strtolower($inputs['login_user_type']) == 'customer') {
            $update = Customer::updateCustomer('customer_uuid', $inputs['logged_in_uuid'], $data);
        }
        if (!$update) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

}

?>
