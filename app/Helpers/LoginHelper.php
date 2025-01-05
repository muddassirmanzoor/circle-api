<?php

namespace App\Helpers;

use App\Freelancer;
use App\Customer;
use App\Notification;
use App\UserDevice;
use Auth;
use Illuminate\Support\Facades\Validator;
use DB;
use App\User;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Request;

Class LoginHelper {
    /*
      |--------------------------------------------------------------------------
      | LoginHelper that contains all the login related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use login processes
      |
     */

    /**
     * Description of LoginHelper
     *
     * @author ILSA Interactive
     */

    /**
     * login method
     * @param type $inputs
     * @return type
     */
    public static function guestLogin($inputs = []) {
        $customer = Customer::getSingleCustomer('type', 'guest');
        if (empty($customer)) {
            return CommonHelper::jsonErrorResponse("Sorry! Please try again later.");
        }
        $response = CustomerResponseHelper::prepareGuestLoginResponse($customer);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function login($inputs = []) {
        $validation = Validator::make($inputs, LoginValidationHelper::loginRules()['rules'], LoginValidationHelper::loginRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['login_user_type'] == 'freelancer') {
            return self::freelancerLogin($inputs);
        } elseif ($inputs['login_user_type'] == 'customer') {
            return CustomerLoginHelper::customerLogin($inputs);
        }
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['general_error']);
    }

    public static function autoLogin($inputs = []) {
        $validation = Validator::make($inputs, LoginValidationHelper::autoLoginRules()['rules'], LoginValidationHelper::autoLoginRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        if ($inputs['login_type'] == 'normal') {
            return self::processNormalAutoLogin($inputs);
        }
        if ($inputs['login_type'] == 'social') {
            return self::processSocialAutoLogin($inputs);
        }
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['general_error']);
    }

    public static function processNormalAutoLogin($inputs) {
        if ($inputs['login_user_type'] == 'freelancer') {
            return self::freelancerLogin($inputs);
        } elseif ($inputs['login_user_type'] == 'customer') {
            return CustomerLoginHelper::customerLogin($inputs);
        }
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['general_error']);
    }

    public static function processSocialAutoLogin($inputs) {
        if ($inputs['login_user_type'] == 'freelancer') {
            return self::freelancerSocialLogin($inputs);
        } elseif ($inputs['login_user_type'] == 'customer') {
            return self::customerSocialLogin($inputs);
        }
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['general_error']);
    }

    public static function logout($inputs) {
        if (empty($inputs['freelancer_uuid']) && empty($inputs['customer_uuid'])) {
            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['logout_validation']);
        }
        $user_info = ['is_login' => 0];
        if (!empty($inputs['freelancer_uuid'])) {
            $update_login_status = Freelancer::updateFreelancer('freelancer_uuid', $inputs['freelancer_uuid'], $user_info);

            $userId = CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['freelancer_uuid'], 'user_id');
            $update_device = self::removeDeviceTokens($userId);
        }
        if (!empty($inputs['customer_uuid'])) {
            $update_login_status = Customer::updateCustomer('customer_uuid', $inputs['customer_uuid'], $user_info);
            $userId = CommonHelper::getRecordByUuid('customers', 'customer_uuid', $inputs['customer_uuid'], 'user_id');

            $update_device = self::removeDeviceTokens($userId);
        }

        if (!$update_login_status /* || !$update_device*/) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['logout_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['logout_success']);
    }

    public static function socialLogin($inputs = []) {
        $validation = Validator::make($inputs, LoginValidationHelper::socialLoginRules()['rules'], LoginValidationHelper::socialLoginRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['login_user_type'] == 'freelancer') {
            return self::freelancerSocialLogin($inputs);
        } elseif ($inputs['login_user_type'] == 'customer') {
            return self::customerSocialLogin($inputs);
        }
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['general_error']);
    }

    public static function freelancerSocialLogin($inputs = []) {
        $freelancer = [];
        if (!empty($inputs['facebook_id'])) {
            $freelancer = Freelancer::getFreelancerDetail('facebook_id', $inputs['facebook_id']);
        } elseif (!empty($inputs['google_id'])) {
            $freelancer = Freelancer::getFreelancerDetail('google_id', $inputs['google_id']);
        } elseif (!empty($inputs['apple_id'])) {
            $freelancer = Freelancer::getFreelancerDetail('apple_id', $inputs['apple_id']);
        }

        if(empty($freelancer) && !$inputs['is_signup_request']) {
            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_error']);  
        }

        if (empty($freelancer)) {
            // if (!empty($inputs['email'])) {
            //     $freelancer = Freelancer::getFreelancerDetail('email', $inputs['email']);
            //     if (!empty($freelancer)) {
            //         return self::updateFreelancerWhileLogin($inputs, $freelancer);
            //     }
            //     DB::rollBack();
            //     return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['invalid_login']);
            // } else {
            //     DB::rollBack();
            //     return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['invalid_login']);
            // }
            if (!empty($inputs['apple_id'])) {
                $customer = Customer::getSingleCustomer('apple_id', $inputs['apple_id']);
                if (!empty($customer)) {
                    $inputs['email'] = $customer['email'] ?? '';
                    $inputs['first_name'] = $customer['first_name'] ?? '';
                    $inputs['last_name'] = $customer['last_name'] ?? '';
                }
            }

            if (!empty($inputs['email'])) {
                $freelancer = Freelancer::getFreelancerDetail('email', $inputs['email']);
                if (!empty($freelancer)) {
                    return self::updateFreelancerWhileLogin($inputs, $freelancer);
                } else {

                    $save_user = User::saveUser();
                    $inputs['user_id'] = $save_user['id'];
                    return FreelancerHelper::freelancerSocialSignp($inputs);
                }
            } else {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['invalid_login']);
            }
        }
       
        $inputs['device'] = ['device_type' => (!empty($inputs['device_type'])) ? $inputs['device_type'] : '', 'device_token' => (!empty($inputs['device_token'])) ? $inputs['device_token'] : ''];
        $update_device = self::updateDeviceData($inputs['device'], $freelancer);
        $update_profile = self::updateProfile($inputs, $freelancer);
        $freelancer['type'] = "freelancer";
