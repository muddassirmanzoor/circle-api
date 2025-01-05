<?php

namespace App\Helpers;

use App\Appointment;
use App\BankDetail;
use App\ClassBooking;
use App\ClassSchedule;
use App\Location;
use App\PaymentDue;
use App\PaymentRequest;
use App\SubscriptionSetting;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Freelancer;
use App\FreelancerEarning;
use App\BlockedTime;
use App\Session;
use App\SessionService;
use App\Qualification;
use App\NotificationSetting;
use App\Folder;
use App\Customer;
use App\Client;
use App\WalkinCustomer;
use App\ScreenSetting;
use App\Subscription;
use App\Post;
use App\Package;
use App\Classes;
use App\FreelancerTransaction;
use App\StoryView;
use App\Http\Controllers\ChatController;

Class FreelancerHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerHelper that contains all the freelancer related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use freelancer processes
      |
     */

    /**
     * Description of FreelancerHelper
     *
     * @author ILSA Interactive
     */
    public static function signupProcess($inputs) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::signupRules()['rules'], FreelancerValidationHelper::signupRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $inputs['freelancer_uuid'] = UuidHelper::generateUniqueUUID("freelancers", "freelancer_uuid");

        if (empty($inputs['facebook_id']) && empty($inputs['google_id']) && empty($inputs['apple_id'])) {
            if (empty($inputs['password'])) {
                return CommonHelper::jsonErrorResponse(FreelancerValidationHelper::signupRules()['message_' . strtolower($inputs['lang'])]['missing_password']);
            }
            $inputs['password'] = CommonHelper::convertToHash($inputs['password']);
        }
        $inputs['default_currency'] = !empty($inputs['currency']) ? $inputs['currency'] : "SAR";
        $inputs['profile_type'] = !empty($inputs['profile_type']) ? $inputs['profile_type'] : 0;

        if (!empty($inputs['company_logo'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['company_logo'], CommonHelper::$s3_image_paths['company_logo']);
        }
        $already_exist = Freelancer::checkFreelancerExistByPhone($inputs['phone_number']);

        if ($already_exist) {
            return CommonHelper::jsonErrorResponse("Phone number already exist");
        }

        $save_user = User::saveUser();

        $inputs['user_id'] = $save_user['id'];
        $save_freelancer = Freelancer::saveFreelancer($inputs);

        if (!$save_freelancer) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['signup_error']);
        }
        //$inputs['profile_uuid'] = $inputs['freelancer_uuid'];
        $inputs['profile_id'] = $save_freelancer['id'];
        $inputs['freelancer_id'] = $save_freelancer['id'];

        $data = ['user_id' => $inputs['user_id'], 'new_appointment' => 1, 'cancellation' => 1, 'no_show' => 1, 'new_follower' => 1];
        $add_settings = NotificationSetting::addSetting($data);

        $folder_data = ['freelancer_id' => $inputs['freelancer_id'], 'image' => null, 'name' => 'Free', 'type' => 'unpaid'];
        $folder = Folder::saveFolder($folder_data);

        $inputs['device'] = ['device_type' => (!empty($inputs['device_type'])) ? $inputs['device_type'] : '', 'device_token' => (!empty($inputs['device_token'])) ? $inputs['device_token'] : ''];

        $update_device = LoginHelper::updateDeviceData($inputs['device'], $inputs);

        $save_freelancer['type'] = "freelancer";

        $create_admin_chat = ChatController::createAdminChat($save_freelancer);

        if (!$update_device || !$add_settings || !$folder) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['signup_error']);
        }

        $freelancer = Freelancer::getFreelancerDetail('id', $save_freelancer['id']);

        $response = FreelancerResponseHelper::prepareSignupResponse($freelancer);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
