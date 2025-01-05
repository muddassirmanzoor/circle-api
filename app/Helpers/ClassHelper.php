<?php

namespace App\Helpers;

use App\FreelancerTransaction;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Classes;
use App\ClassSchedule;
use App\ClassBooking;
use App\Schedule;
use App\Customer;
use App\Client;
use App\Helpers\ClassBookingHelper;
use App\payment\checkout\Checkout;
use App\FreelancerEarning;

class ClassHelper
{
    /*
      |--------------------------------------------------------------------------
      | ClassHelper that contains all the class related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Class processes
      |
     */

    /**
     * Description of ClassHelper
     *
     * @author ILSA Interactive
     */
    public static function getClassDetails($inputs = [])
    {
        $validation = Validator::make($inputs, ClassValidationHelper::getClassDetailRules()['rules'], ClassValidationHelper::getClassDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $class_detail = Classes::getClassDetail('class_uuid', $inputs['class_uuid']);

        if (empty($class_detail)) {
            return CommonHelper::jsonSuccessResponse(ClassValidationHelper::getSingleDayClassRules()['message_' . strtolower($inputs['lang'])]['invalid_class_uuid']);
        }
        $response = ClassResponseHelper::classDetailsResponse($class_detail, $inputs['local_timezone'], $inputs);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    // TODO: Keeping this right now we will modify this issue when we refactor this aap
    public static function getSingleDayClass($inputs = [])
    {
        //dd(strtotime('2021-08-27 20:05:16'));

        $validation = Validator::make($inputs, ClassValidationHelper::getSingleDayClassRules()['rules'], ClassValidationHelper::getSingleDayClassRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $class_detail = Classes::getClassDetail('class_uuid', $inputs['class_uuid']);

        //$classBookings = Classes::getClassBookings('class_uuid', $inputs['class_uuid']);

        if (empty($class_detail)) {
            return CommonHelper::jsonSuccessResponse(ClassValidationHelper::getSingleDayClassRules()['message_' . strtolower($inputs['lang'])]['invalid_class_uuid']);
        }

        $response = ClassResponseHelper::singleDayClassResponse($class_detail, $inputs['date'], $inputs, $class_detail['class_bookings']);

        if (!empty($response['members'])) {
            $clients = Client::getClients('freelancer_uuid', $response['freelancer']['freelancer_uuid']);
            foreach ($response['members'] as $key => $student) {
                $response['members'][$key]['client_uuid'] = null;
                if (!empty($clients)) {
                    foreach ($clients as $single_client) {
                        if ($response['members'][$key]['customer_uuid'] == $single_client['customer_uuid']) {
                            $response['members'][$key]['client_uuid'] = $single_client['client_uuid'];
                            $response['members'][$key]['type'] = 'customer';
                            if (!empty($single_client['customer']['freelancer_id'])) {
                                $response['members'][$key]['type'] = 'walkin_customer';
                            }
                            break;
                        }
                    }
                }
            }
        }
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getSingleClassDetail($inputs = [])
    {

        $validation = Validator::make($inputs, ClassValidationHelper::getSingleDayClassRules()['rules'], ClassValidationHelper::getSingleDayClassRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $class_detail = Classes::getSingleClassDetailQuery('class_uuid', $inputs['class_uuid'], $inputs['class_schedule_uuid']);
        //dd($class_detail);
        //$classBookings = Classes::getSingleClassBookingsDetails('class_uuid', $inputs['class_uuid'],$inputs['class_schedule_uuid']);

        if (empty($class_detail)) {
            return CommonHelper::jsonSuccessResponse(ClassValidationHelper::getSingleDayClassRules()['message_' . strtolower($inputs['lang'])]['invalid_class_uuid']);
        }
        $date = (isset($inputs['date'])) ? $inputs['date'] : '';

        if ($inputs['login_user_type'] == 'customer') {
            $inputs['profile_id'] = Customer::where('customer_uuid', '=', $inputs['logged_in_uuid'])->first()->id;
        } else {
            $inputs['profile_id'] = \App\Freelancer::where('freelancer_uuid', '=', $inputs['logged_in_uuid'])->first()->id;
        }
        $response = ClassResponseHelper::singleDayClassResponseRecords($class_detail, $date, $inputs);

        if (!empty($response['members'])) {
            $freelanceId = CommonHelper::getFreelancerIdByUuid($response['freelancer']['freelancer_uuid']);
            $clients = Client::getClients('freelancer_id', $freelanceId);

            foreach ($response['members'] as $key => $student) {
                $response['members'][$key]['client_uuid'] = null;
                if (!empty($clients)) {
                    foreach ($clients as $single_client) {

                        if ($response['members'][$key]['customer_uuid'] == $single_client['customer']['customer_uuid']) {
                            $response['members'][$key]['client_uuid'] = $single_client['client_uuid'];
                            $response['members'][$key]['type'] = 'customer';
                            if (!empty($single_client['customer']['freelancer_id'])) {
                                $response['members'][$key]['type'] = 'walkin_customer';
                            }
                            break;
                        }
                    }
                }
            }
        }
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function prepareAddClassSlots($inputs = [])
    {

        if (empty($inputs['class_timing'])) {
            return ['success' => false, 'message' => 'Invalid data provided for slots'];
        }

        $schedule = Schedule::getFreelancerSchedule('freelancer_id', $inputs['freelancer_id']);

        $schedule_data = FreelancerScheduleResponseHelper::freelancerScheduleResponse($schedule);

        if (empty($schedule_data)) {
            return ['success' => false, 'message' => 'Please update your schedule first'];
        }

        $days_array = [];
        foreach ($schedule_data as $schedule_day) {
            if (!in_array($schedule_day['day'], $days_array)) {
                array_push($days_array, strtolower($schedule_day['day']));
            }
        }

        $data_array = [];

        foreach ($inputs['class_timing'] as $key => $input_schedule) {
            $validation = Validator::make($input_schedule, ClassValidationHelper::freelancerAddClassScheduleRules()['rules'], ClassValidationHelper::freelancerAddClassScheduleRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return ['success' => false, 'message' => $validation->errors()->first()];
            }

            $data_array[$key]['class_date'] = $input_schedule['date'];
            $data_array[$key]['date'] = $input_schedule['date'];
            $data_array[$key]['from_time'] = $input_schedule['start_time'];
            $data_array[$key]['to_time'] = $input_schedule['end_time'];
            $data_array[$key]['schedule_type'] = $input_schedule['schedule_type'];
            $data_array[$key]['validity_type'] = $input_schedule['validity_type'];
            $data_array[$key]['validity'] = $input_schedule['validity'];
        }


        $data_slot_array = [];
        $date_array = [];
        $index = 0;
        $days_to_add = 1;
        foreach ($data_array as $make_inputs) {
            if (strtolower($make_inputs['validity_type']) == 'daily') {
                $days_to_add = ($make_inputs['validity'] * 1);
            } elseif (strtolower($make_inputs['validity_type']) == 'weekly') {
                $days_to_add = ($make_inputs['validity'] * 7);
            } elseif (strtolower($make_inputs['validity_type']) == 'monthly') {
                $days_to_add = ($make_inputs['validity'] * 30);
            } elseif (strtolower($make_inputs['validity_type']) == 'yearly') {
                $days_to_add = ($make_inputs['validity'] * 365);
            }

            if (strtolower($make_inputs['schedule_type']) == 'weekly') {
                $days_to_add = ($days_to_add / 7);
            } elseif (strtolower($make_inputs['schedule_type']) == 'monthly') {
                $days_to_add = ($days_to_add / 30);
            } elseif (strtolower($make_inputs['schedule_type']) == 'yearly') {
                $days_to_add = ($days_to_add / 365);
            }

            $days_to_add = (int) $days_to_add;

            $add_days = 1;
            $date = $make_inputs['class_date'];
            while ($add_days <= $days_to_add) {
                $day_convert = strtotime($date);
                $day_converted = date("l", $day_convert);
                if (in_array(strtolower($day_converted), $days_array)) {
                    $data_slot_array[$index]['class_date'] = $date;
                    $data_slot_array[$index]['date'] = $date;
                    $data_slot_array[$index]['from_time'] = $make_inputs['from_time'];
                    $data_slot_array[$index]['to_time'] = $make_inputs['to_time'];

                    $data_slot_array[$index]['start_date_time'] = strtotime($date . ' ' . $make_inputs['from_time']);
                    $data_slot_array[$index]['end_date_time'] = strtotime($date . ' ' . $make_inputs['to_time']);

                    $data_slot_array[$index]['schedule_type'] = $make_inputs['schedule_type'];
                    $data_slot_array[$index]['validity_type'] = $make_inputs['validity_type'];
                    $data_slot_array[$index]['validity'] = $make_inputs['validity'];
                    $index++;
                    array_push($date_array, $date);
                }
                $date = self::increaseDate($date, $make_inputs['schedule_type']);
                $add_days++;
            }
        }

        if (empty($data_slot_array) && empty($date_array))
            return ['success' => false, 'message' => 'Today is not available in your schedule.'];
        return ['success' => true, 'inputs_array' => $data_slot_array, 'date_array' => $date_array];
    }

    public static function freelancerAddClass($inputs = [])
    {
        $time = date('H:i:s A');

        $validation = Validator::make($inputs, ClassValidationHelper::freelancerAddClassRules()['rules'], ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $freelancer = \App\Freelancer::checkFreelancer('freelancer_uuid', $inputs['freelancer_uuid']);
        if (empty($freelancer)) {
            return CommonHelper::jsonErrorResponse('Freelancer profile has been deleted');
        }
        if ($freelancer['profile_type'] == 0 || empty($freelancer['bank_detail'])) {
            return CommonHelper::jsonErrorResponse('Please complete your profile or check if you have added bank detail');
        }
        $inputs['freelancer_id'] = $freelancer['id'];
        //        $inputs['freelancer_id'] = CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['freelancer_uuid'], 'id');
        $class_schedule_data = self::prepareAddClassSlots($inputs);

        if (!$class_schedule_data['success']) {
            return CommonHelper::jsonErrorResponse($class_schedule_data['message']);
        }

        usort($class_schedule_data['date_array'], function ($a, $b) {
            $dateTimestamp1 = strtotime($a);
            $dateTimestamp2 = strtotime($b);
            return $dateTimestamp1 < $dateTimestamp2 ? -1 : 1;
        });

        $inputs['start_date'] = $class_schedule_data['date_array'][0];
        $inputs['end_date'] = $class_schedule_data['date_array'][count($class_schedule_data['date_array']) - 1];

        $freelancer_class_data = ClassDataHelper::makeFreelancerClassArray($inputs);

        if (!empty($freelancer_class_data['image'])) {
            MediaUploadHelper::moveSingleS3Image($freelancer_class_data['image'], CommonHelper::$s3_image_paths['class_images']);
        }
        if (!empty($freelancer_class_data['description_video'])) {
            if (empty($freelancer_class_data['description_video_thumbnail'])) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['add_class_error']);
            }
            MediaUploadHelper::moveSingleS3Image($freelancer_class_data['description_video'], CommonHelper::$s3_image_paths['class_description_video']);
            MediaUploadHelper::moveSingleS3Image($freelancer_class_data['description_video_thumbnail'], CommonHelper::$s3_image_paths['class_description_video']);
        }

        $add_class_resp = Classes::saveClass($freelancer_class_data);

        if (!$add_class_resp) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['add_class_error']);
        }

        $class_schedule = self::freelancerAddClassProcess($inputs, $add_class_resp, $class_schedule_data['inputs_array']);

        if (!$class_schedule['success']) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse($class_schedule['message']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponseWithoutData(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

    public static function freelancerAddClassProcess($inputs, $class, $inputs_array)
    {

        foreach ($inputs_array as $key => $day_schedule) {
            $inputs_array[$key]['freelancer_id'] = $inputs['freelancer_id'];
            $inputs_array[$key]['class_schedule_uuid'] = UuidHelper::generateUniqueUUID("class_schedules", "class_schedule_uuid");
            $inputs_array[$key]['class_id'] = $class['id'];
            $inputs_array[$key]['saved_timezone'] = 'UTC';
            $inputs_array[$key]['login_user_type'] = $inputs['login_user_type'];
            $inputs_array[$key]['online_link'] = !empty($inputs['online_link']) ? $inputs['online_link'] : null;
            $inputs_array[$key]['local_timezone'] = !empty($inputs['local_timezone']) ? $inputs['local_timezone'] : null;
            $validation = Validator::make($inputs_array[$key], ClassValidationHelper::freelancerClassScheduleRules()['rules'], ClassValidationHelper::freelancerClassScheduleRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return ['success' => false, 'message' => $validation->errors()->first()];
            }
            $apply_checks = self::applyAddClassChecks($inputs_array[$key]);
            if (!$apply_checks['success']) {

                return ['success' => false, 'message' => $apply_checks['message']];
            }
            unset($inputs_array[$key]['date']);
            unset($inputs_array[$key]['freelancer_id']);
            unset($inputs_array[$key]['login_user_type']);
        }

        $add_class_schedule_resp = ClassSchedule::saveClassSchedule($inputs_array);

        if (!$add_class_schedule_resp) {
            return ['success' => false, 'message' => MessageHelper::getMessageData('error', $inputs['lang'])['success_error']];
        }
        return ['success' => true, 'message' => FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']];
    }

    //    public static function classDayScheduleArray($class_schedules_data, $day_schedule, $class, $inputs) {
    //        $index = count($class_schedules_data);
    //        $schedule_date = $day_schedule['date'];
    //        while ($schedule_date >= $class['start_date'] && $schedule_date <= $class['end_date']) {
    //            $class_array = ['freelancer_uuid' => $inputs['freelancer_uuid'], 'date' => $schedule_date, 'from_time' => $day_schedule['start_time'], 'to_time' => $day_schedule['end_time']];
    //            $apply_checks = self::applyAddClassChecks($class_array);
    //            if (!$apply_checks['success']) {
    //                return ['success' => false, 'message' => $apply_checks['message']];
    //            }
    //            $class_schedules_data[$index]['class_schedule_uuid'] = UuidHelper::generateUniqueUUID("class_schedules", "class_schedule_uuid");
    //            $class_schedules_data[$index]['class_uuid'] = $class['class_uuid'];
    //            $class_schedules_data[$index]['class_date'] = !empty($schedule_date) ? $schedule_date : null;
    //            $class_schedules_data[$index]['from_time'] = !empty($day_schedule['start_time']) ? $day_schedule['start_time'] : null;
    //            $class_schedules_data[$index]['to_time'] = !empty($day_schedule['end_time']) ? $day_schedule['end_time'] : null;
    //            $class_schedules_data[$index]['saved_timezone'] = "UTC";
    //            $class_schedules_data[$index]['local_timezone'] = !empty($inputs['local_timezone']) ? $inputs['local_timezone'] : null;
    //            $class_schedules_data[$index]['schedule_type'] = $day_schedule['schedule_type'];
    //            $validation = Validator::make($class_schedules_data[$index], ClassValidationHelper::freelancerClassScheduleRules()['rules'], ClassValidationHelper::freelancerClassScheduleRules()['message_' . strtolower($inputs['lang'])]);
    //            if ($validation->fails()) {
    //                return ['success' => false, 'message' => $validation->errors()->first()];
    //            }
    //            $index++;
    //            $schedule_date = self::increaseDate($schedule_date, $day_schedule['schedule_type']);
    //        }
    //        return ['success' => true, 'message' => 'Success', 'data' => $class_schedules_data];
    //    }

    public static function increaseDate($schedule_date, $schedule_type)
    {
        if (strtolower($schedule_type) == 'daily') {
            $schedule_date = self::addDaysToDate($schedule_date, 1);
        }
        if (strtolower($schedule_type) == 'weekly') {
            $schedule_date = self::addDaysToDate($schedule_date, 7);
        }
        if (strtolower($schedule_type) == 'monthly') {
            $schedule_date = self::addMonthsToDate($schedule_date, 1);
        }
        return $schedule_date;
    }

    public static function addDaysToDate($date, $days)
    {
        $date_now = strtotime($date);
        $days_added = strtotime("+" . $days . " day", $date_now);
        $updated_date = date('Y-m-d', $days_added);
        return $updated_date;
    }

    public static function addMonthsToDate($date, $months)
    {
        $date_now = strtotime($date);
        $days_added = strtotime("+" . $months . " months", $date_now);
        $updated_date = date('Y-m-d', $days_added);
        return $updated_date;
    }

    public static function applyAddClassChecks($class_array, $lang = 'EN')
    {
        $available_time = ['success' => true, 'message' => 'Successful request'];
        $schedule_check = AppointmentHelper::checkFreelancerSchedule($class_array);
        if (!$schedule_check) {
            $available_time['success'] = false;
            $available_time['message'] = ClassValidationHelper::freelancerClassScheduleRules()['message_' . strtolower($lang)]['schedule_exceeding_error'];
        }

        $appointment_check = AppointmentHelper::checkFreelancerScheduledAppointment($class_array);

        if ($appointment_check) {
            $available_time['success'] = false;
            $available_time['message'] = ClassValidationHelper::freelancerClassScheduleRules()['message_' . strtolower($lang)]['appointment_overlap_error'];
        }

        $blocked_time_check = AppointmentHelper::checkFreelancerBlockedTiming($class_array);
        if ($blocked_time_check) {
            $available_time['success'] = false;
            $available_time['message'] = ClassValidationHelper::freelancerClassScheduleRules()['message_' . strtolower($lang)]['blocked_time_overlap_error'];
        }

        $class_check = AppointmentHelper::checkFreelancerClass($class_array);
        if ($class_check) {
            $available_time['success'] = false;
            $available_time['message'] = ClassValidationHelper::freelancerClassScheduleRules()['message_' . strtolower($lang)]['class_overlap_error'];
        }

        return $available_time;
    }

    public static function getActiveClassesCount($inputs = [])
    {
        $validation = Validator::make($inputs, ClassValidationHelper::getActiveClassesCountRules()['rules'], ClassValidationHelper::getActiveClassesCountRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $total_class_count = 0;
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $class_list = Classes::getFreelancerUpcomingClasses('freelancer_id', $inputs['freelancer_id'], date('Y-m-d'));
        if (!empty($class_list)) {
            foreach ($class_list as $class_list) {
                if (!empty($class_list['schedule'])) {
                    $total_class_count = (int) ($total_class_count + (count($class_list['schedule'])));
                }
            }
        }
        $response = ['total_active_classes' => $total_class_count];
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getClassesList($inputs = [])
    {

        $validation = Validator::make($inputs, ClassValidationHelper::getClassesListRules()['rules'], ClassValidationHelper::getClassesListRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $class_list_response = Classes::getFreelancerUpcomingClasses('freelancer_id', $inputs['freelancer_id'], date('Y-m-d H:i:s'));

        //dd($class_list_response);
        //        if (empty($class_list_response)) {
        //            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        //        }
        $response = ClassResponseHelper::getClassesListResponse($class_list_response, $inputs['local_timezone']);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function changeClassStatus($inputs = [])
    {
        $validation = Validator::make($inputs, ClassValidationHelper::changeClassStatusRules()['rules'], ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $updateClassStatus = false;
        $class_detail = '';
        $inputs['class_schedule_id'] = CommonHelper::getClassSchedulesIdByUuid($inputs['class_schedule_uuid']);
        $inputs['logged_in_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'], 'id');

        if ($inputs['login_user_type'] == "customer") {
            // TODO: Need to fix this there's a bug in this logic
            // $classBookingStatus = ClassBooking::getClassBookingStatus($inputs['class_schedule_id'], $inputs['logged_in_uuid']);
            $classBookingStatus = ClassBooking::getClassBookingStatus($inputs['class_schedule_id'], $inputs['logged_in_id']);

            if ($classBookingStatus === false) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['already_cancel_status']);
            }

            //$class = ClassBooking::getClassBooking($inputs['class_schedule_uuid'], $inputs['logged_in_uuid']);
            $class = ClassBooking::getClassBooking($inputs['class_schedule_id'], $inputs['logged_in_id']);

            if (!empty($class['purchased_package_uuid'])) {
                // get purchase
                $purchasePackageId = \App\PurchasesPackage::where('purchased_packages_uuid', $class['purchased_package_uuid'])->first()->id;
                $purchase = \App\Purchases::where('purchased_package_id', $purchasePackageId)->with('purchasesTransition')->first();
                $class['purchase'] = !empty($purchase) ? $purchase->toArray() : null;
            }
            $class_detail = Classes::getSingleClass('class_uuid', $inputs['class_uuid']);
            $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']);
            $inputs['freelancer_id'] = $class_detail['freelancer_id'];
            $updateClassStatus = ClassBooking::updateClassBooking($inputs['class_schedule_id'], $inputs['logged_in_id'], ["status" => "cancelled"]);

            if (!$updateClassStatus) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['update_class_error']);
            }
            $inputs['class_date'] = '';
            // if customer cancels the class then check the time and refund accordingly calculate freelancer
            //earning and compensate him accordingly
            $processRefunds = self::processRefunds($class, $inputs);
            if (isset($processRefunds['success']) && $processRefunds['success'] == false) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse('Error occurred while refunding customer! please try again later');
            }
            ProcessNotificationHelper::sendClassBookingCancelledNotification($inputs);
        } elseif ($inputs['login_user_type'] == "freelancer") {

            if ($inputs['status'] == 'cancelled') {
                $cs = ClassSchedule::where('class_schedule_uuid', $inputs['class_schedule_uuid'])->first();
                Classes::where('id', $cs->class_id)->update(['status' => $inputs['status']]);
            }

            $update_schedule_inputs = ['class_uuid' => $inputs['class_uuid'], 'class_schedule_uuid' => $inputs['class_schedule_uuid'], 'status' => $inputs['status']];

            $class_detail = Classes::getSingleClass('class_uuid', $inputs['class_uuid']);

            if (empty($class_detail)) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_class_uuid']);
            } elseif ($class_detail['end_date'] < date('Y-m-d')) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['class_date_pass']);
            } elseif ($inputs['logged_in_id'] != $class_detail['freelancer_id']) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
            }


            $class_schedule = ClassSchedule::getClassSchedule('class_schedule_uuid', $inputs['class_schedule_uuid']);

            if (empty($class_schedule)) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_schedule_uuid']);
            } elseif ($class_schedule['class_date'] < date('Y-m-d')) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['class_date_pass']);
            } elseif ($class_schedule['class_date'] == date('Y-m-d') && $class_schedule['from_time'] <= date('H:i:s')) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['class_time_pass']);
            } /* elseif (!empty($class_schedule['class_bookings'])) {
              return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['existing_booking_error']);
              } */

            //            $updateClassBooking = ClassBooking::updateClassBookings('class_schedule_id', $inputs['class_schedule_id'], ["status" => "cancelled"]);
            //            if (!$updateClassBooking) {
            //                DB::rollBack();
            //                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['update_class_error']);
            //            }

            $updateClassSchedule = ClassSchedule::updateClassSchedule('id', $inputs['class_schedule_id'], ['status' => 'cancelled']);
            if (!$updateClassSchedule) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['update_class_error']);
            }

            $inputs['freelancer_id'] = $class_detail['freelancer_id'];
            // Here we are sending the notification
            // getting all the customers who have joined this scheduled
            // as we have to refund them

            $classSchedulesBooking = ClassBooking::where('class_schedule_id', $inputs['class_schedule_id'])
                //                    ->where('status', '=', 'confirmed')
                ->with('purchase.purchasesTransition')
                ->with('schedule')
                ->get();

            $inputs['class_date'] = '';

            foreach ($classSchedulesBooking as $booking) {

                $booking = !empty($booking) ? $booking->toArray() : [];
                if (!empty($booking)) {
                    $inputs['customer_id'] = $booking['customer_id'];
                    // Refund Code
                    // TODO:: need to improve this logic after demo.
                    // incase if one booking gives error for some reason the others
                    //  won't receive refunds
                    if (!empty($booking['purchased_package_uuid'])) {
                        // get purchase
                        $purchasePackageId = \App\PurchasesPackage::where('purchased_packages_uuid', $booking['purchased_package_uuid'])->first()->id;
                        $purchase = \App\Purchases::where('purchased_package_id', $purchasePackageId)->with('purchasesTransition')->first();
                        $booking['purchase'] = !empty($purchase) ? $purchase->toArray() : null;
                    }
                    $processRefunds = self::processRefunds($booking, $inputs);
                    if (isset($processRefunds['success']) && $processRefunds['success'] == false) {
                        DB::rollBack();
                        return CommonHelper::jsonErrorResponse('Error occurred while refunding customer! please try again later');
                    }
                    $updateClassBooking = ClassBooking::updateClassBookings('id', $booking['id'], ["status" => "cancelled"]);
                    if (!$updateClassBooking) {
                        DB::rollBack();
                        return CommonHelper::jsonErrorResponse('Error occurred while changing booking status');
                    }
                    if ($booking['status'] == 'confirmed') {
                        ProcessNotificationHelper::sendClassBookingCancelledNotification($inputs);
                    }
                }
            }
        }


        DB::commit();

        //send mail to freelancer for class status cancelled
        if ($inputs['login_user_type'] == "customer" && $inputs['status'] == "cancelled" && $updateClassStatus) {
            $classid = CommonHelper::getClassUuidBYId($inputs['class_uuid']);
            $check = self::sendEmailtoFreelancerforStatusUpdate($classid, $inputs['class_schedule_id'], $inputs['logged_in_id'], $inputs['status']);
        }
        return CommonHelper::jsonSuccessResponseWithoutData(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['update_class_success']);
    }

    public static function updateClassTransctions($class_bookings, $inputs)
    {
        $transaction_inputs['cancelled_by'] = $inputs['login_user_type'] == 'freelancer' ? 'freelancer' : 'customer';
        $transaction_inputs['cancelled_on'] = date('Y-m-d h:i:s');
        $transaction_inputs['status'] = 'cancelled';

        foreach ($class_bookings as $booking) {
            if (isset($booking['transaction']['id'])) {
                FreelancerTransaction::where('id', $booking['transaction']['id'])->update($transaction_inputs);
            }
        }
    }

    public static function refundClassAmount($class_schedule, $inputs)
    {

        $appointment_time = $class_schedule['class_date'] . ' ' . $class_schedule['from_time'];
        $diff = AppointmentHelper::calculateDateTimeDiffence($appointment_time);
        if ($inputs['login_user_type'] == "freelancer" || strtolower($class_schedule['status']) == "pending" || $diff->y > 0 || $diff->m > 0 || $diff->d > 0 || $diff->h >= 24) {
            if (isset($class_schedule['class_bookings']) && count($class_schedule['class_bookings']) > 0) {
                foreach ($class_schedule['class_bookings'] as $booking_info) {
                    $amount = self::getTotalAmounttoPay($booking_info['transaction']);

                    $refundResponse = MoyasarHelper::refundPayment($booking_info['transaction']['transaction_id'], $amount['amount']);
                    if ($refundResponse['success'] && !empty($refundResponse['payment']) && $refundResponse['payment']->status == 'refunded') :
                        AppointmentHelper::managePaymentDuesAfterRefund($booking_info, 'class', 'full', $refundResponse['payment']->fee);
                    endif;
                    //                    $currency = $booking_info['transaction']['to_currency'];
                    //                    $pay_customer = HyperpayHelper::refundTransactionApi($booking_info['transaction']['transaction_id'], $amount['amount'], $currency);
                    //                    if (isset($pay_customer['referencedId'])) {
                    //                        AppointmentHelper::managePaymentDuesAfterRefund($booking_info, 'class', 'full');
                    //                    }
                }
                return true;
            }
        } elseif ($inputs['login_user_type'] == "customer" && strtolower($class_schedule['status']) == "confirmed" && $diff->y == 0 && $diff->m == 0 && $diff->d == 0 && ($diff->h < 24 || $diff->h > 6)) {
            if (isset($class_schedule['class_bookings']) && count($class_schedule['class_bookings']) > 0) {
                foreach ($class_schedule['class_bookings'] as $booking_info) {
                    $amount = self::getTotalAmounttoPay($booking_info['transaction']);
                    $currency = $booking_info['transaction']['to_currency'];
                    $amount_to_pay = $amount['amount'] / 2;

                    $refundResponse = MoyasarHelper::refundPayment($booking_info['transaction']['transaction_id'], $amount_to_pay);
                    if ($refundResponse['success'] && !empty($refundResponse['payment']) && $refundResponse['payment']->status == 'refunded') :
                        AppointmentHelper::managePaymentDuesAfterRefund($booking_info, 'class', 'partial', $refundResponse['payment']->fee);
                    endif;
                    //                    $pay_customer = HyperpayHelper::refundTransactionApi($booking_info['transaction']['transaction_id'], $amount_to_pay, $currency);
                    //                    if (isset($pay_customer['referencedId'])) {
                    //                        AppointmentHelper::managePaymentDuesAfterRefund($booking_info, 'class', 'partial');
                    //                    }
                }
                return true;
            }
        } else {
            if (isset($class_schedule['class_bookings']) && count($class_schedule['class_bookings']) > 0) {
                foreach ($class_schedule['class_bookings'] as $booking_info) {
                    AppointmentHelper::managePaymentDuesAfterRefund($booking_info, 'class', 'no');
                }
                return true;
            }
        }
        return false;
    }

    public static function getTotalAmountToPay($transaction)
    {
        $data = [];
        if (!empty($transaction)) {
            $data['circl_charges'] = $transaction['circl_charges'];
            $data['hyperpay_fee'] = $transaction['hyperpay_fee'];
            $data['total_deductions'] = $data['circl_charges'] + $data['hyperpay_fee'];
            $data['amount'] = $transaction['total_amount'] - $data['total_deductions'];
        }
        return $data;
    }

    //send email to freelancer
    public static function sendEmailtoFreelancerforStatusUpdate($class_uuid, $class_schedule_uuid, $customer_uuid, $status)
    {

        $get_class_booking_details = ClassBooking::getParticularClassBooking($class_uuid, $class_schedule_uuid, $status);

        $message = "Your Class has been " . ucwords($status);
        $template = "add_class";
        $subject = "Circl - Class " . ucwords($status);
        $send = false;
        if (!empty($get_class_booking_details['class_object']['freelancer'])) {
            $get_class_booking_details['email'] = $get_class_booking_details['class_object']['freelancer']['email'];
            $get_class_booking_details['send_to'] = "freelancer";
            $get_class_booking_details['update_status'] = "1";
            if (!empty($get_class_booking_details['email'])) {
                $send = EmailSendingHelper::sendClassBookingEmail($get_class_booking_details, $message, $status, $template, $subject);
            }
        }
        return ($send) ? true : false;
    }

    public static function deleteClass($inputs = [])
    {
        $validation = Validator::make($inputs, ClassValidationHelper::deleteClassRules()['rules'], ClassValidationHelper::deleteClassRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['login_user_type'] != 'freelancer') {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_access']);
        }
        $class_detail = Classes::getSingleClass('class_uuid', $inputs['class_uuid']);
        $freelanceUUid = CommonHelper::getRecordByUuid('freelancers', 'id', $class_detail['freelancer_id'], 'freelancer_uuid');
        if (empty($class_detail)) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_class_uuid']);
        } elseif ($class_detail['end_date'] < date('Y-m-d')) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['class_date_pass']);
        } elseif ($inputs['logged_in_uuid'] != $freelanceUUid) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
        }
        if (!empty($class_detail['schedule'])) {
            $check_existing_booking = ClassHelper::checkClassBookingExists($class_detail, $inputs);
            if (!$check_existing_booking['success']) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['existing_booking_error']);
            }
        }
        //        $class_schedule = ClassSchedule::getClassSchedule('class_schedule_uuid', $inputs['class_schedule_uuid']);
        //        if (empty($class_schedule)) {
        //            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_schedule_uuid']);
        //        } elseif ($class_schedule['class_date'] < date('Y-m-d')) {
        //            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['class_date_pass']);
        //        } elseif ($class_schedule['class_date'] == date('Y-m-d') && $class_schedule['from_time'] <= date('H:i:s')) {
        //            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['class_time_pass']);
        //        } elseif (!empty($class_schedule['class_bookings'])) {
        //            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['existing_booking_error']);
        //        }
        //        $delete_schedule = ClassSchedule::deleteClassSchedule('class_schedule_uuid', $inputs['class_schedule_uuid']);
        //        if (!$delete_schedule) {
        //            DB::rollBack();
        //            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['delete_schedule_error']);
        //        }
        $delete_schedule = ClassHelper::deleteClassSchedules($class_detail, $inputs);

        if (!$delete_schedule['success']) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse($delete_schedule['message']);
        }
        $update_inputs = ['class_uuid' => $inputs['class_uuid'], 'is_archive' => 1];
        $delete_class = Classes::updateClass('class_uuid', $inputs['class_uuid'], $update_inputs);
        if (empty($delete_class)) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['delete_class_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponseWithoutData(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['delete_class_success']);
    }

    public static function deleteClassSchedules($class, $inputs = [])
    {
        $response = ['success' => true, 'message' => 'Request successful', 'data' => []];
        if (!empty($class['schedule'])) {
            foreach ($class['schedule'] as $single_schedule) {
                $update_inputs = ['class_schedule_uuid' => $single_schedule['class_schedule_uuid'], 'is_archive' => 1];
                $update_schedule = ClassSchedule::updateClassSchedule('class_schedule_uuid', $single_schedule['class_schedule_uuid'], $update_inputs);
                if (!$update_schedule) {
                    return ['success' => false, 'message' => ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['delete_schedule_error']];
                }
            }
        }
        return $response;
    }

    public static function updateClass($inputs = [])
    {
        $validation = Validator::make($inputs, ClassValidationHelper::freelancerUpdateClassRules()['rules'], ClassValidationHelper::freelancerUpdateClassRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['login_user_type'] != 'freelancer') {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_access']);
        }
        $class_detail = Classes::getSingleClass('class_uuid', $inputs['class_uuid']);
        $freelanceUUid = CommonHelper::getRecordByUuid('freelancers', 'id', $class_detail['freelancer_id'], 'freelancer_uuid');
        if (empty($class_detail)) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_class_uuid']);
        } elseif ($inputs['logged_in_uuid'] != $freelanceUUid) {
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['invalid_access']);
        }

        $freelancer_class_data_inputs = ClassDataHelper::makeFreelancerUpdateClassArray($class_detail, $inputs);

        if (!$freelancer_class_data_inputs['success']) {
            return CommonHelper::jsonErrorResponse($freelancer_class_data_inputs['message']);
        }
        $freelancer_class_data = $freelancer_class_data_inputs['data'];
        if (!empty($inputs['image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['image'], CommonHelper::$s3_image_paths['class_images']);
            $freelancer_class_data['image'] = $inputs['image'];
        }
        if (!empty($inputs['description_video'])) {
            if (empty($inputs['description_video_thumbnail'])) {
                return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['add_class_error']);
            }
            MediaUploadHelper::moveSingleS3Image($inputs['description_video'], CommonHelper::$s3_image_paths['class_description_video']);
            MediaUploadHelper::moveSingleS3Image($inputs['description_video_thumbnail'], CommonHelper::$s3_image_paths['class_description_video']);
            $freelancer_class_data['description_video'] = $inputs['description_video'];
            $freelancer_class_data['description_video_thumbnail'] = $inputs['description_video_thumbnail'];
        }
        $update_class_resp = Classes::updateClass('class_uuid', $inputs['class_uuid'], $freelancer_class_data);
        if (empty($update_class_resp)) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['update_class_error']);
        }
        if (!empty($class_detail['schedule']) && !empty($inputs['online_link'])) {
            foreach ($class_detail['schedule'] as $schedule) {
                $schedule_inputs['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                $schedule_inputs['online_link'] = $inputs['online_link'];
                $update_schedule = ClassSchedule::updateClassSchedule('class_schedule_uuid', $schedule['class_schedule_uuid'], $schedule_inputs);
                if (!$update_schedule) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(ClassValidationHelper::freelancerAddClassRules()['message_' . strtolower($inputs['lang'])]['update_class_error']);
                }
            }
        }
        DB::commit();
        $class = Classes::getClassDetail('class_uuid', $inputs['class_uuid']);
        $response = ClassResponseHelper::classDetailsResponse($class, $inputs['local_timezone'], $inputs);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    //    public static function freelancerUpdateClassProcess($inputs, $class) {
    //        $class_schedules_data = [];
    //        foreach ($inputs['class_timing'] as $day_schedule) {
    //            $result_array = self::classDayScheduleUpdateArray($class_schedules_data, $day_schedule, $class, $inputs);
    //            if (!$result_array['success']) {
    //                return ['success' => false, 'message' => $result_array['message']];
    //            }
    //            $class_schedules_data = $result_array['data'];
    //        }
    //        $result = ClassSchedule::where('class_uuid', $inputs['class_uuid'])->delete();
    //        $add_class_schedule_resp = ClassSchedule::saveClassSchedule($class_schedules_data);
    //        if (!$add_class_schedule_resp) {
    //            return ['success' => false, 'message' => MessageHelper::getMessageData('error', $inputs['lang'])['success_error']];
    //        }
    //        return ['success' => true, 'message' => FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']];
    //    }
    //
    //    public static function classDayScheduleUpdateArray($class_schedules_data, $day_schedule, $class, $inputs) {
    //        $index = count($class_schedules_data);
    //        $schedule_date = $day_schedule['date'];
    //        $schedule_date;
    //        while ($schedule_date >= $class['start_date'] && $schedule_date <= $class['end_date']) {
    //            $class_array = ['freelancer_uuid' => $inputs['freelancer_uuid'], 'date' => $schedule_date, 'from_time' => $day_schedule['start_time'], 'to_time' => $day_schedule['end_time']];
    //            $apply_checks = self::applyAddClassChecks($class_array);
    //            if (!$apply_checks['success']) {
    //                return ['success' => false, 'message' => $apply_checks['message']];
    //            }
    //            $class_schedules_data[$index]['class_schedule_uuid'] = UuidHelper::generateUniqueUUID("class_schedules", "class_schedule_uuid");
    //            $class_schedules_data[$index]['class_uuid'] = $class['class_uuid'];
    //            $class_schedules_data[$index]['class_date'] = !empty($schedule_date) ? $schedule_date : null;
    //            $class_schedules_data[$index]['from_time'] = !empty($day_schedule['start_time']) ? $day_schedule['start_time'] : null;
    //            $class_schedules_data[$index]['to_time'] = !empty($day_schedule['end_time']) ? $day_schedule['end_time'] : null;
    //            $class_schedules_data[$index]['schedule_type'] = $day_schedule['schedule_type'];
    //            $class_schedules_data[$index]['online_link'] = !empty($inputs['online_link']) ? $inputs['online_link'] : null;
    //            $validation = Validator::make($class_schedules_data[$index], ClassValidationHelper::freelancerClassScheduleRules()['rules'], ClassValidationHelper::freelancerClassScheduleRules()['message_' . strtolower($inputs['lang'])]);
    //            if ($validation->fails()) {
    //                return ['success' => false, 'message' => $validation->errors()->first()];
    //            }
    //            $index++;
    //            $schedule_date = self::increaseDate($schedule_date, $day_schedule['schedule_type']);
    //        }
    //        return ['success' => true, 'message' => 'Success', 'data' => $class_schedules_data];
    //    }

    public static function getAvailableClasses($inputs)
    {
        $validation = Validator::make($inputs, ClassValidationHelper::getAvailableClassesRule()['rules'], ClassValidationHelper::getAvailableClassesRule()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $date = date('Y-m-d H:i:s');

        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $class_list_response = Classes::getFreelancerUpcomingClasses('freelancer_id', $inputs['freelancer_id'], $date);

        if (empty($class_list_response)) {
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        $response = ClassResponseHelper::getAvailableClassesResponse($class_list_response, $inputs['currency']);

        $pureClass = self::checkClassSchdules($response);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $pureClass);
    }

    public static function checkClassSchdules($classes)
    {
        $response = [];
        foreach ($classes as $class) {
            if (!empty($class['upcoming_schedules'])) {
                $response[] = $class;
            }
        }
        return $response;
    }

    public static function searchClasses($inputs = [])
    {
        $class_response = [];

        if (empty($inputs['freelancer_uuid'])) {
            if ($inputs['login_user_type'] == 'freelancer') {
                $inputs['freelancer_uuid'] = $inputs['logged_in_uuid'];
            }
        }

        if (!empty($inputs['freelancer_uuid'])) {
            $class_response = Classes::searchClasses('freelancer_id', $inputs['freelancer_id'], $inputs['search_params'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        }
        if (!empty($class_response)) {
            return ClassResponseHelper::freelancerClassesResponseAsAppointment($class_response, $inputs['local_timezone']);
        }
        return $class_response;
    }

    public static function checkClassBookingExists($class = [], $inputs = [])
    {
        $response = ['success' => true, 'message' => 'You are good to go'];

        if (!empty($class) && !empty($class['schedule'])) {

            foreach ($class['schedule'] as $single_schedule) {

                if (!empty($single_schedule['class_bookings']) && $single_schedule['status'] != "cancelled") {

                    foreach ($single_schedule['class_bookings'] as $key => $booking) {
                        if ($booking['status'] != "cancelled") {
                            $response = ['success' => false, 'message' => ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['existing_booking_error']];
                            break;
                        }
                    }
                }
            }
        }
        return $response;
    }

    public static function getUpcomingClassSchedules($inputs)
    {
        $validation = Validator::make($inputs, ClassValidationHelper::getAvailableClassSchedulesRule()['rules'], ClassValidationHelper::getAvailableClassSchedulesRule()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $limit = !empty($inputs['limit']) ? $inputs['limit'] : null;
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : null;
        $data_filters = ['local_currency' => $inputs['currency'], 'limit' => $limit, 'offset' => $offset, 'local_timezone' => $inputs['local_timezone']];
        if (strtolower($inputs['login_user_type']) == 'freelancer') {
            $inputs['profile_id'] = CommonHelper::getFreelancerIdByUuid($inputs['profile_uuid']);

            return ClassHelper::getFreelancerUpcomingClassSchedules($inputs, $data_filters);
        } elseif (strtolower($inputs['login_user_type']) == 'customer') {
            return ClassHelper::getCustomerUpcomingClassBookings($inputs, $data_filters);
        }
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
    }

    public static function getFreelancerUpcomingClassSchedules($inputs = [], $data_filters = [])
    {
        $date = date('Y-m-d');

        $upcoming_classes = Classes::getFreelancerUpcomingClasses('freelancer_id', $inputs['profile_id'], $date);

        $response = ClassResponseHelper::upcomingClassesResponseAsAppointment($upcoming_classes, $inputs['local_timezone'], $data_filters);
        usort($response, function ($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t1 - $t2;
        });
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getCustomerUpcomingClassBookings($inputs = [], $data_filters = [])
    {
        $customer = Customer::getSingleCustomerDetail('customer_uuid', $inputs['profile_uuid']);

        if (empty($customer)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('success', $inputs['lang'])['invalid_data']);
        }
        $inputs['customer_id'] = $customer['id'];
        $pending_booked_classes = ClassBooking::pluckClassBookingIds('customer_id', $inputs['customer_id'], 'class_schedule_id', 'pending');
        $get_pending_schedules = ClassSchedule::getMultipleSchedules($pending_booked_classes, 'pending');
        $pending_upcomming_classes = AppointmentResponseHelper::customerUpcomingClassAppointmentsResponse($get_pending_schedules, $inputs['local_timezone'], $customer, 'pending');
        $confirmed_booked_classes = ClassBooking::pluckClassBookingIds('customer_id', $inputs['customer_id'], 'class_schedule_id', 'confirmed');
        $get_confirmed_schedules = ClassSchedule::getMultipleSchedules($confirmed_booked_classes, 'confirmed');
        $confirmed_upcomming_classes = AppointmentResponseHelper::customerUpcomingClassAppointmentsResponse($get_confirmed_schedules, $inputs['local_timezone'], $customer, 'confirmed');
        if (empty($pending_upcomming_classes)) {
            $pending_upcomming_classes = [];
        }
        if (empty($confirmed_upcomming_classes)) {
            $confirmed_upcomming_classes = [];
        }
        $upcomming_classes = array_merge($pending_upcomming_classes, $confirmed_upcomming_classes);
        usort($upcomming_classes, function ($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t1 - $t2;
        });
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $upcomming_classes);
    }

    public static function processRefunds($class, $inputs)
    {
        $response = [];
        $response['status'] = 'Refunded';
        $purchaseData = true;
        $createEarning = true;
        $updateEarning = true;
        $purchaseTransactionData = true;
        $purchaseStatus = self::preparePurchaseStatusToUpdate($class);
        if ($inputs['login_user_type'] == 'customer') {
            // get refund type
            $class['appointment_start_date_time'] = $class['schedule']['start_date_time'];
            $class['freelancer_id'] = $class['purchase']['freelancer_id'];
            $refundType = AfterPayment\StatusConditions::getRefundType($class);
            // calculate customer refund and freelancer earning
            $calculatedRefundAndEarning = AfterPayment\StatusConditions::getRefundAndEarningAfterCalculations($class, $refundType);
            if ($calculatedRefundAndEarning['customer_refund'] > 0) {
                // call to checkout to refund customer
                $class['paid_amount'] = $calculatedRefundAndEarning['customer_refund'];
                $response = Checkout::appointmentFullRefund($class, $inputs['status']);
            }
            //            if ((!empty($class['purchased_package_uuid'])) && (!empty($class['purchase']['purchased_package_id']))) {
            //                $updateEarning = \App\FreelancerEarning::updateData('purchased_package_id', $class['purchase']['purchased_package_id'], ['is_archive' => 1]);
            //            } else {
            //                $updateEarning = \App\FreelancerEarning::updateData('class_booking_id', $class['id'], ['is_archive' => 1]);
            //            }

            if ($calculatedRefundAndEarning['freelancer_earning'] >= 0 && empty($class['purchased_package_uuid'])) {

                $getEarning = FreelancerEarning::where('class_booking_id', $class['id'])->where('is_archive', 0)->orderBy('id', 'desc')->first();
                $updateEarning = FreelancerEarning::updateData('freelancer_earnings_uuid', $getEarning['freelancer_earnings_uuid'], ['is_archive' => 1]);
                // Add to Freelancer Earning but before delete previous earning because
                // customer has cancelled the booking
                $class['freelancer_earning'] = $calculatedRefundAndEarning['freelancer_earning'];
                if ($calculatedRefundAndEarning['freelancer_earning'] > 0) {
                    $records = self::freelancerEarningForBooking($class);
                    $createEarning = FreelancerEarning::createRecords($records);
                }
            }
            if ($calculatedRefundAndEarning['freelancer_earning'] >= 0 && !empty($class['purchased_package_uuid'])) {

                $getEarning = FreelancerEarning::where('class_booking_id', $class['id'])->where('is_archive', 0)->orderBy('id', 'desc')->first();
                $updateEarning = FreelancerEarning::updateData('freelancer_earnings_uuid', $getEarning['freelancer_earnings_uuid'], ['is_archive' => 1]);

                // Add to Freelancer Earning but before delete previous earning because
                // customer has cancelled the booking
                if ($calculatedRefundAndEarning['freelancer_earning'] > 0) {
                    $class['purchased_package_id'] = $class['purchase']['purchased_package_id'];
                    $class['freelancer_earning'] = $calculatedRefundAndEarning['freelancer_earning'];
                    $records = self::freelancerEarningForBooking($class);
                    $createEarning = FreelancerEarning::createRecords($records);
                }
            }
            //            if ($calculatedRefundAndEarning['freelancer_earning'] > 0) {
            //
            //                if (!empty($class['purchased_package_uuid'])) {
            //                    $getEarning = FreelancerEarning::where('appointment_id', $appointment['id'])->where('is_archive', 0)->orderBy('id', 'desc')->first();
            //                    $updateEarning = FreelancerEarning::updateData('freelancer_earnings_uuid', $getEarning['freelancer_earnings_uuid'], ['is_archive' => 1]);
            //                    $calculatedRefundAndEarning['freelancer_earning'] = $class['package_paid_amount'] - $calculatedRefundAndEarning['freelancer_earning'];
            //                }
            //                $class['freelancer_earning'] = $calculatedRefundAndEarning['freelancer_earning'];
            //                $records = self::freelancerEarningForBooking($class);
            //                $createEarning = \App\FreelancerEarning::createRecords($records);
            //            }
            $params = self::purchaseTrasactionParams($class, $class['paid_amount'], $response);
            if (!empty($class['purchased_package_uuid'])) {
                $purchaseStatus = ($purchaseStatus == 'cancelled') ? $response['status'] : $class['purchase']['status'];
                $purchaseData = \App\Purchases::where('id', $params['purchase_id'])->update(['status' => strtolower($purchaseStatus)]);
            } else {
                $purchaseData = \App\Purchases::where('id', $params['purchase_id'])->update(['status' => strtolower($response['status'])]);
            }
            $purchaseTransactionData = \App\PurchasesTransition::createPurchase($params);
            if (((isset($response['success'])) && ($response['success'] == false)) || (!$purchaseData) || (!$purchaseTransactionData) ||
                (!$updateEarning)
            ) {
                DB::rollback();
                return ['success' => false, 'message' => 'Error occured while processing payment'];
            }
            return ['success' => true, 'message' => 'Successful Request'];
        } elseif ($inputs['login_user_type'] == 'freelancer') {

            $response = Checkout::appointmentFullRefund($class, $inputs['status']);
            $updateEarning = \App\FreelancerEarning::updateData('class_booking_id', $class['id'], ['is_archive' => 1]);
            $params = self::purchaseTrasactionParams($class, $class['paid_amount'], $response);
            if (!empty($class['purchased_package_uuid'])) {
                $purchaseStatus = ($purchaseStatus == 'cancelled') ? $response['status'] : $class['purchase']['status'];
                $purchaseData = \App\Purchases::where('id', $params['purchase_id'])->update(['status' => strtolower($purchaseStatus)]);
            } else {
                $purchaseData = \App\Purchases::where('id', $params['purchase_id'])->update(['status' => strtolower($response['status'])]);
            }
            $purchaseTransactionData = \App\PurchasesTransition::createPurchase($params);
            if (((isset($response['success'])) && ($response['success'] == false)) || (!$purchaseData) || (!$purchaseTransactionData) ||
                (!$updateEarning)
            ) {
                DB::rollback();
                return ['success' => false, 'message' => 'Error occured while processing payment'];
            }
            return ['success' => true, 'message' => 'Successful Request'];
        }
    }

    public static function freelancerEarningForBooking($booking)
    {
        $circlFeeAmount = \App\Helpers\BankResponseHelper::prepareCirclFeeAmount($booking['purchase']);
        $transactionCharges = \App\Helpers\BankResponseHelper::prepareTransactionChargesAmount($booking['purchase']);
        return [
            'freelancer_earnings_uuid' => UuidHelper::generateUniqueUUID('freelancer_earnings', 'freelancer_earnings_uuid'),
            'freelancer_id' => $booking['purchase']['freelancer_id'],
            'earned_amount' => $booking['paid_amount'] - $circlFeeAmount - $transactionCharges,
            'purchase_id' => $booking['purchase']['id'],
            'subscription_id' => null,
            'purchased_package_id' => ((isset($booking['purchase']['purchased_package_id'])) && (!empty($booking['purchase']['purchased_package_id']))) ? $booking['purchase']['purchased_package_id'] : null,
            'class_booking_id' => $booking['id'],
            'appointment_id' => null,
            //TODO: change this date from 0 and then test again
            'amount_due_on' => strtotime(date('Y-m-d H:i:s')),
            'currency' => $booking['currency']
        ];
    }

    public static function purchaseTrasactionParams($appointment, $amount, $response)
    {
        return [
            'purchase_id' => $appointment['purchase']['id'],
            'currency' => !empty($appointment['purchase']['purchases_transition']) ? $appointment['purchase']['purchases_transition']['currency'] : $appointment['purchase']['purchased_in_currency'],
            'amount' => $amount,
            'transaction_type' => 'refund',
            'gateway_response' => !empty($response) ? serialize($response) : null,
            'request_parameters',
            'transaction_status' => 'cancelled',
            'checkout_transaction_id' => !empty($appointment['purchase']['purchases_transition']) ? $appointment['purchase']['purchases_transition']['checkout_transaction_id'] : null,
            'class_booking_id' => $appointment['id'],
            'appointment_booking_id' => null,
            'cko_id' => !empty($appointment['purchase']['purchases_transition']) ? $appointment['purchase']['purchases_transition']['cko_id'] : null,
            'customer_card_id' => !empty($appointment['purchase']['purchases_transition']) ? $appointment['purchase']['purchases_transition']['customer_card_id'] : null,
        ];
    }

    public static function preparePurchaseStatusToUpdate($booking)
    {
        $status = 'cancelled';
        $bookingCount = 0;
        if (!empty($booking['purchased_package_uuid'])) {
            // get all package bookings and check their status
            $packageBookings = ClassBooking::getPackageClass($booking['purchased_package_uuid']);
            if (!empty($packageBookings)) {
                foreach ($packageBookings as $key => $classBooking) {
                    if ($classBooking['status'] == 'confirmed') {
                        $bookingCount++;
                    }
                }
                if ($bookingCount <= 1) {
                    $status = 'cancelled';
                } else {
                    $status = 'confirmed';
                }
            }
        }
        return $status;
    }
}