//        $create_chat = ChatController::createAdminChat($freelancer);
        if (!$update_device || !$update_profile) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_error']);
        }
        DB::commit();
        $updated_freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $freelancer['freelancer_uuid']);
        $response = self::processFreelancerLoginResponse($updated_freelancer);
        return CommonHelper::jsonSuccessResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_success'], $response);
    }

    public static function updateFreelancerWhileLogin($inputs = [], $freelancer = []) {
        $inputs['freelancer_uuid'] = $freelancer['freelancer_uuid'];
        if (!empty($inputs['profession']) && empty($inputs['profession_uuid'])) {
            return CommonHelper::jsonErrorResponse(FreelancerValidationHelper::updateProfileRules()['message_' . strtolower($inputs['lang'])]['missing_profession_uuid']);
        }
//        $update_inputs = FreelancerValidationHelper::processFreelancerInputs($inputs);
        $freelancer_inputs_data = FreelancerValidationHelper::processFreelancerInputs($inputs);
        if (!$freelancer_inputs_data['success']) {
            return CommonHelper::jsonErrorResponse($freelancer_inputs_data['message']);
        }
        $update_inputs = $freelancer_inputs_data['data'];
//        $update_inputs['freelancer_uuid'] = $freelancer['freelancer_uuid'];
        $update = Freelancer::updateFreelancer('freelancer_uuid', $freelancer['freelancer_uuid'], $update_inputs);
        if (!$update) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['invalid_login']);
        }
        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $freelancer['freelancer_uuid']);
        $response = self::processFreelancerLoginResponse($freelancer);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_success'], $response);
    }

    public static function freelancerLogin($inputs = []) {
        $validation = Validator::make($inputs, LoginValidationHelper::loginRules()['rules'], LoginValidationHelper::loginRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if (Auth::guard('freelancer')->attempt(['email' => $inputs['email'], 'password' => $inputs['password']])) {

            if (Auth::guard('freelancer')->attempt(['email' => $inputs['email'], 'password' => $inputs['password'], 'is_archive' => 1])) {
                return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['deleted_account']);
            } elseif (Auth::guard('freelancer')->attempt(['email' => $inputs['email'], 'password' => $inputs['password'], 'is_active' => 0])) {
                return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['blocked_account']);
            }
            $freelancer = Freelancer::getFreelancerDetail('email', $inputs['email']);
            $data = ['is_login' => 1];
            $inputs['device'] = ['device_type' => (!empty($inputs['device_type'])) ? $inputs['device_type'] : '', 'device_token' => (!empty($inputs['device_token'])) ? $inputs['device_token'] : ''];
            $update_freelancer = Freelancer::updateFreelancer('freelancer_uuid', $freelancer['freelancer_uuid'], $data);

            $update_device = self::updateDeviceData($inputs['device'], $freelancer);

            if (!$update_device || !$update_freelancer) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_error']);
            }

            $response = self::processFreelancerLoginResponse($freelancer);
            //HyperpayHelper::getCurrencyRate();
            $freelancer['type'] = "freelancer";
