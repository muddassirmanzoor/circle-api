<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use DB;
use App\Classes;
use App\Package;
use App\PackageService;
use App\FreelanceCategory;
use App\Appointment;
use App\Freelancer;
use App\ClassBooking;
use App\ClassSchedule;
use App\Customer;
use App\WalkinCustomer;
use Illuminate\Support\Facades\Redirect;

Class PackageHelper {
    /*
      |--------------------------------------------------------------------------
      | PackageHelper that contains all the Package related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Package processes
      |
     */

    /**
     * Description of PackageHelper
     *
     * @author ILSA Interactive
     */
    public static function addPackage($inputs = []) {
        $add_package_data = PackageDataHelper::makeAddPackageArray($inputs);
        $freelancer = \App\Freelancer::checkFreelancer('freelancer_uuid', $inputs['freelancer_uuid']);
        if (empty($freelancer)) {
            return CommonHelper::jsonErrorResponse('Freelancer profile has been deleted');
        }
        if ($freelancer['profile_type'] == 0 || empty($freelancer['bank_detail'])) {
            return CommonHelper::jsonErrorResponse('Please complete your profile or check if you have added bank detail');
        }
        if (empty($inputs['package_type'])) {
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::addSessionPackageRules()['message_' . strtolower($inputs['lang'])]['missing_package_type']);
        }

        if (strtolower($inputs['package_type']) == 'session') {
            $validation = Validator::make($add_package_data, PackageValidationHelper::addSessionPackageRules()['rules'], PackageValidationHelper::addSessionPackageRules()['message_' . strtolower($inputs['lang'])]);
        } elseif (strtolower($inputs['package_type']) == 'class') {
            $validation = Validator::make($add_package_data, PackageValidationHelper::addClassPackageRules()['rules'], PackageValidationHelper::addClassPackageRules()['message_' . strtolower($inputs['lang'])]);
        } else {
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::addSessionPackageRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
        }

        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }


        if (!empty($inputs['package_image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['package_image'], CommonHelper::$s3_image_paths['package_image']);
            $add_package_data['package_image'] = $inputs['package_image'];
        }

        if (!empty($inputs['description_video'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['description_video'], CommonHelper::$s3_image_paths['package_description_video']);
            $add_package_data['description_video'] = $inputs['description_video'];
            if (!empty($inputs['description_video_thumbnail'])) {
                MediaUploadHelper::moveSingleS3Image($inputs['description_video_thumbnail'], CommonHelper::$s3_image_paths['package_description_video']);
                $add_package_data['description_video_thumbnail'] = $inputs['description_video_thumbnail'];
            }
        }
        $freelancer_id = CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['freelancer_uuid'], 'id');

        $add_package_data['freelancer_id'] = $freelancer_id;
        $package = Package::savePackage($add_package_data);

        if (empty($package)) {
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::addPackageRules()['message_' . strtolower($inputs['lang'])]['package_save_error']);
        }

        $package_details = self::getClassSessionPackageDetails($inputs, $add_package_data, $package);

        if (isset($package_details->original['success']) && !$package_details->original['success']) {
            return CommonHelper::jsonErrorResponse($package_details->original['message']);
        }

        $response = PackageResponseHelper::singlePackageResponse($package_details);

        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function updatePackage($inputs = []) {

        $validation = Validator::make($inputs, PackageValidationHelper::updatePackageRules()['rules'], PackageValidationHelper::updatePackageRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $package = Package::getSinglePackage('package_uuid', $inputs['package_uuid']);

        if (empty($package)) {
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::updatePackageRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
        }
        $update_package_data = PackageDataHelper::updatePackageArray($inputs);
        if (!empty($inputs['package_image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['package_image'], CommonHelper::$s3_image_paths['package_image']);
            $update_package_data['package_image'] = $inputs['package_image'];
        }
        if (!empty($inputs['description_video'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['description_video'], CommonHelper::$s3_image_paths['package_description_video']);
            $update_package_data['description_video'] = $inputs['description_video'];
            if (!empty($inputs['description_video_thumbnail'])) {
                MediaUploadHelper::moveSingleS3Image($inputs['description_video_thumbnail'], CommonHelper::$s3_image_paths['package_description_video']);
                $update_package_data['description_video_thumbnail'] = $inputs['description_video_thumbnail'];
            }
        }
        $update_package = Package::updatePackage('package_uuid', $update_package_data['package_uuid'], $update_package_data);
        if (!$update_package) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::updatePackageRules()['message_' . strtolower($inputs['lang'])]['package_update_error']);
        }
        $package_details = Package::getSinglePackage('package_uuid', $update_package_data['package_uuid']);
        $response = PackageResponseHelper::singlePackageResponse($package_details);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getClassSessionPackageDetails($inputs, $add_package_data, $package) {
        $package_details = [];

        if (strtolower($package['package_type']) == 'session') {
            $package_details = self::getSessionPackageDetail($inputs, $add_package_data, $package);
        }
        if (strtolower($package['package_type']) == 'class') {
            $package_details = self::getClassPackageDetail($inputs, $add_package_data, $package);
        }
        return $package_details;
    }

    public static function getSessionPackageDetail($inputs, $add_package_data, $package) {

        $freelancer_category = FreelanceCategory::checkCategoryExistAgaistFreelancer($add_package_data);

        if (!$freelancer_category) {
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::addPackageRules()['message_' . strtolower($inputs['lang'])]['category_error']);
        }

        $package_services_array = PackageDataHelper::makeAddPackageServiceArray($add_package_data, $package);

        $package_service_data = PackageService::savePackageService($package_services_array);

        if (!$package_service_data) {
            DB::rollBack();
            return CommonHelper::jsonSuccessResponse(PackageValidationHelper::addPackageRules()['message_' . strtolower($inputs['lang'])]['package_save_error']);
        }

        return Package::getSinglePackage('package_uuid', $package['package_uuid']);
    }

    public static function getClassPackageDetail($inputs, $add_package_data, $package) {
//        $add_package_data['date'] = date('Y-m-d');
//        if (empty($add_package_data['class_uuid'])) {
//            return CommonHelper::jsonErrorResponse(PackageValidationHelper::addPackageRules()['message_' . strtolower($inputs['lang'])]['missing_class_uuid']);
//        }
//        $check_service_class = Classes::checkClassSearviceExist('class_uuid', $add_package_data['class_uuid'], $add_package_data);
//        if (empty($check_service_class)) {
//            return CommonHelper::jsonErrorResponse(PackageValidationHelper::addPackageRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
//        }
//        if ($check_service_class['schedule_count'] < $add_package_data['no_of_session']) {
//            return CommonHelper::jsonErrorResponse(PackageValidationHelper::addPackageRules()['message_' . strtolower($inputs['lang'])]['class_session_count_error']);
//        }
//        $package_clases_array = PackageDataHelper::makeAddPackageClassArray($add_package_data, $package['package_uuid'], $check_service_class);
//        $package_clases_data = PackageClass::savePackageClass($package_clases_array);
//        if (!$package_clases_data) {
//            DB::rollBack();
//            return CommonHelper::jsonSuccessResponse(PackageValidationHelper::addPackageRules()['message_' . strtolower($inputs['lang'])]['package_save_error']);
//        }
//        $package_services_array = PackageDataHelper::makeAddPackageServiceArray($add_package_data, $package['package_uuid'], $check_service_class);
//        $package_service_data = PackageService::savePackageService($package_services_array);
//        if (!$package_service_data) {
//            DB::rollBack();
//            return CommonHelper::jsonSuccessResponse(PackageValidationHelper::addPackageRules()['message_' . strtolower($inputs['lang'])]['package_save_error']);
//        }
        return Package::getSinglePackage('package_uuid', $package['package_uuid']);
    }

    public static function getAllPackages($inputs = []) {
        $validation = Validator::make($inputs, PackageValidationHelper::getAllPackagesRules()['rules'], PackageValidationHelper::getAllPackagesRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $classes_count = self::getActiveClassesCount($inputs);

        \Log::info(print_r($classes_count, true));
//            $response = PackageResponseHelper::allPackagesResponse($all_packages, $inputs['currency']);

        $all_packages = Package::getAllPackages('freelancer_id', $inputs['freelancer_id'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));

//        $active_packages = PackageHelper::getfreelancerActivePackages($all_packages);
        if ($inputs['login_user_type'] == "freelancer") {
            $response = PackageResponseHelper::allPackagesResponse($all_packages, $inputs['currency']);
        } elseif ($inputs['login_user_type'] == "customer") {
            $response = PackageResponseHelper::allPackagesResponseForCustomer($all_packages, $inputs['currency'], $classes_count);
            \Log::info("flow is here");
            \Log::info(($response));
        }
//        $freelancer_category_uuid_array = self::getPackagesServiceUuidList($all_packages);
//        $class_list = Classes::getMultipleUpcomingClasses('freelancer_uuid', $inputs['freelancer_uuid'], 'service_uuid', $freelancer_category_uuid_array);
//        $response['upcoming_schedules'] = self::getUpcomingSchedulesPackageResponse($class_list);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getActiveClassesCount($inputs = []) {

        $count = 0;
        $total_class_count = 0;
        if (!empty($inputs['freelancer_uuid'])) {
            $class_list = Classes::getFreelancerUpcomingClasses('freelancer_id', $inputs['freelancer_id'], date('Y-m-d'));
            if (!empty($class_list)) {
                foreach ($class_list as $list) {
                    if (!empty($list['schedule'])) {
                        $total_class_count = (int) ($total_class_count + (count($list['schedule'])));
                    }
                }
            }
            $count = $total_class_count;
        }
        return $count;
    }

    public static function getfreelancerActivePackages($packages) {
        $active_packages = [];
        if (!empty($packages)) {
            foreach ($packages as $package) {
                $check_package = self::checkPackageValidity($package);
                if (!empty($check_package)) {
                    array_push($active_packages, $check_package);
                }
            }
        }
        return $active_packages;
    }

    public static function getPurchasedPackages($inputs = []) {
        $validation = Validator::make($inputs, PackageValidationHelper::getPurchasedPackagesRules()['rules'], PackageValidationHelper::getPurchasedPackagesRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        if (strtolower($inputs['login_user_type']) == 'freelancer') {
            $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
            return PackageHelper::getFreelancerPurchasedPackages($inputs);
        } elseif (strtolower($inputs['login_user_type']) == 'customer') {
            $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']);
            return PackageHelper::getCustomerPurchasedPackages($inputs);
        }
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
    }

    public static function getFreelancerPurchasedPackages($inputs = []) {
        if (empty($inputs['freelancer_uuid'])) {
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::getPurchasedPackagesRules()['message_' . strtolower($inputs['lang'])]['missing_freelancer_uuid']);
        }
        $freelancer = Freelancer::checkFreelancer('freelancer_uuid', $inputs['freelancer_uuid']);
        if (empty($freelancer)) {
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::getPurchasedPackagesRules()['message_' . strtolower($inputs['lang'])]['invalid_freelancer_uuid']);
        }
        $all_packages = Package::getPurchasedPackages('freelancer_id', $inputs['freelancer_id'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        $response = PackageResponseHelper::freelancerPurchasedPackagesResponse($all_packages, $inputs);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getCustomerPurchasedPackages($inputs = []) {
        if (empty($inputs['customer_uuid'])) {
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::getPurchasedPackagesRules()['message_' . strtolower($inputs['lang'])]['missing_customer_uuid']);
        }
        $customer = Customer::getSingleCustomerDetail('customer_uuid', $inputs['customer_uuid']);

        if (empty($customer)) {
            return CommonHelper::jsonErrorResponse(PackageValidationHelper::getPurchasedPackagesRules()['message_' . strtolower($inputs['lang'])]['invalid_customer_uuid']);
        }

        $appoint_package_uuid = Appointment::pluckFavIdsWithStatus('customer_id', $inputs['customer_id'], 'package_id');

//        $appoint_package_uuid = Appointment::pluckFavIds('customer_uuid', $inputs['customer_uuid'], 'package_uuid');
        $uniq_appoint_package_uuid = array_values(array_filter(array_unique($appoint_package_uuid)));

        $class_packages_uuids = ClassBooking::pluckClassBookingIdsWithStatus('customer_id', $inputs['customer_id'], 'package_id');

//        $class_packages_uuids = ClassBooking::pluckClassBookingIds('customer_uuid', $inputs['customer_uuid'], 'package_uuid');
        $uniq_class_packages_uuids = array_values(array_filter(array_unique($class_packages_uuids)));

        $uuids = array_merge($uniq_appoint_package_uuid, $uniq_class_packages_uuids);

        $all_packages = [];
        if (!empty($uuids)) {
            $all_packages = Package::getPackagesUsingUuids($uuids, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        }

        $response = PackageResponseHelper::customerPurchasedPackagesResponse($all_packages, $inputs);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getPurchasedPackageDetails($inputs = []) {
        if( env('APP_ENV') === 'dev')
        {
            if(isset(getallheaders()['Apikey']))
            {
                $apikey = getallheaders()['Apikey'];
            }
            else
            {
                $apikey = null;
            }
        }
        else
        {
            if(isset(getallheaders()['Apikey']))
            {
                $apikey = getallheaders()['Apikey'];
            }
            else
            {
                $apikey = null;
            }
        }
        if ($apikey == null) {
            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
                preg_match("/iPhone|Android|iPad|iPod|webOS|Linux/", $_SERVER['HTTP_USER_AGENT'], $matches);
                $os = current($matches);
                switch ($os) {
                    case 'iPhone':
                        // return Redirect::route('install-app');
                        return redirect('https://apps.apple.com/us/app/circl/id1526462647');

                        break;
                    case 'Android':
                        return Redirect::route('install-app');
//                return redirect('https://play.google.com/store/apps');
                        break;
                    case 'iPad':
                        return Redirect::route('install-app');
//                return redirect('itms-apps://itunes.apple.com/us');
                        break;
                    case 'iPod':
                        return Redirect::route('install-app');
//                return redirect('itms-apps://itunes.apple.com/us');
                        break;
                    case 'webOS':
                        return Redirect::route('install-app');
//                return redirect('https://apps.apple.com/us');
                        break;
                    case 'Linux':
//                return Route::view('/welcome', 'welcome');
                        return Redirect::route('install-app');
//                return redirect('https://apps.apple.com/us');
                        break;
                    default:
                        return Redirect::route('install-app');
                }
            }
        }
        $validation = Validator::make($inputs, PackageValidationHelper::purchasedPackageDetailRules()['rules'], PackageValidationHelper::purchasedPackageDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $package_details = Package::getPurchasedPackageDetails('package_uuid', $inputs['package_uuid'], $inputs);

        if (strtolower($package_details['package_type']) == 'session') {

            $validation = Validator::make($inputs, PackageValidationHelper::sessionPackageDetailRules()['rules'], PackageValidationHelper::sessionPackageDetailRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
        } else {

            $validation = Validator::make($inputs, PackageValidationHelper::classPackageDetailRules()['rules'], PackageValidationHelper::classPackageDetailRules()['message_' . strtolower($inputs['lang'])]);

            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
        }

        $response = PackageResponseHelper::purchasedPackageDetailResponse($package_details, $inputs);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function checkPackageValidity($package) {
        $valid_package = [];
        if ($package['validity_type'] == 'daily') {
            $days = $package['package_validity'] * 1;
        } elseif ($package['validity_type'] == 'weekly') {
            $days = $package['package_validity'] * 7;
        } elseif ($package['validity_type'] == 'monthly') {
            $days = $package['package_validity'] * 30;
        }
        $change_date_format = CommonHelper::setDateFormat($package['created_at'], "Y-m-d");
        $new_date = ClassHelper::addDaysToDate($change_date_format, $days);
        if ($new_date >= date("Y-m-d")) {
            $valid_package = $package;
        }
        return $valid_package;
    }

    public static function createPackageValidityDate($package) {
        $validity_date = null;
        if ($package['validity_type'] == 'daily') {
            $days = $package['package_validity'] * 1;
        } elseif ($package['validity_type'] == 'weekly') {
            $days = $package['package_validity'] * 7;
        } elseif ($package['validity_type'] == 'monthly') {
            $days = $package['package_validity'] * 30;
        }
        $change_date_format = CommonHelper::setDateFormat($package['created_at'], "Y-m-d");
        $validity_date = ClassHelper::addDaysToDate($change_date_format, $days);
        return $validity_date;
    }

    public static function getPackagesServiceUuidList($packages = []) {
        $freelancer_category_uuid_array = [];
        if (!empty($packages)) {
            foreach ($packages as $package) {
                if ((count($package['package_service']) > 0) || (!empty($package['package_class']) && (count($package['package_class']) > 0))) {
//                    if ($package['package_type'] == 'class' && !empty($package['package_class'][0])) {
                    if ($package['package_type'] == 'class' && !empty($package['package_service'][0])) {
                        array_push($freelancer_category_uuid_array, $package['package_service'][0]['service_uuid']);
                    }
                }
            }
        }
        return $freelancer_category_uuid_array;
    }

    public static function getUpcomingSchedulesPackageResponse($classes = []) {
        $response = [];
        if (!empty($classes)) {
            $index = 0;
            foreach ($classes as $class) {
                if (!empty($class['schedule'])) {
                    foreach ($class['schedule'] as $schedule) {
                        $response[$index]['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                        $response[$index]['class_uuid'] = $schedule['class_uuid'];
                        $response[$index]['date'] = $schedule['class_date'];
                        $index++;
                    }
                }
            }
        }
        return $response;
    }

    public static function getPackageDetails($inputs = []) {
        $validation = Validator::make($inputs, PackageValidationHelper::getPackageDetailRules()['rules'], PackageValidationHelper::getPackageDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $package_detail = Package::getSinglePackage('package_uuid', $inputs['package_uuid']);
        if (empty($package_detail)) {
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }
        $customer = Customer::getSingleCustomerDetail('customer_uuid', $inputs['customer_uuid']);
        if (empty($customer)) {
            $walkin_customer = WalkinCustomer::getCustomer('walkin_customer_uuid', $inputs['customer_uuid']);
            if (empty($walkin_customer)) {
                return CommonHelper::jsonErrorResponse(CustomerChecksValidationHelper::checkCustomerAppointmentRules()['message_' . strtolower($inputs['lang'])]['invalid_customer_uuid']);
            }
        }
        $response = ['package_details' => [], 'appointments' => []];
        $response['package_details'] = PackageResponseHelper::singlePackageResponse($package_detail);
        if (strtolower($package_detail['package_type']) == 'session') {
            $package_appointments = Appointment::getCustomerPackageAppointments($inputs['customer_uuid'], $inputs['package_uuid']);
            $response['appointments'] = AppointmentResponseHelper::makeFreelancerAppointmentsResponse($package_appointments, $inputs['local_timezone'], $inputs['login_user_type']);
        }
        if (strtolower($package_detail['package_type']) == 'class') {
            $response['appointments'] = self::getPackageClasses($inputs, $customer);
        }
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getPackageClasses($inputs = [], $customer = []) {
        $classes = [];
        if ($inputs['login_user_type'] == 'customer') {
            $booked_classes = ClassBooking::pluckPackageBookingIds($inputs['package_uuid'], $inputs['customer_uuid'], 'class_schedule_uuid');
            $get_schedules = ClassSchedule::getMultipleSchedules($booked_classes);
            $classes = AppointmentResponseHelper::customerAllClassAppointmentsResponse($get_schedules, $inputs['local_timezone'], $customer);
        }
        return $classes;
    }

    public static function getPackageBasicDetails($inputs = []) {
        $validation = Validator::make($inputs, PackageValidationHelper::getPackageBasicDetailRules()['rules'], PackageValidationHelper::getPackageBasicDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $package_detail = Package::getSinglePackage('package_uuid', $inputs['package_uuid']);
        if (empty($package_detail)) {
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }
        $response = PackageResponseHelper::singlePackageBasicResponse($package_detail);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function deletePackage($inputs = []) {
        $validation = Validator::make($inputs, PackageValidationHelper::deletePackageRules()['rules'], PackageValidationHelper::deletePackageRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $data = ['is_archive' => 1];
        $package = Package::updatePackage('package_uuid', $inputs['package_uuid'], $data);
        if (!$package) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['update_package_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

}

?>