//        $send_code = VerificationHelper::getCode($inputs);
//        return self::processSignup($send_code, $inputs, $response);
    }

    public static function processSignup($code = null, $inputs = [], $response = []) {
        $code_data = json_decode(json_encode($code));
        if (!$code_data->original->success) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['signup_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function freelancerSocialSignp($inputs = []) {
        $inputs['freelancer_uuid'] = UuidHelper::generateUniqueUUID("freelancers", "freelancer_uuid");
        // --- as per the discussion with khalid/we will leave first_name/last_name, if that do not comes from social media api side
        /*if (empty($inputs['first_name'])) {
            if (!empty($inputs['email'])) {
                $mail_parts = explode("@", $inputs['email']);
                $inputs['first_name'] = $mail_parts[0];
                $inputs['last_name'] = "Freelancer";
            } else {
                $uuid_parts = explode("-", $inputs['freelancer_uuid']);
                $inputs['first_name'] = "Freelancer";
                $inputs['last_name'] = $uuid_parts[0];
            }
        }*/

        $validation = Validator::make($inputs, FreelancerValidationHelper::socialSignupRules()['rules'], FreelancerValidationHelper::socialSignupRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['default_currency'] = !empty($inputs['currency']) ? $inputs['currency'] : "SAR";
        $inputs['profile_type'] = !empty($inputs['profile_type']) ? $inputs['profile_type'] : 0;
        $inputs['onboard_count'] = 2;
        $save_freelancer = Freelancer::saveFreelancer($inputs);
        if (!$save_freelancer) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['signup_error']);
        }
        return self::freelancerSocialSignpProcess($inputs);
    }

    public static function freelancerSocialSignpProcess($inputs = []) {

        $inputs['profile_uuid'] = $inputs['freelancer_uuid'];
        $data = ['profile_uuid' => $inputs['profile_uuid'],
            'new_appointment' => 1,
            'user_id' => $inputs['user_id'],
            'cancellation' => 1, 'no_show' => 1, 'new_follower' => 1];

        $add_settings = NotificationSetting::addSetting($data);
        $folder_data = ['freelancer_id' => CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']), 'image' => null, 'name' => 'Free', 'type' => 'unpaid'];
        $folder = Folder::saveFolder($folder_data);
        $inputs['device'] = ['device_type' => (!empty($inputs['device_type'])) ? $inputs['device_type'] : '', 'device_token' => (!empty($inputs['device_token'])) ? $inputs['device_token'] : ''];
        $update_device = LoginHelper::updateDeviceData($inputs['device'], $inputs);
        if (!$update_device || !$add_settings || !$folder) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['signup_error']);
        }

        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
        $freelancer['type'] = "freelancer";
        $create_chat = ChatController::createAdminChat($freelancer);
        $response = LoginHelper::processFreelancerLoginResponse($freelancer);
        DB::commit();

        return CommonHelper::jsonSuccessResponse(LoginValidationHelper::validationMessages()['message_' . strtolower($inputs['lang'])]['login_success'], $response);
    }

    public static function updateFreelancerNameProcess($inputs = []) {

        $save_profile = FreelancerHelper::updateFreelancerName($inputs);
        $result = json_decode(json_encode($save_profile));
        if (!$result->original->success) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse($result->original->message);
        }
        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
        $response = FreelancerResponseHelper::freelancerProfileResponse($freelancer);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['update_success'], $response);
    }

    public static function updateFreelancerName($inputs = []) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::updateFreeLancerNameRules()['rules'], FreelancerValidationHelper::updateFreeLancerNameRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $freelancer_update_inputs['first_name'] = $inputs['first_name'];
        $freelancer_update_inputs['last_name'] = $inputs['last_name'];

        Freelancer::updateFreelancer('freelancer_uuid', $inputs['freelancer_uuid'], $freelancer_update_inputs);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['update_success']);
    }

    public static function updateFreelancerProcess($inputs = []) {
        $freelancerDetails = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
        if(isset($inputs['phone_number']) && $freelancerDetails['phone_number'] != $inputs['phone_number']) {
            $validation = Validator::make($inputs, FreelancerValidationHelper::updatePhoneWithPinCode()['rules'], FreelancerValidationHelper::updatePhoneWithPinCode()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }

            $verificationResult = VerificationHelper::phoneChangeVerification($inputs);
            if(!$verificationResult->original['success']) {
                return $verificationResult;
            }
        }

        $save_profile = FreelancerHelper::updateFreelancer($inputs);

        $result = json_decode(json_encode($save_profile));
        if (!$result->original->success) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse($result->original->message);
        }
        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);

        $response = FreelancerResponseHelper::freelancerProfileResponse($freelancer);

        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['update_success'], $response);
    }

    public static function updateFreelancer($inputs = []) {

        $validation = Validator::make($inputs, FreelancerValidationHelper::updateProfileRules()['rules'], FreelancerValidationHelper::updateProfileRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if (!empty($inputs['profession']) && empty($inputs['profession_uuid'])) {
            return CommonHelper::jsonErrorResponse(FreelancerValidationHelper::updateProfileRules()['message_' . strtolower($inputs['lang'])]['missing_profession_uuid']);
        }

        $check_freelancer = Freelancer::checkFreelancer('freelancer_uuid', $inputs['freelancer_uuid']);
        // Here we are simple updating the basic info of freelance table
        $freelancer_inputs_data = FreelancerValidationHelper::processFreelancerInputs($inputs, $check_freelancer);
        if (!$freelancer_inputs_data['success']) {
            return CommonHelper::jsonErrorResponse($freelancer_inputs_data['message']);
        }
        $freelancer_inputs = $freelancer_inputs_data['data'];
        if (!empty($inputs['phone_number'])) {
            $phone_number = !empty($check_freelancer['phone_number']) ? $check_freelancer['phone_number'] : null;
            $update_number = $check_freelancer['phone_number'];
            if (!empty($phone_number) && $inputs['phone_number'] == $phone_number) {
                $update_number = $phone_number;
            }
            if (!empty($update_number)) {
                $validation = Validator::make($inputs, FreelancerValidationHelper::uniqueFreelancerPhoneRules()['rules'], FreelancerValidationHelper::uniqueFreelancerPhoneRules()['message_' . strtolower($inputs['lang'])]);
                if ($validation->fails()) {
                    return CommonHelper::jsonErrorResponse($validation->errors()->first());
                }
            }

            $freelancer_inputs['phone_number'] = !empty($inputs['phone_number']) ? $inputs['phone_number'] : $update_number;
            //previous work
            //            $validation = Validator::make($inputs, FreelancerValidationHelper::phoneValidationRules()['rules'], FreelancerValidationHelper::phoneValidationRules()['message_' . strtolower($inputs['lang'])]);
            //            if ($validation->fails()) {
            //                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            //            }
            //            $freelancer_inputs['phone_number'] = $inputs['phone_number'];
            $freelancer_inputs['country_code'] = !empty($inputs['country_code']) ? $inputs['country_code'] : null;
            $freelancer_inputs['country_name'] = !empty($inputs['country_name']) ? $inputs['country_name'] : null;
        }
        $process_images = FreelancerMediaHelper::freelancerProfileMediaProcess($freelancer_inputs, $inputs);

        if (!$process_images['success']) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_error']);
        }

        $freelancer_update_inputs = $process_images['response'];
        if (!empty($inputs['profile_image'])) {
            $result = ThumbnailHelper::processThumbnails($inputs['profile_image'], 'profile_image', 'freelancer');
            if (!$result['success']) {
                //                return CommonHelper::jsonErrorResponse($result['data']['errorMessage']);
                return CommonHelper::jsonErrorResponse("Profile image could not be processed");
            }
        }

        if (!empty($inputs['cover_image'])) {
            $result = ThumbnailHelper::processThumbnails($inputs['cover_image'], 'cover_image', 'freelancer');
            if (!$result['success']) {
                //                return CommonHelper::jsonErrorResponse($result['data']['errorMessage']);
                return CommonHelper::jsonErrorResponse("Cover image could not be processed");
            }
        }
        if (!empty($freelancer_inputs['default_currency']) && !empty($freelancer_update_inputs['travelling_cost_per_km'])) {
            $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
            $freelancer_update_inputs['travelling_cost_per_km'] = CommonHelper::getConvertedCurrency($freelancer_update_inputs['travelling_cost_per_km'], $freelancer['default_currency'], $freelancer_inputs['default_currency']);
        }

        $update = Freelancer::updateFreelancer('freelancer_uuid', $inputs['freelancer_uuid'], $freelancer_update_inputs);

        $inputs['freelancerId'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $process_qualifications = self::processFreelancerQualifications($inputs);

        if (!empty($inputs['currency'])) {

            $update_freelancer_categories = self::updateFreelancerCategoriesCurrency($inputs);

            if (!$update_freelancer_categories['success']) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_error']);
            }
        }
        if (!$update || !$process_qualifications['success']) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_error']);
        }
        if (isset($inputs['bankDetail'])) {
            $bank_resp = BankDetail::updateBankDetail('freelancer_id', $inputs['freelancerId'], $inputs['bankDetail']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['update_success']);
    }

    public static function processFreelancerQualifications($inputs = []) {
        $inputs['freelancerId'] = CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['freelancer_uuid']);
        $result = ['success' => true, 'data' => []];
        if (!empty($inputs['qualifications'])) {
            $qualification_inputs = [];
            foreach ($inputs['qualifications'] as $key => $value) {
                $qualification_inputs[$key]['qualification_uuid'] = UuidHelper::generateUniqueUUID('qualifications', 'qualification_uuid');
                $qualification_inputs[$key]['freelancer_id'] = $inputs['freelancerId'];
                $qualification_inputs[$key]['title'] = $value['title'];
            }
            $delete_qualification = Qualification::deleteQualifications('freelancer_id', $inputs['freelancerId']);
            $save_qualification = Qualification::saveQualifications($qualification_inputs);
            if (!$save_qualification) {
                $result = ['success' => false, 'data' => []];
            }
        } elseif (isset($inputs['qualifications']) && empty($inputs['qualifications'])) {
            $delete_qualification = Qualification::deleteQualifications('freelancer_id', $inputs['freelancerId']);
            if (!$delete_qualification) {
                $result = ['success' => false, 'data' => []];
            }
        }
        return $result;
    }

    public static function freelancerAddBlockTime($inputs = []) {

        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerAddBlockTimeRules()['rules'], FreelancerValidationHelper::freelancerAddBlockTimeRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $freelancer_blockTime_data = FreelancerDataHelper::makeFreelancerBlockTimeArray($inputs);

        $appointment_check = BlockTimeHelper::checkFreelancerScheduledAppointment($freelancer_blockTime_data);

        if ($appointment_check) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['appointment_overlap_error']);
        }

        $class_check = BlockTimeHelper::checkFreelancerScheduledClass($freelancer_blockTime_data);

        if ($class_check) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['class_overlap_error']);
        }
        $blocked_time_check = BlockTimeHelper::checkFreelancerBlockedTiming($freelancer_blockTime_data);

        if ($blocked_time_check) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['blocked_time_overlap_error']);
        }

        $save_blocktime = BlockedTime::saveSchedule($freelancer_blockTime_data);
        if (!$save_blocktime) {
            DB::rollBack();
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

    public static function changePassword($inputs = []) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerChangePasswordRules()['rules'], FreelancerValidationHelper::freelancerChangePasswordRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        if (($inputs['login_user_type'] != 'freelancer') && $inputs['login_user_type'] != 'customer') {
            return CommonHelper::jsonErrorResponse('User type is incorrect');
        }
        $column = $inputs['login_user_type'] . '_uuid';
        $updateMethod = 'update' . ucfirst($inputs['login_user_type']);
        $detailMethod = ($inputs['login_user_type'] == 'freelancer') ? 'getFreelancerDetail' : 'getSingleCustomer';
        $get_model = self::getUserTypeDetail($inputs);

        $resp = $get_model::$detailMethod($column, $inputs['profile_uuid']);
        if (empty($resp)) {
            return CommonHelper::jsonErrorResponse('Invalid request, ' . $inputs['login_user_type'] . ' uuid is not available in our records');
        }

        if (!Hash::check($inputs['old_password'], $resp['password'])) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['old_password_error']);
        } else {
            $update = $get_model::$updateMethod($column, $inputs['profile_uuid'], ['password' => Hash::make($inputs['new_password'])]);
            if (!$update) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['change_password_error']);
            }
            DB::commit();
            return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['change_password_success']);
        }
    }

    public static function getUserTypeDetail($input) {
        $model = '';
        if ($input['login_user_type'] == 'freelancer') {
            $model = new Freelancer();
        } elseif ($input['login_user_type'] == 'customer') {
            $model = new Customer();
        }
        return $model;
    }

    public static function addSession($inputs = []) {
        $freelancerSessionData = FreelancerDataHelper::makeFreelancerSessionArray($inputs);
        $validation = Validator::make($freelancerSessionData, SessionValidationHelper::addSessionRules()['rules'], SessionValidationHelper::addSessionRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $session = Session::saveSession($freelancerSessionData);
        $sessionServiceData = FreelancerDataHelper::makeFreelancerSessionServicesArray($inputs, $session['session_uuid']);
        $session_service = SessionService::saveSessionService($sessionServiceData);
        if (empty($session) || !$session_service) {
            DB::rollBack();
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

    public static function setAppointmentsSearchParams($search_params, $return_array) {
        foreach ($search_params as $key => $param) {
            switch ($key) {
                case 'date':
                    $return_array['date'] = ['date', '=', $param];
                    break;

                case 'from_date':
                    $return_array[] = ['date', '>=', $param];
                    break;

                case 'to_date':
                    $return_array[] = ['date', '<=', $param];
                    break;
//
                case 'from_time':
                    $return_array[] = ['from_time', '>=', $param];
                    break;

                case 'to_time':
                    $return_array[] = ['to_time', '<=', $param];
                    break;

                case 'service_uuid':
                    $return_array[] = ['appointment_services.service_uuid', '=', $param];
                    break;

                default:
                    $return_array[] = [$key, '=', $param];
            }
        }

        return $search_params;
    }

    public static function freelancerGetDashboardDetail($inputs = []) {

        $response = [];
        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerDashboarDetailRules()['rules'], FreelancerValidationHelper::freelancerDashboarDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $check_freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
        $inputs['freelancer_id'] = $check_freelancer['id'];

        $inputs['logged_in_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'], 'id', 'freelancer_uuid');

        if (empty($check_freelancer)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        if (isset($check_freelancer['freelancer_categories'][0]) && !empty($check_freelancer['freelancer_categories'][0]['currency'])) {
            $inputs['from_currency'] = $check_freelancer['freelancer_categories'][0]['currency'];
        } else {
            $inputs['from_currency'] = $check_freelancer['default_currency'];
        }

        $inputs['to_currency'] = $check_freelancer['default_currency'];

        $response['counts'] = self::getFreelancerAppointmentcounts($inputs);
        $stories = FreelancerProfileHelper::checkStory($inputs);

        $story_uuid_array = StoryView::pluckData('user_id', $inputs['logged_in_id'], 'story_id');
        $data_to_validate = ['story_uuid_array' => $story_uuid_array];
        $response['stories'] = StoryResponseHelper::processStoriesResponse($stories, $data_to_validate, $inputs['login_user_type']);

        $all_appointments = Appointment::getUpcomingAppointments('freelancer_id', $inputs['freelancer_id'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));

        $response['all_appointments'] = AppointmentResponseHelper::upcomingAppointmentsResponse($all_appointments, $inputs['local_timezone']);

        if (empty($response['all_appointments'])) {
            $response['all_appointments'] = [];
        }
        $classes = Classes::getClasses('freelancer_id', $inputs['freelancer_id'], ['status' => 'confirmed'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));

        $classes_response = ClassResponseHelper::freelancerClassesForDahsboardResponseByDate($classes, $inputs['local_timezone'], null, $response['counts']);
        if (empty($classes_response)) {
            $classes_response = [];
        }

        $response['all_appointments'] = array_merge($response['all_appointments'], $classes_response);
        usort($response['all_appointments'], function ($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t1 - $t2;
        });
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getClassCount($val, $status) {

        $classIds = Classes::where('freelancer_uuid', '=', $val)
                ->where('end_date', '>', date('Y-m-d'))
                ->pluck('class_uuid');
        $query = ClassSchedule::whereIn('class_uuid', $classIds);
        $query->where('status', '=', $status);
        return $query->count();
    }

    public static function getFreelancerAppointmentcounts($inputs) {


        //$classCount = self::getClassCount($inputs['freelancer_uuid'],'cancelled');

        Log::info('Appointment Counts Inputs', [
            'inputs' => $inputs,
        ]);

        // $withdraw_info = self::getFreelancerWithDrawDetails($inputs);

        $data = [];
        $classes = Classes::getClasses('freelancer_id', $inputs['freelancer_id'], ['status' => 'confirmed'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        $classes_response = ClassResponseHelper::freelancerClassesResponseByDate($classes, $inputs['local_timezone']);

        $confirmed_appointment_count = Appointment::getFreelancerAppointmentsCount($inputs['freelancer_id'], 'confirmed');

        $confirmed_classes_count = 0;
        foreach ($classes_response as $class){
            if($class['type'] == 'class' && $class['status'] == 'confirmed'){
                $confirmed_classes_count++;
            }
        }

        $data[] = ['title' => 'confirmed', 'count' => ($confirmed_appointment_count + $confirmed_classes_count)];

//        $data[] = ['title' => 'confirmed', 'count' => Appointment::getFreelancerAppointmentsCount($inputs['freelancer_id'], 'confirmed')];

        $data[] = ['title' => 'pending', 'count' => Appointment::getFreelancerAppointmentsCount($inputs['freelancer_id'], 'pending')];

        $cancelledCount = Appointment::getFreelancerAppointmentsCount($inputs['freelancer_id'], 'cancelled') ;
        $cancellClassCount = Classes::getCancelledClassCount($inputs['freelancer_id']);

        $data[] = ['title' => 'cancelled', 'count' => ($cancelledCount + $cancellClassCount)];
        $check_freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);

//        $earnings = FreelancerTransaction::calculateEarnings('freelancer_id', $inputs['freelancer_id']);
        $availableFreelancerEarning = FreelancerEarning::getEarningWRTTime('freelancer_id', $check_freelancer['id'], 'available');
        $getAvailableBalance = BankHelper::convertFreelancerEarningAccordingToCurrency($check_freelancer, $availableFreelancerEarning);
//        $res = $earnings - ($earnings * (CommonHelper::$circle_commission['commision_rate_percentage'] / 100));
//        $wallet = CommonHelper::getConvertedCurrency($res, $inputs['from_currency'], $inputs['to_currency']);
        //$data[] = ['title' => 'wallet', 'count' => $withdraw_info['available_withdraw']];
        $data[] = ['title' => 'wallet', 'count' => $getAvailableBalance];
        $data[] = ['title' => 'clients', 'count' => Client::getClientsCount('freelancer_id', $inputs['freelancer_id'])];
        $screenCount = self::screenSettingCount($inputs);
        $data[] = ['title' => 'Classes', 'count' => ( $cancellClassCount + $confirmed_classes_count)];

        if(!is_null($screenCount)){
            $data[] = $screenCount;
        }

        Log::info('Appointment Counts Data', [
            'data' => $data,
        ]);
        return $data;
    }

    public static function getFreelancerWithDrawDetails($inputs) {

        $response['total_amount'] = PaymentDue::getUserTotalEarnings($inputs, false);

        $response['completed_withdraw'] = PaymentRequest::getPaymentRequestAmount(2, $inputs['freelancer_id']);

        $response['requested_withdraw'] = PaymentRequest::getPaymentRequestAmount(0, $inputs['freelancer_id']);

        $response['processed_withdraw'] = PaymentRequest::getPaymentRequestAmount(1, $inputs['freelancer_id']);

        $response['pending_withdraw'] = $response['total_amount'] - ($response['completed_withdraw'] + $response['requested_withdraw'] + $response['processed_withdraw']);
        $response['pending_withdraw'] = round($response['pending_withdraw'], 2);

        $response['available_withdraw'] = BankHelper::calculateFreelancerAvailableWithdraw($inputs, $response);

        return $response;
    }

    public static function screenSettingCount($inputs) {
        $count_array = null;
//        $subscription_count = Subscription::getSubscribersCount('subscribed_id', $inputs['freelancer_id']);
//        $count_array = ['title' => 'Subscriptions', 'count' => $subscription_count];

        $screen_setting = ScreenSetting::getSetting('freelancer_id', $inputs['freelancer_id']);
        if (!empty($screen_setting)) {
            $count_array = ['title' => $screen_setting['show_option'], 'count' => 0];
            if (strtolower($screen_setting['show_option']) == 'subscriptions') {
                $count_array['count'] = Subscription::getSubscribersCount('subscribed_id', $inputs['freelancer_id']);
            } elseif (strtolower($screen_setting['show_option']) == 'analytics') {
                $count_array['count'] = Post::getPostCount('freelance_id', $inputs['freelancer_id']);
            } elseif (strtolower($screen_setting['show_option']) == 'packages') {
                $packages = Package::getAllPackages('freelancer_id', $inputs['freelancer_id']);
//                $active_packages = PackageHelper::getfreelancerActivePackages($packages);
                $count_array['count'] = count($packages);
            } elseif (strtolower($screen_setting['show_option']) == 'classes') {
                $count_array['count'] = Classes::getUpcomingClassesCount('freelancer_id', $inputs['freelancer_id'], date('Y-m-d H:i:s'));
            }
        }

        return $count_array;
    }

    public static function saveLocation($inputs, $location) {
        $validation = Validator::make($location, LocationValidationHelper::addLocationRules()['rules'], LocationValidationHelper::addLocationRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $location_inputs = LocationHelper::processAddressInputs($location);
        $location_inputs['location_uuid'] = UuidHelper::generateUniqueUUID('locations', 'location_uuid');
        $save_location = Location::saveLocation($location_inputs);
        return $save_location;
    }

    public static function getSubscriptions($inputs = []) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerUuidRules()['rules'], FreelancerValidationHelper::freelancerUuidRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $subscriptions = SubscriptionSetting::getFreelancerSubscriptions('freelancer_uuid', $inputs['freelancer_uuid']);
        if (!$subscriptions) {
            DB::rollBack();
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['empty_error']);
        }
        DB::commit();
        $response = FreelancerResponseHelper::makeFreelancerSucscriptionArr($subscriptions);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getFreelancerClients($inputs = []) {

        $validation = Validator::make($inputs, FreelancerValidationHelper::getFreelancerClientsRules()['rules'], FreelancerValidationHelper::getFreelancerClientsRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $clients_id_array = Client::getClientsColumn($inputs['freelancer_id'], 'customer_id');

        $clients = Client::getClients('freelancer_id', $inputs['freelancer_id']);

        // $customers = Customer::searchClientCustomers($clients_id_array);

        $customer_response = CustomerResponseHelper::ClientsListResponse($clients, $inputs);

//        $walkin_customers = WalkinCustomer::searchClientWalkinCustomers($inputs['freelancer_uuid']);
        //$walkin_customers = WalkinCustomer::searchMultipleClientWalkinCustomers($clients_id_array);
        //$walkin_customers_response = WalkinCustomerResponseHelper::searchWalkinCustomersResponse($walkin_customers);
        //$merge_customers = array_merge($customer_response, $walkin_customers_response);
        //   $merge_customers = $customer_response;


        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $customer_response);
    }

    public static function updateFreelancerCategoriesCurrency($inputs = []) {

        $result = ['success' => true, 'data' => []];
        $inputs['freelancer_id'] = CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['freelancer_uuid']);
        if (!empty($inputs['currency'])) {
            $freelancer_categories = \App\FreelanceCategory::getAllCategories('freelancer_id', $inputs['freelancer_id']);

            $data = [];
            foreach ($freelancer_categories as $key => $value) {
                $data['currency'] = $inputs['currency'];
                $data['freelancer_category_uuid'] = $value['freelancer_category_uuid'];
                if ($value['currency'] != $inputs['currency']) {
                    if ($value['price'] == null) {
                        $data['price'] = null;
                    } else {
                        $data['price'] = $value['price'] * config('general.globals.' . $inputs['currency']);
                    }
                    $update = \App\FreelanceCategory::updateCategories('freelancer_category_uuid', $value['freelancer_category_uuid'], $data);
                    if (!$update) {
                        $result = ['success' => false, 'data' => []];
                        return $result;
                    }
                }
            }
        }
        return $result;
    }

}

?>