//            $create_chat = ChatController::createAdminChat($freelancer);
            DB::commit();
            return CommonHelper::jsonSuccessResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_success'], $response);
        }
        return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['invalid_login']);
    }

    public static function processFreelancerLoginResponse($data = []) {

        $response = [];
        $response['freelancer_uuid'] = $data['freelancer_uuid'];
        $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
        $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
        $response['company'] = !empty($data['company']) ? $data['company'] : null;
        $response['email'] = !empty($data['email']) ? $data['email'] : null;
        $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
        $response['country_code'] = !empty($data['country_code']) ? $data['country_code'] : null;
        $response['country_name'] = !empty($data['country_name']) ? $data['country_name'] : null;
        $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['profile_image']);
        $response['profile_card_image'] = !empty($data['profile_card_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_card_image'] : null;
        $response['cover_image'] = !empty($data['cover_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_cover_image'] . $data['cover_image'] : null;
        $response['cover_images'] = FreelancerResponseHelper::freelancerCoverImagesResponse($data['cover_image']);
        $response['cover_video'] = !empty($data['cover_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['cover_video'] . $data['cover_video'] : null;
        $response['cover_video_thumb'] = null;
        $response['keys'] = CommonHelper::configKeys();
        if (!empty($data['cover_video'])) {
            $response['cover_video_thumb'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";
//            $response['cover_video_thumb'] = !empty($data['cover_video_thumb']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['cover_video'] . $data['cover_video_thumb'] : null;
        }

        $response['freelancer_industry'] = null;
        if (!empty($data['saved_category'])) {
            $response['freelancer_industry'] = self::prepareIndustryResponse($data);
//            $response['freelancer_industry']['category_uuid'] = $data['saved_category']['category']['category_uuid'];
//            $response['freelancer_industry']['freelancer_uuid'] = $data['freelancer_uuid'];
//            $response['freelancer_industry']['name'] = $data['saved_category']['category']['name'];
//            $response['freelancer_industry']['detail'] = !empty($data['saved_category']['category']['description']) ? $data['saved_category']['category']['description'] : "";
//            $response['freelancer_industry']['image'] = !empty($data['saved_category']['category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $data['saved_category']['category']['image'] : null;
        }

        $response['freelancer_categories'] = FreelancerResponseHelper::freelancerCategoriesResponse(!empty($data['freelancer_categories']) ? $data['freelancer_categories'] : null);
        $response['business_cover_image'] = (!empty($data['freelancer_categories']) && !empty($data['freelancer_categories'][0]['category']) && !empty($data['freelancer_categories'][0]['category']['image'])) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $data['freelancer_categories'][0]['category']['image'] : null;
        $response['gender'] = !empty($data['gender']) ? $data['gender'] : null;
        $response['bio'] = !empty($data['bio']) ? $data['bio'] : null;
        $response['profile_type'] = !empty($data['profile_type']) ? $data['profile_type'] : 0;
        $response['onboard_count'] = $data['onboard_count'];
        $response['locations'] = self::processFreelancerLocationsResponse((!empty($data['locations']) ? $data['locations'] : []));
        $response['can_travel'] = !empty($data['can_travel']) ? $data['can_travel'] : 0;
        $response['travelling_distance'] = !empty($data['travelling_distance']) ? $data['travelling_distance'] : null;
        $response['travelling_cost_per_km'] = !empty($data['travelling_cost_per_km']) ? $data['travelling_cost_per_km'] : 0;

        $response['notification_badge_count'] = Notification::getNotificationBadgeCount('receiver_id', $data['user_id'], 'all');

        $response['is_business'] = !empty($data['is_business']) ? $data['is_business'] : null;
        $response['business_name'] = !empty($data['business_name']) ? $data['business_name'] : null;
        $response['business_logo'] = !empty($data['business_logo']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['mobile_uploads'] . $data['business_logo'] : null;

        $response['qualifications'] = FreelancerResponseHelper::freelancerQualificationResponse(!empty($data['qualifications']) ? $data['qualifications'] : []);
        $response['feedback'] = !empty($data['reviews']) ? FreelancerResponseHelper::appointmentReviewCustomerResponse($data['reviews']) : null;
        $response['currency'] = !empty($data['default_currency']) ? $data['default_currency'] : null;
        $response['age'] = !empty($data['age']) ? $data['age'] : null;
        $response['receive_subscription_request'] = $data['receive_subscription_request'];
        $response['has_subscription_content'] = $data['has_subscription_content'];

        $response['booking_preferences'] = !empty($data['booking_preferences']) ? $data['booking_preferences'] : null;
        $response['profession_details'] = self::processFreelancerProfessionResponse(!empty($data['profession']) ? $data['profession'] : []);

        $response['has_bank_detail'] = ($data['has_bank_detail'] == 1) ? true : false;
        $response['chat_unread_count'] = InboxHelper::getUnreadChatCount($data['freelancer_uuid']);

        $response['added_subscription_once'] = SubscriptionHelper::checkFreelancerSubscriptionSettingExists($data);

        return $response;
    }

    public static function prepareIndustryResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['category_uuid'] = $data['saved_category']['category']['category_uuid'];
            $response['freelancer_uuid'] = $data['freelancer_uuid'];
            $response['name'] = $data['saved_category']['category']['name'];
            $response['detail'] = !empty($data['saved_category']['category']['description']) ? $data['saved_category']['category']['description'] : "";
            $response['image'] = !empty($data['saved_category']['category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $data['saved_category']['category']['image'] : null;
        }
        return $response;
    }

    public static function processFreelancerProfessionResponse($data = []) {
        $response = null;
        if (!empty($data)) {
            $response['profession_uuid'] = !empty($data['profession_uuid']) ? $data['profession_uuid'] : null;
            $response['name'] = !empty($data['name']) ? $data['name'] : null;
        }
        return $response;
    }

    public static function processFreelancerLocationsResponse($data = []) {
        $response = [];
        if (!empty($data)) {

            foreach ($data as $key => $value) {
                $response[$key]['gym_name'] = !empty($value['gym_name']) ? $value['gym_name'] : null;
                $response[$key]['gym_logo'] = !empty($value['gym_logo']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['gym_logo'] . $value['gym_logo'] : null;
                $response[$key]['is_gym'] = $value['is_gym'];
                $response[$key]['address'] = $value['location']['address'];
                $response[$key]['city'] = $value['location']['city'];
                $response[$key]['state'] = $value['location']['state'];
                $response[$key]['country'] = $value['location']['country'];
                $response[$key]['lat'] = $value['location']['lat'];
                $response[$key]['lng'] = $value['location']['lng'];
                $response[$key]['type'] = $value['type'];
                $response[$key]['freelancer_uuid'] = CommonHelper::getRecordByUuid('freelancers', 'id', $value['freelancer_id'], 'freelancer_uuid');
                $response[$key]['freelancer_location_uuid'] = $value['freelancer_location_uuid'];
                $response[$key]['comments'] = $value['comments'];
            }
        }

        return $response;
    }

    public static function customerSocialLogin($inputs = []) {
        $customer = [];
        if (!empty($inputs['facebook_id'])) {
            $customer = Customer::getSingleCustomer('facebook_id', $inputs['facebook_id']);
        } elseif (!empty($inputs['google_id'])) {
            $customer = Customer::getSingleCustomer('google_id', $inputs['google_id']);
        } elseif (!empty($inputs['apple_id'])) {
            $customer = Customer::getSingleCustomer('apple_id', $inputs['apple_id']);
        }

        if(empty($customer) && !$inputs['is_signup_request']) {
            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_error']);  
        }
//        if (empty($customer)) {
//            if (!empty($inputs['email'])) {
//                $customer = Customer::getSingleCustomer('email', $inputs['email']);
//                if (!empty($customer)) {
//                    return self::updateCustomerWhileLogin($inputs, $customer);
//                }
//            }
//            DB::rollBack();
//            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['invalid_login']);
//        }
        if (empty($customer)) {

            if (!empty($inputs['apple_id'])) {
                $freelancer = Freelancer::getFreelancerDetail('apple_id', $inputs['apple_id']);
                if (!empty($freelancer)) {
                    $inputs['email'] = $freelancer['email'] ?? '';
                    $inputs['first_name'] = $freelancer['first_name'] ?? '';
                    $inputs['last_name'] = $freelancer['last_name'] ?? '';
                }
            }

            if (!empty($inputs['email'])) {
                $customer = Customer::getSingleCustomer('email', $inputs['email']);
                if (!empty($customer)) {
                    return self::updateCustomerWhileLogin($inputs, $customer);
                } else {
                    $save_user = User::saveUser();
                    $inputs['user_id'] = $save_user['id'];
                    return CustomerHelper::customerSocialSignp($inputs);
                }
            } else {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['invalid_login']);
            }
        }

        $inputs['device'] = ['device_type' => (!empty($inputs['device_type'])) ? $inputs['device_type'] : '', 'device_token' => (!empty($inputs['device_token'])) ? $inputs['device_token'] : ''];
        $update_device = self::updateDeviceData($inputs['device'], $customer);
        $update_profile = self::updateProfile($inputs, $customer);
        $customer['type'] = "customer";

//        $create_chat = ChatController::createAdminChat($customer);
        if (!$update_device || !$update_profile) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_error']);
        }
        DB::commit();
        $updated_customer = Customer::getSingleCustomer('customer_uuid', $customer['customer_uuid']);
        $response = CustomerResponseHelper::prepareLoginResponse($updated_customer);
        return CommonHelper::jsonSuccessResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_success'], $response);
    }

    public static function updateCustomerWhileLogin($inputs = [], $customer = []) {
        $inputs['customer_uuid'] = $customer['customer_uuid'];
        $customer_inputs_data = CustomerUpdateValidationHelper::processCustomerInputs($inputs);
        if (!$customer_inputs_data['success']) {
            return CommonHelper::jsonErrorResponse($customer_inputs_data['message']);
        }
        $update_inputs = $customer_inputs_data['data'];
//        $update_inputs['customer_uuid'] = $customer['customer_uuid'];
        $update = Customer::updateCustomer('customer_uuid', $update_inputs['customer_uuid'], $update_inputs);
        if (!$update) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['invalid_login']);
        }
        $customer_details = Customer::getSingleCustomer('customer_uuid', $update_inputs['customer_uuid']);
        $response = CustomerResponseHelper::prepareLoginResponse($customer_details);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_success'], $response);
    }

    /**
     * update Device Data method
     * @param type $inputs
     * @param type $profile_uuid
     * @return type
     */
    public static function updateDeviceData($inputs, $params) {
        // PREPARING DEVICE DATA
        UserDevice::where('user_id', $params['user_id'])->delete();         //Remove all of the previous device tokens

        $device = UserDevice::getUserDeviceByUser($params['user_id'], $inputs['device_type']);

        $device_data = [];
        $device_data['device_type'] = $inputs['device_type'];
        $device_data['device_token'] = $inputs['device_token'];
        $device_data['user_id'] = $params['user_id'];

        $publicIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'];
        User::where('id', $device_data['user_id'])->update(['last_login_ip' => $publicIP]);
        if (!empty($device)) {
            return UserDevice::updateUserDevice('id', $device['id'], $device_data);
        } elseif (empty($device)) {
            $device_data['device_uuid'] = UuidHelper::generateUniqueUUID("user_devices", "device_uuid");
            return UserDevice::createDevice($device_data);
        }
    }

    public static function updateProfile($inputs, $profile) {
        $update_profile = true;
        if (!empty($profile)) {
            if ($inputs['login_user_type'] == "freelancer") {
//                $data ['first_name'] = !empty($inputs['first_name']) ? $inputs['first_name'] : (!empty($profile['first_name']) ? $profile['first_name'] : null);
//                $data ['last_name'] = !empty($inputs['last_name']) ? $inputs['last_name'] : (!empty($profile['last_name']) ? $profile['last_name'] : null);
//                $data ['email'] = !empty($inputs['email']) ? $inputs['email'] : (!empty($profile['email']) ? $profile['email'] : null);
                $data ['first_name'] = !empty($inputs['first_name']) ? $inputs['first_name'] : $profile['first_name'];
                $data ['last_name'] = !empty($inputs['last_name']) ? $inputs['last_name'] : $profile['last_name'];
                $data ['email'] = !empty($inputs['email']) ? $inputs['email'] : $profile['email'];
                $update_profile = Freelancer::updateFreelancer('freelancer_uuid', $profile['freelancer_uuid'], $data);
            } else if ($inputs['login_user_type'] == "customer") {
                $data ['first_name'] = !empty($inputs['first_name']) ? $inputs['first_name'] : $profile['first_name'];
                $data ['last_name'] = !empty($inputs['last_name']) ? $inputs['last_name'] : $profile['last_name'];
                $data ['email'] = !empty($inputs['email']) ? $inputs['email'] : $profile['email'];
//                $data ['first_name'] = !empty($inputs['first_name']) ? $inputs['first_name'] : (!empty($profile['first_name']) ? $profile['first_name'] : null);
//                $data ['last_name'] = !empty($inputs['last_name']) ? $inputs['last_name'] : (!empty($profile['last_name']) ? $profile['last_name'] : null);
//                $data ['email'] = !empty($inputs['email']) ? $inputs['email'] : (!empty($profile['email']) ? $profile['email'] : null);
                $update_profile = Customer::updateCustomer('customer_uuid', $profile['customer_uuid'], $data);
            }
        }
        return (!$update_profile) ? false : true;
    }

    public static function removeDeviceTokens($profile_uuid) {
        $result = true;
        $device = UserDevice::getUserAllDevices('user_id', $profile_uuid);
        $device_data = [];
        $device_data['device_token'] = null;
        $device_data['user_id'] = $profile_uuid;
        if (!empty($device)) {
            $result = UserDevice::updateUserDevice('user_id', $profile_uuid, $device_data);
        }
        return $result;
    }

}

?>
